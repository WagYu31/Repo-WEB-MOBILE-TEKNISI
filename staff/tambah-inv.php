<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan semua data yang dibutuhkan telah dikirim
    if (isset($_POST['invoice'], $_POST['dateInv'], $_POST['nominal'], $_POST['kodeTran'], $_POST['jumlahData'], $_POST['jumlahDataAll'])) {
        
        // Ambil data yang dikirimkan melalui formulir
        $invoice = $_POST['invoice'];
        $dateInv = date('Y-m-d', strtotime($_POST['dateInv']));
        $nominal = $_POST['nominal'];
        $nominal_without_rp = str_replace('Rp ', '', $nominal);
        $nominal_without_format = str_replace(['.', ','], '', $nominal_without_rp);

        $kodeTran = $_POST['kodeTran'];
        $jumlahData = $_POST['jumlahData'];
        $jumlahDataAll = $_POST['jumlahDataAll'];
        
        $perOrang = $nominal_without_format / $jumlahData;

        // Pertama, set bonus, invoice, dan tgl_inv menjadi NULL
        $sql_reset = "UPDATE kegiatan SET bonus = NULL, invoice = NULL, tgl_inv = NULL WHERE kode_transaksi = ?";
        $stmt_reset = mysqli_prepare($conn, $sql_reset);
        
        if ($stmt_reset) {
            mysqli_stmt_bind_param($stmt_reset, "s", $kodeTran);
            $result_reset = mysqli_stmt_execute($stmt_reset);

            if ($result_reset) {
                // Setelah reset, lakukan update yang baru
                $sql = "UPDATE kegiatan SET bonus = ?, invoice = ?, tgl_inv = ? WHERE kode_transaksi = ? AND (tgl_selesai IS NOT NULL AND tgl_selesai != '0000-00-00 00:00:00' AND tgl_mulai IS NOT NULL AND tgl_mulai != '0000-00-00 00:00:00')";
                $stmt = mysqli_prepare($conn, $sql);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "dsss", $perOrang, $invoice, $dateInv, $kodeTran);
                    $result = mysqli_stmt_execute($stmt);

                    if ($result) {
                        header("location: laporan-kegiatan.php?success=1");
                        exit; 
                    } else {
                        header("location: laporan-kegiatan.php?error=1");
                        exit;
                    }
                } else {
                    header("location: laporan-kegiatan.php?error=4");
                    exit;
                }
            } else {
                header("location: laporan-kegiatan.php?error=5");
                exit;
            }
        } else {
            header("location: laporan-kegiatan.php?error=6");
            exit;
        }
    } else {
        header("location: laporan-kegiatan.php?error=2");
        exit;
    }
} else {
    header("location: laporan-kegiatan.php?error=3");
    exit;
}

?>