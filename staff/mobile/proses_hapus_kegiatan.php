<?php
session_start();
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $kodeTransaksi = $_POST["kode"];
    $status = "waiting";
    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $now = date('Y-m-d H:i:s'); // Menyimpan date time saat ini ke variabel $now

    // Periksa apakah koneksi database berhasil
    if (!$conn) {
        echo "Database connection failed: " . mysqli_connect_error();
        exit;
    }

    // Lakukan query untuk memperbarui kegiatan berdasarkan kode
    $updateQuery = "UPDATE kegiatan SET deleted_at = ? WHERE id = ? AND status = ?";
    if ($stmt = mysqli_prepare($conn, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, "sss", $now, $kegiatanId, $status);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo "success";
            } else {
                echo "error";
            }
        } else {
            echo "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "error";
    }

    mysqli_close($conn);
} else {
    echo "error";
}
?>
