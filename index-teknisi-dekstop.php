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

include "get-number-task.php";

$query = "SELECT * FROM user WHERE id_user = $id_user";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

// Selanjutnya, Anda dapat menggunakan $id_user sesuai kebutuhan Anda

$teknisi = $data['id_teknisi'];

// Kueri SQL untuk memilih id_teknisi dari tabel kegiatan yang memiliki value array
$sql = "SELECT k.id_teknisi, k.*, t.nama AS nama_teknisi, c.nama AS nama_customer
        FROM kegiatan k
        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
        LEFT JOIN customer c ON k.id_cust = c.id_cust
        WHERE FIND_IN_SET('$teknisi', k.id_teknisi) > 0
        ORDER BY 
            CASE 
                WHEN k.status = 'On Process' THEN 1
                WHEN k.status = 'Pending' THEN 2
                WHEN k.status = 'Clear' THEN 3
            END,
            CASE 
                WHEN k.status = 'On Process' THEN k.tgl_request
                WHEN k.status = 'Pending' THEN k.tgl_request
                WHEN k.status = 'Clear' THEN k.tgl_selesai
            END DESC";

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
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
        }
        td{
            font-size:14px;
        }
        .sync-btn, .sync-pause-btn{
            background-color:#e3b602;
            border:1px solid #e3b602;
        }
        .sync-btn:hover, .sync-pause-btn:hover{
            background-color:#b18e02;
            border:1px solid #b18e02;
        }
        /* CSS untuk mengatur ukuran video sesuai dengan modal */
        #cameraFeed {
            max-width: 100%;
            height: auto;
        }
        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px; /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 43vw; /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red; /* Warna latar belakang notifikasi */
            color: white; /* Warna teks notifikasi */
            font-size: 10px; /* Ukuran teks notifikasi */
            border-radius: 50%; /* Membuat notifikasi menjadi lingkaran */
            padding: 2px 6px; /* Padding untuk notifikasi */
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

        
        .finish-btn{
            background-color: #007bff;
            border:1px solid #007bff;
        }
        
        .p{
            margin-right:20px;
            font-size:13px;
        }
        
        .btn{
            font-size:12px;
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
        .navv input:checked ~ .menuv li:nth-child(1) {
          top: -80px;
          transition-delay: 0.1s;
        }
        .menuv li a span {
            width:65px;
            left:-17%;
        }

        
        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover, .finish-btn:hover {
            background-color: #0056b3; /* Mengubah warna latar saat dihover */
        }
            .table td.action-column {
                width: 100px;
                white-space: nowrap;
                text-align: center;
            }

        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            h2{
                font-size:25px;
                font-weight:bold;
            }
            th, #locationAddress{
                font-size:14px;
            }
            #locationAddress{
                margin-top:5vh;
            }
            td, i{
                font-size:13px;
            }
            .start-btn, .finish-btn{
                margin-bottom:5px;
            }
            /* Sembunyikan kolom kecuali Customer, Status, dan Aksi */
            /*th:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(9)),*/
            /*td:not(:nth-child(1)):not(:nth-child(2)):not(:nth-child(3)):not(:nth-child(4)):not(:nth-child(5)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(9)) {*/
            /*    display: none;*/
            /*}*/
            .table td.action-column {
                width: 70px;
                white-space: wrap;
                text-align: center;
            }
            td.action-column button{
                width:60px;
            }
            .finish-btn{
                margin-top:3px;
                margin-bottom:2.5px;
            }
            .sync-btn{
                margin-top:-2.5px;
                margin-bottom:3px;
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
            <div class="l-navbar d-none d-md-block" id="nav-bar">
                <nav class="nav">
                    <div> <a href="#" class="nav_logo"> <img src="img/logo2.png" width="50px"></img> <span class="nav_logo-name">Loewix</span> </a>
                        <div class="nav_list">
                            <?php
                                if($role == "Admin"){
                                    ?>
                                    <a href="index.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                    </a>

                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="input-customer.php" class="nav_link"> <i class='bx bx-user-plus nav_icon'></i> <span class="nav_name">Input Customer Baru</span> </a>
                                    <?php
                                }
                                else if($role == "SA"){
                                    ?>
                                    <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="waiting_list.php" class="nav_link">
                                        <i class='bx bx-pin nav_icon'></i>
                                        <span class="nav_name">Waiting List</span>
                                    </a>
                                    <a href="teknisi.php" class="nav_link"> <i class='bx bx-user-pin nav_icon'></i> <span class="nav_name">Teknisi</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="input-customer.php" class="nav_link"> <i class='bx bx-user-plus nav_icon'></i> <span class="nav_name">Input Customer Baru</span> </a>
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <a href="input-customer.php" class="nav_link"> <i class='bx bx-user-plus nav_icon'></i> <span class="nav_name">Input Customer Baru</span> </a>
                                    <?php
                                }
                                else{
                                    ?>
                                    <a href="index-teknisi.php" class="nav_link active">
                                        <i class='bx bx-grid-alt nav_icon'></i> 
                                        <span class="nav_name">Dashboard</span>
                                        <?php if ($taskCount > 0): ?>
                                            <span class="notif"><?php echo $taskCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="profile-tek.php" class="nav_link"> <i class='bx bx-trophy nav_icon'></i> <span class="nav_name">Profile</span> </a>
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

            
            <main id="content" class="mx-auto">
                <div class="container">
                
                <!--Bagian 2-->
                    <h2>Data Kegiatan</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Customer</th>
                                <!--<th>Teknisi</th>-->
                                <th>Kegiatan</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                                  
                                    $status = $row['status'];
                                    $statusClass = '';
                                    $id_keg = $row['id_kegiatan'];
            
                                    if ($status == 'Pending') {
                                        $statusClass = 'pending';
                                        $sts = 'Dijadwalkan';
                                    } elseif ($status == 'Reschedule' || $status == 'Reschedule2') {
                                        $statusClass = 'reschedule';
                                        $sts = 'Reschedule';
                                    } elseif ($status == 'Pause') {
                                        $statusClass = 'pause';
                                        $sts = 'Lanjut Nanti';
                                    } elseif ($status == 'On Process') {
                                        $statusClass = 'on-process';
                                        $sts = 'Di Proses';
                                    } elseif ($status == 'Clear') {
                                        $statusClass = 'clear';
                                        $sts = 'Selesai';
                                    }
                                    
                                    
                                    

                                        // Mendapatkan tanggal dan jam dalam format "dd-mm-yyyy H:i" dari "yyyy-mm-dd H:i:s"
                                        $datetime = $row["tgl_request"];
                                        $formattedTime = date("H:i", strtotime($datetime));
                                        $formattedDate = date("d-m-Y", strtotime($datetime));
                                        
                                        $namaHari = date("l", strtotime($datetime));
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
                                    
                                    
                                        echo "<tr>";
                                        echo "<td>" . $no . "</td>";
                                        if($status == "Clear"){
                                            echo "<td style='background-color:#5ada86; font-weight:bold;'>" . $sts . "</td>";
                                        }
                                        else{
                                            echo "<td>" . $sts . "</td>";
                                        }
                                        
                                        
                                    
                                    $getId = "SELECT * FROM reschedule WHERE id_kegiatan = ? ORDER BY tanggal DESC";
                                    $stmt = mysqli_prepare($conn, $getId);
                                        
                                        if ($stmt) {
                                            mysqli_stmt_bind_param($stmt, "i", $id_keg);
                                            mysqli_stmt_execute($stmt);
                                            $getIdRes = mysqli_stmt_get_result($stmt);
                                        
                                            if ($getIdRes) {
                                                if (mysqli_num_rows($getIdRes) > 0) {
                                                    // Pindahkan fetch_assoc ke luar loop while
                                                    $idGet = mysqli_fetch_assoc($getIdRes);
                                                    $tgl_res = $idGet['tanggal'];
                                                    $formattedTimeReq = date("H:i", strtotime($tgl_res));
                                                    $formattedDateReq = date("d-m-Y", strtotime($tgl_res));
                                                    
                                                    $namaHariReq = date("l", strtotime($tgl_res));
                                                    $namaHariIndonesiaReq = "";
                                                        switch ($namaHariReq) {
                                                            case "Monday":
                                                                $namaHariIndonesiaReq = "Senin";
                                                                break;
                                                            case "Tuesday":
                                                                $namaHariIndonesiaReq = "Selasa";
                                                                break;
                                                            case "Wednesday":
                                                                $namaHariIndonesiaReq = "Rabu";
                                                                break;
                                                            case "Thursday":
                                                                $namaHariIndonesiaReq = "Kamis";
                                                                break;
                                                            case "Friday":
                                                                $namaHariIndonesiaReq = "Jumat";
                                                                break;
                                                            case "Saturday":
                                                                $namaHariIndonesiaReq = "Sabtu";
                                                                break;
                                                            case "Sunday":
                                                                $namaHariIndonesiaReq = "Minggu";
                                                                break;
                                                        }
                                                        
                                                        if ($tgl_res != '0000-00-00 00:00:00') {
                                                            echo "<td style='text-align:center;'>" . $namaHariIndonesiaReq . ", " . $formattedDateReq . "</td>";
                                                            echo "<td style='text-align:center;'>" . $formattedTimeReq . "</td>";
                                                        } else {
                                                            echo "<td style='text-align:center;'>" . $namaHariIndonesia . ", " . $formattedDate . "</td>";
                                                            echo "<td style='text-align:center;'>" . $formattedTime . "</td>";
                                                        }
                                                } else {
                                                    echo "<td style='text-align:center;'>" . $namaHariIndonesia . ", " . $formattedDate . "</td>";
                                                    echo "<td style='text-align:center;'>" . $formattedTime . "</td>";
                                                }
                                            } else {
                                                echo "<td style='text-align:center;'>" . $namaHariIndonesia . ", " . $formattedDate . "</td>";
                                                echo "<td style='text-align:center;'>" . $formattedTime . "</td>";
                                            }
                                        } else {
                                            echo "<td style='text-align:center;'>" . $namaHariIndonesia . ", " . $formattedDate . "</td>";
                                            echo "<td style='text-align:center;'>" . $formattedTime . "</td>";
                                        }
                                        


                                        echo "<td>" . $row["nama_customer"] . "</td>";
                                        echo "<td>" . $row["jenis"] . "</td>";
                                        echo "<td>" . $row["keterangan"] . "</td>";
                                        echo "<td class='action-column'>";
                                        ?>
                                        <button class="btn btn-info view-btn" data-id="<?php echo $row['id_kegiatan']; ?>">View</button>
                                        <?php
                                        echo "</td></tr>";
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>Tidak ada data kegiatan.</td></tr>";
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
                    
                    
                    
                <!--Bagian 1    -->
                <div id="locationAddress"></div>
                <div id="map" style="height: 200px; z-index:-1;"></div>
                <button type="button" class="btn btn-primary" id="refreshLocationBtn">Refresh Lokasi</button>
                    
                </div>
            </main>
            
            
<!-- Modal Pop-up Finish Button-->
    <div class="modal fade" id="finishModal" tabindex="-1" role="dialog" aria-labelledby="startModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document"> <!-- Tambahkan class modal-dialog-centered -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="startModalLabel">Input Keterangan Mulai</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='index-teknisi.php';"> 
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                <!-- Video untuk menampilkan aliran dari kamera -->
                <video id="cameraFeed" autoplay></video>

                    <!-- Tombol "Capture" untuk mengambil gambar -->
                    <button type="button" class="btn btn-primary" id="captureBtn">Capture</button>
                    <!-- Input file untuk mengunggah gambar -->
                    <input type="file" accept="image/jpeg" id="imageUpload" style="display: none;visibility:hidden;">
                    <img id="capturedImage" src="" alt="Captured Image" style="max-width: 100%; display: none;">
                    <br>
                    <label for="keteranganMulai">Keterangan:</label>
                    <input type="text" class="form-control" id="keteranganFinish" placeholder="Keterangan">
                    
                    <!-- Gambar hasil capture -->
                    
                </div>
                <div class="modal-footer">
                    <!--<button type="button" class="btn btn-secondary" data-dismiss="modal" id="tutup">Tutup</button>-->
                    <button type="button" class="btn btn-primary" id="saveFinishBtn">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    
    
    <!-- Modal Pop-up Sync -->
<div class="modal fade" id="syncModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel">Reschedule Jadwal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="syncForm">
                    <div class="form-group">
                        <label for="syncDate">Tanggal :</label>
                        <input type="date" class="form-control" id="syncDate" name="syncDate" required>
                    </div>
                    <div class="form-group">
                        <label for="syncTime">Jam :</label>
                        <input type="time" class="form-control" id="syncTime" name="syncTime" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <!--<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>-->
                <button type="button" class="btn btn-primary" id="syncSubmit">Simpan</button>
            </div>
        </div>
    </div>
</div>


    <!-- Modal Pop-up Sync Pause-->
<div class="modal fade" id="syncPauseModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel">Reschedule Jadwal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="syncForm">
                    <div class="form-group">
                        <label for="syncPauseDate">Tanggal :</label>
                        <input type="date" class="form-control" id="syncPauseDate" name="syncPauseDate" required>
                    </div>
                    <div class="form-group">
                        <label for="syncPauseTime">Jam :</label>
                        <input type="time" class="form-control" id="syncPauseTime" name="syncPauseTime" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <!--<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>-->
                <button type="button" class="btn btn-primary" id="syncPauseSubmit">Simpan</button>
            </div>
        </div>
    </div>
</div>

    
    
<!-- Modal Pop-up Pause -->
<div class="modal fade" id="pauseModal" tabindex="-1" role="dialog" aria-labelledby="startModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="startModalLabel">Tangguhkan Kegiatan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="pauseForm">
                    <input type="hidden" id="pauseKegiatanId" name="pauseKegiatanId">
                    <div class="form-group">
                        Status kegiatan Anda akan ditangguhkan sampai tanggal yang akan Anda tentukan.
                        <label for="reDate">Tanggal :</label>
                        <input type="date" class="form-control" id="reDate" name="reDate" required>
                    </div>
                    <div class="form-group">
                        <label for="reTime">Jam :</label>
                        <input type="time" class="form-control" id="reTime" name="reTime" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan :</label>
                        <input type="text" class="form-control" id="keterangan" name="keterangan" placeholder="Keterangan">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="pauseSubmit">Simpan</button>
            </div>
        </div>
    </div>
</div>

    
    
        </div>
    </div>
    
    <?php
        include "foot.php";
    ?>

    <script src="js/script.js"></script>
    <script src="script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <script>
        $('.dropdown-toggle').dropdown()
    </script>
    
    <script>
    $(document).ready(function() {
        // Event handler untuk tombol "Pause"
        $(document).on('click', '.pause-btn', function() {
            var id_kegiatan = $(this).data('id');
            $('#pauseKegiatanId').val(id_kegiatan);
        });
        
        // Event handler untuk tombol "Simpan" pada modal pause
        $('#pauseSubmit').click(function() {
            var id_kegiatan = $('#pauseKegiatanId').val();
            var today = new Date();
            var date = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
            var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
            var dateTime = date + ' ' + time;
            var reDate = $('#reDate').val();
            var reTime = $('#reTime').val();
            var keterangan = $('#keterangan').val();
            
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    var location = latitude + "," + longitude;
        
                    // Kirim data ke server melalui AJAX
                    $.ajax({
                        type: "POST",
                        url: "proses_pause.php",
                        data: {
                            id_kegiatan: id_kegiatan, // Perbaiki variabel yang tidak sesuai
                            reDate: reDate,
                            reTime: reTime,
                            lokasi_selesai: location,
                            tgl_selesai: dateTime,
                            keterangan: keterangan
                        },
                        success: function(response) {
                            if (response === "success") {
                                // Berhasil, tutup modal dan lakukan sesuatu jika diperlukan
                                $('#pauseModal').modal('hide');
                                alert("Kegiatan telah ditangguhkan.");
                                // Refresh halaman atau tindakan lainnya
                                window.location.reload();
                            } else {
                                // Gagal, lakukan sesuatu jika diperlukan
                                alert("Terjadi kesalahan. Silakan coba lagi.");
                            }
                        }
                    });
                });
            } else {
                alert("Geolocation tidak didukung oleh perangkat Anda.");
            }
        });
    });
    </script>


</body>
</html>