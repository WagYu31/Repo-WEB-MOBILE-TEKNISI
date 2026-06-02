<?php
// Koneksi ke database
include 'conn.php'; // Pastikan file ini berisi koneksi database

if (isset($_GET['id'])) {
    // Ambil ID dari parameter URL
    $idData = mysqli_real_escape_string($conn, $_GET['id']);

    // Set timezone ke Jakarta
    date_default_timezone_set('Asia/Jakarta');

    // Timestamp saat ini
    $currentTimestamp = date('Y-m-d H:i:s');

    // Query untuk update kolom deleted_at
    $sqlDelete = "UPDATE data SET deleted_at = '$currentTimestamp' WHERE id = '$idData'";

    // Eksekusi query
    if (mysqli_query($conn, $sqlDelete)) {
        // Jika berhasil, alihkan ke tutorial.php dengan pesan sukses
        header("Location: tutorial.php?status=deleted");
        exit();
    } else {
        // Jika gagal, tampilkan pesan error
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request. No ID specified.";
}

// Tutup koneksi database
mysqli_close($conn);
?>
