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
        // Kueri SQL untuk memilih data kegiatan sales dan customer
        $sql = "SELECT ks.id, ks.kode AS kode_transaksi, ks.jadwal AS tgl_visits, sc.nama AS nama_cust, sc.id AS id_cust
                FROM kegiatan_sales ks
                INNER JOIN sales_customer sc ON ks.id_customer = sc.id
                WHERE ks.deleted_at IS NULL
                GROUP BY ks.kode
                ORDER BY ks.jadwal DESC";

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
            <ul class="list-group m-0 mt-2 col-12" id="data-tek">

                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $kegiatanId = $row['id'];
                        $idC = $row['id_cust'];
                        $namaC = $row['nama_cust'];
                        $kodeTransaksi = $row['kode_transaksi'];
                        $tgl_visits = $row['tgl_visits'];
                ?>
                    <li class="list-group-item border-0 d-flex flex-column justify-content-start align-items-start justify-content-md-between align-items-md-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                        <div class="row px-4 mt-3">

                            <div class="col-12 col-md-12 mb-md-0 bg-gradient-dark d-flex flex-column justify-content-center align-items-start">
                                <h6 class="mb-1 text-white font-weight-bold text-sm p-2">
                                    <?php echo $namaC; ?>
                                </h6>
                            </div>

                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">

                                <?php
                                $sqlLapTek = "SELECT tks.*, s.nama AS nama_sales, tks.id_sales,
                                                     IFNULL(ps.status, 'dijadwalkan') AS status,
                                                     ps.ci_at AS tgl_mulai, ps.co_at AS tgl_selesai,
                                                     ks.kode AS kode_transaksi, ks.jadwal AS tgl_visits,
                                                     ps.keterangan AS hasil_visits
                                              FROM team_kegiatan_sales tks
                                              JOIN sales s ON tks.id_sales = s.id
                                              JOIN kegiatan_sales ks ON tks.id_kegiatan_sales = ks.id
                                              LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
                                              WHERE tks.id_kegiatan_sales = '$kegiatanId' AND tks.deleted_at IS NULL";
                                $resLapTek = mysqli_query($conn, $sqlLapTek);
                                if ($resLapTek && mysqli_num_rows($resLapTek) > 0) {
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
                                                <h6 class="mb-1 text-dark font-weight-bold text-sm">Mulai</h6>
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
                                        $hslVisits = $rowLT['hasil_visits'] ?? '';
                                        $datetime = $rowLT["tgl_visits"];
                                        $formattedDate = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($datetime)) : '-';
                                        $formattedTime = ($datetime && $datetime != '0000-00-00 00:00:00') ? date("H:i", strtotime($datetime)) : '-';

                                        $tglMulai = $rowLT["tgl_mulai"];
                                        $formattedDateMli = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglMulai)) : '-';
                                        $formattedTimeMli = ($tglMulai && $tglMulai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglMulai)) : '-';

                                        $tglSelesai = $rowLT["tgl_selesai"];
                                        $formattedDateSls = ($tglSelesai && $tglSelesai != '0000-00-00 00:00:00') ? date("d-m-Y", strtotime($tglSelesai)) : '-';
                                        $formattedTimeSls = ($tglSelesai && $tglSelesai != '0000-00-00 00:00:00') ? date("H:i", strtotime($tglSelesai)) : '-';
                                    ?>
                                        <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                                            <div class="row px-4">
                                                <div class="col-6 w-md-10 mb-2 mb-md-0">
                                                    <h6 class="mb-1 text-dark font-weight-bold text-sm">
                                                        <?php
                                                        $status = strtolower($rowLT['status']);
                                                        switch ($status) {
                                                            case 'selesai':
                                                                echo 'Selesai';
                                                                break;
                                                            case 'berjalan':
                                                                echo 'Diproses';
                                                                break;
                                                            case 'dijadwalkan':
                                                            default:
                                                                echo 'Dijadwalkan';
                                                                break;
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
                                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateMli; ?></h6>
                                                    <span class="text-xs text-uppercase"><?php echo $formattedTimeMli; ?></span>
                                                </div>
                                                <div class="col-6 w-md-15 mb-2 mb-md-0">
                                                    <h6 class="mb-1 text-dark font-weight-bold text-sm"><?php echo $formattedDateSls; ?></h6>
                                                    <span class="text-xs"><?php echo $formattedTimeSls; ?></span>
                                                </div>
                                                <?php
                                                // Menghitung selisih waktu
                                                if ($rowLT["tgl_mulai"] && $rowLT["tgl_mulai"] != '0000-00-00 00:00:00') {
                                                    if ($rowLT["tgl_visits"] < $rowLT["tgl_mulai"]) {
                                                        $telat_in_seconds = strtotime($rowLT["tgl_mulai"]) - strtotime($rowLT["tgl_visits"]);
                                                        $telat_hours = floor($telat_in_seconds / 3600);
                                                        $telat_minutes = floor(($telat_in_seconds % 3600) / 60);
                                                        $telat_seconds = $telat_in_seconds % 60;
                                                        $telat_formatted = ($telat_hours > 0 ? $telat_hours . ' jam, ' : '') . ($telat_minutes > 0 ? $telat_minutes . ' menit, ' : '') . $telat_seconds . ' detik';
                                                    } else {
                                                        $telat_formatted = '0';
                                                    }
                                                } else {
                                                    $telat_formatted = '-';
                                                }
                                                ?>
                                                <div class="col-6 w-md-5 mb-2 mb-md-0">
                                                    <button class="btn bg-gradient-info text-white detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?php echo $idT; ?>" data-kode="<?php echo $kodeTransaksi; ?>">Lihat</button>
                                                </div>
                                                <?php if ($status == 'selesai') : 
                                                    $shortVisits = strlen($hslVisits) > 30 ? substr($hslVisits, 0, 30) . '...' : $hslVisits;
                                                ?>
                                                    <div class="col-6 w-md-25 mb-2 mb-md-0 d-flex flex-row justify-content-start justify-content-md-center align-items-center text-start text-md-center">
                                                        <span class="text-xs text-dark"><?php echo nl2br(htmlspecialchars($shortVisits)); ?></span>
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
                                ?>
                            </div>
                        </div>
                    </li>
                <?php
                    }
                } else {
                    echo "<li class='list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-4 mb-2 border-radius-lg d-md-block d-block'>Tidak ada data kunjungan.</li>";
                }
                ?>
            </ul>
        </div>
    </div>
</div>