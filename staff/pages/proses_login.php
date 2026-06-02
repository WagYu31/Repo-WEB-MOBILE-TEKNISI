<?php
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nikUsername = isset($_POST['nikUsername']) ? $_POST['nikUsername'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Cek apakah nikUsername ada dalam tabel user
    $checkUserQuery = "SELECT * FROM user WHERE nik = ? OR username = ?";
    $stmt = mysqli_prepare($conn, $checkUserQuery);
    mysqli_stmt_bind_param($stmt, "ss", $nikUsername, $nikUsername);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verifikasi password
        $hashedPassword = $row['password'];
        if (password_verify($password, $hashedPassword)) {
            // Password benar, lakukan proses login
            session_start();
            $_SESSION['nik'] = $row['nik'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Pengecekan peran (role) untuk pengalihan halaman
            if ($_SESSION['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($_SESSION['role'] == 'sekretaris') {
                header('Location: sekretaris/dashboard.php');
            } elseif ($_SESSION['role'] == 'rt') {
                header('Location: rt/dashboard.php');
            } elseif ($_SESSION['role'] == 'warga') {
                header('Location: warga/tagihan-warga.php');
            } else {
                // Role lainnya, sesuaikan dengan kebutuhan
                header('Location: other_dashboard.php');
            }
            exit();
        } else {
            // Menggunakan parameter error pada URL untuk mengirim pesan kesalahan
            header('Location: login.php?error=' . urlencode("Password salah, silahkan cek lagi password Anda."));
            exit();
        }
    } else {
        // Menggunakan parameter error pada URL untuk mengirim pesan kesalahan
        header('Location: login.php?error=' . urlencode("Akun tidak ditemukan, pastikan NIK atau Username Anda terdaftar."));
        exit();
    }
}
?>
