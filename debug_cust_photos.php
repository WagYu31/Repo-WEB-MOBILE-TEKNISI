<?php
include "staff/sales/conn.php";
$res = mysqli_query($conn, "SELECT id, nama, foto FROM sales_customer WHERE nama LIKE '%Coba tambah%'");
echo "<pre>";
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
echo "</pre>";
