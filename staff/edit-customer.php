<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";
// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $id_customer = $_GET["id"];
}

// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_customer_get = $_GET['id'];
    $nama = $_POST["nama"];
    $no_tlp = $_POST["no_tlp"];
    $alamat = $_POST["alamat"];
    $kota = $_POST["kota"];
    $provinsi = $_POST["provinsi"];
    $kodePos = $_POST["kodePos"];
    $kategori = $_POST["kategori"];
    $email = $_POST["email"];
    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $updated_at = date('Y-m-d H:i:s');

    // Lakukan validasi data (jika diperlukan)

    include "nomor_telepon.php";

    // Update data dalam database
    $sql = "UPDATE customer SET 
                nama='$nama', 
                telp='$no_tlp',
                alamat='$alamat',
                kota='$kota',
                provinsi='$provinsi',
                kodepos='$kodePos',
                kategori='$kategori',
                email='$email',
                updated_at='$updated_at'
            WHERE id=$id_customer_get";

    if (mysqli_query($conn, $sql)) {
        // Redirect atau refresh halaman
        echo '<script>window.location.href = "customer.php";</script>';
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php
    include "head.php";
    ?>
    <style>
        ul#data-tek li:nth-child(odd) {
            background-color: white;
        }

        ul#data-tek li:nth-child(even) {
            background-color: #efefef;
            border-radius: 0;
        }

        #toggleLoadMore {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
    <?php
    include "cek-menu.php";
    ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <?php
        include "nav-top.php";
        $todayDate = formatTanggal('dd MMMM yyyy');
        ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4">

            <div class="row mb-4 mt-4">

                <?php
                include "edit-customer-db.php";
                ?>

            </div>
            <?php
            include "footer.php";
            ?>
        </div>


    </main>
    <?php
    include "js-include.php";
    ?>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>