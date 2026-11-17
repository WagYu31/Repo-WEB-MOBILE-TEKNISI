<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
include "bast_init.php";

$pageNow = "BAST";
$role = $jabatan;

$activeTab = $_GET['tab'] ?? 'daftar';
$filterStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['cari'] ?? '');

// KPI Counts
$resCustWajib = mysqli_query($conn, "SELECT COUNT(*) AS tot FROM customer WHERE deleted_at IS NULL AND wajib_bast = 1");
$totalCustomerWajib = intval(($resCustWajib ? mysqli_fetch_assoc($resCustWajib)['tot'] : 0));

// Fetch BAST list (completed/active tasks for Wajib-BAST customers + any task that already has bast_laporan)
$sqlBastList = "SELECT k.id, k.kode, k.kegiatan, k.keterangan, k.jadwal, k.status AS status_kegiatan, k.created_at,
                       c.id AS customer_id, c.nama AS nama_cust, c.alamat AS alamat_cust, COALESCE(c.wajib_bast, 0) AS wajib_bast,
                       b.id AS bast_id, b.no_bast, b.foto_display_utama, b.ket_display_utama, b.status_bast, b.updated_at AS bast_updated_at,
                       t_sub.nama AS teknisi_penginput,
                       (SELECT COUNT(*) FROM bast_titik_kamera bt WHERE bt.bast_id = b.id) AS jumlah_titik
                FROM kegiatan k
                INNER JOIN (
                    SELECT kode, MAX(id) AS max_id
                    FROM kegiatan
                    WHERE deleted_at IS NULL
                    GROUP BY kode
                ) latest ON k.id = latest.max_id
                LEFT JOIN customer c ON k.customer_id = c.id
                LEFT JOIN bast_laporan b ON b.kode = k.kode
                LEFT JOIN teknisi t_sub ON b.teknisi_id = t_sub.id
                WHERE k.deleted_at IS NULL
                  AND k.status != 'dibatalkan'
                  AND (c.wajib_bast = 1 OR b.id IS NOT NULL)";

$bindTypes = '';
$bindVals = [];
if ($search !== '' && $activeTab === 'daftar') {
    $sqlBastList .= " AND (c.nama LIKE ? OR k.kode LIKE ? OR b.no_bast LIKE ?)";
    $bindTypes .= 'sss';
    $sp = "%$search%";
    $bindVals[] = $sp;
    $bindVals[] = $sp;
    $bindVals[] = $sp;
}
$sqlBastList .= " ORDER BY COALESCE(b.updated_at, k.created_at) DESC LIMIT 200";

$stmtList = $conn->prepare($sqlBastList);
if ($stmtList && $bindTypes !== '') {
    $stmtList->bind_param($bindTypes, ...$bindVals);
}
$bastRows = [];
$cntSudah = 0;
$cntBelum = 0;
if ($stmtList) {
    $stmtList->execute();
    $resL = $stmtList->get_result();
    while ($r = $resL->fetch_assoc()) {
        $hasInput = !empty($r['bast_id']) && !empty($r['foto_display_utama']);
        if ($hasInput) $cntSudah++;
        else $cntBelum++;

        if ($filterStatus === 'sudah' && !$hasInput) continue;
        if ($filterStatus === 'belum' && $hasInput) continue;
        $bastRows[] = $r;
    }
    $stmtList->close();
}

