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
        /* ═══ PREMIUM PROGRESS CARD ═══ */
        .progress-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden; display: flex; flex-direction: column;
            max-height: calc(100vh - 140px);
        }
        .progress-header { padding: 24px 24px 16px; flex-shrink: 0; }
        .progress-title-row {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
        }
        .progress-title-left { display: flex; align-items: center; gap: 14px; }
        .progress-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }
        .progress-icon i { color: #fff; font-size: 16px; }
        .progress-title-left h5 { margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; }
        .progress-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }

        .progress-search-form { display: flex; gap: 8px; align-items: center; flex: 1; max-width: 400px; }
        .progress-search-input {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px 9px 36px;
            font-size: 13px; color: #1e293b; background: #f8fafc; font-weight: 500;
            transition: all 0.2s; width: 100%;
        }
        .progress-search-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.08); outline: none; background: #fff; }
        .progress-search-wrap { position: relative; flex: 1; }
        .progress-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; }
        .progress-btn-cari {
            padding: 9px 20px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(245,158,11,0.25); white-space: nowrap;
        }
        .progress-btn-cari:hover { transform: translateY(-1px); }

        /* Table columns header */
        .progress-cols {
            display: grid; grid-template-columns: 35% 25% 40%;
            padding: 10px 24px; background: #f8fafc;
            border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;
            flex-shrink: 0;
        }
        .progress-col-label {
            font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em;
        }

        /* Scrollable rows */
        .progress-scroll { flex: 1; overflow-y: auto; }

        /* Row card */
        .progress-row {
            display: grid; grid-template-columns: 35% 25% 40%;
            padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .progress-row:hover { background: #fafbfc; }

        /* Customer col */
        .cust-badge {
            display: inline-block; font-size: 9px; font-weight: 700;
            padding: 2px 8px; border-radius: 4px; text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .cust-badge-survey { background: #fef3c7; color: #92400e; }
        .cust-badge-default { background: #f1f5f9; color: #64748b; }
        .cust-name { font-size: 14px; font-weight: 700; color: #2563eb; text-decoration: none; display: block; margin-top: 4px; }
        .cust-name:hover { text-decoration: underline; color: #1d4ed8; }
        .cust-desc { font-size: 12px; color: #94a3b8; font-style: italic; margin-top: 2px; line-height: 1.4; }

        /* Teknisi col */
        .tek-item { padding: 4px 0; }
        .tek-item + .tek-item { border-top: 1px solid #f1f5f9; }
        .tek-name { font-size: 12px; font-weight: 700; color: #1e293b; }
        .tek-time { font-size: 10px; color: #94a3b8; margin-top: 1px; }
        .tek-none { font-size: 12px; color: #ef4444; font-style: italic; }

        /* Progress col */
        .doc-checks {
            display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 8px;
        }
        .doc-check-item { display: flex; align-items: center; gap: 4px; }
        .doc-check-item input[type="checkbox"] { width: 15px; height: 15px; cursor: pointer; accent-color: #6366f1; }
        .doc-check-item label { font-size: 11px; font-weight: 600; color: #334155; margin: 0; cursor: pointer; }
        .doc-info-pill {
            font-size: 11px; color: #475569; background: #f1f5f9;
            padding: 4px 10px; border-radius: 8px; margin-bottom: 4px;
            border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 6px;
        }
        .doc-info-pill i { font-size: 12px; }
        .doc-info-pill.pill-invoice { border-color: #bbf7d0; background: #f0fdf4; }
        .doc-info-pill.pill-no-invoice { border-color: #fecaca; background: #fef2f2; }

        .ket-box {
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
            padding: 10px 12px; font-size: 11px; color: #78350f; position: relative;
            margin-top: 8px; line-height: 1.5;
        }
        .ket-edit-btn {
            position: absolute; top: 8px; right: 10px; cursor: pointer;
            color: #d97706; font-size: 12px; transition: color 0.2s;
        }
        .ket-edit-btn:hover { color: #b45309; }
        .btn-add-ket {
            margin-top: 8px; padding: 5px 14px; font-size: 10px; font-weight: 700;
            border: 1.5px solid #f59e0b; border-radius: 8px; background: #fff;
            color: #d97706; cursor: pointer; transition: all 0.2s;
        }
        .btn-add-ket:hover { background: #fef3c7; }

        .status-icon-ok { color: #22c55e; font-size: 14px; }
        .status-icon-no { color: #ef4444; font-size: 14px; }

        /* Footer / Pagination */
        .progress-footer { flex-shrink: 0; padding: 12px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; }
        .pg-list { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; }
        .pg-item a, .pg-item span {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; font-size: 12px; font-weight: 600;
            text-decoration: none; color: #64748b; background: #f8fafc; border: 1px solid #e5e7eb;
            transition: all 0.15s;
        }
        .pg-item a:hover { background: #f1f5f9; color: #1e293b; }
        .pg-item.active a { background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; border-color: transparent; }
        .pg-item.disabled a, .pg-item.disabled span { opacity: 0.4; pointer-events: none; }

        @media (max-width: 768px) {
            .progress-card { max-height: none; }
            .progress-cols { display: none; }
            .progress-row {
                display: flex; flex-direction: column; gap: 12px;
                padding: 16px; margin: 8px 12px; border-radius: 12px;
                border: 1px solid #e5e7eb; background: #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            }
            .progress-search-form { max-width: 100%; }
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
                    <div class="progress-card">
                        <!-- Header -->
                        <div class="progress-header">
                            <div class="progress-title-row">
                                <div class="progress-title-left">
                                    <div class="progress-icon">
                                        <i class="fa-solid fa-list-check"></i>
                                    </div>
                                    <div>
                                        <h5>Progress Kegiatan</h5>
                                        <p>Tracking progress dokumen & absensi</p>
                                    </div>
                                </div>
                                <form method="GET" action="" class="progress-search-form">
                                    <div class="progress-search-wrap">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <input type="text" name="cari" class="progress-search-input" placeholder="Cari nama/kode/keterangan..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                    <button type="submit" class="progress-btn-cari">
                                        <i class="fa-solid fa-magnifying-glass"></i> Cari
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Column headers -->
                        <div class="progress-cols">
                            <span class="progress-col-label">Customer & Kegiatan</span>
                            <span class="progress-col-label">Teknisi & Absensi</span>
                            <span class="progress-col-label">Progress & Dokumen</span>
                        </div>

                        <!-- Scrollable rows -->
                        <div class="progress-scroll">
                        <?php
                        // === COUNT ===
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

                        // === MAIN QUERY ===
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

                        // Collect all rows + kode list for batch teknisi fetch
                        $allRows = [];
                        $kodeList = [];
                        $custIdList = [];
                        while ($row = $result_main->fetch_assoc()) {
                            $allRows[] = $row;
                            $kodeList[] = $row['kode'];
                            $custIdList[] = $row['customer_id'];
                        }
                        $stmt_main->close();

                        // === BATCH TEKNISI FETCH (eliminates N+1) ===
                        $teknisiMap = [];
                        if (!empty($kodeList)) {
                            $placeholders = implode(',', array_fill(0, count($kodeList), '?'));
                            $sql_tek = "SELECT p.kode, p.teknisi_id, t.nama_teknisi,
                                        MIN(p.waktu_mulai) AS waktu_mulai_pertama,
                                        MAX(p.waktu_selesai) AS waktu_selesai_terakhir
                                        FROM pelaksanaan_kegiatan p
                                        JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                                        JOIN kegiatan k ON t.kegiatan_id = k.id AND k.customer_id IN (" . implode(',', array_map('intval', $custIdList)) . ")
                                        WHERE p.kode IN ($placeholders) AND p.deleted_at IS NULL
                                        GROUP BY p.kode, p.teknisi_id";
                            
                            $stmt_tek = $conn->prepare($sql_tek);
                            $tek_types = str_repeat('s', count($kodeList));
                            $stmt_tek->bind_param($tek_types, ...$kodeList);
                            $stmt_tek->execute();
                            $res_tek = $stmt_tek->get_result();
                            while ($trow = $res_tek->fetch_assoc()) {
                                $teknisiMap[$trow['kode']][] = $trow;
                            }
                            $stmt_tek->close();
                        }

                        if (count($allRows) > 0) {
                            foreach ($allRows as $row) {
                                $kodeTransaksi = $row['kode'];
                                $idC = $row['customer_id'];
                                $invoice_no = strtolower(trim($row['invoice']));
                                $tekList = $teknisiMap[$kodeTransaksi] ?? [];
                        ?>
                            <div class="progress-row">
                                <!-- Col 1: Customer -->
                                <div>
                                    <span class="cust-badge <?= (strtolower($row['kegiatan']) == 'survey') ? 'cust-badge-survey' : 'cust-badge-default'; ?>">
                                        <?= htmlspecialchars($row['kegiatan']); ?>
                                    </span>
                                    <a href="https://jadwal.id-giti.com/staff/view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank" class="cust-name">
                                        <?= htmlspecialchars($row['nama_cust']); ?>
                                    </a>
                                    <?php if (!empty($row['keterangan'])): ?>
                                        <div class="cust-desc">"<?= htmlspecialchars($row['keterangan']); ?>"</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Col 2: Teknisi -->
                                <div>
                                    <?php if (!empty($tekList)): ?>
                                        <?php foreach ($tekList as $tek): ?>
                                        <div class="tek-item">
                                            <div class="tek-name"><?= htmlspecialchars($tek['nama_teknisi']); ?></div>
                                            <div class="tek-time">
                                                Mulai: <?= $tek['waktu_mulai_pertama'] ? date("d/m/y H:i", strtotime($tek['waktu_mulai_pertama'])) : '-'; ?> |
                                                Selesai: <?= $tek['waktu_selesai_terakhir'] ? date("d/m/y H:i", strtotime($tek['waktu_selesai_terakhir'])) : '-'; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="tek-none">Belum ada absensi teknisi.</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Col 3: Progress & Dokumen -->
                                <div>
                                    <div class="doc-checks">
                                        <div class="doc-check-item">
                                            <?php if (!empty($row['pkeg_no_invoice'])): ?>
                                                <i class="fas fa-check-circle status-icon-ok"></i>
                                                <label>Invoice</label>
                                            <?php elseif ($invoice_no == 'no'): ?>
                                                <i class="fas fa-times-circle status-icon-no"></i>
                                                <label>Invoice</label>
                                            <?php else: ?>
                                                <input type="checkbox" disabled>
                                                <label>Invoice</label>
                                            <?php endif; ?>
                                        </div>
                                        <div class="doc-check-item">
                                            <input type="checkbox" class="chk-doc" data-kode="<?= $kodeTransaksi; ?>" data-type="so" id="so_<?= $kodeTransaksi; ?>" <?= $row['is_so'] == 1 ? 'checked' : ''; ?>>
                                            <label for="so_<?= $kodeTransaksi; ?>">SO</label>
                                        </div>
                                        <div class="doc-check-item">
                                            <input type="checkbox" class="chk-doc" data-kode="<?= $kodeTransaksi; ?>" data-type="sj" id="sj_<?= $kodeTransaksi; ?>" <?= $row['is_sj'] == 1 ? 'checked' : ''; ?>>
                                            <label for="sj_<?= $kodeTransaksi; ?>">SJ</label>
                                        </div>
                                        <div class="doc-check-item">
                                            <input type="checkbox" class="chk-finish" data-kode="<?= $kodeTransaksi; ?>" id="finish_<?= $kodeTransaksi; ?>" <?= $row['is_finish'] == 1 ? 'checked' : ''; ?>>
                                            <label for="finish_<?= $kodeTransaksi; ?>">Finish</label>
                                        </div>
                                    </div>

                                    <div id="wrapper_info_<?= $kodeTransaksi; ?>">
                                        <?php if (!empty($row['pkeg_no_invoice'])): ?>
                                            <div class="doc-info-pill pill-invoice">
                                                <i class="fas fa-file-invoice-dollar text-success"></i>
                                                <span><strong>Invoice:</strong> <?= htmlspecialchars($row['pkeg_no_invoice']); ?> (<?= $row['pkeg_tgl_invoice'] ? date("d/m/Y", strtotime($row['pkeg_tgl_invoice'])) : '-'; ?>)</span>
                                            </div>
                                        <?php elseif ($invoice_no == 'no'): ?>
                                            <div class="doc-info-pill pill-no-invoice">
                                                <i class="fas fa-times text-danger"></i>
                                                <span><strong>Invoice:</strong> Tanpa / Belum Ada Invoice</span>
                                            </div>
                                        <?php endif; ?>

                                        <div id="info_so_<?= $kodeTransaksi; ?>" class="doc-info-pill" style="<?= $row['is_so'] == 1 ? '' : 'display:none;'; ?>">
                                            <i class="fas fa-file-invoice text-success"></i>
                                            <span><strong>SO:</strong> <span id="txt_no_so_<?= $kodeTransaksi; ?>"><?= htmlspecialchars($row['no_so'] ?? ''); ?></span> (<span id="txt_tgl_so_<?= $kodeTransaksi; ?>"><?= $row['tgl_keluar_so'] ? date("d/m/Y", strtotime($row['tgl_keluar_so'])) : ''; ?></span>)</span>
                                        </div>
                                        <div id="info_sj_<?= $kodeTransaksi; ?>" class="doc-info-pill" style="<?= $row['is_sj'] == 1 ? '' : 'display:none;'; ?>">
                                            <i class="fas fa-truck text-info"></i>
                                            <span><strong>SJ:</strong> <span id="txt_no_sj_<?= $kodeTransaksi; ?>"><?= htmlspecialchars($row['no_sj'] ?? ''); ?></span> (<span id="txt_tgl_sj_<?= $kodeTransaksi; ?>"><?= $row['tgl_keluar_sj'] ? date("d/m/Y", strtotime($row['tgl_keluar_sj'])) : ''; ?></span>)</span>
                                        </div>
                                        <div id="info_finish_<?= $kodeTransaksi; ?>" class="doc-info-pill" style="<?= $row['is_finish'] == 1 ? '' : 'display:none;'; ?>">
                                            <i class="fas fa-check-circle text-primary"></i>
                                            <span><strong>Selesai:</strong> <span id="txt_tgl_finish_<?= $kodeTransaksi; ?>"><?= $row['tgl_cek_finish'] ? date("d/m/Y H:i", strtotime($row['tgl_cek_finish'])) : ''; ?></span></span>
                                        </div>
                                    </div>

                                    <div class="ket-box" id="box_ket_<?= $kodeTransaksi; ?>" <?= empty($row['keterangan_penangguhan']) ? 'style="display:none;"' : ''; ?>>
                                        <i class="fas fa-pencil-alt ket-edit-btn" onclick="openKetModal('<?= $kodeTransaksi; ?>', `<?= htmlspecialchars($row['keterangan_penangguhan'] ?? ''); ?>`)"></i>
                                        <strong><i class="fas fa-exclamation-circle me-1"></i> Penangguhan:</strong><br>
                                        <span id="text_ket_<?= $kodeTransaksi; ?>" style="display:block; margin-top:2px;"><?= nl2br(htmlspecialchars($row['keterangan_penangguhan'] ?? '')); ?></span>
                                    </div>

                                    <button class="btn-add-ket" id="btn_add_ket_<?= $kodeTransaksi; ?>" onclick="openKetModal('<?= $kodeTransaksi; ?>', '')" <?= !empty($row['keterangan_penangguhan']) ? 'style="display:none;"' : ''; ?>>
                                        + Tambah Keterangan
                                    </button>
                                </div>
                            </div>
                        <?php
                            }
                        } else {
                            echo '<div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                                    <i class="fa-solid fa-inbox" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                                    Tidak ada data ditemukan.
                                  </div>';
                        }
                        ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="progress-footer">
                            <ul class="pg-list">
                                <li class="pg-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a href="?page=<?= $page - 1; ?>&cari=<?= urlencode($search); ?>"><i class="fas fa-angle-left"></i></a>
                                </li>
                                <?php
                                $adjacents = 2;
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    if ($i == 1 || $i == $total_pages || ($i >= $page - $adjacents && $i <= $page + $adjacents)) {
                                        $active = ($i == $page) ? 'active' : '';
                                        echo "<li class='pg-item $active'><a href='?page=$i&cari=" . urlencode($search) . "'>$i</a></li>";
                                    } elseif ($i == $page - $adjacents - 1 || $i == $page + $adjacents + 1) {
                                        echo "<li class='pg-item disabled'><span>...</span></li>";
                                    }
                                }
                                ?>
                                <li class="pg-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a href="?page=<?= $page + 1; ?>&cari=<?= urlencode($search); ?>"><i class="fas fa-angle-right"></i></a>
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

    <!-- Modal: Input SO/SJ -->
    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h6 class="modal-title" id="docModalLabel" style="font-weight:700; font-size:14px;">Input Data</h6>
                    <button type="button" class="btn-close action-cancel-doc" aria-label="Close" style="font-size:10px;"></button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <form id="formDoc">
                        <input type="hidden" id="docKode" name="kode">
                        <input type="hidden" id="docType" name="action">
                        <div class="mb-3">
                            <label id="labelNoDoc" class="form-label" style="font-size:12px; font-weight:600;">Nomor Dokumen</label>
                            <input type="text" class="form-control px-2 border" id="inputNoDoc" name="no_doc" required style="border-radius:8px;">
                        </div>
                        <div class="mb-3">
                            <label id="labelTglDoc" class="form-label" style="font-size:12px; font-weight:600;">Tanggal Keluar</label>
                            <input type="date" class="form-control px-2 border" id="inputTglDoc" name="tgl_doc" required style="border-radius:8px;">
                        </div>
                        <button type="submit" class="btn w-100 mb-0" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; border-radius:10px; font-weight:700; font-size:13px; padding:10px;">Simpan & Centang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Keterangan -->
    <div class="modal fade" id="ketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h6 class="modal-title" style="font-weight:700; font-size:14px;">Keterangan Penangguhan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size:10px;"></button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <form id="formKet">
                        <input type="hidden" id="ketKode" name="kode">
                        <input type="hidden" name="action" value="update_keterangan">
                        <div class="mb-3">
                            <textarea class="form-control border p-2 text-sm" id="inputKet" name="keterangan" rows="4" placeholder="Tuliskan alasan atau keterangan..." required style="border-radius:8px;"></textarea>
                        </div>
                        <button type="submit" class="btn w-100 mb-0" style="background:linear-gradient(135deg,#f59e0b,#ef4444); color:#fff; border:none; border-radius:10px; font-weight:700; font-size:13px; padding:10px;">Simpan Keterangan</button>
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