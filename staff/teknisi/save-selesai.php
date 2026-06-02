<?php

include "../conn.php";

// Periksa apakah data yang diperlukan telah diterima
if (isset($_GET['latitude'], $_GET['longitude'], $_GET['id_kegiatan'], $_GET['file1'], $_GET['file2'], $_GET['file3'], $_GET['file4'], $_GET['file5'], $_GET['keterangan'], $_GET['solusi'], $_GET['permasalahan'])) {
    // Ambil data dari $_GET
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];
    $id_kegiatan = $_GET['id_kegiatan'];
    $file1 = $_GET['file1'];
    $file2 = $_GET['file2'];
    $file3 = $_GET['file3'];
    $file4 = $_GET['file4'];
    $file5 = $_GET['file5'];
    $keterangan = $_GET['keterangan'];
    $permasalahan = $_GET['permasalahan'];
    $solusi = $_GET['solusi'];
    $lokasiSelesai = $latitude . "," . $longitude;
    $currDT = date("Y-m-d H:i:s");

    echo $latitude . "<br>";
    echo $longitude . "<br>";
    echo $id_kegiatan . "<br>";
    echo $currDT . "<br>";
    echo $file1 . "<br>";
    echo $file2 . "<br>";
    echo $file3 . "<br>";
    echo $file4 . "<br>";
    echo $file5 . "<br>";

    $sql = "
        SELECT k.*, c.nama AS nama_customer, t.nama AS nama_teknisi 
        FROM kegiatan k
        JOIN customer c ON k.id_cust = c.id_cust
        JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        WHERE k.id_kegiatan = $id_kegiatan
    ";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $kodeTransaksi = $row["kode_transaksi"];
    $idTeknisi = $row["id_teknisi"];
    $namaTeknisi = $row["nama_teknisi"];
    $idCust = $row["id_cust"];
    $namaCustomer = $row["nama_customer"];
    $jenis = $row["jenis"];
    $tglRequest = $row["tgl_request"];
    $reqBy = $row["req_by"];

    $status = "Clear";
    echo $status . "<br>";

    echo $idTeknisi . "<br>";
    echo $jenis . "<br>";


    // Perbarui data kegiatan
    $sql = "UPDATE kegiatan SET tgl_selesai = '$currDT', lokasi_selesai = '$lokasiSelesai', gambar_finish_1 = '$file1', gambar_finish_2 = '$file2', gambar_finish_3 = '$file3', gambar_finish_4 = '$file4', gambar_finish_5 = '$file5', status = '$status', ket_finish = '$permasalahan', ket_finish_2 = '$solusi', ket_finish_3 = '$keterangan' WHERE id_kegiatan = '$id_kegiatan'";

    if (mysqli_query($conn, $sql)) {
        // Query untuk mendapatkan nomor telepon karyawan dengan id_karyawan 2 dan 16
        $token = "VA-ZCZegDvDFHfNq5f4R";
        $query_wa = "SELECT id_karyawan, no_tlp, nama FROM loewix WHERE id_karyawan IN (2, 16)";
        $result_wa = mysqli_query($conn, $query_wa);

        // Pesan WhatsApp yang ingin dikirim
        $message = "Kegiatan " . $jenis . " untuk *" . $namaCustomer . "* baru saja diselesaikan oleh *" . $namaTeknisi . "* , login dan cek https://jadwal.loewix.com untuk cek Laporan dan melanjutkan ke tahap selanjutnya. *Tetap Semangat!*";

        while ($row_wa = mysqli_fetch_assoc($result_wa)) {
            $id_karyawan = $row_wa['id_karyawan'];
            $no_wa = $row_wa['no_tlp'];
            $nama_karyawan = $row_wa['nama'];
            $formatted_wa = preg_replace('/^0/', '62', $no_wa); // Mengubah 0 awal menjadi 62

            // Kirim pesan jika karyawan sesuai dengan kondisi yang diberikan
            if (($id_karyawan == 2) || ($id_karyawan == 16 && $jenis == 'Survey') || ($nama_karyawan == $reqBy)) {
                // Kirim pesan WhatsApp
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.fonnte.com/send',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array(
                        'target' => $formatted_wa,
                        'message' => $message,
                        'countryCode' => '62', // Optional
                    ),
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: $token" // Ganti TOKEN dengan token sebenarnya
                    ),
                ));
                $response = curl_exec($curl);
                $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $error_msg = curl_error($curl);
                curl_close($curl);

                // Debugging: Tampilkan respons dan kode status HTTP
                echo "Response: $response<br>";
                echo "HTTP Code: $httpcode<br>";
                if ($error_msg) {
                    echo "CURL Error: $error_msg<br>";
                }
            }
        }

        // Alihkan ke halaman index.php setelah proses selesai
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Data yang diperlukan tidak diterima, tangani kesalahan di sini
    echo "Error: Data yang diperlukan tidak diterima.";
    echo mysqli_error($conn);
}
