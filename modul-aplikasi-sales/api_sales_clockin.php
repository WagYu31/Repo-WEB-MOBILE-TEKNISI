<?php
/**
 * API Sales Clock In — Tiba di lokasi customer
 * POST: kegiatan_id, sales_id, latitude, longitude, [is_mock]
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

$kegiatanId = intval($_POST['kegiatan_id'] ?? 0);
$salesId    = intval($_POST['sales_id']    ?? 0);
$lat        = trim($_POST['latitude']      ?? '');
$lon        = trim($_POST['longitude']     ?? '');
$isMock     = intval($_POST['is_mock']     ?? 0);
if (!$kegiatanId || !$salesId || empty($lat) || empty($lon)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
}

// Cek apakah jadwal kunjungan adalah hari ini atau masa lalu (tidak boleh masa depan)
$kegQuery = $conn->prepare("SELECT jadwal FROM kegiatan_sales WHERE id = ? AND deleted_at IS NULL LIMIT 1");
$kegQuery->bind_param('i', $kegiatanId);
$kegQuery->execute();
$kegRow = $kegQuery->get_result()->fetch_assoc();
$kegQuery->close();

if (!$kegRow) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Jadwal kunjungan tidak ditemukan']);
    exit;
}

$schedDate = date('Y-m-d', strtotime($kegRow['jadwal']));
$todayDate = date('Y-m-d');

if ($schedDate > $todayDate) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'code'    => 'FUTURE_SCHEDULE',
        'message' => 'Anda tidak bisa melakukan Clock In untuk jadwal kunjungan di masa mendatang (' . date('d M Y', strtotime($schedDate)) . ').',
    ]);
    exit;
}

// Blok Fake GPS (Bypass khusus untuk akun testing sales ID 14 di simulator)
if ($isMock === 1 && $salesId !== 14) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'code'    => 'FAKE_GPS_DETECTED',
        'message' => 'Fake GPS terdeteksi. Gunakan lokasi GPS asli perangkat Anda.',
    ]);
    exit;
}

// ── Validasi Geofence ──────────────────────────────────
// Ambil koordinat & radius geofence dari kegiatan, fallback ke customer
$geoCheck = $conn->prepare("
    SELECT ks.lat AS lat_kegiatan, ks.lon AS lon_kegiatan, ks.rad,
           c.lat  AS lat_customer,  c.lon  AS lon_customer
    FROM kegiatan_sales ks
    LEFT JOIN sales_customer c ON c.id = ks.id_customer
    WHERE ks.id = ? LIMIT 1
");
$geoCheck->bind_param('i', $kegiatanId);
$geoCheck->execute();
$geoData = $geoCheck->get_result()->fetch_assoc();

if ($geoData) {
    $targetLat = !empty($geoData['lat_kegiatan']) ? (float)$geoData['lat_kegiatan'] : (!empty($geoData['lat_customer']) ? (float)$geoData['lat_customer'] : null);
    $targetLon = !empty($geoData['lon_kegiatan']) ? (float)$geoData['lon_kegiatan'] : (!empty($geoData['lon_customer']) ? (float)$geoData['lon_customer'] : null);
    $maxRadius = !empty($geoData['rad']) ? (int)$geoData['rad'] : 100;

    if ($targetLat !== null && $targetLon !== null) {
        // Haversine distance calculation
        $earthRadius = 6371000; // meters
        $latFrom = deg2rad((float)$lat);
        $lonFrom = deg2rad((float)$lon);
        $latTo   = deg2rad($targetLat);
        $lonTo   = deg2rad($targetLon);
        $dLat = $latTo - $latFrom;
        $dLon = $lonTo - $lonFrom;
        $a = sin($dLat/2) * sin($dLat/2) + cos($latFrom) * cos($latTo) * sin($dLon/2) * sin($dLon/2);
        $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));

        if ($distance > $maxRadius) {
            http_response_code(403);
            echo json_encode([
                'status'  => 'error',
                'code'    => 'OUT_OF_GEOFENCE',
                'message' => 'Anda berada ' . round($distance) . 'm dari lokasi tujuan. Jarak maksimal: ' . $maxRadius . 'm.',
            ]);
            exit;
        }
    }
}

$now = date('Y-m-d H:i:s');

// Pastikan sales terdaftar di team kegiatan ini
$chkTeam = $conn->prepare("SELECT id FROM team_kegiatan_sales WHERE id_kegiatan_sales = ? AND id_sales = ? AND deleted_at IS NULL LIMIT 1");
$chkTeam->bind_param('ii', $kegiatanId, $salesId);
$chkTeam->execute();
if (!$chkTeam->get_result()->fetch_assoc()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak terdaftar di kunjungan ini']);
    exit;
}

// Cek sudah clock in sebelumnya?
$chk = $conn->prepare("SELECT id, ci_at FROM pelaksanaan_sales WHERE kegiatan_id = ? AND sales_id = ? LIMIT 1");
$chk->bind_param('ii', $kegiatanId, $salesId);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();

if ($existing && !empty($existing['ci_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sudah Clock In untuk kunjungan ini']);
    exit;
}

if ($existing) {
    // Update record yang sudah ada
    $upd = $conn->prepare("UPDATE pelaksanaan_sales SET ci_at = ?, lat_ci = ?, lon_ci = ?, status = 'berjalan', updated_at = NOW() WHERE id = ?");
    $upd->bind_param('sssi', $now, $lat, $lon, $existing['id']);
    $upd->execute();
} else {
    // Buat record baru
    $ins = $conn->prepare("INSERT INTO pelaksanaan_sales (kegiatan_id, sales_id, ci_at, lat_ci, lon_ci, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'berjalan', NOW(), NOW())");
    $ins->bind_param('iisss', $kegiatanId, $salesId, $now, $lat, $lon);
    $ins->execute();
}

// Update status kegiatan
$conn->query("UPDATE kegiatan_sales SET status = 'berjalan', updated_at = NOW() WHERE id = $kegiatanId AND status = 'dijadwalkan'");

http_response_code(201);
echo json_encode([
    'status'  => 'success',
    'message' => 'Clock In berhasil! Selamat bekerja.',
    'data'    => [
        'kegiatan_id' => $kegiatanId,
        'sales_id'    => $salesId,
        'ci_at'       => $now,
        'lat_ci'      => $lat,
        'lon_ci'      => $lon,
    ],
]);
