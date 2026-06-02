<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_transaksi = $_POST['kodeTran'];
    $invoice = $_POST['invoice'];
    $tgl_inv = date("Y-m-d");


    $sql = "UPDATE kegiatan SET invoice = ?, tgl_inv = ? WHERE kode_transaksi = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $invoice, $tgl_inv, $kode_transaksi);
    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
    mysqli_stmt_close($stmt); // Tutup pernyataan
    mysqli_close($conn);
} else {
    echo "error";
}
?>
