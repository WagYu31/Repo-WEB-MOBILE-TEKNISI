<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

$date = $_GET['date'] ?? date('Y-m');
$month = date('m', strtotime($date));
$year = date('Y', strtotime($date));

$sql = "SELECT t.nama AS teknisi, pf.target, pf.pendapatan, pf.bonus
        FROM pendapatan_fix pf
        JOIN teknisi t ON pf.teknisi_id = t.id
        WHERE MONTH(pf.tanggal) = ? AND YEAR(pf.tanggal) = ? AND pf.deleted_at IS NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$targets = [];
$pendapatan = [];
$bonus = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['teknisi'];
    $targets[] = (float) $row['target'];
    $pendapatan[] = (float) $row['pendapatan'];
    $bonus[] = (float) $row['bonus'];
}

echo json_encode([
    'labels' => $labels,
    'targets' => $targets,
    'pendapatan' => $pendapatan,
    'bonus' => $bonus
]);

$stmt->close();
?>
