<?php
// Koneksi database
include "conn.php";
include "session.php";

// Ambil data input dari form
$teknisiIds = $_POST['teknisi'] ?? [];
$barangData = $_POST['barang'] ?? [];
$userNameQuery = "SELECT name FROM users WHERE id = $idSesi";
$userNameResult = $conn->query($userNameQuery)->fetch_assoc();
$userName = $userNameResult['name'];

// Generate kode random untuk semua row peminjaman
$code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 5);

$conn->begin_transaction(); // Mulai transaksi
try {
    foreach ($barangData as $barangId => $data) {
        if (isset($data['check']) && $data['check'] == "1" && $data['qty'] > 0) {
            $qty = (int)$data['qty'];

            // Update stok barang
            $totalQty = $qty * count($teknisiIds);
            $conn->query("UPDATE barang SET stok = stok - $totalQty, updated_at = '$now' WHERE id = $barangId");

            // Insert log untuk barang ini
            $conn->query("
                INSERT INTO log (barang_id, nama, log, qty, keterangan, updated_at)
                VALUES ($barangId, '$userName', 'keluar', $totalQty, 'Pinjaman', '$now')
            ");

            // Insert ke peminjaman_barang untuk setiap teknisi
            foreach ($teknisiIds as $teknisiId) {
                $conn->query("
                    INSERT INTO peminjaman_barang (teknisi_id, barang_id, qty, status, code, tgl_pinjam, created_at, updated_at)
                    VALUES ($teknisiId, $barangId, $qty, 'dipinjam', '$code', '$now', '$now', '$now')
                ");
            }
        }
    }

    $conn->commit(); // Commit transaksi
    echo "Peminjaman berhasil diproses.";
    header("Location: peminjaman.php"); // Redirect ke halaman inventory
    exit(); // Hentikan eksekusi skrip setelah redirect
} catch (Exception $e) {
    $conn->rollback(); // Rollback jika ada error
    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>
