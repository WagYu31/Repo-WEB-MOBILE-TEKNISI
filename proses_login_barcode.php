<?php
include("staff/conn.php");

// Proses form login jika tombol login ditekan
$username = $_GET["username"];
$password = $_GET["password"];
$login_err = "";

// Query untuk mencari pengguna dengan username yang cocok
$sql = "SELECT id_user, username, nama, nik, password, role FROM user WHERE username = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $param_username);
    $param_username = $username;
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id_user, $username, $nama, $nik, $stored_password, $role);
            if (mysqli_stmt_fetch($stmt)) {
                if ($password === $stored_password) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["nik"] = $nik;
                        $_SESSION["username"] = $username;
                        $_SESSION["id_user"] = $id_user;
                        $_SESSION["role"] = $role;
                        
                        // Redirect berdasarkan peran (role)
                        if ($role === "Admin") {
                            header("location: index-sa.php");
                        } elseif ($role === "Teknisi") {
                            header("location: teknisi/index.php");
                        } elseif ($role === "SA") {
                            header("location: index-sa.php");
                        } elseif ($role === "Sales") {
                            header("location: sales/sales/index.php");
                        } elseif ($role === "Sales Manager") {
                            header("location: sales/index-sa.php");
                        } 
                        else{
                            header("location: guest-mode.php");
                        }

                    $tgl_now = date("Y-m-d H:i:s");
                    $tipe = "Login";
                    $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";

                    if ($stmtHistory = mysqli_prepare($conn, $history)) {
                        mysqli_stmt_bind_param($stmtHistory, "ssss", $nama, $history, $tipe, $tgl_now);
                        if (mysqli_stmt_execute($stmtHistory)) {
                            // Eksekusi query berhasil
                        } else {
                            // Terjadi kesalahan saat eksekusi query
                            echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmtHistory);
                    }
                } else {
                    // Login gagal, set pesan kesalahan
                    $login_err = "Username atau password salah.";
                }
            }
        } else {
            // Login gagal, set pesan kesalahan
            $login_err = "Username atau password salah.";
        }
    } else {
        echo "Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    mysqli_stmt_close($stmt);
}

// Tutup koneksi database
mysqli_close($conn);

// Jika terjadi kesalahan saat login, alihkan ke halaman login.php dengan pesan kesalahan
if ($login_err) {
    // Login gagal, alihkan ke halaman login.php dengan pesan kesalahan
    header("location: index.php?login=failed");
    exit();
}
?>
