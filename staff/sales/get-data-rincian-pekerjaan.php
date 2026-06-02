<?php
include "../conn.php";

if (isset($_POST['id_sales']) && isset($_POST['kode_transaksi'])) {
    $id_teknisi = $_POST['id_sales'];
    $kode_transaksi = $_POST['kode_transaksi'];

    // Query untuk mengambil data dari database
    $sql = "SELECT v.*, s.nama, s.id_sales, c.nama AS nama_cust, c.id_cust 
            FROM visits v
            JOIN sales s ON s.id_sales = v.id_sales
            JOIN cust c ON c.id_cust = v.id_cust
            WHERE v.id_sales = ? AND v.kode_transaksi = ?";

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
                $gambar4 = $data['gambar_4'];
                $gambar5 = $data['gambar_5'];

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
                                    case 'on process':
                                        echo 'Diproses';
                                        break;
                                    case 'clear':
                                        echo 'Selesai';
                                        break;
                                    default:
                                        echo $status; // Jika status tidak sesuai dengan kondisi di atas, biarkan nilainya tetap
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

                        <div class="col-4 mt-2">
                            <span class="text-xs">Hasil Visit</span>
                        </div>
                        <div class="col-8 mt-2">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo str_replace('. ', '<br>', $ketFinish); ?></h6>
                        </div>

                        <div class="col-4">
                            <span class="text-xs">Keterangan Tambahan</span>
                        </div>
                        <div class="col-8">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo str_replace('. ', '<br>', $ketTam); ?></h6>
                        </div>

                        <div class="col-12 mt-2 d-flex flex-wrap justify-content-left align-items-left">
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
                                if (!empty($data[$column]) && $data[$column] !== "NO" && $data[$column] !== "-") :
                            ?>
                                    <div class="image-container w-30 mb-3 me-3">
                                        <img src="assets/img/uploads/<?php echo $data[$column]; ?>" class="img-fluid" alt="">
                                        <div class="download-btn-container position-relative">
                                            <a href="assets/img/uploads/<?php echo $data[$column]; ?>" download class="btn bg-gradient-info download-btn w-100 d-md-block d-none" style="border-radius:0;"><i class="material-icons opacity-10">download</i> Download</a>
                                            <a href="assets/img/uploads/<?php echo $data[$column]; ?>" download class="btn bg-gradient-info download-btn w-100 d-md-none d-block" style="border-radius:0;"><i class="material-icons opacity-10">download</i></a>
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