<?php
include 'conn.php';
include "session.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nama_barang = $_POST['nama_barang'];
    $deskripsi = $_POST['deskripsi'];
    $stok = $_POST['stok'];
    $old_image = $_POST['old_image'];
    $image_name = $old_image; // Default: gunakan gambar lama

    // Cek apakah ada file gambar baru yang di-upload
    if (isset($_FILES['image_barang']) && $_FILES['image_barang']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Buat nama file baru dengan format yang sama
        $extension = pathinfo($_FILES['image_barang']['name'], PATHINFO_EXTENSION);
        $safe_nama_barang = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '_', $nama_barang));
        $random_code = substr(bin2hex(random_bytes(2)), 0, 4);
        $new_filename = "{$id}_{$safe_nama_barang}_{$random_code}.{$extension}";
        $target_file = $target_dir . $new_filename;

        // Upload file baru
        if (move_uploaded_file($_FILES["image_barang"]["tmp_name"], $target_file)) {
            // Hapus file gambar lama jika ada
            if ($old_image && file_exists($target_dir . $old_image)) {
                unlink($target_dir . $old_image);
            }
            // Gunakan nama file yang baru
            $image_name = $new_filename;
        }
    }

    // Update data di database
    $stmt = $conn->prepare("UPDATE barang SET nama_barang = ?, image_barang = ?, deskripsi = ?, stok = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssii", $nama_barang, $image_name, $deskripsi, $stok, $id);

    if ($stmt->execute()) {
        header("Location: inventory.php?status=edit_sukses");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>