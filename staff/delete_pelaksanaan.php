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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE pelaksanaan_kegiatan SET deleted_at = ? WHERE id = ? AND deleted_at IS NULL");
if ($stmt) {
    $stmt->bind_param("si", $now, $id);
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    if ($success && $affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Data pelaksanaan berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data atau data sudah dihapus.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Query gagal disiapkan: ' . $conn->error]);
}

$conn->close();
?>
