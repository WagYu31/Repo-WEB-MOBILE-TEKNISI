<?php
// Memasukkan file koneksi ke database
include "../../conn.php";

// Menangkap data yang dikirim dari formulir
$no_kk = $_POST['noKk'];
$nama_kepala_keluarga = $_POST['nama'];
$nik_kepala_keluarga = $_POST['nik'];
$tempat_lahir = $_POST['tmpLahir'];
$tanggal_lahir = $_POST['tglLahir'];
$jenis_kelamin = $_POST['jenisKelamin'];
$agama = $_POST['agama'];
$hubungan_dalam_keluarga = $_POST['hub'];
$nomor_telepon = $_POST['nomorTelepon'];
$email = $_POST['email'];
$alamat = $_POST['alamat'];
$rt = $_POST['rt'];
$rw = $_POST['rw'];
$kelurahan = $_POST['kelurahan'];
$kecamatan = $_POST['kecamatan'];
$domisili_sekarang = $_POST['domisili'];
$pendidikan = $_POST['pendidikan'];
$pekerjaan = $_POST['pekerjaan'];
$kewarganegaraan = $_POST['kewarganegaraan'];
$status = $_POST['status'];

// Insert data ke tabel kartu_keluarga
$queryKartuKeluarga = "INSERT INTO kartu_keluarga (no_kk, kepala_keluarga, alamat, rt, rw, kecamatan, kelurahan, domisili_sekarang) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmtKartuKeluarga = mysqli_prepare($conn, $queryKartuKeluarga);
mysqli_stmt_bind_param($stmtKartuKeluarga, "ssssssss", 
                        $no_kk, 
                        $nama_kepala_keluarga, 
                        $alamat, 
                        $rt, 
                        $rw, 
                        $kecamatan, 
                        $kelurahan, 
                        $domisili_sekarang);

// Eksekusi statement
mysqli_stmt_execute($stmtKartuKeluarga);

$queryDataKeluarga = "INSERT INTO data_warga (no_kk, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, status_hubungan_dalam_keluarga, nomor_telepon, email, pendidikan, pekerjaan, kewarganegaraan, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmtDataKeluarga = mysqli_prepare($conn, $queryDataKeluarga);
mysqli_stmt_bind_param($stmtDataKeluarga, "iissssssisssss", 
                        $no_kk, 
                        $nik_kepala_keluarga, 
                        $nama_kepala_keluarga, 
                        $tempat_lahir, 
                        $tanggal_lahir, 
                        $jenis_kelamin, 
                        $agama, 
                        $hubungan_dalam_keluarga, 
                        $nomor_telepon, 
                        $email,  
                        $pendidikan, 
                        $pekerjaan, 
                        $kewarganegaraan, 
                        $status);

// Eksekusi statement
mysqli_stmt_execute($stmtDataKeluarga);

// Tutup statement dan koneksi
mysqli_stmt_close($stmtKartuKeluarga);
mysqli_stmt_close($stmtDataKeluarga);
mysqli_close($conn);

// Redirect ke halaman lain setelah proses selesai
header("Location: tables.php");
exit();
?>
