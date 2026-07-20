<?php
include "staff/conn.php";

echo "<pre>";
echo "<h2>Batch Update Customer Phone Numbers</h2>";

$sql = "UPDATE sales_customer sc
JOIN (
    SELECT ks.id_customer, ps.nomer_client
    FROM pelaksanaan_sales ps
    JOIN kegiatan_sales ks ON ps.kegiatan_id = ks.id
    WHERE ps.status = 'selesai' 
      AND ps.nomer_client IS NOT NULL 
      AND ps.nomer_client != '' 
      AND ps.nomer_client != '0'
      AND ps.co_at = (
          SELECT MAX(ps2.co_at)
          FROM pelaksanaan_sales ps2
          JOIN kegiatan_sales ks2 ON ps2.kegiatan_id = ks2.id
          WHERE ks2.id_customer = ks.id_customer 
            AND ps2.status = 'selesai' 
            AND ps2.nomer_client IS NOT NULL 
            AND ps2.nomer_client != '' 
            AND ps2.nomer_client != '0'
      )
) latest_visits ON sc.id = latest_visits.id_customer
SET sc.telp_pribadi = latest_visits.nomer_client
WHERE sc.telp_pribadi IS NULL OR sc.telp_pribadi = '' OR sc.telp_pribadi = '0'";

if (mysqli_query($conn, $sql)) {
    echo "<b>Success:</b> " . mysqli_affected_rows($conn) . " customer phone numbers updated from visit reports.\n";
} else {
    echo "<b>Error:</b> " . mysqli_error($conn) . "\n";
}

echo "</pre>";
?>
