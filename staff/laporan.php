<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Pendapatan";
$currentPage = "Today";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    .table thead th { border-bottom-width: 1px !important; }
    #data-tek tbody tr:nth-child(even) { background-color: #fcfcfc; }
    #data-tek tbody tr:hover { background-color: #f1f4f8; transition: 0.3s; }
    .card { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    @media print {
        .no-print, .sidenav, .navbar, .fixed-plugin { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        #printable-content { width: 100% !important; }
    }
    <?php include "css/floating-menu2.css";?>
  </style>
</head>
<body class="g-sidenav-show bg-gray-100">
    <?php
    include "cek-menu.php";
    $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row mb-4 no-print">
                <div class="col-12 d-flex flex-wrap gap-2">
                    <a href="detail-lap.php" class="btn bg-gradient-dark mb-0">
                        <i class="material-icons text-sm me-1">receipt_long</i> Detail Invoice
                    </a>
                    <a href="print-laporan.php?cariBulanTahun=<?= $current_date;?>" target="_blank" class="btn bg-gradient-info mb-0">
                        <i class="material-icons text-sm me-1">print</i> Print Laporan
                    </a>
                    <a href="cetak-laporan-bulanan.php?bulan=<?= date('m', strtotime($current_date)); ?>&tahun=<?= date('Y', strtotime($current_date)); ?>" target="_blank" class="btn bg-gradient-success mb-0">
                        <i class="material-icons text-sm me-1">description</i> Laporan Lengkap
                    </a>
                    <a href="generate-bonus.php?cariBulanTahun=<?= $current_date;?>" class="btn btn-primary mb-0 ms-auto">
                        <i class="material-icons text-sm me-1">verified</i> Validasi Data
                    </a>
                </div>
            </div>
            <div class="row">
                <?php include "laporan-db.php"; ?>
            </div>
            <?php 
            // include "floating-menu.php"; 
            include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
          Scrollbar.init(document.querySelector('#sidenav-scrollbar'), { damping: '0.5' });
        }
    </script>
</body>
</html>