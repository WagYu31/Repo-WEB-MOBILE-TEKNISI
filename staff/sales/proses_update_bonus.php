<?php
include "../conn.php";

// Pastikan bahwa nilai id_teknisi, kode_transaksi, nominal bonus, nominal denda, dan invoice telah diterima melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil nilai id_teknisi, kode_transaksi, nominal bonus, nominal denda, dan invoice dari $_POST
    $id_teknisi = $_POST['tekId'];
    $kode_transaksi = $_POST['kodeTran'];
    $nominal_bonus = $_POST['bonus'];
    $nominal_denda = $_POST['denda'];
    $invoice = $_POST['invoice'];
    $tgl_inv = date("Y-m-d");

    // Cek apakah invoice dalam tabel kegiatan null
    $cekInvQuery = "SELECT invoice FROM kegiatan WHERE kode_transaksi = ?";
    $cekInvStmt = mysqli_prepare($conn, $cekInvQuery);
    mysqli_stmt_bind_param($cekInvStmt, "s", $kode_transaksi);
    mysqli_stmt_execute($cekInvStmt);
    mysqli_stmt_store_result($cekInvStmt);

    $existingInvoice = null;

    if (mysqli_stmt_num_rows($cekInvStmt) > 0) {
        mysqli_stmt_bind_result($cekInvStmt, $existingInvoice);
        mysqli_stmt_fetch($cekInvStmt);
    }

    if (is_null($existingInvoice)) {
        // Invoice null, lakukan pembaruan langsung
        $sql = "UPDATE kegiatan SET bonus = ?, denda = ?, invoice = ?, tgl_inv = ? WHERE id_teknisi = ? AND kode_transaksi = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ddssss", $nominal_bonus, $nominal_denda, $invoice, $tgl_inv, $id_teknisi, $kode_transaksi);
        if (mysqli_stmt_execute($stmt)) {
            $sqlW = "UPDATE kegiatan SET invoice = ?, tgl_inv = ? WHERE kode_transaksi = ?";
            $stmt2 = mysqli_prepare($conn, $sqlW);
            mysqli_stmt_bind_param($stmt2, "sss", $invoice, $tgl_inv, $kode_transaksi);
            if (mysqli_stmt_execute($stmt2)) {
                echo "success";
            } else {
                echo "error";
            }
            mysqli_stmt_close($stmt2); // Tutup pernyataan
        } else {
            echo "error";
        }
        mysqli_stmt_close($stmt); // Tutup pernyataan
    } else {
        // Invoice tidak null, cek apakah sama dengan $invoice
        if ($existingInvoice == $invoice) {
            // Invoice sama, lakukan pembaruan
            $sql = "UPDATE kegiatan SET bonus = ?, denda = ? WHERE id_teknisi = ? AND kode_transaksi = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ddss", $nominal_bonus, $nominal_denda, $id_teknisi, $kode_transaksi);
            if (mysqli_stmt_execute($stmt)) {
                echo "success";
            } else {
                echo "error";
            }
            mysqli_stmt_close($stmt); // Tutup pernyataan
        } else {
            // Invoice tidak sesuai, kirim peringatan
            echo "warning: Nomor invoice tidak sesuai.";
        }
    }

    // Tutup pernyataan cek invoice
    mysqli_stmt_close($cekInvStmt);
    mysqli_close($conn);
} else {
    echo "error";
}
?>
