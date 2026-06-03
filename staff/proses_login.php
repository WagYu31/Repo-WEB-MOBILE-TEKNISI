<?php
include("conn.php");

// Proses form login jika tombol login ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Query untuk mencari pengguna dengan username yang cocok
    $sql = "SELECT id, email, name, password, jabatan FROM users WHERE email = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_email);
        $param_email = $email;
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $email, $name, $hashed_password, $jabatan);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["email"] = $email;
                        $_SESSION["id"] = $id;
                        $_SESSION["jabatan"] = $jabatan;
                        
                        function isMobileDevice() {
                            return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
                        }

                        // Redirect berdasarkan peran (role)
                        if ($jabatan === "Super Admin") {
                            // index-sa.php sudah responsive (desktop + mobile)
                            header("location: index-sa.php");
                        } elseif ($jabatan === "Teknisi") {
                            header("location: teknisi/index.php");
                        } elseif ($jabatan === "Admin") {
                            // index-sa.php sudah responsive (desktop + mobile)
                            header("location: index-sa.php");
                        } elseif ($jabatan === "Sales") {
                            header("location: sales/sales/index.php");
                        } elseif ($jabatan === "Sales Manager") {
                            header("location: sales/index-sa.php");
                        } else {
                            header("location: guest-mode.php");
                        }

                    } else {
                        // Login gagal, alihkan ke halaman login.php dengan pesan kesalahan
                        header("location: index.php?login=failed");
                    }
                }
            } else {
                $login_err = "Username atau password salah.";
            }
        } else {
            echo "Terjadi kesalahan. Silakan coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
