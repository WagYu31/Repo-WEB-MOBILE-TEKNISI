<?php
/**
 * Mobile API: Get statistik satu teknisi (pendapatan, bonus, target, kegiatan)
 * 
 * Usage: GET /api_teknisi_stats.php?teknisi_id=1&bulan=6&tahun=2026
 * 
 * Response format matches PendapatanResponse model in Flutter
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Use same connection as other staff files
include 'conn.php';
date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

// Validate params
$teknisiId = intval($_GET['teknisi_id'] ?? 0);
$bulan = intval($_GET['bulan'] ?? date('n'));
$tahun = intval($_GET['tahun'] ?? date('Y'));

if ($teknisiId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'teknisi_id is required']);
    exit;
}

$date = sprintf('%04d-%02d', $tahun, $bulan);

// Get teknisi info + target
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

// Count kegiatan (same logic as get_teknisi_data.php)
$stmtKegiatan = $conn->prepare("
    SELECT COUNT(DISTINCT k.kode) AS jumlah
    FROM team_kegiatan tk 
    JOIN kegiatan k ON tk.kegiatan_id = k.id 
    WHERE tk.teknisi_id = ? 
    AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? 
    AND tk.deleted_at IS NULL
");
$stmtKegiatan->bind_param("is", $teknisiId, $date);
$stmtKegiatan->execute();
$jumlahKegiatan = $stmtKegiatan->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtKegiatan->close();

// Count kegiatan selesai
$stmtSelesai = $conn->prepare("
    SELECT COUNT(DISTINCT k.kode) AS jumlah
    FROM team_kegiatan tk 
    JOIN kegiatan k ON tk.kegiatan_id = k.id 
    WHERE tk.teknisi_id = ? 
    AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? 
    AND tk.deleted_at IS NULL
    AND tk.status = 'selesai'
");
$stmtSelesai->bind_param("is", $teknisiId, $date);
$stmtSelesai->execute();
$selesai = $stmtSelesai->get_result()->fetch_assoc()['jumlah'] ?? 0;
$stmtSelesai->close();

// Get total pendapatan from pendapatan_kegiatan (SAME LOGIC as web: nominal_invoice / jumlah_teknisi)
$stmtPendapatan = $conn->prepare("
    SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (
        SELECT COUNT(*) FROM pendapatan_kegiatan pk2 
        WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL
    ))), 0) AS total
    FROM pendapatan_kegiatan pk 
    WHERE pk.teknisi_id = ? 
    AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? 
    AND pk.deleted_at IS NULL
");
$stmtPendapatan->bind_param("sis", $date, $teknisiId, $date);
$stmtPendapatan->execute();
$totalPendapatan = floatval($stmtPendapatan->get_result()->fetch_assoc()['total'] ?? 0);
$stmtPendapatan->close();

// Get total fee (SAME LOGIC as web: 30k per kegiatan dibagi jumlah teknisi, hanya kegiatan tanpa pendapatan_kegiatan)
$rangeStart = $date . '-01';
$rangeEnd = date('Y-m-t', strtotime($rangeStart));

$feeKodes = [];
$sqlFeeKode = "SELECT k.kode FROM kegiatan k 
    WHERE k.created_at >= '$rangeStart' AND k.created_at < DATE_ADD('$rangeEnd', INTERVAL 1 DAY)
    AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
    AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
    GROUP BY k.kode";
$resFeeKode = $conn->query($sqlFeeKode);
while ($rFee = $resFeeKode->fetch_assoc()) $feeKodes[] = $rFee['kode'];

$totalFee = 0;
if (!empty($feeKodes)) {
    $kodePlaceholders = implode(',', array_fill(0, count($feeKodes), '?'));
    $kodeTypes = str_repeat('s', count($feeKodes));
    
    $sqlFeeTek = "SELECT DISTINCT kode, teknisi_id
            FROM pelaksanaan_kegiatan 
            WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
    $stmtFeeTek = $conn->prepare($sqlFeeTek);
    $stmtFeeTek->bind_param($kodeTypes, ...$feeKodes);
    $stmtFeeTek->execute();
    $resFeeTek = $stmtFeeTek->get_result();
    
    $kodeTeknisi = [];
    while ($rFT = $resFeeTek->fetch_assoc()) {
        $kodeTeknisi[$rFT['kode']][$rFT['teknisi_id']] = true;
    }
    $stmtFeeTek->close();
    
    foreach ($kodeTeknisi as $kd => $tekIds) {
        $jml = count($tekIds);
        if ($jml > 0 && isset($tekIds[$teknisiId])) {
            $totalFee += 30000 / $jml;
        }
    }
}

// Calculate bonus (same formula as web panel: 60% of surplus over target)
$target = floatval($teknisiInfo['target'] ?? 0);
$totalKeseluruhan = $totalPendapatan + $totalFee;
$bonus = 0;
if ($totalKeseluruhan > $target && $target > 0) {
    $bonus = ($totalKeseluruhan - $target) * 0.60;
}

$conn->close();

// Return in format matching PendapatanResponse model
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
?>
