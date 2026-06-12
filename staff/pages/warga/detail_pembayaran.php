<?php
include "../../conn.php";
$pageNow = "Detail Pembayaran";
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
        $pageNow = "Detail Pembayaran";
        include "nav-top.php";
        ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center">
                                    <h6 class="mb-0 mx-1">Detail Pembayaran</h6>
                                </div>

                            </div>
                        </div>
                        <div class="card-body p-4 pb-0">
                            <ul class="list-group">
                                <?php
                                $getKodePembayaran = $_GET['kode_pembayaran'];
                                $query = "SELECT 
                                    pembayaran.*,
                                    data_warga.nik,
                                    data_warga.nama AS nama_warga,
                                    pembayaran.tgl_bayar,
                                    GROUP_CONCAT(tagihan.id_tagihan ORDER BY tagihan.id_tagihan SEPARATOR ',') AS id_tagihan,
                                    GROUP_CONCAT(tagihan.nama_tagihan ORDER BY tagihan.id_tagihan SEPARATOR '<br>') AS nama_tagihan,
                                    GROUP_CONCAT(CONCAT('Rp ', REPLACE(FORMAT(tagihan.jumlah, 0), ',', '.')) ORDER BY tagihan.id_tagihan SEPARATOR '<br>') AS jumlah_tagihan,
                                    SUM(tagihan.jumlah) AS total_jumlah,
                                    SUM(pembayaran.jumlah) AS total_pembayaran,
                                    pembayaran.status AS status_pembayaran
                                FROM pembayaran
                                JOIN tagihan ON pembayaran.id_tagihan = tagihan.id_tagihan
                                JOIN data_warga ON pembayaran.id_warga = data_warga.id_warga
                                WHERE pembayaran.kode_pembayaran = ? 
                                GROUP BY pembayaran.kode_pembayaran, data_warga.nik, data_warga.nama, pembayaran.tgl_bayar, pembayaran.status
                                ORDER BY pembayaran.tgl_bayar DESC LIMIT 10";

                                $stmt = mysqli_prepare($conn, $query);
                                mysqli_stmt_bind_param($stmt, "s", $getKodePembayaran);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);


                                while ($row = mysqli_fetch_assoc($result)) {
                                    $idTagihan = $row['id_tagihan'];
                                    $kodePembayaran = $row['kode_pembayaran'];
                                    $nik = $row['nik'];
                                    $namaWarga = $row['nama_warga'];
                                    $tglBayar = formatTanggal('dd MMMM yyyy', $row['tgl_bayar']); // Format tanggal dalam bahasa Indonesia

                                    $namaTagihan = $row['nama_tagihan'];
                                    $buktiPembayaran = $row['bukti_pembayaran'];
                                    $pembayaranVia = $row['id_bank'];
                                    $jumlahTagihan = $row['jumlah_tagihan'];
                                    $totalJumlah = $row['total_jumlah'];
                                    $totalPembayaran = $row['total_pembayaran'];
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
                                }

                                $queryBk = "SELECT * FROM bank_account WHERE id_bank = '$pembayaranVia'";
                                $resultBk = mysqli_query($conn, $queryBk);
                                while ($rowBk = mysqli_fetch_assoc($resultBk)) {
                                    $nmBank = $rowBk['nama_bank'];
                                }
                                ?>

                                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
                                    <div class="row">
                                        <div class="col-12 col-md-12 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold text-lg text-uppercase">Kode Pembayaran : <?php echo $kodePembayaran; ?></h6>
                                        </div>

                                        <div class="col-7 col-md-6 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark font-weight-bold"><?php echo $namaWarga; ?></h6>
                                        </div>

                                        <div class="col-5 col-md-6 mb-2 mb-md-0">
                                            <span class="text-sm"><?php echo $tglBayar; ?></span><br>
                                        </div>

                                        <div class="col-12 col-md-12 mb-0 mb-md-0 mt-4">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm text-uppercase">Detail Pembayaran</h6>
                                        </div>

                                        <div class="col-7 col-md-6 mb-2 mb-md-0">
                                            <span class="text-sm text-dark"><?php echo $namaTagihan; ?></span>
                                        </div>

                                        <div class="col-5 col-md-6 mb-2 mb-md-0">
                                            <span class="text-sm text-dark"><?php echo $jumlahTagihan; ?></span>
                                        </div>

                                        <div class="col-7 col-md-6 mb-2 mb-md-0"></div>

                                        <div class="col-5 col-md-3 mb-2 mb-md-0" style="border-top:1px solid #ddd;">
                                            <h6 class="mb-1 text-dark text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.'); ?></h6>
                                        </div>

                                        <div class="col-12 col-md-12 mb-0 mb-md-0 mt-3">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm text-uppercase">Pembayaran</h6>
                                        </div>

                                        <div class="col-6 col-md-6 mb-2 mb-md-0">
                                            <span class="text-sm text-dark">Metode Pembayaran</span><br>
                                            <span class="text-sm text-dark">Jumlah Pembayaran</span><br>
                                            <span class="text-sm text-dark">Status Pembayaran</span><br>
                                            <span class="text-sm text-dark">Bukti Pembayaran</span>
                                        </div>

                                        <div class="col-6 col-md-6 mb-2 mb-md-0">
                                            <h6 class="mb-1 text-dark text-sm"><?php echo $nmBank; ?></h6>
                                            <h6 class="mb-1 text-dark text-sm">Rp <?php echo number_format($totalJumlah, 0, ',', '.'); ?></h6>
                                            <h6 class="mb-1 text-dark text-sm"><?php echo $statusPembayaran; ?></h6>
                                            <a href="../../assets/img/uploads/<?php echo $buktiPembayaran; ?>" target="_blank">
                                                <img src="../../assets/img/uploads/<?php echo $buktiPembayaran; ?>" class="w-60 d-none d-md-block">
                                            </a>

                                        </div>

                                        <div class="col-12 col-md-12 mb-2 mb-md-0">
                                        <a href="../../assets/img/uploads/<?php echo $buktiPembayaran; ?>" target="_blank">
                                                <img src="../../assets/img/uploads/<?php echo $buktiPembayaran; ?>" class="w-70 w-md-30 d-block d-md-none">
                                            </a>
                                        </div>

                                        <div class="col-12 col-md-12 mb-2 mb-md-0 mt-3 text-start text-md-center">
                                            <?php
                                            if ($statusPembayaran == "Ditolak") {
                                            ?>
                                                <button type="button" class="btn btn-outline-primary w-100" data-toggle="modal" data-target="#verifikasiUlangModal">Verifikasi Ulang</button>
                                            <?php
                                            } else {
                                            }
                                            ?>
                                        </div>
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
    <div class="fixed-plugin d-none">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
    </div>

    <!-- Modal Verifikasi Ulang -->
    <div class="modal fade" id="verifikasiUlangModal" tabindex="-1" aria-labelledby="verifikasiUlangModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifikasiUlangModalLabel">Verifikasi Ulang Pembayaran</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_verifikasi_ulang.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="buktiPembayaran" class="form-label">Upload Bukti Pembayaran Baru</label>
                            <input type="file" class="form-control border p-2" id="buktiPembayaran" name="buktiPembayaran" accept="image/*" required>
                        </div>
                        <span class="text-xs" style="color:#aaa;"><i>*Jika pembayaran Anda ditolak lebih dari 3 kali, silahkan hubungi Bendahara untuk verifikasi secara langsung.</i></span>
                    </div>
                    <input type="hidden" name="kodePembayaran" id="kodePembayaran" value="<?php echo $getKodePembayaran; ?>">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!--   Core JS Files   -->
    <?php
    include "js-include.php";
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

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

</body>

</html>