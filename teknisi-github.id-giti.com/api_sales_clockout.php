<?php
/**
 * API Sales Clock Out — Selesai kunjungan customer
 * POST: kegiatan_id, sales_id, latitude, longitude, catatan_visit, [is_mock]
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

$kegiatanId = intval($_POST['kegiatan_id']   ?? 0);
$salesId    = intval($_POST['sales_id']      ?? 0);
$lat        = trim($_POST['latitude']        ?? '');
$lon        = trim($_POST['longitude']       ?? '');
$catatan    = trim($_POST['catatan_visit']   ?? '');
$isMock     = intval($_POST['is_mock']       ?? 0);

if (!$kegiatanId || !$salesId || empty($lat) || empty($lon)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
}

// Blok Fake GPS
if ($isMock === 1) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'code'    => 'FAKE_GPS_DETECTED',
        'message' => 'Fake GPS terdeteksi. Gunakan lokasi GPS asli perangkat Anda.',
    ]);
    exit;
}

$now = date('Y-m-d H:i:s');

// Cek sudah Clock In
$chk = $conn->prepare("SELECT id, ci_at, co_at FROM pelaksanaan_sales WHERE kegiatan_id = ? AND sales_id = ? LIMIT 1");
$chk->bind_param('ii', $kegiatanId, $salesId);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();

if (!$existing || empty($existing['ci_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum Clock In untuk kunjungan ini']);
    exit;
}

if (!empty($existing['co_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sudah Clock Out untuk kunjungan ini']);
    exit;
}

// Hitung durasi kunjungan
$ci    = new DateTime($existing['ci_at']);
$co    = new DateTime($now);
$diff  = $ci->diff($co);
$durasi = sprintf('%02d:%02d:%02d', $diff->h + ($diff->days * 24), $diff->i, $diff->s);

// Update clock out
$upd = $conn->prepare("UPDATE pelaksanaan_sales SET co_at = ?, lat_co = ?, lon_co = ?, catatan_visit = ?, status = 'selesai', updated_at = NOW() WHERE id = ?");
$upd->bind_param('ssssi', $now, $lat, $lon, $catatan, $existing['id']);
$upd->execute();

// Cascade: update kegiatan jika semua sales sudah selesai
$chkAll = $conn->query("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN ps.status = 'selesai' THEN 1 ELSE 0 END) AS selesai_count
    FROM team_kegiatan_sales tks
    LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
    WHERE tks.id_kegiatan_sales = $kegiatanId AND tks.deleted_at IS NULL
");
$allRow = $chkAll->fetch_assoc();
if ($allRow && $allRow['total'] > 0 && $allRow['total'] == $allRow['selesai_count']) {
    $conn->query("UPDATE kegiatan_sales SET status = 'selesai', updated_at = NOW() WHERE id = $kegiatanId");
}

echo json_encode([
    'status'  => 'success',
    'message' => 'Clock Out berhasil. Kunjungan selesai!',
    'data'    => [
        'kegiatan_id' => $kegiatanId,
        'sales_id'    => $salesId,
        'ci_at'       => $existing['ci_at'],
        'co_at'       => $now,
        'durasi'      => $durasi,
        'lat_co'      => $lat,
        'lon_co'      => $lon,
    ],
]);
