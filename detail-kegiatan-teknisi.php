<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

include "conn.php";

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

include "get-number-task.php";


if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];
} else {
    // Tampilkan pesan jika parameter id_kegiatan tidak ada
    echo "Parameter id_kegiatan tidak valid.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Teknisi</title>

    <script>
    // Periksa lebar jendela browser
    var windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

    // Periksa apakah lebar jendela kurang dari atau sama dengan 768px
    if (windowWidth <= 768) {
        // Redirect jika perangkat adalah mobile
        window.location.href = 'detail-kegiatan-teknisi-mobile.php?id_kegiatan=<?php echo $id_kegiatan;?>';
    } else {
        // Redirect jika perangkat adalah desktop
        window.location.href = 'detail-kegiatan-teknisi-dekstop.php?id_kegiatan=<?php echo $id_kegiatan;?>';
    }
</script>

</head>
<body>
    <!-- Konten halaman index-teknisi.php (tidak akan ditampilkan karena akan di-redirect) -->
</body>
</html>