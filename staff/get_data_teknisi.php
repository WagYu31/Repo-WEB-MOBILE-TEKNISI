<?php

// Query untuk mengambil data id_teknisi dari tabel kegiatan
$selectIdTeknisi = "SELECT * FROM team_kegiatan
                    WHERE kode = '$kode_transaksi'";
$resIdTeknisi = mysqli_query($conn, $selectIdTeknisi);

// Inisialisasi array untuk menyimpan id_teknisi yang terkait
$idTeknisiKegiatan = array();

if ($resIdTeknisi && mysqli_num_rows($resIdTeknisi) > 0) {
    while ($rowIdTeknisi = mysqli_fetch_assoc($resIdTeknisi)) {
        $idTeknisiKegiatan[] = $rowIdTeknisi["teknisi_id"];
        // Menggabungkan id_teknisi menjadi satu string yang dipisahkan koma
    }
}

?>