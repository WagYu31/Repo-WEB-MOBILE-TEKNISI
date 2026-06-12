<?php
include "conn.php";
include "session.php";
$pageNow = "Edit Kegiatan";
include "get-user-data.php";

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
        <?php
        include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-6 col-12 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-md-4 col-6 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                                <div class="chart px-3">
                                    <h5 class="text-light text-bold text-center">Edit Kegiatan</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["kode_transaksi"])) {
                                $kode_transaksi = $_GET["kode_transaksi"];
                            
                                include "get_data_kegiatan_apv.php";
                            }
                            
                            // Menghitung total jumlah data dalam array
                            $totalDataDB = count($idTeknisiKegiatan);
                            
                            $idTeknisiString = implode(',', $idTeknisiKegiatan);
                            
                            include "get_update_kegiatan.php";
                            
                            
                            // Query untuk mengambil data teknisi dan customer
                            $sqlTeknisi = "SELECT * FROM teknisi";
                            $resultTeknisi = mysqli_query($conn, $sqlTeknisi);
                            
                            $sqlCustomer = "SELECT * FROM customer";
                            $resultCustomer = mysqli_query($conn, $sqlCustomer);
                            ?>
                            
                        </div>

                    </div>
                </div>
            </div>
            <?php
            include "footer.php";
            ?>
        </div>
    </main>
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#newCustomerCheckbox").change(function() {
                if (this.checked) {
                    $(".new-customer-fields").show();
                    $("#nama_customer").prop("disabled", true);
                } else {
                    $(".new-customer-fields").hide();
                    $("#nama_customer").prop("disabled", false);
                }
            });

            function refreshCustomerDropdown() {
                // Fetch the updated customer data from the server and populate the dropdown
                $.get("get_customers.php", function(data) {
                    $("#nama_customer").html(data);
                });
            }
        });
    </script>
    <!-- Github buttons -->
</body>

</html>