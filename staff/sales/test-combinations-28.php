<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "GoogleSheetsClient.php";

try {
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    $client = new GoogleSheetsClient($keyPath);
    $accessToken = $client->getAccessToken();

    // 1st char options: 1, I, l
    $first_chars = ['1', 'I', 'l'];
    
    // 28th char options: l, I, 1
    $middle_chars = ['l', 'I', '1'];

    // Template with placeholders: {1}9OV073XNHmo7zACGOpYPyEcmod{28}ZmEv4wzFq7Fg_uoU
    foreach ($first_chars as $c1) {
        foreach ($middle_chars as $c28) {
            $id = $c1 . "9OV073XNHmo7zACGOpYPyEcmod" . $c28 . "ZmEv4wzFq7Fg_uoU";
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
                echo "Title: " . $data['properties']['title'] . "\n";
                echo "Sheets (Tabs):\n";
                foreach ($data['sheets'] as $sheet) {
                    echo "- " . $sheet['properties']['title'] . "\n";
                }
                echo "\n";
                exit; // Stop once we find it!
            } else {
                echo "Response: $response\n\n";
            }
        }
    }
    echo "Done testing all 9 combinations. None succeeded.\n";

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
}
