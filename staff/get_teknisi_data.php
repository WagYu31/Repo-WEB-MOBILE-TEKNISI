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

// === Main query: teknisi + kegiatan count + pendapatan across range ===
$sql = "SELECT 
            t.id, t.nik, t.nama, t.telp, t.target,
            (SELECT COUNT(DISTINCT k.kode) FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id 
             WHERE tk.teknisi_id = t.id AND DATE_FORMAT(k.jadwal, '%Y-%m') IN ($monthPlaceholders) AND tk.deleted_at IS NULL) AS jumlah_kegiatan,
            (SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (
                SELECT COUNT(*) FROM pendapatan_kegiatan pk2 
                WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') IN ($monthPlaceholders) AND pk2.deleted_at IS NULL
            ))), 0) FROM pendapatan_kegiatan pk 
             WHERE pk.teknisi_id = t.id AND DATE_FORMAT(pk.tanggal, '%Y-%m') IN ($monthPlaceholders) AND pk.deleted_at IS NULL) AS total_pendapatan
        FROM teknisi t
        WHERE t.deleted_at IS NULL
        ORDER BY t.nama ASC";

$stmt = $conn->prepare($sql);
// Bind months 3 times (kegiatan, pendapatan subquery count, pendapatan main)
$allMonthParams = array_merge($months, $months, $months);
$allMonthTypes = str_repeat('s', count($allMonthParams));
$stmt->bind_param($allMonthTypes, ...$allMonthParams);
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

    if ($total_penghasilan > $target) {
        $bonus = ($total_penghasilan - $target) * 0.60;
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

// ═══ GRAND TOTAL PENDAPATAN: Match Detail Invoice exactly ═══
$grandMonthPlaceholders = implode(',', array_fill(0, $monthCount, '?'));
$sqlGrandPend = "SELECT COALESCE(SUM(sub.nominal), 0) as total FROM (
    SELECT nominal_invoice as nominal
    FROM pendapatan_kegiatan
    WHERE DATE_FORMAT(tanggal, '%Y-%m') IN ($grandMonthPlaceholders) AND deleted_at IS NULL
    GROUP BY kode
) sub";
$stmtGP = $conn->prepare($sqlGrandPend);
$gpTypes = str_repeat('s', $monthCount);
$stmtGP->bind_param($gpTypes, ...$months);
$stmtGP->execute();
$resGP = $stmtGP->get_result();
$rowGP = $resGP->fetch_assoc();
$grandTotalPendapatan = floatval($rowGP['total'] ?? 0);
$stmtGP->close();

echo json_encode([
    'tableData' => $tableData,
    'chartData' => $chartData,
    'grandTotalPendapatan' => $grandTotalPendapatan,
    'monthCount' => $monthCount,
    'dateStart' => $date,
    'dateEnd' => $dateEnd
]);
?>