<?php
/**
 * API Sales Login
 * POST: nik, password
 * Returns: sales profile JSON
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Read DB credentials from Laravel's .env
$envPath = __DIR__ . '/../.env';
$envVars = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }
}
$servername = $envVars['DB_HOST']     ?? 'localhost';
$username   = $envVars['DB_USERNAME'] ?? 'teknisi_api_root';
$password   = $envVars['DB_PASSWORD'] ?? 'OffOff@18';
$database   = $envVars['DB_DATABASE'] ?? 'teknisi_api_root';

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset('utf8');
date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? $_POST['nik'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit;
}

// Ambil data sales berdasarkan username (nama) ATAU nik
$stmt = $conn->prepare("SELECT id, nik, nama, no_tlp, jabatan, password, foto FROM sales WHERE (nama = ? OR nik = ?) AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$sales  = $result->fetch_assoc();

if (!$sales) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan']);
    exit;
}

// Verifikasi password (plain text atau hash)
$passOk = false;
if (password_verify($password, $sales['password'])) {
    $passOk = true;
} elseif ($password === $sales['password']) {
    // Plain text fallback (untuk akun lama)
    $passOk = true;
}

if (!$passOk) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Password salah']);
    exit;
}

echo json_encode([
    'status'  => 'success',
    'message' => 'Login berhasil',
    'data'    => [
        'id'      => (int)$sales['id'],
        'nik'     => $sales['nik'],
        'nama'    => $sales['nama'],
        'no_tlp'  => $sales['no_tlp'] ?? '',
        'jabatan' => $sales['jabatan'] ?? 'Sales',
        'foto'    => $sales['foto'] ?? '',
    ],
]);
