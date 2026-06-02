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
    <title>Loewix | Detail Kegiatan</title>
        <!-- Tambahkan favicon (logo) -->
        <link rel="icon" href="img/logo3.png" type="image/png">
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
        h3.pending, h3.besok, h3.reschedule {
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
            
            .tabs {
              display: flex;
              flex-wrap: wrap;
              max-width: 100%;
              background: #eee;
              margin-top:-20px;
              font-size:14px;
              box-shadow: 0 48px 80px -32px rgba(0,0,0,0.3);
            }
            .input {
              position: absolute;
              opacity: 0;
            }
            .label {
              width: 100%;
              padding: 20px 30px;
              background: #eee;
              cursor: pointer;
              font-weight: bold;
              font-size: 15px;
              color: #7f7f7f;
              transition: background 0.1s, color 0.1s;
            }
            .label:hover {
              background: #d8d8d8;
            }
            .label:active {
              background: #ccc;
            }
            .input:focus + .label {
              z-index: 1;
            }
            .input:checked + .label {
              background: #fff;
              color: #000;
            }
            @media (min-width: 600px) {
              .label {
                width: auto;
              }
            }
            .panel {
              display: none;
              padding: 20px 30px 30px;
              background: #fff;
            }
            @media (min-width: 600px) {
              .panel {
                order: 99;
              }
            }
            .input:checked + .label + .panel {
              display: block;
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
                                    <a href="index-sa.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
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
                                    <?php
                                }
                                else if($role == "Sales"){
                                    ?>
                                    <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                    <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                    <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                    <?php
                                }
                                else{
                                    ?>
                                    <a href="index-teknisi.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
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
                    <?php
                        $status = $row['status'];
                        $lok_mulai = $row["lokasi_mulai"];
                        $selesai = $row["tgl_selesai"];
                        $lok_selesai = $row["lokasi_selesai"];
                        $ket = $row["keterangan"];
                        $kode_transaksi = $row["kode_transaksi"];
                        $idTeam = $row["id_team"];
                        
                        // Contoh format datetime dalam variabel $request dan $mulai
                        $requestDatetime = $row['tgl_request'];
                        $mulaiDatetime = $row['tgl_mulai'];
                        
                        
                                        $namaHari = date("l", strtotime($requestDatetime));
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
                                    } elseif ($status == 'Reschedule2') {
                                        $statusClass = 'reschedule';
                                        $sts = 'Reschedule';
                                    } elseif ($status == 'Pause') {
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
                    <h2 class="text-lg">Detail Kegiatan : <?php echo $kode_transaksi;?></h2>
                    

                    <!-- Tampilkan detail kegiatan di sini -->

                            <!--<div class="container">-->
                                <div class="table-responsive">
                                <table class="table table-bordered tek">
                                    <tbody>
                                        <tr>
                                            <th colspan="2" class="mnt">DATA CUSTOMER</th>
                                        </tr>
                                        <tr>
                                            <th>Customer</th>
                                            <td><?php echo $row['nama_customer']; ?></td>
                                        </tr>
                                        
                                            <?php
                                                $telepon_cust = $row['telepon_cust'];
                                                
                                                // Periksa apakah nomor telepon diawali dengan "0"
                                                if (substr($telepon_cust, 0, 1) === '0') {
                                                    // Jika ya, ganti "0" menjadi "62"
                                                    $tlp_cust = '62' . substr($telepon_cust, 1);
                                                }
                                            ?>
                                            
                                        <tr>
                                            <th>No Telepon Customer</th>
                                            <td><a href="https://api.whatsapp.com/send?phone=<?php echo $tlp_cust; ?>"><?php echo $telepon_cust; ?></a></td>
                                        </tr>
                                        <tr>
                                            <th>Alamat Customer</th>
                                            <td><?php echo $row['alamat_customer']; ?></td>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="mnt">PERMINTAAN</th>
                                        </tr>
                                        <tr>
                                            <th>Kegiatan</th>
                                            <td><?php echo $row['jenis']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal</th>
                                            <td><?php echo $namaHariIndonesia . ", " . date('d-m-y', strtotime($row['tgl_request'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Waktu</th>
                                            <td><?php echo date('H:i', strtotime($row['tgl_request'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Keterangan</th>
                                            <td><?php echo $row['keterangan']; ?></td>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="mnt">TEKNISI</th>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                            <!--</div>-->
                            
                            
<?php

if ($role == "Admin" || $role == "SA") {
    ?>
    <div class="tabs">
      <?php
      $tab = 1;
      $getTekData = "SELECT k.*, t.nama AS getNamaTeknisi FROM kegiatan k JOIN teknisi t ON t.id_teknisi = k.id_teknisi  WHERE id_team = '$idTeam'";
      $resGetTekData = mysqli_query($conn, $getTekData);
      if (mysqli_num_rows($resGetTekData) > 0) {
          while($dataGetTekData = mysqli_fetch_assoc($resGetTekData)){
              $nama_GetTekData = $dataGetTekData["getNamaTeknisi"];
      ?>
      <input class="input" name="tabs" type="radio" id="tab-<?php echo $tab;?>" <?php echo ($tab === 1 ? 'checked="checked"' : ''); ?>/>
      <label class="label" for="tab-<?php echo $tab;?>"><?php echo $nama_GetTekData?></label>
      <div class="panel" style="width:100%;">
    <div class="container">
        <h5>Lihat Data <?php echo $dataGetTekData['getNamaTeknisi']; ?></h5>
        <button class="btn btn-info view-btn" data-id="<?php echo $dataGetTekData['id_kegiatan']; ?>">Selengkapnya</button>
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
                    <td><?php echo $dataGetTekData['tgl_mulai'] ? date('d-m-y', strtotime($dataGetTekData['tgl_mulai'])) : '-'; ?></td>
                    <td><?php echo $dataGetTekData['tgl_selesai'] ? date('d-m-y', strtotime($dataGetTekData['tgl_selesai'])) : '-'; ?></td>
                </tr>
                <tr>
                    <td>Waktu</td>
                    <td><?php echo $dataGetTekData['tgl_mulai'] ? date('H:i', strtotime($dataGetTekData['tgl_mulai'])) : '-'; ?></td>
                    <td><?php echo $dataGetTekData['tgl_selesai'] ? date('H:i', strtotime($dataGetTekData['tgl_selesai'])) : '-'; ?></td>
                </tr>
                <tr>
                    <td>Review</td>
                    <td colspan="2"><img id="gambarFinish" src="uploads/<?php echo $dataGetTekData['gambar_finish']; ?>" alt="Gambar Selesai" class="img-fluid"></td>
                </tr>
                <tr>
                    <td>Note</td>
                    <td colspan="2"><?php echo $dataGetTekData['ket_finish']; ?></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
      </div>
      <?php
            $tab++;
            }
        }
        else{
            echo "Data Teknisi tidak ditemukan.";
        }
                                                    
      ?>
    
    </div>

<?php

} else {
    echo "-";
}

?>

                            






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
    
    <script>
    $(document).ready(function() {
        $(".view-btn").click(function() {
            var kegiatanId = $(this).data("id");
            window.location.href = "detail_kegiatan_2.php?id_kegiatan=" + kegiatanId;
        });
    });
    </script>

</body>
</html>