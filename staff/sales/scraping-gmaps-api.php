<?php
/**
 * Google Maps Scraping — Google Local Search (GRATIS)
 * 
 * Menggunakan Google Search local results (tbm=lcl) yang mengembalikan
 * data bisnis dalam HTML yang bisa di-parse tanpa JavaScript.
 * 
 * Anti-block:
 * - Max 10 request/hari, 200/bulan
 * - Cache 7 hari
 * - Random delay 2-5 detik
 * - Rotasi User-Agent
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
$action = $input['action'] ?? $_POST['action'] ?? 'search';

// ═══ Stats ═══
if ($action === 'stats') {
    echo json_encode(['error' => false, 'stats' => gmaps_get_stats()]);
    exit;
}

// ═══ Search ═══
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

    // Cache check
    $cacheDir = __DIR__ . '/gmaps_cache';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
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

    // Random delay
    usleep(rand(2000000, 5000000));

    // User agents
    $uas = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
    ];

    $query = $keyword . ' di ' . $city;
    $results = [];

    // ═══ Method 1: Google Local Search (tbm=lcl) ═══
    $url1 = 'https://www.google.com/search?' . http_build_query([
        'q'     => $query,
        'tbm'   => 'lcl',
        'hl'    => 'id',
        'gl'    => 'id',
        'num'   => 20,
    ]);

    $html1 = curlFetch($url1, $uas[array_rand($uas)]);
    gmaps_increment_usage();

    if ($html1) {
        $results = array_merge($results, parseLocalSearch($html1, $city));
    }

    // ═══ Method 2: Regular Google Search (fallback) ═══
    if (count($results) < 3) {
        usleep(rand(1500000, 3000000));
        
        $url2 = 'https://www.google.com/search?' . http_build_query([
            'q'  => $query . ' alamat telepon',
            'hl' => 'id',
            'gl' => 'id',
            'num'=> 20,
        ]);
        
        $html2 = curlFetch($url2, $uas[array_rand($uas)]);
        if ($html2) {
            $results = array_merge($results, parseRegularSearch($html2, $city, $keyword));
        }
    }

    // Deduplicate
    $unique = [];
    $seen = [];
    foreach ($results as $r) {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $r['name']));
        if (!isset($seen[$key]) && strlen($r['name']) >= 3) {
            $seen[$key] = true;
            $unique[] = $r;
        }
    }

    // Sort by trust score
    usort($unique, function($a, $b) { return $b['trust_score'] - $a['trust_score']; });
    $unique = array_slice($unique, 0, 20);

    // Cache
    $response = [
        'error'         => false,
        'keyword'       => $keyword,
        'city'          => $city,
        'total_results' => count($unique),
        'results'       => $unique,
        'scraped_at'    => date('Y-m-d H:i:s'),
        'from_cache'    => false,
    ];
    file_put_contents($cacheFile, json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    $response['stats'] = gmaps_get_stats();
    echo json_encode($response);
    exit;
}

echo json_encode(['error' => true, 'message' => 'Action tidak valid']);
exit;

// ══════════════════════════════════════════════════
// CURL FETCH
// ══════════════════════════════════════════════════
function curlFetch($url, $ua) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en;q=0.7',
            'Connection: keep-alive',
        ],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($html !== false && $code === 200) ? $html : null;
}

// ══════════════════════════════════════════════════
// PARSER: Google Local Search Results
// ══════════════════════════════════════════════════
function parseLocalSearch($html, $city) {
    $results = [];

    // Method A: Parse business cards dari local results
    // Pattern: <div class="VkpGBb"> contains business name, or data-cid attributes
    
    // Extract business names from aria-label or title attributes in local results
    // Google Local search has consistent patterns for business listings
    
    // Pattern 1: Extract from structured data divs
    // Business name biasanya dalam <span> atau <div> dengan class tertentu
    if (preg_match_all('/<span class="OSrXXb"[^>]*>([^<]+)<\/span>/', $html, $names)) {
        foreach ($names[1] as $i => $name) {
            $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');
            if (strlen($name) < 3 || strlen($name) > 100) continue;
            
            $result = makeResult($name, $city);
            
            // Try to find rating near this name
            $pos = strpos($html, $name);
            if ($pos !== false) {
                $chunk = substr($html, $pos, 500);
                // Rating pattern
                if (preg_match('/(\d[.,]\d)\s*</', $chunk, $rm)) {
                    $result['rating'] = floatval(str_replace(',', '.', $rm[1]));
                }
                // Review count
                if (preg_match('/\((\d+)\)/', $chunk, $rcm)) {
                    $result['review_count'] = intval($rcm[1]);
                }
                // Address
                if (preg_match('/(?:Jl\.|Jalan|Ruko|Komp|Blok|No\.|Perum|Gg\.)[^<]{5,150}/', $chunk, $am)) {
                    $result['address'] = html_entity_decode(trim($am[0]), ENT_QUOTES, 'UTF-8');
                }
                // Phone
                if (preg_match('/(?:\+62|62|0)\s*[\d\s\-]{8,15}/', $chunk, $pm)) {
                    $result['phone'] = trim($pm[0]);
                }
            }
            
            $result = recalcTrust($result);
            $results[] = $result;
        }
    }
    
    // Pattern 2: Alternative class names Google uses
    if (empty($results)) {
        // Try dbg0pd class (another common local result class)
        if (preg_match_all('/<div[^>]*class="[^"]*dbg0pd[^"]*"[^>]*>([^<]+)<\/div>/', $html, $names2)) {
            foreach ($names2[1] as $name) {
                $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');
                if (strlen($name) >= 3 && strlen($name) <= 100) {
                    $results[] = makeResult($name, $city);
                }
            }
        }
    }

    // Pattern 3: aria-label on result links (most reliable)
    if (empty($results)) {
        if (preg_match_all('/aria-label="([^"]{3,100})"[^>]*href="[^"]*google\.com\/maps/', $html, $ariaMatches)) {
            foreach ($ariaMatches[1] as $name) {
                $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');
                // Remove "Situs web untuk " prefix if present
                $name = preg_replace('/^(?:Situs web untuk|Website for|Petunjuk arah ke|Directions to)\s*/i', '', $name);
                if (strlen($name) >= 3 && strlen($name) <= 100) {
                    $results[] = makeResult($name, $city);
                }
            }
        }
    }

    // Pattern 4: Parse dari JSON-LD structured data
    if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $jsonLd)) {
        foreach ($jsonLd[1] as $json) {
            $data = json_decode($json, true);
            if (!$data) continue;
            
            // Handle single item or array
            $items = isset($data['@type']) ? [$data] : ($data['@graph'] ?? [$data]);
            foreach ($items as $item) {
                if (isset($item['name']) && isset($item['@type']) && 
                    in_array($item['@type'], ['LocalBusiness', 'Store', 'Organization', 'Place'])) {
                    $r = makeResult($item['name'], $city);
                    if (isset($item['address']['streetAddress'])) $r['address'] = $item['address']['streetAddress'];
                    if (isset($item['telephone'])) $r['phone'] = $item['telephone'];
                    if (isset($item['aggregateRating']['ratingValue'])) $r['rating'] = floatval($item['aggregateRating']['ratingValue']);
                    if (isset($item['aggregateRating']['reviewCount'])) $r['review_count'] = intval($item['aggregateRating']['reviewCount']);
                    $r = recalcTrust($r);
                    $results[] = $r;
                }
            }
        }
    }

    // Pattern 5: Generic business name extraction from result containers
    if (empty($results)) {
        // Google wraps each local result in a div, the business name is typically in an <a> or <span> with data attributes
        if (preg_match_all('/data-cid="[^"]*"[^>]*>.*?<(?:a|span)[^>]*>([^<]{3,80})<\/(?:a|span)>/s', $html, $cidMatches)) {
            foreach ($cidMatches[1] as $name) {
                $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');
                if (strlen($name) >= 3) {
                    $results[] = makeResult($name, $city);
                }
            }
        }
    }

    // Pattern 6: Extract all business-looking names from the page
    if (empty($results)) {
        // Look for patterns that look like business names near map/location content
        if (preg_match_all('/<(?:h[23]|a|div|span)[^>]*>([^<]*(?:CCTV|Security|Kamera|Camera|Hikvision|Dahua|Alarm|Elektronik|Toko)[^<]*)</', $html, $bm)) {
            foreach ($bm[1] as $name) {
                $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');
                if (strlen($name) >= 5 && strlen($name) <= 100 && !preg_match('/[<>{}]/', $name)) {
                    $results[] = makeResult($name, $city);
                }
            }
        }
    }

    return $results;
}

