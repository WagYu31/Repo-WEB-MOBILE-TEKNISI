<?php
include "../../conn.php";
$pageNow = "Riwayat Pembayaran";
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
</head>

<body class="g-sidenav-show  bg-gray-200">

    <?php
    include "cek-menu.php";
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php
        include "nav-top.php";
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-12 text-center text-md-start">
                    <button type="button" class="btn btn-primary w-md-15 w-50" onclick="showPembayaran()">Iuran dan Tagihan</button>
                    <button type="button" class="btn btn-primary w-md-15 w-45" onclick="showSedekah()">Sedekah</button>
                </div>
                <div class="col-lg-12" id="pembayaranTabel">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center">
                                    <h6 class="mb-0 mx-1">Riwayat Pembayaran Iuran dan Tagihan Lain</h6>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 pb-0">
                            <ul class="list-group">
                                <?php
                                setlocale(LC_TIME, 'id_ID.utf8');
                                $query = "SELECT 
                                    pembayaran.kode_pembayaran,
                                    data_warga.nik,
                                    data_warga.nama AS nama_warga,
                                    pembayaran.tgl_bayar,
                                    GROUP_CONCAT(tagihan.id_tagihan SEPARATOR ',') AS id_tagihan,
                                    GROUP_CONCAT(tagihan.nama_tagihan SEPARATOR ', ') AS nama_tagihan,
                                    SUM(tagihan.jumlah) AS total_jumlah,
                                    pembayaran.status AS status_pembayaran
                                FROM pembayaran
                                JOIN tagihan ON pembayaran.id_tagihan = tagihan.id_tagihan
                                JOIN data_warga ON pembayaran.id_warga = data_warga.id_warga
                                WHERE pembayaran.id_warga = '$id_warga'
                                GROUP BY pembayaran.kode_pembayaran, data_warga.nik, data_warga.nama, pembayaran.tgl_bayar, pembayaran.status
                                ORDER BY pembayaran.kode_pembayaran, pembayaran.tgl_bayar DESC 
                                LIMIT 10";


                                $result = mysqli_query($conn, $query);

                                $counter = 0;
                                if(mysqli_num_rows($result) == 0){
                                  echo "<span class='mt-n2 text-sm'>Tidak ada riwayat pembayaran baru</span>";
                                }


                                while ($row = mysqli_fetch_assoc($result)) {
                                    $idTagihan = $row['id_tagihan'];
                                    $kodePembayaran = $row['kode_pembayaran'];
                                    $nik = $row['nik'];
                                    $namaWarga = $row['nama_warga'];
                                    $tglBayar = strftime("%d %B %Y", strtotime($row['tgl_bayar'])); // Format tanggal dalam bahasa Indonesia

                                    $namaTagihan = $row['nama_tagihan'];
                                    $totalJumlah = $row['total_jumlah'];
                                    $statusPembayaran = $row['status_pembayaran'];
                                    if ($statusPembayaran == "Pending") {
                                        $statusPembayaran = "Menunggu Verifikasi";
                                    } elseif ($statusPembayaran == "Verified") {
                                        $statusPembayaran = "Berhasil";
                                    } elseif ($statusPembayaran == "Tolak") {
                                        $statusPembayaran = "Ditolak";
                                    } else {
                                        $statusPembayaran = "?";
                                    }


                                    $counter++;
                                ?>

                                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                                        <div class="row">
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <?php
                                                if ($statusPembayaran == "Menunggu Verifikasi") {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-warning mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">priority_high</i></button>
                                                <?php
                                                } else if ($statusPembayaran == "Berhasil") {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">check</i></button>
                                                <?php
                                                } else {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-danger mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">close</i></button>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="col-6 col-md-4 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm text-uppercase">Kode Pembayaran : <?php echo $kodePembayaran; ?></h6>
                                                <span class="text-xs"><?php echo $namaTagihan; ?></span>
                                            </div>

                                            <div class="col-6 col-md-3 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                                                <span class="text-xs">Tanggal Pembayaran : <?php echo $tglBayar; ?></span>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $statusPembayaran; ?></h6>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0  text-start text-md-center">
                                                <a class="btn btn-outline-primary btn-sm mb-0" href="detail_pembayaran.php?kode_pembayaran=<?php echo $kodePembayaran; ?>">Detail</a>
                                            </div>
                                        </div>
                                    </li>

                                    <?php
                                    if ($statusPembayaran == "Ditolak") {
                                    ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-danger d-block d-md-none">
                                        <?php
                                    } else if ($statusPembayaran == "Berhasil") {
                                        ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-success d-block d-md-none">
                                            <?php
                                            ?>
                                        <?php
                                    } else {
                                        ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-secondary d-block d-md-none">
                                        <?php
                                    }
                                        ?>
                                        <a href="detail_pembayaran.php?kode_pembayaran=<?php echo $kodePembayaran; ?>" class="text-decoration-none">
                                            <div class="row">
                                                <div class="col-12 col-md-3 mb-2 mb-md-0 text-start text-md-center">
                                                </div>
                                                <div class="col-6 col-md-4 mb-2 mb-md-0">
                                                    <span class="text-light text-sm font-weight-bold text-uppercase"><?php echo $kodePembayaran; ?></span>
                                                    <h6 class="text-light text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                                                </div>
                                                <div class="col-6 col-md-3 mb-2 mb-md-0 mt-n1">
                                                    <span class="text-light text-xs"><?php echo $tglBayar; ?></span>
                                                    <h6 class="text-light text-sm font-weight-bold"><?php echo $statusPembayaran; ?></h6>
                                                </div>
                                            </div>
                                        </a>
                                        </li>

                                    <?php
                                    if ($counter >= 10) {
                                        break; // Hentikan loop setelah 10 list
                                    }
                                }
                                    ?>
                            </ul>
                        </div>
                    </div>
                </div>



                <div class="col-lg-12 mt-4" id="sedekahTabel">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center">
                                    <h6 class="mb-0 mx-1">Riwayat Sedekah</h6>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 pb-0">
                            <ul class="list-group">
                                <?php
                                setlocale(LC_TIME, 'id_ID.utf8');
                                $query = "SELECT 
                    sedekah.kode_pembayaran,
                    data_warga.nik,
                    data_warga.nama AS nama_warga,
                    sedekah.tgl_sedekah AS tgl_bayar,
                    '' AS id_tagihan,
                    '' AS nama_tagihan,
                    sedekah.jumlah AS total_jumlah,
                    sedekah.status AS status_pembayaran
                FROM sedekah
                JOIN data_warga ON sedekah.id_warga = data_warga.id_warga
                WHERE sedekah.id_warga = '$id_warga'
                ORDER BY sedekah.kode_pembayaran, sedekah.tgl_sedekah DESC
                LIMIT 10";


                                $result = mysqli_query($conn, $query);

                                $counter = 0;
                                if(mysqli_num_rows($result) == 0){
                                  echo "<span class='mt-n2 text-sm'>Tidak ada riwayat sedekah baru</span>";
                                }

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $idTagihan = $row['id_tagihan'];
                                    $kodePembayaran = $row['kode_pembayaran'];
                                    $nik = $row['nik'];
                                    $namaWarga = $row['nama_warga'];
                                    $tglBayar = strftime("%d %B %Y", strtotime($row['tgl_bayar'])); // Format tanggal dalam bahasa Indonesia

                                    $namaTagihan = $row['nama_tagihan'];
                                    $totalJumlah = $row['total_jumlah'];
                                    $statusPembayaran = $row['status_pembayaran'];
                                    if ($statusPembayaran == "Pending") {
                                        $statusPembayaran = "Menunggu Verifikasi";
                                    } elseif ($statusPembayaran == "Verified") {
                                        $statusPembayaran = "Berhasil";
                                    } elseif ($statusPembayaran == "Tolak") {
                                        $statusPembayaran = "Ditolak";
                                    } else {
                                        $statusPembayaran = "?";
                                    }


                                    $counter++;
                                ?>

                                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                                        <div class="row">
                                            <div class="col-6 col-md-1 mb-2 mb-md-0">
                                                <?php
                                                if ($statusPembayaran == "Menunggu Verifikasi") {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-warning mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">priority_high</i></button>
                                                <?php
                                                } else if ($statusPembayaran == "Berhasil") {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">check</i></button>
                                                <?php
                                                } else {
                                                ?>
                                                    <button class="btn btn-icon-only btn-rounded btn-outline-danger mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">close</i></button>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="col-6 col-md-4 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaWarga; ?></h6>
                                                <span class="text-xs text-uppercase">Kode Pembayaran : <?php echo $kodePembayaran; ?></span>
                                            </div>

                                            <div class="col-6 col-md-3 mb-2 mb-md-0">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                                                <span class="text-xs">Tanggal Pembayaran : <?php echo $tglBayar; ?></span>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $statusPembayaran; ?></h6>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0  text-start text-md-center">
                                                <a class="btn btn-outline-primary btn-sm mb-0" href="detail_pembayaran_sedekah.php?kode_pembayaran=<?php echo $kodePembayaran; ?>">Detail</a>
                                            </div>
                                        </div>
                                    </li>

                                    <?php
                                    if ($statusPembayaran == "Ditolak") {
                                    ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-danger d-block d-md-none">
                                        <?php
                                    } else if ($statusPembayaran == "Berhasil") {
                                        ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-success d-block d-md-none">
                                            <?php
                                            ?>
                                        <?php
                                    } else {
                                        ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between pt-1 px-3 pb-0 mb-2 border-radius-lg bg-gradient-secondary d-block d-md-none">
                                        <?php
                                    }
                                        ?>
                                        <a href="detail_pembayaran_sedekah.php?kode_pembayaran=<?php echo $kodePembayaran; ?>" class="text-decoration-none">
                                            <div class="row">
                                                <div class="col-12 col-md-3 mb-2 mb-md-0 text-start text-md-center">
                                                </div>
                                                <div class="col-6 col-md-4 mb-2 mb-md-0">
                                                    <span class="text-light text-sm font-weight-bold text-uppercase"><?php echo $kodePembayaran; ?></span>
                                                    <h6 class="text-light text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                                                </div>
                                                <div class="col-6 col-md-3 mb-2 mb-md-0 mt-n1">
                                                    <span class="text-light text-xs"><?php echo $tglBayar; ?></span>
                                                    <h6 class="text-light text-sm font-weight-bold"><?php echo $statusPembayaran; ?></h6>
                                                </div>
                                            </div>
                                        </a>
                                        </li>

                                    <?php
                                    if ($counter >= 10) {
                                        break; // Hentikan loop setelah 10 list
                                    }
                                }
                                    ?>
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
        function showPembayaran() {
            document.getElementById("pembayaranTabel").style.display = "block";
            document.getElementById("sedekahTabel").style.display = "none";
        }

        function showSedekah() {
            document.getElementById("pembayaranTabel").style.display = "none";
            document.getElementById("sedekahTabel").style.display = "block";
        }
    </script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>