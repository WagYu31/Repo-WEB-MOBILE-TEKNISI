<?php
include "session.php";
include "conn.php";
include "get-user-data.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_list'])) {
    $kodeList = $_POST['kode_list']; // array of kode transaksi
    $redirect = $_POST['redirect'] ?? 'lap-kegiatan-selesai.php';
    
    if (!is_array($kodeList) || empty($kodeList)) {
        header("Location: $redirect?error=2");
        exit;
    }

    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    $user_display = (!empty($nmUser)) ? $nmUser : "System/Admin";
    $deleted_count = 0;

    foreach ($kodeList as $kodeTransaksi) {
        $kodeTransaksi = trim($kodeTransaksi);
        if (empty($kodeTransaksi)) continue;

        // Ambil nama customer untuk log
        $sqlCust = "SELECT c.nama FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.kode = ? LIMIT 1";
        $stmtCust = mysqli_prepare($conn, $sqlCust);
        mysqli_stmt_bind_param($stmtCust, "s", $kodeTransaksi);
        mysqli_stmt_execute($stmtCust);
        $resCust = mysqli_stmt_get_result($stmtCust);
        $dataCust = mysqli_fetch_assoc($resCust);
        $namaCustomer = $dataCust['nama'] ?? "Unknown";
        mysqli_stmt_close($stmtCust);

        // Soft delete
        $updateQuery = "UPDATE kegiatan SET deleted_at = ?, updated_at = ? WHERE kode = ?";
        if ($stmt = mysqli_prepare($conn, $updateQuery)) {
            mysqli_stmt_bind_param($stmt, "sss", $now, $now, $kodeTransaksi);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $deleted_count++;

                // Log
                $jenis_aksi = "Bulk Delete";
                $log_kode = $kodeTransaksi . " - " . $namaCustomer;
                $sql_log = "INSERT INTO log_kegiatan (jenis_aksi, nama_user, waktu, kode_transaksi) VALUES (?, ?, ?, ?)";
                if ($stmt_log = mysqli_prepare($conn, $sql_log)) {
                    mysqli_stmt_bind_param($stmt_log, "ssss", $jenis_aksi, $user_display, $now, $log_kode);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conn);
    header("Location: $redirect?success=3&count=$deleted_count");
    exit;
} else {
    header("Location: lap-kegiatan-selesai.php?error=3");
    exit;
}
?>
