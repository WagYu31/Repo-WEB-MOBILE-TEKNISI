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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $kegiatan = $_POST["kegiatan"];
        $customer = $_POST["customer"];
        $keterangan = $_POST["keterangan"];
        $status = "Waiting"; // Menambahkan status dengan nilai "Waiting"
        $tgl_now = date("Y-m-d H:i:s");
    
        // Periksa apakah ada kesalahan dalam form (misalnya, jika kegiatan atau customer kosong)
        if (empty($kegiatan) || empty($customer)) {
            echo "Kegiatan dan Customer harus diisi!";
        } else {
            // Masukkan data ke dalam tabel kegiatan
            $sql = "INSERT INTO kegiatan (id_cust, jenis, keterangan, status, req_by, tgl_update)
                    VALUES ('$customer', '$kegiatan', '$keterangan', '$status', '$nmUser', '$tgl_now')";
    
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
                    echo '<script>window.location.href = "index-sa.php";</script>';
                } else if ($role == "Admin") {
                    echo '<script>window.location.href = "index.php";</script>';
                } else if ($role == "Sales") {
                    echo '<script>window.location.href = "index-sales.php";</script>';
                }
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
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
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan-sales.php" class="nav_link active"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
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
                    <form method="POST">
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
                            <label for="nama_customer">Nama Customer</label>
                            <select class="form-control" id="nama_customer" name="customer">
                                <?php
                                // Query untuk mengambil data customer dari tabel customer
                                $sql = "SELECT id_cust, nama FROM customer";
                                $result = mysqli_query($conn, $sql);

                                // Periksa apakah ada data customer
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $id_customer = $row['id_cust'];
                                        $nama_customer = $row['nama'];
                                        // Membuat opsi untuk setiap customer
                                        echo "<option value='$id_customer'>$nama_customer</option>";
                                    }
                                } else {
                                    echo "<option value=''>Tidak ada customer tersedia</option>";
                                }
                                ?>
                            </select>
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
