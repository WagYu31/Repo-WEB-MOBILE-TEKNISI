<?php
include "conn.php";
include "session.php";

if (!isset($_GET['kode_transaksi']) || empty($_GET['kode_transaksi'])) {
    header("Location: lap-noinv.php?error=3");
    exit();
}

$kode_kegiatan = $_GET['kode_transaksi'];
$paid_value = null;

$query_update_kegiatan = "UPDATE kegiatan SET invoice = 'no', paid = ? WHERE kode = ?";
$stmt_update = $conn->prepare($query_update_kegiatan);

if ($stmt_update) {
    $stmt_update->bind_param("ss", $paid_value, $kode_kegiatan);
    
    if ($stmt_update->execute()) {
        header("Location: lap-noinv.php?success=na_updated");
        exit();
    } else {
        header("Location: lap-noinv.php?error=1");
        exit();
    }
    $stmt_update->close();
} else {
    header("Location: lap-noinv.php?error=1");
    exit();
}

$conn->close();
?>