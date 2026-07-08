<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "GoogleSheetsClient.php";

try {
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    $client = new GoogleSheetsClient($keyPath);
    $accessToken = $client->getAccessToken();
    
    $id = "1N8OzWJbJ4FX0pQmcJuvLDaR9WOqjF0dajE4ks4nJhP0";
    
    echo "Fetching all tabs/sheets for Spreadsheet ID: $id\n";
    $url = "https://sheets.googleapis.com/v4/spreadsheets/" . urlencode($id);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: $httpCode\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "Spreadsheet Title: " . $data['properties']['title'] . "\n\n";
        echo "Available Sheets (Tabs):\n";
        foreach ($data['sheets'] as $sheet) {
            echo "- " . $sheet['properties']['title'] . " (ID: " . $sheet['properties']['sheetId'] . ")\n";
        }
    } else {
        echo "Response: $response\n";
    }

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
}
