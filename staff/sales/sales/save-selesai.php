<?php

include "../conn.php";

// Periksa apakah data yang diperlukan telah diterima
if (isset($_GET['latitude'], $_GET['longitude'], $_GET['id_visits'], $_GET['file1'], $_GET['file2'], $_GET['file3'], $_GET['file4'], $_GET['file5'], $_GET['keterangan'], $_GET['hasil'])) {
    // Ambil data dari $_GET
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $id_visits = $_GET['id_visits'];
    $file1 = $_GET['file1'];
    $file2 = $_GET['file2'];
    $file3 = $_GET['file3'];
    $file4 = $_GET['file4'];
    $file5 = $_GET['file5'];
    $keterangan = $_GET['keterangan'];
    $hasil = $_GET['hasil'];
    $lokasiSelesai = $latitude . "," . $longitude;
    $currDT = date("Y-m-d H:i:s");

    echo $latitude . "<br>";
    echo $longitude . "<br>";
    echo $id_visits . "<br>";
    echo $currDT . "<br>";
    echo $file1 . "<br>";
    echo $file2 . "<br>";
    echo $file3 . "<br>";
    echo $file4 . "<br>";
    echo $file5 . "<br>";

    $sql = "SELECT * FROM visits WHERE id_visits = $id_visits";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $kodeTransaksi = $row["kode_transaksi"];
    $id_sales = $row["id_sales"];
    $idCust = $row["id_cust"];
    $tglVisits = $row["tgl_visits"];

    $status = "clear";
    echo $status . "<br>";

    echo $id_sales . "<br>";

    
    // Perbarui data kegiatan
    $sql = "UPDATE visits SET tgl_selesai = '$currDT', lokasi_selesai = '$lokasiSelesai', gambar_1 = '$file1', gambar_2 = '$file2', gambar_3 = '$file3', gambar_4 = '$file4', gambar_5 = '$file5', status = '$status', hasil_visits = '$hasil', keterangan_tambahan = '$keterangan' WHERE id_visits = '$id_visits'";
    if (mysqli_query($conn, $sql)) {
        header("location: index.php");
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    // Data yang diperlukan tidak diterima, tangani kesalahan di sini
    echo "Error: Data yang diperlukan tidak diterima.";
    echo mysqli_error($conn);
}
