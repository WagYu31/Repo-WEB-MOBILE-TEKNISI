<?php

if (isset($_GET['cariTgl']) && !empty($_GET['cariTgl'])) {
    $current_date = $_GET['cariTgl'];
} else {
    $current_date = date("Y-m-d"); // Today's date
}
?>
<div class="col-lg-12">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-row">

                </div>
            </div>
        </div>
        <?php
        $tomorrow_date = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date
        $current_time = date("H:i:s"); // Current time

        // Kueri SQL untuk memilih data dengan tanggal request, tanggal mulai, atau tanggal selesai sama dengan hari ini dan status tidak sama dengan 'N'
        $sql = "SELECT visits.*, cust.nama AS nama_cust, cust.id_cust
                FROM visits
                INNER JOIN cust ON visits.id_cust = cust.id_cust
                WHERE 
                visits.status != 'N'
                GROUP BY visits.kode_transaksi
                ORDER BY visits.tgl_visits DESC";

        $result = mysqli_query($conn, $sql);

        ?>
        <div class="card-body pb-0 p-0">
            <?php

            $tanggal = date("d", strtotime($current_date));
            $tahun = date("Y", strtotime($current_date));
            // Konversi format tanggal dari Y-m-d menjadi d - M - Y
            $formatted_date = date("d - M - Y", strtotime($current_date));

            // Mendapatkan nama hari dalam bahasa Indonesia
            $day_in_indonesian = formatTanggal('EEEE', $current_date);

            // Mendapatkan nama bulan dalam bahasa Indonesia
            $month_in_indonesian = formatTanggal('MMMM', $current_date);

            ?>
            <!--<p class="ms-4 mt-2 text-dark font-weight-bold"><?php echo $day_in_indonesian . ", " . $tanggal . " " . $month_in_indonesian . " " . $tahun; ?></p>-->

            <ul class="list-group m-0 mt-2 col-12" id="data-tek">


                <?php
                $groupedData = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $idC = $row['id_cust'];
                    $namaC = $row['nama_cust'];
                    $kodeTransaksi = $row['kode_transaksi'];
                    $tgl_visits = $row['tgl_visits'];
                ?>
                    <li class="list-group-item border-0 d-flex flex-column justify-content-start align-items-start justify-content-md-between align-items-md-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                        <div class="row px-4 mt-3">

                            <div class="col-12 col-md-12 mb-md-0 bg-gradient-dark d-flex flex-column justify-content-center align-items-start">
                                <h6 class="mb-1 text-white font-weight-bold text-sm p-2">
                                    <?php
                                    echo $namaC;
                                    ?>
                                </h6>
                            </div>

                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                                <?php
                                $sqlLapTek = "SELECT visits.*, sales.nama AS nama_sales, sales.id_sales
                                FROM visits 
                                JOIN sales ON sales.id_sales = visits.id_sales
                                WHERE id_cust = $idC AND kode_transaksi = '$kodeTransaksi' AND status != 'N'";
                                // AND (DATE(tgl_request) = '$current_date' OR DATE(tgl_mulai) = '$current_date' OR DATE(tgl_selesai) = '$current_date')";
                                $resLapTek = mysqli_query($conn, $sqlLapTek);
                                if (mysqli_num_rows($resLapTek) > 0) {

                                ?>
                    <li class="list-group-item border-0 d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-block">
                        <div class="row px-4">
                            <div class="col-6 w-md-10 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Status</h6>
                                <span class="text-xs">/ Kegiatan</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Sales</h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Visits</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm"> Mulai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Selesai</h6>
                                <span class="text-xs">Tanggal / Jam</span>
                            </div>

                            <div class="col-6 w-md-10 mb-2 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Rincian Waktu</h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 text-start text-md-center">
                                <h6 class="mb-1 text-dark font-weight-bold text-sm text-start text-md-center">Hasil Visits</h6>
                            </div>



                        </div>
                    </li>
                    <?php

                                    while ($rowLT = mysqli_fetch_assoc($resLapTek)) {

                                        $idT = $rowLT["id_sales"];
                                        $ketFinish = $rowLT['hasil_visits'];
                                        $sqlMR = "SELECT *
                                                FROM visits 
                                                WHERE id_sales = '$idT' AND kode_transaksi = '$kodeTransaksi' ORDER BY id_sales ASC LIMIT 1";
                                        $resMR = mysqli_query($conn, $sqlMR);
                                        $rowMR = mysqli_fetch_assoc($resMR);
                                        $ket_finish = $rowMR["keterangan_visits"];
                                        $hslVisits = $rowMR['hasil_visits'];

                                        $tanggal_sekarang = date("d-m-Y");
                                        $datetime = $rowMR["tgl_visits"];
                                        $formattedDate = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($datetime)) : '-';
                                        $formattedTime = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("H:i", strtotime($datetime)) : '-';

                                        $tglMulai = $rowMR["tgl_mulai"];
                                        $formattedDateMulai = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglMulai)) : '-';
                                        $formattedTimeMulai = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglMulai)) : '-';

                                        $tglSelesai = $rowLT["tgl_selesai"];

                                        $formattedDateSls = ($tglSelesai && $tglSelesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglSelesai)) : '-';
                                        $formattedTimeSls = ($tglSelesai && $tglSelesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglSelesai)) : '-';

                    ?>
                        <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                            <div class="row px-4">

                                <div class="col-6 w-md-10 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                        <?php
                                        // Mengubah nilai status menjadi teks yang diinginkan
                                        $status = $rowLT['status'];
                                        switch ($status) {
                                            case 'N':
                                            case 'Pause':
                                                echo 'Lanjut Nanti';
                                                break;
                                            case 'dijadwalkan':
                                                echo 'Dijadwalkan';
                                                break;
                                            case 'On Process':
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
                                    <span class="text-xs"><a href="view-kegiatan.php?kode_transaksi=<?php echo $rowLT['kode_transaksi']; ?>&id_sales=<?php echo $idT; ?>"><?php echo $rowLT['kode_transaksi']; ?></a></span>
                                </div>

                                <div class="col-6 w-md-15 mb-2 mb-md-0 text-left">
                                    <h6 class="text-dark font-weight-bold text-sm"><?php echo $rowLT['nama_sales']; ?></h6>
                                </div>


                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDate; ?></h6>
                                    <span class="text-xs text-uppercase"><?php echo $formattedTime; ?></span>
                                </div>
                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateMulai; ?></h6>
                                    <span class="text-xs text-uppercase"><?php echo $formattedTimeMulai; ?></span>
                                </div>
                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                    <?php
                                        if ($ketFinish == "Diselesaikan oleh Admin") {
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $ketFinish; ?></h6>
                                    <?php
                                        } else {
                                    ?>
                                        <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateSls; ?></h6>
                                        <span class="text-xs"><?php echo $formattedTimeSls; ?></span>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <?php
                                        // Menghitung selisih waktu antara tgl_request dan tgl_mulai jika tgl_mulai tidak NULL dan bukan '0000-00-00 00:00:00'
                                        if ($rowLT["tgl_mulai"] && $rowLT["tgl_mulai"] != '0000-00-00 00:00:00') {
                                            if ($rowLT["tgl_visits"] < $rowLT["tgl_mulai"]) {
                                                $datetime_request = strtotime($rowLT["tgl_visits"]);
                                                $datetime_mulai = strtotime($rowLT["tgl_mulai"]);
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

                                <div class="col-6 w-md-5 mb-2 mb-md-0">
                                    <!--<h6 class="mb-1 text-danger font-weight-bold text-sm"><?php echo $telat_formatted; ?></h6>-->
                                    <button class="btn bg-gradient-info text-white detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?php echo $idT; ?>" data-kode="<?php echo $kodeTransaksi; ?>">Lihat</button>
                                    <!-- <span class="text-xs text-uppercase"><?php echo $telat_formatted; ?></span> -->
                                </div>

                                <?php
                                        if ($rowLT['status'] == 'clear') :
                                            $hslVisits = substr(str_replace('. ', '<br>', $hslVisits), 0, 30) . '...';
                                ?>
                                    <div class="col-6 w-md-25 mb-2 mb-md-0 d-flex flex-row justify-content-start justify-content-md-center align-items-center text-start text-md-center">
                                        <span class="text-xs text-dark"><?php echo $hslVisits; ?></span>
                                    </div>
                                <?php else : ?>
                                    <div class="col-6 w-md-25 mb-2 mb-md-0 d-flex flex-row justify-content-start justify-content-md-center align-items-center text-start text-md-center">
                                        <span class="text-xs text-uppercase text-danger">Kegiatan Belum Selesai</span>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </li>
            <?php
                                    }
                                } else {
                                    echo "<li class='list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-4 mb-2 border-radius-lg d-md-block d-block'>Tidak ada kegiatan.</li>";
                                }
                            }

            ?>
        </div>

    </div>
    </li>

    </ul>
</div>
</div>
</div>