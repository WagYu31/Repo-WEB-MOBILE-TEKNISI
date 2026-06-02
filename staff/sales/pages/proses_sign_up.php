<?php
session_start();

include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik = $_POST['nik'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $konfirmasiPassword = $_POST['konfirmasiPassword'];

    $checkNIKQuery = "SELECT * FROM data_warga WHERE nik = ?";
    $checkNIKStmt = mysqli_prepare($conn, $checkNIKQuery);
    mysqli_stmt_bind_param($checkNIKStmt, "s", $nik);
    mysqli_stmt_execute($checkNIKStmt);
    $resultNIK = mysqli_stmt_get_result($checkNIKStmt);

    if (mysqli_num_rows($resultNIK) == 0) {
        $errorMessage = "NIK tidak valid. Harap hubungi Sekretaris RT 12 untuk mendaftarkan diri.";
        $_SESSION['sign_up_error'] = $errorMessage;
        header("Location: sign-up.php");
        exit();
    }

    $checkUserQuery = "SELECT * FROM user WHERE nik = ?";
    $checkUserStmt = mysqli_prepare($conn, $checkUserQuery);
    mysqli_stmt_bind_param($checkUserStmt, "s", $nik);
    mysqli_stmt_execute($checkUserStmt);
    $resultUser = mysqli_stmt_get_result($checkUserStmt);

    if (mysqli_num_rows($resultUser) > 0) {
        $errorMessage = "NIK sudah terdaftar.";
        $_SESSION['sign_up_error'] = $errorMessage;
        header("Location: sign-up.php");
        exit();
    }

    $checkUsernameQuery = "SELECT * FROM user WHERE username = ?";
    $checkUsernameStmt = mysqli_prepare($conn, $checkUsernameQuery);
    mysqli_stmt_bind_param($checkUsernameStmt, "s", $username);
    mysqli_stmt_execute($checkUsernameStmt);
    $resultUsername = mysqli_stmt_get_result($checkUsernameStmt);

    if (mysqli_num_rows($resultUsername) > 0) {
        $errorMessage = "Username sudah terdaftar. Harap gunakan username lain.";
        $_SESSION['sign_up_error'] = $errorMessage;
        header("Location: sign-up.php");
        exit();
    }

    if ($password !== $konfirmasiPassword) {
        $errorMessage = "Password dan konfirmasi password tidak cocok.";
        $_SESSION['sign_up_error'] = $errorMessage;
        header("Location: sign-up.php");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $addUserQuery = "INSERT INTO user (username, password, nik, role) VALUES (?, ?, ?, 'warga')";
    $addUserStmt = mysqli_prepare($conn, $addUserQuery);
    mysqli_stmt_bind_param($addUserStmt, "sss", $username, $hashedPassword, $nik);

    if (mysqli_stmt_execute($addUserStmt)) {
        header('Location: login.php');
        exit();
    } else {
        $errorMessage = "Gagal mendaftar. Silakan coba lagi.";
        $_SESSION['sign_up_error'] = $errorMessage;
        header("Location: sign-up.php");
        exit();
    }
}
?>
