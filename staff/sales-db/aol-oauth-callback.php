<?php
require_once 'config.php';
require_once 'functions.php';

session_start();

if (isset($_GET['error'])) {
    die('Error dari Accurate: ' . displayValue($_GET['error']));
}

if (!isset($_GET['code'])) {
    die('Authorization code tidak ditemukan');
}

$tokenResponse = getAccessToken($_GET['code']);

if (!$tokenResponse['success']) {
    die('Gagal mendapatkan token: ' . displayValue($tokenResponse['error']));
}

$_SESSION['accurate_access_token'] = $tokenResponse['data']['access_token'];
$_SESSION['accurate_refresh_token'] = $tokenResponse['data']['refresh_token'] ?? null;
$_SESSION['accurate_user'] = $tokenResponse['data']['user'] ?? null;

header('Location: index.php');
exit();