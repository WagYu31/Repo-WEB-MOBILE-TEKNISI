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
?>