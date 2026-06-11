<?php
/**
 * API App Version Check
 * 
 * Endpoint sederhana yang mengembalikan versi minimum yang dibutuhkan app teknisi.
 * Untuk force update, ubah min_version ke versi terbaru.
 * 
 * Contoh: Jika rilis v4.0.10, ubah min_version ke "4.0.10"
 * Semua teknisi yang masih pakai versi lama akan dipaksa update.
 * 
 * URL: https://jadwal.id-giti.com/staff/api_app_version.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// ═══════════════════════════════════════════════
// KONFIGURASI VERSI - UBAH DI SINI UNTUK FORCE UPDATE
// ═══════════════════════════════════════════════
$response = [
    'min_version'     => '4.0.10',   // Versi minimum yang dibolehkan
    'latest_version'  => '4.0.10',   // Versi terbaru yang tersedia
    'update_url'      => 'https://jadwal.id-giti.com/staff/download/teknisi-latest.apk', // URL download APK
    'update_message'  => 'Versi terbaru tersedia! Silakan update untuk mendapatkan fitur dan perbaikan terbaru.',
    'force_message'   => 'Versi aplikasi Anda sudah tidak didukung. Silakan update ke versi terbaru untuk melanjutkan.',
];

echo json_encode($response);
?>
