<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS FOR CUSTOMER: Alfian ===\n\n";

// 1. Find the customer
$q_cust = $conn->query("SELECT id, nama, telp FROM customer WHERE nama LIKE '%Alfian%'");
if ($q_cust && $q_cust->num_rows > 0) {
    while ($cust = $q_cust->fetch_assoc()) {
        echo "Customer Found:\n";
        print_r($cust);
        $cust_id = $cust['id'];
        
        // 2. Query all kegiatan for this customer
        echo "\n--- ALL KEGIATAN ROWS (including deleted) ---\n";
        $q_keg = $conn->query("SELECT id, kode, kegiatan, status, paid, invoice, deleted_at, created_at, customer_id, jadwal FROM kegiatan WHERE customer_id = $cust_id ORDER BY id ASC");
        if ($q_keg) {
            while ($keg = $q_keg->fetch_assoc()) {
                print_r($keg);
                
                // For each kegiatan, check if there are pelaksanaan_kegiatan
                $kode = $keg['kode'];
                $q_pel = $conn->query("SELECT id, kegiatan_id, kode, status, deleted_at, waktu_mulai, waktu_selesai, teknisi_id FROM pelaksanaan_kegiatan WHERE kode = '$kode'");
                if ($q_pel && $q_pel->num_rows > 0) {
                    echo "  -> Pelaksanaan Kegiatan for kode '$kode':\n";
                    while ($pel = $q_pel->fetch_assoc()) {
                        echo "     ";
                        print_r($pel);
                    }
                } else {
                    echo "  -> No Pelaksanaan Kegiatan for kode '$kode'\n";
                }
            }
        }
        
        // 3. Test the exact query from customer-detail.php
        echo "\n--- EXACT QUERY FROM customer-detail.php ---\n";
        $sql = "SELECT
                    k.id AS kegiatan_id, k.kode AS kegiatan_kode, k.kegiatan AS jenis_kegiatan, 
                    k.jadwal AS jadwal_kegiatan, k.keterangan AS keterangan_kegiatan, k.lunas,
                    c.nama AS customer_name,
                    p.teknisi_id, p.status, p.waktu_mulai, p.waktu_selesai,
                    t.nama AS teknisi_name
                FROM kegiatan k
                INNER JOIN (SELECT kode, MAX(id) AS max_id FROM kegiatan WHERE deleted_at IS NULL GROUP BY kode) latest ON k.id = latest.max_id
                LEFT JOIN customer c ON k.customer_id = c.id
                LEFT JOIN (
                    SELECT p1.* FROM pelaksanaan_kegiatan p1
                    INNER JOIN (
                        SELECT kode, teknisi_id, waktu_mulai, MAX(id) AS max_id
                        FROM pelaksanaan_kegiatan
                        WHERE deleted_at IS NULL
                        GROUP BY kode, teknisi_id, waktu_mulai
                    ) p2 ON p1.id = p2.max_id
                ) p ON k.kode = p.kode
                LEFT JOIN teknisi t ON p.teknisi_id = t.id
                WHERE k.customer_id = ? AND k.deleted_at IS NULL
                ORDER BY k.jadwal DESC, p.waktu_mulai ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo "Exact query returned " . $res->num_rows . " rows:\n";
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
        $stmt->close();
    }
} else {
    echo "No customer found with name matching 'Alfian'\n";
}
?>
