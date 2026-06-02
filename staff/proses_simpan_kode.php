<?php
// Menggunakan autoload dari Composer untuk memuat kelas PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Membuat objek Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Menulis header kolom
$sheet->setCellValue('A1', 'Kode Garansi');

// Mendapatkan data jumlah dan kode dari POST
$jumlah = $_POST['jumlah'];
$kode_jenis = $_POST['kode_jenis'];

// Menulis kode garansi ke dalam file Excel
for ($i = 0; $i < $jumlah; $i++) {
    $random_code = 'LWX-' . $kode_jenis . '-';
    for ($j = 0; $j < 5; $j++) {
        $random_code .= chr(rand(65, 90)); // Menambahkan karakter acak dari A-Z
    }
    $sheet->setCellValue('A' . ($i + 2), $random_code);
}

// Menyimpan file Excel
$writer = new Xlsx($spreadsheet);
$writer->save('random_codes.xlsx');

// Mengirim file Excel sebagai respons
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="random_codes.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
?>