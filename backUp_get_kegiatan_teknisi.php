<?php
include "conn.php"; // Sertakan file koneksi database Anda di sini

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["tanggal"])) {
        $selectedDate = $_GET["tanggal"];

        // Lakukan query untuk mengambil data kegiatan berdasarkan tanggal
        $query = "SELECT k.id_teknisi, t.nama AS nama_teknisi, k.jenis, k.tgl_request, k.status
                  FROM kegiatan k
                  LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                  WHERE DATE(k.tgl_request) = '$selectedDate'";

        $result = mysqli_query($conn, $query);

        if ($result) {
            $kegiatanData = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $kegiatanInfo = array(
                    "id_teknisi" => $row["id_teknisi"],
                    "nama_teknisi" => $row["nama_teknisi"],
                    "jenis" => $row["jenis"],
                    "tgl_request" => $row["tgl_request"],
                    "status" => $row["status"]
                );
                $kegiatanData[] = $kegiatanInfo;
            }

            // Mengembalikan data dalam format JSON
            echo json_encode($kegiatanData);
        } else {
            echo "Terjadi kesalahan dalam mengambil data kegiatan.";
        }
    } else {
        echo "Parameter tanggal tidak ditemukan.";
    }
} else {
    echo "Metode yang diperbolehkan hanya GET.";
}
?>