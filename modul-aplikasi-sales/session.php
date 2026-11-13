<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id']) && isset($_SESSION['id_user'])) {
    $_SESSION['id'] = $_SESSION['id_user'];
}
if (!isset($_SESSION['jabatan']) && isset($_SESSION['role'])) {
    $_SESSION['jabatan'] = $_SESSION['role'];
}

$idSesi = $_SESSION["id"] ?? $_SESSION["id_user"] ?? null;
$role = $_SESSION["jabatan"] ?? $_SESSION["role"] ?? null;
?>
