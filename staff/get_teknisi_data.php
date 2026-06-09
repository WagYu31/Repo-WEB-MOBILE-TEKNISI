<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

$date = $_GET['date'] ?? date('Y-m');
$monthStart = $date . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// === Main query: teknisi + kegiatan count + pendapatan (same as dashboard) ===
$sql = "SELECT 
            t.id, t.nik, t.nama, t.telp, t.target,
            (SELECT COUNT(DISTINCT k.kode) FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = t.id AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL) AS jumlah_kegiatan,
            (SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL))), 0) FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = t.id AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL) AS total_pendapatan
        FROM teknisi t
        WHERE t.deleted_at IS NULL
        ORDER BY t.nama ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $date, $date, $date);
$stmt->execute();
$result = $stmt->get_result();
$technicians = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Fee 30k calculation (SAME as dashboard laporan-db.php) ===
$feeKodes = [];
$sql = "SELECT k.kode FROM kegiatan k 
        WHERE k.created_at >= '$monthStart' AND k.created_at < DATE_ADD('$monthEnd', INTERVAL 1 DAY)
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
    $target = floatval($row['target'] ?? 0);
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

echo json_encode(['tableData' => $tableData, 'chartData' => $chartData]);
?>