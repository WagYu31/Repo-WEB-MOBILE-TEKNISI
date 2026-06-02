<?php
include "../conn.php";

// Cek apakah ada data yang dikirimkan melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data yang dikirimkan melalui form
    $nama_produk = $_POST["nama_produk"];

    // Cek apakah kode produk atau nama produk sudah terdaftar dalam database
    $check_sql = "SELECT * FROM produk WHERE kode_produk = '$nama_produk'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo "Kode sudah terdaftar.";
    } else {
        // Pastikan ada file yang diunggah
        if (isset($_FILES["photo_produk"])) {
            // Ambil info file
            $file_name = $_FILES["photo_produk"]["name"];
            $file_tmp = $_FILES["photo_produk"]["tmp_name"];
            $file_size = $_FILES["photo_produk"]["size"];
            $file_error = $_FILES["photo_produk"]["error"];

            // Periksa apakah ada error saat mengunggah file
            if ($file_error === 0) {
                // Tentukan lokasi penyimpanan dan nama file baru
                $lokasi_file = "assets/img/uploads/" . $nama_produk . "." . pathinfo($file_name, PATHINFO_EXTENSION);

                // Pindahkan file yang diunggah ke lokasi penyimpanan baru
                if (move_uploaded_file($file_tmp, $lokasi_file)) {
                    // Ambil data lain dari form
                    $jenis = $_POST["jenis"];
                    $kerusakan_cover = $_POST["kerusakan_cover"];
                    $kerusakan_tidak_cover = $_POST["kerusakan_tidak_cover"];
                    $masa_garansi = $_POST["masa_garansi"];

                    // Query untuk menyimpan data ke dalam tabel produk
                    $sql = "INSERT INTO produk (kode_produk, jenis, gambar, lama_garansi, garansi_oke, garansi_notOke)
                            VALUES ('$nama_produk', '$jenis', '$lokasi_file', '$masa_garansi', '$kerusakan_cover', '$kerusakan_tidak_cover')";

                    if ($conn->query($sql) === TRUE) {
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                } else {
                    echo "Gagal menyimpan file.";
                }
            } else {
                echo "Error saat mengunggah file.";
            }
        } else {
            echo "File tidak ditemukan.";
        }
    }
} else {
    // Jika halaman diakses langsung, tampilkan pesan kesalahan
    echo "Akses tidak sah";
}

// Menutup koneksi
$conn->close();
?>