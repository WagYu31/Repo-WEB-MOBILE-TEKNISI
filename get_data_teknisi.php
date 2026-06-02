<?php

// Query untuk mengambil data id_teknisi dari tabel kegiatan
$selectIdTeknisi = "SELECT k.id_teknisi, t.id_teknisi, t.nama AS nama_teknisi FROM kegiatan k
                    LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                    WHERE k.kode_transaksi = '$kode_transaksi'";
$resIdTeknisi = mysqli_query($conn, $selectIdTeknisi);

// Inisialisasi array untuk menyimpan id_teknisi yang terkait
$idTeknisiKegiatan = array();

if ($resIdTeknisi && mysqli_num_rows($resIdTeknisi) > 0) {
    while ($rowIdTeknisi = mysqli_fetch_assoc($resIdTeknisi)) {
        $idTeknisiKegiatan[] = $rowIdTeknisi["id_teknisi"];
        // Menggabungkan id_teknisi menjadi satu string yang dipisahkan koma
    }
}

?>