<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";
    // Tangkap data dari form
// Tangkap data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST["nama"];
    $no_tlp = $_POST["no_tlp"];
    $alamat = $_POST["alamat"];
    $kota = $_POST["kota"];
    $kodePos = $_POST["kodePos"];
    $provinsi = $_POST["provinsi"];
    $kategori = $_POST["kategori"];
    $email = $_POST["email"];
    date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta
    $created_at = date('Y-m-d H:i:s');

    include "nomor_telepon.php";

    // Check if a customer with the same phone number exists
    $checkCustomerQuery = "SELECT nama FROM customer WHERE telp = '$no_tlp' AND deleted_at IS NULL";
    $checkCustomerResult = mysqli_query($conn, $checkCustomerQuery);

    $duplikat = isset($_POST["duplikat"]) ? true : false;

    if (mysqli_num_rows($checkCustomerResult) > 0 && !$duplikat) {
        $existingCustomer = mysqli_fetch_assoc($checkCustomerResult);
        $existingCustomerName = $existingCustomer['nama'];

        // If a customer with the same phone number exists and "Duplikat" is not checked, show an alert
        echo '<script>alert("PERINGATAN! Data pelanggan dengan nomor telepon yang sama sudah ada dengan nama: ' . $existingCustomerName . '\nJika ingin memasukkan data dengan nama atau alamat yang berbeda, silakan centang Duplikat Data.");</script>';
    } else {
        // Insert data into the database
        $sql = "INSERT INTO customer (nama, telp, alamat, kota, kodepos, provinsi, kategori, email, created_at, updated_at) VALUES ('$nama', '$no_tlp', '$alamat', '$kota', '$kodePos', '$provinsi', '$kategori', '$email', '$created_at', '$created_at')";

        if (mysqli_query($conn, $sql)) {
            // Redirect atau refresh halaman
            echo '<script>window.location.href = "customer.php";</script>';
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
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
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <div class="row mb-4 mt-4">

        <?php
            include "tambah-customer-db.php";
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