<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR TECHNICIAN: Agung Putra ===\n\n";

$search_name = "Agung";
$sql = "SELECT * FROM teknisi WHERE nama LIKE '%" . $conn->real_escape_string($search_name) . "%'";
$q = $conn->query($sql);

if ($q) {
    echo "Jumlah data teknisi yang cocok dengan '$search_name': " . $q->num_rows . "\n\n";
    while ($row = $q->fetch_assoc()) {
        echo "Row data:\n";
        print_r($row);
        echo "---------------------------------\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
