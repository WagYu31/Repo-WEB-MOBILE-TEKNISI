<?php
include "../../conn.php";
$pageNow = "Laporan Keuangan RT 12";
include "session.php";
$querySesi = "SELECT * FROM data_warga WHERE nik = '$nikSesi'";
$resultSesi = mysqli_query($conn, $querySesi);
$rowSesi = mysqli_fetch_assoc($resultSesi);
$id_warga = $rowSesi['id_warga'];
$nama = $rowSesi['nama'];

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
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-200">

    <?php
    include "cek-menu.php";
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php
        include "nav-top.php";
        setlocale(LC_TIME, 'id_ID.utf8');
        $getMonth = date("M Y");
        ?>
        <div class="col-lg-12 mt-4">
            <div class="card h-100 py-3">
                <div class="card-header pb-0 p-3">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center">
                            <h6 class="mb-0 mx-1">Laporan Pembayaran</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 pb-0">
                    <ul class="list-group">
                        <li class="list-group-item border-0 bg-gradient-primary border-radius-lg mb-2">
                            <a href="tables.php" class="d-flex align-items-center text-decoration-none text-white">
                                <i class="material-icons opacity-10">chevron_right</i>
                                <h6 class="mb-0 ms-2 font-weight-bold text-white">Data Warga</h6>
                            </a>
                        </li>
                        <li class="list-group-item border-0 bg-gradient-primary border-radius-lg mb-2">
                            <a href="laporan_pembayaran.php" class="d-flex align-items-center text-decoration-none text-white">
                                <i class="material-icons opacity-10">chevron_right</i>
                                <h6 class="mb-0 ms-2 font-weight-bold text-white">Laporan Tagihan</h6>
                            </a>
                        </li>
                        <li class="list-group-item border-0 bg-gradient-primary border-radius-lg mb-2">
                            <a href="laporan_sedekah.php" class="d-flex align-items-center text-decoration-none text-white">
                                <i class="material-icons opacity-10">chevron_right</i>
                                <h6 class="mb-0 ms-2 font-weight-bold text-white">Laporan Sedekah</h6>
                            </a>
                        </li>
                        <li class="list-group-item border-0 bg-gradient-primary border-radius-lg mb-2">
                            <a href="data_pembayaran.php" class="d-flex align-items-center text-decoration-none text-white">
                                <i class="material-icons opacity-10">chevron_right</i>
                                <h6 class="mb-0 ms-2 font-weight-bold text-white">Pembukuan</h6>
                            </a>
                        </li>
                    </ul>

                </div>
            </div>
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

    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>