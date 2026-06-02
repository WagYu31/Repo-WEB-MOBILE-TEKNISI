<?php
include "../../conn.php";
    $nik = $_GET['nik'];

    $queryUpdate = "UPDATE data_warga SET past_member = 'Y' WHERE nik = ?";
    $stmtUpdate = mysqli_prepare($conn, $queryUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "s", $nik);

    if (mysqli_stmt_execute($stmtUpdate)) {
        // Redirect ke halaman verifikasi_pembayaran.php
        header("Location: tables.php");
        exit();
    } else {
        // Handle error jika query tidak berhasil
        echo "Error: " . mysqli_error($conn);
        exit();
    }
?>
