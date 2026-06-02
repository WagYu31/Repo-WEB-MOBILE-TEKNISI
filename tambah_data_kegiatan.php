<?php
include "conn.php"; // Pastikan Anda telah menyertakan file koneksi database (conn.php) di sini

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap data dari formulir kedua
    $namaCustomer = $_POST["nama_customer"];
    $nomorCustomer = $_POST["nomor_customer"];
    $alamatCustomer = $_POST["alamat_customer"];
    $idCustomer = $_POST["id_customer"];
    $kegiatan = $_POST["kegiatan"];
    $keterangan = $_POST["keterangan"];
    $status = "Waiting";
    $req = "Guest";
    

    $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    // Query untuk memeriksa apakah kode_transaksi sudah ada dalam tabel kegiatan
    $query = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];
    
        // Jika kode_transaksi sudah ada dalam tabel, ulangi pembuatan kode_transaksi
        while ($count > 0) {
            $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
            // Kembali memeriksa kode_transaksi
            $query = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
            $result = mysqli_query($conn, $query);
    
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $count = $row['count'];
            } else {
                // Penanganan kesalahan
                die("Error in checking existing kode_transaksi: " . mysqli_error($conn));
            }
        }
    }

    
        $telepon = preg_replace("/[^0-9]/", "", $nomorCustomer);
        
        $telepon = "62" . $telepon;
        
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

    if (!empty($idCustomer)) {
        // Langkah kedua: Tambahkan data kegiatan ke dalam tabel kegiatan
        $queryTambahKegiatan = "INSERT INTO kegiatan (kode_transaksi, id_cust, jenis, keterangan, status, req_by) VALUES ('$kode_transaksi', $idCustomer, '$kegiatan', '$keterangan', '$status', '$req')";
        $resultTambahKegiatan = mysqli_query($conn, $queryTambahKegiatan);

        if ($resultTambahKegiatan) {
            $id_kegiatan = mysqli_insert_id($conn);
            
            $response = [
                "status" => "sukses",
                "kode" => $kode_transaksi,
                "id_kegiatan" => $id_kegiatan
            ];
                    
                    // Mengirim ID kegiatan sebagai respons dalam format JSON
                    header("Content-type: application/json");
                    echo json_encode($response);
        } else {
            $response = ["status" => "gagal", "error" => mysqli_error($conn)];
            // Mengembalikan respons dalam format JSON
            header("Content-type: application/json");
            echo json_encode($response);
        }
    } else {
        // Mengecek nomor telepon di basis data
        $query = "SELECT * FROM customer WHERE nomor_tlp = '$telepon'";
        $result = mysqli_query($conn, $query);
    
        if (mysqli_num_rows($result) > 0) {
            // Nomor telepon sudah ada dalam database, tampilkan pesan kesalahan
            $response = ["status" => "gagal", "message" => "Nomor telepon sudah terdaftar. Silakan gunakan nomor telepon lain atau lakukan tindakan lain sesuai kebutuhan Anda."];
            // Mengembalikan respons dalam format JSON
            header("Content-type: application/json");
            echo json_encode($response);
        } else {
            // Nomor telepon belum terdaftar, lakukan insert ke dalam tabel customer
            $insertCustomerQuery = "INSERT INTO customer (nama, nomor_tlp, alamat) VALUES ('$namaCustomer', '$telepon', '$alamatCustomer')";
            if (mysqli_query($conn, $insertCustomerQuery)) {
                // Dapatkan ID pelanggan baru
                $newCustomerId = mysqli_insert_id($conn);
    
                // Masukkan data ke dalam tabel kegiatan
                $sql = "INSERT INTO kegiatan (kode_transaksi, id_cust, jenis, keterangan, status, req_by)
                        VALUES ('$kode_transaksi', '$newCustomerId', '$kegiatan', '$keterangan', '$status', '$req')";
    
                if (mysqli_query($conn, $sql)) {
                    $id_kegiatan = mysqli_insert_id($conn);
                    $response = [
                        "status" => "sukses",
                        "kode" => $kode_transaksi,
                        "id_kegiatan" => $id_kegiatan
                    ];
                    // Mengirim ID kegiatan sebagai respons dalam format JSON
                    header("Content-type: application/json");
                    echo json_encode($response);
                } else {
                    $response = ["status" => "gagal", "message" => "Gagal menyimpan data kegiatan.", "error" => mysqli_error($conn)];
                    // Mengembalikan respons dalam format JSON
                    header("Content-type: application/json");
                    echo json_encode($response);
                }
            } else {
                $response = ["status" => "gagal", "message" => "Gagal menyimpan data customer.", "error" => mysqli_error($conn)];
                // Mengembalikan respons dalam format JSON
                header("Content-type: application/json");
                echo json_encode($response);
            }
        }
    }

    // // Mengembalikan respons dalam format JSON
    // header("Content-type: application/json");
    // echo json_encode($response);
} else {
    // Jika bukan permintaan POST, tampilkan pesan kesalahan
    echo "Metode yang diperbolehkan hanya POST";
}
?>
