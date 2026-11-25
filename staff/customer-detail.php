<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";

$customer_id = $_GET["id_cust"] ?? 0;
if (!filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
    die("ID Customer tidak valid.");
}
$customer_id = (int)$customer_id;

// 1. Ambil nama customer langsung via Primary Key (O(1))
$customerName = "Tidak Diketahui";
$stmtCust = $conn->prepare("SELECT nama FROM customer WHERE id = ? LIMIT 1");
if ($stmtCust) {
    $stmtCust->bind_param("i", $customer_id);
    $stmtCust->execute();
    $resCust = $stmtCust->get_result();
    if ($resCust && ($rCust = $resCust->fetch_assoc())) {
        $customerName = $rCust['nama'] ?: $customerName;
    }
    $stmtCust->close();
}

// 2. Ambil daftar kegiatan terbaru KHUSUS customer_id ini (tidak scan seluruh tabel!)
$grouped_activities = [];
$sqlKegiatan = "SELECT
                    k.id AS kegiatan_id,
                    k.kode AS kegiatan_kode,
                    k.kegiatan AS jenis_kegiatan,
                    k.jadwal AS jadwal_kegiatan,
                    k.keterangan AS keterangan_kegiatan,
                    k.invoice AS kegiatan_invoice,
                    k.paid AS kegiatan_paid,
                    k.catatan_admin,
                    k.no_so AS kegiatan_no_so,
                    agg.tgl_lunas AS lunas
                FROM (
                    SELECT
                        kode,
                        MAX(id) AS max_id,
                        MAX(CASE
                            WHEN lunas IS NOT NULL
                             AND CAST(lunas AS CHAR) NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
                            THEN SUBSTR(CAST(lunas AS CHAR), 1, 10)
                            ELSE NULL
                        END) AS tgl_lunas
                    FROM kegiatan
                    WHERE customer_id = ? AND deleted_at IS NULL
                    GROUP BY kode
                ) agg
                INNER JOIN kegiatan k ON k.id = agg.max_id
                ORDER BY k.jadwal DESC, k.id DESC";

$stmt = $conn->prepare($sqlKegiatan);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$allKodes = [];
$missingSoKodes = [];

while ($row = $result->fetch_assoc()) {
    $kode = (string)$row['kegiatan_kode'];
    $noSo = trim((string)($row['kegiatan_no_so'] ?? ''));

    $grouped_activities[$kode] = [
        'kode' => $kode,
        'id' => (int)$row['kegiatan_id'],
        'jenis' => (string)($row['jenis_kegiatan'] ?? ''),
        'jadwal' => (string)($row['jadwal_kegiatan'] ?? ''),
        'jadwal_fmt' => !empty($row['jadwal_kegiatan']) ? date("d M Y", strtotime($row['jadwal_kegiatan'])) : '-',
        'keterangan' => (string)($row['keterangan_kegiatan'] ?? ''),
        'no_invoice' => '',
        'nominal_invoice' => 0,
        'lunas' => (string)($row['lunas'] ?? ''),
        'invoice' => (string)($row['kegiatan_invoice'] ?? ''),
        'paid' => (int)($row['kegiatan_paid'] ?? 0),
        'catatan_admin' => (string)($row['catatan_admin'] ?? ''),
        'no_so' => $noSo,
        'pelaksanaan' => []
    ];

    if ($kode !== '') {
        $allKodes[] = $kode;
        if ($noSo === '') {
            $missingSoKodes[] = $kode;
        }
    }
}
$stmt->close();

// 3. Fallback No. SO dari progress_kegiatan HANYA untuk kode milik customer ini yang no_so-nya kosong
if (!empty($missingSoKodes)) {
    foreach (array_chunk($missingSoKodes, 300) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $types = str_repeat('s', count($chunk));
        $stmtSo = $conn->prepare("SELECT kode, MAX(no_so) AS no_so FROM progress_kegiatan WHERE kode IN ({$placeholders}) AND no_so IS NOT NULL AND no_so != '' GROUP BY kode");
        if ($stmtSo) {
            $stmtSo->bind_param($types, ...$chunk);
            $stmtSo->execute();
            $resSo = $stmtSo->get_result();
            while ($rSo = $resSo->fetch_assoc()) {
                $k = (string)$rSo['kode'];
                if (isset($grouped_activities[$k]) && !empty($rSo['no_so'])) {
                    $grouped_activities[$k]['no_so'] = trim((string)$rSo['no_so']);
                }
            }
            $stmtSo->close();
        }
    }
}

