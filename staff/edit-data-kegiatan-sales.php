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
    $sql = "SELECT v.*, s.nama AS nama_sales FROM visits v INNER JOIN sales s ON v.id_sales = s.id_sales WHERE v.kode_transaksi = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $kodeTransaksi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!empty($teknisi)) {
        foreach ($teknisi as $teknisiName) {
            $found = false; // Flag untuk menandai apakah teknisi ditemukan dalam kegiatan atau tidak

            while ($row = mysqli_fetch_assoc($result)) {
                $tekID = $row["id_sales"];
                $nama_teknisi = $row["nama_sales"];
                $keterangan = $row["keterangan_visits"];
                $id_cust = $row["id_cust"];
                $status = $row["status"];

                if ($teknisiName == $nama_teknisi) {
                    $found = true; // Set flag menjadi true karena teknisi ditemukan

                    $sqlUpd = "UPDATE visits SET tgl_visits = ? WHERE kode_transaksi = ? AND id_sales = ?";
                    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
                    mysqli_stmt_bind_param($stmtUpd, "sss", $datetime, $kodeTransaksi, $tekID);
                    $resUpd = mysqli_stmt_execute($stmtUpd);
                    if ($resUpd) {
                    } else {
                    }
                }
            }

            if (!$found) {

                $sql1 = "SELECT id_sales FROM sales WHERE nama = '$teknisiName'";
                $res1 = mysqli_query($conn, $sql1);
                $row1 = mysqli_fetch_assoc($res1);
                $id1 = $row1['id_sales'];

                $sqlAdd = "INSERT INTO visits (kode_transaksi, tgl_visits, id_sales, id_cust, status) VALUES (?, ?, ?, ?, ?)";
                $stmtAdd = mysqli_prepare($conn, $sqlAdd);
                mysqli_stmt_bind_param($stmtAdd, "sssss", $kodeTransaksi, $datetime, $id1, $id_cust, $status);
                $resAdd = mysqli_stmt_execute($stmtAdd);
                if ($resAdd) {
                } else {
                }
            }

            // Mengembalikan pointer ke awal hasil kueri
            mysqli_data_seek($result, 0);
        }

        // Menampilkan nama teknisi yang tidak ada dalam daftar teknisi yang dipilih
        $found_teknisi = array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'nama_sales');
        $missing_teknisi = array_diff($found_teknisi, $teknisi);

        foreach ($missing_teknisi as $missing_name) {
            // Ambil id_teknisi dari tabel teknisi berdasarkan nama = $missing_name
            $sql2 = "SELECT id_sales FROM sales WHERE nama = '$missing_name'";
            $res2 = mysqli_query($conn, $sql2);
            $row2 = mysqli_fetch_assoc($res2);
            $id2 = $row2['id_sales'];
            $status = "clear";

            // Cek jumlah data pada tabel kegiatan dengan kode_transaksi = $kodeTransaksi dan id_teknisi
            $sqlCount = "SELECT COUNT(*) AS count FROM visits WHERE kode_transaksi = ? AND id_sales = ?";
            $stmtCount = mysqli_prepare($conn, $sqlCount);
            mysqli_stmt_bind_param($stmtCount, "ss", $kodeTransaksi, $id2);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
            $countRow = mysqli_fetch_assoc($resultCount);

            if ($countRow['count'] == 1) {
                // Jika hanya ada 1 data, cek tgl_mulai pada kegiatan tersebut
                $sqlCheckDate = "SELECT tgl_mulai FROM visits WHERE kode_transaksi = ? AND id_sales = ?";
                $stmtCheckDate = mysqli_prepare($conn, $sqlCheckDate);
                mysqli_stmt_bind_param($stmtCheckDate, "ss", $kodeTransaksi, $id2);
                mysqli_stmt_execute($stmtCheckDate);
                $resultDate = mysqli_stmt_get_result($stmtCheckDate);
                $rowDate = mysqli_fetch_assoc($resultDate);
                if ($rowDate['tgl_mulai'] === NULL || $rowDate['tgl_mulai'] === '0000-00-00 00:00:00') {
                    // Jika tgl_mulai adalah NULL atau 0000-00-00 00:00:00, maka update kegiatan
                    $sqlDelete = "DELETE FROM visits WHERE kode_transaksi = ? AND id_sales = ?";
                    $stmtDelete = mysqli_prepare($conn, $sqlDelete);
                    mysqli_stmt_bind_param($stmtDelete, "ss", $kodeTransaksi, $id2);
                    $resDelete = mysqli_stmt_execute($stmtDelete);
                    if ($resDelete) {
                    } else {
                    }
                } else {
                    // Jika tgl_mulai tidak NULL atau 0000-00-00 00:00:00, maka hapus kegiatan
                    $sqlFinish = "UPDATE visits SET tgl_visits = ?, status = ?, tgl_selesai = NOW() WHERE kode_transaksi = ? AND id_sales = ?";
                    $stmtFinish = mysqli_prepare($conn, $sqlFinish);
                    mysqli_stmt_bind_param($stmtFinish, "ssss", $datetime, $status, $kodeTransaksi, $id2);
                    $resUpd2 = mysqli_stmt_execute($stmtFinish);
                    if ($resUpd2) {
                    } else {
                    }
                }
            } else {
                // Jika data lebih dari 1, ambil data terakhir dan lakukan update
                $sqlFinish = "UPDATE visits SET tgl_visits = ?, status = ?, tgl_selesai = NOW() WHERE id_kegiatan = (SELECT id_visits FROM visits WHERE kode_transaksi = ? AND id_sales = ? ORDER BY id_visits DESC LIMIT 1)";
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
    
    header("location: dashboard-sales.php");
    exit();
}
