<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

include "conn.php";

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

include "get-number-waiting.php";

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Calculate total pages
$total_records_sql = "SELECT COUNT(*) FROM history_line";
$result_total = mysqli_query($conn, $total_records_sql);
$total_records = mysqli_fetch_array($result_total)[0];
$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT * FROM history_line ORDER BY tanggal DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);

// include "get-next-page.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loewix | Dashboard</title>
    <!-- Tambahkan favicon (logo) -->
    <link rel="icon" href="img/logo3.png" type="image/png">
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
    include "dep-css.php";
    ?>
    <!-- Tambahkan gaya kustom untuk sidebar -->
    <style>
        .modal,
        .fade {
            width: 100vw;
            height: 100vh;
        }

        .modal-content {
            border-radius: 10px;
            /* Menambahkan radius sudut pada modal */
        }

        .modal-header {
            background-color: #007bff;
            /* Warna latar belakang header modal */
            color: #fff;
            /* Warna teks header modal */
            border-bottom: none;
            /* Menghilangkan garis bawah header modal */
        }

        .modal-title {
            font-weight: bold;
        }

        .modal-footer {
            border-top: none;
            /* Menghilangkan garis atas footer modal */
        }

        th {
            text-align: center;
            font-size: 16px;
        }

        td {
            font-size: 14px;
        }

        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px;
            /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 8px;
            /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red;
            /* Warna latar belakang notifikasi */
            color: white;
            /* Warna teks notifikasi */
            font-size: 10px;
            /* Ukuran teks notifikasi */
            border-radius: 50%;
            /* Membuat notifikasi menjadi lingkaran */
            padding: 3px 7px;
            /* Padding untuk notifikasi */
            vertical-align: middle;
            justify-content: center;
        }

        .btn-np {
            text-align: center;
        }

        /* Mengatur gaya untuk tombol "Previous" dan "Next" */
        .btn-np a {
            margin-right: 20px;
            /* Menambahkan jarak kanan 20px antara tombol */
            padding: 4px 10px;
            /* Menyesuaikan ukuran tombol */
            background-color: #007bff;
            /* Memberikan warna latar biru yang bagus */
            color: #fff;
            /* Memberikan warna teks putih */
            border: none;
            /* Menghilangkan border */
            border-radius: 5px;
            /* Memberikan sudut bulat pada tombol */
            text-decoration: none;
            /* Menghilangkan garis bawah pada tautan */
        }

        .edit-btn {
            color: white;
        }

        .btn {
            font-size: 13px;
            height: 35px;
        }

        .p {
            margin-right: 20px;
            font-size: 13px;
        }

        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover {
            background-color: #0056b3;
            /* Mengubah warna latar saat dihover */
        }

        .navbar {
            background-color: white;
            box-shadow: 5px 3px 15px rgba(0, 0, 0, 0.5);
        }

        li.nav-item a i,
        li.nav-item span {
            color: #4723D9;
        }

        ul.menuv li i {
            color: white;
        }

        .menuv li a span {
            width: 65px;
            left: -17%;
        }

        .menuv li a span.tg {
            left: -45%;
        }

        td.duaratus {
            width: 180px;
        }


        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }

            td,
            i {
                font-size: 13px;
            }

            h2 {
                font-size: 25px;
                font-weight: bold;
            }


            .footer {
                margin-top:-2vh;
                margin-bottom: 15vh;
            }

        }

        .mt-70 {
            margin-top: 70px;
        }

        .mb-70 {
            margin-bottom: 70px;
        }

        .card {
            box-shadow: 0 0.46875rem 2.1875rem rgba(4, 9, 20, 0.03), 0 0.9375rem 1.40625rem rgba(4, 9, 20, 0.03), 0 0.25rem 0.53125rem rgba(4, 9, 20, 0.05), 0 0.125rem 0.1875rem rgba(4, 9, 20, 0.03);
            border-width: 0;
            transition: all .2s;
        }

        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 1px solid rgba(26, 54, 126, 0.125);
            border-radius: .25rem;
        }

        .card-body {
            flex: 1 1 auto;
            padding: 1.25rem;
        }

        h5.card-title{
            font-weight: bold;
            text-align: center;
            margin-top:10px;
            margin-bottom: 10px;
            font-size: 30px;
        }

        .vertical-timeline {
            width: 100%;
            position: relative;
            padding: 2rem 0 1rem;
        }

        .vertical-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 107px;
            height: 100%;
            width: 4px;
            background: #e9ecef;
            border-radius: .25rem;
        }

        .vertical-timeline-element {
            position: relative;
            margin: 0 0 1rem;
        }

        .vertical-timeline--animate .vertical-timeline-element-icon.bounce-in {
            visibility: visible;
            animation: cd-bounce-1 .8s;
        }

        .vertical-timeline-element-icon {
            position: absolute;
            top: 0;
            left: 100px;
        }

        .vertical-timeline-element-icon .badge-dot-xl {
            box-shadow: 0 0 0 5px #fff;
        }

        .badge-dot-xl {
            width: 18px;
            height: 18px;
            position: relative;
        }

        .badge:empty {
            display: none;
        }


        .badge-dot-xl::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: .25rem;
            position: absolute;
            left: 50%;
            top: 50%;
            margin: -5px 0 0 -5px;
            background: #fff;
        }

        .vertical-timeline-element-content {
            position: relative;
            margin-left: 135px;
            font-size: .8rem;
        }

        .vertical-timeline-element-content .timeline-title {
            font-size: .8rem;
            text-transform: uppercase;
            margin: 0 0 .5rem;
            padding: 2px 0 0;
            font-weight: bold;
        }

        .vertical-timeline-element-content .vertical-timeline-element-date {
            display: block;
            position: absolute;
            left: -120px;
            top: 0;
            padding-right: 10px;
            text-align: right;
            color: #adb5bd;
            font-size: .7619rem;
            white-space: nowrap;
        }

        .vertical-timeline-element-content:after {
            content: "";
            display: table;
            clear: both;
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
                            if ($role == "Admin") {
                            ?>
                                <a href="index.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                <a href="waiting_list.php" class="nav_link">
                                    <i class='bx bx-pin nav_icon'></i>
                                    <span class="nav_name">Waiting List</span>
                                    <?php if ($waitingCount > 0) : ?>
                                        <span class="notif"><?php echo $waitingCount; ?></span>
                                    <?php endif; ?>
                                </a>

                                <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                            <?php
                            } else if ($role == "SA") {
                            ?>
                                <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                <a href="waiting_list.php" class="nav_link">
                                    <i class='bx bx-pin nav_icon'></i>
                                    <span class="nav_name">Waiting List</span>
                                    <?php if ($waitingCount > 0) : ?>
                                        <span class="notif"><?php echo $waitingCount; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                <a href="sales.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Sales</span> </a>
                                <a href="data-customer.php" class="nav_link"> <i class='bx bxs-group nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="history.php" class="nav_link active"> <i class='bx bxs-time nav_icon'></i> <span class="nav_name">Riwayat</span> </a>
                            <?php
                            } else if ($role == "Sales") {
                            ?>
                                <a href="index-sales.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                            <?php
                            } else {
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
        </div>
        <div class="row d-flex justify-content-center mt-70 mb-70">

            <div class="col-md-6">
                

                <div class="main-card mb-3 card">
                    <div class="card-body">
                        <h5 class="card-title"> TIMELINE</h5>
                        <div class="vertical-timeline vertical-timeline--animate vertical-timeline--one-column">
                            <?php
                            // $tgl_now = date("Y-m-d H:i:s");
                            // echo $tgl_now;
                            
                            while ($rowHistory = mysqli_fetch_assoc($result)) {
                                $nama = $rowHistory['nama'];
                                $tipe = $rowHistory['tipe'];
                                $history = $rowHistory['history'];
                                $tanggal = $rowHistory['tanggal'];
                                $formattedTime = date("H:i", strtotime($tanggal));
                                $formattedDate = date("d-m-Y", strtotime($tanggal));
                                
                                $namaHari = date("l", strtotime($tanggal));
                                $namaHariIndonesia = "";
                                    switch ($namaHari) {
                                        case "Monday":
                                            $namaHariIndonesia = "Senin";
                                            break;
                                        case "Tuesday":
                                            $namaHariIndonesia = "Selasa";
                                            break;
                                        case "Wednesday":
                                            $namaHariIndonesia = "Rabu";
                                            break;
                                        case "Thursday":
                                            $namaHariIndonesia = "Kamis";
                                            break;
                                        case "Friday":
                                            $namaHariIndonesia = "Jumat";
                                            break;
                                        case "Saturday":
                                            $namaHariIndonesia = "Sabtu";
                                            break;
                                        case "Sunday":
                                            $namaHariIndonesia = "Minggu";
                                            break;
                                    }
                            ?>
                            <div class="vertical-timeline-item vertical-timeline-element">
                                <div>
                                    <span class="vertical-timeline-element-icon bounce-in">
                                        <?php
                                            if($tipe == 'Tambah'){
                                                ?>
                                                <i class="badge badge-dot badge-dot-xl badge-success"> </i>
                                                <?php
                                            }
                                            elseif($tipe == 'Jadwal'){
                                                ?>
                                                <i class="badge badge-dot badge-dot-xl badge-primary"> </i>
                                                <?php
                                            }
                                            elseif($tipe == 'Hapus'){
                                                ?>
                                                <i class="badge badge-dot badge-dot-xl badge-danger"> </i>
                                                <?php
                                            }
                                            elseif($tipe == 'Edit' || $tipe == 'Login'){
                                                ?>
                                                <i class="badge badge-dot badge-dot-xl badge-warning"> </i>
                                                <?php
                                            }
                                        ?>
                                    </span>
                                    <div class="vertical-timeline-element-content bounce-in">
                                        <h4 class="timeline-title"><?php echo $nama;?></h4>
                                        <?php
                                        if ($history == "INSERT INTO history_line (nama, history, tipe, tanggal) VALUES (?, ?, ?, ?)") {
                                            $history = "LogIn Account";
                                        }
                                        ?>
                                        <p><?php echo $history;?></p>
                                        
                                        <span class="vertical-timeline-element-date"><?php echo $formattedDate . '<br>' . $formattedTime;?></span>
                                        <!-- <p>Meeting with USA Client, today at <a href="javascript:void(0);" data-abc="true">12:00 PM</a></p>
                                        <span class="vertical-timeline-element-date">9:30 AM</span> -->
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                            ?>

<div class="pagination justify-content-center mt-5">
    <nav aria-label="Page navigation">
        <ul class="pagination">

            <?php
            $num_links = 3; // Jumlah tautan sekitar halaman saat ini yang ingin ditampilkan

            // Tampilkan tautan untuk halaman pertama
            echo '<li class="page-item ' . ($page == 1 ? 'active' : '') . '"><a class="page-link" href="?page=1">1</a></li>';

            // Tampilkan tautan "..." jika ada halaman sebelumnya
            if ($page - $num_links > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Tampilkan tautan di sekitar halaman saat ini
            for ($i = max(2, $page - $num_links); $i <= min($total_pages - 1, $page + $num_links); $i++) {
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
            }

            // Tampilkan tautan "..." jika ada halaman setelahnya
            if ($total_pages - $page > $num_links + 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Tampilkan tautan untuk halaman terakhir
            echo '<li class="page-item ' . ($page == $total_pages ? 'active' : '') . '"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
            ?>

        </ul>
    </nav>
</div>


                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <?php
    include "foot.php";
    include "dep-js.php";
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



</body>

</html>