<?php
/**
 * Auto-initialization for BAST tables, customer column, and upload folders.
 * Safe and idempotent on every include.
 */
if (!defined('BAST_INIT_DONE')) {
    define('BAST_INIT_DONE', true);

    // 1. Ensure upload & template directories exist
    $bastUploadDir = __DIR__ . '/uploads/bast';
    if (!is_dir($bastUploadDir)) {
        @mkdir($bastUploadDir, 0775, true);
    }
    $bastTemplateDir = __DIR__ . '/assets/templates';
    if (!is_dir($bastTemplateDir)) {
        @mkdir($bastTemplateDir, 0775, true);
    }

    if (isset($conn) && $conn) {
        // 2. Add wajib_bast column to customer if not exists
        $chkCol = @mysqli_query($conn, "SHOW COLUMNS FROM customer LIKE 'wajib_bast'");
        if ($chkCol && mysqli_num_rows($chkCol) === 0) {
            @mysqli_query($conn, "ALTER TABLE customer ADD COLUMN wajib_bast TINYINT(1) NOT NULL DEFAULT 0");
        }

        // 3. Create bast_laporan table
        $sqlBastLaporan = "CREATE TABLE IF NOT EXISTS bast_laporan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kegiatan_id INT NOT NULL DEFAULT 0,
            kode VARCHAR(64) NOT NULL,
            customer_id INT DEFAULT NULL,
            teknisi_id INT DEFAULT NULL,
            foto_display_utama VARCHAR(255) DEFAULT NULL,
            ket_display_utama TEXT DEFAULT NULL,
            no_bast VARCHAR(150) DEFAULT NULL,
            hari_tanggal_teks TEXT DEFAULT NULL,
            pihak1_nama VARCHAR(150) DEFAULT NULL,
            pihak1_jabatan VARCHAR(150) DEFAULT NULL,
            pihak1_perusahaan VARCHAR(220) DEFAULT NULL,
            pihak1_alamat TEXT DEFAULT NULL,
            pihak2_nama VARCHAR(150) DEFAULT 'Resmina',
            pihak2_jabatan VARCHAR(150) DEFAULT 'Manager Umum',
            pihak2_perusahaan VARCHAR(220) DEFAULT 'GraViTTi Technology',
            pihak2_alamat TEXT DEFAULT NULL,
            rincian_po_json LONGTEXT DEFAULT NULL,
            deskripsi_pekerjaan_json LONGTEXT DEFAULT NULL,
            device_credentials_json LONGTEXT DEFAULT NULL,
            kota_tanggal_ttd VARCHAR(150) DEFAULT NULL,
            status_bast VARCHAR(30) NOT NULL DEFAULT 'submitted',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_kode (kode),
            KEY idx_customer (customer_id),
            KEY idx_teknisi (teknisi_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($conn, $sqlBastLaporan);

        // 4. Create bast_titik_kamera table
        $sqlBastTitik = "CREATE TABLE IF NOT EXISTS bast_titik_kamera (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bast_id INT NOT NULL,
            urutan INT NOT NULL DEFAULT 1,
            foto_titik VARCHAR(255) NOT NULL,
            keterangan TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_bast_id (bast_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($conn, $sqlBastTitik);
    }
}

function bastIndonesianDay($timestamp = null) {
    $ts = $timestamp ?: time();
    $days = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => "Jum'at",
        6 => 'Sabtu',
    ];
    return $days[(int)date('w', $ts)] ?? 'Senin';
}

function bastIndonesianDate($timestamp = null) {
    $ts = $timestamp ?: time();
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return date('d', $ts) . ' ' . ($months[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
}

function bastRomanMonth($month = null) {
    $m = (int)($month ?: date('n'));
    $romans = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'];
    return $romans[$m] ?? 'I';
}

function bastActiveTemplatePath() {
    $custom = __DIR__ . '/assets/templates/CUSTOM_TEMPLATE_BAST.docx';
    if (file_exists($custom)) {
        return $custom;
    }
    return __DIR__ . '/assets/templates/NEW_TEMPLATE_BAST_YIMM.docx';
}

