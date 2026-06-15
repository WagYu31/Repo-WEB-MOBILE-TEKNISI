<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSING XoHGT8 FOR LAP-KEGIATAN ===\n\n";

$q = $conn->query("SELECT * FROM kegiatan WHERE kode = 'XoHGT8'");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        echo "--- KEGIATAN ROW ---\n";
        print_r($row);
        
        $keg_id = $row['id'];
        
        // Check execution status
        echo "\n--- PELAKSANAAN ROWS ---\n";
        $pe = $conn->query("SELECT * FROM pelaksanaan_kegiatan WHERE kegiatan_id = $keg_id");
        while ($p_row = $pe->fetch_assoc()) {
            print_r($p_row);
        }
        
        // Check latest subquery match
        echo "\n--- SUBQUERY CHECK ---\n";
        $latest = $conn->query("SELECT customer_id, kode, MAX(id) AS max_id FROM kegiatan WHERE deleted_at IS NULL AND kode = 'XoHGT8' GROUP BY customer_id, kode");
        if ($latest) {
            print_r($latest->fetch_assoc());
        }
    }
}
?>
