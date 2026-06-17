<?php
include 'conn.php';
header('Content-Type: application/json');

$kode_invoice = trim($_GET['kode_invoice'] ?? '');
$current_kode = trim($_GET['current_kode'] ?? ''); // kode transaksi saat ini (exclude dari check)

if (empty($kode_invoice)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Cek apakah kode invoice sudah ada di pendapatan_kegiatan (exclude kode transaksi saat ini)
$sql = "SELECT no_invoice, kode FROM pendapatan_kegiatan WHERE no_invoice = ? AND deleted_at IS NULL";
$params = [$kode_invoice];
$types = 's';

if (!empty($current_kode)) {
    $sql .= " AND kode != ?";
    $params[] = $current_kode;
    $types .= 's';
}

$sql .= " LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'exists' => true,
        'message' => "Kode Invoice \"{$kode_invoice}\" sudah digunakan pada transaksi {$row['kode']}"
    ]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
?>
