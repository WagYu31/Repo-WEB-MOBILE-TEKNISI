<?php

include "../../conn.php";

// Periksa apakah data yang diperlukan telah diterima
if (isset($_GET['latitude'], $_GET['longitude'], $_GET['id_visits'])) {
    // Ambil data dari $_GET
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $id_visits = $_GET['id_visits'];
    $currentDateTime = date("Y-m-d H:i:s");
    $status = "on process";
    $lokasiMulai = $latitude . "," . $longitude;

    $selAll = "SELECT * FROM visits WHERE id_visits = $id_visits";
    $resAll = mysqli_query($conn, $selAll);
    $rowAll = mysqli_fetch_assoc($resAll);

    $tglReq = $rowAll["tgl_visits"];
    $tglMulai = $rowAll["tgl_mulai"];

    if (strtotime($tglReq) >= strtotime($tglMulai)){
        $allDiff = '0';
    }
    elseif(strtotime($tglReq) < strtotime($tglMulai)) {
        $diff = strtotime($tglMulai) - strtotime($tglReq);

        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        $allDiff = "$hours jam, $minutes menit, $seconds detik";
    } else {
        $allDiff = '0'; 
    }

    $query = "UPDATE visits SET tgl_mulai = '$currentDateTime', lokasi_mulai = '$lokasiMulai', status = '$status' WHERE id_visits = '$id_visits'";
    if (mysqli_query($conn, $query)) {
        header("location: index.php");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Error: Data yang diperlukan tidak diterima.";
}
