<?php
include "conn.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mendapatkan data dari form
    $nikUsername = isset($_POST['nikUsername']) ? $_POST['nikUsername'] : '';
    $oldPassword = isset($_POST['oldPassword']) ? $_POST['oldPassword'] : '';
    $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
    $confirmNewPassword = isset($_POST['confirmNewPassword']) ? $_POST['confirmNewPassword'] : '';

    // Validasi password baru
    if ($newPassword != $confirmNewPassword) {
        header('Location: change_password.php?error=' . urlencode("Konfirmasi password baru tidak cocok. Silakan coba lagi."));
        exit();
    }

    // Cek apakah nikUsername ada dalam tabel user
    $checkUserQuery = "SELECT * FROM user WHERE nik = ?";
    $stmt = mysqli_prepare($conn, $checkUserQuery);
    mysqli_stmt_bind_param($stmt, "s", $nikUsername);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verifikasi password lama
        $hashedPassword = $row['password'];
        if (password_verify($oldPassword, $hashedPassword)) {
            // Password lama cocok, update password baru
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePasswordQuery = "UPDATE user SET password = ? WHERE nik = ?";
            $stmt = mysqli_prepare($conn, $updatePasswordQuery);
            mysqli_stmt_bind_param($stmt, "ss", $hashedNewPassword, $row['nik']);
            mysqli_stmt_execute($stmt);

            // Redirect ke halaman berhasil
            header('Location: change_password.php?success=' . urlencode("Password berhasil diubah. Silahkan keluar akun dan masuk kembali menggunakan Password baru Anda."));
            exit();
        } else {
            // Password lama tidak cocok
            header('Location: change_password.php?error=' . urlencode("Password lama salah. Silakan coba lagi."));
            exit();
        }
    } else {
        // Akun tidak ditemukan
        header('Location: change_password.php?error=' . urlencode("Akun tidak ditemukan. Pastikan NIK atau Username Anda terdaftar."));
        exit();
    }
}
?>
