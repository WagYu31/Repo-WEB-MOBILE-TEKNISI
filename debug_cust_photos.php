<?php
include "staff/sales/conn.php";
$res = mysqli_query($conn, "SELECT id, nama, telp FROM customer WHERE id = 13");
echo "<h3>customer:</h3><pre>";
print_r(mysqli_fetch_assoc($res));
echo "</pre>";
