<?php

if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
    $current_date = $_GET['cariBulanTahun'];
} else {
    $current_date = date("Y-m"); // Today's date
}
?>
<div class="col-12">
    <div class="card mb-2 p-2 ps-4">
        <input type="text" id="search-input" class="form-control" placeholder="Cari...">
    </div>
</div>
<div class="col-lg-12" id="printable-content">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Target Tercapai Teknisi</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-row">
                    <form method="GET" action="" class="col-12 col-md-12 d-flex align-items-center justify-content-center flex-row">
                        <input type="month" class="form-control border p-2 bg-outline-info w-70 no-print" name="cariBulanTahun" value="<?php echo $current_date; ?>">
                        <input type="hidden" name="idTek" value="<?php echo $idTeknis; ?>">
                        <button class="btn bg-gradient-info w-30 mt-3 ms-2 no-print">Cari</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $tomorrow_date = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date
        $current_time = date("H:i:s"); // Current time

        if (isset($idTeknis) && $idTeknis !== null) {
            if (function_exists('ensurePorsiKerjaColumn')) ensurePorsiKerjaColumn($conn);
            $usesLunasRule = targetTercapaiUsesLunas($current_date);
            $periodWhereSql = targetTercapaiPeriodWhereSql([$current_date], 'pk.tanggal', 'k_lunas.tgl_lunas');
            $orderBySql = $usesLunasRule ? 'k_lunas.tgl_lunas ASC, pk.tanggal ASC' : 'pk.tanggal ASC';
            $idTekEsc = intval($idTeknis);
            $sql = "SELECT pk.*, 
                       GROUP_CONCAT(DISTINCT CONCAT('<a href=\"detail-lap-tek.php?cariBulanTahun=$current_date&idTek=', t.id, '\">', t.nama, IF(pk.porsi_kerja < 1, ' (½ Hari)', ''), '</a>') SEPARATOR '<br>') AS nama_teknisi, 
                       k_lunas.customer_id, 
                       k_lunas.tgl_lunas,
                       c.nama AS nama_cust,
                       SUM(ROUND((pk.nominal_invoice / counts.tek_count) * pk.porsi_kerja)) AS calc_tek_share,
                       (SELECT GROUP_CONCAT(DISTINCT CONCAT(UPPER(k2.kegiatan), ' - ', DATE_FORMAT(k2.jadwal, '%d/%m/%Y')) SEPARATOR ', ')
                        FROM kegiatan k2
                        WHERE k2.kode = pk.kode 
                        AND LOWER(k2.kegiatan) = 'survey'
                       ) AS keterangan_survey,
                       (SELECT GROUP_CONCAT(DISTINCT t2.nama SEPARATOR ', ')
                        FROM kegiatan k3
                        JOIN team_kegiatan tk3 ON tk3.kegiatan_id = k3.id
                        JOIN teknisi t2 ON t2.id = tk3.teknisi_id
                        WHERE k3.kode = pk.kode 
                        AND LOWER(k3.kegiatan) = 'survey'
                        AND tk3.deleted_at IS NULL
                       ) AS surveyor
                FROM pendapatan_kegiatan pk
                JOIN (
                    SELECT kode, COUNT(*) AS tek_count
                    FROM pendapatan_kegiatan
                    WHERE deleted_at IS NULL
                    GROUP BY kode
                ) counts ON counts.kode = pk.kode
                JOIN " . targetTercapaiLunasSubquerySql('k_lunas') . " ON k_lunas.kode = pk.kode
                JOIN customer c ON c.id = k_lunas.customer_id
                JOIN teknisi t ON t.id = pk.teknisi_id
                WHERE pk.deleted_at IS NULL
                AND $periodWhereSql
                AND pk.pendapatan != 0
                AND pk.teknisi_id = $idTekEsc
                GROUP BY pk.kode
                ORDER BY $orderBySql";

            $result = mysqli_query($conn, $sql);

            if (!$result) {
                echo "Error executing query: " . mysqli_error($conn);
            }
        } else {
            echo "idTeknis is not set.";
        }


        $totalBonusAll = 0; // Inisialisasi variabel untuk menampung total keseluruhan

        ?>
        <div class="card-body pb-0 p-0">
            <div class="col-12">
                <p class="text-dark ms-4">
                    Bulan
                    <?php
                    $bt = formatTanggal('MMMM yyyy', $current_date);
                    echo $bt;
                    ?>
                </p>
            </div>
            <ul class="list-group m-0 mt-4 col-12" id="data-tek">

                <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-none">
                    <div class="row px-4 w-md-100">

                        <div class="col-6 w-md-8 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-center">
                                Tgl Invoice
                            </h6>
                        </div>
                        <div class="col-6 w-md-12 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-1 text-start">
                                No Invoice
                            </h6>
                        </div>
                        <div class="col-6 w-md-15 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-2 text-start">
                                Teknisi
                            </h6>
                        </div>
                        <div class="col-6 w-md-15 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 ms-2 text-start">
                                Customer
                            </h6>
                        </div>
                        <div class="col-6 w-md-15 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-start">
                                Ket. Survey
                            </h6>
                        </div>
                        <div class="col-6 w-md-15 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-start">
                                Surveyor
                            </h6>
                        </div>
                        <div class="col-6 w-md-10 mb-md-0">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm p-2 text-end">
                                Nominal Invoice
                            </h6>
                        </div>

                        <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                        </div>

                    </div>
                </li>

                <?php
                while ($row = mysqli_fetch_assoc($result)) {
                    $idT = $row['teknisi_id'];
                    $namaT = $row['nama_teknisi'];
                    $namaC = $row['nama_cust'];
                    $invoice = $row['no_invoice'];
                    $kodeTran = $row['kode'];
                    $nominal = floatval($row['calc_tek_share'] ?? 0);
                    $tglInvRaw = date('d M Y', strtotime($row['tanggal']));
                    $tglLunasRaw = !empty($row['tgl_lunas']) ? date('d M Y', strtotime($row['tgl_lunas'])) : '';
                    $tglInv = (!empty($usesLunasRule) && $tglLunasRaw !== '')
                        ? $tglLunasRaw . ($tglLunasRaw !== $tglInvRaw ? '<div class="text-xs text-muted">Inv: ' . $tglInvRaw . '</div>' : '')
                        : $tglInvRaw;
                    $ketSurvey = $row['keterangan_survey'] ?? '-';
                    $surveyor = $row['surveyor'] ?? '-';

                ?>
                    <li class="list-group-item border-0 d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg d-md-block d-block">
                        <div class="row px-4">


                            <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Tanggal Invoice</span>
                            </div>

                            <div class="col-6 w-md-8 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-normal text-sm p-2 text-start text-md-center">
                                    <?php echo $tglInv; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-12 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Nomor Invoice</span>
                            </div>
                            <div class="col-6 w-md-12 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-md-normal font-weight-bold text-sm p-2 text-start">
                                    <?php echo $invoice; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Teknisi</span>
                            </div>
                            <div class="col-6 w-md-15 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-md-normal font-weight-bold text-sm p-2">
                                    <?php echo $namaT; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Customer</span>
                            </div>
                            <div class="col-6 w-md-15 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-normal text-sm p-2">
                                    <?php echo $namaC; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Ket. Survey</span>
                            </div>
                            <div class="col-6 w-md-15 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-normal text-sm p-2">
                                    <?php echo !empty($ketSurvey) ? '<span class="badge bg-warning text-dark">' . $ketSurvey . '</span>' : '<span class="text-muted">-</span>'; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-15 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Surveyor</span>
                            </div>
                            <div class="col-6 w-md-15 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-normal text-sm p-2">
                                    <?php echo !empty($surveyor) ? $surveyor : '<span class="text-muted">-</span>'; ?>
                                </h6>
                            </div>

                            <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none">
                                <span class="text-xs">Nominal Invoice</span>
                            </div>
                            <div class="col-6 w-md-10 mb-md-0">
                                <h6 class="mb-1 text-dark font-weight-normal text-sm p-2 text-start text-md-end">
                                    <?php
                                    $totalBonusFormatted = "Rp " . number_format($nominal, 0, ',', '.');
                                    echo $totalBonusFormatted;
                                    $totalBonusAll += $nominal;
                                    ?>
                                </h6>
                            </div>


                            <div class="col-12 col-md-10 mb-2 mb-md-0 text-left">


                            </div>

                        </div>
                    </li>
                <?php
                }
                ?>
                <li class="list-group-item border-0 d-flex flex-column justify-content-center align-items-center bg-info ps-0 mb-2 d-md-block d-block">
                    <div class="row px-4">

                        <div class="col-6 w-md-10 mb-2 mb-md-0 d-md-none d-block">
                            <h6 class="mb-1 text-white font-weight-bold text-sm p-2 text-start text-md-center">
                                Total Target Tercapai
                            </h6>
                        </div>
                        <div class="col-6 w-md-15 mb-md-0 d-md-block d-none">
                            <h6 class="mb-1 text-white font-weight-bold text-sm p-2 text-center">
                                TOTAL
                            </h6>
                        </div>
                        <div class="col-6 w-md-70 mb-md-0 d-none d-md-flex">
                        </div>
                        <div class="col-6 w-md-15 mb-md-0">
                            <h6 class="mb-1 text-white font-weight-bold text-sm p-2 text-start text-md-center">
                                <?php
                                echo "Rp " . number_format($totalBonusAll, 0, ',', '.');
                                ?>
                            </h6>
                        </div>
                    </div>

                </li>
            </ul>
        </div>
    </div>
</div>