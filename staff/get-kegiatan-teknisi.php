<?php
include "conn.php"; // Sertakan file koneksi database Anda di sini

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["tanggal"])) {
        $selectedDate = $_GET["tanggal"];
        $sts = "Clear";

        // Lakukan query untuk mendapatkan daftar teknisi yang memiliki kegiatan pada tanggal tertentu
        $query = "SELECT k.id_teknisi, k.tgl_request, k.status, k.jenis, GROUP_CONCAT(t.nama) AS nama_teknisi 
                  FROM kegiatan k
                  LEFT JOIN teknisi t ON FIND_IN_SET(t.id_teknisi, k.id_teknisi) > 0
                  WHERE DATE(k.tgl_request) = '$selectedDate' AND k.status != '$sts'
                  GROUP BY k.id_teknisi, k.tgl_request, k.status, k.jenis";
        $result = mysqli_query($conn, $query);

        if ($result) {
            $teknisiData = array();

            while ($row = mysqli_fetch_assoc($result)) {
                // Masukkan informasi teknisi dan tgl_request ke dalam array
                $teknisiInfo = array(
                    "id_teknisi" => $row["id_teknisi"],
                    "tgl_request" => $row["tgl_request"],
                    "status" => $row["status"],
                    "jenis" => $row["jenis"],
                    "nama_teknisi" => $row["nama_teknisi"] // Tambahkan nama teknisi dalam bentuk string
                );
                $teknisiData[] = $teknisiInfo;
            }

            // Mengembalikan data dalam format JSON
            echo json_encode($teknisiData);
        } else {
            echo "Terjadi kesalahan dalam mengambil data kegiatan teknisi.";
        }
    } else {
        echo "Parameter tanggal tidak ditemukan.";
    }
} else {
    echo "Metode yang diperbolehkan hanya GET.";
}
?>
