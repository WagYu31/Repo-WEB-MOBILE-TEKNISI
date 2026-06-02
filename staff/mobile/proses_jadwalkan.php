<?php
session_start();
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $tanggal = $_POST["tanggal"];
    $jam = $_POST["jam"];
    $selectedTechnicians = $_POST["teknisi"];
    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $now = date('Y-m-d H:i:s'); // Menyimpan date time saat ini ke variabel $now

    if (empty($kegiatanId) || empty($tanggal) || empty($jam) || empty($selectedTechnicians)) {
        echo "Invalid input data.";
        exit;
    }

    $tgl_request = $tanggal . ' ' . $jam;
    $stat = "dijadwalkan";

    // Update status dan jadwal di tabel kegiatan
    $updateQuery = "UPDATE kegiatan SET status = ?, jadwal = ?, updated_at = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    if (!$stmt) {
        echo "Failed to prepare statement: " . mysqli_error($conn);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "sssi", $stat, $tgl_request, $now, $kegiatanId);
    if (!mysqli_stmt_execute($stmt)) {
        echo "Failed to update kegiatan: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Ambil nilai kode dari tabel kegiatan
    $getKodeQuery = "SELECT kode FROM kegiatan WHERE id = ?";
    $stmtKode = mysqli_prepare($conn, $getKodeQuery);
    if (!$stmtKode) {
        echo "Failed to prepare kode statement: " . mysqli_error($conn);
        exit;
    }

    mysqli_stmt_bind_param($stmtKode, "i", $kegiatanId);
    mysqli_stmt_execute($stmtKode);
    mysqli_stmt_bind_result($stmtKode, $kodeKegiatan);
    mysqli_stmt_fetch($stmtKode);
    mysqli_stmt_close($stmtKode);

    // Insert teknisi ke tabel team_kegiatan dengan kode dari tabel kegiatan
    $insertQuery = "INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsert = mysqli_prepare($conn, $insertQuery);
    if (!$stmtInsert) {
        echo "Failed to prepare insert statement: " . mysqli_error($conn);
        exit;
    }

    foreach ($selectedTechnicians as $teknisiId) {
        // Ambil nama teknisi
        $getTeknisiQuery = "SELECT nama FROM teknisi WHERE id = ?";
        $stmtTek = mysqli_prepare($conn, $getTeknisiQuery);
        if (!$stmtTek) {
            echo "Failed to prepare teknisi statement: " . mysqli_error($conn);
            exit;
        }

        mysqli_stmt_bind_param($stmtTek, "i", $teknisiId);
        mysqli_stmt_execute($stmtTek);
        mysqli_stmt_bind_result($stmtTek, $namaTeknisi);
        mysqli_stmt_fetch($stmtTek);
        mysqli_stmt_close($stmtTek);

        // Masukkan data ke dalam team_kegiatan
        mysqli_stmt_bind_param($stmtInsert, "iissss", $kegiatanId, $teknisiId, $namaTeknisi, $kodeKegiatan, $now, $now);
        if (!mysqli_stmt_execute($stmtInsert)) {
            echo "Failed to insert teknisi: " . mysqli_stmt_error($stmtInsert);
            mysqli_stmt_close($stmtInsert);
            exit;
        }
    }

    mysqli_stmt_close($stmtInsert);
    echo "success"; // Response to indicate success
} else {
    echo "Invalid request method.";
}

mysqli_close($conn);
?>
