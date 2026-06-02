<?php
include("../conn.php");

// Proses form login jika tombol login ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Query untuk mencari pengguna dengan username yang cocok
    $sql = "SELECT id_account, username, pw, nip, role FROM account WHERE username = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_username);
        $param_username = $username;
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id_account, $username, $hashed_password, $nip, $role);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id_account"] = $id_account;
                        $_SESSION["username"] = $username;
                        $_SESSION["nip"] = $nip; // Tambahkan sesi NIP
                        $_SESSION["role"] = $role;

                        // Redirect berdasarkan peran (role)
                        if ($role === "Sales Manager") {
                            header("location: index-sa.php");
                        } elseif ($role === "Sales") {
                            header("location: sales/index.php");
                        } elseif ($role === "Telemarketing") {
                            header("location: index-tm.php");
                        } else {
                            header("location: guest-mode.php");
                        }

                        $tgl_now = date("Y-m-d H:i:s");
                        $tipe = "Login";
                        $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, 'LogIn  Account', ? , ?)";

                        if ($stmtHistory = mysqli_prepare($conn, $history)) {
                            mysqli_stmt_bind_param($stmtHistory, "sss", $username, $tipe, $tgl_now);
                            if (mysqli_stmt_execute($stmtHistory)) {
                                // Eksekusi query berhasil
                            } else {
                                // Terjadi kesalahan saat eksekusi query
                                echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmtHistory);
                        }
                    } else {
                        // Login gagal, alihkan ke halaman login.php dengan pesan kesalahan
                        header("location: index.php?login=failed");
                    }
                }
            } else {
                $login_err = "Username atau password salah.";
                header("location: index.php?login=failed");
            }
        } else {
            echo "Terjadi kesalahan. Silakan coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>
