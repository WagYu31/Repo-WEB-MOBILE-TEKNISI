<?php
session_start();
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $nama = $_POST["nama"];
    $kode = $_POST["kode"];

    // Lakukan query untuk menghapus kegiatan berdasarkan id_kegiatan
    $deleteQuery = "DELETE FROM kegiatan WHERE id_kegiatan = ?";
    if ($stmt = mysqli_prepare($conn, $deleteQuery)) {
        mysqli_stmt_bind_param($stmt, "i", $kegiatanId);
        if (mysqli_stmt_execute($stmt)) {
            
            $tgl_now = date("Y-m-d H:i:s");
            $hist = "Menghapus kegiatan $kode";
            $tipe = "Hapus";
            $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";

            if ($stmtHistory = mysqli_prepare($conn, $history)) {
                mysqli_stmt_bind_param($stmtHistory, "ssss", $nama, $hist, $tipe, $tgl_now);
                if (mysqli_stmt_execute($stmtHistory)) {
                    // Eksekusi query berhasil
                } else {
                    // Terjadi kesalahan saat eksekusi query
                    echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmtHistory);
            }

            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }

    mysqli_close($conn);
}
?>
