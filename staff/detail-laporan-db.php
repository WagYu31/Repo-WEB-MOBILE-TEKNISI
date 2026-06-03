<?php
    if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
        $current_date = $_GET['cariBulanTahun'];
    } else {
        $current_date = date("Y-m");
    }
?>
<style>
    .laporan-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .laporan-table thead th {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 14px 12px;
        border: none;
        white-space: nowrap;
    }
    .laporan-table thead th:first-child { border-radius: 10px 0 0 0; }
    .laporan-table thead th:last-child { border-radius: 0 10px 0 0; }
    .laporan-table tbody tr { transition: all 0.2s ease; }
    .laporan-table tbody tr:hover { background: #f0f9ff; transform: scale(1.002); }
    .laporan-table tbody td {
        padding: 12px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .laporan-table tbody tr:last-child td { border-bottom: none; }
    .laporan-table tfoot td {
        padding: 14px 12px;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        border: none;
    }
    .laporan-table tfoot td:first-child { border-radius: 0 0 0 10px; }
    .laporan-table tfoot td:last-child { border-radius: 0 0 10px 0; }
    .badge-survey-tag {
        display: inline-block;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #78350f;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
    .surveyor-name {
        font-weight: 500;
        color: #6366f1;
    }
    .invoice-link { color: #2563eb; font-weight: 600; text-decoration: none; }
    .invoice-link:hover { text-decoration: underline; }
    .teknisi-link { color: #334155; font-weight: 600; text-decoration: none; }
    .teknisi-link:hover { color: #2563eb; }
    .nominal-text { font-weight: 600; color: #059669; white-space: nowrap; }
    .no-data-text { color: #94a3b8; font-style: italic; }
    .row-num { 
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 50%;
        background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 600;
    }
    @media (max-width: 768px) {
        .laporan-table thead { display: none; }
        .laporan-table tbody tr {
            display: block; margin-bottom: 16px; padding: 16px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .laporan-table tbody td {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 4px; border-bottom: 1px solid #f1f5f9;
        }
        .laporan-table tbody td::before {
            content: attr(data-label);
            font-weight: 600; font-size: 12px; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.03em;
            flex: 0 0 40%;
        }
        .laporan-table tbody td:last-child { border-bottom: none; }
        .laporan-table tfoot { display: block; }
        .laporan-table tfoot tr {
            display: flex; justify-content: space-between;
            padding: 14px 16px; border-radius: 12px;
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        }
        .laporan-table tfoot td { padding: 0; background: none; }
        .laporan-table tfoot td:first-child { border-radius: 0; }
        .laporan-table tfoot td:last-child { border-radius: 0; }
        .hide-mobile { display: none !important; }
    }
</style>

<div class="col-12">
    <div class="card mb-2 p-2 ps-4">
        <input type="text" id="search-input" class="form-control" placeholder="🔍 Cari invoice, teknisi, atau customer...">
    </div>
</div>
<div class="col-lg-12" id="printable-content">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Pendapatan Teknisi</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-row">
                    <form method="GET" action="" class="col-12 col-md-12 d-flex align-items-center justify-content-center flex-row">
                        <input type="month" class="form-control border p-2 bg-outline-info w-70 no-print" name="cariBulanTahun" value="<?php echo $current_date;?>">
                        <button class="btn bg-gradient-info w-30 mt-3 ms-2 no-print">Cari</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $tomorrow_date = date("Y-m-d", strtotime("+1 day"));
        $current_time = date("H:i:s");
        
        $sql = "SELECT pk.*, 
                       GROUP_CONCAT(DISTINCT CONCAT('<a href=\"detail-lap-tek.php?cariBulanTahun=$current_date&idTek=', t.id, '\">', t.nama, '</a>') SEPARATOR '<br>') AS nama_teknisi, 
                       k.customer_id, 
                       c.nama AS nama_cust,
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
                JOIN kegiatan k ON k.kode = pk.kode
                JOIN customer c ON c.id = k.customer_id
                JOIN teknisi t ON t.id = pk.teknisi_id
                WHERE pk.deleted_at IS NULL
                AND DATE_FORMAT(pk.tanggal, '%Y-%m') = '$current_date'
                GROUP BY pk.kode
                ORDER BY pk.tanggal ASC";
                
        $result = mysqli_query($conn, $sql);
        $totalBonusAll = 0;
        $rowNum = 0;

        $daftar_bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $timestamp = strtotime($current_date);
        $bulan = $daftar_bulan[(int)date('m', $timestamp)];
        $tahun_display = date('Y', $timestamp);
        ?>
        <div class="card-body pb-0 px-3 pt-2">
            <p class="text-dark ms-1 mb-3" style="font-size:15px;">
                📅 Bulan <strong><?php echo $bulan . ' ' . $tahun_display; ?></strong>
            </p>
            
            <div class="table-responsive" style="border-radius:10px; overflow:hidden;">
                <table class="laporan-table" id="data-tek">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th style="width:90px;">Tgl Invoice</th>
                            <th style="width:130px;">No Invoice</th>
                            <th>Teknisi</th>
                            <th>Customer</th>
                            <th style="width:160px;">Ket. Survey</th>
                            <th style="width:120px;">Surveyor</th>
                            <th style="width:120px; text-align:right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $rowNum++;
                            $idT = $row['teknisi_id'];
                            $namaT = $row['nama_teknisi'];
                            $namaC = $row['nama_cust'];
                            $invoice = $row['no_invoice'];
                            $kodeTran = $row['kode'];
                            $nominal = $row['nominal_invoice'];
                            $tglInv = date('d M Y', strtotime($row['tanggal']));
                            $ketSurvey = $row['keterangan_survey'] ?? '';
                            $surveyor = $row['surveyor'] ?? '';
                            
                            $totalBonusAll += $nominal;
                            $nominalFormatted = "Rp " . number_format($nominal, 0, ',', '.');
                    ?>
                        <tr>
                            <td data-label="#"><span class="row-num"><?php echo $rowNum; ?></span></td>
                            <td data-label="Tgl Invoice"><?php echo $tglInv; ?></td>
                            <td data-label="No Invoice"><span class="invoice-link"><?php echo $invoice; ?></span></td>
                            <td data-label="Teknisi"><span class="teknisi-link"><?php echo $namaT; ?></span></td>
                            <td data-label="Customer"><?php echo $namaC; ?></td>
                            <td data-label="Ket. Survey">
                                <?php if (!empty($ketSurvey)): ?>
                                    <span class="badge-survey-tag"><?php echo $ketSurvey; ?></span>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Surveyor">
                                <?php if (!empty($surveyor)): ?>
                                    <span class="surveyor-name"><?php echo $surveyor; ?></span>
                                <?php else: ?>
                                    <span class="no-data-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Nominal" style="text-align:right;">
                                <span class="nominal-text"><?php echo $nominalFormatted; ?></span>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">
                                <div style="font-size:48px; margin-bottom:8px;">📭</div>
                                <div style="font-size:14px; font-weight:500;">Tidak ada data pendapatan untuk bulan ini</div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7"><strong>TOTAL PENDAPATAN</strong></td>
                            <td style="text-align:right;"><strong><?php echo "Rp " . number_format($totalBonusAll, 0, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>