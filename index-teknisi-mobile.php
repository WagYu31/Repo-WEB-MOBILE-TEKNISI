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
        WHERE FIND_IN_SET('$teknisi', k.id_teknisi) > 0 AND k.status != 'Clear'
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
            END ASC";

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
    
    .custom-list-item {
        background-color: #0808fe; /* Warna latar belakang */
        border: 1px solid #0808fe; /* Warna garis tepi */
        border-radius: 10px; /* Tampilan sudut melengkung */
        border-top-left-radius: 0;
        padding: 15px; /* Ruang dalam setiap item */
        margin-bottom: 15px; /* Jarak antar list */
        transition: background-color 0.3s ease; /* Transisi saat hover */
    }

    .custom-list-item:hover {
        text-decoration: none;
        opacity:0.7;
    }

    .custom-list-item h5 {
        margin-bottom: 0; /* Menghilangkan margin bawah pada judul */
        color:#fff;
        font-weight: bold;
    }

    .custom-list-item p {
        color:#fff;
        margin-bottom: 5px; /* Jarak antar paragraf */
    }

    .custom-list-item .day {
        font-size: 0.9em; /* Ukuran huruf hari */
        color: #fff; /* Warna teks hari */
    }

    .line{
        font-size: 20px;
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
        .tdy, .tmr, .cms{
            color:white;
            font-weight: bold;;
            padding: 3px;
            padding-left: 10px;
            width: 130px;
            border-radius: 5px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        .tdy{
            background-color: #2ecc71;
            border: 2px solid #2ecc71;
        }
        .tmr{
            background-color: #f4c20d;
            border: 2px solid #f4c20d;
        }
        .cms{
            background-color: white;
            color:#0808fe;
            border: 2px solid #0808fe;
            border-bottom:none;
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
        <!-- Bagian 2 -->
        <h2>Kegiatan <?php echo $nmUser;?></h2>
        <div class="list-group">
            <?php
            $today = date("Y-m-d");
            $tomorrow = date("Y-m-d", strtotime("+1 day"));
            if (mysqli_num_rows($result) > 0) {
                $events = array();
                while ($row = mysqli_fetch_assoc($result)) {
                $tgl_request = date("Y-m-d", strtotime($row["tgl_request"]));
        
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
                            $formattedDate = date("d F Y", strtotime($datetime));
                            
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

                                if ($status == "Reschedule" || $status == "Reschedule2" || $status == "Pause") {

                                    $tabRes = "SELECT * FROM reschedule WHERE id_kegiatan = $id_keg ORDER BY id_resc DESC LIMIT 1";
                                    $resultTabRes = mysqli_query($conn, $tabRes);

                                    $tgl_res = "";

                                    // Check if there is at least one row returned
                                    if ($resultTabRes && mysqli_num_rows($resultTabRes) > 0) {
                                        $dataTabRes = mysqli_fetch_assoc($resultTabRes);
                                        $tgl_resc = $dataTabRes['tanggal'];
                                        if ($tgl_resc != '0000-00-00 00:00:00') {
                                            $tgl_res = $tgl_resc;
                                        } else {
                                            $tgl_res = $row['tgl_reschedule'];
                                        }
                                    } else {
                                        // Handle the case where no rows were returned, if needed
                                        $tgl_res = $row['tgl_reschedule'];
                                    }

                                    $formattedTimeReq = date("H:i", strtotime($tgl_res));
                                    $formattedDateReq = date("d F Y", strtotime($tgl_res));

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

                                    if ($tgl_res != '0000-00-00 00:00:00' && $tgl_res != NULL) {
                                        $namaHariIndonesia = $namaHariIndonesiaReq;
                                        $formattedDate = $formattedDateReq;
                                        $formattedTime = $formattedTimeReq;
                                        $tgl_res = date("Y-m-d", strtotime($tgl_res));
                                        $tgl_request = $tgl_res;
                                    } else {
                                        $namaHariIndonesia;
                                        $formattedDate;
                                        $formattedTime;
                                        $tgl_request;
                                    }
                                } 
                    
                                if ($tgl_request == $today) {
                                    $label = 'Hari ini';
                                    $kelas = 'tdy';
                                } elseif ($tgl_request == $tomorrow) {
                                    $label = 'Besok';
                                    $kelas = 'tmr';
                                } else {
                                    $label = 'Akan Datang';
                                    $kelas = 'cms';
                                }
                            
                                // Tambahkan data ke dalam array
                                $events[] = array(
                                    'label' => $label,
                                    'kelas' => $kelas,
                                    'id_kegiatan' => $row['id_kegiatan'],
                                    'nama_customer' => $row['nama_customer'],
                                    'namaHariIndonesia' => $namaHariIndonesia,
                                    'formattedDate' => $formattedDate,
                                    'formattedTime' => $formattedTime,
                                    'jenis' => $row['jenis']
                                );
                            }
                            
                            // Fungsi untuk dibandingkan dan diurutkan berdasarkan label dan tanggal
                            function compareEvents($a, $b) {
                                if ($a['label'] != $b['label']) {
                                    return ($a['label'] == 'Hari ini') ? -1 : 1;
                                } else {
                                    return strcmp($a['formattedDate'], $b['formattedDate']);
                                }
                            }
                            
                            // Urutkan array menggunakan fungsi compareEvents
                            usort($events, 'compareEvents');
                            
                            // Tampilkan hasil
                            foreach ($events as $event) {
                                echo '<div class="' . strtolower($event['kelas']) . '">' . $event['label'] . '</div>';
                                echo '<a href="detail-kegiatan-teknisi.php?id_kegiatan=' . $event['id_kegiatan'] . '" class="list-group-item custom-list-item">';
                                echo '<div class="d-flex w-100 justify-content-between">';
                                echo '<h5 class="mb-1">' . $event["nama_customer"] . '</h5>';
                                echo '</div>';
                                echo '<p class="mb-1">' . $event['namaHariIndonesia'] . ', ' . $event['formattedDate'] . ' <span class="line">|</span> ' . $event['formattedTime'] .'</p>';
                                echo '<p class="day">Kegiatan : ' . $event["jenis"] . '</p>';
                                echo '</a>';
                            }
                }
             else {
                echo '<p class="text-muted">Tidak ada data kegiatan.</p>';
            }
            ?>
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
                <!-- <div id="locationAddress"></div>
                <div id="map" style="height: 200px; z-index:-1;"></div>
                <button type="button" class="btn btn-primary" id="refreshLocationBtn">Refresh Lokasi</button>
                    
                </div> -->
            </main>
            
    
    
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


</body>
</html>