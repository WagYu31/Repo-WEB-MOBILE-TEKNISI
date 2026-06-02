<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Periksa apakah ID customer sudah ada dalam parameter
if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    // ID customer dari parameter
    $id_customer = $_GET["id"];
    $nama = isset($_GET["nama"]) ? htmlspecialchars($_GET["nama"]) : '';

    include "conn.php";

    $sql_cust = "SELECT * FROM customer WHERE id = '$id_customer'";
    $sqlCust = mysqli_query($conn, $sql_cust);
    $rowCust = mysqli_fetch_assoc($sqlCust);
    $namaCustomer = $rowCust['nama'];

    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $deletedAt = date('Y-m-d H:i:s'); // Mendapatkan tanggal dan waktu saat ini

    $sql = "UPDATE customer SET deleted_at = ? WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $deletedAt, $id_customer);
        if ($stmt->execute()) {

            // $tgl_now = date("Y-m-d H:i:s");
            // $hist = "Menghapus customer $namaCustomer";
            // $tipe = "Hapus";
            // $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";

            // if ($stmtHistory = mysqli_prepare($conn, $history)) {
            //     mysqli_stmt_bind_param($stmtHistory, "ssss", $nama, $hist, $tipe, $tgl_now);
            //     if (mysqli_stmt_execute($stmtHistory)) {
            //     } else {
            //         echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
            //     }
            //     mysqli_stmt_close($stmtHistory);
            // }
            header("location: customer.php");
        } else {
            echo "Error saat menghapus data: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error saat mempersiapkan pernyataan: " . $conn->error;
    }

    $conn->close();
} else {
    // Redirect ke halaman data customer jika ID tidak valid
    header("location: customer.php");
}
