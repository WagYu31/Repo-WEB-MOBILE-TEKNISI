<?php
include "staff/sales/conn.php";
$res1 = mysqli_query($conn, "SELECT id, nama, telp_pribadi, foto FROM sales_customer WHERE id = 13");
echo "<h3>sales_customer:</h3><pre>";
print_r(mysqli_fetch_assoc($res1));
echo "</pre>";

$res2 = mysqli_query($conn, "SELECT id, nama, telp, foto FROM customer WHERE id = 13");
echo "<h3>customer:</h3><pre>";
print_r(mysqli_fetch_assoc($res2));
echo "</pre>";
