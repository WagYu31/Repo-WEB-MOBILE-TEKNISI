<?php
// Pastikan pengguna sudah login
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Sisipkan file koneksi dan periksa id teknisi dari sesi
include "conn.php";

// Mengakses id_user dari sesi
$id_user = $_SESSION["id_user"];
$role = $_SESSION["role"];

include "get-user-data.php";

include "get-number-task.php";

$dataTek = "SELECT * FROM user WHERE id_user = $id_user";
$resultDataTek = mysqli_query($conn, $dataTek);
$rowDataTek = mysqli_fetch_assoc($resultDataTek);
$idTek = $rowDataTek["id_teknisi"];

$stat = "Clear";
$pasang = "Pasang Baru";
$surv = "Survey";
$serv = "Service";

$sql = "SELECT COUNT(*) AS survey_count FROM kegiatan WHERE FIND_IN_SET('$idTek', id_teknisi) > 0 AND status = '$stat' AND jenis = '$surv'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$surveyCount = $row['survey_count'];

$sqlPs = "SELECT COUNT(*) AS pasang_count FROM kegiatan WHERE FIND_IN_SET('$idTek', id_teknisi) > 0 AND status = '$stat' AND jenis = '$pasang'";
$resultPs = mysqli_query($conn, $sqlPs);
$rowPs = mysqli_fetch_assoc($resultPs);
$pasangCount = $rowPs['pasang_count'];

$sqlSe = "SELECT COUNT(*) AS service_count FROM kegiatan WHERE FIND_IN_SET('$idTek', id_teknisi) > 0 AND status = '$stat' AND jenis = '$serv'";
$resultSe = mysqli_query($conn, $sqlSe);
$rowSe = mysqli_fetch_assoc($resultSe);
$serviceCount = $rowSe['service_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loewix | Profile Teknisi</title>
        <!-- Tambahkan favicon (logo) -->
        <link rel="icon" href="img/logo3.png" type="image/png">
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
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height:auto;
            margin-top:18vh;
        }
        .profile-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            background: #f9f9f9;
            text-align: center;
            width: 40vw; /* 50% width on desktop */
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border: 2px solid #ddd;
            border-radius: 50%;
            margin: 0 auto 20px;
            margin-top:-90px;
            overflow: hidden;
            background-color:#fff;
        }

        .profile-photo img {
            padding:20px;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .task-counts {
            display: flex;
            justify-content: space-around;
        }

        .task-count {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            margin: 0 5px;
            font-size: 24px; /* Adjust the font size */
        }
        
        .task-count-survey, .task-count-pasang, .task-count-service {
            background: #fff; /* Blue for Survey */
            color: #000;
            border:1px solid #ddd;
        }

        .task-count-survey  {
            color: #007bff; /* Blue for Survey */
        }

        .task-count-pasang {
            color: #28a745; /* Green for Pasang Baru */
        }

        .task-count-service {
            color: #dc3545; /* Red for Service */
        }

        .task-count h5{
            font-size: 15px;
            height:50px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .task-count p{
            font-size: 30px;
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

        @media (max-width: 768px) {
            .profile-card {
                width: 90vw; /* 90% width on mobile */
                padding: 10px;
            }

            .task-count {
                font-size: 18px;
                padding: 10px;
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
                                    <a href="index-teknisi.php" class="nav_link">
                                        <i class='bx bx-grid-alt nav_icon'></i> 
                                        <span class="nav_name">Dashboard</span>
                                        <?php if ($taskCount > 0): ?>
                                            <span class="notif"><?php echo $taskCount; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="profile-tek.php" class="nav_link active"> <i class='bx bx-trophy nav_icon'></i> <span class="nav_name">Profile</span> </a>
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
                // Mendapatkan tanggal paling kecil dengan status 'Clear' dan id_teknisi yang sama
                $sqlMinDate = "SELECT MIN(tgl_request) AS earliest_date FROM kegiatan WHERE status = 'Clear' AND id_teknisi = $idTek";
                $resultMinDate = mysqli_query($conn, $sqlMinDate);
                $rowMinDate = mysqli_fetch_assoc($resultMinDate);
                $earliestDate = $rowMinDate['earliest_date'];
                
                if ($rowMinDate['earliest_date']) {
                    $earliestDate = $rowMinDate['earliest_date'];
                    // Ubah format tanggal ke format yang diinginkan
                    $earliestDateFormatted = date('d M Y', strtotime($earliestDate));
                } else {
                    $earliestDateFormatted = "..."; // Jika tidak ada data yang cocok
                }

            ?>
            
                        
        <?php
            include "btm-nav.php";
        ?>

            
            <main id="content" class="mx-auto">
                <div class="container">
                    <div class="profile-card">
                        <div class="profile-photo">
                            <img src="img/chart.png" alt="Profile Photo" class="prof">
                        </div>
                        <div class="profile-info">
                            <span class="nmUs" style="font-weight:bold;"><?php echo $nmUser; ?></span><br>
                            <?php echo $jabatan; ?><br>
                            <!--<p></p>Sejak <?php echo $earliestDateFormatted;?>, kamu berhasil menyelesaikan :</p>-->
                            <p></p>Kamu berhasil menyelesaikan :</p>
                        </div>
                        <div class="task-counts">
                            <div class="task-count task-count-survey">
                                <h5>Survey</h5>
                                <p><span id="count1" class="display-4"></span></p>
                            </div>
                            <div class="task-count task-count-pasang">
                                <h5>Pasang Baru</h5>
                                <p><span id="count2" class="display-4"></span></p>
                            </div>
                            <div class="task-count task-count-service">
                                <h5>Service</h5>
                                <p><span id="count3" class="display-4"></span></p>
                            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-animateNumber/0.0.14/jquery.animateNumber.min.js"></script>

<script>
    function animateRandomNumbers(ids, ends) {
        var timers = [];
        var currentValues = [];
        
        // Initialize the current values to 0
        for (var i = 0; i < ids.length; i++) {
            currentValues.push(0);
        }
        
        // Iterate through the elements and their corresponding end values
        for (var i = 0; i < ids.length; i++) {
            var id = ids[i];
            var end = ends[i];
            var $element = $(id);
            $element.text('0');
            
            var timer = setInterval(function() {
                // Update the current values randomly
                for (var i = 0; i < ids.length; i++) {
                    currentValues[i] = Math.floor(Math.random() * 1000); // Random between 0 and 999
                }
                
                // Update the text of all elements
                for (var i = 0; i < ids.length; i++) {
                    $(ids[i]).text(currentValues[i]);
                }
            }, 50); // Adjust the interval as needed
            
            timers.push(timer);
        }
        
        setTimeout(function() {
            for (var i = 0; i < timers.length; i++) {
                clearInterval(timers[i]);
            }
            
            // Set the final values for all elements
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                var end = ends[i];
                $(id).text(end);
            }
        }, 2000); // After 3 seconds, stop the animation and set the actual values
    }

    animateRandomNumbers(["#count1", "#count2", "#count3"], [<?php echo $surveyCount > 0 ? $surveyCount : '0'; ?>, <?php echo $pasangCount > 0 ? $pasangCount : '0'; ?>, <?php echo $serviceCount > 0 ? $serviceCount : '0'; ?>]);
</script>


</body>
</html>