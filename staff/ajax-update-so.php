<?php
include 'conn.php';
include 'session.php';
include 'get-user-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak valid']);
    exit;
}

$kode = trim($_POST['kode'] ?? '');
$no_so = trim($_POST['no_so'] ?? '');

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode Transaksi tidak ditemukan']);
    exit;
}

$kode_esc = mysqli_real_escape_string($conn, $kode);
$no_so_esc = !empty($no_so) ? mysqli_real_escape_string($conn, $no_so) : null;
$no_so_sql = $no_so_esc !== null ? "'$no_so_esc'" : "NULL";

// Update tabel kegiatan
$updateKegiatan = mysqli_query($conn, "UPDATE kegiatan SET no_so = $no_so_sql, updated_at = NOW() WHERE kode = '$kode_esc'");

// Sinkronisasi dengan tabel progress_kegiatan
if ($no_so_esc !== null) {
    $chkProg = mysqli_query($conn, "SELECT id FROM progress_kegiatan WHERE kode = '$kode_esc'");
    if ($chkProg && mysqli_num_rows($chkProg) > 0) {
        mysqli_query($conn, "UPDATE progress_kegiatan SET is_so = 1, no_so = '$no_so_esc', tgl_keluar_so = NOW() WHERE kode = '$kode_esc'");
    } else {
        mysqli_query($conn, "INSERT INTO progress_kegiatan (kode, is_so, no_so, tgl_keluar_so) VALUES ('$kode_esc', 1, '$no_so_esc', NOW())");
    }
} else {
    mysqli_query($conn, "UPDATE progress_kegiatan SET is_so = 0, no_so = NULL WHERE kode = '$kode_esc'");
}

if ($updateKegiatan) {
    echo json_encode([
        'status' => 'success',
        'message' => !empty($no_so) ? 'Nomor SO berhasil disimpan!' : 'Nomor SO berhasil dikosongkan!',
        'kode' => $kode,
        'no_so' => $no_so
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . mysqli_error($conn)]);
}
?>
