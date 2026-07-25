<?php
/**
 * Google Maps Scraping — Parser untuk format udm=1 (Google Local 2025+)
 * 
 * Google redirect tbm=lcl ke udm=1 yang berisi data dalam JavaScript.
 * Data toko tersimpan dalam escaped JSON di dalam <script> tags.
 * 
 * Anti-block: 10 req/hari, cache 7 hari, random delay, rotasi UA
 */

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
$action = $input['action'] ?? 'search';

if ($action === 'stats') {
    echo json_encode(['error' => false, 'stats' => gmaps_get_stats()]);
    exit;
}

if ($action === 'search') {
    $keyword = trim($input['keyword'] ?? '');
    $city    = trim($input['city'] ?? '');

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

    // Cache
    $cacheDir = __DIR__ . '/gmaps_cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cacheKey = md5($keyword . '|' . $city);
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

    // Delay
    usleep(rand(1500000, 4000000));

    $uas = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
    ];

    $query = $keyword . ' di ' . $city;
    $allResults = [];

    // ═══ Fetch Google Search ═══
    $url = 'https://www.google.com/search?' . http_build_query([
        'q'   => $query,
        'hl'  => 'id',
        'gl'  => 'id',
        'num' => 20,
    ]);

    $html = curlFetch($url, $uas[array_rand($uas)]);
    gmaps_increment_usage();

    if (!$html) {
        echo json_encode(['error' => true, 'message' => 'Gagal mengakses Google Search', 'stats' => gmaps_get_stats()]);
        exit;
    }

    // ═══ Parse data dari HTML ═══
    // Google menyimpan data bisnis dalam JavaScript code di dalam <script> tags
    // Data mengandung nama toko, alamat, rating, review count, telepon, dll.
    
    // Decode semua unicode escapes di HTML
    $decoded = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function($m) {
        return chr(hexdec($m[1]));
    }, $html);
    $decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($m) {
        return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
    }, $decoded);

    // ─── Strategy 1: Extract business data from JavaScript arrays ───
    // Pattern: business name followed by address, in JS string literals
    // Google stores: ["business name","address","lat,lng",rating,reviewcount,...]
    
    // Look for patterns like: "Nama Toko","Jl. Alamat"
    if (preg_match_all('/"([^"]{3,80})"\s*,\s*"((?:Jl\.|Jalan|Ruko|Komp|Blok|Perum|Gg\.|Gang|Kav|No\.|Gedung|Lt\.|Lantai|Perumahan)[^"]{5,200})"/', $decoded, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $name = cleanText($m[1]);
            $address = cleanText($m[2]);
            if (isValidName($name)) {
                $r = makeResult($name, $address, $city);
                // Look for rating/review near this match
                $pos = strpos($decoded, $m[0]);
                if ($pos !== false) {
                    $chunk = substr($decoded, max(0, $pos - 200), 600);
                    if (preg_match('/(\d[.,]\d)\s*(?:bintang|star|rating)?\s*[\s,]*\(?\s*(\d+)\s*(?:ulasan|review|rating)/i', $chunk, $rm)) {
                        $r['rating'] = floatval(str_replace(',', '.', $rm[1]));
                        $r['review_count'] = intval($rm[2]);
                    } elseif (preg_match('/,(\d[.,]\d),(\d+),/', $chunk, $rm2)) {
                        $rating = floatval(str_replace(',', '.', $rm2[1]));
                        $reviews = intval($rm2[2]);
                        if ($rating >= 1 && $rating <= 5 && $reviews > 0 && $reviews < 50000) {
                            $r['rating'] = $rating;
                            $r['review_count'] = $reviews;
                        }
                    }
                    // Phone
                    if (preg_match('/(?:\+62|062|0)\s*[\d\s\-()]{8,16}/', $chunk, $pm)) {
                        $r['phone'] = trim(preg_replace('/\s+/', '', $pm[0]));
                    }
                }
                $r = recalcTrust($r);
                $allResults[] = $r;
            }
        }
    }

    // ─── Strategy 2: Extract from "title" or "aria-label" attributes ───
    if (preg_match_all('/(?:aria-label|title|data-tooltip)="([^"]{5,100})"/i', $decoded, $labelMatches)) {
        foreach ($labelMatches[1] as $label) {
            $label = cleanText($label);
            // Filter: harus mengandung keyword terkait
            if (!containsCCTVKeyword($label) && !containsCCTVKeyword($keyword)) continue;
            if (!isValidName($label)) continue;
            // Remove common prefixes
            $label = preg_replace('/^(?:Situs web|Website|Petunjuk arah|Directions|Telepon|Rute ke)\s+(?:untuk\s+)?/i', '', $label);
            if (strlen($label) >= 3 && !isAlreadyInResults($label, $allResults)) {
                $allResults[] = makeResult($label, '', $city);
            }
        }
    }

    // ─── Strategy 3: Extract from Google Business Profile data ───
    // Pattern: data between specific markers that contain business info
    if (preg_match_all('/\[(?:null,?)*"([^"]{3,80})"(?:,(?:null|"[^"]*"))*,"([^"]*(?:Jl|Jalan|Ruko|Komp)[^"]*)"(?:,(?:null|"[^"]*"|[\d.]+))*,(\d\.\d),(\d+)/', $decoded, $gbpMatches, PREG_SET_ORDER)) {
        foreach ($gbpMatches as $m) {
            $name = cleanText($m[1]);
            if (isValidName($name) && !isAlreadyInResults($name, $allResults)) {
                $r = makeResult($name, cleanText($m[2]), $city);
                $r['rating'] = floatval($m[3]);
                $r['review_count'] = intval($m[4]);
                $r = recalcTrust($r);
                $allResults[] = $r;
            }
        }
    }

    // ─── Strategy 4: Extract CCTV-related business names ───
    $cctvWords = ['CCTV', 'Hikvision', 'Dahua', 'Security', 'Kamera', 'Camera', 'Alarm', 'Surveillance', 'Toko Elektronik'];
    foreach ($cctvWords as $cctvWord) {
        if (preg_match_all('/"([^"]{5,80}' . preg_quote($cctvWord, '/') . '[^"]{0,50})"/', $decoded, $cctvM)) {
            foreach ($cctvM[1] as $name) {
                $name = cleanText($name);
                if (isValidName($name) && !isAlreadyInResults($name, $allResults)) {
                    $allResults[] = makeResult($name, '', $city);
                }
            }
        }
    }

    // ─── Strategy 5: Parse from visible text with ratings ───
    // Pattern: "Nama Toko 4.5 (120)" or "Nama Toko · 4.5(120)"
    if (preg_match_all('/>([^<]{5,80}(?:CCTV|Security|Kamera|Camera|Hikvision|Dahua|Alarm|Elektronik|Toko)[^<]{0,30})</', $decoded, $visibleM)) {
        foreach ($visibleM[1] as $text) {
            $text = cleanText($text);
            if (isValidName($text) && !isAlreadyInResults($text, $allResults)) {
                $allResults[] = makeResult($text, '', $city);
            }
        }
    }

    // ─── Strategy 6: Extract from href URLs containing place data ───
    if (preg_match_all('/\/maps\/place\/([^\/]+)\//', $decoded, $placeUrls)) {
        foreach ($placeUrls[1] as $placeName) {
            $name = cleanText(urldecode(str_replace('+', ' ', $placeName)));
            if (isValidName($name) && strlen($name) >= 5 && !isAlreadyInResults($name, $allResults)) {
                $allResults[] = makeResult($name, '', $city);
            }
        }
    }

    // Deduplicate
    $unique = [];
    $seen = [];
    foreach ($allResults as $r) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $r['name']));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $r;
        }
    }

    usort($unique, function($a, $b) { return $b['trust_score'] - $a['trust_score']; });
    $unique = array_slice($unique, 0, 20);

    $response = [
        'error'         => false,
        'keyword'       => $keyword,
        'city'          => $city,
        'total_results' => count($unique),
        'results'       => $unique,
        'scraped_at'    => date('Y-m-d H:i:s'),
        'from_cache'    => false,
    ];
    @file_put_contents($cacheFile, json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    $response['stats'] = gmaps_get_stats();
    echo json_encode($response);
    exit;
}

