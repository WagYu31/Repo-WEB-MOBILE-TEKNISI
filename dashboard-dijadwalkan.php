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
        WHERE k.status IN ('Pending') AND DATE(k.tgl_request) >= CURDATE()
        ORDER BY k.tgl_request DESC";

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
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-md-n4 mt-2 position-absolute">
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
            <div class="card-footer p-3">
                <?php
                if($role == "SA"){
                    ?>
                    <a href="index-sa.php"><p class="mb-0">Selengkapnya<span class="float-end mt-1"><i class='bx bxs-chevron-right'></i></span></p></a>
                    <?php
                }
                else{
                    ?>
                    <a href="index.php"><p class="mb-0">Selengkapnya<span class="float-end mt-1"><i class='bx bxs-chevron-right'></i></span></p></a>
                    <?php
                }
                ?>
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
            WHERE k.status IN ('On Process', 'Reschedule', 'Reschedule2', 'Pause')
                AND (
                    DATE(k.tgl_reschedule) < CURDATE()
                    OR k.tgl_mulai < CURDATE()
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
            <main id="content" class="mx-auto">
                <!--<div class="container">-->
                    <h2>Data Kegiatan</h2>
                    <!-- Tabel data kegiatan -->
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Customer</th>
                                <th>Teknisi</th>
                                <th>Kegiatan</th>
                                <!--<th>Keterangan</th>-->
                                <th>Request By</th>
                                <th class="action-column">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $groupedData = []; // Membuat array untuk mengelompokkan data berdasarkan kode_transaksi
                                
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $kodeTransaksi = $row['kode_transaksi'];
                                
                                        // Jika kode_transaksi belum ada dalam groupedData, buat array baru untuknya
                                        if (!isset($groupedData[$kodeTransaksi])) {
                                            $groupedData[$kodeTransaksi] = [
                                                'tgl_request' => [],
                                                'tgl_reschedule' => [],
                                                // 'jam' => [],
                                                'customer' => [],
                                                'teknisi' => [],
                                                'jenis' => [],
                                                'keterangan' => [],
                                                'req_by' => [],
                                                'status' => [],
                                                'id_kegiatan' => []
                                            ];
                                        }
                                
                                        // Masukkan data ke dalam grup sesuai kode_transaksi
                                        $groupedData[$kodeTransaksi]['tgl_request'][] = $row['tgl_request'];
                                        $groupedData[$kodeTransaksi]['tgl_reschedule'][] = $row['tgl_reschedule'];
                                        // $groupedData[$kodeTransaksi]['jam'][] = $row['jam'];
                                        $groupedData[$kodeTransaksi]['customer'][] = $row['nama_customer'];
                                        $teknisiIds = explode(',', $row['id_teknisi']);
                                        $teknisiNames = [];
                                        foreach ($teknisiIds as $teknisiId) {
                                            // Lakukan query untuk mengambil nama teknisi berdasarkan ID
                                            $teknisiQuery = "SELECT nama FROM teknisi WHERE id_teknisi = " . intval($teknisiId);
                                            $teknisiResult = mysqli_query($conn, $teknisiQuery);
                                            if ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                                $teknisiNames[] = $teknisiRow['nama'];
                                            }
                                        }
                                        $groupedData[$kodeTransaksi]['teknisi'][] = implode("<br>", $teknisiNames);
                                        $groupedData[$kodeTransaksi]['jenis'][] = $row['jenis'];
                                        $groupedData[$kodeTransaksi]['keterangan'][] = $row['keterangan'];
                                        $groupedData[$kodeTransaksi]['req_by'][] = $row['req_by'];
                                        $groupedData[$kodeTransaksi]['status'][] = $row['status'];
                                        $groupedData[$kodeTransaksi]['id_kegiatan'][] = $row['id_kegiatan'];
                                    }
                                }
                                
                                $no = 1; // Nomor urutan baris
                                
                                foreach ($groupedData as $kodeTransaksi => $data) {
                                    echo "<tr>";
                                    echo "<td style='text-align:center;'>" . $no . "</td>";
                                    echo "<td>" . $kodeTransaksi . "</td>";
                                    
                                    // Update status based on your specified conditions
                                    $updatedStatus = '';
                                    switch ($data['status'][0]) {
                                        case 'Waiting':
                                            $updatedStatus = 'Belum Dijadwalkan';
                                            break;
                                        case 'Pending':
                                            $updatedStatus = 'Dijadwalkan';
                                            break;
                                        case 'On Process':
                                            $updatedStatus = 'Diproses';
                                            break;
                                        case 'Pause':
                                            $updatedStatus = 'Lanjut Nanti';
                                            break;
                                        case 'Clear':
                                            $updatedStatus = 'Selesai';
                                            break;
                                        case 'Reschedule':
                                            $updatedStatus = 'Dijadwalkan Ulang';
                                            break;
                                        case 'Reschedule2':
                                            $updatedStatus = 'Dijadwalkan Ulang';
                                            break;
                                        default:
                                            $updatedStatus = $data['status'][0]; // Keep the current status if it doesn't match any of the specified conditions
                                    }
                                
                                    echo "<td>" . $updatedStatus . "</td>";
                                    
                                        $datetime = $data["tgl_request"][0];
                                        
                                        // Format dan tampilkan tanggal dan waktu
                                        $formattedTime = date("H:i", strtotime($datetime));
                                        $formattedDate = date("d-m-Y", strtotime($datetime));
                                        echo "<td style='text-align:center;'>" . $formattedDate . "</td>";
                                        echo "<td style='text-align:center;'>" . $formattedTime . "</td>";

                                    echo "<td>" . $data['customer'][0] . "</td>";
                                    echo "<td>";
                                    foreach ($data['teknisi'] as $teknisiName) {
                                        // Query to select id_teknisi based on nama
                                        $teknisiQuery = "SELECT id_teknisi FROM teknisi WHERE nama = '" . mysqli_real_escape_string($conn, $teknisiName) . "'";
                                        $teknisiResult = mysqli_query($conn, $teknisiQuery);
                                    
                                        // Check if the query was successful and fetch the id_teknisi
                                        if ($teknisiRow = mysqli_fetch_assoc($teknisiResult)) {
                                            $teknisiId = $teknisiRow['id_teknisi'];
                                            $link = "teknisi_detail.php?id_teknisi=" . $teknisiId;
                                            echo "<a href='$link'>$teknisiName</a><br>";
                                        } else {
                                            // Handle the case where id_teknisi is not found for a given nama
                                            echo $teknisiName . "<br>";
                                        }
                                    }
                                    echo "</td>";
                                    echo "<td>" . $data['jenis'][0] . "</td>";
                                    // echo "<td>" . $data['keterangan'][0] . "</td>";
                                    echo "<td>" . $data['req_by'][0] . "</td>";
                                        echo "<td style='text-align:center;' class='duaratus'>";
                                        ?>
                                        <button class="btn btn-info view-btn" data-id="<?php echo $data['id_kegiatan'][0]; ?>"><i class="far fa-eye"></i></button>
                                        <!--<button class="btn btn-warning edit-btn" data-id="<?php echo $data['id_kegiatan'][0]; ?>"><i class="far fa-edit"></i></button>-->
                                        <button class="btn btn-danger delete-btn" data-id="<?php echo $kodeTransaksi; ?>"><i class="far fa-trash-alt"></i></button>
                                        <?php
                                        echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                                ?>

                        </tbody>
                    </table>
                    </div>
                    
                    <div class="btn-np">
                        <!-- Tampilkan tombol "Previous" jika tidak ada di halaman pertama -->
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


</body>
</html>