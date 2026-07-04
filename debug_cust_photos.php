<?php
include "staff/sales/conn.php";

// Ambil sales_id dari team_kegiatan_sales untuk kegiatan_id 31
$resSales = mysqli_query($conn, "SELECT id_sales FROM team_kegiatan_sales WHERE id_kegiatan_sales = 31");
$salesRow = mysqli_fetch_assoc($resSales);
$salesId = $salesRow['id_sales'] ?? 0;

echo "<h3>Sales ID untuk kegiatan 31: $salesId</h3>";

// Hitung respon dari api_sales_task.php di folder teknisi-github.id-giti.com
$_GET['sales_id'] = $salesId;
$_GET['filter'] = 'today';

echo "<h3>Isi Respon API (api_sales_task.php):</h3>";
ob_start();
include "teknisi-github.id-giti.com/api_sales_task.php";
$apiResponse = ob_get_clean();

echo "<pre>";
print_r(json_decode($apiResponse, true));
echo "</pre>";
