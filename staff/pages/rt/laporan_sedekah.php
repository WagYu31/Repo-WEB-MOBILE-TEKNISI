<?php
include "../../conn.php";
$pageNow = "Laporan Keuangan RT 12";
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
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-200">

    <?php
    include "cek-menu.php";
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <?php
        include "nav-top.php";
        $getMonth = date("M Y");
        ?>
        <div class="container-fluid py-4">
            <div class="row mt-3 mb-2 no-print">
                <div class="col-12 col-md-12 text-md-start">
                    <div class="btn-group col-8 col-md-3 align-items-center" role="group">
                        <a href="laporan.php" class="btn btn-primary w-md-auto mb-2 mb-md-0">
                            <i class="material-icons d-md-inline">sync</i>
                            <span class="d-md-inline">Refresh</span>
                        </a>
                        <button class="btn btn-outline-primary btn-sm mt-md-0 mt-n2 mb-0 py-2 w-md-auto" onclick="printPage()">
                            Print
                        </button>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-lg-12" id="pembayaranTabel">
                    <div class="card h-100 py-3">
                        <div class="card-header pb-0 p-3">
                            <div class="row align-items-center">
                                <div class="col-lg-8 col-12">
                                    <h6 class="mb-0 mx-1">Laporan Pembayaran Iuran dan Tagihan Lain</h6>
                                </div>
                                <div class="col-lg-4 col-12 no-print">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <button class="btn btn-outline-primary btn-sm mb-0 w-100" onclick="cariTagihan()">Cari</button>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select border p-2" id="bulanTahun" name="bulanTahun">
                                                <option value=""></option>
                                                <?php
                                                $bulanIni = date('m');
                                                $tahunIni = date('Y');

                                                for ($i = 0; $i < 12; $i++) {
                                                    $bulanTahun = date('m-Y', strtotime("+$i months"));
                                                    $namaBulanTahun = date('F Y', strtotime("+$i months"));
                                                    echo "<option value=\"$bulanTahun\"";
                                                    if ($bulanTahun == "$bulanIni-$tahunIni") {
                                                        echo " selected";
                                                    }
                                                    echo ">$namaBulanTahun</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4 pb-0">
                            <ul class="list-group" id="pembayaranList">
                                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
                                    <div class="row">
                                        <div class="col-6 col-md-1 mb-1 mb-md-0">
                                            <span class="text-sm text-uppercase font-weight-bold">No</span>
                                        </div>
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="text-sm text-uppercase font-weight-bold">No KK</span>
                                        </div>

                                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                                            <span class="text-sm text-uppercase font-weight-bold">Nama</span>
                                        </div>

                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="text-sm text-uppercase font-weight-bold">Tagihan</span>
                                        </div>

                                        <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                            <span class="text-sm text-uppercase font-weight-bold">Jumlah Bayar</span>
                                        </div>

                                        <div class="col-6 col-md-2 mb-1 mb-md-0  text-start text-md-center">
                                            <span class="text-sm text-uppercase font-weight-bold">Tanggal Bayar</span>
                                        </div>
                                    </div>
                                </li>
                                <?php
                                $query = "SELECT 
                                sedekah.id_warga, 
                                NULL AS id_tagihan, 
                                sedekah.tgl_sedekah AS tgl_bayar, 
                                sedekah.kode_pembayaran, 
                                sedekah.jumlah, 
                                sedekah.status,
                                'Sedekah' AS nama_tagihan,
                                data_warga.nik,
                                data_warga.nama,
                                data_warga.no_kk
                            FROM 
                                sedekah
                            JOIN 
                                data_warga ON sedekah.id_warga = data_warga.id_warga
                            WHERE 
                                sedekah.status = 'Verified'
                            ORDER BY 
                                tgl_bayar DESC";

                                $result = mysqli_query($conn, $query);

                                $counter = 0;
                                if (mysqli_num_rows($result) == 0) {
                                    echo "<span class='mt-n2 text-sm'>Tidak ada riwayat pembayaran baru</span>";
                                }
                                $no = 1;

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $idTagihan = $row['id_tagihan'];
                                    $kodePembayaran = $row['kode_pembayaran'];
                                    $nik = $row['nik'];
                                    $namaWarga = $row['nama'];
                                    $noKK = $row['no_kk'];
                                    $tglBayar = formatTanggal('dd MMMM yyyy', $row['tgl_bayar']);

                                    $namaTagihan = $row['nama_tagihan'];
                                    $totalJumlah = $row['jumlah'];
                                    $statusPembayaran = $row['status'];
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
                                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg">
                                        <div class="row">
                                            <div class="col-6 col-md-1 mb-1 mb-md-0">
                                                <span class="text-sm text-uppercase"><?php echo $no; ?></span>
                                            </div>
                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <span class="text-sm text-uppercase"><?php echo $noKK; ?></span>
                                            </div>

                                            <div class="col-6 col-md-3 mb-2 mb-md-0">
                                                <span class="text-sm text-capitalize"><?php echo $namaWarga; ?></span>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0">
                                                <span class="text-sm text-capitalize"><?php echo $namaTagihan; ?></span>
                                            </div>

                                            <div class="col-6 col-md-2 mb-2 mb-md-0 text-left text-md-center">
                                                <span class="text-sm font-weight-bold">Rp <?php echo number_format($totalJumlah, 0, ',', '.') . ",00"; ?></span>
                                            </div>

                                            <div class="col-6 col-md-2 mb-1 mb-md-0  text-start text-md-center">
                                                <span class="text-sm"><?php echo $tglBayar; ?></span>
                                            </div>
                                        </div>
                                    </li>
                                <?php
                                    $no++;
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
    <div class="fixed-plugin no-print">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
    </div>


    <?php
    include "js-include.php";
    ?>
    <script>
        function cariTagihan() {
            var bulanTahun = document.getElementById("bulanTahun").value;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "proses_sedekah.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById("pembayaranList").innerHTML = xhr.responseText;
                }
            };
            // Kirim nilai bulan dan tahun yang dipilih
            xhr.send("bulanTahun=" + bulanTahun);
        }
    </script>

    <script>
        function printPage() {
            // Ubah orientasi kertas menjadi landscape sebelum mencetak
            var style = document.createElement('style');
            style.setAttribute('type', 'text/css');
            style.setAttribute('media', 'print');
            style.innerHTML = '@page { size: landscape; }';
            document.head.appendChild(style);

            // Mencetak halaman
            window.print();

            // Hapus gaya CSS setelah mencetak
            document.head.removeChild(style);
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