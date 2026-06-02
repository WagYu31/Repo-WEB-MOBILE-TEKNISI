<?php
include "../../conn.php";
session_start();

// Cek koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ambil nilai dari formulir modal
$idBank = $_POST['editBankSelect'];
$atasNama = $_POST['atasNama'];
$nomorRekening = $_POST['editNomorRekening'];

// Lakukan kueri UPDATE
$updateQuery = "UPDATE bank_account SET atas_nama = '$atasNama', nomor_rekening = '$nomorRekening' WHERE id_bank = $idBank";

// Eksekusi kueri
if (mysqli_query($conn, $updateQuery)) {
    // Sukses
        header("Location: tagihan.php");
        exit();
} else {
    // Gagal
        // Error: Store error message in session and redirect to tagihan.php
        $_SESSION["error_message"] = "Error updating record: " . mysqli_error($conn);
        header("Location: tagihan.php");
        exit();
}

// Tutup koneksi
mysqli_close($conn);
?>
