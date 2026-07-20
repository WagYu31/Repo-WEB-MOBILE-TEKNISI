<?php
include "../conn.php";
echo "<pre>";
echo "<h2>Database Diagnostics</h2>";

$q1 = mysqli_query($conn, "SELECT id, nama, telp_pribadi FROM sales_customer ORDER BY id DESC LIMIT 5");
echo "<h3>Sales Customer (Latest 5):</h3>";
while($r = mysqli_fetch_assoc($q1)) {
    print_r($r);
}

$q2 = mysqli_query($conn, "SELECT id, kegiatan_id, status, nomer_client, co_at FROM pelaksanaan_sales ORDER BY id DESC LIMIT 5");
echo "<h3>Pelaksanaan Sales (Latest 5):</h3>";
while($r = mysqli_fetch_assoc($q2)) {
    print_r($r);
}

$q3 = mysqli_query($conn, "SELECT id, id_customer, status FROM kegiatan_sales ORDER BY id DESC LIMIT 5");
echo "<h3>Kegiatan Sales (Latest 5):</h3>";
while($r = mysqli_fetch_assoc($q3)) {
    print_r($r);
}
echo "</pre>";
?>
