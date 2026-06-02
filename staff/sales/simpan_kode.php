<?php
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = $_POST["nama_produk"];
    $jumlah = $_POST["jumlah"];
    $kode_jenis = $_POST["kode_jenis"];
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // Lakukan iterasi untuk menyimpan setiap random code ke dalam database
    for ($i = 0; $i < $jumlah; $i++) {
        $random_code = 'LWX-' . $kode_jenis . '-';
        for ($j = 0; $j < 5; $j++) {
            $random_code .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Simpan random code ke dalam database
        $query = "INSERT INTO garansi_berjalan (kode_garansi, tgl_cetak) VALUES ('$random_code', NOW())";
        mysqli_query($conn, $query);
    }
}
?>