// 4. Ambil data Invoice terbaru dari pendapatan_kegiatan secara batch (pengganti ratusan correlated subquery)
if (!empty($allKodes)) {
    foreach (array_chunk($allKodes, 300) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $types = str_repeat('s', count($chunk));
        $sqlInv = "SELECT pk.kode, pk.no_invoice, pk.nominal_invoice
                   FROM pendapatan_kegiatan pk
                   INNER JOIN (
                       SELECT kode, MAX(id) AS max_id
                       FROM pendapatan_kegiatan
                       WHERE kode IN ({$placeholders}) AND deleted_at IS NULL
                       GROUP BY kode
                   ) latest_pk ON pk.id = latest_pk.max_id";
        $stmtInv = $conn->prepare($sqlInv);
        if ($stmtInv) {
            $stmtInv->bind_param($types, ...$chunk);
            $stmtInv->execute();
            $resInv = $stmtInv->get_result();
            while ($rInv = $resInv->fetch_assoc()) {
                $k = (string)$rInv['kode'];
                if (isset($grouped_activities[$k])) {
                    $grouped_activities[$k]['no_invoice'] = (string)($rInv['no_invoice'] ?? '');
                    $grouped_activities[$k]['nominal_invoice'] = (float)($rInv['nominal_invoice'] ?? 0);
                }
            }
            $stmtInv->close();
        }
    }
}

// 5. Ambil Absensi Teknisi HANYA untuk kode milik customer ini & HANYA kolom yang dipakai (tanpa image_1..5 / teks panjang)
if (!empty($allKodes)) {
    foreach (array_chunk($allKodes, 300) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $types = str_repeat('s', count($chunk));
        $sqlPel = "SELECT p1.kode, p1.teknisi_id, p1.waktu_mulai, p1.waktu_selesai, t.nama AS teknisi_name
                   FROM pelaksanaan_kegiatan p1
                   INNER JOIN (
                       SELECT kode, teknisi_id, waktu_mulai, MAX(id) AS max_id
                       FROM pelaksanaan_kegiatan
                       WHERE kode IN ({$placeholders}) AND deleted_at IS NULL
                       GROUP BY kode, teknisi_id, waktu_mulai
                   ) p2 ON p1.id = p2.max_id
                   LEFT JOIN teknisi t ON p1.teknisi_id = t.id
                   ORDER BY p1.waktu_mulai ASC";
        $stmtPel = $conn->prepare($sqlPel);
        if ($stmtPel) {
            $stmtPel->bind_param($types, ...$chunk);
            $stmtPel->execute();
            $resPel = $stmtPel->get_result();
            while ($rPel = $resPel->fetch_assoc()) {
                $k = (string)$rPel['kode'];
                if (isset($grouped_activities[$k]) && !empty($rPel['teknisi_name'])) {
                    $wmRaw = (string)($rPel['waktu_mulai'] ?? '');
                    $wsRaw = (string)($rPel['waktu_selesai'] ?? '');
                    $wmValid = ($wmRaw !== '' && substr($wmRaw, 0, 10) !== '0000-00-00');
                    $wsValid = ($wsRaw !== '' && substr($wsRaw, 0, 10) !== '0000-00-00');
                    $grouped_activities[$k]['pelaksanaan'][] = [
                        'teknisi_id' => (int)$rPel['teknisi_id'],
                        'teknisi_name' => (string)$rPel['teknisi_name'],
                        'waktu_mulai_fmt' => $wmValid ? date("d/m/y H:i", strtotime($wmRaw)) : 'n/a',
                        'waktu_mulai_ok' => $wmValid,
                        'waktu_selesai_fmt' => $wsValid ? date("d/m/y H:i", strtotime($wsRaw)) : 'n/a',
                        'waktu_selesai_ok' => $wsValid,
                    ];
                }
            }
            $stmtPel->close();
        }
    }
}

