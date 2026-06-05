<?php
/**
 * Upload Proxy - Bypass Cloudflare Bot Fight Mode
 * Menerima base64-encoded images via JSON POST, decode, 
 * lalu forward ke Laravel API via localhost
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
    echo json_encode(['message' => 'Invalid JSON']);
    exit;
}

// Validasi field wajib
$required = ['kegiatan_id', 'teknisi_id', 'image_satu'];
foreach ($required as $field) {
    if (empty($data[$field])) {
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

foreach ($imageFields as $field) {
    if (!empty($data[$field])) {
        $imageData = base64_decode($data[$field]);
        if ($imageData === false) continue;
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpFile, $imageData);
        $tempFiles[] = $tmpFile;
        
        $postFields[$field] = new CURLFile($tmpFile, 'image/jpeg', $field . '.jpg');
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
    CURLOPT_TIMEOUT => 60,
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
    echo json_encode(['message' => 'Proxy error: ' . $error]);
    exit;
}

http_response_code($httpCode);
echo $response;
