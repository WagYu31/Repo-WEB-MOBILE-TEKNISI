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

// Query untuk mengambil data dari tabel kegiatan dan JOIN dengan tabel teknisi dan customer
$sql = "SELECT k.*, t.nama AS nama_teknisi, c.nama AS nama_customer 
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        LEFT JOIN customer c ON k.id_cust = c.id_cust
        WHERE k.status IN ('Pending', 'On Process', 'Reschedule', 'Reschedule2', 'Pause')
        AND (
            DATE(k.tgl_reschedule) >= CURDATE()
            OR DATE(k.tgl_mulai) >= CURDATE()
            OR DATE(k.tgl_request) >= CURDATE()
        )
        ORDER BY 
            COALESCE(k.tgl_reschedule, k.tgl_mulai, k.tgl_request), 
            k.tgl_request";


    $result = mysqli_query($conn, $sql);
    
include "get-next-page.php";
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
        
        #locationAddress{
            margin-top:15px;
            margin-bottom:10px;
        }
        #map{
            margin-bottom:15px;
        }
        .modal, .fade{
            width:100vw;
            height:100vh;
        }
        .modal-content {
            border-radius: 10px; /* Menambahkan radius sudut pada modal */
        }
    
        .modal-header {
            background-color: #007bff; /* Warna latar belakang header modal */
            color: #fff; /* Warna teks header modal */
            border-bottom: none; /* Menghilangkan garis bawah header modal */
        }
    
        .modal-title {
            font-weight: bold;
        }
    
        .modal-footer {
            border-top: none; /* Menghilangkan garis atas footer modal */
        }
        th{
            text-align:center;
            font-size:16px;
        }
        td{
            font-size:14px;
        }
        .bg-gradient-primary{
            font-size:35px;
            color:white;
            padding:1.1rem;
            padding-top: .5rem;
            padding-bottom: .5rem;
            border-radius:10px;
            background-color:#4723D9;
            top:3px;
        }
        .card, .card-header, .card-footer{
            /*background-color: #F7F6FB;*/
            background-color: #FFF;
        }
        .card-footer, .text-end{
            border-top:none;
        }
        .card-footer{
            background-color:#4723D9;
            border:2px solid #4723D9;
        }
        .card-footer a{
            color:white;
            text-decoration:none;
        }
        .card{
            background-color:white;
            border:2px solid #4723D9;
        }
        .active-card-border{
            border:2px solid #173f5f;
        }
        .active-card-bg{
            background-color: #173f5f;
        }
        .horizontal{
            background-color:#ccc;
        }
        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px; /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 8px; /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red; /* Warna latar belakang notifikasi */
            color: white; /* Warna teks notifikasi */
            font-size: 10px; /* Ukuran teks notifikasi */
            border-radius: 50%; /* Membuat notifikasi menjadi lingkaran */
            padding: 3px 7px; /* Padding untuk notifikasi */
            vertical-align:middle;
            justify-content:center;
        }
        .btn-np{
            text-align:center;
        }/* Mengatur gaya untuk tombol "Previous" dan "Next" */
        .btn-np a{
            margin-right: 20px; /* Menambahkan jarak kanan 20px antara tombol */
            padding: 4px 10px; /* Menyesuaikan ukuran tombol */
            background-color: #007bff; /* Memberikan warna latar biru yang bagus */
            color: #fff; /* Memberikan warna teks putih */
            border: none; /* Menghilangkan border */
            border-radius: 5px; /* Memberikan sudut bulat pada tombol */
            text-decoration: none; /* Menghilangkan garis bawah pada tautan */
        }
        
        .edit-btn{
            color:white;
        }
        
        .btn{
            font-size:13px;
            height:35px;
        }
        
        .p{
            margin-right:20px;
            font-size:13px;
        }
        
        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover {
            background-color: #0056b3; /* Mengubah warna latar saat dihover */
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
        td.duaratus{
            width:180px;
        }
        .btn-warn img{
            height:35px;
            padding:0px;
            margin-top:-10px;
            float:left;
        }


        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            td, i{
                font-size:13px;
            }
            h2{
                font-size:25px;
                font-weight:bold;
            }
            .start-btn, .finish-btn{
                margin-bottom:5px;
            }
            /* Sembunyikan kolom kecuali Customer, Status, dan Aksi */
            th:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(9)):not(:nth-child(10)),
            td:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(9)):not(:nth-child(10)) {
                display: none;
            }
            .table td.action-column {
                width: 200px;
                white-space: nowrap;
                text-align: center;
            }
            
            .footer{
                margin-bottom:12vh;
            }
            .bg-gradient-primary i{
                font-size:25px;
            }
            .bg-gradient-primary{
                color:white;
                padding:1.1rem;
                padding-top: 0rem;
                padding-bottom: .3rem;
                border-radius:10px;
                top:3px;
            }

        }
    </style>
