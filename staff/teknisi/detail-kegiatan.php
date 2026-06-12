<?php
include "../conn.php";
include "../session.php";
$id_user = $_SESSION['id_user'];
include "../get-user-data.php";
$pageNow = "Dashboard";

$user = "SELECT * FROM user WHERE id_user = $id_user";
$resultUs = mysqli_query($conn, $user);
$rwt = mysqli_fetch_assoc($resultUs);
$idt = $rwt['id_teknisi'];

if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];

    $query = "SELECT * FROM user WHERE id_user = $id_user";
    $res = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($res);


    $teknisi = $data['id_teknisi'];

    $sql = "SELECT k.id_teknisi, k.*, t.nama AS nama_teknisi, c.nama AS nama_customer, c.nomor_tlp AS nomor_customer, c.alamat AS alamat_customer
                FROM kegiatan k
                LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                LEFT JOIN customer c ON k.id_cust = c.id_cust
                WHERE FIND_IN_SET('$teknisi', k.id_teknisi) > 0 AND k.id_kegiatan = '$id_kegiatan'";

    $result = mysqli_query($conn, $sql);
} else {
    echo "Parameter id_kegiatan tidak valid.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php
    include "head.php";
    ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="ckeditor5/build/ckeditor.js"></script>
    <style>
        #map {
            height: 200px;
        }

        ul#data-tek li:nth-child(odd) {
            background-color: white;
        }

        ul#data-tek li:nth-child(even) {
            background-color: #efefef;
            border-radius: 0;
        }
        .custom-textarea {
            resize: none;
            width: 100%;
            min-height: 100px;
        }
    </style>
</head>

