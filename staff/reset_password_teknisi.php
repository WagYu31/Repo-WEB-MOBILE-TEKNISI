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
if (!isset($_SESSION['jabatan']) || !in_array($_SESSION['jabatan'], ['Admin', 'SA'])) {
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

// Update di tabel users (Laravel auth)
$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE teknisi_id = ?");
mysqli_stmt_bind_param($stmt, "si", $hashed, $teknisi_id);
$success = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);

if ($success && $affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil direset.']);
} elseif ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'Akun teknisi tidak ditemukan di tabel users.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update password: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
?>
