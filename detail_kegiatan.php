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

include "get-number-task.php";

    if (isset($_GET['id_kegiatan'])) {
        $id_kegiatan = $_GET['id_kegiatan'];
        // Query untuk mengambil data kegiatan berdasarkan id_kegiatan
        $sql = "SELECT k.*, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS telepon_cust, c.alamat AS alamat_customer FROM kegiatan k
                LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                LEFT JOIN customer c ON k.id_cust = c.id_cust
                WHERE k.id_kegiatan = $id_kegiatan";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
        } else {
            // Tampilkan pesan jika data tidak ditemukan
            echo "Data kegiatan tidak ditemukan.";
            exit;
        }
    } else {
        // Tampilkan pesan jika parameter id_kegiatan tidak ada
        echo "Parameter id_kegiatan tidak valid.";
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kegiatan</title>
    <!-- Sisipkan stylesheet Bootstrap -->
    <?php
        include "dep-css.php";
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <!-- Tambahkan gaya kustom untuk sidebar -->
    <link rel="stylesheet" href="css/style.css?rev=<?php echo time();?>">
    <link rel="stylesheet" type="text/css" href="css/foot.css?rev=<?php echo time();?>">
    <style>

        h2, h3{
            display: inline-block;
        }
        /* CSS untuk mengatur warna latar belakang berdasarkan status */
        h3 {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff; /* Warna teks putih */
            margin-right: 10px; /* Jarak antara h2 dan h3 */
        }

        /* Ganti warna latar belakang sesuai dengan status */
        h3.pending, h3.pause {
            background-color: #fdd224;
        }

        h3.on-process {
            background-color: green;
        }

        h3.clear {
            background-color: blue;
        }
        #map-mulai, #map-selesai{
            height:300px;
            width:30vw;
            z-index:0;
        }
        #gambarFinish{
            height:250px;
        }
        #map-selesai, #gambarFinish{
            display:inline-block;
        }
        
        .container table{
            margin-top:5vh;
        }
        th{
            text-align:center;
        }
        table.tek th{
            width:25%;
            text-align:left;
        }
        th.mnt{
            background-color:#eee;
            color:#343a40;
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
            padding: 2px 6px; /* Padding untuk notifikasi */
            vertical-align:middle;
            justify-content:center;
        }
        .sync-btn, .sync-pause-btn{
            background-color:#e3b602;
            border:1px solid #e3b602;
        }
        .sync-btn:hover, .sync-pause-btn:hover{
            background-color:#b18e02;
            border:1px solid #b18e02;
        }
        
        .finish-btn{
            background-color: #007bff;
            border:1px solid #007bff;
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
        .navv input:checked ~ .menuv li:nth-child(1) {
          top: -80px;
          transition-delay: 0.1s;
        }
            /* Gaya untuk baris "Tanggal Reschedule" dan "Waktu Reschedule" */
            table.three td:nth-child(2), table.three td:nth-child(3) {
                background-color: #e6f2ff; /* Ganti warna latar belakang sesuai keinginan Anda */
                font-weight: bold; /* Tambahkan tebal jika diperlukan */
                color: #000; /* Warna teks pada latar belakang yang berbeda */
            }
            
            table.three td{
                text-align:center;
            }
            
            /* Gaya untuk baris "Tanggal Reschedule" dan "Waktu Reschedule" */
            table.three td:nth-child(8) {
                text-align:left;
            }
            
            table.three tr th{
                vertical-align:middle;
                justify-content:center;
            }
        
        @media (max-width: 768px) {
            #map-mulai, #map-selesai{
                height:200px;
            }
            #map-selesai, #map-mulai {
                width: 100%;
                float: none;
            }
            #gambarFinish{
                height:auto;
                display:block;
                margin-top:10px;
            }
            #content{
                padding:0;
            }
            h3{
                margin-top:5vh;
                margin-bottom:0;
                font-size:18px;
                margin-left:15px;
            }
            h2{
                margin-top:10px;
                font-size:22px;
            }
            .container table{
                margin-top:2vh;
            }
            table.two td:first-child, table.two th:first-child {
                display: none; /* Menyembunyikan setiap td pertama */
            }
              .table-responsive {
                overflow-x: auto;
              }
              /* Atur lebar tabel untuk mode mobile */
              table.table {
                width: 100%;
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
            <!-- Konten Utama -->
            <main id="content" class="mx-auto">
                <div class="container">
                    <?php
                        $status = $row['status'];
                        $lok_mulai = $row["lokasi_mulai"];
                        $selesai = $row["tgl_selesai"];
                        $lok_selesai = $row["lokasi_selesai"];
                        $ket = $row["keterangan"];
                        
                        // Contoh format datetime dalam variabel $request dan $mulai
                        $requestDatetime = $row['tgl_request'];
                        $mulaiDatetime = $row['tgl_mulai'];
                        
                        // Memisahkan tanggal dari format datetime dalam format "d-m-y"
                        $requestDate = date('d M Y', strtotime($requestDatetime));
                        $mulaiDate = date('d M Y', strtotime($mulaiDatetime));
                        
                        // Memisahkan jam dari format datetime dalam format "H:i"
                        $requestTime = date('H:i', strtotime($requestDatetime));
                        $mulaiTime = date('H:i', strtotime($mulaiDatetime));

                        $statusClass = '';

                                    if ($status == 'Pending') {
                                        $statusClass = 'pending';
                                        $sts = 'Dijadwalkan';
                                    } elseif ($status == 'Reschedule') {
                                        $statusClass = 'reschedule';
                                        $sts = 'Reschedule';
                                    } elseif ($status == 'Besok') {
                                        $statusClass = 'besok';
                                        $sts = 'Lanjut Besok';
                                    } elseif ($status == 'On Process') {
                                        $statusClass = 'on-process';
                                        $sts = 'Di Proses';
                                    } elseif ($status == 'Clear') {
                                        $statusClass = 'clear';
                                        $sts = 'Selesai';
                                    }
                    ?>

                    <h3 class="<?php echo $statusClass; ?>"><?php echo $sts; ?></h3>
                    <h2 class="text-lg">Detail Kegiatan</h2>

<div class="container">
<div class="table-responsive">
  <table class="table table-bordered two">
        <thead class="thead-dark">
            <tr>
                <th></th>
                <th>Mulai</th>
                <th>Selesai</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tanggal</td>
                <td><?php echo $row['tgl_mulai'] ? date('d-m-y', strtotime($row['tgl_mulai'])) : '-'; ?></td>
                <td><?php echo $row['tgl_selesai'] ? date('d-m-y', strtotime($row['tgl_selesai'])) : '-'; ?></td>
            </tr>
            <tr>
                <td>Waktu</td>
                <td><?php echo $row['tgl_mulai'] ? date('H:i', strtotime($row['tgl_mulai'])) : '-'; ?></td>
                <td><?php echo $row['tgl_selesai'] ? date('H:i', strtotime($row['tgl_selesai'])) : '-'; ?></td>
            </tr>
            <tr>
                <td>Alamat Lokasi</td>
                <td><div id="locationAddress"></div></td>
                <td><div id="locationAddress2"></div></td>
            </tr>
            <tr>
                <td>Maps</td>
                <td><div id="map-mulai"></div></td>
                <td><div id="map-selesai"></div></td>
            </tr>
            <tr>
                <td>Review</td>
                <td colspan="2">
                                        <?php if (!empty($row['gambar_finish_1']) && $row['gambar_finish_1'] != '-') : ?>
                                            <img id="gambarFinish" src="uploads/<?php echo $row['gambar_finish_1']; ?>" alt="Gambar Selesai" class="img-fluid">
                                        <?php endif; ?>

                                        <?php if (!empty($row['gambar_finish_2']) && $row['gambar_finish_2'] != '-') : ?>
                                            <img id="gambarFinish" src="uploads/<?php echo $row['gambar_finish_2']; ?>" alt="Gambar Selesai" class="img-fluid">
                                        <?php endif; ?>

                                        <?php if (!empty($row['gambar_finish_3']) && $row['gambar_finish_3'] != '-') : ?>
                                            <img id="gambarFinish" src="uploads/<?php echo $row['gambar_finish_3']; ?>" alt="Gambar Selesai" class="img-fluid">
                                        <?php endif; ?>

                                        <?php if (!empty($row['gambar_finish_4']) && $row['gambar_finish_4'] != '-') : ?>
                                            <img id="gambarFinish" src="uploads/<?php echo $row['gambar_finish_4']; ?>" alt="Gambar Selesai" class="img-fluid">
                                        <?php endif; ?>

                                        <?php if (!empty($row['gambar_finish_5']) && $row['gambar_finish_5'] != '-') : ?>
                                            <img id="gambarFinish" src="uploads/<?php echo $row['gambar_finish_5']; ?>" alt="Gambar Selesai" class="img-fluid">
                                        <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Note</td>
                <td colspan="2"><?php echo $row['ket_finish']; ?></td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

                            
                            
<div class="container">
    <?php
        // Memeriksa apakah ada id_kegiatan yang sama dalam tabel reschedule
        $checkIdKegiatan = "SELECT COUNT(*) as total FROM reschedule WHERE id_kegiatan = $id_kegiatan";
        $result = mysqli_query($conn, $checkIdKegiatan);
        $rw = mysqli_fetch_assoc($result);
    
        if ($rw['total'] > 0) {
    ?>
<div class="table-responsive">
  <table class="table table-bordered three">
            <thead class="thead-dark">
                <tr>
                    <th style="width:3%;" rowspan="2" class="rw">No</th>
                    <th style="width:25%;" colspan="2">Reschedule</th>
                    <th style="width:25%;" colspan="2">Mulai</th>
                    <th style="width:25%;" colspan="2">Selesai</th>
                    <th style="width:22%;" rowspan="2" class="rw">Keterangan</th>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Data dari tabel reschedule
                echo "<tr>";
                echo "<td>" . 1 . "</td>";
                echo "<td>" . $requestDate . "</td>";
                echo "<td>" . $requestTime . "</td>";
                echo "<td>" . $mulaiDate . "</td>";
                echo "<td>" . $mulaiTime . "</td>";
                // echo "<td id='alamatLokasiMulai'></td>";

                $res = "SELECT * FROM reschedule WHERE id_kegiatan = $id_kegiatan";
                $resRes = mysqli_query($conn, $res);
                
                $no = 2;

                if ($resRes && mysqli_num_rows($resRes) > 0) {
                    $data = mysqli_fetch_assoc($resRes); // Ambil data pertama
                    $id_resc = $data["id_resc"];
                    $tgl_req = $data["tanggal"];
                    $mli = $data["tgl_mulai"];
                    $stat = $data["status"];
                    if($stat == "Pause"){
                        $stat = "Reschedule";
                    }
                    else if($stat == "Clear"){
                        $stat = "Selesai";
                    }
                    // $lok_mli = $data["lokasi_mulai"];
                    $allData = array(); // Inisialisasi array untuk semua data

                    echo "<td>" . date('d M Y', strtotime($data["tgl_selesai"])) . "</td>";
                    echo "<td>" . date('H:i', strtotime($data["tgl_selesai"])) . "</td>";
                    // echo "<td id='alamatLokasiSelesai'></td>";
                    echo "<td>" . $data["keterangan"] . "</td>";
                    echo "</tr>";

                    while ($data = mysqli_fetch_assoc($resRes)) {
                        $allData[] = $data; // Tambahkan data ke array
                    }

                    // Tampilkan semua data kecuali satu data terakhir
                    $count = count($allData);
                    for ($i = 0; $i < $count - 1; $i++) {
                        
                        $statu = $allData[$i]["status"];
                        if($statu == "Pause"){
                            $statu = "Reschedule";
                        }
                        else if($statu == "Clear"){
                            $statu = "Selesai";
                        }
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td>" . date('d M Y', strtotime($allData[$i]["tanggal"])) . "</td>";
                        echo "<td>" . date('H:i', strtotime($allData[$i]["tanggal"])) . "</td>";
                        echo "<td>" . date('d M Y', strtotime($allData[$i]["tgl_mulai"])) . "</td>";
                        echo "<td>" . date('H:i', strtotime($allData[$i]["tgl_mulai"])) . "</td>";
                        // echo "<td>" . $dt["lokasi_mulai"] . "</td>";
                        // echo "<td id='alamatLokasiMulai-$i'></td>";
                        echo "<td>" . date('d M Y', strtotime($allData[$i]["tgl_selesai"])) . "</td>";
                        echo "<td>" . date('H:i', strtotime($allData[$i]["tgl_selesai"])) . "</td>";
                        // echo "<td id='alamatLokasiSelesai-$i'></td>"; 
                        echo "<td>" . $allData[$i]["keterangan"] . "</td>";
                        echo "</tr>";
                        $no++;
                    }

                    // Query untuk mendapatkan data terakhir dari tabel reschedule
                    $akhir = "SELECT * FROM reschedule WHERE id_kegiatan = $id_kegiatan ORDER BY id_resc DESC LIMIT 1";
                    $resAkhir = mysqli_query($conn, $akhir);

                    if ($resAkhir && mysqli_num_rows($resAkhir) > 0) {
                        $dt = mysqli_fetch_assoc($resAkhir);
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td>" . date('d M Y', strtotime($dt["tanggal"])) . "</td>";
                        echo "<td>" . date('H:i', strtotime($dt["tanggal"])) . "</td>";
                        echo "<td>" . date('d M Y', strtotime($dt["tgl_mulai"])) . "</td>";
                        echo "<td>" . date('H:i', strtotime($dt["tgl_mulai"])) . "</td>";
                        // echo "<td id='alamatLokasiMulai-Terakhir'></td>";
                    } else {
                        // Tambahkan penanganan ketika data terakhir tidak ditemukan
                        echo "<tr><td colspan='4'>Data terakhir tidak ditemukan</td></tr>";
                    }
                }
                $selesai = $row["tgl_selesai"];
                if ($selesai == '00-00-0000' || empty($selesai)) {
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                } else {
                    echo "<td>" . date('d M Y', strtotime($selesai)) . "</td>";
                    echo "<td>" . date('H:i', strtotime($selesai)) . "</td>";
                }

                // echo "<td id='alamatLokasiSelesai-Terakhir'></td>";
                echo "<td>" . $ket . "</td>";
                echo "</tr>";
                ?>

            </tbody>
        </table>
        </div>
        <?php
            } else {
                // Tampilkan pesan jika tidak ada id_kegiatan yang sama dalam tabel reschedule
            }
        ?>
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
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="script.js"></script>

    <!-- Sisipkan script JavaScript untuk Google Maps -->
    <script>
        // Mendapatkan nilai lengkap dari kolom lokasi_mulai
        var lokasiMulai = "<?php echo $row['lokasi_mulai']; ?>";

        // Memisahkan koordinat latitude dan longitude
        var koordinatMulai = lokasiMulai.split(',');

        // Konversi string menjadi float
        var latitudeMulai = parseFloat(koordinatMulai[0]);
        var longitudeMulai = parseFloat(koordinatMulai[1]);

        // Inisialisasi peta dan atur koordinat awal untuk lokasi mulai
        var mapMulai = L.map('map-mulai').setView([latitudeMulai, longitudeMulai], 15);

        // Tambahkan layer peta OSM untuk lokasi mulai
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapMulai);

        // Tambahkan marker pada koordinat lokasi mulai
        L.marker([latitudeMulai, longitudeMulai]).addTo(mapMulai)
            .bindPopup('Lokasi Mulai')
            .openPopup();
            
        // Fungsi untuk mengambil alamat dari koordinat
        function getAddressFromCoordinates(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    var address = data.display_name;
                    document.getElementById('locationAddress').innerHTML = address;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('locationAddress').innerHTML = "Tidak dapat mengambil alamat. Pastikan GPS Aktif.";
                });
        }
        
        // Panggil fungsi untuk lokasi mulai
        getAddressFromCoordinates(latitudeMulai, longitudeMulai);

    </script>
    
        <!-- Sisipkan script JavaScript untuk Google Maps untuk lokasi selesai -->
    <script>
        // Mendapatkan nilai lengkap dari kolom lokasi_selesai
        var lokasiSelesai = "<?php echo $row['lokasi_selesai']; ?>";

        // Memisahkan koordinat latitude dan longitude
        var koordinatSelesai = lokasiSelesai.split(',');

        // Konversi string menjadi float
        var latitudeSelesai = parseFloat(koordinatSelesai[0]);
        var longitudeSelesai = parseFloat(koordinatSelesai[1]);

        // Inisialisasi peta dan atur koordinat awal untuk lokasi selesai
        var mapSelesai = L.map('map-selesai').setView([latitudeSelesai, longitudeSelesai], 15);

        // Tambahkan layer peta OSM untuk lokasi selesai
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapSelesai);

        // Tambahkan marker pada koordinat lokasi selesai
        L.marker([latitudeSelesai, longitudeSelesai]).addTo(mapSelesai)
            .bindPopup('Lokasi Selesai')
            .openPopup();
            
        // Fungsi untuk mengambil alamat dari koordinat
        function getAddressFromCoordinates(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    var address = data.display_name;
                    document.getElementById('locationAddress2').innerHTML = address;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('locationAddress2').innerHTML = "Tidak dapat mengambil alamat. Pastikan GPS Aktif.";
                });
        }
        
        // Panggil fungsi untuk lokasi mulai
        getAddressFromCoordinates(latitudeSelesai, longitudeSelesai);
    </script>
    

</body>
</html>