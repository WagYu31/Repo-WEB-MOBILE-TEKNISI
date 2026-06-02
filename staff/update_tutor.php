<?php
// Koneksi ke database
include 'conn.php';

// Fungsi untuk membuat kode acak
function generateRandomCode($length = 7) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['idData'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Folder target untuk menyimpan file
    $targetDir = "../../jadwal-3/api/storage/app/pdf/";

    // Proses media_1
    $media1 = $_FILES['media_1']['name'];
    $media1Path = null;
    if ($media1) {
        $extension1 = pathinfo($media1, PATHINFO_EXTENSION);
        $randomName1 = generateRandomCode() . "_media_1." . $extension1;
        $media1Path = $targetDir . $randomName1;
        if (!move_uploaded_file($_FILES['media_1']['tmp_name'], $media1Path)) {
            echo "<script>alert('Gagal mengunggah file Media 1'); window.history.back();</script>";
            exit;
        }
    }

    // Proses media_2
    $media2 = $_FILES['media_2']['name'];
    $media2Path = null;
    if ($media2) {
        $extension2 = pathinfo($media2, PATHINFO_EXTENSION);
        $randomName2 = generateRandomCode() . "_media_2." . $extension2;
        $media2Path = $targetDir . $randomName2;
        if (!move_uploaded_file($_FILES['media_2']['tmp_name'], $media2Path)) {
            echo "<script>alert('Gagal mengunggah file Media 2'); window.history.back();</script>";
            exit;
        }
    }

    // Query update
    $sql = "UPDATE data SET title = '$title', description = '$description', updated_at = NOW()";

    if ($media1Path) {
        $sql .= ", media_1 = '$randomName1'";
    }

    if ($media2Path) {
        $sql .= ", media_2 = '$randomName2'";
    }

    $sql .= " WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Data berhasil diperbarui'); window.location.href = 'tutorial.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Akses tidak diizinkan!'); window.history.back();</script>";
}
