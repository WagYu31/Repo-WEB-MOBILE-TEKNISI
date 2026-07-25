<?php
/**
 * APK Download Handler
 * Serves APK files for mobile app updates
 * URL: https://jadwal.id-giti.com/staff/download/index.php?file=LoewixSales-v1.8.0.apk
 */

$file = basename($_GET['file'] ?? '');

if (empty($file) || !preg_match('/^LoewixSales-v[\d.]+\.apk$/', $file)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$filePath = __DIR__ . '/' . $file;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'APK file not found: ' . htmlspecialchars($file);
    exit;
}

// Force download
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
?>
