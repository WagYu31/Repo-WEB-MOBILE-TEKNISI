<?php

include "conn.php";


// Query untuk mengambil data dari tabel loewix sesuai dengan nik
$queryLoewix = "SELECT * FROM users WHERE id = '$idSesi'";
$resultLoewix = mysqli_query($conn, $queryLoewix);

if (mysqli_num_rows($resultLoewix) > 0) {
    // Data ditemukan, lakukan sesuatu dengan data dari tabel loewix
    while ($rowLoewix = mysqli_fetch_assoc($resultLoewix)) {
        $id = $rowLoewix["id"];
        $nmUser = $rowLoewix["name"];
        $jabatan = $rowLoewix["jabatan"];
        // $wa = $rowLoewix["no_tlp"];
    }
} else {
    // Data tidak ditemukan di tabel loewix
}
