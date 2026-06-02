<?php
include "../conn.php";
include "../session.php";
$id_user = $_SESSION['id_user'];
include "../get-user-data.php";
$pageNow = "Kegiatan";

$user = "SELECT * FROM user WHERE id_user = $id_user";
$resultUs = mysqli_query($conn, $user);
$rwt = mysqli_fetch_assoc($resultUs);
$idt = $rwt['id_teknisi'];
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
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "../nav-top.php";
    setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
    $todayDate = strftime('%d %B %Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">


      <div class="row mb-4 mt-0">

        <?php
        include "kegiatan-db-all.php";
        ?>

      </div>
      <?php
      include "../footer.php";
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