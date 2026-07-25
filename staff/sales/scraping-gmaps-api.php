<?php
/**
 * Google Maps Scraping — Foursquare Places API (GRATIS 100K req/bulan)
 * 
 * Google Search tidak bisa di-scrape karena 100% JavaScript-rendered.
 * Foursquare Places API = FREE, no credit card, 100K requests/month.
 * Data: nama toko, alamat, telepon, rating, kategori, foto, dll.
 * 
 * Register gratis: https://foursquare.com/developers
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'conn.php';
require_once 'gmaps-config.php';
ob_clean();

// Foursquare API Key (FREE - 100K req/bulan)
// Register di: https://foursquare.com/developers → Create Project → Copy API Key
define('FSQ_API_KEY', defined('FOURSQUARE_API_KEY') ? FOURSQUARE_API_KEY : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'search';

if ($action === 'stats') {
    echo json_encode(['error' => false, 'stats' => gmaps_get_stats()]);
    exit;
}

if ($action === 'search') {
    $keyword = trim($input['keyword'] ?? '');
    $city    = trim($input['city'] ?? '');
    $radius  = intval($input['radius'] ?? 25000);

    if (empty($keyword) || empty($city)) {
        echo json_encode(['error' => true, 'message' => 'Kata kunci dan kota wajib diisi']);
        exit;
    }

    // Rate limit
    $limit = gmaps_check_limit();
    if (!$limit['allowed']) {
        echo json_encode(['error' => true, 'blocked' => true, 'message' => $limit['reason'], 'stats' => gmaps_get_stats()]);
        exit;
    }

    // Cache (7 hari)
    $cacheDir = __DIR__ . '/gmaps_cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
    $cacheKey = md5($keyword . '|' . $city . '|' . $radius);
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 604800) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && !empty($cached['results'])) {
            $cached['from_cache'] = true;
            $cached['stats'] = gmaps_get_stats();
            echo json_encode($cached);
            exit;
        }
    }

    // ═══ City coordinates lookup ═══
    $cityCoords = getCityCoordinates();
    $cityKey = strtolower(trim($city));
    
    $lat = null;
    $lng = null;
    foreach ($cityCoords as $name => $coords) {
        if (strtolower($name) === $cityKey || stripos($name, $city) !== false || stripos($city, $name) !== false) {
            $lat = $coords[0];
            $lng = $coords[1];
            break;
        }
    }

    if (!$lat || !$lng) {
        echo json_encode(['error' => true, 'message' => "Koordinat untuk kota '$city' tidak ditemukan"]);
        exit;
    }

    $apiKey = FSQ_API_KEY;
    if (empty($apiKey)) {
        echo json_encode([
            'error' => true, 
            'message' => 'Foursquare API Key belum dikonfigurasi. Register GRATIS di https://foursquare.com/developers lalu masukkan API Key di gmaps-config.php'
        ]);
        exit;
    }

    // ═══ Foursquare Places Search ═══
    $results = [];
    
    // Search up to 50 places
    $fsqUrl = 'https://api.foursquare.com/v3/places/search?' . http_build_query([
        'query'  => $keyword,
        'll'     => "$lat,$lng",
        'radius' => min($radius, 50000),
        'limit'  => 50,
        'fields' => 'fsq_id,name,location,tel,website,rating,stats,categories,photos,hours,verified,price,popularity,link',
        'sort'   => 'RELEVANCE',
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $fsqUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $apiKey,
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    gmaps_increment_usage();

    if ($response === false || $httpCode !== 200) {
        $errorMsg = 'Gagal mengakses Foursquare API. HTTP ' . $httpCode;
        if ($httpCode === 401) $errorMsg = 'API Key tidak valid. Cek kembali Foursquare API Key di gmaps-config.php';
        if ($httpCode === 429) $errorMsg = 'Rate limit tercapai. Coba lagi nanti.';
        if ($curlError) $errorMsg .= ': ' . $curlError;
        
        // Try parse error message
        $errData = json_decode($response, true);
        if (isset($errData['message'])) $errorMsg .= ' - ' . $errData['message'];
        
        echo json_encode(['error' => true, 'message' => $errorMsg, 'stats' => gmaps_get_stats()]);
        exit;
    }

    $data = json_decode($response, true);
    $places = $data['results'] ?? [];

    foreach ($places as $place) {
        $name = $place['name'] ?? '';
        if (empty($name)) continue;

        $address = '';
        if (isset($place['location'])) {
            $loc = $place['location'];
            $parts = array_filter([
                $loc['address'] ?? '',
                $loc['locality'] ?? '',
                $loc['region'] ?? '',
            ]);
            $address = implode(', ', $parts);
        }

        $phone = $place['tel'] ?? '';
        $website = $place['website'] ?? '';
        $rating = isset($place['rating']) ? round($place['rating'] / 2, 1) : 0; // FSQ uses 0-10, convert to 0-5
        $totalRatings = $place['stats']['total_ratings'] ?? 0;
        $totalPhotos = $place['stats']['total_photos'] ?? 0;
        $verified = $place['verified'] ?? false;

        // Category
        $category = '';
        if (!empty($place['categories'])) {
            $category = $place['categories'][0]['name'] ?? '';
        }

        // Photo URL
        $photoUrl = '';
        if (!empty($place['photos'])) {
            $photo = $place['photos'][0];
            $photoUrl = ($photo['prefix'] ?? '') . '200x200' . ($photo['suffix'] ?? '');
        }

        // Coordinates
        $placeLat = $place['location']['lat'] ?? $lat;
        $placeLng = $place['location']['lng'] ?? $lng;

        // Google Maps URL
        $mapsUrl = 'https://www.google.com/maps/search/' . urlencode($name . ' ' . $city);

        // Trust Score
        $trustScore = 10;
        if (!empty($address)) $trustScore += 15;
        if (!empty($phone)) $trustScore += 20;
        if (!empty($website)) $trustScore += 10;
        if ($totalRatings >= 20) $trustScore += 20;
        elseif ($totalRatings >= 10) $trustScore += 15;
        elseif ($totalRatings >= 5) $trustScore += 10;
        if ($rating >= 4.0) $trustScore += 15;
        elseif ($rating >= 3.0) $trustScore += 5;
        if ($totalPhotos >= 5) $trustScore += 5;
        if ($verified) $trustScore += 5;

        $trustLevel = 'berisiko';
        if ($trustScore >= 70) $trustLevel = 'terpercaya';
        elseif ($trustScore >= 40) $trustLevel = 'perlu_cek';

        $results[] = [
            'place_id'        => $place['fsq_id'] ?? md5($name . $city),
            'name'            => $name,
            'address'         => $address,
            'phone'           => $phone,
            'website'         => $website,
            'maps_url'        => $mapsUrl,
            'rating'          => $rating,
            'review_count'    => $totalRatings,
            'photo_count'     => $totalPhotos,
            'photo_ref'       => $photoUrl,
            'lat'             => $placeLat,
            'lng'             => $placeLng,
            'business_status' => $verified ? 'Verified' : '',
            'types'           => [$category],
            'primary_type'    => $category,
            'trust_score'     => $trustScore,
            'trust_level'     => $trustLevel,
        ];
    }

    // Sort by trust score
    usort($results, function($a, $b) { return $b['trust_score'] - $a['trust_score']; });

    $responseData = [
        'error'         => false,
        'keyword'       => $keyword,
        'city'          => $city,
        'total_results' => count($results),
        'results'       => $results,
        'scraped_at'    => date('Y-m-d H:i:s'),
        'from_cache'    => false,
        'source'        => 'Foursquare Places API',
    ];

    @file_put_contents($cacheFile, json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    $responseData['stats'] = gmaps_get_stats();
    echo json_encode($responseData);
    exit;
}

echo json_encode(['error' => true, 'message' => 'Action tidak valid']);

// ══════════════════════════════════════════════════
// CITY COORDINATES
// ══════════════════════════════════════════════════
function getCityCoordinates() {
    return [
        'Tangerang'       => [-6.1783, 106.6319],
        'Tangerang Selatan' => [-6.2886, 106.7183],
        'Jakarta Pusat'   => [-6.1862, 106.8345],
        'Jakarta Selatan' => [-6.2615, 106.8106],
        'Jakarta Barat'   => [-6.1484, 106.7558],
        'Jakarta Timur'   => [-6.2250, 106.9004],
        'Jakarta Utara'   => [-6.1380, 106.8631],
        'Bekasi'          => [-6.2383, 106.9756],
        'Bogor'           => [-6.5971, 106.8060],
        'Depok'           => [-6.4025, 106.7942],
        'Bandung'         => [-6.9175, 107.6191],
        'Surabaya'        => [-7.2575, 112.7521],
        'Semarang'        => [-6.9666, 110.4196],
        'Yogyakarta'      => [-7.7972, 110.3688],
        'Medan'           => [3.5952, 98.6722],
        'Makassar'        => [-5.1477, 119.4327],
        'Palembang'       => [-2.9761, 104.7754],
        'Denpasar'        => [-8.6500, 115.2167],
        'Malang'          => [-7.9666, 112.6326],
        'Surakarta'       => [-7.5755, 110.8243],
        'Pekanbaru'       => [0.5071, 101.4478],
        'Balikpapan'      => [-1.2379, 116.8529],
        'Manado'          => [1.4748, 124.8421],
        'Pontianak'       => [-0.0263, 109.3425],
        'Banjarmasin'     => [-3.3194, 114.5907],
        'Samarinda'       => [-0.4948, 117.1436],
        'Serang'          => [-6.1103, 106.1512],
        'Cilegon'         => [-6.0023, 106.0507],
        'Karawang'        => [-6.3227, 107.3376],
        'Cirebon'         => [-6.7320, 108.5523],
        'Tasikmalaya'     => [-7.3274, 108.2207],
        'Sukabumi'        => [-6.9277, 106.9300],
        'Purwokerto'      => [-7.4214, 109.2342],
        'Tegal'           => [-6.8797, 109.1426],
        'Pekalongan'      => [-6.8886, 109.6753],
        'Kediri'          => [-7.8167, 112.0170],
        'Madiun'          => [-7.6298, 111.5300],
        'Jember'          => [-8.1727, 113.6881],
        'Jambi'           => [-1.6101, 103.6131],
        'Padang'          => [-0.9471, 100.4172],
        'Bandar Lampung'  => [-5.3971, 105.2668],
        'Mataram'         => [-8.5833, 116.1167],
        'Kupang'          => [-10.1772, 123.6070],
        'Ambon'           => [-3.6954, 128.1814],
        'Jayapura'        => [-2.5916, 140.6690],
        'Batam'           => [1.0456, 104.0305],
        'Cikarang'        => [-6.3103, 107.1731],
        'Sidoarjo'        => [-7.4478, 112.7183],
        'Gresik'          => [-7.1625, 112.6513],
        'Kudus'           => [-6.8048, 110.8405],
    ];
}
?>
