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

// Update password langsung di tabel user_teknisi
$stmt = $conn->prepare("UPDATE user_teknisi SET password = ? WHERE teknisi_id = ? AND deleted_at IS NULL");
$stmt->bind_param("si", $hashed, $teknisi_id);
$success = $stmt->execute();
$affected = $stmt->affected_rows;

if ($success && $affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil direset.']);
} elseif ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'Akun teknisi tidak ditemukan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $conn->error]);
}

$stmt->close();
?>
