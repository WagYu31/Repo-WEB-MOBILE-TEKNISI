<?php
session_start();
include "conn.php";

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header('Location: halaman_anda.php'); // Ganti dengan halaman list kegiatan Anda
    exit();
}

// Ambil data dari form
$kode_transaksi = $_POST['kode_transaksi'];
$invoice = $_POST['invoice'];
$garansi = $_POST['garansi'] ?? 'Tidak'; // Ambil pilihan "Ya" atau "Tidak"
$keterangan_garansi = !empty($_POST['keterangan_garansi']) ? $_POST['keterangan_garansi'] : NULL;

// Validasi dasar
if (empty($kode_transaksi) || empty($invoice)) {
    $_SESSION['error_message'] = "Kode Transaksi dan Kode Invoice wajib diisi.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Jika pilihan garansi adalah "Tidak", pastikan keterangan garansi juga dikosongkan (diset NULL)
if ($garansi === 'Tidak') {
    $keterangan_garansi = NULL;
}

// Siapkan query UPDATE yang aman
$sql = "UPDATE kegiatan SET 
            invoice = ?, 
            garansi = ?, 
            keterangan_garansi = ? 
        WHERE 
            kode = ?";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssss", $invoice, $garansi, $keterangan_garansi, $kode_transaksi);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Data untuk kode '$kode_transaksi' berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data: " . mysqli_stmt_error($stmt);
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Query gagal disiapkan: " . mysqli_error($conn);
}

mysqli_close($conn);

// Alihkan pengguna kembali ke halaman sebelumnya
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>