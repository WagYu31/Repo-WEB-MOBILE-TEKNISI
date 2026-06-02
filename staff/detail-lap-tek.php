<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Pendapatan";
$currentPage = "Today";
$role = $_SESSION['jabatan'];

$idTeknis = isset($_GET['idTek']) ? $_GET['idTek'] : null; // Ensure $idTeknis is set

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
    @media print {
    .no-print {
        display: none;
    }
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
    setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
    $todayDate = strftime('%d %B %Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <div class="row mb-4 mt-0">
        <div class="col-md-4 col-12 d-flex justify-content-start align-items-center">
            <button class="btn bg-gradient-info w-30 btn-print">Print</button>
        </div>

          <div class="col-8">
              
          </div>
        
        <?php
        include "detail-laporan-tek-db.php";
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
  
  <script>
    // Fungsi untuk mencetak konten
    function printContent() {
        var content = document.getElementById("printable-content").innerHTML;
        var originalBody = document.body.innerHTML;
        document.body.innerHTML = content;
        window.print();
        document.body.innerHTML = originalBody;
    }

    // Menambahkan event listener untuk tombol "Print"
    document.querySelector(".btn-print").addEventListener("click", printContent);
</script>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
</body>

</html>