<?php
/**
 * API Sales App Version Check
 * 
 * Endpoint untuk mengecek versi minimum Sales App.
 * Untuk force update, ubah min_version ke versi terbaru.
 * 
 * URL: https://jadwal.id-giti.com/staff/api_sales_version.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// ═══════════════════════════════════════════════
// KONFIGURASI VERSI - UBAH DI SINI UNTUK FORCE UPDATE
// ═══════════════════════════════════════════════
$response = [
    'min_version'     => '1.5.0',   // Versi minimum yang dibolehkan
    'latest_version'  => '1.5.0',   // Versi terbaru yang tersedia
    'update_url'      => 'https://jadwal.id-giti.com/staff/download/LoewixSales-v1.5.0.apk',
    'update_message'  => 'Versi terbaru (v1.5.0) tersedia! Fitur baru: Notifikasi push, Video compress, Kode Customer & Kunjungan.',
    'force_message'   => 'Versi aplikasi Anda sudah tidak didukung. Silakan update ke versi terbaru.',
];

echo json_encode($response);
?>
