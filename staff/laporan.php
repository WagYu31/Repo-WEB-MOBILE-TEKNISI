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
    @media print {
        .no-print, .sidenav, .navbar, .fixed-plugin { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .container-fluid { padding: 0 !important; }
    }
    <?php include "css/floating-menu2.css";?>
    /* Action bar */
    .action-bar {
        display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
        margin-bottom: 24px;
    }
    .action-btn {
        padding: 10px 20px; border: none; border-radius: 10px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: all 0.2s;
        letter-spacing: 0.02em;
    }
    .action-btn:hover { transform: translateY(-1px); }
    .btn-detail { background: #1e293b; color: #fff; box-shadow: 0 4px 12px rgba(30,41,59,0.2); }
    .btn-detail:hover { background: #334155; color: #fff; box-shadow: 0 6px 16px rgba(30,41,59,0.3); }
    .btn-print { background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; box-shadow: 0 4px 12px rgba(6,182,212,0.25); }
    .btn-print:hover { color: #fff; box-shadow: 0 6px 16px rgba(6,182,212,0.35); }
    .btn-lengkap { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 4px 12px rgba(34,197,94,0.25); }
    .btn-lengkap:hover { color: #fff; box-shadow: 0 6px 16px rgba(34,197,94,0.35); }
    .btn-validasi { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.25); margin-left: auto; }
    .btn-validasi:hover { color: #fff; box-shadow: 0 6px 16px rgba(99,102,241,0.35); }
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php
    include "cek-menu.php";
    $current_date = (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) ? $_GET['cariBulanTahun'] : date("Y-m");
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="action-bar no-print">
                <a href="detail-lap.php" class="action-btn btn-detail">
                    <i class="fa-solid fa-file-invoice"></i> Detail Invoice
                </a>
                <a href="print-laporan.php?cariBulanTahun=<?= $current_date;?>" target="_blank" class="action-btn btn-print">
                    <i class="fa-solid fa-print"></i> Print Laporan
                </a>
                <a href="cetak-laporan-bulanan.php?bulan=<?= date('m', strtotime($current_date)); ?>&tahun=<?= date('Y', strtotime($current_date)); ?>" target="_blank" class="action-btn btn-lengkap">
                    <i class="fa-solid fa-file-lines"></i> Laporan Lengkap
                </a>
                <a href="generate-bonus.php?cariBulanTahun=<?= $current_date;?>" class="action-btn btn-validasi">
                    <i class="fa-solid fa-circle-check"></i> Validasi Data
                </a>
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