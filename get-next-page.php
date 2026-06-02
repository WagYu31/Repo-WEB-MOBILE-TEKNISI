<?php

include "conn.php";
$dataPerPage = 10;
$itemsPerPage = 20;

// Hitung jumlah total data
$totalData = mysqli_num_rows($result);

// Tentukan jumlah halaman
$totalPages = ceil($totalData / $itemsPerPage);

// Dapatkan halaman yang sedang ditampilkan
if (isset($_GET['page'])) {
    $currentPage = $_GET['page'];
} else {
    $currentPage = 1;
}

$no = ($currentPage - 1) * $itemsPerPage + 1;


// Hitung batas awal dan akhir data untuk halaman saat ini
$startData = ($currentPage - 1) * $itemsPerPage;

// Query untuk menampilkan data dengan batasan sesuai halaman yang ditampilkan
$sql .= " LIMIT $startData, $itemsPerPage";
$result = mysqli_query($conn, $sql);

    
?>