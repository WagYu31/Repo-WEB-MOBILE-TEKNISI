<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSING SOFT-DELETED CODES ===\n\n";

$codes = ['XoHGT8', 'QD8aE1'];
foreach ($codes as $code) {
    echo "--- Code: $code ---\n";
    echo "Kegiatan:\n";
    $q1 = $conn->query("SELECT id, customer_id, kode, kegiatan, jadwal, deleted_at FROM kegiatan WHERE kode = '$code'");
    if ($q1) {
        while ($row = $q1->fetch_assoc()) {
            print_r($row);
        }
    }
    
    echo "Pelaksanaan:\n";
    $q2 = $conn->query("SELECT id, kegiatan_id, status, waktu_mulai, waktu_selesai, teknisi_id, deleted_at FROM pelaksanaan_kegiatan WHERE kode = '$code'");
    if ($q2) {
        while ($row = $q2->fetch_assoc()) {
            print_r($row);
        }
    }
    
    echo "Team:\n";
    $q3 = $conn->query("SELECT * FROM team_kegiatan WHERE kegiatan_id IN (SELECT id FROM kegiatan WHERE kode = '$code')");
    if ($q3) {
        while ($row = $q3->fetch_assoc()) {
            print_r($row);
        }
    }
    echo "\n";
}
?>


