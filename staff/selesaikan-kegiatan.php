<?php
session_start();
include "conn.php";

// Periksa apakah 'kode' telah diterima melalui URL
if (isset($_GET['kode']) && isset($_GET['id'])) {
    $kodeTransaksi = $_GET['kode'];
    $idKegiatan = $_GET['id'];

    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $now = date('Y-m-d H:i:s'); // Menyimpan date time saat ini ke variabel $now
    $stss = "selesai";

    // Periksa apakah koneksi database berhasil
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Lakukan query untuk memperbarui kolom deleted_at berdasarkan kode
    $updateQuery = "UPDATE kegiatan
                    SET status = ?, updated_at = ?
                    WHERE kode = ? AND id = ?";
    if ($stmt = mysqli_prepare($conn, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, "sssi", $stss, $now, $kodeTransaksi, $idKegiatan);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                // Jika berhasil, alihkan ke halaman index-sa.php
                header("Location: index-sa.php");
                exit;
            } else {
                echo "Gagal menghapus kegiatan. Data mungkin sudah dihapus atau kode tidak ditemukan.";
            }
        } else {
            echo "Gagal mengeksekusi query: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Gagal menyiapkan query: " . mysqli_error($conn);
    }

    mysqli_close($conn);
} else {
    echo "Kode kegiatan tidak ditemukan.";
}
