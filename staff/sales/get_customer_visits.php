<?php
include "conn.php";
include "session.php";

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([]);
    exit();
}

$customerId = intval($_GET['id']);

$sql = "SELECT s.id AS sales_id, s.nama, s.foto, MAX(ps.co_at) AS terakhir_kunjung, COUNT(ps.id) AS total_kunjungan
        FROM team_kegiatan_sales tks
        INNER JOIN pelaksanaan_sales ps ON tks.id_kegiatan_sales = ps.kegiatan_id AND tks.id_sales = ps.sales_id
        INNER JOIN kegiatan_sales ks ON tks.id_kegiatan_sales = ks.id
        INNER JOIN sales s ON tks.id_sales = s.id
        WHERE ks.id_customer = ? AND ps.status = 'selesai' AND tks.deleted_at IS NULL AND ks.deleted_at IS NULL
        GROUP BY s.id
        ORDER BY terakhir_kunjung DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$visits = [];
while ($row = $result->fetch_assoc()) {
    $formattedDate = '-';
    if (!empty($row['terakhir_kunjung'])) {
        $formattedDate = date('d M Y, H:i', strtotime($row['terakhir_kunjung'])) . ' WIB';
    }
    
    $visits[] = [
        'sales_id' => (int)$row['sales_id'],
        'nama' => $row['nama'],
        'foto' => $row['foto'],
        'terakhir_kunjung' => $formattedDate,
        'total_kunjungan' => (int)$row['total_kunjungan']
    ];
}
$stmt->close();

echo json_encode($visits);
?>
