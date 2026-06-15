<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSING MISSING CODES ===\n\n";

$codes = ['WUjWBK', 'tmR9ij', 'yStxTe', 'sDLPgA', 'NlsMgf', '93YaSX'];
foreach ($codes as $code) {
    echo "--- Code: $code ---\n";
    $q = $conn->query("SELECT id, customer_id, kode, kegiatan, jadwal, deleted_at FROM kegiatan WHERE kode = '$code'");
    if ($q && $q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "No match\n";
    }
}
?>