// ══════════════════════════════════════════════════
// PARSER: Regular Google Search Results  
// ══════════════════════════════════════════════════
function parseRegularSearch($html, $city, $keyword) {
    $results = [];
    
    // Extract from search result titles (<h3> tags)
    if (preg_match_all('/<h3[^>]*>([^<]+)<\/h3>/', $html, $titles)) {
        foreach ($titles[1] as $title) {
            $title = html_entity_decode(trim($title), ENT_QUOTES, 'UTF-8');
            
            // Filter: harus mengandung keyword terkait CCTV/security atau nama toko
            $keywords = ['CCTV', 'cctv', 'Security', 'Kamera', 'Camera', 'Hikvision', 'Dahua', 
                         'Alarm', 'Toko', 'Distributor', 'Supplier', 'Installer', 'Jual', 'Pasang'];
            $match = false;
            foreach ($keywords as $kw) {
                if (stripos($title, $kw) !== false) { $match = true; break; }
            }
            
            if (!$match) continue;
            if (strlen($title) < 5 || strlen($title) > 120) continue;
            
            // Clean up title — hapus suffix seperti " - Google Maps", " | Tokopedia", dll
            $title = preg_replace('/\s*[-|·–—]\s*(Google Maps|Maps|Tokopedia|Shopee|Bukalapak|Lazada|Blibli|Instagram|Facebook|Reviews|Review|Ulasan).*$/i', '', $title);
            $title = trim($title);
            
            if (strlen($title) >= 3) {
                $results[] = makeResult($title, $city);
            }
        }
    }

    return $results;
}

// ══════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════
function makeResult($name, $city) {
    return [
        'place_id'     => md5($name . $city),
        'name'         => $name,
        'address'      => '',
        'phone'        => '',
        'website'      => '',
        'maps_url'     => 'https://www.google.com/maps/search/' . urlencode($name . ' ' . $city),
        'rating'       => 0,
        'review_count' => 0,
        'photo_count'  => 0,
        'photo_ref'    => '',
        'lat'          => 0,
        'lng'          => 0,
        'business_status' => '',
        'types'        => [],
        'primary_type' => '',
        'trust_score'  => 15,
        'trust_level'  => 'berisiko',
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
