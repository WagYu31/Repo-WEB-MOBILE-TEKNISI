<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== DIAGNOSTICS & RESTORE FOR CUSTOMER: IBU OKTA ===\n\n";

// 1. Restore the kegiatan records for code 'fo1DWX'
echo "--- RUNNING RESTORE PATCH ---\n";
$restore = $conn->query("UPDATE kegiatan SET deleted_at = NULL WHERE kode = 'fo1DWX'");
if ($restore) {
    echo "Restore kegiatan fo1DWX: SUCCESS (deleted_at set to NULL)\n";
} else {
    echo "Restore kegiatan fo1DWX: FAILED (" . $conn->error . ")\n";
}
echo "\n";

// 2. Fetch the updated data to verify
$q_cust = $conn->query("SELECT id, nama, telp FROM customer WHERE id = 554");
if ($q_cust && $q_cust->num_rows > 0) {
    $cust = $q_cust->fetch_assoc();
    echo "Customer: " . $cust['nama'] . " (ID: " . $cust['id'] . ")\n\n";
    
    echo "--- KEGIATAN ROWS AFTER RESTORE ---\n";
    $q_keg = $conn->query("SELECT id, kode, kegiatan, status, deleted_at, created_at, customer_id, jadwal FROM kegiatan WHERE customer_id = 554 ORDER BY id ASC");
    if ($q_keg) {
        while ($keg = $q_keg->fetch_assoc()) {
            print_r($keg);
        }
    }
}
?>
