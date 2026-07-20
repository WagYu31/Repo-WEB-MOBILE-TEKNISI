<?php
include "staff/conn.php";

echo "<pre>";
echo "<h2>Batch Update Customer Phone Numbers (Latest Visit Date)</h2>";

// Fetch all completed sales visits with valid phone numbers, sorted by newest first
$q = mysqli_query($conn, "
    SELECT ks.id_customer, ps.nomer_client, ps.co_at 
    FROM pelaksanaan_sales ps
    JOIN kegiatan_sales ks ON ps.kegiatan_id = ks.id
    WHERE ps.nomer_client IS NOT NULL 
      AND ps.nomer_client != '' 
      AND ps.nomer_client != '0'
      AND ps.status = 'selesai'
    ORDER BY ps.co_at DESC
");

if (!$q) {
    echo "<b>Query Error:</b> " . mysqli_error($conn) . "\n";
    exit;
}

$updated = 0;
$processedCustomers = [];

while ($row = mysqli_fetch_assoc($q)) {
    $custId = $row['id_customer'];
    $phone = trim($row['nomer_client']);
    
    // Skip older visits since we sorted by newest (co_at DESC)
    if (in_array($custId, $processedCustomers)) {
        continue;
    }
    $processedCustomers[] = $custId;
    
    // Check current customer phone
    $check = mysqli_query($conn, "SELECT telp_pribadi, nama FROM sales_customer WHERE id = $custId");
    if ($check && $cust = mysqli_fetch_assoc($check)) {
        $currentPhone = trim($cust['telp_pribadi'] ?? '');
        
        // Always overwrite with the phone number from the latest visit if it is different
        if ($currentPhone !== $phone) {
            $safePhone = mysqli_real_escape_string($conn, $phone);
            $upd = mysqli_query($conn, "UPDATE sales_customer SET telp_pribadi = '$safePhone', updated_at = NOW() WHERE id = $custId");
            if ($upd) {
                echo "Updated customer: " . htmlspecialchars($cust['nama']) . " (ID: $custId) -> phone: $phone (from latest report date: {$row['co_at']})\n";
                $updated++;
            }
        } else {
            echo "Skipped customer: " . htmlspecialchars($cust['nama']) . " (ID: $custId) -> already has latest phone: $currentPhone\n";
        }
    }
}

echo "\n<b>Total customer phone numbers updated:</b> $updated\n";
echo "</pre>";
?>
