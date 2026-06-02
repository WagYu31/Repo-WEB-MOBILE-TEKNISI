<?php
include "conn.php";

$nik = $username = $password = $confirm_password = "";
$nik_err = $username_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi NIK
    if (empty(trim($_POST["nik"]))) {
        $nik_err = "Harap masukkan NIK.";
        echo "<script>alert('$nik_err'); window.location.href = 'signup.php';</script>";
    } else {
        $nik = trim($_POST["nik"]);

        $check_nik_sql = "SELECT id_user FROM user WHERE nik = ?";
        if ($stmt = mysqli_prepare($conn, $check_nik_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_nik);
            $param_nik = $nik;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $nik_err = "NIK ini sudah terdaftar.";
                    echo "<script>alert('$nik_err'); window.location.href = 'signup.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'signup.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validasi username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Harap masukkan username.";
        echo "<script>alert('$username_err'); window.location.href = 'signup.php';</script>";
    } else {
        $username = trim($_POST["username"]);
        
        $check_username_sql = "SELECT id_user FROM user WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $check_username_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "Username ini sudah digunakan.";
                    echo "<script>alert('$username_err'); window.location.href = 'signup.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'signup.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validasi password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Harap masukkan password.";
        echo "<script>alert('$password_err'); window.location.href = 'signup.php';</script>";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password harus memiliki setidaknya 8 karakter.";
        echo "<script>alert('$password_err'); window.location.href = 'signup.php';</script>";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validasi konfirmasi password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Harap konfirmasi password.";
        echo "<script>alert('$confirm_password_err'); window.location.href = 'signup.php';</script>";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password tidak cocok.";
            echo "<script>alert('$confirm_password_err'); window.location.href = 'signup.php';</script>";
        }
    }
    
    // Cek apakah ada error validasi sebelum melanjutkan
    if (empty($nik_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "SELECT nama, jabatan, id_teknisi FROM loewix WHERE nik = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_nik);
            $param_nik = $nik;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $nama, $jabatan, $id_teknisi);
                    mysqli_stmt_fetch($stmt);
                    
                    // Set peran berdasarkan jabatan
                    if ($jabatan == "Teknisi" || $jabatan == "IT Support") {
                        $jabatan = "Teknisi";
                    } elseif ($jabatan == "Admin") {
                        $jabatan = "Admin";
                        $id_teknisi = NULL;
                    } elseif ($jabatan == "Manager") {
                        $jabatan = "SA";
                        $id_teknisi = NULL;
                    } elseif ($jabatan == "Sales") {
                        $jabatan = "Sales";
                        $id_teknisi = NULL;
                    } else {
                        $jabatan = $jabatan;
                        $id_teknisi = NULL;
                    }
                    
                    $sql = "INSERT INTO user (nik, nama, username, password, role, id_teknisi) VALUES (?, ?, ?, ?, ?, ?)";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ssssss", $param_nik, $param_nama, $param_username, $param_password, $param_role, $param_id_teknisi);
                        $param_nik = $nik;
                        $param_nama = $nama;
                        $param_username = $username;
                        $param_password = $hashed_password;
                        $param_role = $jabatan;
                        $param_id_teknisi = $id_teknisi;
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = "Akun berhasil dibuat!";
                            echo "<script>alert('$message'); window.location.href = 'login.php';</script>";
                        } else {
                            $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                            echo "<script>alert('$message'); window.location.href = 'signup.php';</script>";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $message = "NIK tidak ditemukan";
                    echo "<script>alert('$message'); window.location.href = 'signup.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'signup.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>