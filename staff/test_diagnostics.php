<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR CODES: Orn4Fg (SHIO PAN PIK 2) AND 6cEiKS (Melvin) ===\n\n";

$codes = ['Orn4Fg', '6cEiKS'];

foreach ($codes as $code) {
    echo "=========================================\n";
    echo "CODE: $code\n";
    echo "=========================================\n";

    // 1. Query kegiatan
    echo "--- KEGIATAN ROWS ---\n";
    $q1 = $conn->query("SELECT id, status, paid, deleted_at, customer_id, created_at, jadwal FROM kegiatan WHERE kode = '$code'");
    if ($q1) {
        while ($row = $q1->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }

    // 2. Query pelaksanaan_kegiatan
    echo "\n--- PELAKSANAAN KEGIATAN ROWS ---\n";
    $q2 = $conn->query("SELECT id, kegiatan_id, status, deleted_at, waktu_mulai, waktu_selesai, teknisi_id FROM pelaksanaan_kegiatan WHERE kode = '$code'");
    if ($q2) {
        while ($row = $q2->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }

    // 3. Test Query inside lap-kegiatan.php
    echo "\n--- TEST QUERY LAP-KEGIATAN.PHP (Is it selected?) ---\n";
    $sql = "SELECT k.id, k.kode, k.status, k.paid, k.deleted_at, c.nama AS nama_cust
            FROM kegiatan k
            INNER JOIN (SELECT kode, MAX(id) AS max_id FROM kegiatan WHERE deleted_at IS NULL GROUP BY kode) latest ON k.id = latest.max_id
            LEFT JOIN customer c ON k.customer_id = c.id
            WHERE k.kode = '$code'";
    $q3 = $conn->query($sql);
    if ($q3) {
        if ($q3->num_rows > 0) {
            while ($row = $q3->fetch_assoc()) {
                print_r($row);
                
                // Let's check the subquery manually for this k.id
                $k_id = $row['id'];
                $check_subquery = "SELECT px.id, px.status, px.deleted_at FROM pelaksanaan_kegiatan px
                                   WHERE px.kegiatan_id = '$k_id' AND px.deleted_at IS NULL
                                   AND px.status IN ('Lanjut Nanti', 'Lanjutan', 'berjalan', 'dijadwalkan')";
                $q_sub = $conn->query($check_subquery);
                echo "Subquery check for k.id = $k_id:\n";
                if ($q_sub->num_rows > 0) {
                    while ($sub_row = $q_sub->fetch_assoc()) {
                        echo "  [BLOCKED BY ACTIVE SESSION] -> ";
                        print_r($sub_row);
                    }
                } else {
                    echo "  [NOT BLOCKED BY ACTIVE SESSION]\n";
                }
            }
        } else {
            echo "No row returned by main query for this code.\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}
?>
