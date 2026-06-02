<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Progress Kegiatan";
$currentPage = "Progress";
$role = $jabatan;

$limit = 30;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = $_GET['cari'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .table th, .table td { vertical-align: top !important; }
        .table .customer-info h6 { font-size: 1rem; color: #344767; }
        .table .technician-list .technician-item { border-bottom: 1px solid #f0f2f5; }
        .table .technician-list .technician-item:last-child { border-bottom: none; }
        .progress-checkboxes label { font-size: 11px; margin-left: 4px; font-weight: 600; cursor: pointer; }
        .progress-checkboxes input[type="checkbox"] { cursor: pointer; width: 14px; height: 14px; margin-top: 1px; }
        .info-doc-box { font-size: 11px; color: #5a5a5a; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; border: 1px solid #e9ecef; }
        .box-keterangan { background-color: #fff3cd; border: 1px solid #ffe69c; border-radius: 6px; padding: 8px 10px; font-size: 11px; margin-top: 8px; position: relative; color: #664d03; }
        .btn-edit-ket { position: absolute; top: 6px; right: 8px; cursor: pointer; color: #ffc107; font-size: 12px; }
        .btn-edit-ket:hover { color: #cc9a06; }
        @media (max-width: 767px) {
            .table-responsive { overflow-x: auto; }
            .col-doc-info { min-width: 250px; }
        }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; setlocale(LC_TIME, 'id_ID.utf8'); ?>
        <div class="container-fluid py-4">
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Progress Kegiatan</h5>
                                <form method="GET" action="" class="w-100 w-md-50">
                                    <div class="input-group">
                                        <input type="text" name="cari" class="form-control p-4" style="border-bottom:1px solid #adb5bd" placeholder="Cari berdasarkan nama/kode/keterangan..." value="<?= htmlspecialchars($search) ?>">
                                        <button class="btn btn-primary mb-0" type="submit"><i class="material-icons text-sm">search</i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 35%;">Customer & Kegiatan</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 25%;">Teknisi & Absensi</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 40%;">Progress & Dokumen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $search_query = "";
                                        $params = [];
                                        $types = "";

                                        if (!empty($search)) {
                                            $search_query = " AND (c.nama LIKE ? OR k.kode LIKE ? OR k.kegiatan LIKE ? OR k.keterangan LIKE ?)";
                                            $search_param = "%$search%";
                                            $params = [$search_param, $search_param, $search_param, $search_param];
                                            $types = "ssss";
                                        }

                                        $sql_count = "SELECT COUNT(*) as total FROM kegiatan k
                                                      INNER JOIN (SELECT MAX(id) as max_id FROM kegiatan GROUP BY kode) k2 ON k.id = k2.max_id
                                                      LEFT JOIN customer c ON k.customer_id = c.id
                                                      WHERE k.deleted_at IS NULL" . $search_query;
                                        
                                        $stmt_count = $conn->prepare($sql_count);
                                        if (!empty($search)) { $stmt_count->bind_param($types, ...$params); }
                                        $stmt_count->execute();
                                        $total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
                                        $total_pages = ceil($total_rows / $limit);
                                        $stmt_count->close();

                                        $sql_main = "SELECT k.*, c.nama AS nama_cust,
                                                     pk.is_so, pk.no_so, pk.tgl_keluar_so,
                                                     pk.is_sj, pk.no_sj, pk.tgl_keluar_sj,
                                                     pk.is_finish, pk.tgl_cek_finish, 
                                                     pk.keterangan_penangguhan,
                                                     (SELECT no_invoice FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as pkeg_no_invoice,
                                                     (SELECT tanggal FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as pkeg_tgl_invoice
                                                     FROM kegiatan k
                                                     INNER JOIN (
                                                         SELECT MAX(id) as max_id FROM kegiatan GROUP BY kode
                                                     ) k2 ON k.id = k2.max_id
                                                     LEFT JOIN customer c ON k.customer_id = c.id
                                                     LEFT JOIN progress_kegiatan pk ON k.kode = pk.kode
                                                     WHERE k.deleted_at IS NULL" . $search_query . "
                                                     ORDER BY k.created_at DESC LIMIT ?, ?";

                                        $params[] = $offset;
                                        $params[] = $limit;
                                        $types .= "ii";

                                        $stmt_main = $conn->prepare($sql_main);
                                        $stmt_main->bind_param($types, ...$params);
                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();

                                        if ($result_main->num_rows > 0) {
                                            while ($row = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row['kode'];
                                                $idC = $row['customer_id'];
                                                $invoice_no = strtolower(trim($row['invoice']));
                                        ?>
                                                <tr style="border-bottom:1px solid #adb5bd">
                                                    <td class="ps-4 py-3 customer-info text-wrap">
                                                        <div class="d-flex align-items-start gap-2 mb-1">
                                                            <span class="badge <?= (strtolower($row['kegiatan']) == 'survey') ? 'badge-warning' : 'badge-secondary'; ?> text-uppercase mt-1" style="font-size:9px !important;">
                                                                <?= htmlspecialchars($row['kegiatan']);?>
                                                            </span>
                                                            <a href="https://jadwal.id-giti.com/staff/view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                                <h6 class="font-weight-bold mb-0 text-primary" style="font-size:14px;"><?= htmlspecialchars($row['nama_cust']); ?></h6>
                                                            </a>
                                                        </div>
                                                        <p class="text-xs text-secondary mb-1">"<?= !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '-'; ?>"</p>
                                                    </td>

                                                    <td class="technician-list py-3 pe-2">
                                                        <?php
                                                        $sql_teknisi = "SELECT p.status, t.nama_teknisi,
                                                                        (SELECT MIN(waktu_mulai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_mulai_pertama,
                                                                        (SELECT MAX(waktu_selesai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_selesai_terakhir
                                                                    FROM pelaksanaan_kegiatan p
                                                                    JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                                                                    JOIN kegiatan k ON t.kegiatan_id = k.id
                                                                    WHERE p.kode = ? AND k.customer_id = ? AND p.deleted_at IS NULL
                                                                    GROUP BY p.teknisi_id";
                                                        
                                                        $stmt_teknisi = $conn->prepare($sql_teknisi);
                                                        $stmt_teknisi->bind_param("si", $kodeTransaksi, $idC);
                                                        $stmt_teknisi->execute();
                                                        $result_teknisi = $stmt_teknisi->get_result();

                                                        if($result_teknisi->num_rows > 0) {
                                                            while($row_teknisi = $result_teknisi->fetch_assoc()) {
                                                        ?>
                                                        <div class="d-flex flex-column py-1 technician-item">
                                                            <p class="text-sm font-weight-bold mb-0 text-dark" style="font-size:12px !important;"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></p>
                                                            <p class="text-xs text-secondary mb-0" style="font-size:10px !important;">
                                                                Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m/y H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?> | 
                                                                Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m/y H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?>
                                                            </p>
                                                        </div>
                                                        <?php
                                                            }
                                                        } else {
                                                            echo "<p class='text-xs text-danger mb-0'>Belum ada absensi teknisi.</p>";
                                                        }
                                                        $stmt_teknisi->close();
                                                        ?>
                                                    </td>

                                                    <td class="py-3 pe-4 col-doc-info">
                                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2 progress-checkboxes">
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($row['pkeg_no_invoice'])): ?>
                                                                    <i class="fas fa-check-circle text-success" style="font-size:14px;"></i>
                                                                    <span class="mb-0 text-dark" style="font-size:11px; font-weight:600; margin-left:4px;">Invoice</span>
                                                                <?php elseif ($invoice_no == 'no'): ?>
                                                                    <i class="fas fa-times-circle text-danger" style="font-size:14px;"></i>
                                                                    <span class="mb-0 text-dark" style="font-size:11px; font-weight:600; margin-left:4px;">Invoice</span>
                                                                <?php else: ?>
                                                                    <input type="checkbox" disabled>
                                                                    <label class="mb-0 text-dark">Invoice</label>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <input type="checkbox" class="chk-doc" data-kode="<?= $kodeTransaksi; ?>" data-type="so" id="so_<?= $kodeTransaksi; ?>" <?= $row['is_so'] == 1 ? 'checked' : ''; ?>>
                                                                <label class="mb-0 text-dark" for="so_<?= $kodeTransaksi; ?>">SO</label>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <input type="checkbox" class="chk-doc" data-kode="<?= $kodeTransaksi; ?>" data-type="sj" id="sj_<?= $kodeTransaksi; ?>" <?= $row['is_sj'] == 1 ? 'checked' : ''; ?>>
                                                                <label class="mb-0 text-dark" for="sj_<?= $kodeTransaksi; ?>">SJ</label>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <input type="checkbox" class="chk-finish" data-kode="<?= $kodeTransaksi; ?>" id="finish_<?= $kodeTransaksi; ?>" <?= $row['is_finish'] == 1 ? 'checked' : ''; ?>>
                                                                <label class="mb-0 text-dark" for="finish_<?= $kodeTransaksi; ?>">Finish</label>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex flex-column" id="wrapper_info_<?= $kodeTransaksi; ?>">
                                                            <?php if (!empty($row['pkeg_no_invoice'])): ?>
                                                                <div class="info-doc-box">
                                                                    <i class="fas fa-file-invoice-dollar text-success me-1"></i> <strong>Invoice:</strong> <?= htmlspecialchars($row['pkeg_no_invoice']); ?> (<?= $row['pkeg_tgl_invoice'] ? date("d/m/Y", strtotime($row['pkeg_tgl_invoice'])) : '-'; ?>)
                                                                </div>
                                                            <?php elseif ($invoice_no == 'no'): ?>
                                                                <div class="info-doc-box">
                                                                    <i class="fas fa-times text-danger me-1"></i> <strong>Invoice:</strong> Tanpa / Belum Ada Invoice
                                                                </div>
                                                            <?php endif; ?>

                                                            <div id="info_so_<?= $kodeTransaksi; ?>" class="info-doc-box" style="<?= $row['is_so'] == 1 ? '' : 'display:none;'; ?>">
                                                                <i class="fas fa-file-invoice text-success me-1"></i> <strong>SO:</strong> <span id="txt_no_so_<?= $kodeTransaksi; ?>"><?= htmlspecialchars($row['no_so'] ?? ''); ?></span> (<span id="txt_tgl_so_<?= $kodeTransaksi; ?>"><?= $row['tgl_keluar_so'] ? date("d/m/Y", strtotime($row['tgl_keluar_so'])) : ''; ?></span>)
                                                            </div>
                                                            <div id="info_sj_<?= $kodeTransaksi; ?>" class="info-doc-box" style="<?= $row['is_sj'] == 1 ? '' : 'display:none;'; ?>">
                                                                <i class="fas fa-truck text-info me-1"></i> <strong>SJ:</strong> <span id="txt_no_sj_<?= $kodeTransaksi; ?>"><?= htmlspecialchars($row['no_sj'] ?? ''); ?></span> (<span id="txt_tgl_sj_<?= $kodeTransaksi; ?>"><?= $row['tgl_keluar_sj'] ? date("d/m/Y", strtotime($row['tgl_keluar_sj'])) : ''; ?></span>)
                                                            </div>
                                                            <div id="info_finish_<?= $kodeTransaksi; ?>" class="info-doc-box" style="<?= $row['is_finish'] == 1 ? '' : 'display:none;'; ?>">
                                                                <i class="fas fa-check-circle text-primary me-1"></i> <strong>Selesai:</strong> <span id="txt_tgl_finish_<?= $kodeTransaksi; ?>"><?= $row['tgl_cek_finish'] ? date("d/m/Y H:i", strtotime($row['tgl_cek_finish'])) : ''; ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="box-keterangan mt-2" id="box_ket_<?= $kodeTransaksi; ?>" <?= empty($row['keterangan_penangguhan']) ? 'style="display:none;"' : ''; ?>>
                                                            <i class="fas fa-pencil-alt btn-edit-ket" onclick="openKetModal('<?= $kodeTransaksi; ?>', `<?= htmlspecialchars($row['keterangan_penangguhan'] ?? ''); ?>`)"></i>
                                                            <span class="font-weight-bold"><i class="fas fa-exclamation-circle me-1"></i> Penangguhan:</span><br>
                                                            <span id="text_ket_<?= $kodeTransaksi; ?>" style="line-height:1.2; display:block; margin-top:2px;"><?= nl2br(htmlspecialchars($row['keterangan_penangguhan'] ?? '')); ?></span>
                                                        </div>

                                                        <button class="btn btn-outline-warning btn-sm mt-2 mb-0 py-1 px-3" style="font-size:10px;" id="btn_add_ket_<?= $kodeTransaksi; ?>" onclick="openKetModal('<?= $kodeTransaksi; ?>', '')" <?= !empty($row['keterangan_penangguhan']) ? 'style="display:none;"' : ''; ?>>
                                                            + Tambah Keterangan
                                                        </button>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center py-5 text-secondary'>Tidak ada data ditemukan.</td></tr>";
                                        }
                                        $stmt_main->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <div class="card-footer px-3 border-0 d-flex justify-content-center justify-content-lg-center">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $page - 1; ?>&cari=<?= urlencode($search); ?>"><i class="fas fa-angle-left"></i> </a>
                                </li>
                                <?php
                                $adjacents = 2;
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    if ($i == 1 || $i == $total_pages || ($i >= $page - $adjacents && $i <= $page + $adjacents)) {
                                        $active = ($i == $page) ? 'active' : '';
                                        echo "<li class='page-item $active'><a class='page-link' href='?page=$i&cari=" . urlencode($search) . "'>$i</a></li>";
                                    } elseif ($i == $page - $adjacents - 1 || $i == $page + $adjacents + 1) {
                                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                                    }
                                }
                                ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $page + 1; ?>&cari=<?= urlencode($search); ?>"> <i class="fas fa-angle-right"></i></a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="docModalLabel">Input Data</h6>
                    <button type="button" class="btn-close action-cancel-doc" aria-label="Close" style="color:#000;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <form id="formDoc">
                        <input type="hidden" id="docKode" name="kode">
                        <input type="hidden" id="docType" name="action">
                        <div class="mb-3">
                            <label id="labelNoDoc" class="form-label" style="font-size:12px;">Nomor Dokumen</label>
                            <input type="text" class="form-control px-2 border" id="inputNoDoc" name="no_doc" required>
                        </div>
                        <div class="mb-3">
                            <label id="labelTglDoc" class="form-label" style="font-size:12px;">Tanggal Keluar</label>
                            <input type="date" class="form-control px-2 border" id="inputTglDoc" name="tgl_doc" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-0">Simpan & Centang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Keterangan Penangguhan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="color:#000;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <form id="formKet">
                        <input type="hidden" id="ketKode" name="kode">
                        <input type="hidden" name="action" value="update_keterangan">
                        <div class="mb-3">
                            <textarea class="form-control border p-2 text-sm" id="inputKet" name="keterangan" rows="4" placeholder="Tuliskan alasan atau keterangan..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 mb-0">Simpan Keterangan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
    $(document).ready(function() {
        let currentDocCheckbox = null;
        let currentType = null;
        let currentKode = null;

        function formatDateID(dateStr) {
            let d = new Date(dateStr);
            return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) + "/" + d.getFullYear();
        }

        function formatDateTimeID() {
            let d = new Date();
            return ("0" + d.getDate()).slice(-2) + "/" + ("0" + (d.getMonth() + 1)).slice(-2) + "/" + d.getFullYear() + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
        }

        $('.chk-doc').on('change', function(e) {
            e.preventDefault();
            let kode = $(this).data('kode');
            let type = $(this).data('type');
            let isChecked = $(this).is(':checked');
            let checkbox = $(this);

            if(isChecked) {
                checkbox.prop('checked', false); 
                currentDocCheckbox = checkbox;
                currentType = type;
                currentKode = kode;
                
                $('#docKode').val(kode);
                $('#docType').val('update_' + type);
                $('#labelNoDoc').text(type === 'so' ? 'Nomor SO' : 'Nomor SJ');
                $('#inputNoDoc').attr('name', type === 'so' ? 'no_so' : 'no_sj').val('');
                $('#labelTglDoc').text(type === 'so' ? 'Tanggal Keluar SO' : 'Tanggal Keluar SJ');
                $('#inputTglDoc').attr('name', type === 'so' ? 'tgl_keluar_so' : 'tgl_keluar_sj').val('');
                
                $('#docModalLabel').text('Input Data ' + type.toUpperCase());
                $('#docModal').modal('show');
            } else {
                if(confirm("Yakin ingin menghapus tanda dan data " + type.toUpperCase() + " ini?")) {
                    $.post('ajax-progress.php', { action: 'uncheck_doc', type: type, kode: kode }, function(res) {
                        try {
                            let resp = JSON.parse(res);
                            if(resp.status === 'success') {
                                $('#info_' + type + '_' + kode).hide();
                            } else {
                                alert("Gagal memperbarui database.");
                                checkbox.prop('checked', true);
                            }
                        } catch(e) {
                            checkbox.prop('checked', true);
                        }
                    });
                } else {
                    checkbox.prop('checked', true);
                }
            }
        });

        $('.action-cancel-doc').click(function(){
            $('#docModal').modal('hide');
        });

        $('#formDoc').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let inputNo = $('#inputNoDoc').val();
            let inputTgl = $('#inputTglDoc').val();

            $.ajax({
                url: 'ajax-progress.php',
                type: 'POST',
                data: formData,
                success: function(res) {
                    try {
                        let resp = JSON.parse(res);
                        if(resp.status === 'success') {
                            if(currentDocCheckbox) {
                                currentDocCheckbox.prop('checked', true);
                            }
                            $('#txt_no_' + currentType + '_' + currentKode).text(inputNo);
                            $('#txt_tgl_' + currentType + '_' + currentKode).text(formatDateID(inputTgl));
                            $('#info_' + currentType + '_' + currentKode).show();
                            
                            $('#docModal').modal('hide');
                        } else {
                            alert("Gagal menyimpan data.");
                        }
                    } catch(e) {
                        alert("Terjadi kesalahan sistem.");
                    }
                }
            });
        });

        $('.chk-finish').on('change', function() {
            let kode = $(this).data('kode');
            let isChecked = $(this).is(':checked') ? 1 : 0;
            let checkbox = $(this);
            
            $.post('ajax-progress.php', { action: 'update_finish', kode: kode, is_finish: isChecked }, function(res) {
                try {
                    let resp = JSON.parse(res);
                    if(resp.status === 'success') {
                        if(isChecked) {
                            $('#txt_tgl_finish_' + kode).text(formatDateTimeID());
                            $('#info_finish_' + kode).show();
                        } else {
                            $('#info_finish_' + kode).hide();
                        }
                    } else {
                        alert("Gagal mengupdate status finish.");
                        checkbox.prop('checked', !isChecked);
                    }
                } catch(e) {
                    checkbox.prop('checked', !isChecked);
                }
            });
        });

        $('#formKet').on('submit', function(e) {
            e.preventDefault();
            let kode = $('#ketKode').val();
            let ketValue = $('#inputKet').val();

            $.ajax({
                url: 'ajax-progress.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    try {
                        let resp = JSON.parse(res);
                        if(resp.status === 'success') {
                            $('#ketModal').modal('hide');
                            if(ketValue.trim() !== '') {
                                $('#text_ket_' + kode).html(ketValue.replace(/\n/g, "<br>"));
                                $('#box_ket_' + kode).show();
                                $('#btn_add_ket_' + kode).hide();
                            } else {
                                $('#box_ket_' + kode).hide();
                                $('#btn_add_ket_' + kode).show();
                            }
                        } else {
                            alert("Gagal menyimpan keterangan.");
                        }
                    } catch(e) {
                        alert("Terjadi kesalahan sistem.");
                    }
                }
            });
        });
    });

    function openKetModal(kode, currentText) {
        $('#ketKode').val(kode);
        $('#inputKet').val(currentText);
        $('#ketModal').modal('show');
    }
    </script>
</body>
</html>