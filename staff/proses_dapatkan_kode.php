<?php
include "conn.php";
// include "session.php";
$pageNow = "Kode Garansi";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = $_POST["nama_produk"];
    $jumlah = $_POST["jumlah"];

    $query = "SELECT * FROM produk WHERE kode_produk = '$nama_produk'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $jenis = $row['jenis'];

    $kode_jenis = "";
    switch ($jenis) {
        case "Kamera IP":
            $kode_jenis = "IPCAM";
            break;
        case "Kamera AHD":
            $kode_jenis = "AHDCAM";
            break;
        case "NVR":
            $kode_jenis = "NVR";
            break;
        case "DVR":
            $kode_jenis = "DVR";
            break;
        case "POE":
            $kode_jenis = "POE";
            break;
        default:
            $kode_jenis = "";
            break;
    }

    // Array karakter yang digunakan untuk kode garansi
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';


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
                .tampilan-print {
                    display: block !important;
                }

                .tampilanUmum {
                    display: flex !important;
                }
            }

            @media screen {
                .tampilan-print {
                    display: none !important;
                }

                .tampilanUmum {
                    display: block !important;
                }
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
            setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
            $todayDate = strftime('%d %B %Y');
            ?>
            <!-- End Navbar -->
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-lg-12 col-md-12 mt-4 mb-4">
                        <div class="card z-index-2">
                            <div class="card-header col-10 col-md-3 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                                    <div class="chart px-3">
                                        <h5 class="text-light text-bold">Kode Garansi</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-4 ms-3">
                                    <img src="<?php echo $row['gambar']; ?>" class="img-fluid" style="max-height: 150px;">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 col-12 ms-3">
                                        <div class="row">
                                            <div class="col-md-6 mb-3 col-6">
                                                <strong>Nama Produk</strong>
                                            </div>
                                            <div class="col-md-6 mb-3 col-6">
                                                : <?php echo $nama_produk; ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3 col-6">
                                                <strong>Jumlah</strong>
                                            </div>
                                            <div class="col-md-6 mb-3 col-6">
                                                : <?php echo $jumlah; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <button type="button" class="btn btn-outline-primary" onclick="cetakKode()">Simpan Kode</button>

                                    </div>


                                    <div class="tampilanUmum">
                                        <div class="row" id="cetak">
                                        <?php
                                        for ($i = 0; $i < $jumlah; $i++) {
                                            $random_code = 'LWX-' . $kode_jenis . '-';
                                            for ($j = 0; $j < 5; $j++) {
                                                $random_code .= $characters[rand(0, strlen($characters) - 1)];
                                            }

                                        echo '<div class="col-lg-2 col-md-6 col-12 px-1 px-md-2 mb-3">
                                        <div class="card p-1 p-md-3 text-center">
                                            <div class="h6 mb-3 d-none text-sm d-md-block text-uppercase">' . $nama_produk . '</div>
                                        <div class="h6 mb-3 text-sm d-block d-md-none text-uppercase">' . $nama_produk . '</div>';

                                            ?>
                                            <img class='p-1' alt='Kode Garansi'
                                                src='https://barcode.tec-it.com/barcode.ashx?data=<?php echo $random_code;?>&translate-esc=on'/>
                                            <?php
                                        echo '
                                        </div>
                                        </div>';

                                    $queryInsert = "INSERT INTO garansi_berjalan (kode_garansi, tgl_cetak) VALUES ('$random_code', NOW())";
                                    $resultInsert = mysqli_query($conn, $queryInsert);
                                        }
                                    } else {
                                        echo "<div class='col-12'>Akses tidak sah</div>";
                                    }
                                        ?>
                                        </div>
                                    </div>

                                    <div class="tampilan-print">
                                        <div class="row">
                                            <div class="col-12">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Kode Garansi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        for ($i = 0; $i < $jumlah; $i++) {
                                                            $random_code = 'LWX-' . $kode_jenis . '-';
                                                            for ($j = 0; $j < 5; $j++) {
                                                                $random_code .= $characters[rand(0, strlen($characters) - 1)];
                                                            }
                                                            echo '<tr><td class="border">' . $random_code . '</td></tr>';
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>


                <?php
                include "footer.php";
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
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }

        function cetakKode() {
            var printContents = document.getElementsByClassName('tampilan-print')[0].innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
    </body>

    </html>