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
                                $no_kk_query = "SELECT no_kk FROM data_warga WHERE nik = '$nikSesi'";
                                $result_no_kk = mysqli_query($conn, $no_kk_query);
                                $row_noKK = mysqli_fetch_assoc($result_no_kk);
                                $no_kk = $row_noKK["no_kk"];
                                $queryBlmDibayar = "SELECT SUM(tagihan.jumlah) AS total_BB 
                                FROM tagihan 
                                LEFT JOIN pembayaran ON tagihan.id_tagihan = pembayaran.id_tagihan
                                                     AND pembayaran.id_warga IN (
                                                         SELECT id_warga
                                                         FROM data_warga
                                                         WHERE no_kk = (
                                                             SELECT no_kk
                                                             FROM data_warga
                                                             WHERE nik = '$nikSesi'
                                                         )
                                                     )
                                WHERE tagihan.status = 'Y'
                                AND pembayaran.id_pembayaran IS NULL";

                                $resultBlmDibayar = mysqli_query($conn, $queryBlmDibayar);

                                $rowBB = mysqli_fetch_assoc($resultBlmDibayar);
                                $totalBelumDibayar = $rowBB["total_BB"];

                                // Format jumlah total tagihan yang belum dibayar menjadi rupiah
                                $totalBBFormatted = "Rp " . number_format($totalBelumDibayar, 2, ',', '.');
                                ?>
                                <div class="col-md-3 col-6">
                                    <div class="card my-2">
                                        <div class="card-header mx-4 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                                                <i class="material-icons opacity-10">account_balance</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0">Belum Dibayar</h6>
                                            <span class="text-xs">Iuran Warga</span>
                                            <hr class="horizontal dark my-3">
                                            <h5 class="mb-0"><?php echo $totalBBFormatted; ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $queryTlhDibayar = "SELECT SUM(pembayaran.jumlah) AS total_TB 
                                FROM pembayaran 
                                JOIN data_warga ON pembayaran.id_warga = data_warga.id_warga
                                                     AND pembayaran.id_warga IN (
                                                         SELECT id_warga
                                                         FROM data_warga
                                                         WHERE no_kk = (
                                                             SELECT no_kk
                                                             FROM data_warga
                                                             WHERE nik = '$nikSesi'
                                                         )
                                                     )
                                WHERE pembayaran.status = 'Verified'";

                                $resultTlhDibayar = mysqli_query($conn, $queryTlhDibayar);

                                $rowTB = mysqli_fetch_assoc($resultTlhDibayar);
                                $totalTlhDibayar = $rowTB["total_TB"];

                                // Format jumlah total tagihan yang belum dibayar menjadi rupiah
                                $totalTBFormatted = "Rp " . number_format($totalTlhDibayar, 2, ',', '.');
                                ?>
                                <div class="col-md-3 col-6">
                                    <div class="card my-2">
                                        <div class="card-header mx-4 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                                                <i class="material-icons opacity-10">account_balance</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0">Telah Dibayar</h6>
                                            <span class="text-xs">Iuran Warga</span>
                                            <hr class="horizontal dark my-3">
                                            <h5 class="mb-0"><?php echo $totalTBFormatted; ?></h5>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                $queryTlhDibayar = "SELECT SUM(pembayaran.jumlah) AS total_TB 
                                FROM pembayaran 
                                JOIN data_warga ON pembayaran.id_warga = data_warga.id_warga
                                                     AND pembayaran.id_warga IN (
                                                         SELECT id_warga
                                                         FROM data_warga
                                                         WHERE no_kk = (
                                                             SELECT no_kk
                                                             FROM data_warga
                                                             WHERE nik = '$nikSesi'
                                                         )
                                                     )
                                WHERE pembayaran.status != 'Verified'";
                                $resultTlhDibayar = mysqli_query($conn, $queryTlhDibayar);

                                $rowTB = mysqli_fetch_assoc($resultTlhDibayar);
                                $totalTlhDibayar = $rowTB["total_TB"];

                                // Format jumlah total tagihan yang belum dibayar menjadi rupiah
                                $totalTBFormatted = "Rp " . number_format($totalTlhDibayar, 2, ',', '.');
                                ?>
                                <div class="col-md-3 col-6">
                                    <div class="card my-2">
                                        <div class="card-header mx-4 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                                                <i class="material-icons opacity-10">account_balance</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0">Tertahan</h6>
                                            <span class="text-xs">Belum Diverifikasi</span>
                                            <hr class="horizontal dark my-3">
                                            <h5 class="mb-0"><?php echo $totalTBFormatted; ?></h5>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                $querySedekah = "SELECT SUM(jumlah) AS total_SDK FROM sedekah WHERE id_warga = $id_warga";
                                $resultSedekah = mysqli_query($conn, $querySedekah);

                                $rowSdk = mysqli_fetch_assoc($resultSedekah);
                                $totalSedekah = $rowSdk["total_SDK"];

                                // Format jumlah total tagihan yang belum dibayar menjadi rupiah
                                $totalSDKFormatted = "Rp " . number_format($totalSedekah, 2, ',', '.');
                                ?>
                                <div class="col-md-3 col-6">
                                    <div class="card my-2">
                                        <div class="card-header mx-4 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg">
                                                <i class="material-icons opacity-10">account_balance</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0">Sedekah</h6>
                                            <span class="text-xs">Mari Berbagi</span>
                                            <hr class="horizontal dark my-3">
                                            <h5 class="mb-0"><?php echo $totalSDKFormatted; ?></h5>
                                        </div>
                                    </div>
                                </div>


                            </div>


                        </div>



                        <div class="col-md-12 mb-lg-0 mb-4">
                            <div class="card mt-4">
                                <div class="card-header pb-0 p-3">
                                    <div class="row">
                                        <div class="col-6 d-flex align-items-center">
                                            <h6 class="mb-0">Iuran Bulanan</h6>
                                        </div>
                                        <div class="col-6 text-end">
                                            <button type="button" class="btn bg-gradient-dark mb-0" onclick="redirectToPayment()"><i class="material-icons text-sm">money</i>&nbsp;&nbsp;Bayar</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <?php
                                        $queryTgh = "SELECT *
                                        FROM tagihan
                                        WHERE tagihan.status = 'Y'
                                          AND NOT EXISTS (
                                              SELECT 1
                                              FROM pembayaran
                                              JOIN data_warga ON pembayaran.id_warga = data_warga.id_warga
                                              WHERE pembayaran.id_tagihan = tagihan.id_tagihan
                                                AND data_warga.no_kk = (
                                                    SELECT no_kk
                                                    FROM data_warga
                                                    WHERE nik = '$nikSesi'
                                                )
                                          )";

                                        $resultTgh = mysqli_query($conn, $queryTgh);
                                        while ($rowTgh = mysqli_fetch_assoc($resultTgh)) {
                                            $idTagihan = $rowTgh["id_tagihan"];
                                            $namaTagihan = $rowTgh["nama_tagihan"];
                                            $tglTagihan = $rowTgh["tgl_tagihan"];
                                            $jumlahTagihan = $rowTgh["jumlah"];

                                            // Format tanggal menjadi d-m-Y
                                            $tglTagihanFormatted = date("d - M - Y", strtotime($tglTagihan));

                                            // Format jumlah menjadi rupiah
                                            $jumlahTagihanFormatted = "Rp " . number_format($jumlahTagihan, 2, ',', '.');

                                        ?>
                                            <div class="col-md-6 mb-md-0 mb-4 my-4">
                                                <form method="POST" style="display: inline;">
                                                    <div class="card card-body border card-plain border-radius-lg d-flex align-items-center flex-row" onclick="toggleCheckbox('<?php echo $idTagihan; ?>')">
                                                        <div class="col-2 col-md-2 text-end">
                                                            <!-- Add checkbox with tagihan id as value -->
                                                            <input type="checkbox" id="tagihan_<?php echo $idTagihan; ?>" name="tagihan" value="<?php echo $idTagihan; ?>" class="form-check">
                                                        </div>
                                                        <div class="col-10 col-md-10">
                                                            <label for="tagihan_<?php echo $idTagihan; ?>">
                                                                <h6 class="mb-0"><?php echo $namaTagihan; ?></h6>
                                                                <span class="mb-0 text-s"><?php echo $jumlahTagihanFormatted; ?></span>
                                                            </label>
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
                    <div class="card h-100">
                        <div class="card-header pb-0 px-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-0">Transaksi Anda</h6>
                                </div>
                                <?php
                                setlocale(LC_TIME, 'id_ID.utf8');
                                $getToday = date("d M Y");
                                ?>
                                <div class="col-md-6 d-flex justify-content-start justify-content-md-end align-items-center">
                                    <i class="material-icons me-2 text-lg">date_range</i>
                                    <small>1 - <?php echo $getToday;?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-4 p-3">
                            <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Terbaru</h6>
                            <ul class="list-group">
                                <?php
                                include "../../conn.php";
                                $query = "SELECT 
                                pembayaran.id_warga, 
                                pembayaran.id_tagihan, 
                                pembayaran.tgl_bayar, 
                                pembayaran.kode_pembayaran, 
                                pembayaran.jumlah, 
                                pembayaran.status,
                                tagihan.nama_tagihan
                            FROM 
                                pembayaran
                            JOIN 
                                tagihan ON pembayaran.id_tagihan = tagihan.id_tagihan
                            JOIN 
                                data_warga ON pembayaran.id_warga = data_warga.id_warga
                            WHERE 
                                data_warga.nik = '$nikSesi'
                            UNION ALL
                            SELECT 
                                sedekah.id_warga, 
                                NULL AS id_tagihan, 
                                sedekah.tgl_sedekah AS tgl_bayar, 
                                sedekah.kode_pembayaran, 
                                sedekah.jumlah, 
                                sedekah.status,
                                'Sedekah' AS nama_tagihan
                            FROM 
                                sedekah
                            JOIN 
                                data_warga ON sedekah.id_warga = data_warga.id_warga
                            WHERE 
                                data_warga.nik = '$nikSesi'
                            ORDER BY 
                                tgl_bayar DESC
                            LIMIT 10";

                                $result = mysqli_query($conn, $query);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $id_warga = $row["id_warga"];
                                    $id_tagihan = $row["id_tagihan"];
                                    $tgl_bayar = $row["tgl_bayar"];
                                    $kode_pembayaran = $row["kode_pembayaran"];
                                    $jumlah = $row["jumlah"];
                                    $nama_tagihan = $row["nama_tagihan"];
                                    $tglBayarFormatted = date("d M Y", strtotime($tgl_bayar));
                                    $statusPembayaran = $row["status"];
                                    $jumlahFormatted = "Rp " . number_format($jumlah, 2, ',', '.');
                                ?>

                                    <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                                        <div class="d-flex align-items-center">
                                                <?php
                                                    if($statusPembayaran == "Verified"){
                                                        ?>
                                                            <button class="btn btn-icon-only btn-rounded btn-outline-success mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">check</i></button>
                                                        <?php
                                                    }
                                                    else if($statusPembayaran == "Pending"){
                                                        ?>
                                                            <button class="btn btn-icon-only btn-rounded btn-outline-dark mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">priority_high</i></button>
                                                        <?php
                                                    }
                                                    else{
                                                        ?>
                                                            <button class="btn btn-icon-only btn-rounded btn-outline-danger mb-0 me-3 p-3 btn-sm d-flex align-items-center justify-content-center"><i class="material-icons text-lg">close</i></button>
                                                        <?php
                                                    }
                                                ?>
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-1 text-dark text-sm"><?php echo $nama_tagihan; ?></h6>
                                                <span class="text-xs"><?php echo $tglBayarFormatted; ?></span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center text-success text-gradient text-sm font-weight-bold">
                                            <?php echo $jumlahFormatted; ?>
                                        </div>
                                    </li>
                                <?php
                                }
                                ?>
                            </ul>

                            <div class="col-12 text-end">
                                <a class="btn btn-outline-primary mb-3 mt-3 w-100" href="status_pembayaran.php" type="button">Lihat Semua Transaksi</a>
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




    <!-- MODAL POP UP -->

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

<?php
include "js-include.php";
?>

    <script>
        function toggleCheckbox(id) {
            var checkbox = document.getElementById('tagihan_' + id);
            checkbox.checked = !checkbox.checked;
        }

        function redirectToPayment() {
            var selectedTagihan = document.querySelectorAll('input[name="tagihan"]:checked');

            if (selectedTagihan.length > 0) {
                var selectedTagihanIds = [];
                selectedTagihan.forEach(function(tagihan) {
                    selectedTagihanIds.push(tagihan.value);
                });

                var redirectUrl = 'proses_pembayaran.php?id_tagihan=' + selectedTagihanIds.join(',');

                // Redirect to payment page
                window.location.href = redirectUrl;
            } else {
                alert('Pilih setidaknya satu tagihan untuk membayar.');
            }
        }
    </script>

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
</body>

</html>