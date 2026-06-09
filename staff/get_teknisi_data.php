<?php
header('Content-Type: application/json');
include 'conn.php';
include 'session.php';

$date = $_GET['date'] ?? date('Y-m');

$sql = "SELECT 
            t.id, t.nik, t.nama, t.telp, t.target,
            (SELECT COUNT(DISTINCT k.kode) FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE tk.teknisi_id = t.id AND DATE_FORMAT(k.jadwal, '%Y-%m') = ? AND tk.deleted_at IS NULL) AS jumlah_kegiatan,
            (SELECT COALESCE(SUM(ROUND(pk.nominal_invoice / (SELECT COUNT(*) FROM pendapatan_kegiatan pk2 WHERE pk2.kode = pk.kode AND DATE_FORMAT(pk2.tanggal, '%Y-%m') = ? AND pk2.deleted_at IS NULL))), 0) FROM pendapatan_kegiatan pk WHERE pk.teknisi_id = t.id AND DATE_FORMAT(pk.tanggal, '%Y-%m') = ? AND pk.deleted_at IS NULL) AS total_pendapatan,
            (SELECT COALESCE(SUM(k.paid), 0) 
             FROM team_kegiatan tk 
             JOIN (
                 SELECT 
                     kode,
                     CASE 
                         WHEN jadwal < '2025-08-01' THEN 0 
                         ELSE paid 
                     END AS paid
                 FROM kegiatan
                 WHERE DATE_FORMAT(jadwal, '%Y-%m') = ? AND paid REGEXP '^[0-9]+$'
                 GROUP BY kode, paid, jadwal
             ) AS k ON tk.kode = k.kode 
             WHERE tk.teknisi_id = t.id AND tk.deleted_at IS NULL
            ) AS total_fee
        FROM teknisi t
        WHERE t.deleted_at IS NULL
        ORDER BY t.nama ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $date, $date, $date, $date);
$stmt->execute();
$result = $stmt->get_result();
$technicians = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$tableData = [];
$chartData = ['labels' => [], 'targets' => [], 'pendapatan' => [], 'bonus' => []];

foreach ($technicians as $row) {
    $pendapatan = floatval($row['total_pendapatan'] ?? 0);
    $fee = floatval($row['total_fee'] ?? 0);
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