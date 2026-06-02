<?php
// Konfigurasi koneksi database
include "conn.php";
include "session.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang = $_POST['nama_barang'];
    $deskripsi = $_POST['deskripsi'];
    $stok = $_POST['stok'];

    // 1. INSERT data teks terlebih dahulu dengan image_barang diisi NULL
    $stmt = $conn->prepare("INSERT INTO barang (nama_barang, deskripsi, stok, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssi", $nama_barang, $deskripsi, $stok);
    
    if (!$stmt->execute()) {
        die("Error saat menyimpan data awal: " . $stmt->error);
    }

    // 2. Dapatkan ID dari barang yang baru saja dimasukkan
    $last_id = $conn->insert_id;
    $stmt->close();

    // 3. Proses gambar HANYA JIKA ada file yang di-upload
    if (isset($_FILES['image_barang']) && $_FILES['image_barang']['error'] == 0) {
        $file = $_FILES['image_barang'];
        $target_dir = "uploads/";
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // 4. Buat nama file baru sesuai format yang diinginkan
        // Membersihkan nama barang untuk dijadikan bagian dari nama file
        $safe_nama_barang = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '_', $nama_barang));
        // Membuat 4 kode acak
        $random_code = substr(bin2hex(random_bytes(2)), 0, 4); // contoh: a1b2
        
        $new_filename = "{$last_id}_{$safe_nama_barang}_{$random_code}.{$extension}";
        $target_file = $target_dir . $new_filename;

        // 5. Pindahkan file yang di-upload
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // 6. UPDATE baris data dengan nama file gambar yang baru
            $stmt_update = $conn->prepare("UPDATE barang SET image_barang = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_filename, $last_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Opsional: berikan pesan error jika upload gagal
            echo "Maaf, terjadi kesalahan saat mengupload file.";
        }
    }

    $conn->close();
    header("Location: inventory.php?status=sukses");
    exit();
}
?>