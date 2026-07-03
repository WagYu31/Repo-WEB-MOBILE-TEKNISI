<?php

if (isset($_GET['cariTgl']) && !empty($_GET['cariTgl'])) {
    $current_date = $_GET['cariTgl'];
} else {
    $current_date = date("Y-m-d"); // Today's date
}
?>
<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">Laporan Kegiatan</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-row">

                </div>
            </div>
        </div>
        <?php
        // Kueri SQL untuk memilih data kegiatan sales dan customer
        $sql = "SELECT ks.id, ks.id AS kode_transaksi, ks.jadwal AS tgl_visits, sc.nama AS nama_cust, sc.id AS id_cust
                FROM kegiatan_sales ks
                INNER JOIN sales_customer sc ON ks.id_customer = sc.id
                WHERE ks.deleted_at IS NULL
                ORDER BY ks.jadwal DESC";

        $result = mysqli_query($conn, $sql);

        ?>
        <div class="card-body p-3">
            <?php

            $tanggal = date("d", strtotime($current_date));
            $tahun = date("Y", strtotime($current_date));
            $formatted_date = date("d - M - Y", strtotime($current_date));
            $day_in_indonesian = formatTanggal('EEEE', $current_date);
            $month_in_indonesian = formatTanggal('MMMM', $current_date);

            ?>
            <div class="d-flex flex-column gap-4 mt-2">

                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $kegiatanId = $row['id'];
                        $idC = $row['id_cust'];
                        $namaC = $row['nama_cust'];
                        $kodeTransaksi = $row['kode_transaksi'];
                        $tgl_visits = $row['tgl_visits'];
                ?>
                    <!-- Customer Activity Card -->
                    <div class="card border mb-3" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); overflow: hidden;">
                        
                        <!-- Customer Name Title Header -->
                        <div class="bg-gradient-dark p-3">
                            <h6 class="mb-0 text-white font-weight-bold text-sm">
                                <?php echo $namaC; ?>
                            </h6>
                        </div>

                        <!-- Inner Activities Table -->
                        <div class="p-3">
                            <?php
                            $sqlLapTek = "SELECT tks.*, s.nama AS nama_sales, tks.id_sales,
                                                 IFNULL(ps.status, 'dijadwalkan') AS status,
                                                 ps.ci_at AS tgl_mulai, ps.co_at AS tgl_selesai,
                                                 ks.id AS kode_transaksi, ks.jadwal AS tgl_visits,
                                                 ps.keterangan AS hasil_visits
                                          FROM team_kegiatan_sales tks
                                          JOIN sales s ON tks.id_sales = s.id
                                          JOIN kegiatan_sales ks ON tks.id_kegiatan_sales = ks.id
                                          LEFT JOIN pelaksanaan_sales ps ON ps.kegiatan_id = tks.id_kegiatan_sales AND ps.sales_id = tks.id_sales
                                          WHERE tks.id_kegiatan_sales = '$kegiatanId' AND tks.deleted_at IS NULL";
                            $resLapTek = mysqli_query($conn, $sqlLapTek);
                            if ($resLapTek && mysqli_num_rows($resLapTek) > 0) {
                            ?>
                                <!-- Header Row for Desktop -->
                                <div class="row px-3 py-2 bg-light d-none d-md-flex font-weight-bold text-xs text-secondary mb-2" style="border-radius: 6px;">
                                    <div class="col-md-2">Status / Kegiatan</div>
                                    <div class="col-md-2">Sales</div>
                                    <div class="col-md-2">Visits</div>
                                    <div class="col-md-2">Mulai</div>
                                    <div class="col-md-2">Selesai</div>
                                    <div class="col-md-1 text-center">Rincian</div>
                                    <div class="col-md-1 text-center">Hasil</div>
                                </div>

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
                                    $status = strtolower($rowLT['status']);
                                ?>
                                    <!-- Activity Row -->
                                    <div class="row px-3 py-3 align-items-center mb-2" style="border-bottom: 1px solid #f1f5f9;">
                                        
                                        <!-- Status / Kegiatan -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Status</span>
                                            <span class="badge <?php 
                                                echo match($status) {
                                                    'selesai' => 'bg-success',
                                                    'berjalan' => 'bg-warning',
                                                    default => 'bg-secondary'
                                                };
                                            ?> text-xs">
                                                <?php echo ucfirst($status == 'berjalan' ? 'Diproses' : ($status == 'selesai' ? 'Selesai' : 'Dijadwalkan')); ?>
                                            </span>
                                            <div class="text-xs mt-1 font-weight-bold">
                                                <a href="view-kegiatan.php?kode_transaksi=<?php echo $rowLT['kode_transaksi']; ?>&id_sales=<?php echo $idT; ?>" class="text-info">
                                                    #<?php echo $rowLT['kode_transaksi']; ?>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Sales -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Sales</span>
                                            <h6 class="text-dark font-weight-bold text-sm mb-0"><?php echo $rowLT['nama_sales']; ?></h6>
                                        </div>

                                        <!-- Visits -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Visits</span>
                                            <span class="text-xs text-dark font-weight-bold d-block"><?php echo $formattedDate; ?></span>
                                            <span class="text-xxs text-secondary"><?php echo $formattedTime; ?></span>
                                        </div>

                                        <!-- Mulai -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Mulai</span>
                                            <span class="text-xs text-dark d-block"><?php echo $formattedDateMli; ?></span>
                                            <span class="text-xxs text-secondary"><?php echo $formattedTimeMli; ?></span>
                                        </div>

                                        <!-- Selesai -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Selesai</span>
                                            <span class="text-xs text-dark d-block"><?php echo $formattedDateSls; ?></span>
                                            <span class="text-xxs text-secondary"><?php echo $formattedTimeSls; ?></span>
                                        </div>

                                        <!-- Rincian (Detail button) -->
                                        <div class="col-6 col-md-1 text-md-center mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Rincian</span>
                                            <button class="btn bg-gradient-info btn-sm text-white px-3 py-1 mb-0 detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?php echo $idT; ?>" data-kode="<?php echo $kodeTransaksi; ?>">Lihat</button>
                                        </div>

                                        <!-- Hasil Visits -->
                                        <div class="col-6 col-md-1 text-md-center">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Hasil</span>
                                            <?php if ($status == 'selesai') : 
                                                $shortVisits = strlen($hslVisits) > 30 ? substr($hslVisits, 0, 30) . '...' : $hslVisits;
                                            ?>
                                                <span class="text-xs text-dark d-block" title="<?php echo htmlspecialchars($hslVisits); ?>"><?php echo nl2br(htmlspecialchars($shortVisits)); ?></span>
                                            <?php else : ?>
                                                <span class="text-xxs text-uppercase font-weight-bold text-danger">Belum Selesai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php
                                }
                            } else {
                                echo "<div class='text-center text-sm text-secondary py-3'>Tidak ada kegiatan.</div>";
                            }
                            ?>
                        </div>
                    </div>
                <?php
                    }
                } else {
                    echo "<div class='text-center text-sm text-secondary py-4'>Tidak ada data kunjungan.</div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>