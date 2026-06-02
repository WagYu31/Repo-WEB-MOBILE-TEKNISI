<?php

include "../conn.php";

// Periksa apakah data yang diperlukan telah diterima
if (isset($_GET['latitude'], $_GET['longitude'], $_GET['id_kegiatan'], $_GET['file1'], $_GET['file2'], $_GET['file3'], $_GET['pauseDate'], $_GET['pauseTime'], $_GET['keterangan'])) {
    // Ambil data dari $_GET
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $id_kegiatan = $_GET['id_kegiatan'];
    $file1 = $_GET['file1'];
    $file2 = $_GET['file2'];
    $file3 = $_GET['file3'];
    $pauseDate = $_GET['pauseDate'];
    $pauseTime = $_GET['pauseTime'];
    $keterangan = $_GET['keterangan'];
    $lokasiSelesai = $latitude . "," . $longitude;
    $pauseDateTime = $pauseDate . " " . $pauseTime;
    $currDT = date("Y-m-d H:i:s");

    echo $latitude . "<br>";
    echo $longitude . "<br>";
    echo $id_kegiatan . "<br>";
    echo $currDT . "<br>";
    echo $file1 . "<br>";
    echo $file2 . "<br>";
    echo $file3 . "<br>";
    echo $pauseDateTime . "<br>";

    $sql = "SELECT * FROM kegiatan WHERE id_kegiatan = $id_kegiatan";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $kodeTransaksi = $row["kode_transaksi"];
    $idTeknisi = $row["id_teknisi"];
    $idCust = $row["id_cust"];
    $jenis = $row["jenis"];
    $tglRequest = $row["tgl_request"];
    $reqBy = $row["req_by"];

    $statusN = "N";
    $status = "Pause";
    echo $status . "<br>";

    echo $idTeknisi . "<br>";
    echo $jenis . "<br>";

        // Perbarui data kegiatan
        $sql = "UPDATE kegiatan SET tgl_selesai = '$currDT', lokasi_selesai = '$lokasiSelesai', gambar_finish_1 = '$file1', gambar_finish_2 = '$file2', gambar_finish_3 = '$file3', status = '$statusN', ket_finish = '$keterangan' WHERE id_kegiatan = '$id_kegiatan'";
        if (mysqli_query($conn, $sql)) {
            // Jika update berhasil, lakukan insert baru
            $sqlIns = "INSERT INTO kegiatan (kode_transaksi, id_teknisi, id_cust, jenis, tgl_request, keterangan, status, req_by) VALUES
            ('$kodeTransaksi', '$idTeknisi', '$idCust', '$jenis', '$pauseDateTime', '$keterangan', '$status', '$reqBy')";
            if (mysqli_query($conn, $sqlIns)) {
                // Jika insert berhasil, arahkan ke halaman index.php
                header("location: index.php");
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    

} else {
    // Data yang diperlukan tidak diterima, tangani kesalahan di sini
    echo "Error: Data yang diperlukan tidak diterima.";
    echo mysqli_error($conn);
}
?>
