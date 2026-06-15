<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS: Agung Putra (ID: 40) Relations ===\n\n";

$teknisi_id = 40;

// 1. Check team_kegiatan
$q1 = $conn->query("SELECT COUNT(*) AS total FROM team_kegiatan WHERE teknisi_id = $teknisi_id");
$total_team = $q1 ? $q1->fetch_assoc()['total'] : 0;
echo "1. Data di team_kegiatan: $total_team baris\n";

// 2. Check pelaksanaan_kegiatan
$q2 = $conn->query("SELECT COUNT(*) AS total FROM pelaksanaan_kegiatan WHERE teknisi_id = $teknisi_id");
$total_pelaksanaan = $q2 ? $q2->fetch_assoc()['total'] : 0;
echo "2. Data di pelaksanaan_kegiatan: $total_pelaksanaan baris\n";

// 3. Check pendapatan_kegiatan
$q3 = $conn->query("SELECT COUNT(*) AS total FROM pendapatan_kegiatan WHERE teknisi_id = $teknisi_id");
$total_pendapatan = $q3 ? $q3->fetch_assoc()['total'] : 0;
echo "3. Data di pendapatan_kegiatan: $total_pendapatan baris\n";

// 4. Check if there are active / incomplete activities for this technician
echo "\n--- KEGIATAN AKTIF / BELUM SELESAI YANG MELIBATKAN AGUNG PUTRA ---\n";

$sql_active = "SELECT k.id, k.kode, k.kegiatan, k.jadwal, k.status, k.paid, c.nama AS nama_cust
               FROM kegiatan k
               LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
               LEFT JOIN customer c ON k.customer_id = c.id
               WHERE t.teknisi_id = $teknisi_id 
                 AND k.status NOT IN ('selesai', 'selesai by admin', 'Clear')
                 AND k.deleted_at IS NULL";

$q_active = $conn->query($sql_active);
if ($q_active) {
    echo "Jumlah kegiatan aktif: " . $q_active->num_rows . "\n\n";
    while ($row = $q_active->fetch_assoc()) {
        print_r($row);
        echo "---------------------------------\n";
    }
} else {
    echo "Error active check: " . $conn->error . "\n";
}

// 5. Check all activities in general that involve Agung Putra
echo "\n--- 5 KEGIATAN TERBARU YANG MELIBATKAN AGUNG PUTRA ---\n";
$sql_all = "SELECT k.id, k.kode, k.kegiatan, k.jadwal, k.status, c.nama AS nama_cust
            FROM kegiatan k
            LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
            LEFT JOIN customer c ON k.customer_id = c.id
            WHERE t.teknisi_id = $teknisi_id
              AND k.deleted_at IS NULL
            ORDER BY k.jadwal DESC LIMIT 5";
$q_all = $conn->query($sql_all);
if ($q_all) {
    while ($row = $q_all->fetch_assoc()) {
        print_r($row);
        echo "---------------------------------\n";
    }
}
?>
