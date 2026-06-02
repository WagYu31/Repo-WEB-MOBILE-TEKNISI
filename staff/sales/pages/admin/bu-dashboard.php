<?php
include "../../conn.php";
include "session.php";
$pageNow = "Dashboard";
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

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
    $todayDate = strftime('%d %B %Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">person</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Jumlah Kartu Keluarga</p>
                <?php

                $query = "SELECT COUNT(*) as total_kk FROM kartu_keluarga";
                $result = mysqli_query($conn, $query);

                if ($result) {
                  $row = mysqli_fetch_assoc($result);
                  $jumlah_kk = $row['total_kk'];
                } else {
                  // Handle error jika query tidak berhasil
                  $jumlah_kk = 0;
                }

                ?>
                <h4 class="mb-0"><?php echo $jumlah_kk; ?></h4>
              </div>

            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">person</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Jumlah Warga</p>
                <?php
                $query = "SELECT COUNT(*) as total_warga FROM data_warga";
                $result = mysqli_query($conn, $query);

                if ($result) {
                  $row = mysqli_fetch_assoc($result);
                  $jumlahWarga = $row['total_warga'];
                } else {
                  // Handle error jika query tidak berhasil
                  $jumlahWarga = 0;
                }

                ?>
                <h4 class="mb-0"><?php echo $jumlahWarga; ?></h4>
              </div>

            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Sisa Saldo</p>
                <?php
                $query = "SELECT SUM(jumlah) as total_uang_masuk FROM (
                  SELECT jumlah FROM pembayaran WHERE status = 'Verified'
                  UNION ALL
                  SELECT jumlah FROM sedekah WHERE status = 'Verified'
                ) AS combined";

                $result = mysqli_query($conn, $query);
                $row = mysqli_fetch_assoc($result);
                $totalUangMasuk = $row['total_uang_masuk'];

                $queryPengeluaran = "SELECT SUM(jumlah) as total_pengeluaran FROM pembayaran";
                $resultPengeluaran = mysqli_query($conn, $queryPengeluaran);

                while ($rowPengeluaran = mysqli_fetch_assoc($resultPengeluaran)) {
                  $jumlahPengeluaran = $rowPengeluaran['total_pengeluaran'];
                  $jumlahPengeluaranRupiah = "Rp " . number_format($jumlahPengeluaran, 0, ',', '.') . ",00";
                }

                $sisa = $totalUangMasuk - $jumlahPengeluaran;
                $formattedSisaSaldo = "Rp " . number_format($sisa, 0, ',', '.') . ",00";
                ?>
                <h4 class="mb-0"><?php echo $formattedSisaSaldo; ?></h4>
              </div>

            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Dana Terpakai</p>
                <h4 class="mb-0"><?php echo $jumlahPengeluaranRupiah; ?></h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0">Diperbarui pada : <span class="text-success text-sm font-weight-bolder"><?php echo $todayDate; ?></span></p>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
          <div class="card z-index-2 ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                <div class="chart px-3">
                  <h5 class="text-light text-bold">Iuran Wajib</h5>
                </div>
              </div>
            </div>
            <?php
            $currentMonth = date('m');
            $currentYear = date('Y');
            function getIndonesianMonthName($month)
            {
              $monthNames = array(
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
              );

              return $monthNames[$month];
            }

            $currentMonth = date('n');
            $currentMonthText = getIndonesianMonthName($currentMonth);

            $query = "SELECT SUM(p.jumlah) as total_iuran_wajib
              FROM pembayaran p
              JOIN tagihan t ON p.id_tagihan = t.id_tagihan
              WHERE MONTH(p.tgl_bayar) = $currentMonth
                AND YEAR(p.tgl_bayar) = $currentYear
                AND t.jenis = 'Wajib'";

            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $totalIuranWajib = $row['total_iuran_wajib'];

            $formattedTotalIuranWajib = "Rp " . number_format($totalIuranWajib, 0, ',', '.') . ",00";
            ?>

            <div class="card-body">
              <h6 class="mb-0"><?php echo $formattedTotalIuranWajib; ?></h6>
              <p class="text-sm">Dana untuk pembayaran keamanan dan sampah</p>
              <hr class="dark horizontal">
              <div class="d-flex">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"><?php echo $currentMonthText . " " . $currentYear; ?></p>
              </div>
            </div>

          </div>
        </div>
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
          <div class="card z-index-2  ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-success shadow-success border-radius-lg py-3 pe-1">
                <div class="chart text-light px-3">
                  <h5 class="text-light text-bold">Iuran Lain (Tidak Wajib)</h5>
                </div>
              </div>
            </div>

            <?php

            $query = "SELECT SUM(p.jumlah) as total_iuran_tidak_wajib
              FROM pembayaran p
              JOIN tagihan t ON p.id_tagihan = t.id_tagihan
              WHERE MONTH(p.tgl_bayar) = $currentMonth
                AND YEAR(p.tgl_bayar) = $currentYear
                AND t.jenis = 'Tidak Wajib'";

            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $totalIuranTidakWajib = $row['total_iuran_tidak_wajib'];

            $formattedTotalIuranTidakWajib = "Rp " . number_format($totalIuranTidakWajib, 0, ',', '.') . ",00";
            ?>

            <div class="card-body">
              <h6 class="mb-0 "><?php echo $formattedTotalIuranTidakWajib; ?></h6>
              <p class="text-sm ">Digunakan untuk kegiatan RT atau membantu warga</p>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"><?php echo $currentMonthText . " " . $currentYear; ?></p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 mt-4 mb-3">
          <div class="card z-index-2 ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                <div class="chart text-light px-3">
                  <h5 class="text-light text-bold">Sedekah</h5>
                </div>
              </div>
            </div>
            <?php
            $query = "SELECT SUM(jumlah) AS total_sedekah FROM sedekah
                WHERE status = 'Verified'
                AND MONTH(tgl_sedekah) = $currentMonth
                AND YEAR(tgl_sedekah) = $currentYear";

            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $totalSedekah = $row['total_sedekah'];

            $formattedTotalSedekah = "Rp " . number_format($totalSedekah, 0, ',', '.') . ",00";


            // Menghitung jumlah sedekah yang statusnya Pending
            $queryPending = "SELECT SUM(jumlah) AS total_pending FROM sedekah
                WHERE status = 'Pending'
                AND MONTH(tgl_sedekah) = $currentMonth
                AND YEAR(tgl_sedekah) = $currentYear";

            $resultPending = mysqli_query($conn, $queryPending);
            $rowPending = mysqli_fetch_assoc($resultPending);
            $totalPending = $rowPending['total_pending'];

            // Format jumlah menjadi mata uang
            $formattedTotalPending = "Rp " . number_format($totalPending, 0, ',', '.') . ",00";


            ?>
            <div class="card-body">
              <h6 class="mb-0 "><?php echo $formattedTotalSedekah; ?></h6>
              <p class="text-sm ">Dana belum diverifikasi : <span class="text-danger text-sm font-weight-bolder"><?php echo $formattedTotalPending;?></span></p>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm"><?php echo $currentMonthText . " " . $currentYear; ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row mb-4">
        <div class="col-lg-12">
          <div class="card h-100 py-3">
            <div class="card-header pb-0 p-3">
              <div class="row">
                <div class="col-12 d-flex align-items-center">
                  <h6 class="mb-0 mx-1">Verifikasi Pembayaran</h6>
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
                GROUP BY pembayaran.kode_pembayaran, data_warga.nik, data_warga.nama, pembayaran.tgl_bayar, pembayaran.status
                ORDER BY pembayaran.kode_pembayaran, pembayaran.tgl_bayar DESC LIMIT 10";

                $result = mysqli_query($conn, $query);

                $counter = 0;

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
                      <div class="col-6 col-md-4 mb-2 mb-md-0">
                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaWarga; ?></h6>
                        <span class="text-xs text-uppercase">Kode Pembayaran : <?php echo $kodePembayaran; ?></span>
                      </div>

                      <div class="col-6 col-md-3 mb-2 mb-md-0">
                        <h6 class="mb-1 text-dark font-weight-bold text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                        <span class="text-xs">Tanggal Pembayaran : <?php echo $tglBayar; ?></span>
                      </div>

                      <div class="col-6 col-md-3 mb-2 mb-md-0 text-left text-md-center">
                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $statusPembayaran; ?></h6>
                      </div>

                      <div class="col-6 col-md-2 mb-2 mb-md-0  text-start text-md-center">
                        <a class="btn btn-outline-primary btn-sm mb-0" href="detail_pembayaran.php?kode_pembayaran=<?php echo $kodePembayaran; ?>">Detail</a>
                      </div>
                    </div>
                  </li>


                  <li class="list-group-item border-0 d-flex flex-column justify-content-between p-3 mb-2 border-radius-lg bg-gradient-secondary d-block d-md-none">
                    <a href="detail_pembayaran.php?kode_pembayaran=<?php echo $kodePembayaran; ?>" class="text-decoration-none">
                      <div class="row">
                        <div class="col-12 col-md-3 mb-2 mb-md-0 text-start text-md-center">
                          <h6 class="mb-1 text-light text-sm"><?php echo $statusPembayaran; ?></h6>
                        </div>
                        <div class="col-6 col-md-4 mb-2 mb-md-0">
                          <h6 class="mb-1 text-light font-weight-bold text-sm"><?php echo $namaWarga; ?></h6>
                          <h6 class="mb-1 text-light font-weight-bold text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></h6>
                        </div>

                        <div class="col-6 col-md-3 mb-2 mb-md-0 mt-n1">
                          <span class="text-light text-xs text-uppercase font-weight-bold">Kode : <?php echo $kodePembayaran; ?></span><br>
                          <span class="text-light text-xs"><?php echo $tglBayar; ?></span>
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







        <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
          <div class="card">
            <div class="card-header pb-0">
              <div class="row">
                <div class="col-lg-6 col-7">
                  <h6>Projects</h6>
                  <p class="text-sm mb-0">
                    <i class="fa fa-check text-info" aria-hidden="true"></i>
                    <span class="font-weight-bold ms-1">30 done</span> this month
                  </p>
                </div>
                <div class="col-lg-6 col-5 my-auto text-end">
                  <div class="dropdown float-lg-end pe-4">
                    <a class="cursor-pointer" id="dropdownTable" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="fa fa-ellipsis-v text-secondary"></i>
                    </a>
                    <ul class="dropdown-menu px-2 py-3 ms-sm-n4 ms-n5" aria-labelledby="dropdownTable">
                      <li><a class="dropdown-item border-radius-md" href="javascript:;">Action</a></li>
                      <li><a class="dropdown-item border-radius-md" href="javascript:;">Another action</a></li>
                      <li><a class="dropdown-item border-radius-md" href="javascript:;">Something else here</a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Companies</th>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Members</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Budget</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Completion</th>
                    </tr>
                  </thead>
                  <tbody>


                    <?php
                    $queryBank = "SELECT * FROM bank_account";
                    $resultBank = mysqli_query($conn, $queryBank);
                    while ($rowBank = mysqli_fetch_assoc($resultBank)) {
                      $namaBank = $rowBank['nama_bank'];
                      $nomor_rekening = $rowBank['nomor_rekening'];
                      $logoBank = $rowBank['logo'];
                    ?>
                      <tr>
                        <td>
                          <div class="d-flex px-2 py-1">
                            <div>
                              <img src="../../assets/img/bank/<?php echo $logoBank; ?>" class="avatar avatar-sm me-3" alt="xd">
                            </div>
                            <div class="d-flex flex-column justify-content-center">
                              <h6 class="mb-0 text-sm"><?php echo $namaBank; ?></h6>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="avatar-group mt-2">
                            <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ryan Tompson">
                              <img src="../../assets/img/team-1.jpg" alt="team1">
                            </a>
                            <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Romina Hadid">
                              <img src="../../assets/img/team-2.jpg" alt="team2">
                            </a>
                            <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Alexander Smith">
                              <img src="../../assets/img/team-3.jpg" alt="team3">
                            </a>
                            <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Jessica Doe">
                              <img src="../../assets/img/team-4.jpg" alt="team4">
                            </a>
                          </div>
                        </td>
                        <td class="align-middle text-center text-sm">
                          <span class="text-xs font-weight-bold"> $14,000 </span>
                        </td>
                        <td class="align-middle">
                          <div class="progress-wrapper w-75 mx-auto">
                            <div class="progress-info">
                              <div class="progress-percentage">
                                <span class="text-xs font-weight-bold">60%</span>
                              </div>
                            </div>
                            <div class="progress">
                              <div class="progress-bar bg-gradient-info w-60" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php
                    }
                    ?>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card h-100">
            <div class="card-header pb-0">
              <h6>Orders overview</h6>
              <p class="text-sm">
                <i class="fa fa-arrow-up text-success" aria-hidden="true"></i>
                <span class="font-weight-bold">24%</span> this month
              </p>
            </div>
            <div class="card-body p-3">
              <div class="timeline timeline-one-side">
                <div class="timeline-block mb-3">
                  <span class="timeline-step">
                    <i class="material-icons text-success text-gradient">notifications</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">$2400, Design changes</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">22 DEC 7:20 PM</p>
                  </div>
                </div>
                <div class="timeline-block mb-3">
                  <span class="timeline-step">
                    <i class="material-icons text-danger text-gradient">code</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">New order #1832412</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">21 DEC 11 PM</p>
                  </div>
                </div>
                <div class="timeline-block mb-3">
                  <span class="timeline-step">
                    <i class="material-icons text-info text-gradient">shopping_cart</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">Server payments for April</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">21 DEC 9:34 PM</p>
                  </div>
                </div>
                <div class="timeline-block mb-3">
                  <span class="timeline-step">
                    <i class="material-icons text-warning text-gradient">credit_card</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">New card added for order #4395133</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">20 DEC 2:20 AM</p>
                  </div>
                </div>
                <div class="timeline-block mb-3">
                  <span class="timeline-step">
                    <i class="material-icons text-primary text-gradient">key</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">Unlock packages for development</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">18 DEC 4:54 AM</p>
                  </div>
                </div>
                <div class="timeline-block">
                  <span class="timeline-step">
                    <i class="material-icons text-dark text-gradient">payments</i>
                  </span>
                  <div class="timeline-content">
                    <h6 class="text-dark text-sm font-weight-bold mb-0">New order #9583120</h6>
                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">17 DEC</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php
      include "../footer.php";
      ?>
    </div>
  </main>
  <div class="fixed-plugin d-none d-md-block">
    <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
      <i class="material-icons py-2">settings</i>
    </a>
  </div>
  <?php
  include "js-include.php";
  ?>
  <script>
    var ctx = document.getElementById("chart-bars").getContext("2d");

    new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["M", "T", "W", "T", "F", "S", "S"],
        datasets: [{
          label: "Sales",
          tension: 0.4,
          borderWidth: 0,
          borderRadius: 4,
          borderSkipped: false,
          backgroundColor: "rgba(255, 255, 255, .8)",
          data: [50, 20, 10, 22, 50, 10, 40],
          maxBarThickness: 6
        }, ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              suggestedMin: 0,
              suggestedMax: 500,
              beginAtZero: true,
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
              color: "#fff"
            },
          },
          x: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });


    var ctx2 = document.getElementById("chart-line").getContext("2d");

    new Chart(ctx2, {
      type: "line",
      data: {
        labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Mobile apps",
          tension: 0,
          borderWidth: 0,
          pointRadius: 5,
          pointBackgroundColor: "rgba(255, 255, 255, .8)",
          pointBorderColor: "transparent",
          borderColor: "rgba(255, 255, 255, .8)",
          borderColor: "rgba(255, 255, 255, .8)",
          borderWidth: 4,
          backgroundColor: "transparent",
          fill: true,
          data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
          maxBarThickness: 6

        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });

    var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

    new Chart(ctx3, {
      type: "line",
      data: {
        labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Mobile apps",
          tension: 0,
          borderWidth: 0,
          pointRadius: 5,
          pointBackgroundColor: "rgba(255, 255, 255, .8)",
          pointBorderColor: "transparent",
          borderColor: "rgba(255, 255, 255, .8)",
          borderWidth: 4,
          backgroundColor: "transparent",
          fill: true,
          data: [50, 40, 300, 220, 500, 250, 400, 230, 500],
          maxBarThickness: 6

        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              padding: 10,
              color: '#f8f9fa',
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });
  </script>
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
</body>

</html>