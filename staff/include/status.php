<?php
$status_terubah = '';
switch ($status) {
    case 'waiting':
        $status_terubah = 'Dalam Antrian';
        break;
    case 'dijadwalkan':
        $status_terubah = 'Dijadwalkan';
        break;
    case 'berjalan':
        $status_terubah = 'Dalam Proses';
        break;
    case 'selesai':
        $status_terubah = 'Selesai';
        break;
    case 'selesai by admin':
        $status_terubah = 'Diselesaikan oleh Admin';
        break;
    case 'Lanjut Nanti':
        $status_terubah = 'Berlanjut';
        break;
    case 'Lanjutan':
        $status_terubah = 'Dilanjutkan';
        break;
    default:
        $status_terubah = $status;
        break;
}
?>