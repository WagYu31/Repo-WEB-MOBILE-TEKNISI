<?php
include "../conn.php";

$nip = $username = $password = $confirm_password = "";
$nip_err = $username_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi NIP
    if (empty(trim($_POST["nip"]))) {
        $nip_err = "Harap masukkan NIP.";
        echo "<script>alert('$nip_err'); window.location.href = 'sign-up.php';</script>";
    } else {
        $nip = trim($_POST["nip"]);

        $check_nip_sql = "SELECT id_account FROM account WHERE nip = ?";
        if ($stmt = mysqli_prepare($conn, $check_nip_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_nip);
            $param_nip = $nip;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $nip_err = "NIP ini sudah terdaftar.";
                    echo "<script>alert('$nip_err'); window.location.href = 'sign-up.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'sign-up.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validasi username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Harap masukkan username.";
        echo "<script>alert('$username_err'); window.location.href = 'sign-up.php';</script>";
    } else {
        $username = trim($_POST["username"]);
        
        $check_username_sql = "SELECT id_account FROM account WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $check_username_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "Username ini sudah digunakan.";
                    echo "<script>alert('$username_err'); window.location.href = 'sign-up.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'sign-up.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validasi password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Harap masukkan password.";
        echo "<script>alert('$password_err'); window.location.href = 'sign-up.php';</script>";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password harus memiliki setidaknya 8 karakter.";
        echo "<script>alert('$password_err'); window.location.href = 'sign-up.php';</script>";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validasi konfirmasi password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Harap konfirmasi password.";
        echo "<script>alert('$confirm_password_err'); window.location.href = 'sign-up.php';</script>";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password tidak cocok.";
            echo "<script>alert('$confirm_password_err'); window.location.href = 'sign-up.php';</script>";
        }
    }
    
    // Cek apakah ada error validasi sebelum melanjutkan
    if (empty($nip_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "SELECT nama, jabatan, id_sales FROM sales WHERE nip = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_nip);
            $param_nip = $nip;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $nama, $jabatan, $id_sales);
                    mysqli_stmt_fetch($stmt);
                    
                    // Set peran berdasarkan jabatan
                    if ($jabatan == "Sales Manager") {
                        $role = "Sales Manager";
                    } elseif ($jabatan == "Sales") {
                        $role = "Sales";
                    } elseif ($jabatan == "Telemarketing") {
                        $role = "Telemarketing";
                    }else {
                        $role = $jabatan;
                    }
                    
                    $sql = "INSERT INTO account (nip, username, pw, role, id_sales) VALUES (?, ?, ?, ?, ?)";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sssss", $param_nip, $param_username, $param_password, $param_role, $param_id_sales);
                        $param_nip = $nip;
                        $param_username = $username;
                        $param_password = $hashed_password;
                        $param_role = $role;
                        $param_id_sales = $id_sales;
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $message = "Akun berhasil dibuat!";
                            echo "<script>alert('$message'); window.location.href = 'index.php';</script>";
                        } else {
                            $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                            echo "<script>alert('$message'); window.location.href = 'sign-up.php';</script>";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $message = "NIP tidak ditemukan";
                    echo "<script>alert('$message'); window.location.href = 'sign-up.php';</script>";
                }
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi nanti.";
                echo "<script>alert('$message'); window.location.href = 'sign-up.php';</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>
