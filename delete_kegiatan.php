<?php
include "conn.php";

if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];

    // Query DELETE untuk menghapus kegiatan berdasarkan id_kegiatan
    $deleteQuery = "DELETE FROM kegiatan WHERE id_kegiatan = $id_kegiatan";

    if (mysqli_query($conn, $deleteQuery)) {
        // Kegiatan berhasil dihapus, redirect kembali ke halaman sebelumnya
        header("Location: index-sa.php"); // Ganti 'previous_page.php' dengan halaman yang sesuai
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Parameter id_kegiatan tidak valid.";
}
?>
