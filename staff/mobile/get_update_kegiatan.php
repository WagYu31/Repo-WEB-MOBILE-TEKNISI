<?php

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_kegiatan"])) {
        $jenis_kegiatan = $_POST["jenis_kegiatan"];
        $keterangan = $_POST["keterangan"];
        $tanggal_request = $_POST["tgl_request_date"];
        $waktu_request = $_POST["tgl_request_time"];
        $customer_id = $_POST["customer_id"];
        $teknisiIds = $_POST["teknisi_ids"];

        // Mengambil nilai dari array pertama
        $teknisiIdsPertama = $teknisiIds[0];
        
        
        $tgl_request = $tanggal_request . " " . $waktu_request;
        
        // Menggabungkan teknisi yang dipilih menjadi satu string yang dipisahkan koma
        $teknisiIdsString = implode(',', $teknisiIds);
    
        // Query untuk mengupdate kegiatan berdasarkan ID kegiatan
        $update_sql = "UPDATE kegiatan SET kegiatan = '$jenis_kegiatan', keterangan = '$keterangan', jadwal = '$tgl_request', customer_id = $customer_id, id_teknisi = '$teknisiIdsPertama' WHERE id_kegiatan = $id_kegiatan";
        
        // Eksekusi query update
        $update_result = mysqli_query($conn, $update_sql);
        
        if ($update_result) {
            // Jika berhasil diubah, arahkan kembali ke halaman dashboard atau halaman lain yang sesuai
            // header("location: index.php");
            echo count($teknisiIds);
        } else {
            // Jika terjadi kesalahan, tampilkan pesan kesalahan
            echo "Gagal mengupdate kegiatan. Error: " . mysqli_error($conn);
            echo "ID Kegiatan: $id_kegiatan<br>";
            echo "Update SQL: $update_sql<br>";
        }
        
    


    }
    
?>