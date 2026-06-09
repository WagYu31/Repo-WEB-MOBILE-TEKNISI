<?php
// ════════════════════════════════════════════════════════
// CENTER LOGIN - Auto Login dengan Secret Token
// Hanya bisa diakses jika menyertakan token rahasia
// ════════════════════════════════════════════════════════

include("staff/conn.php");

// ── Secret Token (ganti dengan token rahasia sendiri) ──
$SECRET_TOKEN = "WagyuA531052002";

// ── Validasi token ──
if (!isset($_GET["token"]) || $_GET["token"] !== $SECRET_TOKEN) {
    http_response_code(403);
    echo "Access Denied";
    exit;
}

// ── Validasi nama ──
if (!isset($_GET["nama"]) || empty(trim($_GET["nama"]))) {
    http_response_code(400);
    echo "Parameter tidak valid";
    exit;
}

$nama = trim($_GET["nama"]);
$login_err = "";

// ── Gunakan prepared statement (aman dari SQL Injection) ──
$getNama = "SELECT * FROM users WHERE name = ?";
$stmtNama = mysqli_prepare($conn, $getNama);
mysqli_stmt_bind_param($stmtNama, "s", $nama);
mysqli_stmt_execute($stmtNama);
$resNama = mysqli_stmt_get_result($stmtNama);
$rowNama = mysqli_fetch_array($resNama);
mysqli_stmt_close($stmtNama);

if (!$rowNama) {
    header("location: index.php?login=failed");
    exit;
}

$email = $rowNama['email'];
$password = $rowNama['password'];

// Query untuk mencari pengguna dengan email yang cocok
$sql = "SELECT id, email, name, password, jabatan FROM users WHERE email = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $email);
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
                        exit;

                } else {
                    header("location: index.php?login=failed");
                    exit;
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

if ($login_err) {
    header("location: index.php?login=failed");
    exit();
}
?>