</head>
<body id="body-pd">
    <div class="container-fluid">
        <div class="row">
        <div class="col-xl-3 col-12 col-sm-6 mb-xl-0 mb-md-4 mb-1 mt-5">
          <div class="card active-card-border ">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-md-n4 mt-2 position-absolute active-card-border  active-card-bg">
                <i class='bx bxs-user'></i>
              </div>
              <div class="text-end pt-md-1 pt-0">
                <p class="text-sm mb-0 text-capitalize">Sedang Berlangsung</p>
                <?php

                $queryOn = "SELECT k.kode_transaksi, COUNT(*) as total_on_process 
                    FROM kegiatan k
                    WHERE k.status IN ('On Process', 'Reschedule', 'Reschedule2', 'Pause')
                        AND (
                            (k.tgl_reschedule IS NOT NULL AND DATE(k.tgl_reschedule) >= CURDATE())
                            OR (k.tgl_reschedule IS NULL AND k.tgl_mulai >= CURDATE())
                        ) 
                    GROUP BY k.kode_transaksi";

                $resultOn = mysqli_query($conn, $queryOn);

                if ($resultOn) {
                    $jumlah_on = mysqli_num_rows($resultOn); // Menghitung jumlah baris (bukan total data) karena kita sudah melakukan GROUP BY
                } else {
                    // Handle error jika query tidak berhasil
                    $jumlah_on = 0;
                }

                ?>
                <h4 class="mb-0"><?php echo $jumlah_on;?></h4>
              </div>

            </div>
            <!--<hr class="dark horizontal my-0">-->
            <div class="card-footer active-card-border active-card-bg p-3">
              <a href="index-sa.php"><p class="mb-0">Selengkapnya<span class="float-end mt-1"><i class='bx bxs-chevron-right'></i></span></p></a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-md-4 mb-1 mt-md-5 mt-1">
          <div class="card tealGreen">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-md-n4 mt-2 position-absolute tealGreen">
                 <i class='bx bxs-check-square'></i>
              </div>
              <div class="text-end pt-md-1 pt-0">
                <p class="text-sm mb-0 text-capitalize">Kegiatan Selesai</p>
                <?php

                $queryOn = "SELECT kode_transaksi, COUNT(*) as total_on_process FROM kegiatan WHERE status = 'Clear' GROUP BY kode_transaksi";
                $resultOn = mysqli_query($conn, $queryOn);

                if ($resultOn) {
                    $jumlah_on = mysqli_num_rows($resultOn); // Menghitung jumlah baris (bukan total data) karena kita sudah melakukan GROUP BY
                } else {
                    // Handle error jika query tidak berhasil
                    $jumlah_on = 0;
                }

                ?>
                <h4 class="mb-0"><?php echo $jumlah_on;?></h4>
              </div>

            </div>
            <!--<hr class="dark horizontal my-0">-->
            <div class="card-footer p-3 tealGreen">
              <a href="dashboard-sa.php"><p class="mb-0">Selengkapnya<span class="float-end mt-1"><i class='bx bxs-chevron-right'></i></span></p></a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-12 col-sm-6 mb-xl-0 mb-md-4 mb-1 mt-md-5 mt-1">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-md-n4 mt-2 position-absolute">
                <i class='bx bx-error-circle'></i>
              </div>
              <div class="text-end pt-md-1 pt-0">
                <p class="text-sm mb-0 text-capitalize">Tidak Terselesaikan</p>
                <?php

                $queryOn = "SELECT COUNT(DISTINCT k.kode_transaksi) as total_on_process 
                FROM kegiatan k
                WHERE k.status IN ('On Process', 'Reschedule', 'Reschedule2', 'Pause', 'Pending')
                    AND (
                        DATE(k.tgl_reschedule) < CURDATE()
                        OR k.tgl_mulai < CURDATE()
                        OR k.tgl_request < CURDATE()
                    )";

                $resultOn = mysqli_query($conn, $queryOn);
                
                $jumlah_on = 0; // Inisialisasi jumlah total kegiatan dalam proses
                
                if ($resultOn) {
                    // Loop through each row in the result set
                    while ($rowOn = mysqli_fetch_assoc($resultOn)) {
                        // Accumulate the total count
                        $jumlah_on += $rowOn['total_on_process'];
                    }
                } else {
                    // Handle error jika query tidak berhasil
                    $jumlah_on = 0;
                }


                ?>
                <h4 class="mb-0"><?php echo $jumlah_on;?></h4>
              </div>

            </div>
            <!--<hr class="dark horizontal my-0">-->
            <div class="card-footer p-3">
              <a href="dashboard-tidak-selesai.php"><p class="mb-0">Selengkapnya<span class="float-end mt-1"><i class='bx bxs-chevron-right'></i></span></p></a>
            </div>
          </div>
        </div>
        </div>
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
                                    <a href="index.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
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
                                    <a href="index-sa.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
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
                                    <a href="index-sales.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
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
                    </div>
            <main id="content" class="mx-auto">

            <?php include "data_kegiatan_hari_ini.php";?>
                    
                    
                    <div class="btn-np">
                        <?php

                        if ($currentPage > 1) {
                            echo '<a href="?page=' . ($currentPage - 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-left"></i></a>';
                        }
                        
                        echo '<span class="p"> Halaman ' . $currentPage . ' dari ' . $totalPages . ' </span>';
            
                        // Tampilkan tombol "Next" jika tidak ada di halaman terakhir
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '" class="btn btn-secondary"><i class="fas fa-chevron-right"></i></a>';
                        }
                        ?>
                    </div>
                <!--</div>-->
                <?php
                
                // Query untuk mengambil data kegiatan dengan status "Waiting"
                $sql = "SELECT k.*, t.nama AS nama_teknisi 
                        FROM kegiatan k
                        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                        WHERE k.status = 'Waiting'
                        ORDER BY 
                            CASE WHEN DATE(k.tgl_request) = CURDATE() THEN 1 
                                 WHEN DATE(k.tgl_request) > CURDATE() THEN 2
                                 ELSE 3 END,
                            CASE WHEN k.tgl_request = '0000-00-00 00:00:00' THEN k.tgl_update ELSE k.tgl_request END DESC,
                            CASE WHEN DATE(k.tgl_request) < CURDATE() THEN 1 ELSE 2 END,
                            k.tgl_request DESC";
                
                $result = mysqli_query($conn, $sql);
                ?>
                <h2>Waiting List Customer</h2>
                    <!-- Tabel data kegiatan Waiting -->
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Kegiatan</th>
                                <th width="10%">Nama Customer</th>
                                <th width="10%">Nomor WhatsApp</th>
                                <th width="7%">Status</th>
                                <th width="8%">Tanggal</th>
                                <th width="5%">Jam</th>
                                <th width="25%">Alamat</th>
                                <th width="10%">Request By</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td style='text-align:center;'>" . $no . "</td>";
                                    echo "<td>" . $row["jenis"] . "</td>";
                                
                                    // Query untuk mengambil nama customer berdasarkan id_cust
                                    $customerId = $row["id_cust"];
                                    $customerQuery = "SELECT * FROM customer WHERE id_cust = '$customerId'";
                                    $customerResult = mysqli_query($conn, $customerQuery);
                                
                                    if ($customerRow = mysqli_fetch_assoc($customerResult)) {
                                        echo "<td>" . $customerRow["nama"] . "</td>"; // Tampilkan tanggal request
                                        
                                        $nomorHandphone = $customerRow['nomor_tlp'];
                                    
                                        // Cek apakah nomor handphone dimulai dengan angka 0
                                        if (substr($nomorHandphone, 0, 1) === '0') {
                                            // Ganti angka 0 dengan 62
                                            $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                        }
                                        
                                        echo "<td><a href='https://api.whatsapp.com/send?phone=$nomorHandphone' target='_blank'>";
                                        echo $customerRow['nomor_tlp'];
                                        echo "</a></td>";
                                    
                                    if ($row["tgl_request"] == '0000-00-00 00:00:00') {
                                        echo "<td>Dilaporkan</td>";
                                        echo "<td>" . date('d-m-y', strtotime($row["tgl_update"])) . "</td>";
                                        echo "<td>" . date('H:i', strtotime($row["tgl_update"])) . "</td>";
                                    } else {
                                        // Mengambil tanggal request dari database
                                        $tgl_request = strtotime($row["tgl_request"]);
                                        
                                        $current_date = strtotime(date('Y-m-d')); 
                                        
                                        $cekDate = date('Y-m-d', $tgl_request);
                                        $today = new DateTime();
                                        $esok = $today->modify('+1 day');
                                        $tomorrow = $esok->format('Y-m-d');
                                        $lusa = $esok->modify('+1 days');
                                        $day_after_tomorrow = $lusa->format('Y-m-d');

                                        // Mengecek apakah tanggal request adalah besok atau lusa
                                        if ($tgl_request < $current_date) {
                                                echo '<td style="color: red; font-weight:bold;">Dijadwalkan</td>';
                                                // Tanggal request telah lewat, tambahkan highlight warna merah
                                                echo '<td style="color: red; font-weight:bold;">' . date('d-m-y', $tgl_request) . '</td>';
                                                echo '<td style="color: red; font-weight:bold;">' . date('H:i', $tgl_request) . '</td>';
                                        } else {
                                            // Mengecek apakah tanggal request melewati batas hari ini
                                            if ($cekDate == $current_date || $cekDate == $tomorrow || $cekDate == $day_after_tomorrow) {
                                            echo '<td style="color: blue; font-weight:bold;">Dijadwalkan</td>';
                                            echo '<td style="color: blue; font-weight:bold;">' . date('d-m-y', $tgl_request) . '</td>';
                                            echo '<td style="color: blue; font-weight:bold;">' . date('H:i', $tgl_request) . '</td>';
                                            } else {
                                                // Tanggal request masih dalam batas hari ini
                                                echo '<td>Dijadwalkan</td>';
                                                echo '<td>' . date('d-m-y', $tgl_request) . '</td>';
                                                echo '<td>' . date('H:i', $tgl_request) . '</td>';
                                            }
                                        }
                                    }


                                        echo "<td>" . $customerRow["alamat"] . "</td>";
                                    } else {
                                        echo "<td>Data Customer Tidak Ditemukan</td>"; // Tampilkan tanggal request
                                    }
                                
                                    echo "<td>" . $row["req_by"] . "</td>";
                                    echo "<td style='text-align:center;'>";
                                    ?>
                                    <button class="btn btn-primary jadwalkan-btn" data-id="<?php echo $row["id_kegiatan"]; ?>" data-tgl-request="<?php echo $row["tgl_request"]; ?>">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <?php
                                    echo ' <button class="btn btn-danger hapus-btn" data-id="' . $row["id_kegiatan"] . '" data-kode="' . $row["kode_transaksi"] . '" data-nama="' . $nmUser . '"><i class="far fa-trash-alt"></i></button>';
                                    echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='6'>Tidak ada data kegiatan Waiting.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                
                                <!-- Modal Jadwalkan -->
                <div class="modal fade" id="jadwalkanModal" tabindex="-1" role="dialog" aria-labelledby="jadwalkanModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="jadwalkanModalLabel">Jadwalkan Kegiatan</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="location.href='waiting_list.php';">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                                <div class="modal-body">
                                    <!-- Form to schedule an activity -->
                                    <form id="jadwalkanForm">
                                        <div class="form-group">
                                            <label for="tanggal">Tanggal:</label>
                                            <input type="date" class="form-control" id="tanggal" name="tanggal">
                                        </div>
                                        <div class="form-group">
                                            <label for="jam">Jam:</label>
                                            <input type="time" class="form-control" id="jam" name="jam">
                                        </div>
                                        <div class="form-group">
                                            <label for="nama_teknisi">Nama Teknisi</label>
                                            <?php
                                            // Query to fetch data from the 'teknisi' table
                                            $sql = "SELECT id_teknisi, nama FROM teknisi";
                                            $result = mysqli_query($conn, $sql);
                                
                                            // Check if there are any technicians available
                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $id_teknisi = $row['id_teknisi'];
                                                    $nama_teknisi = $row['nama'];
                                
                                                    // Display checkboxes for each technician
                                                    echo "<div class='form-check'>";
                                                    echo "<input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='$id_teknisi' id='teknisi$id_teknisi' disabled>";
                                                    echo "<label class='form-check-label' for='teknisi$id_teknisi'>$nama_teknisi</label>";
                                                    echo "</div>";
                                                }
                                            } else {
                                                echo "Tidak ada teknisi tersedia.";
                                            }
                                            ?>
                                        </div>
                                    </form>
                                </div>


                            <div class="modal-footer">
                                <!--<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>-->
                                <button type="button" class="btn btn-primary" id="submitJadwalkan">Jadwalkan</button>
                            </div>
                        </div>
                    </div>
                
                
                
            </main>
        </div>
    </div>
    

    <?php
        include "foot.php";
        include "dep-js.php";
    ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    
        $(".view-btn").click(function() {
            var kegiatanId = $(this).data("id");
            window.location.href = "detail_kegiatan_2.php?id_kegiatan=" + kegiatanId;
        });
        $(".edit-btn").click(function() {
            var kegiatanId = $(this).data("id");
            window.location.href = "edit_kegiatan.php?id_kegiatan=" + kegiatanId;
        });
    </script>
    
    
    <script>
    // Fungsi untuk mengkonfirmasi penghapusan
    $(".delete-btn").click(function() {
        var kodeTransaksi = $(this).data("id");
        if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
            // Redirect ke halaman yang akan menghapus kegiatan berdasarkan id_kegiatan
            window.location.href = "delete_kegiatan_sa.php?kodeTransaksi=" + kodeTransaksi;
        }
    });
    </script>
    
    <script>
        
        // Fungsi untuk menampilkan modal jadwalkan
        $(".jadwalkan-btn").click(function () {
            var kegiatanId = $(this).data("id");
            // Reset form modal
            $("#jadwalkanForm")[0].reset();
            // Menambahkan data-id ke form untuk mengidentifikasi kegiatan yang akan dijadwalkan
            $("#jadwalkanForm").attr("data-id", kegiatanId);
            // Menampilkan modal
            $("#jadwalkanModal").modal("show");
        
            // Ambil nilai tgl_request dari elemen data-tgl-request
            var tglRequest = $(this).data("tgl-request");
            var tanggalInput = document.getElementById("tanggal");
            var jamInput = document.getElementById("jam");
        
            // Periksa apakah tgl_request tidak kosong (tidak NULL)
            if (tglRequest) {
                // Pisahkan tanggal dan waktu dari tgl_request
                var tglWaktu = tglRequest.split(" ");
                if (tglWaktu.length === 2) {
                    var tanggal = tglWaktu[0];
                    var waktu = tglWaktu[1];
                    // Isi nilai pada input tanggal dan jam
                    tanggalInput.value = tanggal;
                    jamInput.value = waktu;
                    
                    handleDateChange();
                }
            }
        });

    
        // Fungsi untuk mengirim jadwal ke server
        $("#submitJadwalkan").click(function () {
            var kegiatanId = $("#jadwalkanForm").data("id");
            var tanggal = $("#tanggal").val();
            var jam = $("#jam").val();
            // Mengumpulkan teknisi yang terpilih
            // var selectedTechnicians = $(".teknisi-checkbox:checked").map(function () {
            //     return this.value;
            // }).get().join(",");
            // Mengumpulkan teknisi yang terpilih dalam bentuk array
            var selectedTechnicians = $(".teknisi-checkbox:checked").map(function () {
                return this.value;
            }).get();

            // Kirim data ke server menggunakan AJAX (sesuaikan dengan URL dan data yang dibutuhkan)
            $.ajax({
                url: "proses_jadwalkan.php", // Ganti dengan URL yang sesuai
                type: "POST",
                data: {
                    kegiatanId: kegiatanId,
                    teknisi: selectedTechnicians,
                    tanggal: tanggal,
                    jam: jam
                },
                success: function (response) {
                    if (response === "success") {
                        // Tutup modal setelah berhasil
                        $("#jadwalkanModal").modal("hide");
                        alert("Berhasil");
                        // Refresh halaman
                        window.location.reload();
                    } else {
                        alert("Gagal menjadwalkan kegiatan.");
                    }
                },
                error: function () {
                    alert("Terjadi kesalahan saat menghubungi server.");
                }
            });
        });
        
        
        // Fungsi untuk menampilkan modal konfirmasi penghapusan
        $(".hapus-btn").click(function () {
            var kegiatanId = $(this).data("id");
            var nama = $(this).data("nama");
            var kode = $(this).data("kode");
            if (confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
                // Kirim permintaan penghapusan ke server menggunakan AJAX (sesuaikan dengan URL yang sesuai)
                $.ajax({
                    url: "proses_hapus_kegiatan.php", // Ganti dengan URL yang sesuai
                    type: "POST",
                    data: {
                        kegiatanId: kegiatanId,
                        nama : nama,
                        kode : kode
                    },
                    success: function (response) {
                        if (response === "success") {
                            alert("Kegiatan berhasil dihapus.");
                            // Refresh halaman
                            window.location.reload();
                        } else {
                            alert("Gagal menghapus kegiatan.");
                        }
                    },
                    error: function () {
                        alert("Terjadi kesalahan saat menghubungi server.");
                    }
                });
            }
        });

    </script>
