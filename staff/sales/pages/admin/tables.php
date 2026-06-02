<?php
include "../../conn.php";
$pageNow = "Data Warga";
include "session.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <?php
  include "head.php";
  ?>
      <script>
        $(document).ready(function() {
            $(".edit-btn").click(function() {
                // Get the nik value from the data-nik attribute
                var nik = $(this).attr("data-nik");

                // Redirect to edit_data_warga.php with the nik parameter
                window.location.href = "detail_data_warga.php?nik=" + nik;
            });
        });
    </script>
</head>

<body class="g-sidenav-show  bg-gray-200">

<?php
    include "cek-menu.php";
    ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Data Warga RT 12 RW 4 Tanah Merdeka</h6>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Warga</th>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Keluarga</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis Kelamin</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pekerjaan / Pendidikan</th>
                      <th class="text-secondary opacity-7"></th>
                    </tr>
                  </thead>
                  <tbody>


                    <?php

                    $query = "SELECT data_warga.*, kartu_keluarga.* FROM data_warga JOIN kartu_keluarga ON data_warga.no_kk = kartu_keluarga.no_kk";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                      $nama = $row["nama"];
                      $nik = $row["nik"];
                      $no_kk = $row["no_kk"];
                      $jenis_kelamin = $row["jenis_kelamin"];
                      $tempat_lahir = $row["tempat_lahir"];
                      $tanggal_lahir = $row["tanggal_lahir"];
                      $kepala_keluarga = $row["kepala_keluarga"];
                      $pendidikan = $row["pendidikan"];
                      $pekerjaan = $row["pekerjaan"];
                      $nomor_telepon = $row["nomor_telepon"];
                      $email = $row["email"];
                      $rt = $row["rt"];
                      $rw = $row["rw"];
                      $alamat = $row["alamat"];
                      $kecamatan = $row["kecamatan"];
                      $kelurahan = $row["kelurahan"];
                      $kota = $row["kota"];
                      $kode_pos = $row["kode_pos"];
                      $status = $row["status"];
                      $hubungan = $row["status_hubungan_dalam_keluarga"];
                      $agama = $row["agama"];
                      $kewarganegaraan = $row["kewarganegaraan"];
                      $domisili = $row["domisili_sekarang"];


                    ?>


                      <tr>
                        <td>
                          <div class="d-flex px-2 py-1">
                            <div>
                              <?php
                              if ($jenis_kelamin == 'Laki-Laki') {
                              ?>
                                <img src="../../assets/img/man.png" class="avatar avatar-sm me-3 border-radius-lg" alt="user1">
                              <?php
                              } elseif ($jenis_kelamin == 'Perempuan') {
                              ?>
                                <img src="../../assets/img/female.png" class="avatar avatar-sm me-3 border-radius-lg" alt="user1">
                              <?php
                              } else {
                              ?>
                                <img src="../../assets/img/user.png" class="avatar avatar-sm me-3 border-radius-lg" alt="user1">
                              <?php
                              }
                              ?>

                            </div>
                            <div class="d-flex flex-column justify-content-center">
                              <h6 class="mb-0 text-sm"><?php echo $nama; ?></h6>
                              <p class="text-xs text-secondary mb-0">NIK : <?php echo $nik; ?></p>
                            </div>
                          </div>
                        </td>
                        <td>
                          <p class="text-xs font-weight-bold mb-0"><?php echo $hubungan; ?></p>
                          <p class="text-xs text-secondary mb-0">No. KK : <?php echo $no_kk; ?></p>
                        </td>
                        <td>
                          <span class="text-secondary text-xs font-weight-bold"><?php echo $jenis_kelamin; ?></span>
                        </td>
                        <td>
                          <p class="text-xs font-weight-bold mb-0"><?php echo $pekerjaan; ?></p>
                          <p class="text-xs text-secondary mb-0"><?php echo $pendidikan; ?></p>
                        </td>
                        <td class="align-middle">
                          <button type="button" class="btn btn-link text-secondary font-weight-bold text-xs edit-btn" data-nik="<?php echo $nik; ?>">
                              Detail
                          </button>
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
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>