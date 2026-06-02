<?php
include "../conn.php";

// Pastikan bahwa nilai id_kegiatan, dan nominal bonus telah diterima melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil nilai id_kegiatan dari $_POST
    $id_kegiatan = $_POST['kegiatanId'];

    // Persiapkan pernyataan SQL dengan prepared statement
    $sql = "UPDATE kegiatan SET bonus = NULL, denda = NULL WHERE id_kegiatan = ?";

    // Persiapkan pernyataan
    $stmt = mysqli_prepare($conn, $sql);

    // Bind parameter ke pernyataan
    mysqli_stmt_bind_param($stmt, "d", $id_kegiatan);

    // Eksekusi pernyataan
    if (mysqli_stmt_execute($stmt)) {
        // Jika pembaruan berhasil, kirimkan respon "success" ke JavaScript
        echo "success";
    } else {
        // Jika terjadi kesalahan, kirimkan respon "error" ke JavaScript
        echo "error";
    }

    // Tutup pernyataan
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    // Jika id_kegiatan tidak diterima, kirimkan respon "error" ke JavaScript
    echo "error";
}
?>
