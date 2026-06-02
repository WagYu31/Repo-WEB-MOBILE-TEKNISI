<?php
// Include file koneksi ke database (conn.php)
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mendapatkan ID kegiatan dari data yang dikirim melalui AJAX
    $id_kegiatan = $_POST["id_kegiatan"];

    // Mendapatkan tanggal esok hari
    $tanggal_esok = date("Y-m-d", strtotime("+1 day"));

    // Query untuk mengupdate data kegiatan dengan status "Besok" dan tanggal esok hari
    $query = "UPDATE kegiatan SET status = 'Besok', tgl_selesai = '$tanggal_esok' WHERE id_kegiatan = $id_kegiatan";

    if (mysqli_query($conn, $query)) {
        // Jika query berhasil dijalankan, kirim respons "success" ke AJAX
        echo "success";
    } else {
        // Jika terjadi kesalahan, kirim respons "error" ke AJAX
        echo "error";
    }
} else {
    // Jika request bukan POST, kirim respons "error"
    echo "error";
}

// Tutup koneksi database
mysqli_close($conn);
?>
