<?php
include "conn.php";

if (isset($_GET['kodeTransaksi'])) {
    $kode = $_GET['kodeTransaksi'];
    
    $sel = "SELECT * FROM kegiatan WHERE kode_transaksi = '$kode'";
    $resSel = mysqli_query($conn, $sel);
    while($data = mysqli_fetch_assoc($resSel)){
        $id_kegiatan = $data["id_kegiatan"];
        $delKeg = "DELETE FROM reschedule WHERE id_kegiatan = $id_kegiatan";
        if(mysqli_query($conn, $delKeg)){
            // Query DELETE untuk menghapus kegiatan berdasarkan id_kegiatan
            $deleteQuery = "DELETE FROM kegiatan WHERE kode_transaksi = '$kode'";
        
            if (mysqli_query($conn, $deleteQuery)) {
                $nama = "Herdina Panjaitan";
                $tgl_now = date("Y-m-d H:i:s");
                $hist = "Menghapus kegiatan $kode";
                $tipe = "Hapus";
                $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";
    
                if ($stmtHistory = mysqli_prepare($conn, $history)) {
                    mysqli_stmt_bind_param($stmtHistory, "ssss", $nama, $hist, $tipe, $tgl_now);
                    if (mysqli_stmt_execute($stmtHistory)) {
                        // Eksekusi query berhasil
                    } else {
                        // Terjadi kesalahan saat eksekusi query
                        echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmtHistory);
                }

                header("Location: index.php"); // Ganti 'previous_page.php' dengan halaman yang sesuai
                exit;
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
        else{
            echo "Error: " . mysqli_error($conn);
        }
    }
} else {
    echo "Parameter id_kegiatan tidak valid.";
}
?>
