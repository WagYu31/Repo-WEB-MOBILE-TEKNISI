<?php
include "conn.php";

if (isset($_POST['id_teknisi']) && isset($_POST['kode_transaksi'])) {
    $id_teknisi = $_POST['id_teknisi'];
    $kode_transaksi = $_POST['kode_transaksi'];

    // Query untuk mengambil data dari database
    $sql = "SELECT k.*, t.nama, t.id_teknisi, c.nama AS nama_cust, c.id_cust 
            FROM kegiatan k
            JOIN teknisi t ON t.id_teknisi = k.id_teknisi
            JOIN customer c ON c.id_cust = k.id_cust
            WHERE k.id_teknisi = ? AND k.kode_transaksi = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "is", $id_teknisi, $kode_transaksi);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

?>
    <ul class="list-group m-0 mt-2 col-12" id="data-rincian">
        <li class="list-group-item border-0 d-none d-md-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
            <div class="row px-4">
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                    <!--<span class="text-xs">/ Kegiatan</span>-->
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Request</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm"> Mulai</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>
                    <span class="text-xs">Tanggal / Jam</span>
                </div>

                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <h6 class="mb-1 text-dark font-weight-bold text-sm">Terlambat</h6>
                </div>
            </div>
        </li>

        <?php

        if ($result) {
            while ($data = mysqli_fetch_assoc($result)) {
                $namaTek = $data['nama'];
                $customer = $data['nama_cust'];
                $ketFinish = $data['ket_finish'];

                $tgl_request = $data['tgl_request'];
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

                <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
                    <div class="row px-4">
                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Status</div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                <?php
                                    switch ($status) {
                                        case 'N':
                                            echo 'Lanjut Nanti';
                                            break;
                                        case 'Pause':
                                            echo 'Lanjut Nanti';
                                            break;
                                        case 'Pending':
                                            echo 'Dijadwalkan';
                                            break;
                                        case 'On Process':
                                            echo 'Diproses';
                                            break;
                                        case 'Clear':
                                            echo 'Selesai';
                                            break;
                                        default:
                                            echo $status; // Jika status tidak sesuai dengan kondisi di atas, biarkan nilainya tetap
                                    }
                                ?>
                            </h6>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Request</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateReq; ?></h6>
                            <span class="text-xs"><?php echo $formattedTimeReq; ?></span>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Mulai</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"> <?php echo $formattedDateMli; ?></h6>
                            <span class="text-xs"><?php echo $formattedTimeMli; ?></span>
                        </div>

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Tanggal Selesai</div>
                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                            <?php
                                if($ketFinish == "Diselesaikan oleh Admin"){
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $ketFinish; ?></h6>
                                    <?php
                                }else{
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateSls; ?></h6>
                                        <span class="text-xs"><?php echo $formattedTimeSls; ?></span>
                                    <?php
                                }
                            ?>
                            
                            
                        </div>
                        
                                        <?php
                                        // Menghitung selisih waktu antara tgl_request dan tgl_mulai jika tgl_mulai tidak NULL dan bukan '0000-00-00 00:00:00'
                                        if ($tgl_mulai && $tgl_mulai != '0000-00-00 00:00:00') {
                                            if ($tgl_request < $tgl_mulai) {
                                                $datetime_request = strtotime($tgl_request);
                                                $datetime_mulai = strtotime($tgl_mulai);
                                                $telat_in_seconds = $datetime_mulai - $datetime_request;

                                                // Mengonversi selisih waktu menjadi jam, menit, dan detik
                                                $telat_hours = floor($telat_in_seconds / 3600);
                                                $telat_minutes = floor(($telat_in_seconds % 3600) / 60);
                                                $telat_seconds = $telat_in_seconds % 60;

                                                // Format selisih waktu ke dalam string "x jam, y menit, z detik"
                                                $telat_formatted = '';
                                                if ($telat_hours > 0) {
                                                    $telat_formatted .= $telat_hours . ' jam, ';
                                                }
                                                if ($telat_minutes > 0) {
                                                    $telat_formatted .= $telat_minutes . ' menit, ';
                                                }
                                                $telat_formatted .= $telat_seconds . ' detik';
                                            } else {
                                                $telat_formatted = '0';
                                            }
                                        } else {
                                            $telat_formatted = '-';
                                        }
                                ?>
                                

                        <div class="col-6 mb-2 mb-md-0 d-block d-md-none">Terlambat</div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $telat_formatted; ?></h6>
                        </div>
                    </div>
                </li>

        <?php
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