$activitiesList = array_values($grouped_activities);
$totalActivities = count($activitiesList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Riwayat Pelanggan - <?= htmlspecialchars($customerName) ?></title>
    <?php include "head.php"; ?>
    <style>
        .so-badge-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.02em;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            line-height: 1.2;
        }
        .so-badge-btn:hover {
            background: #dcfce7;
            border-color: #86efac;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(22, 101, 52, 0.12);
        }
        .btn-so-add {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 3px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.2;
        }
        .btn-so-add:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .search-clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: #e2e8f0;
            color: #475569;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 11px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            padding: 0;
        }
        .search-clear-btn:hover {
            background: #cbd5e1;
            color: #0f172a;
        }
        .filter-pill-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-pill-btn:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        .filter-pill-btn.active {
            background: #1e293b;
            border-color: #1e293b;
            color: #ffffff;
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-xl-12 col-lg-12">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="mb-0 text-uppercase">Riwayat Kegiatan</h5>
                                        <span class="badge bg-light text-dark border" style="font-size:11px; font-weight:700;">
                                            <?= number_format($totalActivities) ?> Kegiatan
                                        </span>
                                    </div>
                                    <p class="text-sm mb-0 mt-1"><strong><?= htmlspecialchars($customerName) ?></strong></p>
                                </div>
                                <div class="col-md-6">
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text text-body bg-white border"><i class="fas fa-search" aria-hidden="true"></i></span>
                                            <input type="text" id="searchInput" class="form-control p-2 border pe-5" placeholder="Cari kegiatan, no. SO, invoice, teknisi, atau catatan..." autocomplete="off">
                                        </div>
                                        <button type="button" id="btnClearSearch" class="search-clear-btn" title="Hapus pencarian" aria-label="Hapus pencarian">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                        <small class="text-muted" id="searchCounter" style="font-size:11.5px;"></small>
                                        <small class="text-muted" style="font-size:11px;">Pencarian instan aktif</small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($totalActivities > 0): ?>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
                                <button type="button" class="filter-pill-btn active" data-filter="all" onclick="setStatusFilter('all', this)">Semua (<?= $totalActivities ?>)</button>
                                <button type="button" class="filter-pill-btn" data-filter="has_so" onclick="setStatusFilter('has_so', this)">Ada No. SO</button>
                                <button type="button" class="filter-pill-btn" data-filter="belum_input" onclick="setStatusFilter('belum_input', this)">Belum Input Invoice</button>
                                <button type="button" class="filter-pill-btn" data-filter="belum_lunas" onclick="setStatusFilter('belum_lunas', this)">Belum Lunas</button>
                                <button type="button" class="filter-pill-btn" data-filter="lunas" onclick="setStatusFilter('lunas', this)">Lunas</button>
                                <button type="button" class="filter-pill-btn" data-filter="no_pay" onclick="setStatusFilter('no_pay', this)">No Payment</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="activityList"></div>
                    <div id="loadMoreWrap" class="text-center mb-4" style="display:none;">
                        <button type="button" id="btnLoadMore" class="btn btn-outline-primary btn-sm px-4 fw-bold me-2" onclick="loadMoreActivities()">
                            Tampilkan Lebih Banyak
                        </button>
                        <button type="button" id="btnShowAll" class="btn btn-light border btn-sm px-3 fw-bold" onclick="showAllActivities()">
                            Tampilkan Semua
                        </button>
                    </div>
                    <div id="emptyStateBox" class="alert alert-info text-white text-center" style="display:none;">
                        Tidak ada riwayat kegiatan untuk pelanggan ini.
                    </div>
                    <div id="noResultsMessage" class="alert alert-warning text-white text-center" style="display:none;">
                        Pencarian tidak menemukan riwayat kegiatan yang cocok.
                    </div>
                </div>
            </div>

            <!-- Modal Input / Edit No SO -->
            <div class="modal fade" id="modalInputSO" tabindex="-1" aria-labelledby="modalInputSOLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                    <div class="modal-content" style="border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
                        <div class="modal-header pb-0 border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="modal-title fw-bold text-dark mb-0" id="modalInputSOLabel">
                                    <i class="fa-solid fa-receipt text-success me-2"></i>Nomor SO (Sales Order)
                                </h6>
                                <small class="text-secondary text-xxs" id="modalSOCustName"></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formInputSO" onsubmit="submitSOForm(event)">
                            <div class="modal-body px-4 py-3">
                                <input type="hidden" id="soKodeInput" name="kode">

                                <div class="mb-2">
                                    <label class="form-label fw-bold text-xs text-secondary text-uppercase mb-1">Nomor Sales Order (SO)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="fa-solid fa-hashtag text-muted"></i></span>
                                        <input type="text" class="form-control rounded-end-3" id="soNumberInput" name="no_so" placeholder="Contoh: 2608.SOL.06406" autocomplete="off" style="font-family: monospace; font-size: 13px; font-weight: 700;">
                                    </div>
                                    <div class="form-text text-xxs text-muted mt-1">Kosongkan jika ingin menghapus nomor SO.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                                <button type="button" class="btn btn-sm btn-light rounded-3 fw-bold px-3 py-2 text-xs" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-sm btn-primary rounded-3 fw-bold px-4 py-2 text-xs shadow-sm" id="btnSaveSO">
                                    <i class="fa-solid fa-check me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script>
    const CUSTOMER_NAME = <?= json_encode($customerName, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const ACTIVITIES_DATA = <?= json_encode($activitiesList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const BATCH_SIZE = 25;
    let activeStatusFilter = 'all';
    let filteredList = [];
    let renderedCount = 0;
    let searchTimer = null;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2brEscaped(str) {
        return escapeHtml(str).replace(/\r\n|\r|\n/g, '<br>');
    }

    function formatRupiah(num) {
        const n = Math.round(Number(num) || 0);
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function getStatusKey(item) {
        if (item.no_invoice) {
            return (item.lunas && item.lunas !== '0000-00-00') ? 'lunas' : 'belum_lunas';
        }
        return (item.invoice === 'n/a') ? 'no_pay' : 'belum_input';
    }

    function buildSearchIndex(item) {
        const statusKey = getStatusKey(item);
        const statusText = statusKey === 'lunas' ? 'lunas'
            : statusKey === 'belum_lunas' ? 'belum lunas'
            : statusKey === 'no_pay' ? 'no payment'
            : 'belum input';
        const tekNames = (item.pelaksanaan || []).map(p => p.teknisi_name + ' ' + p.waktu_mulai_fmt + ' ' + p.waktu_selesai_fmt).join(' ');
        item._statusKey = statusKey;
        item._search = [
            item.jenis,
            item.kode,
            item.jadwal_fmt,
            item.jadwal,
            item.no_so,
            item.no_invoice,
            item.keterangan,
            item.catatan_admin,
            statusText,
            tekNames
        ].join(' ').toLowerCase();
    }

    // Bangun index pencarian 1 kali di memori (instan < 2ms bahkan untuk ribuan kegiatan)
    for (let i = 0; i < ACTIVITIES_DATA.length; i++) {
        buildSearchIndex(ACTIVITIES_DATA[i]);
    }

    function renderCardHtml(act) {
        const kodeEsc = escapeHtml(act.kode);
        const soEsc = escapeHtml(act.no_so);
        const custEsc = escapeHtml(CUSTOMER_NAME);

        const headerSoHtml = act.no_so
            ? `<span class="badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:0.02em;padding:3px 8px;">
                   <i class="fa-solid fa-receipt me-1"></i>${soEsc}
               </span>`
            : '';

        const bodySoHtml = act.no_so
            ? `<button type="button" class="so-badge-btn" onclick="openInputSO('${kodeEsc}')" title="Klik untuk edit No. SO">
                   <i class="fa-solid fa-receipt text-success me-1"></i>
                   <span class="so-txt" id="txt_so_${kodeEsc}">${soEsc}</span>
                   <i class="fa-solid fa-pen ms-1 opacity-50" style="font-size: 8px;"></i>
               </button>`
            : `<button type="button" class="btn-so-add" onclick="openInputSO('${kodeEsc}')" title="Input Nomor SO">
                   <i class="fa-solid fa-plus"></i> Input SO
               </button>`;

        let financeHtml = '';
        if (act.no_invoice) {
            const lunasBadge = (act.lunas && act.lunas !== '0000-00-00')
                ? '<span class="badge badge-sm bg-gradient-success">LUNAS</span>'
                : '<span class="badge badge-sm bg-gradient-danger">BELUM LUNAS</span>';
            financeHtml = `
                <p class="text-sm mb-1"><strong>No. Invoice:</strong> ${escapeHtml(act.no_invoice)}</p>
                <p class="text-sm mb-1"><strong>Nominal:</strong> <span class="font-weight-bold text-success">Rp ${formatRupiah(act.nominal_invoice)}</span></p>
                <p class="text-sm mb-0"><strong>Status:</strong> ${lunasBadge}</p>`;
        } else {
            const isNoPay = (act.invoice === 'n/a');
            const nomText = isNoPay ? ('Rp ' + formatRupiah(act.paid) + ' (fee)') : 'Rp 0';
            const statusPill = isNoPay
                ? '<span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;">NO PAYMENT</span>'
                : '<span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;">BELUM INPUT</span>';
            let noteBox = '';
            if (isNoPay && act.catatan_admin) {
                noteBox = `<div style="margin-top:8px;padding:8px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:10px;color:#991b1b;">${escapeHtml(act.catatan_admin)}</div>`;
            } else if (!isNoPay) {
                noteBox = `<div style="margin-top:8px;padding:8px 12px;background:#fefce8;border:1px solid #fde68a;border-radius:6px;font-size:10px;color:#92400e;">⚠️ Invoice belum di-input. Silakan input melalui <strong>Laporan Kegiatan</strong>.</div>`;
            }
            financeHtml = `
                <p class="text-sm mb-1"><strong>No. Invoice:</strong> <span class="text-muted">-</span></p>
                <p class="text-sm mb-1"><strong>Nominal:</strong> <span class="text-muted">${nomText}</span></p>
                <p class="text-sm mb-1"><strong>Status:</strong> ${statusPill}</p>
                ${noteBox}`;
        }

        let absensiHtml = '<p class="text-sm text-muted">Belum ada data absensi.</p>';
        if (act.pelaksanaan && act.pelaksanaan.length > 0) {
            let rowsHtml = '';
            for (let j = 0; j < act.pelaksanaan.length; j++) {
                const t = act.pelaksanaan[j];
                rowsHtml += `
                    <tr style="border-bottom:1px solid #bfbfbf;">
                        <td>
                            <a class="text-sm font-weight-bold mb-0" href="list-kegiatan-teknisi.php?idTek=${encodeURIComponent(t.teknisi_id)}">
                                ${escapeHtml(t.teknisi_name)}
                            </a>
                        </td>
                        <td>
                            <p class="text-sm mb-0 ${t.waktu_mulai_ok ? 'text-dark' : 'text-danger fw-bold'}">${escapeHtml(t.waktu_mulai_fmt)}</p>
                        </td>
                        <td>
                            <p class="text-sm mb-0 ${t.waktu_selesai_ok ? 'text-dark' : 'text-danger fw-bold'}">${escapeHtml(t.waktu_selesai_fmt)}</p>
                        </td>
                    </tr>`;
            }
            absensiHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-dark text-xs font-weight-bolder">Teknisi</th>
                                <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Mulai</th>
                                <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Selesai</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>`;
        }

        return `
            <div class="card mb-4 activity-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 text-dark text-capitalize d-flex align-items-center flex-wrap gap-2">
                            <span><i class="fa-solid fa-bolt text-primary me-2"></i>${escapeHtml(act.jenis)}</span>
                            <span id="header_so_${kodeEsc}">${headerSoHtml}</span>
                        </h6>
                        <span class="text-xs text-muted">${escapeHtml(act.jadwal_fmt)} (${kodeEsc})</span>
                    </div>
                    <a href="view-kegiatan.php?kode_transaksi=${encodeURIComponent(act.kode)}" target="_blank" class="btn btn-sm btn-outline-info mb-0">Lihat Detail Kegiatan</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="font-weight-bolder">Ringkasan</h6>
                            <p class="text-sm text-muted">${act.keterangan ? nl2brEscaped(act.keterangan) : 'Tidak ada keterangan umum.'}</p>
                            <hr class="horizontal dark my-2">
                            <h6 class="font-weight-bolder">Informasi Keuangan</h6>
                            <p class="text-sm mb-1 d-flex align-items-center flex-wrap">
                                <strong class="me-1">No. SO:</strong>
                                <span id="wrap_so_${kodeEsc}">${bodySoHtml}</span>
                            </p>
                            ${financeHtml}
                        </div>
                        <div class="col-md-8 border-start ps-md-4 mt-4 mt-md-0">
                            <h6 class="font-weight-bolder">Absensi Teknisi</h6>
                            ${absensiHtml}
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function updateLoadMoreControls() {
        const remaining = filteredList.length - renderedCount;
        const wrap = document.getElementById('loadMoreWrap');
        const btnMore = document.getElementById('btnLoadMore');
        const btnAll = document.getElementById('btnShowAll');
        const counter = document.getElementById('searchCounter');
        const term = (document.getElementById('searchInput').value || '').trim();

        if (ACTIVITIES_DATA.length === 0) {
            counter.textContent = '0 kegiatan';
        } else if (term !== '' || activeStatusFilter !== 'all') {
            counter.textContent = `Ditemukan ${filteredList.length} dari ${ACTIVITIES_DATA.length} kegiatan (ditampilkan ${renderedCount})`;
        } else {
            counter.textContent = `Menampilkan ${renderedCount} dari ${ACTIVITIES_DATA.length} kegiatan`;
        }

        if (remaining > 0) {
            const nextStep = Math.min(BATCH_SIZE, remaining);
            btnMore.textContent = `Tampilkan ${nextStep} Kegiatan Lainnya (Sisa ${remaining})`;
            btnAll.style.display = remaining > BATCH_SIZE ? 'inline-block' : 'none';
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
        }
    }

    function appendNextBatch(count) {
        const container = document.getElementById('activityList');
        const end = Math.min(renderedCount + count, filteredList.length);
        let html = '';
        for (let i = renderedCount; i < end; i++) {
            html += renderCardHtml(filteredList[i]);
        }
        if (html) {
            container.insertAdjacentHTML('beforeend', html);
        }
        renderedCount = end;
        updateLoadMoreControls();
    }

    function loadMoreActivities() {
        appendNextBatch(BATCH_SIZE);
    }

    function showAllActivities() {
        appendNextBatch(filteredList.length - renderedCount);
    }

    function applyFilterAndRender() {
        const rawTerm = (document.getElementById('searchInput').value || '').trim();
        const terms = rawTerm ? rawTerm.toLowerCase().split(/\s+/).filter(Boolean) : [];
        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) {
            clearBtn.style.display = rawTerm ? 'flex' : 'none';
        }

        const emptyBox = document.getElementById('emptyStateBox');
        const noResBox = document.getElementById('noResultsMessage');
        const container = document.getElementById('activityList');

        if (ACTIVITIES_DATA.length === 0) {
            container.innerHTML = '';
            emptyBox.style.display = 'block';
            noResBox.style.display = 'none';
            filteredList = [];
            renderedCount = 0;
            updateLoadMoreControls();
            return;
        }

        emptyBox.style.display = 'none';

        filteredList = [];
        for (let i = 0; i < ACTIVITIES_DATA.length; i++) {
            const item = ACTIVITIES_DATA[i];
            if (activeStatusFilter !== 'all') {
                if (activeStatusFilter === 'has_so') {
                    if (!item.no_so) continue;
                } else if (item._statusKey !== activeStatusFilter) {
                    continue;
                }
            }
            if (terms.length > 0) {
                let matched = true;
                for (let t = 0; t < terms.length; t++) {
                    if (item._search.indexOf(terms[t]) === -1) {
                        matched = false;
                        break;
                    }
                }
                if (!matched) continue;
            }
            filteredList.push(item);
        }

        container.innerHTML = '';
        renderedCount = 0;

        if (filteredList.length === 0) {
            noResBox.style.display = 'block';
            updateLoadMoreControls();
            return;
        }

        noResBox.style.display = 'none';
        appendNextBatch(BATCH_SIZE);
    }

    function setStatusFilter(filterKey, btnEl) {
        activeStatusFilter = filterKey;
        document.querySelectorAll('.filter-pill-btn').forEach(b => b.classList.remove('active'));
        if (btnEl) btnEl.classList.add('active');
        applyFilterAndRender();
    }

    function findActivityByKode(kode) {
        for (let i = 0; i < ACTIVITIES_DATA.length; i++) {
            if (ACTIVITIES_DATA[i].kode === kode) return ACTIVITIES_DATA[i];
        }
        return null;
    }

    function openInputSO(kode) {
        const act = findActivityByKode(kode);
        const currentSO = act ? (act.no_so || '') : '';
        document.getElementById('soKodeInput').value = kode;
        document.getElementById('soNumberInput').value = currentSO;
        document.getElementById('modalSOCustName').textContent = CUSTOMER_NAME ? ('Customer: ' + CUSTOMER_NAME + ' (#' + kode + ')') : ('#' + kode);

        const modalEl = document.getElementById('modalInputSO');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        setTimeout(() => {
            const input = document.getElementById('soNumberInput');
            input.focus();
            input.select();
        }, 300);
    }

    function submitSOForm(e) {
        e.preventDefault();
        const kode = document.getElementById('soKodeInput').value;
        const noSO = document.getElementById('soNumberInput').value.trim();
        const btn = document.getElementById('btnSaveSO');
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...';

        const formData = new FormData();
        formData.append('kode', kode);
        formData.append('no_so', noSO);

        fetch('ajax-update-so.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if (res.status === 'success') {
                // Update data in-memory & search index agar langsung bisa dicari
                const act = findActivityByKode(kode);
                if (act) {
                    act.no_so = noSO;
                    buildSearchIndex(act);
                }

                const kodeEsc = escapeHtml(kode);
                const wrap = document.getElementById('wrap_so_' + kode);
                if (wrap) {
                    if (noSO) {
                        wrap.innerHTML = `
                            <button type="button" class="so-badge-btn" onclick="openInputSO('${kodeEsc}')" title="Klik untuk edit No. SO">
                                <i class="fa-solid fa-receipt text-success me-1"></i>
                                <span class="so-txt" id="txt_so_${kodeEsc}">${escapeHtml(noSO)}</span>
                                <i class="fa-solid fa-pen ms-1 opacity-50" style="font-size: 8px;"></i>
                            </button>`;
                    } else {
                        wrap.innerHTML = `
                            <button type="button" class="btn-so-add" onclick="openInputSO('${kodeEsc}')" title="Input Nomor SO">
                                <i class="fa-solid fa-plus"></i> Input SO
                            </button>`;
                    }
                }

                const headerWrap = document.getElementById('header_so_' + kode);
                if (headerWrap) {
                    headerWrap.innerHTML = noSO
                        ? `<span class="badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:0.02em;padding:3px 8px;">
                               <i class="fa-solid fa-receipt me-1"></i>${escapeHtml(noSO)}
                           </span>`
                        : '';
                }

                const modalEl = document.getElementById('modalInputSO');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            } else {
                alert(res.message || 'Terjadi kesalahan');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Gagal menghubungi server: ' + err.message);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('btnClearSearch');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(applyFilterAndRender, 80);
            });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    applyFilterAndRender();
                }
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.focus();
                applyFilterAndRender();
            });
        }

        applyFilterAndRender();
    });
    </script>
</body>
</html>
