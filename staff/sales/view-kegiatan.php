<?php
include "../conn.php";
include "../session.php";
$pageNow = "Edit Kegiatan";
include "../get-user-data.php"
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
        .image-container {
            width: 30%;
            position: relative;
            vertical-align: bottom;
        }

        /* Media query untuk mode mobile */
        @media (max-width: 768px) {
            .image-container {
                width: 100%;
            }
        }

        .download-btn-container {
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            text-align: center;
        }
    </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
    <?php
    include "cek-menu.php";
    ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php
        include "nav-top.php";
        setlocale(LC_TIME, 'id_ID');
        $todayDate = strftime('%d %B %Y');
        ?>
        <div class="container-fluid pe-4 py-4">
            <div class="row">
                <div class="col-lg-4 col-12 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-md-4 col-6 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-primary border-radius-lg py-3 pe-1">
                                <div class="chart px-3">
                                    <h5 class="text-light text-bold text-center">Kegiatan</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body col-12">
                            <?php
                            if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["kode_transaksi"])) {
                                $kode_transaksi = $_GET["kode_transaksi"];
                                $idSales = ''; // Inisialisasi $idTeknis

                                // Check if id_teknisi is provided
                                if (isset($_GET["id_sales"])) {
                                    $idSales = $_GET["id_sales"];
                                } else {
                                    // If id_teknisi is not provided, get the first id_teknisi for the given kode_transaksi
                                    $sql = "SELECT id_sales FROM visits WHERE kode_transaksi = ? LIMIT 1";
                                    $stmt = mysqli_prepare($conn, $sql);
                                    mysqli_stmt_bind_param($stmt, "s", $kode_transaksi);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);

                                    if ($row = mysqli_fetch_assoc($result)) {
                                        $idSales = $row['id_sales'];
                                    }
                                }

                                include "get-kegiatan.php";
                            }
                            $totalDataDB = count($idTeknisiKegiatan);

                            $idTeknisiString = implode(',', $idTeknisiKegiatan);

                            include "get_update_kegiatan.php";


                            // Query untuk mengambil data teknisi dan customer
                            $sqlTeknisi = "SELECT * FROM sales";
                            $resultTeknisi = mysqli_query($conn, $sqlTeknisi);

                            $sqlCustomer = "SELECT * FROM cust";
                            $resultCustomer = mysqli_query($conn, $sqlCustomer);
                            ?>

                        </div>

                    </div>
                </div>

                <?php

                include "get-detail-kegiatan-tek.php";
                ?>
            </div>


            <?php
            include "footer.php";
            ?>
        </div>
    </main>
    <?php
    include "js-include.php";
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script>
        // Menambahkan event listener untuk semua tombol download
        var downloadBtns = document.querySelectorAll('.download-btn');
        downloadBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                // Mencegah default action dari link
                e.preventDefault();
                // Mengambil link download dari atribut href
                var downloadLink = this.getAttribute('href');
                // Membuat element <a> sementara untuk melakukan download
                var tempLink = document.createElement('a');
                tempLink.href = downloadLink;
                tempLink.setAttribute('download', '');
                // Menambahkan element <a> sementara ke dalam dokumen
                document.body.appendChild(tempLink);
                // Mengklik element <a> sementara untuk memulai proses download
                tempLink.click();
                // Menghapus element <a> sementara setelah proses download selesai
                document.body.removeChild(tempLink);
            });
        });
    </script>
    <!-- Github buttons -->
</body>

</html>