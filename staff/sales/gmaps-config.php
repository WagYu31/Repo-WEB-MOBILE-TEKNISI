<?php
/**
 * Google Maps Scraping — Konfigurasi & Rate Limiter
 * 
 * Menyimpan API Key, batas request, dan fungsi rate limiting.
 * Counter disimpan di file JSON untuk persistensi lintas request.
 */

// ══════════════════════════════════════════════════
// KONFIGURASI — UBAH DI SINI
// ══════════════════════════════════════════════════
// Foursquare API Key (GRATIS - 100.000 request/bulan, tanpa kartu kredit)
// Register di: https://foursquare.com/developers → Create Project → Copy API Key
define('FOURSQUARE_API_KEY', ''); // ← PASTE API KEY DI SINI

// Batas penggunaan — proteksi internal
define('GMAPS_DAILY_LIMIT',   50);    // Max request per hari
define('GMAPS_MONTHLY_LIMIT', 1000);  // Max request per bulan (Foursquare: 100K free)

// File counter
define('GMAPS_USAGE_FILE', __DIR__ . '/gmaps_usage.json');

// ══════════════════════════════════════════════════
// FUNGSI RATE LIMITER
// ══════════════════════════════════════════════════

/**
 * Baca data usage dari file JSON
 */
function gmaps_read_usage() {
    if (!file_exists(GMAPS_USAGE_FILE)) {
        return [
            'daily'   => [],
            'monthly' => [],
            'total'   => 0
        ];
    }
    $data = json_decode(file_get_contents(GMAPS_USAGE_FILE), true);
    return $data ?: ['daily' => [], 'monthly' => [], 'total' => 0];
}

/**
 * Simpan data usage ke file JSON
 */
function gmaps_write_usage($data) {
    file_put_contents(GMAPS_USAGE_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Cek apakah masih bisa request (belum melebihi limit)
 * @return array ['allowed' => bool, 'reason' => string, 'daily_used' => int, 'monthly_used' => int]
 */
function gmaps_check_limit() {
    $usage = gmaps_read_usage();
    $today = date('Y-m-d');
    $month = date('Y-m');

    $dailyUsed   = $usage['daily'][$today] ?? 0;
    $monthlyUsed = $usage['monthly'][$month] ?? 0;

    // Cek batas harian
    if ($dailyUsed >= GMAPS_DAILY_LIMIT) {
        return [
            'allowed'      => false,
            'reason'       => 'Batas harian tercapai (' . GMAPS_DAILY_LIMIT . ' request/hari). Coba lagi besok.',
            'daily_used'   => $dailyUsed,
            'daily_limit'  => GMAPS_DAILY_LIMIT,
            'monthly_used' => $monthlyUsed,
            'monthly_limit'=> GMAPS_MONTHLY_LIMIT,
        ];
    }

    // Cek batas bulanan
    if ($monthlyUsed >= GMAPS_MONTHLY_LIMIT) {
        return [
            'allowed'      => false,
            'reason'       => 'Batas bulanan tercapai (' . GMAPS_MONTHLY_LIMIT . ' request/bulan). Tunggu bulan depan atau hubungi developer.',
            'daily_used'   => $dailyUsed,
            'daily_limit'  => GMAPS_DAILY_LIMIT,
            'monthly_used' => $monthlyUsed,
            'monthly_limit'=> GMAPS_MONTHLY_LIMIT,
        ];
    }

    return [
        'allowed'      => true,
        'reason'       => 'OK',
        'daily_used'   => $dailyUsed,
        'daily_limit'  => GMAPS_DAILY_LIMIT,
        'monthly_used' => $monthlyUsed,
        'monthly_limit'=> GMAPS_MONTHLY_LIMIT,
    ];
}

/**
 * Tambah 1 ke counter setelah request berhasil
 */
function gmaps_increment_usage() {
    $usage = gmaps_read_usage();
    $today = date('Y-m-d');
    $month = date('Y-m');

    // Increment daily
    if (!isset($usage['daily'][$today])) {
        $usage['daily'][$today] = 0;
    }
    $usage['daily'][$today]++;

    // Increment monthly
    if (!isset($usage['monthly'][$month])) {
        $usage['monthly'][$month] = 0;
    }
    $usage['monthly'][$month]++;

    // Increment total
    $usage['total'] = ($usage['total'] ?? 0) + 1;

    // Cleanup: hapus data harian lebih dari 30 hari lalu
    $cutoff = date('Y-m-d', strtotime('-30 days'));
    foreach ($usage['daily'] as $day => $count) {
        if ($day < $cutoff) unset($usage['daily'][$day]);
    }

    // Cleanup: hapus data bulanan lebih dari 6 bulan lalu
    $monthCutoff = date('Y-m', strtotime('-6 months'));
    foreach ($usage['monthly'] as $m => $count) {
        if ($m < $monthCutoff) unset($usage['monthly'][$m]);
    }

    gmaps_write_usage($usage);
}

/**
 * Get current usage stats (untuk ditampilkan di frontend)
 */
function gmaps_get_stats() {
    $usage = gmaps_read_usage();
    $today = date('Y-m-d');
    $month = date('Y-m');

    return [
        'daily_used'    => $usage['daily'][$today] ?? 0,
        'daily_limit'   => GMAPS_DAILY_LIMIT,
        'daily_remaining'=> GMAPS_DAILY_LIMIT - ($usage['daily'][$today] ?? 0),
        'monthly_used'  => $usage['monthly'][$month] ?? 0,
        'monthly_limit' => GMAPS_MONTHLY_LIMIT,
        'monthly_remaining' => GMAPS_MONTHLY_LIMIT - ($usage['monthly'][$month] ?? 0),
        'total_all_time'=> $usage['total'] ?? 0,
    ];
}
?>
