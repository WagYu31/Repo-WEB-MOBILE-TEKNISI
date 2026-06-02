<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php
    include "staff/head.php";
    ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            /* background-image: url('img/loewix.png'); */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100vw;
            /* Sesuaikan dengan lebar halaman */
            height: 100vh;
            /* Sesuaikan dengan tinggi halaman */
            display: flex;
            justify-content: center;
            align-items: center;
        }


        .image-container {
            max-width: 80%;
        }

        img {
            display: block;
            width: 100%;
            height: auto;
        }
    </style>

</head>

<body>
    <div class="image-container">
        <img src="img/loewix.png" alt="Logo" class="text-center mx-auto">
    </div>
</body>
<script>
    setTimeout(function() {
        window.location.href = 'staff/index.php';
    }, 1000);
</script>

</html>