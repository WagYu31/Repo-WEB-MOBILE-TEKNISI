<?php
/**
 * migrate_sales.php
 * Jalankan SEKALI untuk menambah kolom clock in/out ke tabel pelaksanaan_sales
 * Akses: https://jadwal.id-giti.com/staff/migrate_sales.php
 * HAPUS file ini setelah berhasil dijalankan!
 */
session_start();
$jabatan = $_SESSION['jabatan'] ?? $_SESSION['role'] ?? '';
if (!isset($_SESSION['id']) || !in_array($jabatan, ['Super Admin', 'Admin'])) {
    die('❌ Akses ditolak. Login sebagai Admin/Super Admin terlebih dahulu. (jabatan: ' . $jabatan . ')');
}

include 'conn.php'; // pakai conn.php yang sudah ada di server

$migrations = [
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS ci_at DATETIME NULL COMMENT 'Clock In timestamp'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS co_at DATETIME NULL COMMENT 'Clock Out timestamp'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS lat_ci VARCHAR(30) NULL COMMENT 'Latitude saat Clock In'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS lon_ci VARCHAR(30) NULL COMMENT 'Longitude saat Clock In'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS lat_co VARCHAR(30) NULL COMMENT 'Latitude saat Clock Out'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS lon_co VARCHAR(30) NULL COMMENT 'Longitude saat Clock Out'",
    "ALTER TABLE pelaksanaan_sales ADD COLUMN IF NOT EXISTS catatan_visit TEXT NULL COMMENT 'Catatan kunjungan sales'",
];

echo '<meta charset="utf-8">';
echo '<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:30px;} 
      .ok{color:#10b981} .err{color:#ef4444} .info{color:#3b82f6}
      h2{color:#fff} hr{border-color:#334155}</style>';
echo '<h2>🚀 Sales Migration — pelaksanaan_sales</h2><hr>';

$success = 0;
$skip    = 0;
$errors  = 0;

foreach ($migrations as $sql) {
    preg_match('/ADD COLUMN IF NOT EXISTS (\w+)/', $sql, $m);
    $col = $m[1] ?? $sql;
    
    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            echo "<p class='ok'>✅ Kolom <b>$col</b> berhasil ditambahkan</p>";
            $success++;
        } else {
            echo "<p class='info'>ℹ️ Kolom <b>$col</b> sudah ada (skip)</p>";
            $skip++;
        }
    } else {
        echo "<p class='err'>❌ Error pada <b>$col</b>: " . $conn->error . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<p class='ok'><b>Selesai!</b> Ditambahkan: $success | Skip: $skip | Error: $errors</p>";

if ($errors === 0) {
    echo "<p style='color:#f59e0b'>⚠️ <b>PENTING:</b> Hapus file <code>staff/migrate_sales.php</code> dari server setelah ini!</p>";
}
