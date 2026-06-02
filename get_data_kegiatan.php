<?php

    // Query untuk mengambil data kegiatan berdasarkan ID kegiatan
    $sql = "SELECT k.*, t.nama AS nama_teknisi, c.nama AS nama_customer 
            FROM kegiatan k
            LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
            LEFT JOIN customer c ON k.id_cust = c.id_cust
            WHERE k.id_kegiatan = $id_kegiatan";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id_team = $row["id_team"];
        $kode_transaksi = $row["kode_transaksi"];
        $teknisiIds = explode(',', $row["id_teknisi"]);
        $customer_id = $row["id_cust"];
        $jenis_kegiatan = $row["jenis"];
        $tgl_request = $row["tgl_request"];
        $tgl_reschedule = $row["tgl_reschedule"];
        $keterangan = $row["keterangan"];
        $tgl_mulai = $row["tgl_mulai"];
        $lokasi_mulai = $row["lokasi_mulai"];
        $gambar_start = $row["gambar_start"];
        $ket_start = $row["ket_start"];
        $tgl_selesai = $row["tgl_selesai"];
        $lokasi_selesai = $row["lokasi_selesai"];
        $gambar_finish = $row["gambar_finish"];
        $ket_finish = $row["ket_finish"];
        $status = $row["status"];
        $req_by = $row["req_by"];
        
        // Anda bisa menambahkan kolom-kolom lain yang ingin diubah di sini
    } else {
        echo "Data kegiatan tidak ditemukan.";
    }
    
    include "get_data_teknisi.php";
    
?>