<body class="g-sidenav-show  bg-white-200">
    <?php
    include "cek-menu.php";
    ?>

    <main class="main-content position-relative border-radius-lg bg-white">
        <?php
        include "../nav-top.php";
        $todayDate = formatTanggal('dd MMMM yyyy');
        ?>
        <div class="container-fluid py-4 pt-0">


            <div class="row mb-4">
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-end align-items-center mt-n5">
                        <a href="index.php" style="display:flex; flex:column; vertical-align: middle;" class="btn bg-gradient-primary p-1 px-2 text-white"><i class="material-icons opacity-10 me-2 text-white" style="margin-top:2px;">arrow_back</i> Kembali</a>
                    </div>
                </div>

                <div class="container col-12 p-5 pt-0">



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

                            $tglReq = $row["tgl_request"];
                            $tglMul = $row["tgl_mulai"];
                            $tglSel = $row["tgl_selesai"];

                            if ($tglSel !== NULL && $tglSel !== '0000-00-00 00:00:00') {
                                $tgl_request = $tglSel;
                            } else {
                                if ($tglMul !== NULL && $tglMul !== '0000-00-00 00:00:00') {
                                    $tgl_request = $tglMul;
                                } else {
                                    $tgl_request = $tglReq;
                                }
                            }

                            $datetime = $tgl_request;
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
                                <div class="col-md-12 mb-2 mt-4">
                                    <span class="label label"><?php echo $row['jenis']; ?></span>
                                    <h4><?php echo $row['nama_customer']; ?></h4>
                                    <?php
                                    $telepon_cust = $row['nomor_customer'];
                                    if (substr($telepon_cust, 0, 1) === '0') {
                                        $tlp_cust = '62' . substr($telepon_cust, 1);
                                    }
                                    ?>
                                    <a href="https://api.whatsapp.com/send?phone=<?php echo $tlp_cust; ?>"><?php echo $telepon_cust; ?></a>
                                </div>

                                <div class="col-md-12 mt-2 mb-2 d-flex flex-row justify-content-center align-items-center">
                                    <?php
                                    
                                        if ($role == "Teknisi" && $formattedDate == date("d-m-Y")) {
                                            if ($status == "Pending" or $status == "Reschedule" or $status == "Pause") {
                                        ?>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <a href="get-mulai.php?id_kegiatan=<?php echo $row['id_kegiatan']; ?>" class="w-90 btn btn-info start-btn btn-block p-3">Mulai</a>
                                                </div>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <button class="w-90 btn btn-warning sync-btn btn-block p-3" data-toggle="modal" data-target="#rescheduleModal" data-id="<?php echo $row['id_kegiatan']; ?>">Reschedule</button>
                                                </div>
                                            <?php
                                            } else if ($status == "On Process") {
                                            ?>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <button class="w-90 btn btn-info pause-btn btn-block p-3" data-toggle="modal" data-target="#pauseModal" data-id="<?php echo $row['id_kegiatan']; ?>">Lanjut Nanti</button>
                                                </div>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <button class="w-90 btn btn-success finish-btn btn-block p-3" data-toggle="modal" data-target="#selesaiModal" data-id="<?php echo $row['id_kegiatan']; ?>">Selesai</button>
                                                </div>
                                            <?php
                                            } else if ($status == "Reschedule2") {
                                            ?>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <button class="w-90 btn btn-info start-pause-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Mulai</button>
                                                </div>
                                                <div class="col-md-6 col-6 m-0 p-0 text-center">
                                                    <button class="w-90 btn btn-warning sync-pause-btn btn-block p-3" data-id="<?php echo $row['id_kegiatan']; ?>">Reschedule</button>
                                                </div>
                                    <?php
                                            }
                                        }
                                    
                                    ?>
                                </div>

                                <div class="col-md-12 mt-0 mb-3">
                                    <h4 class="mb-1 text-s">Detail Kegiatan</h4>
                                    <ul class="list ms-n4 mt-2">

                                        <?php
                                        $selectTeknisi = "SELECT k.id_teknisi, k.ket_finish, k.kode_transaksi, t.nama AS nama_teknisi, t.id_teknisi FROM kegiatan k
                                        LEFT JOIN teknisi t ON k.id_teknisi = t.id_teknisi
                                        WHERE k.kode_transaksi = '$kode_transaksi'
                                        GROUP BY k.id_teknisi";
                                        $resTek = mysqli_query($conn, $selectTeknisi);
                                        if (mysqli_num_rows($resTek) > 0) {
                                            while ($dataTek = mysqli_fetch_assoc($resTek)) {
                                                $ketFin = $dataTek['ket_finish'];
                                                if ($ketFin == 'Diselesaikan oleh Admin') {
                                                    echo "";
                                                } else {
                                                    echo "<li style='list-style-type:none;'><i class='material-icons opacity-10 bg-warning text-white me-2' style='padding:3px; border-radius:50%; font-size:13px;'>chevron_right</i> " . $dataTek["nama_teknisi"] . "</li>";
                                                }
                                            }
                                        } else {
                                            echo "Data Teknisi tidak ditemukan.";
                                        }

                                        echo "</ul>";

                                        ?>
                                </div>


                                <div class="col-md-12 mt-0 mb-3">
                                    <ul class="ms-n4 mt-n3">
                                        <li style="list-style-type:none;"><i class="material-icons opacity-10 text-white me-2" style="background-color:blue; padding:3px; border-radius:50%; font-size:13px;">refresh</i> <?php echo $sts; ?></li>
                                        <li style="list-style-type:none;"><i class="material-icons opacity-10 text-white me-2" style="background-color:blue; padding:3px; border-radius:50%; font-size:13px;">calendar_month</i><?php echo " " . $namaHariIndonesia . ", " . $formattedDate; ?></li>
                                        <li style="list-style-type:none;"><i class="material-icons opacity-10 text-white me-2" style="background-color:blue; padding:3px; border-radius:50%; font-size:13px;">alarm</i> <?php echo $formattedTime; ?></li>

                                    </ul>

                                </div>

                                <?php
                                $alamat_cust = $row['alamat_customer'];
                                $keterangan_cust = $row['keterangan'];

                                function makeLinksClickable($text)
                                {
                                    $pattern = '/(http|https|ftp):\/\/[^\s]+/i';
                                    $replacement = '<a href="$0" target="_blank">$0</a>';
                                    $text = preg_replace($pattern, $replacement, $text);

                                    return $text;
                                }

                                ?>
                                <div class="col-md-12 mt-0 mb-0" style="text-align:justify; border-bottom:1px solid #eee;">
                                    <h6 class="mt-2 mb-1">ALAMAT :</h6>
                                    <p style="word-wrap: break-word;"><?php echo makeLinksClickable($alamat_cust); ?></p>
                                    <p>
                                    <h6 class="mt-4">CATATAN :</h6><?php echo makeLinksClickable($keterangan_cust); ?></p>
                                </div>

                            </div>
                </div>

                <div class="container">




            <?php
                        }
                    } else {
                        echo "Tidak ada data kegiatan.";
                    }
            ?>

                </div>

                <div class="col-md-12 lokasi p-5 pt-0">
                    <!-- Bagian 1 -->
                    <div id="locationAddress" class="mb-2 text-sm"></div>
                    <div id="map"></div>
                    <button type="button" class="btn btn-primary mt-3" id="refreshLocationBtn">Refresh Lokasi</button>
                </div>

            </div>

        </div>

        <!-- Modal untuk input tanggal dan jam -->
        <div class="modal fade" id="pauseModal" tabindex="-1" role="dialog" aria-labelledby="pauseModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pauseModalLabel">Lanjut Nanti</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="pauseForm" method="POST" action="get-pause.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="pauseDate">Tanggal</label>
                                <input type="date" class="form-control border p-2" id="pauseDate" name="pauseDate" required>
                            </div>
                            <div class="form-group">
                                <label for="pauseTime">Jam</label>
                                <input type="time" class="form-control border p-2" id="pauseTime" name="pauseTime">
                            </div>
                            <div class="form-group">
                                <label for="keterangan" class="font-weight-bold mt-2">Keterangan</label>
                                <textarea class="form-control border p-2 custom-textarea" id="editor4" name="keterangan" onkeydown="handleEnter(event, 'editor4')"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image1">Gambar 1</label>
                                <input type="file" class="form-control border p-2" id="image1" name="image1">
                            </div>
                            <div class="form-group">
                                <label for="image2">Gambar 2</label>
                                <input type="file" class="form-control border p-2" id="image2" name="image2">
                            </div>
                            <div class="form-group">
                                <label for="image3">Gambar 3</label>
                                <input type="file" class="form-control border p-2" id="image3" name="image3">
                            </div>
                            <!-- Hidden input untuk menyimpan ID kegiatan -->
                            <input type="hidden" id="kegiatanId" name="kegiatanId" value="<?php echo $id_kegiatan; ?>">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                        <button type="submit" form="pauseForm" class="btn btn-primary" id="submitPause">Simpan</button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Modal untuk input tanggal dan jam -->
        <div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rescheduleModalLabel">Reschedule</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="rescheduleForm" method="POST" action="get-reschedule.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="rescheduleDate">Tanggal</label>
                                <input type="date" class="form-control border p-2" id="rescheduleDate" name="rescheduleDate" required>
                            </div>
                            <div class="form-group">
                                <label for="rescheduleTime">Jam</label>
                                <input type="time" class="form-control border p-2" id="rescheduleTime" name="rescheduleTime">
                            </div>
                            <!-- Hidden input untuk menyimpan ID kegiatan -->
                            <input type="hidden" id="kegiatanId" name="kegiatanId" value="<?php echo $id_kegiatan; ?>">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                        <button type="submit" form="rescheduleForm" class="btn btn-primary" id="submitreschedule">Simpan</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal untuk input tanggal dan jam -->
        <div class="modal fade" id="selesaiModal" tabindex="-1" role="dialog" aria-labelledby="selesaiModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="selesaiModalLabel">Selesai</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="selesaiForm" method="POST" action="get-selesai.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="permasalahan" class="font-weight-bold">Permasalahan</label>
                                <textarea class="form-control border p-2 custom-textarea" id="editor1" name="permasalahan" onkeydown="handleEnter(event, 'editor1')"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="solusi" class="font-weight-bold mt-2">Solusi</label>
                                <textarea class="form-control border p-2 custom-textarea" id="editor2" name="solusi" onkeydown="handleEnter(event, 'editor2')"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="keterangan" class="font-weight-bold mt-2">Keterangan Tambahan</label>
                                <textarea class="form-control border p-2 custom-textarea" id="editor3" name="keterangan" onkeydown="handleEnter(event, 'editor3')"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image1 font-weight-bold mt-2">Gambar 1</label>
                                <input type="file" class="form-control border p-2" id="image1" name="image1">
                            </div>
                            <div class="form-group">
                                <label for="image2 font-weight-bold mt-2">Gambar 2</label>
                                <input type="file" class="form-control border p-2" id="image2" name="image2">
                            </div>
                            <div class="form-group">
                                <label for="image3 font-weight-bold mt-2">Gambar 3</label>
                                <input type="file" class="form-control border p-2" id="image3" name="image3">
                            </div>
                            <div class="form-group">
                                <label for="image3 font-weight-bold mt-2">Gambar 4</label>
                                <input type="file" class="form-control border p-2" id="image4" name="image4">
                            </div>
                            <div class="form-group">
                                <label for="image3 font-weight-bold mt-2">Gambar 5</label>
                                <input type="file" class="form-control border p-2" id="image5" name="image5">
                            </div>
                            <!-- Hidden input untuk menyimpan ID kegiatan -->
                            <input type="hidden" id="kegiatanId" name="kegiatanId" value="<?php echo $id_kegiatan; ?>">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                        <button type="submit" form="selesaiForm" class="btn btn-primary" id="submitSelesai">Simpan</button>
                    </div>
                </div>
            </div>
        </div>


        <?php
        include "../footer.php";
        ?>

    </main>
    <?php
    include "js-include.php";
    ?>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function handleEnter(event, textareaId) {
            // Periksa apakah tombol Enter ditekan (keyCode 13)
            if (event.keyCode === 13) {
                // Mencegah perilaku default dari tombol Enter
                event.preventDefault();
                let textarea = document.getElementById(textareaId);
                let start = textarea.selectionStart;
                let end = textarea.selectionEnd;
                
                // Tetapkan karakter yang ingin disisipkan saat Enter ditekan
                let insertText = ". ";
    
                // Sisipkan karakter di posisi kursor saat ini
                let newValue = textarea.value.substring(0, start) + insertText + textarea.value.substring(end);
    
                // Tetapkan nilai textarea yang baru dengan karakter yang telah disisipkan
                textarea.value = newValue;
    
                // Atur kembali posisi kursor setelah karakter yang disisipkan
                textarea.selectionStart = textarea.selectionEnd = start + insertText.length;
    
                // Mengembalikan false untuk mencegah penanganan lebih lanjut dari event
                return false;
            }
        }
    </script>



</body>

</html>