<?php
/**
 * Google Maps Scraping — Web Scraper (GRATIS tanpa API Key)
 * 
 * Scraping langsung dari Google Maps search results.
 * Rate limiter KETAT untuk mencegah IP diblokir Google.
 * 
 * Strategi anti-block:
 * - Max 10 request per hari (sangat konservatif)
 * - Random delay 3-8 detik antar request
 * - Rotasi User-Agent browser
 * - Cache hasil selama 7 hari
 * - Max 500 request per bulan
 */

// Suppress PHP errors from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'conn.php';
require_once 'gmaps-config.php';
ob_clean();

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
// ACTION: Search Google Maps (Web Scraping)
// ══════════════════════════════════════════════════
if ($action === 'search') {
    $keyword = trim($input['keyword'] ?? $_POST['keyword'] ?? '');
    $city    = trim($input['city'] ?? $_POST['city'] ?? '');

    if (empty($keyword) || empty($city)) {
        echo json_encode(['error' => true, 'message' => 'Kata kunci dan kota wajib diisi']);
        exit;
    }

    // ─── Rate Limit ─────────────────────────────────
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

    // ─── Cek Cache ──────────────────────────────────
    $cacheDir = __DIR__ . '/gmaps_cache';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
    
    $cacheKey = md5($keyword . '|' . $city);
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    
    // Pakai cache jika ada dan belum expired (7 hari)
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 604800) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) {
            $cached['from_cache'] = true;
            $cached['stats'] = gmaps_get_stats();
            echo json_encode($cached);
            exit;
        }
    }

    // ─── Random delay (anti-block) ──────────────────
    usleep(rand(2000000, 5000000)); // 2-5 detik delay

    // ─── User Agent Rotasi ──────────────────────────
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
    ];
    $ua = $userAgents[array_rand($userAgents)];

    // ─── Scrape Google Maps ─────────────────────────
    $searchQuery = urlencode($keyword . ' di ' . $city);
    $url = "https://www.google.com/maps/search/{$searchQuery}/";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ],
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Increment counter setelah request terkirim
    gmaps_increment_usage();

    if ($html === false || $httpCode !== 200) {
        echo json_encode([
            'error'   => true,
            'message' => 'Gagal mengakses Google Maps. HTTP ' . $httpCode . ($curlError ? ': ' . $curlError : ''),
            'stats'   => gmaps_get_stats(),
        ]);
        exit;
    }

    // ─── Parse hasil dari HTML ──────────────────────
    $results = parseGoogleMapsHtml($html, $city);

    // Simpan ke cache
    $cacheData = [
        'error'         => false,
        'keyword'       => $keyword,
        'city'          => $city,
        'total_results' => count($results),
        'results'       => $results,
        'scraped_at'    => date('Y-m-d H:i:s'),
        'from_cache'    => false,
    ];
    file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    $cacheData['stats'] = gmaps_get_stats();
    echo json_encode($cacheData);
    exit;
}

echo json_encode(['error' => true, 'message' => 'Action tidak valid']);
exit;

