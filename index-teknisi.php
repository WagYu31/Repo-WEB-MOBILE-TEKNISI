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
        window.location.href = 'index-teknisi-mobile.php';
    } else {
        // Redirect jika perangkat adalah desktop
        window.location.href = 'index-teknisi-dekstop.php';
    }
</script>

</head>
<body>
    <!-- Konten halaman index-teknisi.php (tidak akan ditampilkan karena akan di-redirect) -->
</body>
</html>