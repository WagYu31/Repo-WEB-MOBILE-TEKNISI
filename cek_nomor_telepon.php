<?php
include "conn.php";
// Tangkap data dari form saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telepon = $_POST["nomorTelepon"];

    // Hilangkan karakter selain angka dari nomor telepon
    
    $telepon = preg_replace("/[^0-9]/", "", $telepon);
            // Ubah format nomor telepon
        if (substr($telepon, 0, 1) == "0") {
            // Jika angka pertama adalah 0, biarkan seperti itu
        } elseif (substr($telepon, 0, 2) == "62") {
            // Jika angka pertama adalah 62, ganti dengan 0
            $telepon = "0" . substr($telepon, 2);
        } elseif (substr($telepon, 0, 3) == "+62") {
            // Jika angka pertama adalah +62, ganti dengan 0
            $telepon = "0" . substr($telepon, 3);
        }elseif (substr($telepon, 0, 5) == "+6262") {
            // Jika angka pertama adalah +6262, ganti dengan 0
            $telepon = "0" . substr($telepon, 5);
        }elseif (substr($telepon, 0, 4) == "6262") {
            // Jika angka pertama adalah 6162, ganti dengan 0
            $telepon = "0" . substr($telepon, 4);
        }elseif (substr($telepon, 0, 6) == "+62+62") {
            // Jika angka pertama adalah +62+62, ganti dengan 0
            $telepon = "0" . substr($telepon, 6);
        } else {
            // Jika angka pertama bukan 0, 62, atau +62, tambahkan 0 di depannya
            $telepon = "0" . $telepon;
        }

    // Mengecek nomor telepon di basis data
        $query = "SELECT * FROM customer WHERE nomor_tlp = '$telepon'";
        $result = mysqli_query($conn, $query);
    
        if (mysqli_num_rows($result) > 0) {
            // Jika nomor telepon ditemukan di basis data, ambil data
            $row = mysqli_fetch_assoc($result);
    
            // Ambil nama customer dari hasil query
            $namaCustomer = $row["nama"];
            // $nomorCustomer = $row["nomor_tlp"];
            $nomorCustomer = substr($telepon, 1);
            $alamatCustomer = $row["alamat"];
            $idCustomer = $row["id_cust"];
    
            // Kembalikan data dalam format JSON
            $response = [
                "status" => "terdaftar",
                "namaCustomer" => $namaCustomer,
                "nomorCustomer" => $nomorCustomer,
                "alamatCustomer" => $alamatCustomer,
                "idCustomer" => $idCustomer
            ];
    
            header("Content-Type: application/json");
            echo json_encode($response);
        } else {
            // Nomor telepon tidak ditemukan di basis data
            $telepon = substr($telepon, 1);
            $response = [
                "status" => "tidak terdaftar",
                "nmr" => $telepon
            ];
        
            header("Content-Type: application/json");
            echo json_encode($response);
        }

    
    } else {
        // Jika bukan permintaan POST, tampilkan pesan kesalahan
        echo "Metode yang diperbolehkan hanya POST";
    }

?>