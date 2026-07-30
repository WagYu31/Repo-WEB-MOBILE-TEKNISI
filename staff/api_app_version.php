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
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// ═══════════════════════════════════════════════
// KONFIGURASI VERSI - UBAH DI SINI UNTUK FORCE UPDATE
// ═══════════════════════════════════════════════
$response = [
    'min_version'     => '4.0.33',   // Versi minimum yang dibolehkan
    'latest_version'  => '4.0.33',   // Versi terbaru yang tersedia
    'update_url'      => 'https://jadwal.id-giti.com/staff/download/teknisi-v4.0.33-b2.apk?v=' . time(), // URL download APK (bypassing CDN cache)
    'update_message'  => 'Versi terbaru (v4.0.33) tersedia! Menampilkan riwayat catatan & lampiran bukti admin.',
    'force_message'   => 'Versi aplikasi Anda perlu diperbarui (v4.0.33). Silakan update ke versi terbaru untuk melihat riwayat catatan admin.',
];

echo json_encode($response);
?>
