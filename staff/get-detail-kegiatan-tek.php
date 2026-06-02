<?php

$sqlT = "SELECT p.*, p.keterangan AS keterangan_selesai, t.nama_teknisi
         FROM pelaksanaan_kegiatan p
         LEFT JOIN (
            SELECT DISTINCT teknisi_id, nama_teknisi 
            FROM team_kegiatan
         ) t ON p.teknisi_id = t.teknisi_id
         WHERE p.kode = '$kode_transaksi' AND p.teknisi_id = '$idTeknis'
         ORDER BY p.waktu_mulai ASC";

$resultT = mysqli_query($conn, $sqlT);
$jumlah_rowT = mysqli_num_rows($resultT);

?>
<div class="col-lg-8 col-11 mt-2 mt-md-4 mb-4 card p-4 ms-md-0 ms-3">
    <?php
    $sqlTk = "SELECT nama FROM teknisi WHERE id = '$idTeknis'";
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
    if (mysqli_num_rows($resultT) == 0) {
    ?>
        <div class="row p-md-4 p-2 pt-3" style="border-top:1px solid #ddd;">
            <p class="ms-1">Belum Ada Kegiatan</p>
        </div>
        <?php
    } else {
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
                        case 'waiting':
                        case 'dijadwalkan':
                            $updatedStatusT = ($updatedStatusT == 'waiting') ? 'Dalam Antrian' : 'Dijadwalkan';
                            $warna = 'yellow';
                            break;
                        case 'berjalan':
                            $updatedStatusT = 'Dalam Proses';
                            $warna = 'green';
                            break;
                        case 'Lanjut Nanti':
                            $updatedStatusT = 'Berlanjut';
                            $warna = 'yellow';
                            break;
                        case 'Lanjutan':
                            $updatedStatusT = 'Dilanjutkan';
                            $warna = 'yellow';
                            break;
                        case 'selesai':
                        case 'selesai by admin':
                            $updatedStatusT = ($updatedStatusT == 'selesai') ? 'Selesai' : 'Diselesaikan oleh Admin';
                            $warna = 'blue';
                            break;
                        default:
                            // Jika status tidak sesuai dengan kondisi di atas, gunakan status asli
                            $updatedStatusT = $rowT['status'];
                    }
                    ?>

                    <p class="text-uppercase"><strong>Status : <?php echo $updatedStatusT; ?></strong></p>
                </div>



                <div class="col-md-6 col-12 mt-2 text-sm text-dark">
                    <div class="row d-flex flex-row">
                        <div class="col-5">Tanggal Mulai</div>
                        <div class="col-1"><strong>:</strong></div>
                        <div class="col-6"><?php echo ($rowT['waktu_mulai'] !== '0000-00-00 00:00:00' && !is_null($rowT['waktu_mulai'])) ? date('d-m-Y | H:i', strtotime($rowT['waktu_mulai'])) : '-'; ?></div>
                    </div>
                </div>
                <!--<div class="col-12 d-block d-md-none mt-md-3 mt-0 mb-3"></div>-->
                <div class="col-md-6 col-12 mt-md-2 mt-1 text-sm text-dark">
                    <div class="row d-flex flex-row">
                        <div class="col-5">Tanggal Selesai</div>
                        <div class="col-1"><strong>:</strong></div>
                        <div class="col-6"><?php echo ($rowT['waktu_selesai'] !== '0000-00-00 00:00:00' && !is_null($rowT['waktu_selesai'])) ? date('d-m-Y | H:i', strtotime($rowT['waktu_selesai'])) : '-'; ?></div>
                    </div>

                </div>

                <div class="col-md-12 text-dark mt-2 mb-0">
                    <p><strong>Keterangan / Permasalahan</strong></p>
                </div>
                <div class="col-md-12 mt-n3 text-sm text-dark">
                    <?php
                    $ket_fin = $rowT['status'];
                    if ($ket_fin == "selesai by admin") {
                    ?>
                        <div class="row d-flex flex-row">
                            <div class="col-5">Keterangan</div>
                            <div class="col-1"><strong>:</strong></div>
                            <div class="col-6"><?php echo isset($rowT['keterangan']) ? $rowT['keterangan'] : '-'; ?></strong></div>
                        </div>
                    <?php
                    } else {
                    ?>
                        <div class="row d-flex flex-row">
                            <div class="col-md-3 col-5">Permasalahan</div>
                            <div class="col-1"><strong>:</strong></div>
                            <div class="col-md-8 col-6"><?php echo isset($rowT['permasalahan']) ? str_replace(". ", "<br>", $rowT['permasalahan']) : '-'; ?></div>
                        </div>
                        <div class="row d-flex flex-row">
                            <div class="col-md-3 col-5">Solusi</div>
                            <div class="col-1"><strong>:</strong></div>
                            <div class="col-md-8 col-6"><?php echo isset($rowT['solusi']) ? str_replace(". ", "<br>", $rowT['solusi']) : '-'; ?></div>
                        </div>
                        <div class="row d-flex flex-row">
                            <div class="col-md-3 col-5">Keterangan</div>
                            <div class="col-1"><strong>:</strong></div>
                            <div class="col-md-8 col-6"><?php echo isset($rowT['keterangan_selesai']) ? str_replace(". ", "<br>", $rowT['keterangan_selesai']) : '-'; ?></div>
                        </div>
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
                        'image_1',
                        'image_2',
                        'image_3',
                        'image_4',
                        'image_5'
                    );

                    foreach ($gambar_finish_columns as $column) :
                        // Periksa apakah gambar tidak NULL dan tidak kosong
                        if (!empty($rowT[$column]) && $rowT[$column] !== "NO" && $rowT[$column] !== "-") :
                    ?>
                        <div class="image-container mb-3 me-3">
                            <img src="https://grav-tech.com/jadwal-3/api/storage/app/image/<?php echo $rowT[$column]; ?>" class="img-fluid" alt="">
                            <div class="download-btn-container position-relative">
                                <a href="https://grav-tech.com/jadwal-3/download.php?file=<?php echo urlencode($rowT[$column]); ?>" 
                                   class="btn bg-gradient-info download-btn w-100 d-md-block d-none" 
                                   style="border-radius:0;">
                                   <i class="material-icons opacity-10">download</i> Download
                                </a>
                                <a href=https://grav-tech.com/jadwal-3/download.php?file=<?php echo urlencode($rowT[$column]); ?>" 
                                   class="btn bg-gradient-info download-btn w-100 d-md-none d-block" 
                                   style="border-radius:0;">
                                    <i class="material-icons opacity-10">download</i>
                                </a>
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
    }
    ?>
</div>