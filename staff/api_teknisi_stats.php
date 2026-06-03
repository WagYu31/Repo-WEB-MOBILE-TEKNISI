<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'conn.php';

$teknisiId = intval($_GET['teknisi_id'] ?? 0);
$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));

if ($teknisiId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'teknisi_id is required']);
    exit;
}

$date = sprintf('%04d-%02d', $tahun, $bulan);

$stmtInfo = $conn->prepare("SELECT id, nik, nama, target FROM teknisi WHERE id = ? AND deleted_at IS NULL");
$stmtInfo->bind_param("i", $teknisiId);
$stmtInfo->execute();
$teknisiInfo = $stmtInfo->get_result()->fetch_assoc();
$stmtInfo->close();

if (!$teknisiInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'Teknisi tidak ditemukan']);
    exit;
}

$stmtKegiatan = $conn->prepare("SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL");
$stmtKegiatan->bind_param("is", $teknisiId, $date);
$stmtKegiatan->execute();
$jumlahKegiatan = $stmtKegiatan->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtKegiatan->close();

$stmtSelesai = $conn->prepare("SELECT COUNT(DISTINCT k.kode) AS jumlah FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = ? AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL AND tk.status = 'selesai'");
$stmtSelesai->bind_param("is", $teknisiId, $date);
$stmtSelesai->execute();
$selesai = $stmtSelesai->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtSelesai->close();

$stmtPendapatan = $conn->prepare("SELECT COALESCE(SUM(pk.pendapatan), 0) AS total FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = ? AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL");
$stmtPendapatan->bind_param("is", $teknisiId, $date);
$stmtPendapatan->execute();
$totalPendapatan = floatval($stmtPendapatan->get_result()->fetch_assoc()['total'] ?? 0);
$stmtPendapatan->close();

$stmtFee = $conn->prepare("SELECT COALESCE(SUM(k.paid), 0) AS total FROM team_kegiatan tk JOIN (SELECT kode, CASE WHEN jadwal < '2025-08-01' THEN 0 ELSE paid END AS paid FROM kegiatan WHERE DATE_FORMAT(jadwal, '%Y-%m') = ? AND paid REGEXP '^[0-9]+$' GROUP BY kode, paid, jadwal) AS k ON tk.kode = k.kode WHERE tk.teknisi_id = ? AND tk.deleted_at IS NULL");
$stmtFee->bind_param("si", $date, $teknisiId);
$stmtFee->execute();
$totalFee = floatval($stmtFee->get_result()->fetch_assoc()['total'] ?? 0);
$stmtFee->close();

$target = floatval($teknisiInfo['target'] ?? 0);
$totalKeseluruhan = $totalPendapatan + $totalFee;
$bonus = ($totalKeseluruhan > $target && $target > 0) ? ($totalKeseluruhan - $target) * 0.60 : 0;

$conn->close();

echo json_encode([
    'teknisi_id' => intval($teknisiInfo['id']),
    'nama_teknisi' => $teknisiInfo['nama'],
    'bulan' => $bulan,
    'tahun' => $tahun,
    'target' => intval($target),
    'jumlah_kegiatan' => intval($jumlahKegiatan),
    'selesai' => intval($selesai),
    'invoice' => 0,
    'fee' => intval($totalFee),
    'total_pendapatan' => intval($totalPendapatan),
    'total_keseluruhan' => intval($totalKeseluruhan),
    'bonus' => intval($bonus),
]);
