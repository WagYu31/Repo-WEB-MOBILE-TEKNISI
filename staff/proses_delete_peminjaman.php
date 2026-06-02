<?php
session_start();
include "conn.php"; // Pastikan file koneksi Anda benar

// Pastikan request adalah GET dan parameter id ada
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    $_SESSION['error_message'] = "Permintaan tidak valid.";
    header('Location: halaman-peminjaman.php'); // Ganti dengan halaman daftar peminjaman Anda
    exit();
}

// Ambil id dari URL dan pastikan itu adalah angka
$peminjaman_id = (int)$_GET['id'];

if ($peminjaman_id <= 0) {
    $_SESSION['error_message'] = "ID Peminjaman tidak valid.";
    header('Location: halaman-peminjaman.php'); // Ganti dengan halaman daftar peminjaman Anda
    exit();
}

// Set timezone ke Jakarta
date_default_timezone_set('Asia/Jakarta');
$deleted_at = date('Y-m-d H:i:s'); // Waktu saat ini

// Siapkan query UPDATE yang aman menggunakan Prepared Statements
$sql = "UPDATE peminjaman_barang SET deleted_at = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // Bind parameter ke query (s = string, i = integer)
    mysqli_stmt_bind_param($stmt, "si", $deleted_at, $peminjaman_id);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Cek apakah ada baris yang terpengaruh
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['success_message'] = "Data peminjaman berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Data peminjaman tidak ditemukan atau sudah dihapus.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data: " . mysqli_stmt_error($stmt);
    }
    
    // Tutup statement
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Query gagal disiapkan: " . mysqli_error($conn);
}

// Tutup koneksi
mysqli_close($conn);

// Alihkan pengguna kembali ke halaman daftar peminjaman
// Pastikan nama file ini sesuai dengan halaman Anda
header('Location: peminjaman.php');
exit();
?>