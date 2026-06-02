<?php
include "../../conn.php";

$no_kk = $_POST['noKk'];
$nik = $_POST['nik'];
$nama = $_POST['nama'];
$jenis_kelamin = $_POST['jenisKelamin'];
$tempat_lahir = $_POST['tmpLahir'];
$tanggal_lahir = $_POST['tglLahir'];
$pendidikan = $_POST['pendidikan'];
$nomor_telepon = $_POST['nomorTelepon'];
$email = $_POST['email'];
$pekerjaan = $_POST['pekerjaan'];
$status = $_POST['status'];
$status_hubungan_dalam_keluarga = $_POST['hub'];
$agama = $_POST['agama'];
$kewarganegaraan = $_POST['kewarganegaraan'];

$queryDataWarga = "INSERT INTO data_warga (no_kk, nik, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, pendidikan, nomor_telepon, email, pekerjaan, status, status_hubungan_dalam_keluarga, agama, kewarganegaraan) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmtDataWarga = mysqli_prepare($conn, $queryDataWarga);
mysqli_stmt_bind_param($stmtDataWarga, "ssssssssssssss", 
                        $no_kk, 
                        $nik, 
                        $nama, 
                        $jenis_kelamin, 
                        $tempat_lahir, 
                        $tanggal_lahir, 
                        $pendidikan, 
                        $nomor_telepon, 
                        $email, 
                        $pekerjaan, 
                        $status, 
                        $status_hubungan_dalam_keluarga, 
                        $agama, 
                        $kewarganegaraan);


mysqli_stmt_execute($stmtDataWarga);
mysqli_stmt_close($stmtDataWarga);
mysqli_close($conn);

header("Location: tables.php");
exit();
?>
