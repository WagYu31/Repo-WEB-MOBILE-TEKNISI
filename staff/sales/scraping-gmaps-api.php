<?php
/**
 * Google Maps Scraping — API Proxy
 * 
 * Server-side proxy untuk Google Maps Places API (New).
 * API Key tersimpan aman di server — tidak terekspos ke browser.
 * 
 * Endpoint:
 *   POST scraping-gmaps-api.php
 *   Body: { action: "search", keyword: "Toko CCTV", city: "Tangerang", radius: 25000 }
 *   Body: { action: "stats" }  // Get usage stats
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conn.php';
require_once 'gmaps-config.php';

// Pastikan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? 'search';

// ══════════════════════════════════════════════════
// ACTION: Get Usage Stats
// ══════════════════════════════════════════════════
if ($action === 'stats') {
    echo json_encode(['error' => false, 'stats' => gmaps_get_stats()]);
    exit;
}

// ══════════════════════════════════════════════════
// ACTION: Search Google Maps
// ══════════════════════════════════════════════════
if ($action === 'search') {
    $keyword = trim($input['keyword'] ?? $_POST['keyword'] ?? '');
    $city    = trim($input['city'] ?? $_POST['city'] ?? '');
    $radius  = intval($input['radius'] ?? $_POST['radius'] ?? 25000);

    if (empty($keyword) || empty($city)) {
        echo json_encode(['error' => true, 'message' => 'Kata kunci dan kota wajib diisi']);
        exit;
    }

    // ─── Cek Rate Limit ─────────────────────────────
    $limit = gmaps_check_limit();
    if (!$limit['allowed']) {
        echo json_encode([
            'error'   => true,
            'blocked' => true,
            'message' => $limit['reason'],
            'stats'   => gmaps_get_stats(),
        ]);
        exit;
    }

    // ─── Geocode kota untuk mendapatkan koordinat ───
    $geoUrl = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $city . ', Indonesia',
        'key'     => GMAPS_API_KEY,
    ]);

    $geoResponse = @file_get_contents($geoUrl);
    if ($geoResponse === false) {
        echo json_encode(['error' => true, 'message' => 'Gagal menghubungi Google Geocoding API. Periksa koneksi server.']);
        exit;
    }

    $geoData = json_decode($geoResponse, true);
    if (empty($geoData['results'])) {
        echo json_encode(['error' => true, 'message' => 'Kota "' . htmlspecialchars($city) . '" tidak ditemukan di Google Maps']);
        exit;
    }

    $lat = $geoData['results'][0]['geometry']['location']['lat'];
    $lng = $geoData['results'][0]['geometry']['location']['lng'];

    // ─── Places API (New) — Text Search ─────────────
    $searchQuery = $keyword . ' di ' . $city;
    
    $placesUrl = 'https://places.googleapis.com/v1/places:searchText';
    
    $requestBody = json_encode([
        'textQuery'         => $searchQuery,
        'locationBias'      => [
            'circle' => [
                'center'  => ['latitude' => $lat, 'longitude' => $lng],
                'radius'  => min($radius, 50000), // max 50km
            ]
        ],
        'maxResultCount'    => 20,
        'languageCode'      => 'id',
    ]);

    // Field mask — hanya ambil field yang dibutuhkan (hemat biaya!)
    $fieldMask = implode(',', [
        'places.id',
        'places.displayName',
        'places.formattedAddress',
        'places.nationalPhoneNumber',
        'places.internationalPhoneNumber',
        'places.websiteUri',
        'places.googleMapsUri',
        'places.rating',
        'places.userRatingCount',
        'places.photos',
        'places.location',
        'places.businessStatus',
        'places.types',
        'places.primaryType',
    ]);

    $ch = curl_init($placesUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $requestBody,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . GMAPS_API_KEY,
            'X-Goog-FieldMask: ' . $fieldMask,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['error' => true, 'message' => 'Gagal menghubungi Google Places API: ' . $curlError]);
        exit;
    }

    // Increment counter setelah request berhasil terkirim ke Google
    gmaps_increment_usage();

    $data = json_decode($response, true);

    // Handle API error
    if ($httpCode !== 200) {
        $errorMsg = $data['error']['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')';
        echo json_encode([
            'error'   => true,
            'message' => 'Google API Error: ' . $errorMsg,
            'stats'   => gmaps_get_stats(),
        ]);
        exit;
    }

    $places = $data['places'] ?? [];
    $results = [];

    foreach ($places as $place) {
        // ─── Hitung Trust Score ──────────────────────
        $trustScore = 0;
        $reviewCount = $place['userRatingCount'] ?? 0;
        $rating     = $place['rating'] ?? 0;
        $hasPhone   = !empty($place['nationalPhoneNumber'] ?? $place['internationalPhoneNumber'] ?? '');
        $hasWebsite = !empty($place['websiteUri'] ?? '');
        $photoCount = count($place['photos'] ?? []);

        // Review (max 30 pts)
        if ($reviewCount >= 20) $trustScore += 30;
        elseif ($reviewCount >= 10) $trustScore += 20;
        elseif ($reviewCount >= 5) $trustScore += 10;

        // Foto (max 25 pts)
        if ($photoCount >= 5) $trustScore += 25;
        elseif ($photoCount >= 3) $trustScore += 20;
        elseif ($photoCount >= 1) $trustScore += 10;

        // Telepon (20 pts)
        if ($hasPhone) $trustScore += 20;

        // Website (15 pts)
        if ($hasWebsite) $trustScore += 15;

        // Rating (max 10 pts)
        if ($rating >= 4.0) $trustScore += 10;
        elseif ($rating >= 3.0) $trustScore += 5;

        // Trust level
        if ($trustScore >= 70) $trustLevel = 'terpercaya';
        elseif ($trustScore >= 40) $trustLevel = 'perlu_cek';
        else $trustLevel = 'berisiko';

        // Photo URL (ambil yang pertama)
        $photoRef = '';
        if (!empty($place['photos'][0]['name'])) {
            $photoRef = $place['photos'][0]['name'];
        }

        $results[] = [
            'place_id'     => $place['id'] ?? '',
            'name'         => $place['displayName']['text'] ?? '',
            'address'      => $place['formattedAddress'] ?? '',
            'phone'        => $place['nationalPhoneNumber'] ?? ($place['internationalPhoneNumber'] ?? ''),
            'website'      => $place['websiteUri'] ?? '',
            'maps_url'     => $place['googleMapsUri'] ?? '',
            'rating'       => $rating,
            'review_count' => $reviewCount,
            'photo_count'  => $photoCount,
            'photo_ref'    => $photoRef,
            'lat'          => $place['location']['latitude'] ?? 0,
            'lng'          => $place['location']['longitude'] ?? 0,
            'business_status' => $place['businessStatus'] ?? '',
            'types'        => $place['types'] ?? [],
            'primary_type' => $place['primaryType'] ?? '',
            'trust_score'  => $trustScore,
            'trust_level'  => $trustLevel,
        ];
    }

    // Sort by trust score descending
    usort($results, function($a, $b) {
        return $b['trust_score'] - $a['trust_score'];
    });

    echo json_encode([
        'error'        => false,
        'keyword'      => $keyword,
        'city'         => $city,
        'center'       => ['lat' => $lat, 'lng' => $lng],
        'radius'       => $radius,
        'total_results'=> count($results),
        'results'      => $results,
        'stats'        => gmaps_get_stats(),
    ]);
    exit;
}

// Action tidak dikenal
echo json_encode(['error' => true, 'message' => 'Action tidak valid: ' . htmlspecialchars($action)]);
?>