// Fetch Customers for Tab 2 (Ceklis Customer Wajib BAST)
$customersAll = [];
$resCustAll = mysqli_query($conn, "SELECT id, nama, alamat, telp, COALESCE(wajib_bast, 0) AS wajib_bast FROM customer WHERE deleted_at IS NULL ORDER BY wajib_bast DESC, nama ASC");
if ($resCustAll) {
    while ($rc = mysqli_fetch_assoc($resCustAll)) {
        $customersAll[] = $rc;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .bast-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
        .bast-kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(15,23,42,0.03); }
        .bast-kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; flex-shrink: 0; }
        .bast-kpi-num { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .bast-kpi-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

        .bast-tabs { display: flex; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 18px; box-shadow: 0 1px 4px rgba(0,0,0,0.03); }
        .bast-tab-btn { flex: 1; padding: 14px 16px; text-align: center; font-size: 12.5px; font-weight: 700; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
        .bast-tab-btn:hover { background: #f8fafc; color: #0f172a; }
        .bast-tab-btn.active { color: #ea580c; background: #fff7ed; border-bottom-color: #ff5b22; }
        .bast-tab-pill { font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 12px; background: #f1f5f9; color: #334155; }
        .bast-tab-btn.active .bast-tab-pill { background: #ff5b22; color: #fff; }

        .bast-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(15,23,42,0.03); }
        .bast-item-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 16px; }
        .bast-card { border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 16px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; }
        .bast-card:hover { border-color: #cbd5e1; box-shadow: 0 8px 24px rgba(15,23,42,0.06); transform: translateY(-2px); }
        .bast-badge { font-size: 10.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }
        .bast-badge.ok { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .bast-badge.wait { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .bast-badge.kode { background: #eef2ff; color: #4338ca; font-family: monospace; font-weight: 800; }

        .bast-thumb-strip { display: flex; gap: 8px; margin-top: 12px; align-items: center; flex-wrap: wrap; }
        .bast-thumb { width: 64px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; cursor: pointer; }

        .bast-btn { padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1.5px solid transparent; cursor: pointer; transition: all 0.18s; }
        .bast-btn-edit { background: linear-gradient(135deg, #ff5b22, #ff7a00); color: #fff; box-shadow: 0 4px 12px rgba(255,91,34,0.22); }
        .bast-btn-edit:hover { color: #fff; opacity: 0.94; transform: translateY(-1px); }
        .bast-btn-word { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .bast-btn-word:hover { background: #1d4ed8; color: #fff; }

        .cust-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border: 1px solid #f1f5f9; border-radius: 12px; margin-bottom: 8px; transition: background 0.15s, border-color 0.15s; }
        .cust-row:hover { background: #f8fafc; border-color: #e2e8f0; }
        .cust-row.is-wajib { background: #fffaf7; border-color: #fed7aa; }

        .toast-bast { position: fixed; bottom: 24px; right: 24px; z-index: 99999; background: #0f172a; color: #fff; padding: 12px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        @media (max-width: 992px) {
            .bast-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .bast-item-grid { grid-template-columns: 1fr; }
            .bast-tabs { flex-direction: column; }
        }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">

            <!-- KPI Summary -->
            <div class="bast-kpi-grid">
                <div class="bast-kpi-card">
                    <div class="bast-kpi-icon" style="background:linear-gradient(135deg,#ff5b22,#ff7a00);"><i class="fa-solid fa-file-signature"></i></div>
                    <div>
                        <div class="bast-kpi-num"><?= $cntSudah + $cntBelum ?></div>
                        <div class="bast-kpi-lbl">Total Kegiatan BAST</div>
                    </div>
                </div>
                <div class="bast-kpi-card">
                    <div class="bast-kpi-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fa-solid fa-camera-retro"></i></div>
                    <div>
                        <div class="bast-kpi-num"><?= $cntSudah ?></div>
                        <div class="bast-kpi-lbl">Sudah Input Teknisi</div>
                    </div>
                </div>
                <div class="bast-kpi-card">
                    <div class="bast-kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="bast-kpi-num"><?= $cntBelum ?></div>
                        <div class="bast-kpi-lbl">Menunggu Input BAST</div>
                    </div>
                </div>
                <div class="bast-kpi-card">
                    <div class="bast-kpi-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1);"><i class="fa-solid fa-building-circle-check"></i></div>
                    <div>
                        <div class="bast-kpi-num" id="kpiTotalCustWajib"><?= $totalCustomerWajib ?></div>
                        <div class="bast-kpi-lbl">Customer Wajib BAST</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bast-tabs">
                <a href="bast.php?tab=daftar" class="bast-tab-btn <?= $activeTab === 'daftar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-word"></i> Daftar & Edit Dokumen BAST
                    <span class="bast-tab-pill"><?= $cntSudah + $cntBelum ?></span>
                </a>
                <a href="bast.php?tab=customer" class="bast-tab-btn <?= $activeTab === 'customer' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i> Ceklis Customer Wajib BAST
                    <span class="bast-tab-pill" id="tabCustWajibCount"><?= $totalCustomerWajib ?></span>
                </a>
                <a href="bast.php?tab=template" class="bast-tab-btn <?= $activeTab === 'template' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-arrow-up"></i> Template Word (.docx)
                </a>
            </div>

            <?php if ($activeTab === 'daftar'): ?>
            <!-- TAB 1: DAFTAR DOKUMEN BAST -->
            <div class="bast-panel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 style="margin:0;font-size:16px;font-weight:800;color:#0f172a;">Dokumen Berita Acara Serah Terima (BAST)</h5>
                        <p style="margin:2px 0 0;font-size:12px;color:#64748b;">Klik <strong>Edit &amp; Preview BAST</strong> untuk mengedit isi dokumen sebelum dicetak atau diunduh ke Word (<code>.docx</code>).</p>
                    </div>
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="hidden" name="tab" value="daftar">
                        <select name="status" onchange="this.form.submit()" style="border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 12px;font-size:12.5px;font-weight:600;color:#1e293b;background:#f8fafc;">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="sudah" <?= $filterStatus === 'sudah' ? 'selected' : '' ?>>✅ Sudah Input Teknisi</option>
                            <option value="belum" <?= $filterStatus === 'belum' ? 'selected' : '' ?>>⏳ Menunggu Input Teknisi</option>
                        </select>
                        <input type="text" name="cari" value="<?= htmlspecialchars($search) ?>" placeholder="Cari customer / kode..." style="border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 14px;font-size:12.5px;">
                        <button type="submit" class="bast-btn" style="background:#0f172a;color:#fff;">Cari</button>
                    </form>
                </div>

                <?php if (empty($bastRows)): ?>
                    <div style="text-align:center;padding:56px 20px;">
                        <i class="fa-solid fa-folder-open" style="font-size:44px;color:#cbd5e1;margin-bottom:12px;display:block;"></i>
                        <div style="font-size:15px;font-weight:700;color:#475569;">Belum ada data kegiatan BAST</div>
                        <div style="font-size:12.5px;color:#94a3b8;margin-top:4px;">Tandai pelanggan di tab <strong>Ceklis Customer Wajib BAST</strong> atau tunggu teknisi menginput BAST dari aplikasi.</div>
                    </div>
                <?php else: ?>
                    <div class="bast-item-grid">
                        <?php foreach ($bastRows as $row):
                            $hasBast = !empty($row['bast_id']) && !empty($row['foto_display_utama']);
                            $jmlTitik = intval($row['jumlah_titik']);
                        ?>
                        <div class="bast-card">
                            <div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bast-badge kode">#<?= htmlspecialchars($row['kode']) ?></span>
                                        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;"><?= htmlspecialchars($row['kegiatan']) ?></span>
                                    </div>
                                    <?php if ($hasBast): ?>
                                        <span class="bast-badge ok"><i class="fa-solid fa-circle-check"></i> Sudah Input (1 Display + <?= $jmlTitik ?> Titik)</span>
                                    <?php else: ?>
                                        <span class="bast-badge wait"><i class="fa-regular fa-clock"></i> Menunggu Input Teknisi</span>
                                    <?php endif; ?>
                                </div>

                                <div style="font-size:15.5px;font-weight:800;color:#0f172a;"><?= htmlspecialchars($row['nama_cust'] ?: '-') ?></div>
                                <div style="font-size:12px;color:#64748b;margin-top:2px;">"<?= htmlspecialchars($row['keterangan'] ?: 'Tidak ada catatan kegiatan') ?>"</div>

                                <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:#64748b;margin-top:8px;">
                                    <span><i class="fa-regular fa-calendar"></i> Jadwal: <?= !empty($row['jadwal']) ? date('d M Y', strtotime($row['jadwal'])) : '-' ?></span>
                                    <?php if (!empty($row['no_bast'])): ?>
                                        <span><i class="fa-solid fa-hashtag" style="color:#ff5b22;"></i> <strong><?= htmlspecialchars($row['no_bast']) ?></strong></span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['teknisi_penginput'])): ?>
                                        <span><i class="fa-solid fa-user-gear" style="color:#059669;"></i> <?= htmlspecialchars($row['teknisi_penginput']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($hasBast): ?>
                                <div class="bast-thumb-strip">
                                    <img src="uploads/bast/<?= htmlspecialchars($row['foto_display_utama']) ?>" class="bast-thumb" title="Display Keseluruhan: <?= htmlspecialchars($row['ket_display_utama'] ?? '') ?>" onclick="window.open(this.src, '_blank')">
                                    <div style="font-size:11.5px;color:#334155;flex:1;min-width:140px;">
                                        <div style="font-weight:700;">Display Utama:</div>
                                        <div style="color:#64748b;"><?= htmlspecialchars($row['ket_display_utama'] ?: 'Display CCTV Keseluruhan') ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid #f1f5f9;">
                                <a href="bast-editor.php?kode=<?= urlencode($row['kode']) ?>" class="bast-btn bast-btn-edit" style="flex:1.4;">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit &amp; Preview BAST
                                </a>
                                <a href="bast-export-docx.php?kode=<?= urlencode($row['kode']) ?>" class="bast-btn bast-btn-word" style="flex:1;">
                                    <i class="fa-solid fa-file-word"></i> Unduh .docx
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php elseif ($activeTab === 'customer'): ?>
            <!-- TAB 2: CEKLIS CUSTOMER WAJIB BAST -->
            <div class="bast-panel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 style="margin:0;font-size:16px;font-weight:800;color:#0f172a;">Ceklis Customer Wajib BAST</h5>
                        <p style="margin:2px 0 0;font-size:12px;color:#64748b;">Centang customer yang memerlukan dokumen BAST. Teknisi otomatis mendapat pengingat <strong>Wajib BAST</strong> setelah pekerjaan selesai (bisa diinput dari rumah).</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="bast-btn cust-filter-pill" data-f="all" onclick="filterCustBast('all', this)" style="background:#0f172a;color:#fff;">Semua (<?= count($customersAll) ?>)</button>
                        <button type="button" class="bast-btn cust-filter-pill" data-f="wajib" onclick="filterCustBast('wajib', this)" style="background:#fff7ed;color:#ea580c;border-color:#fed7aa;">Wajib BAST Saja</button>
                    </div>
                </div>

                <div style="position:relative;margin-bottom:14px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    <input type="text" id="searchCustBast" oninput="filterCustBast(currentCustFilter)" placeholder="Ketik cepat nama customer (misal: YAMAHA, MULIA BAJA...)" style="width:100%;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px 10px 38px;font-size:13px;font-weight:600;color:#0f172a;background:#f8fafc;">
                </div>

                <div id="custBastList" style="max-height:62vh;overflow-y:auto;padding-right:4px;">
                    <?php foreach ($customersAll as $c):
                        $isW = intval($c['wajib_bast']) === 1;
                        $searchKey = strtolower(($c['nama'] ?? '') . ' ' . ($c['alamat'] ?? ''));
                    ?>
                    <label class="cust-row <?= $isW ? 'is-wajib' : '' ?>" data-search="<?= htmlspecialchars($searchKey, ENT_QUOTES) ?>" data-wajib="<?= $isW ? '1' : '0' ?>" style="cursor:pointer;">
                        <div style="min-width:0;padding-right:12px;">
                            <div style="font-size:13.5px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span><?= htmlspecialchars($c['nama']) ?></span>
                                <span class="wajib-tag" style="display:<?= $isW ? 'inline-block' : 'none' ?>;background:#ff5b22;color:#fff;font-size:9.5px;font-weight:800;padding:2px 8px;border-radius:10px;">WAJIB BAST</span>
                            </div>
                            <?php if (!empty($c['alamat'])): ?>
                                <div style="font-size:11.5px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:680px;"><?= htmlspecialchars($c['alamat']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" <?= $isW ? 'checked' : '' ?> onchange="toggleCustomerBast(<?= intval($c['id']) ?>, this)" style="width:44px;height:22px;cursor:pointer;">
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- TAB 3: TEMPLATE WORD (.DOCX) -->
            <div class="bast-panel">
                <h5 style="margin:0;font-size:16px;font-weight:800;color:#0f172a;">Pengaturan Template Dasar Word (<code>.docx</code>)</h5>
                <p style="margin:4px 0 18px;font-size:12.5px;color:#64748b;">Saat ini sistem menggunakan format kop surat &amp; struktur dari <strong>NEW TEMPLATE BAST YIMM.docx</strong>. Ketika template BAST terbaru sudah selesai direview bersama Bapak, Anda bisa mengunggah file <code>.docx</code> terbarunya di sini kapan saja.</p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <div style="border:1.5px solid #e2e8f0;border-radius:14px;padding:18px;background:#f8fafc;">
                        <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Kop Surat &amp; Template Aktif</div>
                        <?php if (file_exists(__DIR__ . '/assets/templates/bast_kop_header.png')): ?>
                            <div style="background:#fff;padding:14px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:12px;text-align:center;">
                                <img src="assets/templates/bast_kop_header.png?t=<?= time() ?>" style="max-height:54px;max-width:100%;object-fit:contain;" alt="Kop Surat">
                            </div>
                        <?php endif; ?>
                        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:12px;">
                            <i class="fa-solid fa-file-word" style="color:#2563eb;"></i>
                            <?= file_exists(__DIR__ . '/assets/templates/CUSTOM_TEMPLATE_BAST.docx') ? 'CUSTOM_TEMPLATE_BAST.docx (Terbaru)' : 'NEW_TEMPLATE_BAST_YIMM.docx (Default)' ?>
                        </div>
                        <?php if (file_exists(bastActiveTemplatePath())): ?>
                            <a href="assets/templates/<?= basename(bastActiveTemplatePath()) ?>" download class="bast-btn bast-btn-word">
                                <i class="fa-solid fa-download"></i> Unduh File Master Template (.docx)
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="border:1.5px dashed #cbd5e1;border-radius:14px;padding:18px;background:#fff;">
                        <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Upload Template BAST Terbaru (.docx)</div>
                        <p style="font-size:12px;color:#64748b;margin-bottom:14px;">Unggah file Microsoft Word (<code>.docx</code>) baru setelah format final disetujui.</p>
                        <form id="formUploadTemplate" onsubmit="uploadNewTemplate(event)">
                            <input type="file" name="template_docx" accept=".docx" required style="display:block;width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:9px;font-size:12.5px;margin-bottom:12px;">
                            <button type="submit" class="bast-btn bast-btn-edit" id="btnUploadTpl">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Pasang Template .docx Baru
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php include "footer.php"; ?>
        </div>
    </main>

    <div id="bastToast" class="toast-bast"></div>

    <?php include "js-include.php"; ?>
    <script>
    let currentCustFilter = 'all';

    function showBastToast(msg) {
        const el = document.getElementById('bastToast');
        el.textContent = msg;
        el.style.display = 'block';
        clearTimeout(window._bastToastTimer);
        window._bastToastTimer = setTimeout(() => { el.style.display = 'none'; }, 2400);
    }

    function filterCustBast(mode, btnEl) {
        if (mode) currentCustFilter = mode;
        if (btnEl) {
            document.querySelectorAll('.cust-filter-pill').forEach(b => {
                b.style.background = '#f8fafc';
                b.style.color = '#475569';
            });
            btnEl.style.background = '#0f172a';
            btnEl.style.color = '#fff';
        }
        const q = (document.getElementById('searchCustBast')?.value || '').toLowerCase().trim();
        document.querySelectorAll('#custBastList .cust-row').forEach(row => {
            const s = row.getAttribute('data-search') || '';
            const w = row.getAttribute('data-wajib') === '1';
            const okText = q === '' || s.indexOf(q) !== -1;
            const okMode = currentCustFilter === 'all' || (currentCustFilter === 'wajib' && w);
            row.style.display = (okText && okMode) ? 'flex' : 'none';
        });
    }

    function toggleCustomerBast(custId, cb) {
        const wajib = cb.checked ? 1 : 0;
        const row = cb.closest('.cust-row');
        const fd = new FormData();
        fd.append('action', 'toggle_customer');
        fd.append('customer_id', custId);
        fd.append('wajib_bast', wajib);

        fetch('api_bast.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    row.setAttribute('data-wajib', wajib ? '1' : '0');
                    row.classList.toggle('is-wajib', wajib === 1);
                    const tag = row.querySelector('.wajib-tag');
                    if (tag) tag.style.display = wajib === 1 ? 'inline-block' : 'none';
                    const kpi = document.getElementById('kpiTotalCustWajib');
                    const tab = document.getElementById('tabCustWajibCount');
                    if (kpi) kpi.textContent = res.total_wajib;
                    if (tab) tab.textContent = res.total_wajib;
                    showBastToast(wajib ? '✅ Ditandai Wajib BAST' : 'Ceklis Wajib BAST dilepas');
                    if (currentCustFilter === 'wajib') filterCustBast('wajib');
                } else {
                    cb.checked = !cb.checked;
                    alert(res.message || 'Gagal menyimpan');
                }
            })
            .catch(() => {
                cb.checked = !cb.checked;
                alert('Gagal menghubungi server');
            });
    }

    function uploadNewTemplate(e) {
        e.preventDefault();
        const btn = document.getElementById('btnUploadTpl');
        const fd = new FormData(document.getElementById('formUploadTemplate'));
        fd.append('action', 'upload_template');
        btn.disabled = true;
        btn.textContent = 'Mengunggah...';
        fetch('api_bast.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                alert(res.message || 'Selesai');
                if (res.success) location.reload();
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Pasang Template .docx Baru';
            });
    }
    </script>
</body>
</html>

