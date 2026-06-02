<?php
include 'conn.php';

// Atur zona waktu Jakarta
date_default_timezone_set('Asia/Jakarta');

$username = $_POST['username'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$user_id_raw = $_POST['user_id']; // contoh: sales_3 atau teknisi_7

// Validasi input dasar
if (empty($username) || empty($password) || empty($confirm_password) || empty($user_id_raw)) {
  echo "<script>alert('Semua field harus diisi.'); window.history.back();</script>";
  exit;
}

// Validasi konfirmasi password
if ($password !== $confirm_password) {
  echo "<script>alert('Konfirmasi password tidak cocok.'); window.history.back();</script>";
  exit;
}

// Pisahkan role dan ID
list($role, $id) = explode('_', $user_id_raw);

// Enkripsi password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Waktu saat ini (format SQL DATETIME)
$now = date('Y-m-d H:i:s');

// Ambil nama berdasarkan role dan id
if ($role === 'sales') {
  $getNamaQuery = "SELECT nama FROM sales WHERE id = ?";
} elseif ($role === 'teknisi') {
  $getNamaQuery = "SELECT nama FROM teknisi WHERE id = ?";
} else {
  echo "<script>alert('Role tidak valid.'); window.history.back();</script>";
  exit;
}

$stmtNama = mysqli_prepare($conn, $getNamaQuery);
mysqli_stmt_bind_param($stmtNama, 'i', $id);
mysqli_stmt_execute($stmtNama);
$resultNama = mysqli_stmt_get_result($stmtNama);

if ($row = mysqli_fetch_assoc($resultNama)) {
  $nama = $row['nama'];
} else {
  echo "<script>alert('Data tidak ditemukan.'); window.history.back();</script>";
  exit;
}

// Cek apakah sudah punya akun
if ($role === 'sales') {
  $checkQuery = "SELECT * FROM user_sales WHERE sales_id = ?";
  $insertQuery = "INSERT INTO user_sales (username, password, sales_id, nama, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
} else {
  $checkQuery = "SELECT * FROM user_teknisi WHERE teknisi_id = ?";
  $insertQuery = "INSERT INTO user_teknisi (username, password, teknisi_id, nama, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
}

$stmtCheck = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmtCheck, 'i', $id);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);

if (mysqli_num_rows($resultCheck) > 0) {
  echo "<script>alert('Akun sudah terdaftar sebelumnya.'); window.history.back();</script>";
  exit;
}

// Insert akun baru
$stmtInsert = mysqli_prepare($conn, $insertQuery);
mysqli_stmt_bind_param($stmtInsert, 'ssisss', $username, $hashed_password, $id, $nama, $now, $now);
if (mysqli_stmt_execute($stmtInsert)) {
  echo "<script>alert('Pendaftaran berhasil!'); window.location.href='index.php';</script>";
} else {
  echo "<script>alert('Terjadi kesalahan saat mendaftar.'); window.history.back();</script>";
}
?>
