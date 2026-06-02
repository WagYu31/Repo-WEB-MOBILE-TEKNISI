<?php
$waktu_mulai_log = microtime(true);

register_shutdown_function(function() use ($waktu_mulai_log) {
    $waktu_selesai_log = microtime(true);
    $durasi = round($waktu_selesai_log - $waktu_mulai_log, 4);
    
    $ip_pengunjung = $_SERVER['REMOTE_ADDR'] ?? 'IP tidak diketahui';
    $metode_akses = $_SERVER['REQUEST_METHOD'] ?? 'Metode tidak diketahui';
    $halaman_tujuan = $_SERVER['REQUEST_URI'] ?? 'Halaman tidak diketahui';
    
    $data_post = !empty($_POST) ? json_encode($_POST) : 'Tidak ada data POST.';
    $data_get = !empty($_GET) ? json_encode($_GET) : 'Tidak ada data GET.';
    
    $error_terakhir = error_get_last();
    if ($error_terakhir !== null) {
        $pesan_error = "Terjadi error [{$error_terakhir['type']}] di baris {$error_terakhir['line']}: {$error_terakhir['message']}";
    } else {
        $pesan_error = "Halaman berjalan lancar tanpa error.";
    }

    $waktu_sekarang = date('Y-m-d H:i:s');
    
    $laporan = "Pengunjung mengakses dengan data GET: $data_get dan data POST: $data_post. Status: $pesan_error";

    require_once 'conn.php';

    if (isset($conn) && $conn instanceof mysqli) {
        $query_simpan = $conn->prepare("INSERT INTO log_aktivitas (waktu, ip_pengunjung, halaman, metode, durasi, pesan_manusia) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($query_simpan) {
            $query_simpan->bind_param("ssssds", $waktu_sekarang, $ip_pengunjung, $halaman_tujuan, $metode_akses, $durasi, $laporan);
            $query_simpan->execute();
            $query_simpan->close();
        }
    }
});
?>