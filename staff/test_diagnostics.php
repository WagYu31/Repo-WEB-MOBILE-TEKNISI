<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR TECHNICIAN: Agung Putra ===\n\n";

$search_name = "Agung";
$sql = "SELECT id, nik, nama, telp, status, deleted_at, created_at FROM teknisi WHERE nama LIKE '%" . $conn->real_escape_string($search_name) . "%'";
$q = $conn->query($sql);

if ($q) {
    echo "Jumlah data teknisi yang cocok dengan '$search_name': " . $q->num_rows . "\n\n";
    while ($row = $q->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "NIK: " . $row['nik'] . "\n";
        echo "Nama: " . $row['nama'] . "\n";
        echo "Telp: " . $row['telp'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Deleted At: " . ($row['deleted_at'] ? $row['deleted_at'] : "NULL (Aktif)") . "\n";
        echo "Created At: " . $row['created_at'] . "\n";
        echo "---------------------------------\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
