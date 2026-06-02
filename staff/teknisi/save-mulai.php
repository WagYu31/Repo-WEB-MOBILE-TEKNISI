<?php

include "../conn.php";

// Periksa apakah data yang diperlukan telah diterima
if (isset($_GET['latitude'], $_GET['longitude'], $_GET['id_kegiatan'])) {
    // Ambil data dari $_GET
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $id_kegiatan = $_GET['id_kegiatan'];
    $currentDateTime = date("Y-m-d H:i:s");
    $status = "On Process";
    $lokasiMulai = $latitude . "," . $longitude;

    $selAll = "SELECT * FROM kegiatan WHERE id_kegiatan = $id_kegiatan";
    $resAll = mysqli_query($conn, $selAll);
    $rowAll = mysqli_fetch_assoc($resAll);

    $tglReq = $rowAll["tgl_request"];
    $tglMulai = $rowAll["tgl_mulai"];

    // Periksa apakah tgl_request < tgl_mulai
    if (strtotime($tglReq) >= strtotime($tglMulai)){
        $allDiff = '0'; // Jika tgl_request >= tgl_mulai
    }
    elseif(strtotime($tglReq) < strtotime($tglMulai)) {
        // Menghitung selisih waktu
        $diff = strtotime($tglMulai) - strtotime($tglReq);

        // Mengonversi selisih waktu menjadi jam, menit, dan detik
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        // Format selisih waktu ke dalam string "x jam, y menit, z detik"
        $allDiff = "$hours jam, $minutes menit, $seconds detik";
    } else {
        $allDiff = '0'; // Jika tgl_request >= tgl_mulai
    }

    $query = "UPDATE kegiatan SET tgl_mulai = '$currentDateTime', lokasi_mulai = '$lokasiMulai', status = '$status', late = '$allDiff' WHERE id_kegiatan = '$id_kegiatan'";
    if (mysqli_query($conn, $query)) {
        header("location: index.php");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Data yang diperlukan tidak diterima, tangani kesalahan di sini
    echo "Error: Data yang diperlukan tidak diterima.";
}