// ══════════════════════════════════════════════════
// PARSER: Extract data dari Google Maps HTML
// ══════════════════════════════════════════════════
function parseGoogleMapsHtml($html, $city) {
    $results = [];

    // Google Maps menyimpan data dalam format JSON tersembunyi di HTML
    // Cari pattern data JSON yang berisi info tempat
    
    // Method 1: Parse dari window.APP_INITIALIZATION_STATE
    if (preg_match('/window\.APP_INITIALIZATION_STATE\s*=\s*(\[.+?\]);\s*(?:window\.|<\/script>)/s', $html, $match)) {
        $jsonStr = $match[1];
        // Coba parse nested JSON
        if (preg_match_all('/"([^"]{2,100})"\s*,\s*"([^"]*(?:Jl\.|Jalan|Ruko|Blok|No\.|Kav|Komplek|RT|RW|Kel|Kec|Kota|Kab)[^"]*)"/', $jsonStr, $addressMatches, PREG_SET_ORDER)) {
            foreach ($addressMatches as $am) {
                $name = $am[1];
                $address = $am[2];
                // Skip jika nama terlalu pendek atau bukan nama toko
                if (strlen($name) < 3) continue;
                $results[] = buildResult($name, $address, $city);
            }
        }
    }

    // Method 2: Parse dari embedded JSON arrays (data places)
    // Google Maps memasukkan data dalam format: [null,null,null,"nama toko",...]
    if (preg_match_all('/\["([^"]{3,80})"\s*,\s*"([^"]*(?:Jl\.|Jalan|Ruko|Blok|No\.|Komplek|Gedung|Lantai|Kav)[^"]*)"[^]]*?,\s*([\d.]+)\s*,\s*(\d+)\s*\]/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $exists = false;
            foreach ($results as $r) {
                if ($r['name'] === $m[1]) { $exists = true; break; }
            }
            if (!$exists) {
                $result = buildResult($m[1], $m[2], $city);
                $result['rating'] = floatval($m[3]);
                $result['review_count'] = intval($m[4]);
                $result = recalcTrust($result);
                $results[] = $result;
            }
        }
    }

    // Method 3: Regex untuk nama + rating dari format umum Google Maps data
    if (preg_match_all('/\\\\\"([^\\\\\"]{3,80})\\\\\"[^}]*?\\\\\"((?:Jl|Jalan|Ruko|Komp|Blok|Perum)[^\\\\\"]{5,200})\\\\\"/', $html, $m3, PREG_SET_ORDER)) {
        foreach ($m3 as $m) {
            $name = stripcslashes($m[1]);
            $address = stripcslashes($m[2]);
            $exists = false;
            foreach ($results as $r) {
                if ($r['name'] === $name) { $exists = true; break; }
            }
            if (!$exists && strlen($name) > 3) {
                $results[] = buildResult($name, $address, $city);
            }
        }
    }

    // Method 4: Fallback — cari nama-nama yang mengandung keyword CCTV/security
    if (empty($results)) {
        $cctvKeywords = ['CCTV', 'cctv', 'Hikvision', 'Dahua', 'Security', 'Kamera', 'Camera', 'Alarm', 'Surveillance'];
        foreach ($cctvKeywords as $kw) {
            if (preg_match_all('/\\\\?"([^"\\\\]{5,80}' . preg_quote($kw) . '[^"\\\\]{0,80})\\\\?"/', $html, $kwMatches)) {
                foreach ($kwMatches[1] as $name) {
                    $name = trim(stripcslashes($name));
                    // Filter noise
                    if (strlen($name) > 80 || strlen($name) < 5) continue;
                    if (strpos($name, 'http') !== false) continue;
                    if (strpos($name, '\\') !== false) continue;
                    if (preg_match('/[{}()<>]/', $name)) continue;
                    
                    $exists = false;
                    foreach ($results as $r) {
                        if ($r['name'] === $name) { $exists = true; break; }
                    }
                    if (!$exists) {
                        $results[] = buildResult($name, '', $city);
                    }
                }
            }
        }
    }

    // Method 5: Parse data dari format protobuf-like yang Google pakai
    // Pattern: nama toko diikuti alamat, rating, review count
    if (preg_match_all('/\x22([^\x22]{4,80})\x22[^\x22]{0,500}?\x22((?:Jl\.|Jalan|Ruko|Komp|Blok|Perum|Gg\.|Gang)[^\x22]{5,200})\x22/', $html, $m5, PREG_SET_ORDER)) {
        foreach ($m5 as $m) {
            $name = $m[1];
            $address = $m[2];
            if (strlen($name) < 4 || strlen($name) > 80) continue;
            if (preg_match('/[{}()<>\\\\\/]/', $name)) continue;
            
            $exists = false;
            foreach ($results as $r) {
                if ($r['name'] === $name) { $exists = true; break; }
            }
            if (!$exists) {
                $results[] = buildResult($name, $address, $city);
            }
        }
    }

    // Deduplicate by name
    $unique = [];
    $seen = [];
    foreach ($results as $r) {
        $key = strtolower(trim($r['name']));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $r;
        }
    }

    // Sort by trust score desc
    usort($unique, function($a, $b) {
        return $b['trust_score'] - $a['trust_score'];
    });

    return array_slice($unique, 0, 20); // Max 20 results
}

function buildResult($name, $address, $city) {
    $name = trim(html_entity_decode($name, ENT_QUOTES, 'UTF-8'));
    $address = trim(html_entity_decode($address, ENT_QUOTES, 'UTF-8'));
    
    $hasPhone = false;
    $hasWebsite = false;
    $rating = 0;
    $reviewCount = 0;

    // Trust score basic
    $trustScore = 10; // Base score for existing in Google Maps
    if (!empty($address)) $trustScore += 20;
    
    $trustLevel = 'berisiko';
    if ($trustScore >= 70) $trustLevel = 'terpercaya';
    elseif ($trustScore >= 40) $trustLevel = 'perlu_cek';

    return [
        'place_id'     => md5($name . $address),
        'name'         => $name,
        'address'      => $address,
        'phone'        => '',
        'website'      => '',
        'maps_url'     => 'https://www.google.com/maps/search/' . urlencode($name . ' ' . $city),
        'rating'       => $rating,
        'review_count' => $reviewCount,
        'photo_count'  => 0,
        'photo_ref'    => '',
        'lat'          => 0,
        'lng'          => 0,
        'business_status' => '',
        'types'        => [],
        'primary_type' => '',
        'trust_score'  => $trustScore,
        'trust_level'  => $trustLevel,
    ];
}

function recalcTrust($result) {
    $ts = 10;
    if (!empty($result['address'])) $ts += 20;
    if ($result['review_count'] >= 20) $ts += 30;
    elseif ($result['review_count'] >= 10) $ts += 20;
    elseif ($result['review_count'] >= 5) $ts += 10;
    if ($result['rating'] >= 4.0) $ts += 10;
    elseif ($result['rating'] >= 3.0) $ts += 5;
    if (!empty($result['phone'])) $ts += 20;

    $result['trust_score'] = $ts;
    $result['trust_level'] = $ts >= 70 ? 'terpercaya' : ($ts >= 40 ? 'perlu_cek' : 'berisiko');
    return $result;
}
?>
