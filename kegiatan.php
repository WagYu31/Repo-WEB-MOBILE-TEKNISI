<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

    include "conn.php";
    
include "get-number-waiting.php";

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

    // Tangkap data dari form saat form disubmit
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $kegiatan = $_POST["kegiatan"];
        $tanggal = $_POST["tanggal"];
        $customer = $_POST["customer"];
        $keterangan = $_POST["keterangan"];
        $newCust = $_POST["newCustomerName"];
        $newPhone = $_POST["newCustomerPhone"];
        $newAddress = $_POST["newCustomerAddress"];
        $status = "Waiting"; // Menambahkan status dengan nilai "pending"
        
        
        $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
            // Query untuk memeriksa apakah kode_transaksi sudah ada dalam tabel kegiatan
            $queryKode = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
            $resultKode = mysqli_query($conn, $queryKode);
            
            if ($resultKode) {
                $rowKode = mysqli_fetch_assoc($resultKode);
                $count = $rowKode['count'];
            
                // Jika kode_transaksi sudah ada dalam tabel, ulangi pembuatan kode_transaksi
                while ($count > 0) {
                    $kode_transaksi = "LWX" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
                    // Kembali memeriksa kode_transaksi
                    $queryKode = "SELECT COUNT(*) AS count FROM kegiatan WHERE kode_transaksi = '$kode_transaksi'";
                    $resultKode = mysqli_query($conn, $queryKode);
            
                    if ($resultKode) {
                        $rowKode = mysqli_fetch_assoc($resultKode);
                        $count = $rowKode['count'];
                    } else {
                        // Penanganan kesalahan
                        die("Error in checking existing kode_transaksi: " . mysqli_error($conn));
                    }
                }
            }
        
        $no_tlp = preg_replace("/[^0-9]/", "", $newPhone);
    
        // Lakukan validasi data (jika diperlukan)
    
        // Ubah format nomor telepon
        if (substr($no_tlp, 0, 1) == "0") {
            // Jika angka pertama adalah 0, biarkan seperti itu
        } elseif (substr($no_tlp, 0, 2) == "62") {
            // Jika angka pertama adalah 62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 2);
        } elseif (substr($no_tlp, 0, 3) == "+62") {
            // Jika angka pertama adalah +62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 3);
        } elseif (substr($no_tlp, 0, 5) == "+6262") {
            // Jika angka pertama adalah +6262, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 5);
        } elseif (substr($no_tlp, 0, 4) == "6262") {
            // Jika angka pertama adalah 6162, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 4);
        } elseif (substr($no_tlp, 0, 6) == "+62+62") {
            // Jika angka pertama adalah +62+62, ganti dengan 0
            $no_tlp = "0" . substr($no_tlp, 6);
        } else {
            // Jika angka pertama bukan 0, 62, atau +62, tambahkan 0 di depannya
            $no_tlp = "0" . $no_tlp;
        }

        // Check if a customer with the same phone number exists
        $checkCustomerQuery = "SELECT nama FROM customer WHERE nomor_tlp = '$no_tlp'";
        $checkCustomerResult = mysqli_query($conn, $checkCustomerQuery);

        $duplikat = isset($_POST["duplikat"]) ? true : false;
        $tgl_now = date("Y-m-d H:i:s");
        
        if (mysqli_num_rows($checkCustomerResult) > 0 && !$duplikat) {
            $existingCustomer = mysqli_fetch_assoc($checkCustomerResult);
            $existingCustomerName = $existingCustomer['nama'];
        
            // If a customer with the same phone number exists and "Duplikat" is not checked, show an alert
            echo '<script>alert("PERINGATAN! Data pelanggan dengan nomor telepon yang sama sudah ada dengan nama: ' . $existingCustomerName . '\nJika ingin memasukkan data dengan nama atau alamat yang berbeda, silakan centang Duplikat Data.");</script>';
        } else {
            // If there is no existing customer with the same phone number, or "Duplikat" is checked, proceed to insert the kegiatan
            if (!empty($newCust) && !empty($newPhone) && !empty($newAddress)) {
                // Insert the new customer into the database
                $addSql = "INSERT INTO customer (nama, nomor_tlp, alamat) VALUES ('$newCust', '$no_tlp', '$newAddress')";
        
                if (mysqli_query($conn, $addSql)) {
                    // Get the ID of the newly inserted customer
                    $id_cust = mysqli_insert_id($conn);
        
                    // Continue with inserting a new activity (kegiatan)
                    $sql = "INSERT INTO kegiatan (id_cust, jenis, tgl_request, keterangan, status, req_by, kode_transaksi, tgl_update)
                            VALUES ('$id_cust', '$kegiatan', '$tanggal', '$keterangan', '$status', '$nmUser', '$kode_transaksi', '$tgl_now')";
        
                    if (mysqli_query($conn, $sql)) {

                        $hist = "Menambah kegiatan $kode_transaksi";
                        $tipe = "Tambah";
                        $history = "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)";
            
                        if ($stmtHistory = mysqli_prepare($conn, $history)) {
                            mysqli_stmt_bind_param($stmtHistory, "ssss", $nmUser, $hist, $tipe, $tgl_now);
                            if (mysqli_stmt_execute($stmtHistory)) {
                                // Eksekusi query berhasil
                            } else {
                                // Terjadi kesalahan saat eksekusi query
                                echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmtHistory);
                        }

                        if ($role == "Teknisi") {
                            echo '<script>window.location.href = "index-teknisi.php";</script>';
                        } else if ($role == "SA") {
                            echo '<script>window.location.href = "waiting_list.php";</script>';
                        } else if ($role == "Admin") {
                            echo '<script>window.location.href = "waiting_list.php";</script>';
                        } else {
                            echo '<script>window.location.href = "index-sales.php";</script>';
                        }
                    } else {
                        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                    }
                } else {
                    echo "Error: " . $addSql . "<br>" . mysqli_error($conn);
                }
            } else {
                // Insert a new activity (kegiatan) with the selected customer
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
                            // Eksekusi query berhasil
                        } else {
                            // Terjadi kesalahan saat eksekusi query
                            echo "Terjadi kesalahan dalam menambahkan catatan ke tabel history_line: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmtHistory);
                    }

                    if ($role == "Teknisi") {
                        echo '<script>window.location.href = "index-teknisi.php";</script>';
                    } else if ($role == "SA") {
                        echo '<script>window.location.href = "waiting_list.php";</script>';
                    } else if ($role == "Admin") {
                        echo '<script>window.location.href = "waiting_list.php";</script>';
                    } else {
                        echo '<script>window.location.href = "index-sales.php";</script>';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kegiatan</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
        include "dep-css.php";
    ?>
    <style>
        
        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px; /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 8px; /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red; /* Warna latar belakang notifikasi */
            color: white; /* Warna teks notifikasi */
            font-size: 10px; /* Ukuran teks notifikasi */
            border-radius: 50%; /* Membuat notifikasi menjadi lingkaran */
            padding: 2px 6px; /* Padding untuk notifikasi */
            vertical-align:middle;
            justify-content:center;
        }
        .new-customer-fields{
            margin-left:5vw;
            width:50vw;
            background-color:#e9ecef;
            padding:25px;
            border-radius:10px;
            padding-left:35px;
            padding-right:35px;
            margin-bottom:5vh;
        }
        p.small-text{
            font-size:11px;
            color:blue;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 5px 3px 15px rgba(0, 0, 0, 0.5);
        }
        
        li.nav-item a i, li.nav-item span{
            color:#4723D9;
        }
        
        ul.menuv li i{
            color:white;
        }
        .menuv li a span {
            width:65px;
            left:-17%;
        }
        .menuv li a span.tg{
            left:-45%;
        }
        
        @media (max-width: 768px) {
            .new-customer-fields{
                width:70vw;
            }
            .footer{
                margin-bottom:12vh;
            }
        }
    </style>
</head>
<body id="body-pd">
    <div class="container-fluid">
        <div class="row">
            <?php
                include "header.php";
            ?>
            
            <div class="l-navbar" id="nav-bar">
                <nav class="nav">
                    <div> <a href="#" class="nav_logo"> <img src="img/logo2.png" width="50px"></img> <span class="nav_logo-name">Loewix</span> </a>
                        <div class="nav_list">
                            <?php
                                if($role == "Admin"){
                                    ?>
                                    <a href="index.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link active"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>

                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else if($role == "SA"){
                                    ?>
                                    <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link active"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                        <?php if ($waitingCount > 0): ?>
                                            <span class="notif"><?php echo $waitingCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="history.php" class="nav_link"> <i class='bx bxs-time nav_icon'></i> <span class="nav_name">Riwayat</span> </a>
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link active"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else{
                                    ?>
                                    <a href="index-teknisi.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="profile-tek.php" class="nav_link"> <i class='bx bx-throphy nav_icon'></i> <span class="nav_name">Profile</span> </a>
                                    <!--<a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>-->
                                <?php
                                }
                            ?>
                            <!-- <a href="#" class="nav_link"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Stats</span> </a> -->
                        </div>
                    </div> <a href="logout.php" class="nav_link"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </a>
                </nav>
            </div>
            <?php
                include "btm-nav.php";
            ?>
            <!-- Konten Utama -->
            <main id="content" class="mx-auto">
                <div class="container">
                    <h2>Form Kegiatan</h2>
                    <!-- Form input data survey -->
                    <form method="POST" id="kegiatanForm">
            
                        <div class="form-group">
                            <label for="nama_customer">Nama Customer</label>
                            <select class="form-control" id="nama_customer" name="customer">
                                <?php
                                // Query untuk mengambil data customer dari tabel customer
                                $sql = "SELECT * FROM customer ORDER BY nama ASC";
                                $result = mysqli_query($conn, $sql);
            
                                // Periksa apakah ada data customer
                                echo "<option value=0>Pilih Customer ...</option>";
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $id_customer = $row['id_cust'];
                                        $nama_customer = $row['nama'];
                                        // Membuat opsi untuk setiap customer
                                        
                                        $nomorHandphone = $row['nomor_tlp'];
                                    
                                        echo "<option value='$id_customer'>$nama_customer - $nomorHandphone</option>";
                                    }
                                } else {
                                    echo "<option value=''>Tidak ada customer tersedia</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
            
                        <div class="form-group">
                            <input type="checkbox" id="newCustomerCheckbox">
                            <label for="newCustomerCheckbox">Tambahkan Customer Baru</label>
                        </div>
                        
                        <div class="new-customer-fields" style="display: none;">
                            <div class="form-group">
                                <label for="newCustomerName">Nama Customer Baru</label>
                                <input type="text" class="form-control" id="newCustomerName" name="newCustomerName" placeholder="Nama customer baru">
                            </div>
                            <div class="form-group">
                                <label for="newCustomerPhone">Nomor Telepon Customer Baru</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+62</span>
                                    </div>
                                    <input type="text" class="form-control" id="newCustomerPhone" name="newCustomerPhone" placeholder="Masukkan No WhatsApp">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="newCustomerAddress">Alamat Customer Baru</label>
                                <textarea class="form-control" id="newCustomerAddress" rows="3" name="newCustomerAddress" placeholder="Masukkan alamat"></textarea>
                            </div>
                            <div class="form-group">
                                <input type="checkbox" id="duplikat" name="duplikat">
                                <label for="duplikat">Duplikat Data</label>
                                <p class="small-text">* Centang jika ingin input data customer dengan nama atau alamat berbeda tapi memiliki nomor telepon yang sama.</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="Kegiatan">Kegiatan</label>
                            <select class="form-control" id="kegiatan" name="kegiatan">
                                <option value="Survey">Survey</option>
                                <option value="Pasang Baru">Pasang Baru</option>
                                <option value="Service">Service</option>
                                <!-- Tambahkan daftar nama teknisi lainnya di sini -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tanggal_survey">Tanggal dan Waktu Survey</label>
                            <input type="datetime-local" class="form-control" id="tanggal_survey" name="tanggal">
                        </div>
            
                        
            
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    
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

    <!-- Tambahkan script untuk mengatur sidebar responsif -->
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });
        });
    </script>
</body>
</html>