echo json_encode(['error' => true, 'message' => 'Action tidak valid']);

// ══════════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════════

function curlFetch($url, $ua) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIE         => 'CONSENT=PENDING+987; SOCS=CAESHAgBEhJnd3NfMjAyMzA4MTUtMF9SQzIaAmVuIAEaBgiAo_CmBg',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en;q=0.7',
        ],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($html !== false && $code === 200) ? $html : null;
}

function cleanText($str) {
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    $str = preg_replace('/\\\\[nrt]/', ' ', $str);
    $str = preg_replace('/\s+/', ' ', trim($str));
    return $str;
}

function isValidName($name) {
    if (strlen($name) < 3 || strlen($name) > 100) return false;
    if (preg_match('/[{}<>\\\\\/\[\]]/', $name)) return false;
    if (preg_match('/^(http|www\.|function|var |const |let |return |null|undefined|true|false)/i', $name)) return false;
    if (preg_match('/\.(js|css|html|php|png|jpg|svg)$/i', $name)) return false;
    if (preg_match('/^[\d\s\.\-,]+$/', $name)) return false; // Only numbers/symbols
    // Must contain at least 2 letters
    if (preg_match_all('/[a-zA-Z]/', $name) < 2) return false;
    return true;
}

function containsCCTVKeyword($text) {
    $keywords = ['CCTV', 'cctv', 'Security', 'Kamera', 'Camera', 'Hikvision', 'Dahua', 
                 'Alarm', 'Surveillance', 'Elektronik', 'Toko', 'Distributor', 'Supplier',
                 'Installer', 'Pasang', 'Jual'];
    foreach ($keywords as $kw) {
        if (stripos($text, $kw) !== false) return true;
    }
    return false;
}

function isAlreadyInResults($name, $results) {
    $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    foreach ($results as $r) {
        $rKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $r['name']));
        if ($key === $rKey) return true;
    }
    return false;
}

function makeResult($name, $address, $city) {
    return [
        'place_id'        => md5($name . $city),
        'name'            => $name,
        'address'         => $address,
        'phone'           => '',
        'website'         => '',
        'maps_url'        => 'https://www.google.com/maps/search/' . urlencode($name . ' ' . $city),
        'rating'          => 0,
        'review_count'    => 0,
        'photo_count'     => 0,
        'photo_ref'       => '',
        'lat'             => 0,
        'lng'             => 0,
        'business_status' => '',
        'types'           => [],
        'primary_type'    => '',
        'trust_score'     => 15,
        'trust_level'     => 'berisiko',
    ];
}

function recalcTrust($r) {
    $ts = 15;
    if (!empty($r['address'])) $ts += 15;
    if (!empty($r['phone'])) $ts += 20;
    if ($r['review_count'] >= 20) $ts += 25;
    elseif ($r['review_count'] >= 10) $ts += 15;
    elseif ($r['review_count'] >= 5) $ts += 10;
    if ($r['rating'] >= 4.0) $ts += 15;
    elseif ($r['rating'] >= 3.0) $ts += 5;
    $r['trust_score'] = $ts;
    $r['trust_level'] = $ts >= 70 ? 'terpercaya' : ($ts >= 40 ? 'perlu_cek' : 'berisiko');
    return $r;
}
?>
