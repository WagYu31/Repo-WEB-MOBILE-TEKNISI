<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "GoogleSheetsClient.php";

try {
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    echo "Checking key path: " . $keyPath . "\n";
    echo "File exists: " . (file_exists($keyPath) ? 'Yes' : 'No') . "\n";
    echo "Is readable: " . (is_readable($keyPath) ? 'Yes' : 'No') . "\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($keyPath)), -4) . "\n";

    if (file_exists($keyPath)) {
        $content = file_get_contents($keyPath);
        $json = json_decode($content, true);
        echo "Valid JSON: " . ($json ? 'Yes' : 'No') . "\n";
        if ($json) {
            echo "Client Email: " . ($json['client_email'] ?? 'Not set') . "\n";
            echo "Private Key length: " . (isset($json['private_key']) ? strlen($json['private_key']) : 0) . "\n";
        }
    }

    echo "\nTesting Client Authentication...\n";
    $client = new GoogleSheetsClient($keyPath);
    
    // Test spreadsheet (the one shared by user)
    $testSpreadsheetId = "19OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU";
    
    // Just try to clear a dummy cell to test authentication & write permissions
    // Using a safe dummy cell Sheet1!Z100
    echo "Sending authentication and clearing cell Sheet1!Z100...\n";
    $res = $client->clearValues($testSpreadsheetId, "Sheet1!Z100");
    echo "Success! Google API Response:\n";
    print_r($res);

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
