<?php
/**
 * Mobile API: Get statistik satu teknisi (pendapatan, bonus, target, kegiatan)
 * 
 * Usage: GET /api_teknisi_stats.php?teknisi_id=1&bulan=7&tahun=2026&filter_type=month
 * 
 * Response format matches PendapatanResponse model in Flutter
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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
    $startBulan = floor(($bulan - 1) / 3) * 3 + 1;
    $endBulan = $startBulan + 2;
    $startTahun = $tahun;

    $quarterStart = sprintf('%04d-%02d-01', $startTahun, $startBulan);
    $quarterEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $startTahun, $endBulan)));
    
    for ($i = $startBulan; $i <= $endBulan; $i++) {
        $months[] = sprintf('%04d-%02d', $startTahun, $i);
    }
} else {
    $quarterStart = sprintf('%04d-%02d-01', $tahun, $bulan);
    $quarterEnd = date('Y-m-t', strtotime($quarterStart));
    $months[] = sprintf('%04d-%02d', $tahun, $bulan);
}

$monthCount = count($months);
$monthConditions = implode(',', array_map(function($m) { return "'$m'"; }, $months));

// 1. Get teknisi info + target
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

// 2. Count kegiatan (same as get_teknisi_data.php)
$sql_kegiatan = "SELECT COUNT(DISTINCT k_c.kode) AS jumlah 
                 FROM pelaksanaan_kegiatan pk_c 
                 JOIN kegiatan k_c ON k_c.id = pk_c.kegiatan_id
                 JOIN customer cust_c ON cust_c.id = k_c.customer_id
                 WHERE pk_c.teknisi_id = $teknisiId AND pk_c.deleted_at IS NULL AND k_c.deleted_at IS NULL
                 AND DATE(pk_c.waktu_mulai) >= '$quarterStart' AND DATE(pk_c.waktu_mulai) <= '$quarterEnd'";
$res_kegiatan = mysqli_query($conn, $sql_kegiatan);
$jumlahKegiatan = intval(mysqli_fetch_assoc($res_kegiatan)['jumlah'] ?? 0);

// 3. Count kegiatan selesai
$sql_selesai = "SELECT COUNT(DISTINCT k_c.kode) AS jumlah 
                FROM pelaksanaan_kegiatan pk_c 
                JOIN kegiatan k_c ON k_c.id = pk_c.kegiatan_id
                WHERE pk_c.teknisi_id = $teknisiId AND pk_c.deleted_at IS NULL AND k_c.deleted_at IS NULL
                AND DATE(pk_c.waktu_mulai) >= '$quarterStart' AND DATE(pk_c.waktu_mulai) <= '$quarterEnd'
                AND pk_c.status = 'selesai'";
$res_selesai = mysqli_query($conn, $sql_selesai);
$selesai = intval(mysqli_fetch_assoc($res_selesai)['jumlah'] ?? 0);

// 4. Get total pendapatan from pendapatan_kegiatan (SAME LOGIC as web get_teknisi_data.php)
$sql_pendapatan = "SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (
                    SELECT COUNT(*) FROM pendapatan_kegiatan pk2 
                    WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') IN ($monthConditions) AND pk2.deleted_at IS NULL
                ))), 0) AS total
                FROM pendapatan_kegiatan pk 
                WHERE pk.teknisi_id = $teknisiId 
                AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($monthConditions) 
                AND pk.deleted_at IS NULL";
$res_pendapatan = mysqli_query($conn, $sql_pendapatan);
$totalPendapatan = floatval(mysqli_fetch_assoc($res_pendapatan)['total'] ?? 0);

// 5. Get total fee (SAME LOGIC as web get_teknisi_data.php: 30k per kegiatan / jumlah teknisi)
$feeKodes = [];
$sql_fee_kodes = "SELECT k.kode FROM kegiatan k 
                  WHERE k.created_at >= '$quarterStart' AND k.created_at < DATE_ADD('$quarterEnd', INTERVAL 1 DAY)
                  AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
                  AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
                  GROUP BY k.kode";
$res_fk = mysqli_query($conn, $sql_fee_kodes);
if ($res_fk) {
    while ($r = mysqli_fetch_assoc($res_fk)) {
        $feeKodes[] = $r['kode'];
    }
}

$totalFee = 0;
if (!empty($feeKodes)) {
    $kodePlaceholders = implode(',', array_map(function($k) { return "'$k'"; }, $feeKodes));
    $sql_fee_tek = "SELECT DISTINCT kode, teknisi_id
                    FROM pelaksanaan_kegiatan 
                    WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
    $res_ft = mysqli_query($conn, $sql_fee_tek);
    if ($res_ft) {
        $kodeTeknisi = [];
        while ($r = mysqli_fetch_assoc($res_ft)) {
            $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
        }
        foreach ($kodeTeknisi as $kd => $tekIds) {
            $jml = count($tekIds);
            if ($jml > 0 && isset($tekIds[$teknisiId])) {
                $totalFee += 30000 / $jml;
            }
        }
    }
}

// 6. Target, Total & Bonus (Exact web panel logic)
$target = floatval($teknisiInfo['target'] ?? 0) * $monthCount;
$totalKeseluruhan = $totalPendapatan + $totalFee;
$bonus = 0;
if ($totalKeseluruhan > $target && $target > 0) {
    $bonus = ($totalKeseluruhan - $target) * 0.60;
}

$quarterLabel = '';
foreach ($months as $i => $m) {
    $dt = DateTime::createFromFormat('Y-m', $m);
    $quarterLabel .= ($i > 0 ? ', ' : '') . ($dt ? $dt->format('M Y') : $m);
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
    'invoice' => 0,
    'fee' => intval($totalFee),
    'total_pendapatan' => intval($totalPendapatan),
    'total_keseluruhan' => intval($totalKeseluruhan),
    'bonus' => intval($bonus),
    'grand_total' => intval($totalPendapatan),
]);
