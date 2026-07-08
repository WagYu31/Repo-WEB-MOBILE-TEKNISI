<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "../conn.php";
require_once "GoogleSheetsClient.php";

$spreadsheetId = "1N8OzWJbJ4FX0pQmcJuvLDaR9WOqjF0dajE4ks4nJhP0";
$keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
$client = new GoogleSheetsClient($keyPath);
$accessToken = $client->getAccessToken();

// Test ranges
$ranges = [
    "Sheet1!C3:C4",
    "'Sheet1'!C3:C4",
    "Sheet1!C3",
    "'Sheet1'!C3"
];

foreach ($ranges as $range) {
    echo "Testing range: $range\n";
    $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($spreadsheetId) . "/values/" . urlencode($range) . "?valueInputOption=USER_ENTERED";
    
    $payload = json_encode([
        'range' => $range,
        'majorDimension' => 'ROWS',
        'values' => [["TEST SALES"], ["TEST LOKASI"]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken,
        "Content-Type: application/json",
        "Content-Length: " . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: $httpCode\n";
    echo "Response: $response\n\n";
}
