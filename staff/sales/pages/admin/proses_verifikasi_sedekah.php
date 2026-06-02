<?php
include "../../conn.php";
if (isset($_POST['verifikasi_submit'])) {
    // Ambil data dari formulir
    $kodePembayaran = $_POST['kode_pembayaran'];

    // Proses verifikasi di sini
    // Misalnya, jalankan query untuk mengubah status menjadi 'Verified'
    $queryUpdate = "UPDATE sedekah SET status = 'Verified' WHERE kode_pembayaran = ?";
    $stmtUpdate = mysqli_prepare($conn, $queryUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "s", $kodePembayaran);

    if (mysqli_stmt_execute($stmtUpdate)) {
        // Redirect ke halaman verifikasi_pembayaran.php
        header("Location: dashboard.php");
        exit();
    } else {
        // Handle error jika query tidak berhasil
        echo "Error: " . mysqli_error($conn);
        exit();
    }
} else {
    // Tampilkan halaman verifikasi_pembayaran.php
    // ... (kode untuk menampilkan informasi verifikasi)
}
?>
