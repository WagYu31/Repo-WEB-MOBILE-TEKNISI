<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Mock server request method
$_SERVER['REQUEST_METHOD'] = 'POST';

// Set parameters
$_POST['spreadsheet_id'] = "1N8OzWJbJ4FX0pQmcJuvLDaR9WOqjF0dajE4ks4nJhP0";
$_POST['sheet_name'] = "LAPORAN MINGGUAN";
$_POST['id_sales'] = 20;
$_POST['bulan'] = "2026-07";

// Mock session
session_start();
$_SESSION['role'] = 'admin';

try {
    echo "Executing proses-sync-sheets.php with mocked POST/session...\n";
    include "proses-sync-sheets.php";
    echo "\nExecution finished.\n";
} catch (Exception $e) {
    echo "Exception caught in test-sync-db.php: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
