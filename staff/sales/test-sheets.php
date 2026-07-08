<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "../conn.php";
include_once "../include/format_tanggal.php";
require_once "GoogleSheetsClient.php";

$spreadsheetId = "1N8OzWJbJ4FX0pQmcJuvLDaR9WOqjF0dajE4ks4nJhP0";
$sheetName = "Sheet1";
$idSales = 0; // Semua Sales
$bulan = "2026-07";

try {
    echo "1. Fetching data from DB...\n";
    $whereClauses = ["ks.deleted_at IS NULL", "tks.deleted_at IS NULL"];
    if (!empty($bulan)) {
        $whereClauses[] = "DATE_FORMAT(ks.jadwal, '%Y-%m') = '" . mysqli_real_escape_string($conn, $bulan) . "'";
    }
    $whereSql = implode(" AND ", $whereClauses);

    $sql = "SELECT ks.id AS kegiatan_id, ks.kode AS kode_kunjungan, ks.jadwal AS tgl_visits, 
                   sc.nama AS nama_cust, sc.kategori AS jenis_usaha, sc.telp_pribadi AS contact_person,
                   sc.alamat AS alamat_cust, ks.alamat_lokasi AS alamat_kegiatan, sc.kota AS kota_cust,
                   ps.catatan_visit, ps.keterangan AS ps_keterangan,
                   ps.image_1, ps.image_2, ps.image_3, ps.image_4, ps.image_5,
                   s.nama AS nama_sales
            FROM kegiatan_sales ks
            JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales
            JOIN sales s ON tks.id_sales = s.id
            JOIN sales_customer sc ON ks.id_customer = sc.id
            LEFT JOIN pelaksanaan_sales ps ON (ps.kegiatan_id = ks.id AND ps.sales_id = tks.id_sales)
            WHERE $whereSql
            ORDER BY ks.jadwal ASC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception("DB Error: " . mysqli_error($conn));
    }

    $rows = [];
    $no = 1;
    $lokasiKunjungan = "SEMUA WILAYAH";
    $firstCity = '';

    while ($row = mysqli_fetch_assoc($result)) {
        $kegiatanId = $row['kegiatan_id'];
        $jenisKunjungan = "Kunjungan Pertama";
        if (preg_match('/KJG-(\d+)/', $row['kode_kunjungan'], $matchesKjg)) {
            $num = intval($matchesKjg[1]);
            if ($num == 1) {
                $jenisKunjungan = "Kunjungan Pertama";
            } elseif ($num == 2) {
                $jenisKunjungan = "Kunjungan Kedua";
            } elseif ($num == 3) {
                $jenisKunjungan = "Kunjungan Ketiga";
            } else {
                $jenisKunjungan = "Kunjungan Ke-" . $num;
            }
        }

        if (empty($firstCity) && !empty($row['kota_cust'])) {
            $firstCity = strtoupper($row['kota_cust']);
            $lokasiKunjungan = $firstCity;
        }

        $tglKunjungan = '-';
        if (!empty($row['tgl_visits']) && $row['tgl_visits'] !== '0000-00-00 00:00:00') {
            $tglKunjungan = formatTanggal('EEEE, dd MMMM yyyy', $row['tgl_visits']);
        }

        $mapsLink = !empty($row['alamat_kegiatan']) ? $row['alamat_kegiatan'] : (!empty($row['alamat_cust']) ? $row['alamat_cust'] : '');
        if (!empty($mapsLink) && strpos($mapsLink, '📍') === false) {
            $mapsLink = '📍 ' . $mapsLink;
        }

        $cp = !empty($row['contact_person']) ? $row['contact_person'] : '';

        $fotoLinks = [];
        $apiBase = "https://api-teknisi.id-giti.com/storage/image/";
        for ($i = 1; $i <= 5; $i++) {
            $imgCol = "image_$i";
            if (!empty($row[$imgCol])) {
                $fotoLinks[] = $apiBase . $row[$imgCol];
            }
        }
        $buktiFoto = implode("\n", $fotoLinks);
        $catatan = !empty($row['catatan_visit']) ? $row['catatan_visit'] : (!empty($row['ps_keterangan']) ? $row['ps_keterangan'] : '');

        $rows[] = [
            $no++,
            $tglKunjungan,
            $row['nama_cust'],
            $mapsLink,
            ucfirst($row['jenis_usaha']),
            $cp,
            $jenisKunjungan,
            $buktiFoto,
            $catatan,
            ''
        ];
    }
    
    echo "Fetched " . count($rows) . " rows from database.\n";

    echo "2. Initializing Google Sheets Client...\n";
    $keyPath = __DIR__ . '/../../staff/config/google-sheets-key.json';
    $client = new GoogleSheetsClient($keyPath);
    
    echo "3. Updating header values (Sales Name & Location)...\n";
    $client->updateValues($spreadsheetId, "{$sheetName}!C3:C4", [
        ["SEMUA SALES"],
        [$lokasiKunjungan]
    ]);
    
    echo "4. Clearing previous data rows (A15:J1000)...\n";
    $client->clearValues($spreadsheetId, "{$sheetName}!A15:J1000");
    
    echo "5. Writing data rows starting from A15...\n";
    if (!empty($rows)) {
        $client->updateValues($spreadsheetId, "{$sheetName}!A15:J" . (15 + count($rows) - 1), $rows);
    }
    
    echo "SUCCESS! Fully completed sheet sync successfully!\n";

} catch (Exception $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
