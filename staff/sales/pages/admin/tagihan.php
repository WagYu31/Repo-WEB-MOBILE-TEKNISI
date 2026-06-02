<?php
include "../../conn.php";
include "session.php";
    $pageNow = "Tagihan";
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
        <div class="col-lg-8">
          <div class="row">
            <div class="col-xl-12 mb-xl-0 mb-4">
              <div class="row">

                <?php
                $queryBank = "SELECT * FROM bank_account WHERE nomor_rekening != 'NULL'";
                $resultBank = mysqli_query($conn, $queryBank);
                while ($rowBank = mysqli_fetch_assoc($resultBank)) {
                  $idBank = $rowBank['id_bank'];
                  $namaBank = $rowBank['nama_bank'];
                  $nomor_rekening = $rowBank['nomor_rekening'];
                  $logoBank = $rowBank['logo'];

                  // Query untuk menghitung total pembayaran berdasarkan nama bank
                  $queryTotalPayment = "SELECT SUM(jumlah) as total_jumlah FROM pembayaran WHERE id_bank = '$idBank' AND status = 'Verified'";
                  $resultTotalPayment = mysqli_query($conn, $queryTotalPayment);
                  $rowTotalPayment = mysqli_fetch_assoc($resultTotalPayment);
                  $jumlahPerBank = $rowTotalPayment['total_jumlah'];

                  // Jika $jumlahPerBank NULL, ubah menjadi "Rp 0"
                  if ($jumlahPerBank === null) {
                    $jumlahPerBank = 0;
                  }
                ?>
                  <div class="col-md-3 col-6">
                    <div class="card my-2">
                      <div class="card-header mx-4 p-3 text-center">
                        <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                          <i class="material-icons opacity-10">account_balance</i>
                        </div>
                      </div>
                      <div class="card-body pt-0 p-3 text-center">
                        <h6 class="text-center mb-0"><?php echo $namaBank; ?></h6>
                        <span class="text-xs"><?php echo $nomor_rekening; ?></span>
                        <hr class="horizontal dark my-3">
                        <h5 class="mb-0">Rp <?php echo number_format($jumlahPerBank, 0, ',', '.') . ",00"; ?></h5>
                      </div>
                    </div>
                  </div>
                <?php
                }
                ?>

              </div>



            </div>



            <div class="col-md-12 mb-lg-0 mb-4">
              <div class="card mt-4 py-3">
                <div class="card-header pb-0 p-3">
                  <div class="row">
                    <div class="col-6 d-flex align-items-center">
                      <h6 class="mb-0">Metode Pembayaran</h6>
                    </div>
                    <div class="col-6 text-end">
                      <a class="btn bg-gradient-dark mb-0" href="#" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="material-icons text-sm">add</i>&nbsp;&nbsp;Tambah Metode Pembayaran
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card-body p-3">
                  <div class="row">
                    <?php
                    $queryBk = "SELECT * FROM bank_account WHERE nomor_rekening != 'NULL'";
                    $resultBk = mysqli_query($conn, $queryBk);
                    while ($rowBk = mysqli_fetch_assoc($resultBk)) {
                      $namaBank = $rowBk['nama_bank'];
                      $nomor_rekening = $rowBk['nomor_rekening'];
                      $logoBk = $rowBk['logo'];
                      $atas_nama = $rowBk['atas_nama'];
                    ?>
                      <!-- Inside your while loop -->
                      <div class="col-md-6 mb-md-0 mb-0 my-4">
                        <form id="deleteForm_<?php echo $rowBk['id_bank']; ?>" action="delete_bank_account.php" method="POST" style="display: inline;">
                          <input type="hidden" name="idBank" value="<?php echo $rowBk['id_bank']; ?>">
                          <div class="card card-body border card-plain border-radius-lg d-flex align-items-center flex-row">
                            <img class="w-10 me-3 mb-0" src="../../assets/img/bank/2/<?php echo $logoBk; ?>" alt="logo">
                            <div class="col-7 col-md-7">
                              <h6 class="mb-0"><?php echo $nomor_rekening; ?></h6>
                              <span class="mb-0 text-xs"><?php echo $atas_nama; ?></span>
                            </div>
                            <div class="ms-auto">
                              <!-- Inside your while loop -->
                              <?php
                              if ($nomor_rekening == "TUNAI") {
                                echo "<p style='font-size:12px;margin-top:15px;' class='ms-auto'>Permanen</p>";
                              } else {
                              ?>
                                <i class="material-icons text-dark cursor-pointer" data-bs-placement="top" title="Edit Rekening" data-bs-target="#editModal" data-bs-toggle="modal" onclick="populateEditModal('<?php echo $rowBk['id_bank']; ?>', '<?php echo $rowBk['nama_bank']; ?>', '<?php echo $rowBk['nomor_rekening']; ?>', '<?php echo $rowBk['atas_nama']; ?>')">edit</i>
                                <button type="button" class="material-icons text-dark cursor-pointer" style="background-color:white;border:none;" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus Rekening" onclick="submitDeleteForm('<?php echo $rowBk['id_bank']; ?>')">delete</button>
                              <?php
                              }
                              ?>
                            </div>
                          </div>
                        </form>
                      </div>

                    <?php
                    }
                    ?>
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
                  <h6 class="mb-0">Tagihan</h6>
                </div>
                <div class="col-6 text-end">
                  <button class="btn btn-outline-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#tambahTagihanModal">Tambah Tagihan</button>
                </div>

              </div>
            </div>
            <div class="card-body p-3 pb-0">
              <ul class="list-group">
                <?php
                $queryTagihan = "SELECT * FROM tagihan ORDER BY tgl_tagihan DESC LIMIT 10";
                $resultTagihan = mysqli_query($conn, $queryTagihan);

                $counter = 0;

                while ($rowTagihan = mysqli_fetch_assoc($resultTagihan)) {
                  $idTagihan = $rowTagihan['id_tagihan'];
                  $namaTagihan = $rowTagihan['nama_tagihan'];
                  $jumlahTagihan = $rowTagihan['jumlah'];

                  // Mengubah nilai ke format nominal rupiah
                  $jumlahTagihanRupiah = "Rp " . number_format($jumlahTagihan, 0, ',', '.') . ",00";

                  $jenisTagihan = $rowTagihan['jenis'];
                  $statusTagihan = $rowTagihan['status'];
                  $statusClass = ($statusTagihan == "Y") ? "aktif" : "tidak";
                  $statusTagihanSekarang = ($statusTagihan == "Y") ? "On" : "Off";
                  $counter++;
                ?>

                  <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                    <div class="d-flex flex-column col-5 col-md-6">
                      <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaTagihan; ?></h6>
                      <span class="text-xs"><?php echo $jenisTagihan; ?></span>
                    </div>

                    <div class="d-flex align-items-center text-sm col-4 col-md-3">
                      <?php echo $jumlahTagihanRupiah; ?>
                    </div>

                    <div class="d-flex align-items-center text-sm col-3">
                      <button class="btn btn-link text-dark text-sm mb-0 px-0 ms-4 toggle-status" data-id="<?php echo $idTagihan; ?>" data-status="<?php echo $statusTagihan; ?>">
                        <span class="status-indicator <?php echo $statusClass; ?>"><i class="material-icons text-lg position-relative me-1">circle</i> <?php echo $statusTagihanSekarang; ?></span>
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
      <div class="row">
        <div class="col-md-7 mt-4">
          <div class="card">
            <div class="card-header pb-0 px-3">
              <h6 class="mb-0">Billing Information</h6>
            </div>
            <div class="card-body pt-4 p-3">
              <ul class="list-group">

                <?php
                $queryBk2 = "SELECT * FROM bank_account WHERE nomor_rekening != 'NULL'";
                $resultBk2 = mysqli_query($conn, $queryBk2);
                while ($rowBk2 = mysqli_fetch_assoc($resultBk2)) {
                  $namaBank2 = $rowBk2['nama_bank'];
                  $atas_nama  = $rowBk2['atas_nama'];
                  $nomor_rekening2 = $rowBk2['nomor_rekening'];
                  $logoBk2 = $rowBk2['logo'];
                ?>

                  <li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg">
                    <div class="d-flex flex-column">
                      <h6 class="mb-3 text-sm"><?php echo $namaBank2; ?></h6>
                      <span class="mb-2 text-xs">Atas Nama : <span class="text-dark font-weight-bold ms-sm-2"><?php echo $atas_nama; ?></span></span>
                      <!-- <span class="mb-2 text-xs">Email Address: <span class="text-dark ms-sm-2 font-weight-bold">oliver@burrito.com</span></span> -->
                      <span class="text-xs">Nomor Rekening : <span class="text-dark ms-sm-2 font-weight-bold"><?php echo $nomor_rekening2; ?></span></span>
                    </div>
                    <div class="ms-auto text-end">
                      <a class="btn btn-link text-danger text-gradient px-3 mb-0" href="javascript:;"><i class="material-icons text-sm me-2">delete</i>Delete</a>
                      <a class="btn btn-link text-dark px-3 mb-0" href="javascript:;"><i class="material-icons text-sm me-2">edit</i>Edit</a>
                    </div>
                  </li>
                <?php
                }
                ?>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-5 mt-4">
          
          <div class="card h-100 mb-4">
            <div class="card-header pb-0 px-3">
              <div class="row">
                <div class="col-md-6">
                  <h6 class="mb-0">Your Transaction's</h6>
                </div>
                <div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center">
                  <i class="material-icons me-2 text-lg">date_range</i>
                  <small>23 - 30 March 2020</small>
                </div>
              </div>
            </div>
            <div class="card-body pt-4 p-3">
              <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Newest</h6>
              <ul class="list-group">

                <?php
                $query = "SELECT * FROM pembayaran";
                $result = mysqli_query($conn, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                  $id_warga = $row["id_warga"];
                  $id_tagihan = $row["id_tagihan"];
                  $tgl_bayar = $row["tgl_bayar"];
                  $jumlah = $row["jumlah"];
                ?>
                  <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                    <div class="d-flex align-items-center">
                      <button class="btn btn-icon-only btn-rounded btn-outline-danger mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">expand_more</i></button>
                      <div class="d-flex flex-column">
                        <h6 class="mb-1 text-dark text-sm"><?php echo $id_warga; ?></h6>
                        <span class="text-xs"><?php echo $tgl_bayar; ?></span>
                      </div>
                    </div>
                    <div class="d-flex align-items-center text-danger text-gradient text-sm font-weight-bold">
                      <?php echo $jumlah; ?>
                    </div>
                  </li>
                <?php
                }
                ?>


              </ul>
              <h6 class="text-uppercase text-body text-xs font-weight-bolder my-3">Yesterday</h6>
              <ul class="list-group">
                <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                  <div class="d-flex align-items-center">
                    <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">expand_less</i></button>
                    <div class="d-flex flex-column">
                      <h6 class="mb-1 text-dark text-sm">Stripe</h6>
                      <span class="text-xs">26 March 2020, at 13:45 PM</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center text-success text-gradient text-sm font-weight-bold">
                    + $ 750
                  </div>
                </li>
                <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                  <div class="d-flex align-items-center">
                    <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">expand_less</i></button>
                    <div class="d-flex flex-column">
                      <h6 class="mb-1 text-dark text-sm">HubSpot</h6>
                      <span class="text-xs">26 March 2020, at 12:30 PM</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center text-success text-gradient text-sm font-weight-bold">
                    + $ 1,000
                  </div>
                </li>
                <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                  <div class="d-flex align-items-center">
                    <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">expand_less</i></button>
                    <div class="d-flex flex-column">
                      <h6 class="mb-1 text-dark text-sm">Creative Tim</h6>
                      <span class="text-xs">26 March 2020, at 08:30 AM</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center text-success text-gradient text-sm font-weight-bold">
                    + $ 2,500
                  </div>
                </li>
                <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                  <div class="d-flex align-items-center">
                    <button class="btn btn-icon-only btn-rounded btn-outline-dark mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">priority_high</i></button>
                    <div class="d-flex flex-column">
                      <h6 class="mb-1 text-dark text-sm">Webflow</h6>
                      <span class="text-xs">26 March 2020, at 05:00 AM</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center text-dark text-sm font-weight-bold">
                    Pending
                  </div>
                </li>
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




  <!-- MODAL POP UP -->

  <!-- Tambah Tagihan Modal -->
  <div class="modal fade" id="tambahTagihanModal" tabindex="-1" aria-labelledby="tambahTagihanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tambahTagihanModalLabel">Tambah Tagihan Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Form Tambah Tagihan -->
          <form id="formTambahTagihan" action="proses_tambah_tagihan.php" method="post">
            <div class="mb-3">
              <label for="namaTagihan" class="form-label">Nama Tagihan</label>
              <input type="text" class="form-control border p-2" id="namaTagihan" name="namaTagihan" required>
            </div>
            <div class="mb-3">
              <label for="jumlahTagihan" class="form-label">Jumlah Tagihan</label>
              <div class="input-group">
                <!-- <span class="input-group-text">Rp</span> -->
                <input type="hidden" id="jumlahTagihanHidden" name="jumlahTagihan" required>
                <input type="text" class="form-control border p-2" id="jumlahTagihan" oninput="formatRupiah(this)" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="jenisTagihan" class="form-label">Jenis Tagihan</label>
              <select class="form-select border p-2" id="jenisTagihan" name="jenisTagihan" required>
                <option value="wajib">Wajib</option>
                <option value="tidak">Tidak Wajib</option>
              </select>
            </div>
            <!-- Input tanggal otomatis di-handle oleh script JavaScript -->
            <input type="hidden" id="jumlahTagihanHidden" name="jumlahTagihanHidden">
            <input type="hidden" id="tanggalTagihan" name="tanggalTagihan" value="<?php echo date('Y-m-d'); ?>">
            <button type="submit" class="btn btn-primary">Simpan</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Nomor Rekening</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editForm" action="update_rekening_2.php" method="POST">
            <!-- Form fields for editing -->
            <div class="mb-3">
              <label for="editBankSelect" class="form-label">Nama Bank:</label>
              <select class="form-select border p-2" id="editBankSelect" name="editBankSelect">
                <?php
                // Ambil data bank dari database dan tambahkan opsi ke dalam elemen select
                $queryBanks = "SELECT * FROM bank_account";
                $resultBanks = mysqli_query($conn, $queryBanks);

                while ($rowBanks = mysqli_fetch_assoc($resultBanks)) {
                  $idBank = $rowBanks['id_bank'];
                  $namaBank = $rowBanks['nama_bank'];
                  echo '<option value="' . $idBank . '">' . $namaBank . '</option>';
                }
                ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="atasNama" class="form-label">Atas Nama:</label>
              <input type="text" class="form-control border p-2" id="atasNama" name="atasNama" required>
            </div>
            <div class="mb-3">
              <label for="editNomorRekening" class="form-label">Nomor Rekening:</label>
              <input type="number" class="form-control border p-2" id="editNomorRekening" name="editNomorRekening" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
          </form>
        </div>
      </div>
    </div>
  </div>



  <!-- Modal -->
  <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Tambah Metode Pembayaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addPaymentForm" action="update_rekening.php" method="POST">
            <div class="mb-3">
              <label for="bankSelect" class="form-label">Nama Bank:</label>
              <select class="form-select border p-2" id="bankSelect" name="bankSelect">
                <?php
                // Fetch banks with NULL nomor_rekening
                $queryBanksNull = "SELECT * FROM bank_account WHERE nomor_rekening IS NULL";
                $resultBanksNull = mysqli_query($conn, $queryBanksNull);

                while ($rowBanksNull = mysqli_fetch_assoc($resultBanksNull)) {
                  echo '<option value="' . $rowBanksNull['id_bank'] . '">' . $rowBanksNull['nama_bank'] . '</option>';
                }
                ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="atasNama" class="form-label">Atas Nama:</label>
              <input type="text" class="form-control border p-2" id="atasNama" name="atasNama" required>
            </div>
            <div class="mb-3">
              <label for="nomorRekening" class="form-label">Nomor Rekening:</label>
              <input type="number" class="form-control border p-2" id="nomorRekening" name="nomorRekening" required>
            </div>
            <button type="submit" class="btn btn-primary">Tambah</button>
          </form>

        </div>
      </div>
    </div>
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