<?php
header('Content-Type: text/plain');
include 'conn.php';

echo "=== RESTORING ACTIVITY XoHGT8 (2026-03-26) ===\n\n";

// 1. Update kegiatan table
$update1 = $conn->query("UPDATE kegiatan SET deleted_at = NULL WHERE kode = 'XoHGT8' AND customer_id = 66");
if ($update1) {
    echo "Successfully restored kegiatan row for XoHGT8 (rows affected: " . $conn->affected_rows . ").\n";
} else {
    echo "Failed to restore kegiatan row: " . $conn->error . "\n";
}

// 2. Update pelaksanaan_kegiatan table
$update2 = $conn->query("UPDATE pelaksanaan_kegiatan SET deleted_at = NULL WHERE kode = 'XoHGT8'");
if ($update2) {
    echo "Successfully restored pelaksanaan_kegiatan rows for XoHGT8 (rows affected: " . $conn->affected_rows . ").\n";
} else {
    echo "Failed to restore pelaksanaan_kegiatan rows: " . $conn->error . "\n";
}

echo "\nDone. Please refresh the customer detail page to check.\n";
?>
