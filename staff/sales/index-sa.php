<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Today";
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

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }
        <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $todayDate = strftime('%d %B %Y');
    ?>

      <div class="container-fluid py-4">
        <?php 
        // include 'top-point.php';
        ?>
        <div class="row mb-4 mt-4">
          <?php
          include "kegiatan-db.php";
          ?>
        </div>
        <?php
        include "floating-menu.php";
        include "footer.php";
        ?>
      </div>
    <?php
    // }
    ?>


  </main>
  <?php
  include "js-include.php";
  ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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