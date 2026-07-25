<?php
/**
 * Debug: Analisis format data Google Search
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

$query = 'Toko CCTV di Tangerang';
$url = 'https://www.google.com/search?' . http_build_query([
    'q' => $query, 'hl' => 'id', 'gl' => 'id', 'num' => 20,
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    CURLOPT_ENCODING       => 'gzip',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_COOKIE         => 'CONSENT=PENDING+987; SOCS=CAESHAgBEhJnd3NfMjAyMzA4MTUtMF9SQzIaAmVuIAEaBgiAo_CmBg',
    CURLOPT_HTTPHEADER     => ['Accept: text/html', 'Accept-Language: id-ID,id;q=0.9'],
]);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== GOOGLE SEARCH DEBUG ===\n";
echo "HTTP: $code | Size: " . strlen($html) . " bytes\n\n";

// Decode hex and unicode escapes
$decoded = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function($m) {
    return chr(hexdec($m[1]));
}, $html);
$decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function($m) {
    return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
}, $decoded);

echo "=== ALL CCTV MENTIONS (context ±100 chars) ===\n\n";
$offset = 0;
$count = 0;
while (($pos = stripos($decoded, 'CCTV', $offset)) !== false && $count < 30) {
    $start = max(0, $pos - 100);
    $chunk = substr($decoded, $start, 300);
    // Clean for display
    $chunk = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $chunk);
    echo "--- Match " . (++$count) . " at position $pos ---\n";
    echo $chunk . "\n\n";
    $offset = $pos + 4;
}

echo "\n=== LOOKING FOR /maps/place/ URLs ===\n";
preg_match_all('/\/maps\/place\/([^\/\\"]+)/', $decoded, $placeUrls);
if (!empty($placeUrls[1])) {
    foreach (array_unique($placeUrls[1]) as $p) {
        echo "  - " . urldecode(str_replace('+', ' ', $p)) . "\n";
    }
} else {
    echo "  None found\n";
}

echo "\n=== LOOKING FOR PHONE NUMBERS ===\n";
preg_match_all('/(?:\+62|0)\s*[\d][\d\s\-]{7,14}/', $decoded, $phones);
if (!empty($phones[0])) {
    foreach (array_unique($phones[0]) as $p) {
        echo "  - " . trim($p) . "\n";
    }
} else {
    echo "  None found\n";
}

echo "\n=== LOOKING FOR Jl./Jalan ADDRESSES ===\n";
preg_match_all('/(?:Jl\.|Jalan|Ruko|Komp)[^"\\\\<]{5,150}/', $decoded, $addrs);
if (!empty($addrs[0])) {
    foreach (array_unique(array_slice($addrs[0], 0, 20)) as $a) {
        echo "  - " . trim($a) . "\n";
    }
} else {
    echo "  None found\n";
}

echo "\n=== LOOKING FOR RATINGS (X.Y pattern) ===\n";
preg_match_all('/(\d\.\d)\x{2605}/u', $decoded, $ratings); // star character
echo "  Star ratings: " . count($ratings[0]) . "\n";
preg_match_all('/(\d[.,]\d)\s*(?:\((\d+)\)|\x{2605})/u', $decoded, $ratingsFull, PREG_SET_ORDER);
foreach (array_slice($ratingsFull, 0, 20) as $rf) {
    echo "  - Rating: " . $rf[1] . (isset($rf[2]) ? " ({$rf[2]} reviews)" : "") . "\n";
}

echo "\n=== LOOKING FOR BUSINESS NAME PATTERNS ===\n";
// Pattern: text between quotes that contains CCTV
preg_match_all('/"([^"]{5,80}(?:CCTV|cctv|Security|Kamera|Camera|Hikvision|Dahua)[^"]{0,40})"/', $decoded, $bizNames);
if (!empty($bizNames[1])) {
    echo "  Found " . count($bizNames[1]) . " matches:\n";
    foreach (array_unique(array_slice($bizNames[1], 0, 30)) as $bn) {
        // Filter noise
        if (preg_match('/[{}<>\\\\]/', $bn)) continue;
        if (strpos($bn, 'function') !== false) continue;
        if (strpos($bn, 'http') !== false) continue;
        echo "  - " . $bn . "\n";
    }
} else {
    echo "  None found\n";
}

echo "\n=== SAMPLE OF ALL QUOTED STRINGS (3-80 chars, alphabetic) ===\n";
preg_match_all('/"([^"]{3,80})"/', $decoded, $allQuoted);
$filtered = [];
foreach ($allQuoted[1] as $q) {
    if (preg_match('/[{}<>\\\\\/;=()]/', $q)) continue;
    if (preg_match('/^(function|var|const|let|return|null|undefined|true|false|none|this)/i', $q)) continue;
    if (preg_match('/\.(js|css|html|php|png|jpg|svg|woff)/i', $q)) continue;
    if (preg_match('/^[\d\s\.\-,]+$/', $q)) continue;
    if (preg_match_all('/[a-zA-Z]/', $q) < 3) continue;
    $filtered[] = $q;
}
$filtered = array_unique($filtered);
echo "  Total: " . count($filtered) . " unique strings\n";
foreach (array_slice($filtered, 0, 50) as $f) {
    echo "  - " . $f . "\n";
}
?>
