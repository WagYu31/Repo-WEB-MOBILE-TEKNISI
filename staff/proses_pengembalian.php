<?php
session_start();
include "conn.php"; // Pastikan file koneksi Anda benar

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah.");
}

// [PERBAIKAN] Ambil data dari form dan pastikan tipenya integer untuk keamanan
$peminjaman_id = isset($_POST['peminjaman_id']) ? $_POST['peminjaman_id'] : 0;
$barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
$qty_akhir = isset($_POST['qty_akhir']) ? (int)$_POST['qty_akhir'] : 0;
$keterangan = $_POST['keterangan'];
$tgl_kembali = date('Y-m-d H:i:s'); // Waktu saat ini

// Validasi yang lebih kuat
if ($peminjaman_id <= 0 || $barang_id <= 0 || $qty_akhir <= 0) {
    $_SESSION['error_message'] = "Data yang dikirim tidak lengkap atau tidak valid.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Mulai transaksi database untuk memastikan semua query berhasil
mysqli_begin_transaction($conn);

try {
    // 1. Update tabel peminjaman_barang
    $sql_peminjaman = "UPDATE peminjaman_barang SET 
                            qty_akhir = ?, 
                            keterangan = ?, 
                            tgl_kembali = ?, 
                            status = 'selesai' 
                        WHERE 
                            id = ? AND tgl_kembali IS NULL"; // Tambahan: hanya update jika belum kembali
    
    $stmt_peminjaman = mysqli_prepare($conn, $sql_peminjaman);
    mysqli_stmt_bind_param($stmt_peminjaman, "issi", $qty_akhir, $keterangan, $tgl_kembali, $peminjaman_id);
    mysqli_stmt_execute($stmt_peminjaman);
    
    // [PERBAIKAN] Cek apakah ada baris yang benar-benar ter-update
    if (mysqli_stmt_affected_rows($stmt_peminjaman) == 0) {
        throw new Exception("Gagal memperbarui data peminjaman, mungkin sudah dikembalikan.");
    }

    // 2. Update (tambah) stok di tabel barang
    $sql_barang = "UPDATE barang SET stok = stok + ? WHERE id = ?";
    $stmt_barang = mysqli_prepare($conn, $sql_barang);
    mysqli_stmt_bind_param($stmt_barang, "ii", $qty_akhir, $barang_id);
    mysqli_stmt_execute($stmt_barang);
    
    // [PERBAIKAN] Cek apakah stok berhasil di-update
    if (mysqli_stmt_affected_rows($stmt_barang) == 0) {
        throw new Exception("Gagal memperbarui stok barang, ID barang tidak ditemukan.");
    }

    // Jika semua query berhasil, simpan perubahan
    mysqli_commit($conn);
    $_SESSION['success_message'] = "Barang berhasil dikembalikan dan stok telah diperbarui.";

} catch (Exception $e) {
    // Jika ada satu saja query yang gagal, batalkan semua perubahan
    mysqli_rollback($conn);
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

// Tutup statement dan koneksi
if (isset($stmt_peminjaman)) mysqli_stmt_close($stmt_peminjaman);
if (isset($stmt_barang)) mysqli_stmt_close($stmt_barang);
mysqli_close($conn);

// Alihkan pengguna kembali ke halaman sebelumnya
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>