<?php
include "session.php";
include "conn.php";
include "get-user-data.php";

if (isset($_GET['kode'])) {
    $kodeTransaksi = $_GET['kode'];
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');

    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    $sqlCust = "SELECT c.nama FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.kode = ?";
    $stmtCust = mysqli_prepare($conn, $sqlCust);
    mysqli_stmt_bind_param($stmtCust, "s", $kodeTransaksi);
    mysqli_stmt_execute($stmtCust);
    $resCust = mysqli_stmt_get_result($stmtCust);
    $dataCust = mysqli_fetch_assoc($resCust);
    $namaCustomer = $dataCust['nama'] ?? "Unknown";

    $updateQuery = "UPDATE kegiatan SET deleted_at = ?, updated_at = ? WHERE kode = ?";
    if ($stmt = mysqli_prepare($conn, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, "sss", $now, $now, $kodeTransaksi);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $user_display = (!empty($nmUser)) ? $nmUser : "System/Admin";
                $jenis_aksi = "Delete";
                $log_kode = $kodeTransaksi . " - " . $namaCustomer;
                
                $sql_log = "INSERT INTO log_kegiatan (jenis_aksi, nama_user, waktu, kode_transaksi) VALUES (?, ?, ?, ?)";
                if ($stmt_log = mysqli_prepare($conn, $sql_log)) {
                    mysqli_stmt_bind_param($stmt_log, "ssss", $jenis_aksi, $user_display, $now, $log_kode);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }

                header("Location: index-sa.php");
                exit;
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>