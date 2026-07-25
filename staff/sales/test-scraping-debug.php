<?php
/**
 * Google Maps Scraping — DEBUG TOOL
 * Cek apa yang Google kirim balik ke server
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Google Scraping Debug</h2>";

$query = 'Toko CCTV di Tangerang';
$url = 'https://www.google.com/search?' . http_build_query([
    'q'   => $query,
    'tbm' => 'lcl',
    'hl'  => 'id',
    'gl'  => 'id',
    'num' => 10,
]);

echo "<p><b>URL:</b> " . htmlspecialchars($url) . "</p>";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    CURLOPT_ENCODING       => 'gzip',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_COOKIE         => 'CONSENT=PENDING+987; SOCS=CAESHAgBEhJnd3NfMjAyMzA4MTUtMF9SQzIaAmVuIAEaBgiAo_CmBg',
    CURLOPT_HTTPHEADER     => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: id-ID,id;q=0.9,en;q=0.7',
    ],
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "<p><b>HTTP Code:</b> $httpCode</p>";
echo "<p><b>Final URL:</b> " . htmlspecialchars($finalUrl) . "</p>";
echo "<p><b>cURL Error:</b> " . ($curlError ?: 'None') . "</p>";
echo "<p><b>Response Length:</b> " . strlen($html) . " bytes</p>";

if ($html) {
    // Check for CAPTCHA/consent
    $hasCaptcha = (stripos($html, 'captcha') !== false || stripos($html, 'recaptcha') !== false);
    $hasConsent = (stripos($html, 'consent') !== false || stripos($html, 'Before you continue') !== false);
    $hasResults = (stripos($html, 'CCTV') !== false);
    
    echo "<p><b>Has CAPTCHA:</b> " . ($hasCaptcha ? '⛔ YES' : '✅ NO') . "</p>";
    echo "<p><b>Has Consent Page:</b> " . ($hasConsent ? '⛔ YES' : '✅ NO') . "</p>";
    echo "<p><b>Has 'CCTV' in response:</b> " . ($hasResults ? '✅ YES' : '⛔ NO') . "</p>";
    
    // Show first 3000 chars
    echo "<h3>First 3000 chars of HTML:</h3>";
    echo "<pre style='background:#f5f5f5; padding:10px; max-height:400px; overflow:auto; font-size:11px;'>" . htmlspecialchars(substr($html, 0, 3000)) . "</pre>";
    
    // Try to find business names
    echo "<h3>Regex Extraction Attempts:</h3>";
    
    // Pattern 1: OSrXXb class
    preg_match_all('/<span class="OSrXXb"[^>]*>([^<]+)<\/span>/', $html, $m1);
    echo "<p><b>Pattern OSrXXb:</b> " . count($m1[1]) . " matches</p>";
    if (!empty($m1[1])) echo "<pre>" . htmlspecialchars(implode("\n", array_slice($m1[1], 0, 10))) . "</pre>";
    
    // Pattern 2: dbg0pd class
    preg_match_all('/class="[^"]*dbg0pd[^"]*"[^>]*>([^<]+)/', $html, $m2);
    echo "<p><b>Pattern dbg0pd:</b> " . count($m2[1]) . " matches</p>";
    if (!empty($m2[1])) echo "<pre>" . htmlspecialchars(implode("\n", array_slice($m2[1], 0, 10))) . "</pre>";
    
    // Pattern 3: aria-label with maps
    preg_match_all('/aria-label="([^"]{3,100})"[^>]*href="[^"]*google\.com\/maps/', $html, $m3);
    echo "<p><b>Pattern aria-label+maps:</b> " . count($m3[1]) . " matches</p>";
    if (!empty($m3[1])) echo "<pre>" . htmlspecialchars(implode("\n", array_slice($m3[1], 0, 10))) . "</pre>";
    
    // Pattern 4: h3 titles
    preg_match_all('/<h3[^>]*>([^<]+)<\/h3>/', $html, $m4);
    echo "<p><b>Pattern h3 titles:</b> " . count($m4[1]) . " matches</p>";
    if (!empty($m4[1])) echo "<pre>" . htmlspecialchars(implode("\n", array_slice($m4[1], 0, 10))) . "</pre>";
    
    // Pattern 5: data-cid
    preg_match_all('/data-cid="([^"]*)"/', $html, $m5);
    echo "<p><b>Pattern data-cid:</b> " . count($m5[1]) . " matches</p>";
    
    // Pattern 6: JSON-LD
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m6);
    echo "<p><b>JSON-LD blocks:</b> " . count($m6[1]) . "</p>";
    
    // Pattern 7: Any mention of CCTV
    preg_match_all('/[>"]([^<"]{3,80}CCTV[^<"]{0,80})[<"]/', $html, $m7);
    echo "<p><b>CCTV mentions:</b> " . count($m7[1]) . " matches</p>";
    if (!empty($m7[1])) echo "<pre>" . htmlspecialchars(implode("\n", array_unique(array_slice($m7[1], 0, 15)))) . "</pre>";

    // Save full HTML for analysis
    file_put_contents(__DIR__ . '/gmaps_debug_response.html', $html);
    echo "<p>Full HTML saved to gmaps_debug_response.html (" . strlen($html) . " bytes)</p>";
}
?>
