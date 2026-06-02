<li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block">
    <div class="row px-4">
        <?php
        $updatedStatus = '';

        foreach ($data['status'] as $status) {
            if ($status != 'selesai') {
                $updatedStatus = $status;

                switch ($updatedStatus) {
                    case 'waiting':
                        $updatedStatus = 'Dalam Antrian';
                        break;
                    case 'dijadwalkan':
                        $updatedStatus = 'Dijadwalkan';
                        break;
                    case 'berjalan':
                        $updatedStatus = 'Dalam Proses';
                        break;
                    case 'selesai':
                        $updatedStatus = 'Selesai';
                        break;
                    case 'menunggu laporan':
                        $updatedStatus = 'Selesai';
                        break;
                    case 'selesai by admin':
                        $updatedStatus = 'Diselesaikan Admin';
                        break;
                    case 'Lanjut Nanti':
                        $updatedStatus = 'Berlanjut';
                        break;
                    case 'Lanjutan':
                        $updatedStatus = 'Dilanjutkan';
                        break;
                    default:
                        $updatedStatus = $data['status'][0];
                }
                break;
            }
        }

        if (empty($updatedStatus)) {
            $updatedStatus = 'Selesai';
        }


        if ($pageNow != "Task") {

        ?>

            <div class="col-6 col-md-1 mb-2 mb-md-0 mt-1">
                <h6 class="mb-n1 text-dark font-weight-bold text-xs">
                    <?php echo ucwords(strtolower($data['kegiatan'][0])); ?>
                </h6>
                <span class="text-xs"><?php echo $updatedStatus; ?></span>
                <span class="text-xs font-weight-bold"><?php echo $kodeTransaksi; ?></span>
            </div>

        <?php
        }

        $smallestIndex = 0;
        $datetime = $data['jadwal'][0];

        $formattedTime = date("H:i", strtotime($datetime));
        $formattedDate = date("d/m/Y", strtotime($datetime));
        $tanggal_sekarang = date("d-m-Y");

        $fontColor = ($formattedDate > $tanggal_sekarang && $pageNow != "Task") ? "blue" : "black";
        ?>

        <?php if ($pageNow != 'Task') { ?>
            <div class="col-6 col-md-1 mb-2 mb-md-0 text-center d-flex justify-content-center align-items-start">
                <h6 class="mb-1 font-weight-bold text-sm"><?php echo $formattedTime; ?></h6>
            </div>
        <?php } else { ?>
            <div class="col-6 col-md-1 mb-2 mb-md-0 text-center d-flex justify-content-center align-items-start flex-column">
                <h6 class="mb-1 font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
                <span class="mb-1 text-xs"><?php echo $formattedTime; ?></span>
            </div>
        <?php } ?>



        <?php
        $nomorHandphone = $data['cust_nomor'][0];

        if (substr($nomorHandphone, 0, 1) === '0') {
            $nomorHandphone = '62' . substr($nomorHandphone, 1);
        }

        ?>

        <div class="col-6 col-md-2 mb-2 mb-md-0 text-center text-md-left d-flex justify-content-start align-items-center flex-column">
            <a href="customer-detail.php?id_cust=<?php echo $data['customer_id'][0]; ?>"><h6 class="text-dark font-weight-bold mb-0 text-xs"><?php echo $data['customer'][0]; ?></h6></a>
            <span class="text-xs text-uppercase"><a href="https://api.whatsapp.com/send?phone=<?php echo $nomorHandphone; ?>" target="_blank"><?php echo $data['cust_nomor'][0]; ?></a></span>
        </div>

        <?php

        if ($pageNow != "Task") {

        ?>

            <div class="col-6 col-md-3 mb-2 mb-md-0">

            <?php
        } else {
            ?>
                <div class="col-6 col-md-4 mb-2 mb-md-0">
                <?php
            }
            $sqlGetLatestKegiatan = "SELECT k.id 
                                     FROM kegiatan k
                                     WHERE k.kode = '$kodeTransaksi'
                                     ORDER BY k.id DESC
                                     LIMIT 1";

            $queryGetLatestKegiatan = mysqli_query($conn, $sqlGetLatestKegiatan);
            $rowLatestKegiatan = mysqli_fetch_assoc($queryGetLatestKegiatan);
            $latestKegiatanId = $rowLatestKegiatan['id'];

            $sqlGetTeknisi = "SELECT t.nama_teknisi, t.teknisi_id 
                              FROM team_kegiatan t
                              WHERE t.kegiatan_id = '$latestKegiatanId' 
                              AND t.deleted_at IS NULL";

            $queryGetTeknisi = mysqli_query($conn, $sqlGetTeknisi);

            while ($rowTeknisi = mysqli_fetch_assoc($queryGetTeknisi)) {
                ?>
                    <div class="d-flex align-items-start px-1 mb-1 mt-1 justify-content-start">
                        <?php
                        if (!function_exists('shortenTechnicianName')) {
                            function shortenTechnicianName($fullName)
                            {
                                // Variasi nama Muhammad yang akan disingkat menjadi "M."
                                $muhammadVariants = [
                                    'Muhammad',
                                    'Mohammed',
                                    'Mohammad',
                                    'Muhammed',
                                    'Mohamed',
                                    'Mohamad',
                                    'Muhamad',
                                    'Muhamed',
                                    'Mohamud',
                                    'Mohummad',
                                    'Mohummed'
                                ];

                                // Pecah nama menjadi array berdasarkan spasi
                                $words = explode(" ", $fullName);

                                // Cek apakah kata pertama adalah salah satu dari variasi "Muhammad"
                                if (in_array($words[0], $muhammadVariants)) {
                                    $words[0] = "M.";
                                }

                                // Gabungkan kembali nama dan cek panjangnya
                                $shortenedName = implode(" ", $words);

                                // Jika panjangnya lebih dari 20 karakter, singkat nama terakhir sebagai inisial
                                if (strlen($shortenedName) > 15) {
                                    foreach ($words as $index => $word) {
                                        // Ambil kata terakhir dan jadikan inisial
                                        if ($index > 1 && $index === count($words) - 1) {
                                            $words[$index] = strtoupper($word[0]) . '.';
                                        }
                                    }
                                    $shortenedName = implode(" ", $words);
                                }

                                return $shortenedName;
                            }
                        }

                        // Contoh penggunaan
                        echo '<div class="col-6 px-0 text-start">
                            <a href="list-kegiatan-teknisi.php?idTek=' . $rowTeknisi['teknisi_id'] .  '"><h6 class="mb-0 text-dark font-weight-bold text-xs">'
                            . shortenTechnicianName($rowTeknisi['nama_teknisi']) .
                            '</a></h6>
                        </div>';

                        $teknisi_id = $rowTeknisi['teknisi_id'];

                        $getStatusToday = "SELECT waktu_mulai, waktu_selesai, status AS status_pelaksanaan FROM pelaksanaan_kegiatan
                                            WHERE kegiatan_id = '$latestKegiatanId' AND teknisi_id = '$teknisi_id'";

                        $resultGetStatusToday = mysqli_query($conn, $getStatusToday);

                        if (mysqli_num_rows($resultGetStatusToday) > 0) {
                            $rowGetStatusToday = mysqli_fetch_assoc($resultGetStatusToday);

                            $waktuMulai = $rowGetStatusToday['waktu_mulai'];
                            $waktuSelesai = $rowGetStatusToday['waktu_selesai'];
                            $statusPelaksanaan = $rowGetStatusToday['status_pelaksanaan'];
                            
                            if ($statusPelaksanaan == 'selesai') {
                                echo "<div class='bg-success text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Selesai</div>";
                            } elseif ($statusPelaksanaan == 'berjalan') {
                                echo "<div class='bg-info text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Dikerjakan</div>";
                            } elseif ($statusPelaksanaan == 'menunggu laporan') {
                                echo "<div class='bg-warning text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Menunggu Laporan</div>";
                            } elseif ($statusPelaksanaan == 'Lanjut Nanti') {
                                echo "<div class='bg-secondary text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Lanjut Nanti</div>";
                            } else {
                                echo "<div class='bg-secondary text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Dijadwalkan</div>";
                            }
                        } else {
                            echo "<div class='bg-secondary text-white rounded-pill p-0 px-2 text-center' style='font-size:10px;'>Dijadwalkan</div>";
                        }
                        ?>
                    </div>
                <?php
            }
                ?>
                </div>


                <?php if ($pageNow != 'Task') { ?>
                    <div class="col-6 col-md-2 px-0 text-start text-md-left d-flex justify-content-start align-items-start">
                        <span class="text-xs w-95"><?php echo $data['alamat'][0]; ?></span>
                    </div>
                <?php } else { ?>
                    <div class="col-6 col-md-3 px-0 text-start text-md-left d-flex justify-content-start align-items-start">
                        <span class="text-xs w-100"><?php echo $data['alamat'][0]; ?></span>
                    </div>
                <?php } ?>

                <div class="col-6 col-md-1 mb-2 mb-md-0 d-flex justify-content-between align-items-start text-center">
                    <div class="text-left ms-3">
                        <h6 class="mb-1 text-primary px-2 py-1 rounded-pill btn btn-outline-primary font-weight-bold text-xs">
                            <?php
                            if (!function_exists('getInitials')) {
                                function getInitials($fullName)
                                {
                                    $words = explode(" ", $fullName);
                                    $initials = "";
                                    foreach ($words as $word) {
                                        $initials .= strtoupper($word[0]);
                                    }
                                    return $initials;
                                }
                            }
                            echo getInitials($data['request'][0]);
                            ?>
                        </h6>
                    </div>

                    <div class="text-right ms-4">
                        <?php
                        $createdAt = $data['created_at'][0];
                        $formattedDatecreatedAt = date("d/M", strtotime($createdAt));
                        $formattedTimecreatedAt = date("H:i", strtotime($createdAt));
                        ?>
                        <h6 class="mb-0 font-weight-bold" style="font-size:12px;"><?php echo $formattedDatecreatedAt; ?></h6>
                        <span class="text-xs text-uppercase"><?php echo $formattedTimecreatedAt; ?></span>
                    </div>
                </div>

                <?php if ($pageNow != 'Task') { ?>
                    <div class="col-6 p-0 col-md-2 mb-2 mb-md-0 d-flex justify-content-md-end justify-content-start align-items-start pt-1">
                        <a class="btn btn-info view-btn w-15 p-1 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10" style="font-size:12px;">visibility</i></a>
                        <button class="btn btn-warning edit-btn w-15 p-1 text-center me-1" data-id="<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10" style="font-size:12px;">autorenew</i></button>
                        <a class="btn btn-danger delete-btn w-15 p-1 text-center" href="delete-kegiatan.php?kode=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10" style="font-size:12px;">delete</i></a>
                    </div>

                <?php } else { ?>
                    <div class="col-6 p-0 col-md-1 mb-2 mb-md-0 d-flex justify-content-md-end justify-content-start align-items-start pt-1">
                        <a class="btn btn-info view-btn w-30 p-1 text-center me-1" href="view-kegiatan.php?kode_transaksi=<?php echo $kodeTransaksi; ?>"><i class="material-icons opacity-10" style="font-size:12px;">visibility</i></a>
                    </div>
                <?php } ?>


            </div>
</li>