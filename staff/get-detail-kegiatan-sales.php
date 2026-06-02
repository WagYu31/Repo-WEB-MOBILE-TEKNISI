<?php

// Query untuk mengambil data kegiatan berdasarkan ID kegiatan
$sqlT = "SELECT v.*, s.nama AS nama_sales, c.nama AS nama_customer, c.no_wa AS cust_nomor
            FROM visits v
            LEFT JOIN sales s ON v.id_sales = s.id_sales
            LEFT JOIN cust c ON v.id_cust = c.id_cust
            WHERE v.kode_transaksi = '$kode_transaksi' AND v.id_sales = '$idSales'";

$resultT = mysqli_query($conn, $sqlT);
$jumlah_rowT = mysqli_num_rows($resultT);

?>
<div class="col-lg-8 col-11 mt-2 mt-md-4 mb-4 card p-4 ms-md-0 ms-3">
    <?php
    $sqlTk = "SELECT nama FROM sales WHERE id_sales = '$idSales'";
    $resultTk = mysqli_query($conn, $sqlTk);
    $namarow = mysqli_fetch_assoc($resultTk);
    ?>
    <div class="row px-4">
        <div class="col-md-12 d-flex flex-column text-dark text-uppercase">
            <p style="font-size: 20px;"><strong><?php echo isset($namarow['nama']) ? $namarow['nama'] : '-'; ?></strong></p>
        </div>
    </div>

    <?php
    $rowNumber = 1;
    while ($rowT = mysqli_fetch_assoc($resultT)) {
    ?>
        <div class="row p-md-4 p-2 pt-3" style="border-top:1px solid #ddd;">

        <div class="col-md-12 text-dark mb-0 mt-2">
                <?php
                // Inisialisasi variabel untuk status dan warna default
                $updatedStatusT = isset($rowT['status']) ? $rowT['status'] : '-';
                $warna = '';

                // Memeriksa dan mengubah status serta warna sesuai dengan kondisi
                switch ($updatedStatusT) {
                    case 'dijadwalkan':
                        $updatedStatusT = 'Dijadwalkan';
                        $warna = 'yellow';
                        break;
                    case 'on process':
                        $updatedStatusT = 'Diproses';
                        $warna = 'green';
                        break;
                    case 'clear':
                        $updatedStatusT = 'Selesai';
                        $warna = 'blue';
                        break;
                    default:
                        // Jika status tidak sesuai dengan kondisi di atas, gunakan status asli
                        $updatedStatusT = $rowT['status'];
                }
                ?>

                <!-- Menambahkan warna teks sesuai dengan warna yang ditentukan -->
                <p class="text-uppercase"><strong>Status : <?php echo $updatedStatusT; ?></strong></p>
            </div>
            <div class="col-md-10 col-12 mt-n2 d-flex flex-row">
                <div class="w-25"><strong>Tanggal Visits</strong></div>
                <div class="w-5" style="margin-left:7px;"><strong>:</strong></div>
                <div class="w-70"><?php echo date('d-m-Y | H:i', strtotime($rowT['tgl_visits'])); ?></div>
            </div>

            <div class="col-md-10 col-12 d-flex flex-row text-danger">
    <div class="w-25"><strong>Terlambat</strong></div>
    <div class="w-5" style="margin-left:7px;"><strong>:</strong></div>
    <!-- <div class="w-70"> -->
        <?php
        // if ($rowT['late'] && $rowT['late'] != '0000-00-00 00:00:00') {
        //     echo $rowT['late'];
        // } else {
        //     if ($rowT["tgl_mulai"] && $rowT["tgl_mulai"] != '0000-00-00 00:00:00') {
        //         $datetime_request = strtotime($rowT["tgl_request"]);
        //         $datetime_mulai = strtotime($rowT["tgl_mulai"]);
        //         $telat_in_seconds = $datetime_mulai - $datetime_request;

        //         $telat_hours = floor($telat_in_seconds / 3600);
        //         $telat_minutes = floor(($telat_in_seconds % 3600) / 60);
        //         $telat_seconds = $telat_in_seconds % 60;

        //         $telat_formatted = '';
        //         if ($telat_hours > 0) {
        //             $telat_formatted .= $telat_hours . ' jam, ';
        //         }
        //         if ($telat_minutes > 0) {
        //             $telat_formatted .= $telat_minutes . ' menit, ';
        //         }
        //         $telat_formatted .= $telat_seconds . ' detik';

        //         echo $telat_formatted;
        //     } else {
        //         echo '-';
        //     }
        // }
        ?>
    <!-- </div> -->
</div>
            <div class="col-12 col-md-2"></div>

            <div class="col-md-6 col-12 mt-4">
                <div class="row d-flex flex-row">
                    <div class="col-md-5 col-5"><strong>Tanggal Mulai</strong></div>
                    <div class="col-md-1 col-2"><strong>:</strong></div>
                    <div class="col-md-6 col-5"><?php echo ($rowT['tgl_mulai'] !== '0000-00-00 00:00:00' && !is_null($rowT['tgl_mulai'])) ? date('d-m-Y | H:i', strtotime($rowT['tgl_mulai'])) : '-'; ?></div>
                </div>
                <div class="row">
                    <div class="col-md-5 col-5"><strong>Lokasi Mulai</strong></div>
                    <div class="col-md-1 col-2"><strong>:</strong></div>
                </div>
                <div class="row d-flex flex-row">

                    <?php
                    include "get-lok.php";
                    ?>

                    <div class="col-12">
                        <?php
                        $lokasi_parts = explode(',', $rowT['lokasi_mulai']);

                        if (count($lokasi_parts) == 2) {
                            $latitude = $lokasi_parts[0];
                            $longitude = $lokasi_parts[1];

                            // Panggil fungsi yang sesuai dengan nomor baris
                            $addressFunction = ${"getAddressFromCoordinates$rowNumber"};
                            $address = $addressFunction($latitude, $longitude);

                            echo $address;
                        } else {
                            echo "Format lokasi tidak valid.";
                        }
                        ?>

                    </div>
                </div>
            </div>
            <div class="col-12 d-block d-md-none mt-3 mb-3"></div>
            <div class="col-md-6 col-12 mt-4">
                <div class="row d-flex flex-row">
                    <div class="col-md-5 col-5"><strong>Tanggal Selesai</strong></div>
                    <div class="col-md-1 col-2"><strong>:</strong></div>
                    <div class="col-md-6 col-5"><?php echo ($rowT['tgl_selesai'] !== '0000-00-00 00:00:00' && !is_null($rowT['tgl_selesai'])) ? date('d-m-Y | H:i', strtotime($rowT['tgl_selesai'])) : '-'; ?></div>
                </div>
                <div class="row d-flex flex-row">
                    <div class="col-md-5 col-5"><strong>Lokasi Selesai</strong></div>
                    <div class="col-md-1 col-2"><strong>:</strong></div>
                </div>
                <div class="row">
                    <?php
                    include "get-lok-end.php";
                    ?>
                    <div class="col-12">
                        <?php
                        $lokasi_parts = explode(',', $rowT['lokasi_selesai']);

                        if (count($lokasi_parts) == 2) {
                            $latitude = $lokasi_parts[0];
                            $longitude = $lokasi_parts[1];

                            // Panggil fungsi yang sesuai dengan nomor baris
                            $addressFunction = ${"getAddressFromCoordinatesEnd$rowNumber"};
                            $address = $addressFunction($latitude, $longitude);

                            echo $address;
                        } else {
                            echo "Format lokasi tidak valid.";
                        }
                        ?>

                    </div>

                </div>
            </div>

            <div class="col-md-12 text-dark mt-2 mb-0">
                <p><strong>Keterangan / Permasalahan</strong></p>
            </div>
            <div class="col-md-12 mt-n3">
                <?php
                $ket_fin = $rowT['hasil_visits'];
                if ($ket_fin == "Diselesaikan oleh Admin") {
                ?>
                    <p class="text-primary"><strong><?php echo isset($rowT['hasil_visits']) ? $rowT['hasil_visits'] : '-'; ?></strong></p>
                <?php
                } else {
                ?>
                    <p><strong>Hasil Visit :</strong> <?php echo isset($rowT['hasil_visits']) ? $rowT['hasil_visits'] : '-'; ?></p>
                    <p class="mt-n3"><strong>Keterangan Tambahan :</strong> <?php echo isset($rowT['keterangan_tambahan']) ? $rowT['keterangan_tambahan'] : '-'; ?></p>
                <?php
                }
                ?>
            </div>
            <div class="col-md-12 text-dark mt-1 mb-0">
                <p><strong>Dokumentasi</strong></p>
            </div>
            <div class="col-md-12 mt-n2 d-flex justify-content-left flex-wrap">
                <?php
                $gambar_finish_columns = array(
                    'gambar_1',
                    'gambar_2',
                    'gambar_3',
                    'gambar_4',
                    'gambar_5'
                );

                foreach ($gambar_finish_columns as $column) :
                    // Periksa apakah gambar tidak NULL dan tidak kosong
                    if (!empty($rowT[$column]) && $rowT[$column] !== "NO" && $rowT[$column] !== "-") :
                ?>
                        <div class="image-container mb-3 me-3">
                            <img src="assets/img/uploads/<?php echo $rowT[$column]; ?>" class="img-fluid" alt="">
                            <div class="download-btn-container position-relative">
                                <a href="assets/img/uploads/<?php echo $rowT[$column]; ?>" download class="btn bg-gradient-info download-btn w-100 d-md-block d-none" style="border-radius:0;"><i class="material-icons opacity-10">download</i> Download</a>
                                <a href="assets/img/uploads/<?php echo $rowT[$column]; ?>" download class="btn bg-gradient-info download-btn w-100 d-md-none d-block" style="border-radius:0;"><i class="material-icons opacity-10">download</i></a>
                            </div>
                        </div>
                <?php
                    endif;
                endforeach;
                ?>
            </div>


        </div>
    <?php
        $rowNumber++;
    }
    ?>
</div>