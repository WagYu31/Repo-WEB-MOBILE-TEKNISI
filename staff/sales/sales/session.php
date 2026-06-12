<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header('Location: ../index.php');
  exit();
}

// Ambil nilai NIK dari sesi
$nikSesi = $nik;
$role = $_SESSION["role"];
?>