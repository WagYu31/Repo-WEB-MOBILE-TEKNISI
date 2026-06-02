<?php
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_kegiatan"])) {
    
    
    $kode_transaksi   = mysqli_real_escape_string($conn, $_POST["kode_transaksi"]);
    $jenis_kegiatan   = mysqli_real_escape_string($conn, $_POST["kegiatan_pilihan"]);
    $tanggal_pilihan  = mysqli_real_escape_string($conn, $_POST["tanggal_pilihan"]);
    $waktu_pilihan    = mysqli_real_escape_string($conn, $_POST["waktu_pilihan"]);
    $tgl_request      = $tanggal_pilihan . " " . $waktu_pilihan;
    
    $sqlCek = "SELECT c.nama FROM kegiatan k 
               LEFT JOIN customer c ON k.customer_id = c.id 
               WHERE k.kode = '$kode_transaksi' LIMIT 1";
    $queryCek = mysqli_query($conn, $sqlCek);
    $dataCek = mysqli_fetch_assoc($queryCek);
    $nama_customer = isset($dataCek['nama']) ? $dataCek['nama'] : "Customer Tidak Ditemukan";

    // 2. Query Update Kegiatan
    $update_sql = "UPDATE kegiatan SET 
                    kegiatan = '$jenis_kegiatan', 
                    jadwal = '$tgl_request' 
                  WHERE kode = '$kode_transaksi'";
    
    $update_result = mysqli_query($conn, $update_sql);
    
    if ($update_result) {
        
        $waktu_log      = date("Y-m-d H:i:s");
        $halaman        = "Edit Kegiatan";
        $metode         = "Edit";
        $durasi         = 0;
        
        $user_display   = (!empty($nmUser)) ? $nmUser : "System/Admin";
        
        $isi_pesan = "$user_display telah melakukan edit pada kegiatan [$kode_transaksi] dengan nama customer $nama_customer";
        
        $pesan_manusia_escaped = mysqli_real_escape_string($conn, $isi_pesan);

        $sql_log = "INSERT INTO log_aktivitas (waktu, ip_pengunjung, halaman, metode, durasi, pesan_manusia) 
                    VALUES ('$waktu_log', '-', '$halaman', '$metode', '$durasi', '$pesan_manusia_escaped')";
        
        if (!mysqli_query($conn, $sql_log)) {
            echo "Error Log: " . mysqli_error($conn);
            exit;
        }

        echo "<script>
                alert('Data berhasil diupdate dan dicatat di log!');
                window.location.href='edit_kegiatan.php?kode_transaksi=$kode_transaksi';
              </script>";
    } else {
        echo "Gagal mengupdate kegiatan. Error: " . mysqli_error($conn);
    }
}
?>