<?php

// Query untuk mengambil data id_teknisi dari tabel kegiatan
$selectIdTeknisi = "SELECT v.id_sales, s.id_sales, s.nama AS nama_sales FROM visits v
                    LEFT JOIN sales s ON v.id_sales = s.id_sales
                    WHERE v.kode_transaksi = '$kode_transaksi'";
$resIdTeknisi = mysqli_query($conn, $selectIdTeknisi);

// Inisialisasi array untuk menyimpan id_teknisi yang terkait
$idTeknisiKegiatan = array();

if ($resIdTeknisi && mysqli_num_rows($resIdTeknisi) > 0) {
    while ($rowIdTeknisi = mysqli_fetch_assoc($resIdTeknisi)) {
        $idTeknisiKegiatan[] = $rowIdTeknisi["id_sales"];
        // Menggabungkan id_teknisi menjadi satu string yang dipisahkan koma
    }
}

?>