<?php
session_start();

// ── Auth check ──
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

include "conn.php";

if (isset($_GET['id_kegiatan']) && is_numeric($_GET['id_kegiatan'])) {
    $id_kegiatan = intval($_GET['id_kegiatan']);

    // Prepared statement — aman dari SQL Injection
    $stmt = mysqli_prepare($conn, "DELETE FROM kegiatan WHERE id_kegiatan = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_kegiatan);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: index-sa.php");
        exit;
    } else {
        mysqli_stmt_close($stmt);
        echo "Terjadi kesalahan. Silakan coba lagi.";
    }
} else {
    echo "Parameter tidak valid.";
}

mysqli_close($conn);
?>
