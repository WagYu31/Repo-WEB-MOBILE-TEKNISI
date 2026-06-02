<?php
include("staff/conn.php");

// Proses form login jika tombol login ditekan
$token = $_GET["token"];

// Array yang berisi token dan informasi pengguna terkait
$tokens = array(
    "uS7Xt3gYpW1R" => array("username" => "febry4gh", "password" => "$2y$10$DLWqw5fNjUejbZAeyOsKHOr3MyzxBX5rK3lJcRqAXIDUwvkDHpflS"),
    "5qDfJ8hPmZ0K" => array("username" => "admin123*", "password" => "$2y$10$azyTIEPlTXOOgmFC1tksiuy8qfqEhTnSpLcgf/bQNu7t0M6js8L/W"),
    "N6iSxV9wE2jQ" => array("username" => "robi", "password" => "$2y$10$Oe67xPhoVyI2SznljP8mc.Y9YRxf8MDT0.NstRpdQtOG279LhMzz."),
    "tF0nK4mG1vYb" => array("username" => "agung", "password" => "$2y$10$jtct7GUiqkySwzsl68Xd8ucw0rxP60uwJBDjh8n1qPGntoRZC3X1W"),
    "h2sDzU7yI8Xm" => array("username" => "arif123", "password" => "$2y$10$axVnKkBI/AmFjn7cLDYkN.d0mHC81rOFTizchm2ukDiGcL79iR10C"),
    "M4aLc9bT3dFz" => array("username" => "Diyat", "password" => "$2y$10$qKEBK1wkHKLWMEFmNetYG.hFENqbkP2/J0nZKgkYW0vLm7dweN4Q."),
    "kV1tN6rZpJ8D" => array("username" => "pato", "password" => "$2y$10$./muvyWgG.noCrw2r62VlOF9hhIqw1VOlhxl08rIO1MTG9t4JaNTa"),
    "Q7dE0gX4iY2W" => array("username" => "Imam123", "password" => "$2y$10$yb1hrtid0bY7ReJB1E7xf.z7olXp8aXCEHIuDx1N483czqJ.N9MOW")
);

// Cek apakah token ada dalam array tokens
if (array_key_exists($token, $tokens)) {
    $user_info = $tokens[$token];
    $username = $user_info["username"];
    $password = $user_info["password"];

    // Query untuk mencari pengguna dengan username yang cocok
    $sql = "SELECT id_user, nama, nik, role FROM user WHERE username = ? AND password = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id_user, $nama, $nik, $role);
                if (mysqli_stmt_fetch($stmt)) {
                    session_start();
                    $_SESSION["loggedin"] = true;
                    $_SESSION["nik"] = $nik;
                    $_SESSION["username"] = $username;
                    $_SESSION["id_user"] = $id_user;
                    $_SESSION["role"] = $role;

                    // Redirect berdasarkan peran (role)
                    switch ($role) {
                        case "Admin":
                        case "Admin2":
                        case "SA":
                            header("location: staff/index-sa.php");
                            break;
                        case "Teknisi":
                            header("location: staff/teknisi/index.php");
                            break;
                        case "Sales":
                            header("location: staff/index-sales.php");
                            break;
                        case "Guest":
                            header("location: guest-mode.php");
                            break;
                        default:
                            // Default redirection
                            header("location: index.php");
                            break;
                    }
                    exit();
                }
            } else {
                $login_err = "Login gagal. Username atau password salah.";
            }
        } else {
            echo "Terjadi kesalahan. Silakan coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $login_err = "Token tidak valid.";
}

mysqli_close($conn);

if ($login_err) {
    header("location: index.php?login=failed&error=" . urlencode($login_err));
    exit();
}
?>
