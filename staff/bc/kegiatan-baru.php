<?php
include "conn.php";
include "session.php";
$pageNow = "Kegiatan Baru";
include "get-user-data.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatan = $_POST["kegiatan"];
    $tanggal = $_POST["tanggal"];
    $customer = $_POST["customer"];
    $keterangan = $_POST["keterangan"];
    $status = "Waiting";


    $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $queryKode = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
    $resultKode = mysqli_query($conn, $queryKode);

    if ($resultKode) {
        $rowKode = mysqli_fetch_assoc($resultKode);
        $count = $rowKode['count'];

        while ($count > 0) {
            $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            $queryKode = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
            $resultKode = mysqli_query($conn, $queryKode);

            if ($resultKode) {
                $rowKode = mysqli_fetch_assoc($resultKode);
                $count = $rowKode['count'];
            } else {
                die("Error in checking existing kode_transaksi: " . mysqli_error($conn));
            }
        }
    }

    $no_tlp = preg_replace("/[^0-9]/", "", $newPhone);

    if (substr($no_tlp, 0, 1) == "0") {
    } elseif (substr($no_tlp, 0, 2) == "62") {
        $no_tlp = "0" . substr($no_tlp, 2);
    } elseif (substr($no_tlp, 0, 3) == "+62") {
        $no_tlp = "0" . substr($no_tlp, 3);
    } elseif (substr($no_tlp, 0, 5) == "+6262") {
        $no_tlp = "0" . substr($no_tlp, 5);
    } elseif (substr($no_tlp, 0, 4) == "6262") {
        $no_tlp = "0" . substr($no_tlp, 4);
    } elseif (substr($no_tlp, 0, 6) == "+62+62") {
        $no_tlp = "0" . substr($no_tlp, 6);
    } else {
        $no_tlp = "0" . $no_tlp;
    }

    $checkCustomerQuery = "SELECT nama FROM customer WHERE nomor_tlp = '$no_tlp'";
    $checkCustomerResult = mysqli_query($conn, $checkCustomerQuery);

    $duplikat = isset($_POST["duplikat"]) ? true : false;
    $tgl_now = date("Y-m-d H:i:s");

    if (mysqli_num_rows($checkCustomerResult) > 0 && !$duplikat) {
        $existingCustomer = mysqli_fetch_assoc($checkCustomerResult);
        $existingCustomerName = $existingCustomer['nama'];

        echo '<script>alert("PERINGATAN! Data pelanggan dengan nomor telepon yang sama sudah ada dengan nama: ' . $existingCustomerName . '\nJika ingin memasukkan data dengan nama atau alamat yang berbeda, silakan centang Duplikat Data.");</script>';
    } else {
        if (!empty($newCust) && !empty($newPhone) && !empty($newAddress)) {
            $addSql = "INSERT INTO customer (nama, nomor_tlp, alamat) VALUES ('$newCust', '$no_tlp', '$newAddress')";

            if (mysqli_query($conn, $addSql)) {
                $id_cust = mysqli_insert_id($conn);

                $sql = "INSERT INTO kegiatan (id_cust, jenis, tgl_request, keterangan, status, req_by, kode_transaksi, tgl_update)
                            VALUES ('$id_cust', '$kegiatan', '$tanggal', '$keterangan', '$status', '$nmUser', '$kode_transaksi', '$tgl_now')";

                if (mysqli_query($conn, $sql)) {

                    $hist = "Menambah kegiatan $kode_transaksi";
                    $tipe = "Tambah";
                    $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";

                    if ($stmtHistory = mysqli_prepare($conn, $history)) {
                        mysqli_stmt_bind_param($stmtHistory, "ssss", $nmUser, $hist, $tipe, $tgl_now);
                        if (mysqli_stmt_execute($stmtHistory)) {
                        } else {
                            echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmtHistory);
                    }

                    if ($role == "Teknisi") {
                        echo '<script>window.location.href = "index-sa.php";</script>';
                    } else if ($role == "SA") {
                        echo '<script>window.location.href = "index-sa.php";</script>';
                    } else if ($role == "Admin") {
                        echo '<script>window.location.href = "index-sa.php";</script>';
                    } else {
                        echo '<script>window.location.href = "index-sa.php";</script>';
                    }
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                }
            } else {
                echo "Error: " . $addSql . "<br>" . mysqli_error($conn);
            }
        } else {
            $sql = "INSERT INTO kegiatan (id_cust, jenis, tgl_request, keterangan, status, req_by, kode_transaksi, tgl_update)
                        VALUES ('$customer', '$kegiatan', '$tanggal', '$keterangan', '$status', '$nmUser', '$kode_transaksi', '$tgl_now')";

            if (mysqli_query($conn, $sql)) {

                $tgl_now = date("Y-m-d H:i:s");
                $hist = "Menambah kegiatan $kode_transaksi";
                $tipe = "Tambah";
                $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";

                if ($stmtHistory = mysqli_prepare($conn, $history)) {
                    mysqli_stmt_bind_param($stmtHistory, "ssss", $nmUser, $hist, $tipe, $tgl_now);
                    if (mysqli_stmt_execute($stmtHistory)) {
                    } else {
                        echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmtHistory);
                }

                if ($role == "Teknisi") {
                    echo '<script>window.location.href = "index-sa.php";</script>';
                } else if ($role == "SA") {
                    echo '<script>window.location.href = "index-sa.php";</script>';
                } else if ($role == "Admin") {
                    echo '<script>window.location.href = "index-sa.php";</script>';
                } else {
                    echo '<script>window.location.href = "index-sa.php";</script>';
                }
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
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


        .dropdown {
            position: relative;
            display: inline-block;
            width: 200px;
        }

        .dropdown-button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropdown-button .icon {
            margin-left: 10px;
        }

        #dropdownSearch {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
        }

        #dropdownItems {
            display: none;
            position: absolute;
            background-color: #f1f1f1;
            width: 100%;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            max-height: 200px;
            overflow-y: auto;

        }

        .dropdown-item {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: #ddd;
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
                                    <div class="dropdown">
                                        <label for="nama_customer">Nama Customer</label>
                                        <!-- Tambahkan type="button" -->
                                        <button type="button" class="dropdown-button" onclick="toggleDropdown(event)">
                                            Pilih Customer ▼
                                        </button>
                                        <div id="dropdownItems" style="display: none;">
                                            <input type="text" id="dropdownSearch" placeholder="Search...">
                                            <?php
                                            $sql = "SELECT * FROM customer ORDER BY nama ASC";
                                            $result = mysqli_query($conn, $sql);
                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $id_customer = $row['id'];
                                                    $nama_customer = $row['nama'];
                                                    $nomorHandphone = $row['telp'];

                                                    echo "<div class='dropdown-item'>$nama_customer - $nomorHandphone - $id_customer</div>";
                                                }
                                            } else {
                                                echo "";
                                            }
                                            ?>
                                            <!-- Tambahkan item lainnya di sini -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="nama_cust" name="nama_cust">
                                </div>

                                <div class="form-group mt-2">
                                    <label for="Kegiatan">Kegiatan</label>
                                    <select class="form-control border p-2" id="kegiatan" name="kegiatan">
                                        <option value="Survey">Survey</option>
                                        <option value="Pasang Baru">Pasang Baru</option>
                                        <option value="Service">Service</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="tanggal_survey">Tanggal dan Waktu Survey</label>
                                    <input type="datetime-local" class="form-control border p-2" id="tanggal_survey" name="tanggal">
                                </div>

                                <div class="form-group mb-4">
                                    <label for="keterangan">Keterangan</label>
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
        function toggleDropdown(event) {
            event.preventDefault();

            const dropdownItems = document.getElementById('dropdownItems');
            dropdownItems.style.display = dropdownItems.style.display === 'block' ? 'none' : 'block';
        }

        document.getElementById('dropdownSearch').addEventListener('input', function() {
            const filter = this.value.toUpperCase();
            const items = document.getElementsByClassName('dropdown-item');

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                const txtValue = item.textContent || item.innerText;
                item.style.display = txtValue.toUpperCase().includes(filter) ? '' : 'none';
            }
        });

        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown');
            const dropdownItems = document.getElementById('dropdownItems');
            if (!dropdown.contains(event.target)) {
                dropdownItems.style.display = 'none';
            }
        });

        document.getElementById('dropdownItems').addEventListener('click', function(event) {
            event.stopPropagation();
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
            xhr.open('GET', 'data-kegiatan-cust.php?customer_id=' + customerId, true);
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