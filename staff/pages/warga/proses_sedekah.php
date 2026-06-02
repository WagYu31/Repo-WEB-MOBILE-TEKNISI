<?php
include "../../conn.php";

// Pastikan file ini dipanggil setelah formulir di-submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Lakukan validasi atau sanitasi data jika diperlukan
    $nominal = $_POST["nominal"];
    $keterangan = $_POST["keterangan"];
    $selectedBank = $_POST["selected_bank"];
    $id_warga = $_POST["id_warga"];

    // Proses upload bukti pembayaran
    $uploadDir = "../../assets/img/uploads/"; // Folder tempat menyimpan file
    $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $fileName = $_FILES['bukti_pembayaran']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($fileExt, $allowedExt)) {
        // Generate kode pembayaran (7 digit kombinasi angka dan huruf acak)
        $kode_pembayaran = substr(md5(uniqid(rand(), true)), 0, 7);

        // Generate nama file baru
        $newFileName = $id_warga . "_" . date('Ymd_His') . "_" . $kode_pembayaran . "." . $fileExt;
        $uploadFile = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $uploadFile)) {
            // File berhasil diupload, lanjutkan dengan proses pembayaran

            // Masukkan data ke tabel sedekah
            $tgl_sedekah = date('Y-m-d H:i:s');
            $status = "Pending";

            $queryInsert = "INSERT INTO sedekah (id_warga, kode_pembayaran, tgl_sedekah, jumlah, keterangan, bukti_pembayaran, id_bank, status)
                VALUES ($id_warga, '$kode_pembayaran', '$tgl_sedekah', '$nominal', '$keterangan', '$newFileName', '$selectedBank', '$status')";
            $resultInsert = mysqli_query($conn, $queryInsert);

            if ($resultInsert) {
                // Tambahkan logika sesuai dengan kebutuhan Anda
                // Misalnya, tampilkan pesan sukses atau redirect ke halaman lain
                header("Location: sedekah.php");
                exit();
            } else {
                // Jika terjadi kesalahan saat memasukkan data ke database
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            // Jika terjadi kesalahan saat upload file
            echo "Error: File tidak berhasil diupload.";
        }
    } else {
        // Jika format file tidak sesuai
        echo "Error: Format file tidak sesuai. Gunakan file gambar (jpg, jpeg, png, gif, webp).";
    }
} else {
    // Jika formulir tidak disubmit, mungkin ada aksi lain yang perlu dilakukan
    // Misalnya, redirect ke halaman formulir atau tampilkan pesan kesalahan
    header("Location: sedekah.php");
    exit();
}
?>
