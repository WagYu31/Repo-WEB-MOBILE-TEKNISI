<?php
include "conn.php";
session_start();

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi login habis, silakan login ulang.']);
    exit;
}

// Only allow admin
if (!isset($_SESSION['jabatan']) || !in_array($_SESSION['jabatan'], ['Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']);
    exit;
}

$teknisi_id = isset($_POST['teknisi_id']) ? intval($_POST['teknisi_id']) : 0;
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

if ($teknisi_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Teknisi tidak valid.']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
    exit;
}

// Hash password (compatible with Laravel's bcrypt)
$hashed = password_hash($new_password, PASSWORD_BCRYPT);

// Update password langsung di tabel user_teknisi (atau buat jika belum ada)
date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

// Ambil info dari tabel teknisi
$stmtTek = $conn->prepare("SELECT nik, nama, deleted_at FROM teknisi WHERE id = ?");
$stmtTek->bind_param("i", $teknisi_id);
$stmtTek->execute();
$resTek = $stmtTek->get_result();
if ($resTek->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Akun teknisi tidak ditemukan.']);
    exit;
}
$tekData = $resTek->fetch_assoc();
$stmtTek->close();

$nik = $tekData['nik'];
$nama = $tekData['nama'];
$tek_deleted_at = $tekData['deleted_at'];

// Cek apakah ada record di user_teknisi
$stmtCheck = $conn->prepare("SELECT id FROM user_teknisi WHERE teknisi_id = ?");
$stmtCheck->bind_param("i", $teknisi_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

$success = false;

if ($resCheck->num_rows === 0) {
    // Belum ada record di user_teknisi. Buat baru!
    $stmtInsert = $conn->prepare("INSERT INTO user_teknisi (username, password, teknisi_id, nama, created_at, updated_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtInsert->bind_param("ssissss", $nama, $hashed, $teknisi_id, $nama, $now, $now, $tek_deleted_at);
    $success = $stmtInsert->execute();
    $stmtInsert->close();
} else {
    // Sudah ada record di user_teknisi. Update password & pastikan deleted_at sinkron
    $stmtUpdate = $conn->prepare("UPDATE user_teknisi SET password = ?, username = ?, deleted_at = ?, updated_at = ? WHERE teknisi_id = ?");
    $stmtUpdate->bind_param("ssssi", $hashed, $nama, $tek_deleted_at, $now, $teknisi_id);
    $success = $stmtUpdate->execute();
    $stmtUpdate->close();
}
$stmtCheck->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil direset.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $conn->error]);
}
?>
