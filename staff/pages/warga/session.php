<?php
session_start();

// Cek apakah pengguna sudah login atau belum
if (!isset($_SESSION['nik']) || empty($_SESSION['nik'])) {
  // Jika belum login, alihkan ke halaman login.php
  header('Location: ../login.php');
  exit();
}

// Ambil nilai NIK dari sesi
$nikSesi = $_SESSION["nik"];
$querySesi = "SELECT * FROM data_warga WHERE nik = $nikSesi";
$resultSesi = mysqli_query($conn, $querySesi);
$rowSesi = mysqli_fetch_assoc($resultSesi);
$no_kk = $rowSesi['no_kk'];

$querySesiUser = "SELECT * FROM user WHERE nik = $nikSesi";
$resultSesiUser = mysqli_query($conn, $querySesiUser);
$rowUser = mysqli_fetch_assoc($resultSesiUser);
$roleSesi = $rowUser['role'];
?>