<?php
session_start();
setlocale(LC_TIME, 'id_ID');
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
  // Jika belum login, alihkan ke halaman login.php
  header('Location: index.php');
  exit();
}

// Ambil nilai NIK dari sesi
$idSesi = $_SESSION["id"];
$role = $_SESSION["jabatan"];
?>