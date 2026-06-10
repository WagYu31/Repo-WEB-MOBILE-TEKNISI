<?php
include "conn.php";
include "session.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']);
    exit;
}

$kode = $_POST['kode'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';

if (empty($kode)) {
    echo json_encode(['success' => false, 'message' => 'Kode transaksi tidak boleh kosong.']);
    exit;
}

$stmt = $conn->prepare("UPDATE kegiatan SET keterangan = ? WHERE kode = ? AND deleted_at IS NULL");
$stmt->bind_param("ss", $keterangan, $kode);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan catatan.']);
}

$stmt->close();
$conn->close();
