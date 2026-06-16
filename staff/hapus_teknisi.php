<?php
include "conn.php"; // Pastikan Anda telah menyertakan file koneksi database (conn.php) di sini

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap ID sales yang akan dihapus dari permintaan POST
    $nik = $_POST["nik"];
    $selTek = "SELECT * FROM teknisi WHERE nik = '$nik'";
    $resultTek = mysqli_query($conn, $selTek);
    $teknisi = mysqli_fetch_assoc($resultTek);
    $idTek = $teknisi['id'];

    // Lakukan query DELETE untuk menghapus data login teknisi berdasarkan teknisi_id
    $queryHapusSales = "DELETE FROM user_teknisi WHERE teknisi_id = '$idTek'";

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
