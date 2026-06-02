<?php
include "../../conn.php";
include "session.php";
$pageNow = "Pengeluaran";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php
    include "head.php";
    setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
    $todayDate = strftime('%B %Y');
    ?>
    <style>
        .aktif {
            color: #21d375;
        }

        .tidak {
            color: #d0342c;
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
        $pageNow = "Verifikasi Pembayaran";
        include "nav-top.php";
        ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row mt-4">
                <div class="col-lg-8 mb-lg-0 mb-4">
                    <div class="card z-index-2 mt-4">
                        <div class="card-body mt-n5 px-3">
                            <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1 mb-3">
                                <div class="col-12 p-2 px-4">
                                    <h4 class="font-weight-bolder text-light">Pengeluaran</h4>
                                    <?php
                                    $queryPengeluaranI = "SELECT SUM(jumlah) as total_pengeluaran FROM pengeluaran";

                                    $resultPengeluaranI = mysqli_query($conn, $queryPengeluaranI);
                                    $rowPengeluaranI = mysqli_fetch_assoc($resultPengeluaranI);
                                    $totalPengeluaran = $rowPengeluaranI['total_pengeluaran'];

                                    // Format jumlah uang masuk sebagai nominal rupiah
                                    $formattedTotalPengeluaran = "Rp " . number_format($totalPengeluaran, 0, ',', '.') . ",00";
                                    ?>
                                    <p class="text-xs my-auto font-weight-bold text-light"><?php echo $todayDate; ?></p>
                                    <h4 class="font-weight-bolder text-light text-end"><?php echo $formattedTotalPengeluaran; ?></h4>
                                </div>
                            </div>
                            <h6 class="ms-2 mt-4 mb-0"> Active Users </h6>
                            <p class="text-sm ms-2"> (<span class="font-weight-bolder">+11%</span>) than last week </p>
                            <div class="container border-radius-lg">
                                <div class="row">
                                    <div class="col-6 col-md-3 py-3 ps-0">
                                        <div class="d-flex mb-2">
                                            <div class="icon icon-shape icon-xxs shadow border-radius-sm bg-gradient-primary text-center me-2 d-flex align-items-center justify-content-center">
                                                <i class="material-icons opacity-10">groups</i>
                                            </div>
                                            <p class="text-xs my-auto font-weight-bold">Pemasukan <?php echo $todayDate; ?></p>
                                        </div>
                                        <h4 class="font-weight-bolder">42K</h4>
                                        <div class="progress w-75">
                                            <div class="progress-bar bg-dark w-60" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 py-3 ps-0">
                                        <div class="d-flex mb-2">
                                            <div class="icon icon-shape icon-xxs shadow border-radius-sm bg-gradient-info text-center me-2 d-flex align-items-center justify-content-center">
                                                <i class="material-icons opacity-10">ads_click</i>
                                            </div>
                                            <p class="text-xs mt-1 mb-0 font-weight-bold">Total Pemasukan</p>
                                        </div>
                                        <h4 class="font-weight-bolder">1.7m</h4>
                                        <div class="progress w-75">
                                            <div class="progress-bar bg-dark w-90" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 py-3 ps-0">
                                        <div class="d-flex mb-2">
                                            <div class="icon icon-shape icon-xxs shadow border-radius-sm bg-gradient-warning text-center me-2 d-flex align-items-center justify-content-center">
                                                <i class="material-icons opacity-10">receipt</i>
                                            </div>
                                            <p class="text-xs mt-1 mb-0 font-weight-bold">Total Pengeluaran</p>
                                        </div>
                                        <h4 class="font-weight-bolder">399$</h4>
                                        <div class="progress w-75">
                                            <div class="progress-bar bg-dark w-30" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 py-3 ps-0">
                                        <div class="d-flex mb-2">
                                            <div class="icon icon-shape icon-xxs shadow border-radius-sm bg-gradient-danger text-center me-2 d-flex align-items-center justify-content-center">
                                                <i class="material-icons opacity-10">category</i>
                                            </div>
                                            <p class="text-xs mt-1 mb-0 font-weight-bold">Sisa Saldo</p>
                                        </div>
                                        <h4 class="font-weight-bolder">74</h4>
                                        <div class="progress w-75">
                                            <div class="progress-bar bg-dark w-50" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-6 d-flex align-items-center">
                                    <h6 class="mb-0">Pengeluaran</h6>
                                </div>
                                <div class="col-6 text-end">
                                    <button class="btn btn-outline-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#tambahPengeluaranModal">Tambah</button>
                                </div>

                            </div>
                        </div>
                        <div class="card-body p-3 pb-0">
                            <ul class="list-group">
                                <?php
                                $queryPengeluaran = "SELECT * FROM pengeluaran ORDER BY tgl_pengeluaran DESC LIMIT 10";
                                $resultPengeluaran = mysqli_query($conn, $queryPengeluaran);

                                $counter = 0;

                                while ($rowPengeluaran = mysqli_fetch_assoc($resultPengeluaran)) {
                                    $idPengeluaran = $rowPengeluaran['id_pengeluaran'];
                                    $namaPengeluaran = $rowPengeluaran['keterangan'];
                                    $tglPengeluaran = $rowPengeluaran['tgl_pengeluaran'];
                                    setlocale(LC_TIME, 'id_ID');
                                    $tanggalFormatted = strftime('%d %b %Y', strtotime($tglPengeluaran));
                                    $jumlahPengeluaran = $rowPengeluaran['jumlah'];
                                    $jumlahPengeluaranRupiah = "Rp " . number_format($jumlahPengeluaran, 0, ',', '.') . ",00";
                                    $counter++;
                                ?>

                                    <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                                        <div class="d-flex flex-column col-5 col-md-5">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaPengeluaran; ?></h6>
                                            <span class="text-xs"><?php echo $tanggalFormatted; ?></span>
                                        </div>

                                        <div class="d-flex align-items-center text-sm col-4 col-md-4">
                                            <?php echo $jumlahPengeluaranRupiah; ?>
                                        </div>

                                        <div class="d-flex align-items-center text-sm col-3">
                                            <button class="btn btn-link text-dark text-sm mb-0 px-0 ms-4 toggle-status">
                                                <span class="status-indicator"><i class="material-icons text-lg position-relative me-1">edit</i></span>
                                                <span class="status-indicator"><i class="material-icons text-lg position-relative me-1">delete</i></span>
                                            </button>
                                        </div>
                                    </li>


                                <?php
                                    if ($counter >= 10) {
                                        break; // Hentikan loop setelah 10 list
                                    }
                                }
                                ?>
                                <div class="text-end col-12">
                                    <button class="btn btn-primary mt-2" id="loadMore">Load More</button>
                                </div>


                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <?php
            include "../footer.php";
            ?>
        </div>
    </main>
    <div class="fixed-plugin">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
    </div>






    <!--   Core JS Files   -->
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
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="../../assets/js/material-dashboard.min.js?v=3.1.0"></script>

    <!-- Tambahkan script ini di bagian head atau sebelum penutup tag body -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

</body>

</html>