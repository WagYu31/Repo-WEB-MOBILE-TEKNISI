<?php
include 'conn.php';

$kode = 'PI3dyk';

echo "=== PELAKSANAAN KEGIATAN FOR KODE: $kode ===\n\n";

$sql = "SELECT id, teknisi_id, status, waktu_mulai, waktu_selesai, created_at 
        FROM pelaksanaan_kegiatan 
        WHERE kode = ? 
        ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $kode);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    // Get teknisi name
    $t_res = $conn->query("SELECT nama FROM teknisi WHERE id = " . intval($row['teknisi_id']));
    $t_row = $t_res->fetch_assoc();
    $t_name = $t_row['nama'] ?? 'Unknown';
    
    echo "ID: " . $row['id'] . "\n";
    echo "Teknisi: " . $t_name . " (ID: " . $row['teknisi_id'] . ")\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Mulai: " . $row['waktu_mulai'] . "\n";
    echo "Selesai: " . $row['waktu_selesai'] . "\n";
    echo "Created At: " . $row['created_at'] . "\n";
    echo "-------------------------------------\n";
}
?>
