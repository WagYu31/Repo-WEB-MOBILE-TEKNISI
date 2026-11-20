<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

$date = $_GET['date'] ?? date('Y-m');
$dateEnd = $_GET['date_end'] ?? $date; // Optional: for multi-month range

// Build list of months in range
$months = [];
$cur = $date;
while ($cur <= $dateEnd) {
    $months[] = $cur;
    $cur = date('Y-m', strtotime($cur . '-01 +1 month'));
}

// Calculate absolute date range
$rangeStart = $months[0] . '-01';
$rangeEnd = date('Y-m-t', strtotime(end($months) . '-01'));
$monthCount = count($months);

// Build IN clause for months
$monthPlaceholders = implode(',', array_fill(0, $monthCount, '?'));
$monthTypes = str_repeat('s', $monthCount);

$targetPeriodWhereSql = targetTercapaiPeriodWhereSql($months, 'pk.tanggal', 'k_lunas.tgl_lunas');

// === Main query: teknisi + kegiatan count + pendapatan across range ===
$sql = "SELECT 
            t.id, t.nik, t.nama, t.telp, t.target,
            (SELECT COUNT(*) FROM pelaksanaan_kegiatan pk_c 
             JOIN kegiatan k_c ON k_c.id = pk_c.kegiatan_id
             JOIN customer cust_c ON cust_c.id = k_c.customer_id
             WHERE pk_c.teknisi_id = t.id AND pk_c.deleted_at IS NULL AND k_c.deleted_at IS NULL
             AND DATE(pk_c.waktu_mulai) >= ? AND DATE(pk_c.waktu_mulai) <= ?) AS jumlah_kegiatan,
            (SELECT COALESCE(SUM(ROUND((pk.nominal_invoice / (
                SELECT COUNT(*) FROM pendapatan_kegiatan pk2 
                WHERE pk2.kode = pk.kode AND pk2.deleted_at IS NULL
            )) * pk.porsi_kerja)), 0)
             FROM pendapatan_kegiatan pk 
             JOIN " . targetTercapaiLunasSubquerySql('k_lunas') . " ON k_lunas.kode = pk.kode
             WHERE pk.teknisi_id = t.id AND pk.deleted_at IS NULL AND $targetPeriodWhereSql) AS total_pendapatan
        FROM teknisi t
        WHERE t.deleted_at IS NULL
        ORDER BY t.nama ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $rangeStart, $rangeEnd);
$stmt->execute();
$result = $stmt->get_result();
$technicians = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Fee 30k calculation across range ===
$feeKodes = [];
$sql = "SELECT k.kode FROM kegiatan k 
        WHERE k.created_at >= '$rangeStart' AND k.created_at < DATE_ADD('$rangeEnd', INTERVAL 1 DAY)
        AND k.paid REGEXP '^[0-9]+$' AND k.deleted_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode)
        GROUP BY k.kode";
$res = mysqli_query($conn, $sql);
while ($r = mysqli_fetch_assoc($res)) $feeKodes[] = $r['kode'];

$feeMap = [];
if (!empty($feeKodes)) {
    $kodePlaceholders = implode(',', array_fill(0, count($feeKodes), '?'));
    $kodeTypes = str_repeat('s', count($feeKodes));
    
    $sql = "SELECT DISTINCT kode, teknisi_id
            FROM pelaksanaan_kegiatan 
            WHERE kode IN ($kodePlaceholders) AND waktu_mulai IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($kodeTypes, ...$feeKodes);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $kodeTeknisi = [];
    while ($r = $res->fetch_assoc()) {
        $kodeTeknisi[$r['kode']][$r['teknisi_id']] = true;
    }
    $stmt->close();
    
    foreach ($kodeTeknisi as $kd => $tekIds) {
        $jml = count($tekIds);
        if ($jml > 0) {
            $share = 30000 / $jml;
            foreach ($tekIds as $tid => $_) {
                if (!isset($feeMap[$tid])) $feeMap[$tid] = 0;
                $feeMap[$tid] += $share;
            }
        }
    }
}

// === Build response ===
$tableData = [];
$chartData = ['labels' => [], 'targets' => [], 'pendapatan' => [], 'bonus' => []];

foreach ($technicians as $row) {
    $pendapatan = floatval($row['total_pendapatan'] ?? 0);
    $fee = floatval($feeMap[$row['id']] ?? 0);
    $total_penghasilan = $pendapatan + $fee;
    // For multi-month, multiply target by number of months
    $target = floatval($row['target'] ?? 0) * $monthCount;
    $bonus = 0;

    if ($total_penghasilan > $target && $target > 0) {
        $bonus = round(($total_penghasilan - $target) * 0.60);
    }

    $tableData[] = [
        'id' => $row['id'],
        'nik' => $row['nik'],
        'nama' => $row['nama'],
        'jumlah_kegiatan' => $row['jumlah_kegiatan'] ?? 0,
        'total_pendapatan' => $pendapatan,
        'total_fee' => $fee,
        'target' => $target,
        'bonus' => $bonus
    ];

    $chartData['labels'][] = $row['nama'];
    $chartData['targets'][] = $target;
    $chartData['pendapatan'][] = $total_penghasilan;
    $chartData['bonus'][] = $bonus;
}

usort($tableData, function($a, $b) {
    $totalA = $a['total_pendapatan'] + $a['total_fee'];
    $totalB = $b['total_pendapatan'] + $b['total_fee'];
    return $totalB <=> $totalA;
});

// ═══ GRAND TOTAL PENDAPATAN: Match Detail Invoice & Rekapitulasi (supports 50% porsi_kerja) ═══
$sqlGrandPend = "SELECT COALESCE(SUM(ROUND((pk.nominal_invoice / counts.tek_count) * pk.porsi_kerja)), 0) as total
    FROM pendapatan_kegiatan pk
    JOIN (
        SELECT kode, COUNT(*) as tek_count
        FROM pendapatan_kegiatan
        WHERE deleted_at IS NULL
        GROUP BY kode
    ) counts ON pk.kode = counts.kode
    JOIN " . targetTercapaiLunasSubquerySql('k_lunas') . " ON k_lunas.kode = pk.kode
    WHERE pk.deleted_at IS NULL
      AND $targetPeriodWhereSql";
$resGP = mysqli_query($conn, $sqlGrandPend);
$rowGP = $resGP ? mysqli_fetch_assoc($resGP) : null;
$grandTotalPendapatan = floatval($rowGP['total'] ?? 0);

echo json_encode([
    'tableData' => $tableData,
    'chartData' => $chartData,
    'grandTotalPendapatan' => $grandTotalPendapatan,
    'monthCount' => $monthCount,
    'dateStart' => $date,
    'dateEnd' => $dateEnd
]);
?>