<?php
include("staff/conn.php");

// Proses form login jika tombol login ditekan
$nama = $_GET["nama"];
$login_err = "";

$getNama = "SELECT * FROM users WHERE name = '$nama'";
$resNama = mysqli_query($conn, $getNama);
$rowNama = mysqli_fetch_array($resNama);
$email = $rowNama['email'];
$password = $rowNama['password'];

// Query untuk mencari pengguna dengan username yang cocok
$sql = "SELECT id, email, name, password, jabatan FROM users WHERE email = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $param_email);
    $param_email = $email;
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $email, $nama, $stored_password, $jabatan);
            if (mysqli_stmt_fetch($stmt)) {
                if ($password === $stored_password) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["email"] = $email;
                        $_SESSION["id"] = $id;
                        $_SESSION["jabatan"] = $jabatan;
                        
                        // Redirect berdasarkan peran (role)
                        if ($jabatan === "Super Admin") {
                            header("location: staff/index-sa.php");
                        } elseif ($jabatan === "Teknisi") {
                            header("location: teknisi/index.php");
                        } elseif ($jabatan === "Admin") {
                            header("location: staff/index-sa.php");
                        } elseif ($jabatan === "Sales") {
                            header("location: sales/sales/index.php");
                        } elseif ($jabatan === "Sales Manager") {
                            header("location: sales/index-sa.php");
                        } else {
                            header("location: guest-mode.php");
                        }

                } else {
                    // Login gagal, set pesan kesalahan
                        header("location: index.php?login=failed");
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
