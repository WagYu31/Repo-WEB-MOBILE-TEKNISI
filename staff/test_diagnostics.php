<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSING KEGIATAN FOR CUSTOMER 66 ===\n\n";

// 1. Query all kegiatan for customer_id = 66
$q = $conn->query("SELECT id, customer_id, kode, kegiatan, jadwal, deleted_at FROM kegiatan WHERE customer_id = 66 ORDER BY jadwal DESC");
if ($q && $q->num_rows > 0) {
    echo "--- KEGIATAN FOR ALFIAN (customer_id = 66) ---\n";
    while ($row = $q->fetch_assoc()) {
        $id = $row['id'];
        $kode = $row['kode'];
        $jadwal = $row['jadwal'];
        
        // Find the global MAX(id) for this code where deleted_at IS NULL
        $sub = $conn->query("SELECT MAX(id) AS max_id FROM kegiatan WHERE kode = '$kode' AND deleted_at IS NULL");
        $sub_row = $sub ? $sub->fetch_assoc() : null;
        $max_id = $sub_row ? $sub_row['max_id'] : null;
        
        // Check if there is any row with that max_id to see its customer_id
        $cust_max = null;
        if ($max_id) {
            $c_q = $conn->query("SELECT customer_id, deleted_at FROM kegiatan WHERE id = $max_id");
            $c_row = $c_q ? $c_q->fetch_assoc() : null;
            $cust_max = $c_row ? $c_row['customer_id'] : null;
        }

        echo "ID: $id | Kode: $kode | Jadwal: $jadwal | Deleted: " . ($row['deleted_at'] ?? 'NULL') . "\n";
        echo "   -> Global MAX(id) for this code: " . ($max_id ?? 'None') . " (belongs to customer_id: " . ($cust_max ?? 'N/A') . ")\n";
        echo "   -> Will it be selected by customer-detail.php? " . ($id == $max_id ? 'YES' : 'NO') . "\n\n";
    }
} else {
    echo "No kegiatan found for customer 66.\n";
}
?>


