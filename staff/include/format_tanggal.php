<?php
/**
 * Helper function to replace deprecated strftime() in PHP 8.1+
 * Uses IntlDateFormatter when available, with a pure-PHP Indonesian fallback
 * when php-intl is not installed on the server.
 */

if (!function_exists('formatTanggal')) {
    function formatTanggal(string $pattern = 'dd MMMM yyyy', $dateInput = null): string {
        if (class_exists('IntlDateFormatter')) {
            $fmt = new IntlDateFormatter(
                'id_ID',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                'Asia/Jakarta',
                IntlDateFormatter::GREGORIAN,
                $pattern
            );
            if ($dateInput === null) {
                return (string)$fmt->format(new DateTime());
            }
            if (is_int($dateInput)) {
                return (string)$fmt->format($dateInput);
            }
            return (string)$fmt->format(new DateTime((string)$dateInput));
        }

        // Pure-PHP fallback when ext-intl is not installed
        if ($dateInput === null) {
            $ts = time();
        } elseif (is_int($dateInput) || ctype_digit((string)$dateInput)) {
            $ts = (int)$dateInput;
        } else {
            $s = trim((string)$dateInput);
            if (preg_match('/^\d{4}-\d{2}$/', $s)) {
                $s .= '-01';
            }
            $ts = strtotime($s) ?: time();
        }

        $daysFull = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];
        $monthsFull = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $monthsShort = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        $d = date('d', $ts);
        $m = (int)date('n', $ts);
        $y = date('Y', $ts);
        $w = (int)date('w', $ts);

        return strtr($pattern, [
            'EEEE' => $daysFull[$w],
            'MMMM' => $monthsFull[$m],
            'MMM'  => $monthsShort[$m],
            'yyyy' => $y,
            'dd'   => $d,
        ]);
    }
}

if (!function_exists('ensurePorsiKerjaColumn')) {
    function ensurePorsiKerjaColumn($conn): void {
        static $checked = false;
        if ($checked || !$conn) return;
        $checked = true;
        $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `pendapatan_kegiatan` LIKE 'porsi_kerja'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            @mysqli_query($conn, "ALTER TABLE `pendapatan_kegiatan` ADD COLUMN `porsi_kerja` DECIMAL(4,2) NOT NULL DEFAULT 1.00 COMMENT '1.00 = Full Day, 0.50 = Setengah Hari' AFTER `pendapatan`");
        }
    }
}
if (isset($conn) && $conn) {
    ensurePorsiKerjaColumn($conn);
}
