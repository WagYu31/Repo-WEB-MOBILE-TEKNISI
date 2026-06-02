<?php

include "../../conn.php";
// Pastikan hanya menjalankan proses jika ada pengiriman form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Periksa apakah file telah dipilih
    if (isset($_FILES["buktiPembayaran"]) && $_FILES["buktiPembayaran"]["error"] == UPLOAD_ERR_OK) {
        // Direktori upload file
        $uploadDir = "../../assets/img/uploads/";

        // Extensi file yang diizinkan
        $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        // Mendapatkan informasi file yang diupload
        $fileName = $_FILES['buktiPembayaran']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Memeriksa apakah ekstensi file diizinkan
        if (in_array($fileExt, $allowedExt)) {
            // Mendapatkan kode pembayaran dari form
            $kodePembayaran = $_POST['kodePembayaran'];

            // Generate nama file baru dengan format yang diinginkan
            $newFileName = $kodePembayaran . "_" . date('Ymd_His') . "." . $fileExt;

            // Path lengkap file yang akan diupload
            $uploadFile = $uploadDir . $newFileName;

            // Memindahkan file ke direktori tujuan
            if (move_uploaded_file($_FILES['buktiPembayaran']['tmp_name'], $uploadFile)) {
                // Update database dengan nama file baru
                $query = "UPDATE pembayaran SET bukti_pembayaran = ?, status = 'Pending' WHERE kode_pembayaran = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ss", $newFileName, $kodePembayaran);
                mysqli_stmt_execute($stmt);

                // Periksa apakah pembaharuan berhasil
                if (mysqli_affected_rows($conn) > 0) {
                    header("Location: detail_pembayaran.php?kode_pembayaran=".$kodePembayaran);
                    exit();
                } else {
                    echo "Gagal memperbarui bukti pembayaran.";
                }
            } else {
                echo "Gagal mengunggah file.";
            }
        } else {
            echo "Ekstensi file tidak diizinkan.";
        }
    } else {
        echo "Tidak ada file yang dipilih atau terjadi kesalahan saat mengunggah.";
    }
}
?>
