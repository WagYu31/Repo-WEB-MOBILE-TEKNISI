<?php
// Filter variables
$filterSales = isset($_GET['id_sales']) ? intval($_GET['id_sales']) : 0;
$filterBulan = isset($_GET['bulan']) ? trim($_GET['bulan']) : date("Y-m");

// Fetch sales list for dropdown
$resSalesList = mysqli_query($conn, "SELECT id, nama FROM sales WHERE deleted_at IS NULL ORDER BY nama ASC");
$salesOptions = [];
if ($resSalesList) {
    while ($rS = mysqli_fetch_assoc($resSalesList)) {
        $salesOptions[] = $rS;
    }
}
?>
<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: none;">
        <div class="card-header pb-3 p-3 bg-transparent border-bottom">
            <div class="row align-items-center">
                <div class="col-12 col-xl-4 d-flex align-items-center mb-3 mb-xl-0">
                    <h5 class="mb-0 mx-1 ms-2 font-weight-bold text-dark text-uppercase" style="letter-spacing: 0.5px;"><i class="fa-solid fa-clipboard-list text-primary me-2"></i>Laporan Kunjungan Sales</h5>
                </div>
                <div class="col-12 col-xl-8">
                    <form method="GET" action="" class="row g-2 align-items-center justify-content-xl-end">
                        <div class="col-12 col-sm-4">
                            <select name="id_sales" class="form-select border p-2 bg-white text-dark" style="border-radius: 8px;">
                                <option value="0">-- Semua Sales --</option>
                                <?php foreach ($salesOptions as $opt) : ?>
                                    <option value="<?php echo $opt['id']; ?>" <?php echo $filterSales == $opt['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-3">
                            <input type="month" class="form-control border p-2 bg-white text-dark" name="bulan" value="<?php echo $filterBulan; ?>" style="border-radius: 8px;">
                        </div>
                        <div class="col-6 col-sm-2 d-grid">
                            <button type="submit" class="btn bg-gradient-primary mb-0 p-2 text-white" style="border-radius: 8px;">Cari</button>
                        </div>
                        <div class="col-6 col-sm-3 d-grid">
                            <button type="button" class="btn bg-gradient-success mb-0 p-2 text-white" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#syncSheetsModal">
                                <i class="fa-solid fa-file-excel me-1"></i>Sync Sheets
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        // Build SQL with filters
        $whereClauses = ["ks.deleted_at IS NULL"];
        if ($filterSales > 0) {
            $whereClauses[] = "ks.id IN (SELECT id_kegiatan_sales FROM team_kegiatan_sales WHERE id_sales = $filterSales AND deleted_at IS NULL)";
        }
        if (!empty($filterBulan)) {
            $whereClauses[] = "DATE_FORMAT(ks.jadwal, '%Y-%m') = '" . mysqli_real_escape_string($conn, $filterBulan) . "'";
        }
        
        $whereSql = implode(" AND ", $whereClauses);

        $sql = "SELECT ks.id, ks.id AS kode_transaksi, ks.jadwal AS tgl_visits, sc.nama AS nama_cust, sc.id AS id_cust
                FROM kegiatan_sales ks
                INNER JOIN sales_customer sc ON ks.id_customer = sc.id
                WHERE $whereSql
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
                    <div class="card mb-4" style="border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden; background-color: #ffffff;">
                        
                        <!-- Customer Name Title Header -->
                        <div class="p-3 border-bottom d-flex align-items-center" style="background-color: #f8fafc;">
                            <div class="d-flex align-items-center">
                                <span class="bg-gradient-primary p-2 text-white d-flex align-items-center justify-content-center me-2" style="border-radius: 10px; width: 32px; height: 32px;">
                                    <i class="fa-solid fa-building text-xs"></i>
                                </span>
                                <h6 class="mb-0 text-dark font-weight-bold text-sm" style="letter-spacing: 0.02em;">
                                    <?php echo htmlspecialchars($namaC); ?>
                                </h6>
                            </div>
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
                            if ($filterSales > 0) {
                                $sqlLapTek .= " AND tks.id_sales = $filterSales";
                            }
                            $resLapTek = mysqli_query($conn, $sqlLapTek);
                            if ($resLapTek && mysqli_num_rows($resLapTek) > 0) {
                            ?>
                                <!-- Header Row for Desktop -->
                                <div class="row px-3 py-2 bg-light d-none d-md-flex font-weight-bold text-xxs text-uppercase text-secondary mb-2" style="border-radius: 8px; letter-spacing: 0.5px;">
                                    <div class="col-md-2">Status / Kegiatan</div>
                                    <div class="col-md-2">Sales Agent</div>
                                    <div class="col-md-2">Jadwal Kunjungan</div>
                                    <div class="col-md-2">Waktu Mulai</div>
                                    <div class="col-md-2">Waktu Selesai</div>
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

                                    $badgeStyle = match($status) {
                                        'selesai' => 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;',
                                        'berjalan' => 'background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;',
                                        default => 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'
                                    };
                                ?>
                                    <!-- Activity Row -->
                                    <div class="row px-3 py-3 align-items-center mb-2" style="border-bottom: 1px solid #f1f5f9; border-radius: 8px; transition: background 0.15s ease;">
                                        
                                        <!-- Status / Kegiatan -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Status</span>
                                            <span class="badge text-xxs font-weight-bold px-2.5 py-1.5" style="<?php echo $badgeStyle; ?> border-radius: 12px; display: inline-block;">
                                                <?php echo ucfirst($status == 'berjalan' ? 'Diproses' : ($status == 'selesai' ? 'Selesai' : 'Dijadwalkan')); ?>
                                            </span>
                                            <div class="text-xs mt-1 font-weight-bold">
                                                <a href="view-kegiatan.php?kode_transaksi=<?php echo $rowLT['kode_transaksi']; ?>&id_sales=<?php echo $idT; ?>" class="text-primary text-xxs font-weight-bold">
                                                    <i class="fa-solid fa-hashtag me-0.5"></i><?php echo $rowLT['kode_transaksi']; ?>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Sales -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Sales</span>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs bg-gradient-light me-2 d-flex align-items-center justify-content-center" style="border-radius: 50%; width: 24px; height: 24px;">
                                                    <i class="fa-solid fa-user text-secondary text-xxs"></i>
                                                </div>
                                                <h6 class="text-dark font-weight-bold text-xs mb-0"><?php echo htmlspecialchars($rowLT['nama_sales']); ?></h6>
                                            </div>
                                        </div>

                                        <!-- Visits -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Visits</span>
                                            <div class="d-flex flex-column">
                                                <span class="text-xs text-dark font-weight-bold"><i class="fa-regular fa-calendar me-1 text-secondary"></i><?php echo $formattedDate; ?></span>
                                                <span class="text-xxs text-secondary font-weight-bold ms-3.5"><?php echo $formattedTime; ?></span>
                                            </div>
                                        </div>

                                        <!-- Mulai -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Mulai</span>
                                            <div class="d-flex flex-column">
                                                <?php if ($formattedDateMli !== '-') : ?>
                                                    <span class="text-xs text-dark font-weight-bold"><i class="fa-regular fa-clock me-1 text-secondary"></i><?php echo $formattedDateMli; ?></span>
                                                    <span class="text-xxs text-secondary font-weight-bold ms-3.5"><?php echo $formattedTimeMli; ?></span>
                                                <?php else : ?>
                                                    <span class="text-xs text-secondary">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Selesai -->
                                        <div class="col-6 col-md-2 mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Selesai</span>
                                            <div class="d-flex flex-column">
                                                <?php if ($formattedDateSls !== '-') : ?>
                                                    <span class="text-xs text-dark font-weight-bold"><i class="fa-regular fa-circle-check me-1 text-secondary"></i><?php echo $formattedDateSls; ?></span>
                                                    <span class="text-xxs text-secondary font-weight-bold ms-3.5"><?php echo $formattedTimeSls; ?></span>
                                                <?php else : ?>
                                                    <span class="text-xs text-secondary">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Rincian (Detail button) -->
                                        <div class="col-6 col-md-1 text-md-center mb-2 mb-md-0">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Rincian</span>
                                            <button class="btn btn-outline-primary btn-xs mb-0 px-2.5 py-1.5 font-weight-bold detailBtn" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#detailModal" data-id="<?php echo $idT; ?>" data-kode="<?php echo $kodeTransaksi; ?>">
                                                <i class="fa-solid fa-eye me-1"></i>Lihat
                                            </button>
                                        </div>

                                        <!-- Hasil Visits -->
                                        <div class="col-6 col-md-1 text-md-center">
                                            <span class="d-md-none text-xxs text-secondary d-block font-weight-bold">Hasil</span>
                                            <?php if ($status == 'selesai') : 
                                                $shortVisits = strlen($hslVisits) > 25 ? substr($hslVisits, 0, 25) . '...' : $hslVisits;
                                            ?>
                                                <div class="p-1 px-2 bg-light text-xxs text-dark border-radius-sm text-start d-inline-block font-weight-bold" style="max-width: 100%; border-radius: 6px;" title="<?php echo htmlspecialchars($hslVisits); ?>">
                                                    <?php echo nl2br(htmlspecialchars($shortVisits)); ?>
                                                </div>
                                            <?php else : ?>
                                                <span class="badge text-xxs font-weight-bold px-2 py-1" style="background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 12px; display: inline-block;">Belum Selesai</span>
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
            </div>
        </div>
    </div>
</div>

<!-- Modal Sync Google Sheets -->
<div class="modal fade" id="syncSheetsModal" tabindex="-1" aria-labelledby="syncSheetsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark" id="syncSheetsModalLabel">
                    <i class="fa-solid fa-file-excel text-success me-2"></i>Sync ke Google Sheets
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="syncSheetsForm">
                <div class="modal-body py-3">
                    <div class="alert alert-info text-white text-xs border-0" style="background: linear-gradient(135deg, #1d4ed8, #3b82f6); border-radius: 12px; line-height: 1.5;">
                        <i class="fa-solid fa-circle-info me-2 text-sm"></i>
                        Pastikan Anda telah membagikan Spreadsheet target sebagai <strong>Editor</strong> ke email service account berikut:<br>
                        <code class="text-white bg-dark px-2 py-1 mt-1 d-inline-block rounded-sm select-all" style="font-family: monospace; font-size: 11px; letter-spacing: 0.2px;">sheets-sync@loewix-sales.iam.gserviceaccount.com</code>
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label text-xs font-weight-bold text-secondary text-uppercase mb-1">ID Spreadsheet atau URL</label>
                        <input type="text" name="spreadsheet_id" id="sheetIdInput" class="form-control border p-2 text-sm" 
                               placeholder="Masukkan ID / Link Google Sheets" 
                               value="19OV073XNHmo7zACGOpYPyEcmodlZmEv4wzFq7Fg_uoU" style="border-radius: 8px;" required>
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label text-xs font-weight-bold text-secondary text-uppercase mb-1">Nama Sheet (Tab)</label>
                        <input type="text" name="sheet_name" id="sheetNameInput" class="form-control border p-2 text-sm" 
                               placeholder="Contoh: Sheet1" value="Sheet1" style="border-radius: 8px;" required>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <label class="form-label text-xs font-weight-bold text-secondary text-uppercase mb-1">Bulan</label>
                            <input type="text" class="form-control border p-2 text-sm bg-light" value="<?php echo date('F Y', strtotime($filterBulan . '-01')); ?>" style="border-radius: 8px;" readonly disabled>
                            <input type="hidden" name="bulan" value="<?php echo $filterBulan; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-xs font-weight-bold text-secondary text-uppercase mb-1">Sales Agent</label>
                            <?php
                            $selectedSalesName = "Semua Sales";
                            if ($filterSales > 0) {
                                foreach ($salesOptions as $opt) {
                                    if ($opt['id'] == $filterSales) {
                                        $selectedSalesName = $opt['nama'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            <input type="text" class="form-control border p-2 text-sm bg-light" value="<?php echo htmlspecialchars($selectedSalesName); ?>" style="border-radius: 8px;" readonly disabled>
                            <input type="hidden" name="id_sales" value="<?php echo $filterSales; ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-link text-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bg-gradient-success mb-0" id="btnDoSync" style="border-radius: 8px;">
                        <span id="syncSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Mulai Sync
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $("#syncSheetsForm").on('submit', function(e) {
        e.preventDefault();
        
        const btn = $("#btnDoSync");
        const spinner = $("#syncSpinner");
        
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        $.ajax({
            url: "proses-sync-sheets.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                
                if (response.status === 'success') {
                    alert(response.message);
                    $("#syncSheetsModal").modal('hide');
                } else {
                    alert("Gagal: " . response.message);
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                alert("Terjadi kesalahan koneksi server: " + error);
            }
        });
    });
});
</script>