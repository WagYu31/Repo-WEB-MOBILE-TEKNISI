<?php
include "../../conn.php";
include "session.php";

// Ambil nilai NIK dari sesi
$nikSesi = $_SESSION["nik"];
$querySesi = "SELECT * FROM data_warga WHERE nik = '$nikSesi'";
$resultSesi = mysqli_query($conn, $querySesi);
$rowSesi = mysqli_fetch_assoc($resultSesi);
$id_warga = $rowSesi['id_warga'];
$nama = $rowSesi['nama'];

$pageNow = "Sedekah";
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

<body class="g-sidenav-show bg-gray-200">

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
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Mari Bersedekah</h4>
                        </div>
                        <div class="card-body mt-n4">
                            <?php
                            $queryBankAccount = "SELECT * FROM bank_account WHERE nomor_rekening != 'NULL'";
                            $resultBankAccount = mysqli_query($conn, $queryBankAccount);
                            if ($resultBankAccount) {
                                $bankOptions = '';
                                while ($rowBankAccount = mysqli_fetch_assoc($resultBankAccount)) {
                                    $bankOptions .= "<option value='{$rowBankAccount['id_bank']}' data-rekening='{$rowBankAccount['nomor_rekening']}'>{$rowBankAccount['nama_bank']} - {$rowBankAccount['nomor_rekening']}</option>";
                                }
                            } else {
                                echo "Error: " . mysqli_error($conn);
                                exit();
                            }
                            ?>
                            <form action="proses_sedekah.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="nominal" class="form-label">Nominal Sedekah</label>
                                    <input type="hidden" id="nominalHidden" name="nominal" required>
                                    <input type="text" class="form-control border p-2" id="nominal" placeholder="Nominal Sedekah" oninput="formatRupiah(this)" required>
                                </div>
                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <input type="text" class="form-control border p-2" id="keterangan" name="keterangan" placeholder="Keterangan" required>
                                </div>

                                <div class="mb-3">
                                    <h6>Pilih Metode Pembayaran :</h6>
                                    <div class="d-flex align-items-center">
                                        <select class="form-select border p-2" id="selected_bank" name="selected_bank" onchange="displaySelectedRekening()">
                                            <?php echo $bankOptions; ?>
                                        </select>
                                    </div>
                                    <p class='text-xs mt-2' id='bankRekeningInfo'><i>***Silahkan pilih metode pembayaran dan transfer ke nomor rekening tujuan yang tertera, lampirkan bukti transfer berupa photo atau screenshoot.</i></p>
                                    <div id='selectedRekening' class='mt-3 me-3'></div>
                                    <button class='btn btn-outline-primary btn-sm mt-0 mb-0' data-bs-toggle='tooltip' data-bs-placement='top' title='Copy Nomor Rekening' onclick='copyRekening()'>Copy Nomor Rekening</button>
                                </div>

                                <div class="mb-3">
                                    <label for="bukti_pembayaran" class="form-label">Bukti Pembayaran</label>
                                    <input type="file" class="form-control border p-2" id="bukti_pembayaran" name="bukti_pembayaran" required accept="image/*">
                                </div>
                                <input type="hidden" name="id_warga" value="<?php echo $id_warga; ?>">
                                <button type="submit" class="btn btn-primary mt-4 col-12 col-md-12">Bayar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-6 d-flex align-items-center">
                                    <h6 class="mb-0">Sedekah Saya</h6>
                                </div>

                            </div>
                        </div>
                        <div class="card-body p-3 pb-0">
                            <ul class="list-group">
                                <?php
                                $querySedekah = "SELECT * FROM sedekah WHERE id_warga = '$id_warga' ORDER BY tgl_sedekah DESC LIMIT 10";
                                $resultSedekah = mysqli_query($conn, $querySedekah);

                                $counter = 0;

                                while ($rowSedekah = mysqli_fetch_assoc($resultSedekah)) {
                                    $idSedekah = $rowSedekah['id_sedekah'];
                                    $kode_pembayaran = $rowSedekah['kode_pembayaran'];
                                    $namaSedekah = $rowSedekah['keterangan'];
                                    $tglSedekah = $rowSedekah['tgl_sedekah'];
                                    setlocale(LC_TIME, 'id_ID');
                                    $tanggalFormatted = strftime('%d %b %Y', strtotime($tglSedekah));
                                    $jumlahSedekah = $rowSedekah['jumlah'];
                                    $jumlahSedekahRupiah = "Rp " . number_format($jumlahSedekah, 0, ',', '.') . ",00";
                                    $counter++;
                                ?>

                                    <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                                        <div class="d-flex flex-column col-8 col-md-8">
                                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $namaSedekah; ?></h6>
                                            <span class="text-xs"><?php echo $tanggalFormatted; ?></span>
                                        </div>

                                        <div class="d-flex align-items-center text-sm col-4 col-md-4">
                                            <?php echo $jumlahSedekahRupiah; ?>
                                        </div>
                                    </li>


                                <?php
                                    if ($counter >= 10) {
                                        break; // Hentikan loop setelah 10 list
                                    }
                                }
                                ?>
                                <?php
                                
                                if(mysqli_num_rows($resultSedekah) == 0){
                                    echo "<span class='mt-n2 text-sm'>Tidak ada riwayat sedekah baru</span>";
                                  }
                                else{
                                    ?>
                                <div class="text-end col-12">
                                    <button class="btn btn-primary mt-2" id="loadMore">Load More</button>
                                </div>
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
    <div class="fixed-plugin d-none d-md-block">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
    </div>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>

    <script>
        function displaySelectedRekening() {
            var selectedBank = document.getElementById('selected_bank');
            var selectedOption = selectedBank.options[selectedBank.selectedIndex];
            var nomorRekening = selectedOption.getAttribute('data-rekening');
            var selectedRekening = document.getElementById('selectedRekening');

            // Tampilkan nomor rekening yang dipilih di div selectedRekening
            selectedRekening.innerHTML = `<span class='slc-rek'><strong>${nomorRekening}</strong></span>`;
            selectedRekening.style.display = 'inline';
        }

        function copyRekening() {
            var selectedBank = document.getElementById('selected_bank');
            var selectedOption = selectedBank.options[selectedBank.selectedIndex];
            var nomorRekening = selectedOption.getAttribute('data-rekening');

            // Salin nomor rekening ke clipboard
            navigator.clipboard.writeText(nomorRekening)
                .then(function() {
                    // Tampilkan tooltip atau pesan sukses (opsional)
                    var tooltipContainer = document.getElementById('bankRekeningInfo');
                    tooltipContainer.innerHTML = '<span class="text-success">Nomor rekening berhasil disalin!</span>';
                    setTimeout(function() {
                        tooltipContainer.innerHTML = '<i>***Silahkan pilih metode pembayaran dan transfer ke nomor rekening tujuan yang tertera, lampirkan bukti transfer berupa photo atau screenshoot.</i>';
                    }, 3000); // Setelah 3 detik, kembalikan pesan aslinya

                    // Fokuskan kembali ke elemen select setelah menyalin
                    selectedBank.focus();
                })

                .catch(function(err) {
                    console.error('Gagal menyalin ke clipboard:', err);
                });
        }

        function formatRupiah(input) {
            // Remove non-numeric characters
            var rawValue = input.value.replace(/[^\d]/g, '');

            // Format as currency for display
            var formattedValue = 'Rp ' + new Intl.NumberFormat('id-ID').format(rawValue);

            // Update the displayed value
            input.value = formattedValue;

            // Update the hidden input with the raw numeric value
            document.getElementById('nominalHidden').value = rawValue;
        }
    </script>

    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>