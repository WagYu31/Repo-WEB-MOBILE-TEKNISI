<?php
include "../../conn.php";
$pageNow = "Data Pembayaran";
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
    $pageNow = "Pembukuan";
    include "nav-top.php";
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-lg-12">
          <div class="row">
            <div class="col-xl-12 mb-xl-0 mb-4">
              <div class="row">

                <div class="col-md-3 col-6">
                  <div class="card my-2">
                    <div class="card-header mx-4 p-3 text-center">
                      <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                        <i class="material-icons opacity-10">account_balance</i>
                      </div>
                    </div>
                    <?php
                    // Mendapatkan tanggal awal dan akhir bulan ini
                    $firstDayOfMonth = date("Y-m-01");
                    $bulanIni = date("M Y");
                    $lastDayOfMonth = date("Y-m-t");

                    // Query untuk menghitung total uang masuk
                    $queryUangMasuk = "SELECT SUM(jumlah) as total_uang_masuk FROM (
                        SELECT jumlah FROM pembayaran WHERE status = 'Verified' AND tgl_bayar BETWEEN '$firstDayOfMonth' AND '$lastDayOfMonth'
                        UNION ALL
                        SELECT jumlah FROM sedekah WHERE status = 'Verified' AND tgl_sedekah BETWEEN '$firstDayOfMonth' AND '$lastDayOfMonth'
                    ) AS combined";
                    $resultUangMasuk = mysqli_query($conn, $queryUangMasuk);
                    $rowUangMasuk = mysqli_fetch_assoc($resultUangMasuk);
                    $totalUangMasuk = $rowUangMasuk['total_uang_masuk'];

                    // Query untuk menghitung total pengeluaran bulan ini
                    $queryPengeluaran = "SELECT SUM(jumlah) as total_pengeluaran FROM pengeluaran WHERE tgl_pengeluaran BETWEEN '$firstDayOfMonth' AND '$lastDayOfMonth'";
                    $resultPengeluaran = mysqli_query($conn, $queryPengeluaran);
                    $rowPengeluaran = mysqli_fetch_assoc($resultPengeluaran);
                    $totalPengeluaran = $rowPengeluaran['total_pengeluaran'];

                    // Menghitung sisa saldo
                    $sisa = $totalUangMasuk - $totalPengeluaran;

                    // Format uang masuk, pengeluaran, dan sisa saldo menjadi mata uang Rupiah
                    $formattedTotalUangMasuk = "Rp " . number_format($totalUangMasuk, 0, ',', '.') . ",00";
                    $formattedTotalPengeluaran = "Rp " . number_format($totalPengeluaran, 0, ',', '.') . ",00";
                    $formattedSisaSaldo = "Rp " . number_format($sisa, 0, ',', '.') . ",00";

                    ?>
                    <div class="card-body pt-0 p-3 text-center">
                      <h6 class="text-center mb-0">Sisa Saldo</h6>
                      <span class="text-xs">Bulan : <?php echo $bulanIni; ?></span>
                      <hr class="horizontal dark my-3">
                      <h5 class="mb-0"><?php echo $formattedSisaSaldo; ?></h5>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="card my-2">
                    <div class="card-header mx-4 p-3 text-center">
                      <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                        <i class="material-icons opacity-10">account_balance</i>
                      </div>
                    </div>
                    <div class="card-body pt-0 p-3 text-center">
                      <h6 class="text-center mb-0">Total Pengeluaran</h6>
                      <span class="text-xs">Bulan : <?php echo $bulanIni; ?></span>
                      <hr class="horizontal dark my-3">
                      <h5 class="mb-0"><?php echo $formattedTotalPengeluaran; ?></h5>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="card my-2">
                    <div class="card-header mx-4 p-3 text-center">
                      <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                        <i class="material-icons opacity-10">account_balance</i>
                      </div>
                    </div>
                    <div class="card-body pt-0 p-3 text-center">
                      <h6 class="text-center mb-0">Total Pemasukan</h6>
                      <span class="text-xs">Bulan : <?php echo $bulanIni; ?></span>
                      <hr class="horizontal dark my-3">
                      <h5 class="mb-0"><?php echo $formattedTotalUangMasuk; ?></h5>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="card my-2">
                    <div class="card-header mx-4 p-3 text-center">
                      <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                        <i class="material-icons opacity-10">account_balance</i>
                      </div>
                    </div>
                    <?php
                    // Query untuk menghitung total uang masuk
                    $queryUangMasuk = "SELECT SUM(jumlah) as total_uang_masuk FROM (
                      SELECT jumlah FROM pembayaran WHERE status = 'Verified'
                      UNION ALL
                      SELECT jumlah FROM sedekah WHERE status = 'Verified'
                  ) AS combined";
                    $resultUangMasuk = mysqli_query($conn, $queryUangMasuk);
                    $rowUangMasuk = mysqli_fetch_assoc($resultUangMasuk);
                    $totalUangMasuk = $rowUangMasuk['total_uang_masuk'];
                    $formattedTotalUangMasuk = "Rp " . number_format($totalUangMasuk, 0, ',', '.') . ",00";
                    ?>
                    <div class="card-body pt-0 p-3 text-center">
                      <h6 class="text-center mb-0">Grand Total Pemasukan</h6>
                      <span class="text-xs">All Day</span>
                      <hr class="horizontal dark my-3">
                      <h5 class="mb-0"><?php echo $formattedTotalUangMasuk; ?></h5>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12">
          <div class="card h-100 mb-4">
            <div class="card-header pb-0 px-3">
              <div class="row">
                <div class="col-md-6">
                  <h6 class="mb-0">Data Transaksi</h6>
                </div>
                <?php
                // Atur locale ke bahasa Indonesia
                $tanggalSaatIni = date('Y-m-d');

                // Hitung hari pertama dari bulan ini
                $hariPertamaBulanIni = date('Y-m-01');

                // Format tanggal dengan nama bulan dalam bahasa Indonesia
                $tanggalSaatIniFormat = formatTanggal('dd MMMM yyyy', $tanggalSaatIni);
                $hariPertamaBulanIniFormat = formatTanggal('dd MMMM yyyy', $hariPertamaBulanIni);

                // Tampilkan rentang tanggal
                echo '<div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center">';
                echo '<i class="material-icons me-2 text-lg">date_range</i>';
                echo '<small>' . $hariPertamaBulanIniFormat . ' - ' . $tanggalSaatIniFormat . '</small>';
                echo '</div>';
                ?>

              </div>
            </div>
            <div class="card-body pt-4 p-3">
              <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Newest</h6>
              <ul class="list-group">

                <?php
                $query = "
                SELECT 'pembayaran' AS jenis, id_warga, NULL AS keterangan, kode_pembayaran, id_tagihan, MAX(tgl_bayar) AS tgl, status, SUM(jumlah) AS jumlah
                FROM pembayaran
                WHERE status <> 'Tolak'
                GROUP BY kode_pembayaran
                UNION
                SELECT 'pengeluaran' AS jenis, NULL AS id_warga, keterangan, NULL AS kode_pembayaran, NULL AS id_tagihan, MAX(tgl_pengeluaran) AS tgl, NULL AS status, SUM(jumlah) AS jumlah
                FROM pengeluaran
                GROUP BY keterangan
                ORDER BY tgl DESC
                LIMIT 20
                ";
                $result = mysqli_query($conn, $query);

                while ($row = mysqli_fetch_assoc($result)) {
                  $jenis = $row["jenis"];
                  $id_warga = $row["id_warga"];
                  $id_tagihan = $row["id_tagihan"];
                  $tgl = $row["tgl"];
                  $status = $row["status"];
                  $jumlah = $row["jumlah"];
                  $keterangan = $row["keterangan"];

                  // Menentukan warna outline berdasarkan jenis dan status (untuk tabel pembayaran)
                  $outlineColor = ($jenis == 'pembayaran' && $status == 'Verified') ? 'success' : 'dark';
                  $iconBayar = ($jenis == 'pembayaran' && $status == 'Verified') ? 'expand_less' : 'priority_high';

                  // Menentukan warna outline berdasarkan jenis (untuk tabel pengeluaran)
                  $outlineColor = ($jenis == 'pengeluaran') ? 'danger' : $outlineColor;
                  $iconBayar = ($jenis == 'pengeluaran') ? 'expand_more' : $iconBayar;
                ?>
                  <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                    <div class="d-flex align-items-center">
                      <button class="btn btn-icon-only btn-rounded btn-outline-<?php echo $outlineColor; ?> mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg"><?php echo $iconBayar; ?></i></button>
                      <div class="d-flex flex-column">
                        <h6 class="mb-1 text-dark text-sm">
                          <?php
                          if ($jenis == 'pembayaran') {
                            // Query untuk mendapatkan nama warga berdasarkan id_warga
                            $queryNamaWarga = "SELECT nama FROM data_warga WHERE id_warga = $id_warga";
                            $resultNamaWarga = mysqli_query($conn, $queryNamaWarga);
                            $rowNamaWarga = mysqli_fetch_assoc($resultNamaWarga);
                            $namaWarga = $rowNamaWarga['nama'];
                            echo $namaWarga;
                          } else {
                            // Tampilkan keterangan untuk jenis pengeluaran
                            echo $keterangan;
                          }
                          ?>
                        </h6>
                        <span class="text-xs"><?php echo formatTanggal('dd MMMM yyyy', $tgl); ?></span>
                      </div>
                    </div>
                    <div class="d-flex align-items-center text-<?php echo $outlineColor; ?> text-gradient text-sm font-weight-bold">
                      <?php
                      if ($jenis == 'pembayaran') {
                        if ($status == 'Verified') {
                          echo '+ Rp ' . number_format($jumlah, 2);
                        } else {
                          echo '! Rp ' . number_format($jumlah, 2);
                        }
                      } else {
                        // Jenis adalah 'pengeluaran'
                        echo '- Rp ' . number_format($jumlah, 2);
                      }
                      ?>
                    </div>
                  </li>

                <?php
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

  <!--   Core JS Files   -->
  <?php
  include "js-include.php";
  ?>

  <script>
    function submitDeleteForm(idBank) {
      if (confirm("Apakah Anda yakin ingin menghapus rekening bank ini?")) {
        document.getElementById('deleteForm_' + idBank).submit();
      }
    }
  </script>

  <script>
    function populateEditModal(idBank, namaBank, nomorRekening, atasNama) {
      document.getElementById('editBankSelect').value = idBank; // Set the selected value
      document.getElementById('atasNama').value = atasNama;
      document.getElementById('editNomorRekening').value = nomorRekening;
    }
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
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../../assets/js/material-dashboard.min.js?v=3.1.0"></script>

  <!-- Tambahkan script ini di bagian head atau sebelum penutup tag body -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
    $(document).ready(function() {
      $(".toggle-status").click(function() {
        var button = $(this);

        var idTagihan = button.data("id");
        var currentStatus = button.data("status");
        var newStatus = (currentStatus == "Y") ? "N" : "Y";

        $.ajax({
          type: "POST",
          url: "update_status_tagihan.php", // Gantilah dengan URL yang sesuai
          data: {
            idTagihan: idTagihan,
            newStatus: newStatus
          },
          success: function(response) {
            if (response == "success") {
              // Reload halaman secara langsung
              location.reload();
            }
          }
        });
      });
    });
  </script>

  <script>
    // Ambil formulir tambah tagihan
    const formTambahTagihan = document.getElementById('formTambahTagihan');

    // Tambahkan event listener untuk event submit
    formTambahTagihan.addEventListener('submit', function(e) {
      e.preventDefault(); // Mencegah aksi bawaan formulir (submit)

      // Kirim data formulir menggunakan AJAX
      fetch(this.action, {
          method: this.method,
          body: new FormData(this),
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Jika penyisipan berhasil, tutup modal dan lakukan reload halaman
            alert(data.message);
            $('#tambahTagihanModal').modal('hide');
            location.reload();
          } else {
            // Jika ada kesalahan, tampilkan pesan kesalahan
            alert(data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    });


    function formatRupiah(input) {
      // Remove non-numeric characters
      var rawValue = input.value.replace(/[^\d]/g, '');

      // Format as currency for display
      var formattedValue = 'Rp ' + new Intl.NumberFormat('id-ID').format(rawValue);

      // Update the displayed value
      input.value = formattedValue;

      // Update the hidden input with the raw numeric value
      document.getElementById('jumlahTagihanHidden').value = rawValue;
    }
  </script>



</body>

</html>