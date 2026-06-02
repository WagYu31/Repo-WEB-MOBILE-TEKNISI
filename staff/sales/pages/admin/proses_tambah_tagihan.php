<?php
// Sertakan file koneksi ke database
include '../../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Tangkap data dari formulir
  $namaTagihan = $_POST['namaTagihan'];
  $jumlahTagihan = $_POST['jumlahTagihan'];
  $jenisTagihan = $_POST['jenisTagihan'];
  $tanggalTagihan = $_POST['tanggalTagihan'];
  $jenisTagihan = ($jenisTagihan == "wajib") ? "Wajib" : "Tidak Wajib";

  // Siapkan query untuk menyisipkan data ke database
  $queryInsert = "INSERT INTO tagihan (nama_tagihan, jumlah, jenis, tgl_tagihan, status) VALUES ('$namaTagihan', '$jumlahTagihan', '$jenisTagihan', '$tanggalTagihan', 'Y')";

  // Jalankan query
  $resultInsert = mysqli_query($conn, $queryInsert);

  // Cek apakah penyisipan berhasil
  if ($resultInsert) {
    echo json_encode(array('status' => 'success', 'message' => 'Tagihan berhasil ditambahkan'));
  } else {
    echo json_encode(array('status' => 'error', 'message' => 'Gagal menambahkan tagihan'));
  }

  // Tutup koneksi ke database
  mysqli_close($conn);
} else {
  // Jika bukan metode POST, kirim respons error
  echo json_encode(array('status' => 'error', 'message' => 'Metode tidak diizinkan'));
}
?>
