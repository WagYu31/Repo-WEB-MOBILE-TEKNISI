<?php
include "../../conn.php";
include "session.php";
    $pageNow = "Verifikasi Pembayaran";
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
    include "nav-top.php";
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
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
                  $tglBayar = formatTanggal('dd MMMM yyyy', $row['tgl_bayar']); // Format tanggal dalam bahasa Indonesia

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