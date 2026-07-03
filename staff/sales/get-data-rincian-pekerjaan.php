<?php
include "conn.php";

if (isset($_POST['id_sales']) && isset($_POST['kode_transaksi'])) {
    $id_teknisi = $_POST['id_sales'];
    $kode_transaksi = $_POST['kode_transaksi'];

    // Query untuk mengambil data dari database
    $sql = "SELECT tks.*, s.nama, tks.id_sales, sc.nama AS nama_cust, sc.id AS id_cust,
                   ks.kode AS kode_transaksi, ks.jadwal AS tgl_visits,
                   IFNULL(ps.status, 'dijadwalkan') AS status,
                   ps.ci_at AS tgl_mulai, ps.co_at AS tgl_selesai,
                   CONCAT(IFNULL(ps.lat_ci, ''), ',', IFNULL(ps.lon_ci, '')) AS lokasi_mulai,
                   CONCAT(IFNULL(ps.lat_co, ''), ',', IFNULL(ps.lon_co, '')) AS lokasi_selesai,
                   ps.keterangan AS hasil_visits, ps.catatan_visit AS keterangan_tambahan,
                   ps.image_1 AS gambar_1, ps.image_2 AS gambar_2, ps.image_3 AS gambar_3
            FROM team_kegiatan_sales tks
            JOIN sales s ON tks.id_sales = s.id
            JOIN kegiatan_sales ks ON tks.id_kegiatan_sales = ks.id
            JOIN sales_customer sc ON ks.id_customer = sc.id
            LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
            WHERE tks.id_sales = ? AND ks.id = ? AND tks.deleted_at IS NULL";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $id_teknisi, $kode_transaksi);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

?>
    <ul class="list-group m-3 mt-2 col-12" id="data-rincian">

        <?php

        if ($result) {
            $rowNumber = 1;
            while ($data = mysqli_fetch_assoc($result)) {
                $namaTek = $data['nama'];
                $customer = $data['nama_cust'];
                $ketFinish = $data['hasil_visits'];
                $ketTam = $data['keterangan_tambahan'];
                $gambar1 = $data['gambar_1'];
                $gambar2 = $data['gambar_2'];
                $gambar3 = $data['gambar_3'];
                $gambar4 = '';
                $gambar5 = '';

                $tgl_request = $data['tgl_visits'];
                $formattedDateReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_request)) : '-';
                $formattedTimeReq = ($tgl_request && $tgl_request != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_request)) : '-';

                $tgl_mulai = $data['tgl_mulai'];
                $formattedDateMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_mulai)) : '-';
                $formattedTimeMli = ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_mulai)) : '-';

                $tgl_selesai = $data['tgl_selesai'];
                $formattedDateSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tgl_selesai)) : '-';
                $formattedTimeSls = ($tgl_selesai && $tgl_selesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tgl_selesai)) : '-';

                $status = $data['status'];

        ?>
                <div class="col-12">
                    <div class="row px-4">
                        <div class="col-4">
                            <span class="text-xs">Status</span>
                        </div>
                        <div class="col-8">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php
                                switch ($status) {
                                    case 'dijadwalkan':
                                        echo 'Dijadwalkan';
                                        break;
                                    case 'berjalan':
                                        echo 'Diproses';
                                        break;
                                    case 'selesai':
                                        echo 'Selesai';
                                        break;
                                    default:
                                        echo $status;
                                }
                                ?>
                            </h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Tanggal / Jam Visits</span>
                        </div>
                        <div class="col-8">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php echo $formattedDateReq . " / " . $formattedTimeReq; ?>
                            </h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Tanggal / Jam Mulai</span>
                        </div>
                        <div class="col-8">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php echo $formattedDateMli . " / " . $formattedTimeMli; ?>
                            </h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Lokasi Mulai</span>
                        </div>
                        <div class="col-8">
                            <?php
                            include "get-lok.php";
                            ?>

                            <div class="col-12">
                                <?php
                                $lokasi_parts = explode(',', $data['lokasi_mulai']);

                                if (count($lokasi_parts) == 2 && !empty($lokasi_parts[0]) && !empty($lokasi_parts[1])) {
                                    $latitude = $lokasi_parts[0];
                                    $longitude = $lokasi_parts[1];

                                    $addressFunction = ${"getAddressFromCoordinates$rowNumber"};
                                    $address = $addressFunction($latitude, $longitude);

                                    echo $address;
                                } else {
                                    echo "Lokasi mulai tidak tersedia.";
                                }
                                ?>

                            </div>
                        </div>

                        <div class="col-4 mt-2">
                            <span class="text-xs">Tanggal / Jam Selesai</span>
                        </div>
                        <div class="col-8 mt-2">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php echo $formattedDateSls . " / " . $formattedTimeSls; ?>
                            </h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Lokasi Selesai</span>
                        </div>
                        <div class="col-8">
                            <?php
                            include "get-lok-end.php";
                            ?>
                            <div class="col-12">
                                <?php
                                $lokasi_parts = explode(',', $data['lokasi_selesai']);

                                if (count($lokasi_parts) == 2 && !empty($lokasi_parts[0]) && !empty($lokasi_parts[1])) {
                                    $latitude = $lokasi_parts[0];
                                    $longitude = $lokasi_parts[1];

                                    $addressFunction = ${"getAddressFromCoordinatesEnd$rowNumber"};
                                    $address = $addressFunction($latitude, $longitude);

                                    echo $address;
                                } else {
                                    echo "Lokasi selesai tidak tersedia.";
                                }
                                ?>
                            </div>
                        </div>

                        <div class="col-4 mt-2">
                            <span class="text-xs">Hasil Visit</span>
                        </div>
                        <div class="col-8 mt-2">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo str_replace('. ', '<br>', $ketFinish ?? '-'); ?></h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Keterangan Tambahan</span>
                        </div>
                        <div class="col-8">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo str_replace('. ', '<br>', $ketTam ?? '-'); ?></h6>
                        </div>

                        <div class="col-12 mt-2 d-flex flex-wrap justify-content-left align-items-left">
                            <?php
                            $gambar_finish_columns = array(
                                'gambar_1',
                                'gambar_2',
                                'gambar_3'
                            );

                            foreach ($gambar_finish_columns as $column) :
                                if (!empty($data[$column]) && $data[$column] !== "NO" && $data[$column] !== "-") :
                                    $imageUrl = "https://api-teknisi.id-giti.com/storage/image/" . htmlspecialchars($data[$column]);
                            ?>
                                    <div class="image-container w-30 mb-3 me-3" style="position: relative; border-radius: 8px; overflow: hidden; border: 1.5px solid #e2e8f0;">
                                        <img src="<?php echo $imageUrl; ?>" class="img-fluid" style="width: 100%; height: 120px; object-fit: cover;" alt="">
                                        <div class="download-btn-container position-relative">
                                            <a href="<?php echo $imageUrl; ?>" target="_blank" class="btn bg-gradient-info download-btn w-100 d-md-block d-none" style="border-radius:0;"><i class="material-icons opacity-10 font-size-sm">visibility</i> Lihat Foto</a>
                                            <a href="<?php echo $imageUrl; ?>" target="_blank" class="btn bg-gradient-info download-btn w-100 d-md-none d-block" style="border-radius:0;"><i class="material-icons opacity-10">visibility</i></a>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>


        <?php
                $rowNumber++;
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
        ?>
        </li>
    </ul>
<?php
    mysqli_stmt_close($stmt);
}
?>