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


if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];

    $query = "SELECT * FROM user WHERE id_user = $id_user";
    $res = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($res);

    // Selanjutnya, Anda dapat menggunakan $id_user sesuai kebutuhan Anda

    $teknisi = $data['id_teknisi'];

    // Kueri SQL untuk memilih id_teknisi dari tabel kegiatan yang memiliki value array
    $sql = "SELECT k.id_teknisi, k.*, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS nomor_customer, c.alamat AS alamat_customer
                FROM kegiatan k
                LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                LEFT JOIN customer c ON k.id_cust = c.id_cust
                WHERE FIND_IN_SET('$teknisi', k.id_teknisi) > 0 AND k.id_kegiatan = '$id_kegiatan'
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
} else {
    // Tampilkan pesan jika parameter id_kegiatan tidak ada
    echo "Parameter id_kegiatan tidak valid.";
    exit;
}

include "get-next-page.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loewix | Detail Kegiatan Teknisi</title>
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
        body {
            padding-bottom: 56px;
            /* Sesuaikan tinggi bottom navigation, contoh ini 56px */
        }

        .bottom-navigation {
            position: fixed;
            z-index: 999;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #f8f9fa;
            /* Sesuaikan warna latar belakang */
            border-top: 1px solid #dee2e6;
        }

        .bottom-navigation a:hover {
            background-color: #e9ecef;
            /* Sesuaikan warna latar belakang saat hover */
        }

        #locationAddress {
            margin-top: 15px;
            margin-bottom: 10px;
        }

        #map {
            margin-bottom: 15px;
        }

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

        td {
            font-size: 14px;
        }

        h2,
        h3 {
            display: inline-block;
        }

        /* CSS untuk mengatur warna latar belakang berdasarkan status */
        h3 {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            /* Warna teks putih */
            margin-right: 10px;
            /* Jarak antara h2 dan h3 */
        }

        /* Ganti warna latar belakang sesuai dengan status */
        h3.pending,
        h3.pause {
            color: #fdd224;
        }

        h3.on-process {
            color: green;
        }

        h3.clear {
            color: blue;
        }

        .sync-btn,
        .sync-pause-btn {
            background-color: #e3b602;
            border: 1px solid #e3b602;
        }

        .sync-btn:hover,
        .sync-pause-btn:hover {
            background-color: #b18e02;
            border: 1px solid #b18e02;
        }

        /* CSS untuk mengatur ukuran video sesuai dengan modal */
        #cameraFeed {
            max-width: 100%;
            height: auto;
        }

        /* Gaya untuk notifikasi */
        .notif {
            position: absolute;
            top: -5px;
            /* Sesuaikan dengan posisi vertikal yang diinginkan */
            left: 43vw;
            /* Sesuaikan dengan posisi horizontal yang diinginkan */
            background-color: red;
            /* Warna latar belakang notifikasi */
            color: white;
            /* Warna teks notifikasi */
            font-size: 10px;
            /* Ukuran teks notifikasi */
            border-radius: 50%;
            /* Membuat notifikasi menjadi lingkaran */
            padding: 2px 6px;
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


        .finish-btn {
            background-color: #007bff;
            border: 1px solid #007bff;
        }

        .p {
            margin-right: 20px;
            font-size: 13px;
        }

        .btn {
            font-size: 12px;
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

        .navv input:checked~.menuv li:nth-child(1) {
            top: -80px;
            transition-delay: 0.1s;
        }

        .menuv li a span {
            width: 65px;
            left: -17%;
        }

        th {
            text-align: center;
        }

        table.tek th {
            width: 25%;
            text-align: left;
        }

        th.mnt {
            background-color: #eee;
            color: #343a40;
        }

        /* Mengatur sel "Teknisi" agar teks berada di atas dan sejajar dengan kiri */
        table tr th.tns {
            vertical-align: top;
        }

        /* Mengatur gaya saat tombol dihover (diarahkan) */
        .btn-np a:hover,
        .finish-btn:hover {
            background-color: #0056b3;
            /* Mengubah warna latar saat dihover */
        }

        .btn {
            border-radius: 0;
        }

        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }

            h2 {
                font-size: 25px;
                font-weight: bold;
            }

            th,
            #locationAddress {
                font-size: 14px;
            }

            #locationAddress {
                margin-top: 5vh;
            }

            td,
            i {
                font-size: 14px;
            }

            .footer {
                margin-bottom: 4vh;
            }
        }

        .row {
            display: flex;
            flex-wrap: wrap;
        }

        .col-md-6 {
            width: 50%;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 20px;
        }

        p {
            line-height: 1.5;
            font-size: 15px;
        }

        ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        h2,
        h5 {
            margin: 0;
            padding: 0;
        }

        h5 {
            font-size: 16px;
            font-weight: bold;
        }

        li {
            margin-bottom: 10px;
            font-size: 15px;
        }

        i.icn,
        i.icn-back,
        i.icn-tek {
            background-color: #0336ff;
            padding: 4px;
            color: #fff;
            border-radius: 50%;
            margin-right: 5px;
        }

        i.icn-back {
            background-color: #ff0266;
        }

        i.icn-tek {
            background-color: #ffa500;
        }

        .btn-block {
            font-weight: 600;
            font-size: 16px;
        }

        .lokasi {
            border-top: 1px solid #ddd;
        }

        .right-back a {
            color: blue;
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
                            if ($role == "Admin") {
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
                            } else if ($role == "SA") {
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
                            } else if ($role == "Sales") {
                            ?>
                                <a href="index-sales.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                                <a href="kegiatan.php" class="nav_link"> <i class='bx bx-bookmark nav_icon'></i> <span class="nav_name">Kegiatan</span> </a>
                                <a href="data-customer.php" class="nav_link"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Data Customer</span> </a>
                                <a href="input-customer.php" class="nav_link"> <i class='bx bx-user-plus nav_icon'></i> <span class="nav_name">Input Customer Baru</span> </a>
                            <?php
                            } else {
                            ?>
                                <a href="index-teknisi.php" class="nav_link active">
                                    <i class='bx bx-grid-alt nav_icon'></i>
                                    <span class="nav_name">Dashboard</span>
                                    <?php if ($taskCount > 0) : ?>
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

            <main id="content" class="mx-auto col-md-12 col-lg-8">
                <div class="container">

                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6 mt-3 text-right right-back">
                            <i class="bx bx-chevrons-left icn-back"></i> <a href="index-teknisi.php">Kembali</a>
                        </div>
                    </div>


                    <?php

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $kode_transaksi = $row['kode_transaksi'];
                            $status = $row['status'];
                            $statusClass = '';

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

                    ?>
                            <div class="row">
                                <div class="col-md-12 mb-4 mt-4">
                                    <span class="label label"><?php echo $row['jenis']; ?></span>
                                    <h4><?php echo $row['nama_customer']; ?></h4>
                                    <?php
                                    $telepon_cust = $row['nomor_customer'];
                                    if (substr($telepon_cust, 0, 1) === '0') {
                                        // Jika ya, ganti "0" menjadi "62"
                                        $tlp_cust = '62' . substr($telepon_cust, 1);
                                    }
                                    ?>
                                    <a href="https://api.whatsapp.com/send?phone=<?php echo $tlp_cust; ?>"><?php echo $telepon_cust; ?></a>
                                </div>

                                <div class="col-md-12 mt-0 mb-3">
                                    <h2 class="mb-3">Detail Kegiatan</h2>
                                    <ul class="list-bd">

                                        <?php
                                        $selectTeknisi = "SELECT k.id_teknisi, k.kode_transaksi, t.nama AS nama_teknisi, t.id_teknisi FROM kegiatan k
                                        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                                        WHERE k.kode_transaksi = '$kode_transaksi'";
                                        $resTek = mysqli_query($conn, $selectTeknisi);
                                        if (mysqli_num_rows($resTek) > 0) {
                                            while ($dataTek = mysqli_fetch_assoc($resTek)) {
                                                echo "<li><i class='bx bx-chevron-right icn-tek'></i> " . $dataTek["nama_teknisi"] . "</li>";
                                            }
                                        } else {
                                            echo "Data Teknisi tidak ditemukan.";
                                        }

                                        echo "</ul>";

                                        ?>
                                </div>

                                <div class="col-md-12 mt-0 mb-3">
                                    <ul>
                                        <li><i class="bx bxs-hourglass-top icn"></i> <?php echo $sts; ?></li>
                                        <?php
                                        if ($status == "Reschedule" || $status == "Reschedule2" || $status == "Pause") {

                                            $tabRes = "SELECT * FROM reschedule WHERE id_kegiatan = $id_kegiatan ORDER BY id_resc DESC LIMIT 1";
                                            $resultTabRes = mysqli_query($conn, $tabRes);

                                            $tgl_res = "";

                                            // Check if there is at least one row returned
                                            if ($resultTabRes && mysqli_num_rows($resultTabRes) > 0) {
                                                $dataTabRes = mysqli_fetch_assoc($resultTabRes);
                                                $tgl_resc = $dataTabRes['tanggal'];
                                                if ($tgl_resc != '0000-00-00 00:00:00' && $tgl_resc != NULL) {
                                                    $tgl_res = $tgl_resc;
                                                } else {
                                                    $tgl_res = $row['tgl_reschedule'];
                                                }
                                            } 
                                            else {
                                                // Handle the case where no rows were returned, if needed
                                                $tgl_res = $row['tgl_reschedule'];
                                            }

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

                                            if ($tgl_res != '0000-00-00 00:00:00' && $tgl_res != NULL) {
                                                echo "<li><i class='bx bx-calendar icn'></i> " . $namaHariIndonesiaReq . ", " . $formattedDateReq . "</li>";
                                                echo "<li><i class='bx bx-time icn'></i> " . $formattedTimeReq . "</li>";
                                            } else {
                                                echo "<li><i class='bx bx-calendar icn'></i> " . $namaHariIndonesia . ", " . $formattedDate . "</li>";
                                                echo "<li><i class='bx bx-time icn'></i> " . $formattedTime . "</li>";
                                            }
                                        } else {
                                            echo "<li><i class='bx bx-calendar icn'></i> " . $namaHariIndonesia . ", " . $formattedDate . "</li>";
                                            echo "<li><i class='bx bx-time icn'></i> " . $formattedTime . "</li>";
                                        }
                                        ?>
                                    </ul>

                                </div>

                                <?php
                                    $alamat_cust = $row['alamat_customer'];
                                    $keterangan_cust = $row['keterangan'];

                                    // Fungsi untuk mengganti tautan dengan elemen HTML <a>
                                    function makeLinksClickable($text) {
                                        // Temukan semua tautan dengan ekspresi reguler
                                        $pattern = '/(http|https|ftp):\/\/[^\s]+/i';
                                        $replacement = '<a href="$0" target="_blank">$0</a>';
                                        $text = preg_replace($pattern, $replacement, $text);

                                        return $text;
                                    }

                                ?>


                                <div class="col-md-12 mt-0 mb-3" style="text-align:justify;">
                                    <h5 class="mt-2 mb-1">ALAMAT :</h5>
                                    <p style="word-wrap: break-word;"><?php echo makeLinksClickable($alamat_cust); ?></p>
                                    <p>
                                    <h5 class="mt-4 mb-1">CATATAN :</h5><?php echo makeLinksClickable($keterangan_cust); ?></p>
                                </div>
                            </div>
                </div>

                <div class="container">


                    <!-- Bottom Navigation -->
                    <div class="bottom-navigation row m-0 p-0">
                        <?php
                            if ($role == "Teknisi") {
                                if ($status == "Pending" or $status == "Reschedule") {
                        ?>
                                <div class="col-md-6 m-0 p-0">
                                    <button class="btn btn-primary start-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Mulai</button>
                                </div>
                                <div class="col-md-6 m-0 p-0">
                                    <button class="btn btn-primary sync-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Reschedule</button>
                                </div>
                            <?php
                                } else if ($status == "On Process") {
                            ?>
                                <div class="col-md-6 m-0 p-0">
                                    <button class="btn btn-success pause-btn btn-block p-3" data-toggle="modal" data-target="#pauseModal" data-id="<?php echo $row['id_kegiatan']; ?>">Lanjut Nanti</button>
                                </div>
                                <div class="col-md-6 m-0 p-0">
                                    <a href="detail-kegiatan-finish.php?id_kegiatan<?php echo $row['id_kegiatan'];?>">Selesai</a>
                                    <button class="btn btn-success finish-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Selesai</button>
                                </div>
                            <?php
                                } else if ($status == "Pause" or $status == "Reschedule2") {
                            ?>
                                <div class="col-md-6 m-0 p-0">
                                    <button class="btn btn-primary start-pause-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Mulai</button>
                                </div>
                                <div class="col-md-6 m-0 p-0">
                                    <button class="btn btn-primary sync-pause-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Reschedule</button>
                                </div>
                        <?php
                                }
                            }
                        ?>
                    </div>



            <?php
                        }
                    } else {
                        echo "Tidak ada data kegiatan.";
                    }
            ?>

            <!-- <button class="btn btn-primary" onclick="window.location.href='detail_kegiatan.php?id_kegiatan=<?php echo $id_kegiatan; ?>';">Lihat Lebih Lengkap</button> -->
                </div>

                <div class="col-md-12 mt-4 lokasi">
                    <!--Bagian 1    -->
                    <div id="locationAddress"></div>
                    <div id="map" style="height: 200px; z-index:-1;"></div>
                    <button type="button" class="btn btn-primary" id="refreshLocationBtn">Refresh Lokasi</button>
                </div>
        </div>
        </main>


        <!-- Modal Pop-up Finish Button-->
        <div class="modal fade" id="finishModal" tabindex="-1" role="dialog" aria-labelledby="startModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document"> <!-- Tambahkan class modal-dialog-centered -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="startModalLabel">Input Keterangan Mulai</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='detail-kegiatan-teknisi.php?id_kegiatan=<?php echo $id_kegiatan; ?>';">
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='detail-kegiatan-teknisi.php?id_kegiatan=<?php echo $id_kegiatan; ?>';">
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='detail-kegiatan-teknisi.php?id_kegiatan=<?php echo $id_kegiatan; ?>';">
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='detail-kegiatan-teknisi.php?id_kegiatan=<?php echo $id_kegiatan; ?>';">
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