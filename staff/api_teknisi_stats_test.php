<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'conn.php';

$teknisiId = intval($_GET['teknisi_id'] ?? 0);
$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));
$filterType = $_GET['filter_type'] ?? 'month'; // 'month' or 'quarter'

if ($teknisiId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'teknisi_id is required']);
    exit;
}

$months = [];
if ($filterType === 'quarter') {
    // Standard Quarter calculation (Jan-Mar, Apr-Jun, Jul-Sep, Oct-Dec)
    $startBulan = floor(($bulan - 1) / 3) * 3 + 1;
    $endBulan = $startBulan + 2;
    $startTahun = $tahun;

    $quarterStart = sprintf('%04d-%02d-01', $startTahun, $startBulan);
    $quarterEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $startTahun, $endBulan)));
    
    for ($i = $startBulan; $i <= $endBulan; $i++) {
        $months[] = sprintf('%04d-%02d', $startTahun, $i);
    }
} else {
    // Single month
    $quarterStart = sprintf('%04d-%02d-01', $tahun, $bulan);
    $quarterEnd = date('Y-m-t', strtotime($quarterStart));
    $months[] = sprintf('%04d-%02d', $tahun, $bulan);
}

$monthConditions = implode(',', array_map(function($m) { return "'$m'"; }, $months));

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

// 1. Jumlah Kegiatan (Lunas)
$sql_lunas = "SELECT COUNT(DISTINCT pk.kode) AS jumlah_lunas FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = $teknisiId AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($monthConditions) AND pk.deleted_at IS NULL";
$res_lunas = mysqli_query($conn, $sql_lunas);
$jumlahKegiatan = mysqli_fetch_assoc($res_lunas)['jumlah_lunas'] ?? 0;

// 2. Selesai count
$sql_selesai = "SELECT COUNT(DISTINCT pk.kode) AS jumlah FROM pelaksanaan_kegiatan pk JOIN kegiatan k ON k.id = pk.kegiatan_id WHERE pk.teknisi_id = $teknisiId AND pk.deleted_at IS NULL AND k.deleted_at IS NULL AND DATE(pk.waktu_mulai) >= '$quarterStart' AND DATE(pk.waktu_mulai) <= '$quarterEnd' AND k.status IN ('selesai', 'selesai by admin')";
$res_selesai = mysqli_query($conn, $sql_selesai);
$selesai = mysqli_fetch_assoc($res_selesai)['jumlah'] ?? 0;

// 3. Pendapatan
$sql_pendapatan = "SELECT COALESCE(SUM(share_amount), 0) AS total FROM (
    SELECT pk.kode,
           ROUND(pk.nominal_invoice / counts.tek_count) AS share_amount
    FROM pendapatan_kegiatan pk
    JOIN (
        SELECT kode, COUNT(*) AS tek_count
        FROM pendapatan_kegiatan
        WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($monthConditions) AND deleted_at IS NULL
        GROUP BY kode
    ) counts ON pk.kode = counts.kode
    WHERE pk.teknisi_id = $teknisiId
    AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($monthConditions)
    AND pk.deleted_at IS NULL
    GROUP BY pk.kode
) deduped";
$res_pendapatan = mysqli_query($conn, $sql_pendapatan);
$totalPendapatan = floatval(mysqli_fetch_assoc($res_pendapatan)['total'] ?? 0);

// 4. Fee 30k
$feeKodes = [];
$sql_fee = "SELECT k.kode FROM kegiatan k 
        WHERE k.created_at >= '$quarterStart' AND k.created_at < DATE_ADD('$quarterEnd', INTERVAL 1 DAY)
        AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
        GROUP BY k.kode";
$res_fee_kode = mysqli_query($conn, $sql_fee);
while ($r = mysqli_fetch_assoc($res_fee_kode)) $feeKodes[] = $r['kode'];

$totalFee = 0;
if (!empty($feeKodes)) {
    $kodePlaceholders = implode(',', array_map(function($k) { return "'$k'"; }, $feeKodes));
    $sql_fee_teknisi = "SELECT DISTINCT kode, teknisi_id
            FROM pelaksanaan_kegiatan 
            WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
    $res_fee_teknisi = mysqli_query($conn, $sql_fee_teknisi);
    
    $kodeTeknisi = [];
    while ($r = mysqli_fetch_assoc($res_fee_teknisi)) {
        $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
    }
    
    foreach ($kodeTeknisi as $kd => $tekIds) {
        $jml = count($tekIds);
        if ($jml > 0 && isset($tekIds[$teknisiId])) {
            $totalFee += 30000 / $jml;
        }
    }
}

$target = floatval($teknisiInfo['target'] ?? 0);
$totalKeseluruhan = $totalPendapatan + $totalFee;
$bonus = ($totalKeseluruhan > $target && $target > 0) ? ($totalKeseluruhan - $target) * 0.60 : 0;

$quarterLabel = '';
foreach ($months as $i => $m) {
    $dt = DateTime::createFromFormat('Y-m', $m);
    $quarterLabel .= ($i > 0 ? ', ' : '') . $dt->format('M Y');
}

$conn->close();

echo json_encode([
    'teknisi_id' => intval($teknisiInfo['id']),
    'nama_teknisi' => $teknisiInfo['nama'],
    'bulan' => $bulan,
    'tahun' => $tahun,
    'filter_type' => $filterType,
    'quarter_months' => $months,
    'quarter_label' => $quarterLabel,
    'target' => intval($target),
    'jumlah_kegiatan' => intval($jumlahKegiatan),
    'selesai' => intval($selesai),
    'fee' => intval($totalFee),
    'total_pendapatan' => intval($totalPendapatan),
    'total_keseluruhan' => intval($totalKeseluruhan),
    'bonus' => intval($bonus),
]);
