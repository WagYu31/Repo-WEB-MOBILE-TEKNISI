<?php
include "session.php";

$file = $_GET['file'] ?? '';
$basePath = "../../repositories/staging-teknisi-api/storage/app/public/tutorial/";
$filePath = $basePath . $file;

if (!empty($file) && file_exists($filePath)) {
    $mime = mime_content_type($filePath);
    $filename = basename($filePath);

    header("Content-Type: $mime");
    header("Content-Disposition: inline; filename=\"$filename\"");
    header("Content-Length: " . filesize($filePath));
    
    readfile($filePath);
    exit;
} else {
    http_response_code(404);
    echo "File tidak ditemukan atau akses dilarang.";
}