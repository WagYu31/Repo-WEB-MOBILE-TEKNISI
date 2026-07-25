<?php
/**
 * Test Foursquare API Key
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once 'gmaps-config.php';

$apiKey = FOURSQUARE_API_KEY;
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 1: Standard format
echo "=== Test 1: Authorization: KEY ===\n";
$result1 = testFSQ($apiKey, 'Authorization: ' . $apiKey);
echo $result1 . "\n\n";

// Test 2: Bearer format
echo "=== Test 2: Authorization: Bearer KEY ===\n";
$result2 = testFSQ($apiKey, 'Authorization: Bearer ' . $apiKey);
echo $result2 . "\n\n";

// Test 3: fsq prefix
echo "=== Test 3: Authorization: fsq KEY ===\n";
$result3 = testFSQ($apiKey, 'Authorization: fsq ' . $apiKey);
echo $result3 . "\n\n";

function testFSQ($key, $authHeader) {
    $url = 'https://api.foursquare.com/v3/places/search?' . http_build_query([
        'query' => 'CCTV',
        'll'    => '-6.1783,106.6319',
        'radius'=> 25000,
        'limit' => 3,
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            $authHeader,
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $output = "HTTP: $httpCode\n";
    if ($error) $output .= "cURL Error: $error\n";
    
    $data = json_decode($response, true);
    if ($data && isset($data['results'])) {
        $output .= "✅ SUCCESS! Found " . count($data['results']) . " places:\n";
        foreach ($data['results'] as $p) {
            $output .= "  - " . ($p['name'] ?? 'N/A') . "\n";
        }
    } else {
        $output .= "Response: " . substr($response, 0, 500) . "\n";
    }
    
    return $output;
}
?>
