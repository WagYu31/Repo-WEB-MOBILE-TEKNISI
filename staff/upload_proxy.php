<?php
/**
 * Upload Proxy - Bypass Cloudflare Bot Fight Mode
 * Menerima base64-encoded images via JSON POST, decode, 
 * lalu forward ke Laravel API via localhost sebagai multipart
 */

// Increase PHP limits for large image uploads
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '50M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '120');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Base64-Upload');

// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

// Baca JSON body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON', 'raw_length' => strlen($input)]);
    exit;
}

// Validasi field wajib (tanpa image - bisa sync tanpa gambar)
$required = ['kegiatan_id', 'teknisi_id'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(422);
        echo json_encode(['message' => "Field $field is required"]);
        exit;
    }
}

// Siapkan cURL multipart ke localhost
$ch = curl_init('http://127.0.0.1/api/v4/pelaksanaanselesai');

$postFields = [
    'kegiatan_id' => $data['kegiatan_id'] ?? '',
    'teknisi_id' => $data['teknisi_id'] ?? '',
    'permasalahan' => $data['permasalahan'] ?? '',
    'solusi' => $data['solusi'] ?? '',
    'keterangan' => $data['keterangan'] ?? '',
    'keterangan_garansi' => $data['keterangan_garansi'] ?? '',
];

// Decode dan simpan images sementara
$tempFiles = [];
$imageFields = ['image_satu', 'image_dua', 'image_tiga', 'image_empat', 'image_lima'];
$imageCount = 0;

foreach ($imageFields as $field) {
    if (!empty($data[$field])) {
        $imageData = base64_decode($data[$field]);
        if ($imageData === false) {
            continue;
        }
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpFile, $imageData);
        $tempFiles[] = $tmpFile;
        
        // Detect if it's a video or image
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        $ext = 'jpg';
        if (strpos($mimeType, 'video') !== false) $ext = 'mp4';
        elseif (strpos($mimeType, 'png') !== false) $ext = 'png';
        
        $postFields[$field] = new CURLFile($tmpFile, $mimeType, $field . '.' . $ext);
        $imageCount++;
    }
}

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Host: api-teknisi.id-giti.com',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 120,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Cleanup temp files
foreach ($tempFiles as $f) {
    @unlink($f);
}

if ($error) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Proxy error: ' . $error,
        'images_received' => $imageCount,
    ]);
    exit;
}

http_response_code($httpCode);
echo $response;
