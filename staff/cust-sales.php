<?php
include "conn.php";
include "session.php";
$pageNow = "Daftar Kegiatan Sales";
include "get-user-data.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari formulir
    $customer_id = $_POST['customer'];
    $sales_ids = $_POST['sales'];
    $visit_date = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];

    // Generate kode transaksi
    $kode_transaksi = 'LWX-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5) . '-SLS';

    // Waktu saat ini
    date_default_timezone_set('Asia/Jakarta');
    $tgl_update = date('Y-m-d H:i:s');
    $status = 'dijadwalkan';

    // Lakukan proses insert untuk setiap sales yang dipilih
    foreach ($sales_ids as $sales_id) {
        // Lakukan proses insert ke tabel visits
        $query = "INSERT INTO visits (kode_transaksi, id_sales, id_cust, tgl_update, tgl_visits, keterangan_visits, status) 
                  VALUES ('$kode_transaksi', '$sales_id', '$customer_id', '$tgl_update', '$visit_date', '$keterangan', '$status')";

        // Jalankan query
        if (mysqli_query($conn, $query)) {
            if ($role == "Sales Manager") {
                echo '<script>window.location.href = "index-sa.php";</script>';
            } else if ($role == "Sales") {
                echo '<script>window.location.href = "sales/index.php";</script>';
            } else if ($role == "SA") {
                echo '<script>window.location.href = "dashboard-sales.php";</script>';
            } else {
                echo '<script>window.location.href = "index-sa.php";</script>';
            }
        } else {
            echo "Error: " . $query . "<br>" . mysqli_error($conn);
        }
    }
}

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
        ul#data-kegiatan-cust li:nth-child(odd) {
            background-color: white;
        }

        ul#data-kegiatan-cust li:nth-child(even) {
            background-color: #efefef;
            border-radius: 0;
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
        $todayDate = formatTanggal('dd MMMM yyyy');
        ?>
        <div class="container-fluid py-4">
            <div class="row d-flex flex-row">
                <div class="col-12 col-md-5 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-9 col-md-7 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
                                <div class="chart px-3">
                                    <h5 class="text-light text-bold">Tambah Kegiatan Baru</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="kegiatanForm">

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark" for="nama_customer">Nama Customer</label>
                                    <select class="form-control border p-2" id="nama_customer" name="customer">
                                        <?php
                                        $sql = "SELECT * FROM cust ORDER BY nama_toko ASC";
                                        $result = mysqli_query($conn, $sql);

                                        echo "<option value=0>Pilih Customer ...</option>";
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $id_customer = $row['id_cust'];
                                                $nama_customer = $row['nama_toko'];

                                                $nomorHandphone = $row['no_wa'];

                                                echo "<option value='$id_customer'>$nama_customer - $nomorHandphone</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Tidak ada customer tersedia</option>";
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" id="nama_cust" name="nama_cust">
                                </div>

                                <div class="form-group mt-2">
                                    <label class="font-weight-bold text-dark">Pilih Sales</label><br>
                                    <?php
                                    // Query untuk mengambil data sales
                                    $sql_sales = "SELECT id_sales, nama FROM sales";
                                    $result_sales = mysqli_query($conn, $sql_sales);

                                    // Loop untuk menampilkan checkbox untuk setiap sales
                                    while ($row_sales = mysqli_fetch_assoc($result_sales)) {
                                        $id_sales = $row_sales['id_sales'];
                                        $nama_sales = $row_sales['nama'];
                                    ?>
                                        <input type="checkbox" class="ms-4" id="sales_<?php echo $id_sales; ?>" name="sales[]" value="<?php echo $id_sales; ?>">
                                        <label for="sales_<?php echo $id_sales; ?>"><?php echo $nama_sales; ?></label><br>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <div class="form-group mt-2">
                                    <label class="font-weight-bold text-dark" for="tanggal_survey">Rencana Visit</label>
                                    <input type="datetime-local" class="form-control border p-2" id="tanggal_survey" name="tanggal">
                                </div>

                                <div class="form-group mb-4 mt-2">
                                    <label class="font-weight-bold text-dark" for="keterangan">Keterangan</label>
                                    <textarea class="form-control border p-2" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea>
                                </div>
                                <button type="submit" class="btn bg-gradient-info">Submit</button>
                            </form>

                        </div>

                    </div>
                </div>

                <div class="col-12 col-md-7 mt-4 mb-4" id="outputData">
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
                $.get("get_customers.php", function(data) {
                    $("#nama_customer").html(data);
                });
            }
        });
    </script>


    <script>
        var selectCustomer = document.getElementById('nama_customer');
        var inputNamaCust = document.getElementById('nama_cust');

        selectCustomer.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var selectedCustomerText = selectedOption.text;

            inputNamaCust.value = selectedCustomerText;

            var customerId = selectedOption.value;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'data-kegiatan-cust-sales.php?customer_id=' + customerId, true);
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    document.getElementById('outputData').innerHTML = xhr.responseText;
                } else {
                    console.error('Terjadi kesalahan saat melakukan request data kegiatan:', xhr);
                }
            };
            xhr.onerror = function() {
                console.error('Terjadi kesalahan saat melakukan request data kegiatan:', xhr);
            };
            xhr.send();
        });
    </script>
</body>

</html>