<?php
include "conn.php";
include "session.php";

date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

if (isset($_GET['id'])) {
    $kegiatan_id = intval($_GET['id']);
    
    $sql = "SELECT * FROM kegiatan_reasons WHERE kegiatan_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kegiatan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode($data);
}
?>