<?php
include "../../conn.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $id_warga = $_POST["id_warga"];
    $id_tagihanArr = explode(',', $_POST['id_tagihan']);
    $selected_bank = $_POST["selected_bank"];

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
            $tgl_bayar = date('Y-m-d H:i:s');
            $jumlah = 0;

            foreach ($id_tagihanArr as $id_tagihan) {
                // Hitung total jumlah tagihan
                $queryTagihan = "SELECT jumlah FROM tagihan WHERE id_tagihan = ?";
                $stmtTagihan = mysqli_prepare($conn, $queryTagihan);
                mysqli_stmt_bind_param($stmtTagihan, 'i', $id_tagihan);
                mysqli_stmt_execute($stmtTagihan);
                $resultTagihan = mysqli_stmt_get_result($stmtTagihan);
                $rowTagihan = mysqli_fetch_assoc($resultTagihan);
                $jumlah = $rowTagihan['jumlah'];

            // Simpan data pembayaran ke tabel pembayaran
            $status = "Pending";
            $queryBayar = "INSERT INTO pembayaran (id_warga, id_tagihan, bukti_pembayaran, tgl_bayar, id_bank, jumlah, kode_pembayaran, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtBayar = mysqli_prepare($conn, $queryBayar);
            mysqli_stmt_bind_param($stmtBayar, 'iissssss', $id_warga, $id_tagihan, $newFileName, $tgl_bayar, $selected_bank, $jumlah, $kode_pembayaran, $status);
            mysqli_stmt_execute($stmtBayar);

            }
                header("Location: tagihan-warga.php");
                exit();
        } else {
            echo "Error in uploading the file.";
        }
    } else {
        echo "Invalid file format. Please upload a valid image file.";
    }
}
?>
