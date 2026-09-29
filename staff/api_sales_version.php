<?php
/**
 * API Sales Version Check
 * Returns the latest and minimum required app version.
 * To force update: set min_version equal to latest_version.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

 = 'https://jadwal.id-giti.com/staff/download/LoewixSales-v1.9.9.apk?v=' . time();

 = 'Versi terbaru (v1.9.9) tersedia! Pembaruan tombol +/- pada audit sisa fisik rak toko dan panduan cara audit konsinyasi TIP TOK.';

 = [
    'status'         => 'success',
    'latest_version' => '1.9.9',
    'min_version'    => '1.9.9',
    'version'        => '1.9.9',
    'version_code'   => 199,
    'force_update'   => true,
    'update_url'     => ,
    'download_url'   => ,
    'update_message' => ,
    'force_message'  => ,
    'changelog'      => ,
    'data' => [
        'latest_version' => '1.9.9',
        'min_version'    => '1.9.9',
        'version'        => '1.9.9',
        'version_code'   => 199,
        'force_update'   => true,
        'update_url'     => ,
        'download_url'   => ,
        'update_message' => ,
        'force_message'  => ,
        'changelog'      => ,
    ]
];

echo json_encode();
