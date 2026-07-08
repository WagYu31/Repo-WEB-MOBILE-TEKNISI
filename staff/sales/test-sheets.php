<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "GoogleSheetsClient.php";

try {
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    $client = new GoogleSheetsClient($keyPath);
    $accessToken = $client->getAccessToken();
    
    // Variations of the ID due to potential font lookalikes
    $variations = [
        "19OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU", // starting with number 1
        "I9OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU", // starting with capital letter I
        "l9OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU"  // starting with lowercase letter l
    ];
    
    foreach ($variations as $id) {
        echo "Testing Spreadsheet ID: $id\n";
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
            echo "SUCCESS! Found valid spreadsheet with this ID!\n";
            $data = json_decode($response, true);
            echo "Title: " . $data['properties']['title'] . "\n\n";
        } else {
            echo "Response: $response\n\n";
        }
    }

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
}
