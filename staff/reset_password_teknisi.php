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

// Cari user_id dari tabel user_teknisi
$stmt_lookup = $conn->prepare("SELECT user_id FROM user_teknisi WHERE teknisi_id = ? LIMIT 1");
$stmt_lookup->bind_param("i", $teknisi_id);
$stmt_lookup->execute();
$result = $stmt_lookup->get_result();
$row = $result->fetch_assoc();
$stmt_lookup->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Akun user untuk teknisi ini tidak ditemukan.']);
    exit;
}

$user_id = $row['user_id'];

// Update password di tabel users
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed, $user_id);
$success = $stmt->execute();
$affected = $stmt->affected_rows;

if ($success && $affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil direset.']);
} elseif ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan atau password sama.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $conn->error]);
}

$stmt->close();
?>