<script>
// Fungsi untuk menampilkan modal jadwalkan dan mengisi tanggal dan jam saat change dan load
function handleDateChange() {
    // Mendapatkan nilai tanggal yang dipilih
    var selectedDate = document.getElementById("tanggal").value;
    var selectedTime = document.getElementById("jam").value;

    // Mendapatkan semua kotak centang teknisi
    var checkboxes = document.querySelectorAll(".teknisi-checkbox");

    // Disable semua kotak centang
    checkboxes.forEach(function (checkbox) {
        checkbox.disabled = false;
    });

    // Lakukan permintaan AJAX saat tanggal berubah atau halaman dimuat
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "get-kegiatan-teknisi.php?tanggal=" + selectedDate, true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var kegiatanData = JSON.parse(xhr.responseText);

            checkboxes.forEach(function (checkbox) {
                var id_teknisi = checkbox.value;
                var teknisiData = kegiatanData.find(function (data) {
                    return data.id_teknisi == id_teknisi;
                });

                if (teknisiData) {
                    checkbox.disabled = false;
                    // Format ulang teks label
                    var formattedText = " ( ";
                    if (teknisiData.jenis) {
                        formattedText += teknisiData.jenis + " ";
                    }
                    formattedText += ` jam ${teknisiData.tgl_request.substring(11, 16)}`;
                    if (teknisiData.status == "Pending") {
                        formattedText += " - Dijadwalkan";
                    } else if (teknisiData.status == "On Process") {
                        formattedText += " - Dalam proses";
                    }
                    formattedText += ")";

                    checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "") + formattedText;
                } else {
                    // Jika tidak ada data teknisi, hapus teks yang ada di dalam tanda kurung
                    checkbox.nextElementSibling.textContent = checkbox.nextElementSibling.textContent.replace(/\(.*\)/, "");
                }
            });
        }
    };

    xhr.send();
}

// Tambahkan event listener ke elemen tanggal
document.getElementById("tanggal").addEventListener("change", handleDateChange);

// Jalankan kode saat halaman dimuat
window.addEventListener("load", handleDateChange);



</script>


<script>
    const jamInput = document.getElementById("jam");

    jamInput.addEventListener("input", function() {
        const selectedTime = new Date(`2000-01-01T${jamInput.value}`);
        const minTime = new Date(`2000-01-01T07:00`);
        const maxTime = new Date(`2000-01-01T20:00`);

        if (selectedTime < minTime || selectedTime > maxTime) {
            alert("Jam harus berada dalam rentang antara jam 07:00 pagi sampai jam 20:00 malam.");
            jamInput.value = ""; // Menghapus input jika waktu di luar rentang
        }
    });
</script>



</body>
</html>