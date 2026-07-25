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

    // ─── Lookup koordinat kota (hardcoded — tanpa Geocoding API) ───
    $cityCoords = [
        // Jabodetabek & Banten
        'Jakarta Pusat' => [-6.1862, 106.8340], 'Jakarta Utara' => [-6.1384, 106.8638],
        'Jakarta Barat' => [-6.1683, 106.7588], 'Jakarta Selatan' => [-6.2615, 106.8106],
        'Jakarta Timur' => [-6.2250, 106.9004], 'Tangerang' => [-6.1702, 106.6403],
        'Tangerang Selatan' => [-6.2886, 106.7177], 'Bekasi' => [-6.2383, 106.9756],
        'Depok' => [-6.4025, 106.7942], 'Bogor' => [-6.5971, 106.8060],
        'Cilegon' => [-6.0023, 106.0507], 'Serang' => [-6.1103, 106.1640],
        'Lebak' => [-6.5645, 106.2522], 'Pandeglang' => [-6.3089, 106.1053],
        // Jawa Barat
        'Bandung' => [-6.9175, 107.6191], 'Cimahi' => [-6.8842, 107.5413],
        'Karawang' => [-6.3227, 107.3376], 'Purwakarta' => [-6.5569, 107.4462],
        'Subang' => [-6.5714, 107.7522], 'Sukabumi' => [-6.9277, 106.9300],
        'Cianjur' => [-6.7340, 107.0428], 'Garut' => [-7.2167, 107.9008],
        'Tasikmalaya' => [-7.3274, 108.2207], 'Cirebon' => [-6.7320, 108.5523],
        'Indramayu' => [-6.3276, 108.3247], 'Majalengka' => [-6.8362, 108.2275],
        'Kuningan' => [-6.9756, 108.4836], 'Sumedang' => [-6.8563, 107.9191],
        'Banjar' => [-7.3715, 108.5357],
        // Jawa Tengah
        'Semarang' => [-6.9667, 110.4196], 'Solo' => [-7.5755, 110.8243],
        'Salatiga' => [-7.3306, 110.5084], 'Magelang' => [-7.4797, 110.2177],
        'Pekalongan' => [-6.8886, 109.6753], 'Tegal' => [-6.8797, 109.1256],
        'Brebes' => [-6.8713, 109.0399], 'Cilacap' => [-7.7268, 109.0154],
        'Purwokerto' => [-7.4214, 109.2342], 'Kebumen' => [-7.6666, 109.6522],
        'Kudus' => [-6.8048, 110.8405], 'Demak' => [-6.8937, 110.6372],
        'Jepara' => [-6.5936, 110.6717], 'Klaten' => [-7.7056, 110.6042],
        'Boyolali' => [-7.5323, 110.5956], 'Karanganyar' => [-7.6003, 110.9581],
        'Wonogiri' => [-7.8149, 110.9222], 'Blora' => [-6.9666, 111.4112],
        'Rembang' => [-6.7073, 111.3468], 'Kendal' => [-6.9186, 110.2031],
        'Batang' => [-6.9044, 109.7253], 'Pemalang' => [-6.8912, 109.3813],
        // Jawa Timur
        'Surabaya' => [-7.2575, 112.7521], 'Malang' => [-7.9666, 112.6326],
        'Sidoarjo' => [-7.4478, 112.7183], 'Gresik' => [-7.1625, 112.6531],
        'Mojokerto' => [-7.4704, 112.4401], 'Pasuruan' => [-7.6469, 112.9075],
        'Probolinggo' => [-7.7543, 113.2159], 'Lumajang' => [-8.1349, 113.2246],
        'Jember' => [-8.1845, 113.6681], 'Banyuwangi' => [-8.2193, 114.3691],
        'Kediri' => [-7.8167, 112.0167], 'Blitar' => [-8.0983, 112.1681],
        'Tulungagung' => [-8.0654, 111.9024], 'Nganjuk' => [-7.6052, 111.8973],
        'Madiun' => [-7.6298, 111.5300], 'Ponorogo' => [-7.8684, 111.4632],
        'Lamongan' => [-7.1189, 112.4175], 'Tuban' => [-6.8987, 112.0497],
        'Bojonegoro' => [-7.1501, 111.8819],
        // DIY
        'Yogyakarta' => [-7.7956, 110.3695], 'Sleman' => [-7.7166, 110.3558],
        'Bantul' => [-7.8880, 110.3275],
        // Bali & NTT
        'Denpasar' => [-8.6500, 115.2167], 'Badung' => [-8.5819, 115.1770],
        'Mataram' => [-8.5833, 116.1167], 'Kupang' => [-10.1787, 123.6070],
        // Sumatera
        'Medan' => [3.5952, 98.6722], 'Pekanbaru' => [0.5071, 101.4478],
        'Padang' => [-0.9471, 100.4172], 'Palembang' => [-2.9761, 104.7754],
        'Bandar Lampung' => [-5.3971, 105.2668], 'Batam' => [1.0456, 104.0305],
        'Jambi' => [-1.6101, 103.6131], 'Bengkulu' => [-3.7928, 102.2608],
        'Banda Aceh' => [5.5483, 95.3238],
        // Kalimantan
        'Pontianak' => [-0.0263, 109.3425], 'Banjarmasin' => [-3.3186, 114.5944],
        'Balikpapan' => [-1.2654, 116.8312], 'Samarinda' => [-0.4948, 117.1436],
        'Palangka Raya' => [-2.2136, 113.9108],
        // Sulawesi
        'Makassar' => [-5.1477, 119.4327], 'Manado' => [1.4748, 124.8421],
        'Palu' => [-0.9003, 119.8779], 'Kendari' => [-3.9985, 122.5130],
        'Gorontalo' => [0.5435, 123.0568],
        // Maluku & Papua
        'Ambon' => [-3.6954, 128.1814], 'Jayapura' => [-2.5337, 140.7181],
        'Sorong' => [-0.8618, 131.2869],
    ];

    $coordKey = $city;
    if (!isset($cityCoords[$coordKey])) {
        echo json_encode(['error' => true, 'message' => 'Koordinat kota "' . htmlspecialchars($city) . '" belum tersedia. Hubungi developer untuk menambahkan.']);
        exit;
    }

    $lat = $cityCoords[$coordKey][0];
    $lng = $cityCoords[$coordKey][1];

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
