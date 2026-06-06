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
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$waktu_mulai = isset($_POST['waktu_mulai']) ? trim($_POST['waktu_mulai']) : '';
$waktu_selesai = isset($_POST['waktu_selesai']) ? trim($_POST['waktu_selesai']) : '';
$permasalahan = isset($_POST['permasalahan']) ? trim($_POST['permasalahan']) : '';
$solusi = isset($_POST['solusi']) ? trim($_POST['solusi']) : '';
$keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

// Validate status
$validStatuses = ['berjalan', 'menunggu laporan', 'selesai', 'Lanjut Nanti', 'Lanjutan'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
    exit;
}

// Format waktu
$waktu_mulai_db = !empty($waktu_mulai) ? date('Y-m-d H:i:s', strtotime($waktu_mulai)) : null;
$waktu_selesai_db = !empty($waktu_selesai) ? date('Y-m-d H:i:s', strtotime($waktu_selesai)) : null;

$stmt = $conn->prepare("UPDATE pelaksanaan_kegiatan SET status = ?, waktu_mulai = ?, waktu_selesai = ?, permasalahan = ?, solusi = ?, keterangan = ? WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("ssssssi", $status, $waktu_mulai_db, $waktu_selesai_db, $permasalahan, $solusi, $keterangan, $id);
$success = $stmt->execute();
$affected = $stmt->affected_rows;

if ($success && $affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Data pelaksanaan berhasil diperbarui.']);
} elseif ($affected === 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau tidak ada perubahan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . $conn->error]);
}

$stmt->close();
?>
