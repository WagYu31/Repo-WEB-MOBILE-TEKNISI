<?php
include "staff/conn.php";

echo "<pre>";
echo "<h2>Batch Update Customer Phone Numbers (Robust)</h2>";

$q = mysqli_query($conn, "
    SELECT ks.id_customer, ps.nomer_client, ps.co_at 
    FROM pelaksanaan_sales ps
    JOIN kegiatan_sales ks ON ps.kegiatan_id = ks.id
    WHERE ps.nomer_client IS NOT NULL 
      AND ps.nomer_client != '' 
      AND ps.nomer_client != '0'
    ORDER BY ps.co_at ASC
");

if (!$q) {
    echo "<b>Query Error:</b> " . mysqli_error($conn) . "\n";
    exit;
}

$updated = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $custId = $row['id_customer'];
    $phone = trim($row['nomer_client']);
    
    // Check current telp_pribadi
    $check = mysqli_query($conn, "SELECT telp_pribadi, nama FROM sales_customer WHERE id = $custId");
    if ($check && $cust = mysqli_fetch_assoc($check)) {
        $currentPhone = trim($cust['telp_pribadi']);
        // If current phone is empty, '0', or shorter than 6 characters (e.g. '62'), update it!
        if (empty($currentPhone) || $currentPhone === '0' || $currentPhone === '' || strlen($currentPhone) < 6) {
            $safePhone = mysqli_real_escape_string($conn, $phone);
            $upd = mysqli_query($conn, "UPDATE sales_customer SET telp_pribadi = '$safePhone', updated_at = NOW() WHERE id = $custId");
            if ($upd) {
                echo "Updated customer: " . htmlspecialchars($cust['nama']) . " (ID: $custId) -> phone: $phone\n";
                $updated++;
            }
        } else {
            echo "Skipped customer: " . htmlspecialchars($cust['nama']) . " (ID: $custId) -> already has phone: $currentPhone\n";
        }
    }
}

echo "\n<b>Total customer phone numbers updated:</b> $updated\n";
echo "</pre>";
?>
