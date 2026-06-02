<?php
include 'conn.php';
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $status = $_POST['status'];
    $barang_id = $_POST['barang_id'];
    $now = date('Y-m-d H:i:s');
    $success = false;
    $idPeminjaman = $_POST['idpeminjaman'];
    $qty_akhir = $_POST['qty_akhir'];
    $idSesi = $_SESSION['id_karyawan'];

    function generateRandomCode($length = 5) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    if ($status === 'ditolak' || $status === 'aktif') {
        $sql = "UPDATE peminjaman_barang SET status = ?, updated_at = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $status, $now, $idPeminjaman);
        $success = $stmt->execute();
        $stmt->close();
    } elseif ($status === 'acc' || $status === 'selesai') {
        $denda = !empty($_POST['denda']) ? $_POST['denda'] : NULL;
        $keterangan = !empty($_POST['keterangan']) ? $_POST['keterangan'] : NULL;
        
        $gambar_kembali = NULL;
        if (!empty($_FILES['gambar_kembali']['name'])) {
            $originalFileName = $_FILES['gambar_kembali']['name'];
            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
            $randomCode = generateRandomCode(5);

            $newFileName = "pinjam_{$code}_{$randomCode}.{$fileExtension}";
            $gambar_kembali = 'assets/' . $newFileName;

            if (!move_uploaded_file($_FILES['gambar_kembali']['tmp_name'], $gambar_kembali)) {
                header("Location: peminjaman.php");
                exit();
            }
        }

        $sql = "UPDATE peminjaman_barang SET 
                    status = ?, 
                    qty_akhir = ?, 
                    denda = ?, 
                    keterangan = ?, 
                    gambar_kembali = ?, 
                    updated_at = ?
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", 
            $status, 
            $qty_akhir, 
            $denda, 
            $keterangan, 
            $gambar_kembali, 
            $now, 
            $idPeminjaman
        );
        
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $sqlUser = "SELECT name FROM users WHERE id = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("i", $idSesi);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();
            $user = $resultUser->fetch_assoc();
            $stmtUser->close();

            $nama = $user['name'];

            $sqlLog = "INSERT INTO log (barang_id, nama, log, qty, keterangan, updated_at) 
                        VALUES (?, ?, 'Tambah Barang', ?, 'Pengembalian', ?)";
            $stmtLog = $conn->prepare($sqlLog);
            $stmtLog->bind_param("isss", $barang_id, $nama, $qty_akhir, $now);
            $stmtLog->execute();
            $stmtLog->close();

            $sqlBarang = "UPDATE barang SET stok = stok + ? WHERE id = ?";
            $stmtBarang = $conn->prepare($sqlBarang);
            $stmtBarang->bind_param("ii", $qty_akhir, $barang_id);
            $stmtBarang->execute();
            $stmtBarang->close();
        }
    }
    
    header("Location: peminjaman.php");
    exit();
}
?>
