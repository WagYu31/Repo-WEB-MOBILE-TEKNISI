<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_teknisi = $_POST['tekId'];
    $kode_transaksi = $_POST['kodeTran'];
    $nominal_denda = $_POST['denda'];

    $sql = "UPDATE kegiatan SET denda = ? WHERE id_teknisi = ? AND kode_transaksi = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "dss", $nominal_denda, $id_teknisi, $kode_transaksi);
    if (mysqli_stmt_execute($stmt)) {
        echo "success";
    } else {
        echo "error";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "error";
}
?>
