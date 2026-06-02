<?php
// Nonaktifkan output buffering agar header berfungsi dengan benar
ob_clean();
flush();

// Pastikan ada parameter file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("File tidak ditemukan!");
}

// Sanitasi nama file untuk keamanan
$file = basename($_GET['file']);
$filePath = __DIR__ . "/api/storage/app/image/" . $file;

// Periksa apakah file ada
if (!file_exists($filePath)) {
    die("File tidak tersedia!");
}

// Set header agar langsung terdownload
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Baca file dan kirim ke output
readfile($filePath);
exit;
?>
