<?php

include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kodeTransaksi = $_POST["kode_transaksi"];
    $tanggal_pilihan = $_POST["tanggal_pilihan"];
    $waktu_pilihan = $_POST["waktu_pilihan"];
    $teknisi = $_POST["teknisi"];
    $datetime_pilih = $tanggal_pilihan . " " . $waktu_pilihan;

    // Menyiapkan data waktu untuk kueri SQL
    $datetime = date("Y-m-d H:i:s", strtotime($datetime_pilih));

    // Mendapatkan teknisi yang terkait dengan kode transaksi
    $sql = "SELECT k.*, t.nama AS nama_teknisi FROM kegiatan k INNER JOIN teknisi t ON k.id_teknisi = t.id_teknisi WHERE k.kode_transaksi = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $kodeTransaksi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!empty($teknisi)) {
        foreach ($teknisi as $teknisiName) {
            $found = false; // Flag untuk menandai apakah teknisi ditemukan dalam kegiatan atau tidak

            while ($row = mysqli_fetch_assoc($result)) {
                $tekID = $row["id_teknisi"];
                $nama_teknisi = $row["nama_teknisi"];
                $jenis = $row["jenis"];
                $keterangan = $row["keterangan"];
                $id_cust = $row["id_cust"];
                $status = $row["status"];
                $req_by = $row["req_by"];

                if ($teknisiName == $nama_teknisi) {
                    $found = true; // Set flag menjadi true karena teknisi ditemukan

                    $sqlUpd = "UPDATE kegiatan SET tgl_request = ? WHERE kode_transaksi = ? AND id_teknisi = ?";
                    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
                    mysqli_stmt_bind_param($stmtUpd, "sss", $datetime, $kodeTransaksi, $tekID);
                    $resUpd = mysqli_stmt_execute($stmtUpd);
                    if ($resUpd) {
                    } else {
                    }
                }
            }

            if (!$found) {

                $sql1 = "SELECT id_teknisi FROM teknisi WHERE nama = '$teknisiName'";
                $res1 = mysqli_query($conn, $sql1);
                $row1 = mysqli_fetch_assoc($res1);
                $id1 = $row1['id_teknisi'];

                $sqlAdd = "INSERT INTO kegiatan (kode_transaksi, tgl_request, id_teknisi, id_cust, jenis, status, req_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtAdd = mysqli_prepare($conn, $sqlAdd);
                mysqli_stmt_bind_param($stmtAdd, "sssssss", $kodeTransaksi, $datetime, $id1, $id_cust, $jenis, $status, $req_by);
                $resAdd = mysqli_stmt_execute($stmtAdd);
                if ($resAdd) {
                } else {
                }
            }

            // Mengembalikan pointer ke awal hasil kueri
            mysqli_data_seek($result, 0);
        }

        // Menampilkan nama teknisi yang tidak ada dalam daftar teknisi yang dipilih
        $found_teknisi = array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'nama_teknisi');
        $missing_teknisi = array_diff($found_teknisi, $teknisi);

        foreach ($missing_teknisi as $missing_name) {
            // Ambil id_teknisi dari tabel teknisi berdasarkan nama = $missing_name
            $sql2 = "SELECT id_teknisi FROM teknisi WHERE nama = '$missing_name'";
            $res2 = mysqli_query($conn, $sql2);
            $row2 = mysqli_fetch_assoc($res2);
            $id2 = $row2['id_teknisi'];
            $status = "Clear";

            // Cek jumlah data pada tabel kegiatan dengan kode_transaksi = $kodeTransaksi dan id_teknisi
            $sqlCount = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = ? AND id_teknisi = ?";
            $stmtCount = mysqli_prepare($conn, $sqlCount);
            mysqli_stmt_bind_param($stmtCount, "ss", $kodeTransaksi, $id2);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
            $countRow = mysqli_fetch_assoc($resultCount);

            if ($countRow['count'] == 1) {
                // Jika hanya ada 1 data, cek tgl_mulai pada kegiatan tersebut
                $sqlCheckDate = "SELECT tgl_mulai FROM kegiatan WHERE kode_transaksi = ? AND id_teknisi = ?";
                $stmtCheckDate = mysqli_prepare($conn, $sqlCheckDate);
                mysqli_stmt_bind_param($stmtCheckDate, "ss", $kodeTransaksi, $id2);
                mysqli_stmt_execute($stmtCheckDate);
                $resultDate = mysqli_stmt_get_result($stmtCheckDate);
                $rowDate = mysqli_fetch_assoc($resultDate);
                if ($rowDate['tgl_mulai'] === NULL || $rowDate['tgl_mulai'] === '0000-00-00 00:00:00') {
                    // Jika tgl_mulai adalah NULL atau 0000-00-00 00:00:00, maka update kegiatan
                    $sqlDelete = "DELETE FROM kegiatan WHERE kode_transaksi = ? AND id_teknisi = ?";
                    $stmtDelete = mysqli_prepare($conn, $sqlDelete);
                    mysqli_stmt_bind_param($stmtDelete, "ss", $kodeTransaksi, $id2);
                    $resDelete = mysqli_stmt_execute($stmtDelete);
                    if ($resDelete) {
                    } else {
                    }
                } else {
                    // Jika tgl_mulai tidak NULL atau 0000-00-00 00:00:00, maka hapus kegiatan
                    $sqlFinish = "UPDATE kegiatan SET tgl_request = ?, status = ?, tgl_selesai = NOW(), ket_finish = 'Diselesaikan oleh Admin' WHERE kode_transaksi = ? AND id_teknisi = ?";
                    $stmtFinish = mysqli_prepare($conn, $sqlFinish);
                    mysqli_stmt_bind_param($stmtFinish, "ssss", $datetime, $status, $kodeTransaksi, $id2);
                    $resUpd2 = mysqli_stmt_execute($stmtFinish);
                    if ($resUpd2) {
                    } else {
                    }
                }
            } else {
                // Jika data lebih dari 1, ambil data terakhir dan lakukan update
                $sqlFinish = "UPDATE kegiatan SET tgl_request = ?, status = ?, tgl_selesai = NOW(), ket_finish = 'Diselesaikan oleh Admin' WHERE id_kegiatan = (SELECT id_kegiatan FROM kegiatan WHERE kode_transaksi = ? AND id_teknisi = ? ORDER BY id_kegiatan DESC LIMIT 1)";
                $stmtFinish = mysqli_prepare($conn, $sqlFinish);
                mysqli_stmt_bind_param($stmtFinish, "ssss", $datetime, $status, $kodeTransaksi, $id2);
                $resUpd2 = mysqli_stmt_execute($stmtFinish);
                if ($resUpd2) {
                } else {
                }
            }
        }
    } else {
        echo "Tidak ada teknisi yang dipilih.";
    }
    
    header("location: index-sa.php");
    exit();
}
