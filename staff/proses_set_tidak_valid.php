<?php
include "conn.php";
include "session.php";

// Periksa apakah 'kode' ada di URL
if (isset($_GET['kode']) && !empty($_GET['kode'])) {
    $kode = $_GET['kode'];

    // Siapkan query UPDATE yang aman
    $updateQuery = "UPDATE kegiatan SET paid = 'n/a' WHERE kode = ?";
    $stmt = $conn->prepare($updateQuery);
    
    if ($stmt) {
        $stmt->bind_param("s", $kode);
        
        // Jalankan query
        if ($stmt->execute()) {
            // Jika berhasil, arahkan kembali ke halaman laporan dengan notifikasi sukses
            header("Location: lap-kegiatan.php?success=na_updated");
        } else {
            // Jika gagal, arahkan kembali dengan notifikasi error
            header("Location: lap-kegiatan.php?error=1");
        }
        $stmt->close();
    } else {
        // Gagal menyiapkan statement
        header("Location: lap-kegiatan.php?error=1");
    }
    
    $conn->close();
} else {
    // Jika 'kode' tidak ada di URL
    header("Location: lap-kegiatan.php?error=3");
}
exit();
?>