<?php
include "conn.php"; // Pastikan Anda telah menyertakan file koneksi database (conn.php) di sini

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap ID sales yang akan dihapus dari permintaan POST
    $nik = $_POST["nik"];

    // Lakukan query DELETE untuk menghapus data sales berdasarkan ID
    $queryHapusSales = "DELETE FROM loewix WHERE nik = '$nik'"; // Ganti 'nama_tabel_sales' dengan nama tabel yang sesuai

    if (mysqli_query($conn, $queryHapusSales)) {
        $query = "DELETE FROM teknisi WHERE nik = '$nik'";
        if (mysqli_query($conn, $query)) {
            echo "sukses";
        }
    } else {
        // Jika terjadi kesalahan
        echo "gagal";
    }
} else {
    // Jika bukan permintaan POST, tampilkan pesan kesalahan
    echo "Metode yang diperbolehkan hanya POST";
}
?>
