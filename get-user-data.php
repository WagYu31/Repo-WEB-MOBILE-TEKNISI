<?php

include "conn.php";
// Query untuk mengambil data nik dari tabel user berdasarkan id_user
$queryUser = "SELECT * FROM user WHERE id_user = $id_user";
$resultUser = mysqli_query($conn, $queryUser);

if (mysqli_num_rows($resultUser) > 0) {
    $rowUser = mysqli_fetch_assoc($resultUser);
    $nik = $rowUser["nik"];

    // Query untuk mengambil data dari tabel loewix sesuai dengan nik
    $queryLoewix = "SELECT * FROM loewix WHERE nik = '$nik'";
    $resultLoewix = mysqli_query($conn, $queryLoewix);

    if (mysqli_num_rows($resultLoewix) > 0) {
        // Data ditemukan, lakukan sesuatu dengan data dari tabel loewix
        while ($rowLoewix = mysqli_fetch_assoc($resultLoewix)) {
            $nik = $rowLoewix["nik"];
            $nmUser = $rowLoewix["nama"];
            $jabatan = $rowLoewix["jabatan"];
            $wa = $rowLoewix["no_tlp"];
        }
    } else {
        // Data tidak ditemukan di tabel loewix
    }
} else {
    // Data tidak ditemukan di tabel user berdasarkan id_user
}

?>