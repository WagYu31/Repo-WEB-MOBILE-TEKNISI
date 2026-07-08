<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "GoogleSheetsClient.php";

try {
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    
    $client = new GoogleSheetsClient($keyPath);
    $client->authenticate();
    $accessToken = $client->accessToken;
    
    $testSpreadsheetId = "19OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU";
    
    echo "Testing GET request for Spreadsheet metadata...\n";
    $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($testSpreadsheetId);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: " . $httpCode . "\n";
    echo "Response:\n";
    echo $response . "\n";

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
