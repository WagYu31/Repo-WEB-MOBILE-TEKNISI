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

        /* ── Professional Header & Toolbar ── */
        .riwayat-toolbar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
        }
        .riwayat-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .riwayat-search-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 13.5px;
            pointer-events: none;
            z-index: 3;
        }
        .riwayat-search-input {
            width: 100%;
            height: 42px;
            padding: 0 38px 0 40px;
            border: 1.5px solid #cbd5e1;
            border-radius: 11px;
            font-size: 13.5px;
            font-weight: 500;
            color: #0f172a;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .riwayat-search-input:focus {
            outline: none;
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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
            transition: all 0.15s ease;
        }
        .search-clear-btn:hover {
            background: #cbd5e1;
            color: #0f172a;
        }
        .filter-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.15s ease;
            line-height: 1.2;
        }
        .filter-pill-btn:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        .filter-pill-btn.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.18);
        }
        .filter-pill-count {
            font-size: 10.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            color: #334155;
        }
        .filter-pill-btn.active .filter-pill-count {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* ── Compact Page Size & Sort Controls ── */
        .ctrl-chip-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 4px 10px;
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }
        .pg-select {
            appearance: auto;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #ffffff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 8px;
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .pg-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* ── Professional Bottom Pagination Bar ── */
        .pagination-bar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }
        .pg-info-text {
            font-size: 12.5px;
            color: #475569;
            font-weight: 500;
        }
        .pg-info-text strong {
            color: #0f172a;
            font-weight: 800;
        }
        .pg-nav-group {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        .pg-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .pg-btn:hover:not(:disabled):not(.active) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .pg-btn.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.22);
        }
        .pg-btn:disabled {
            opacity: 0.42;
            cursor: not-allowed;
            background: #f8fafc;
        }
        .pg-ellipsis {
            min-width: 26px;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: 13px;
            user-select: none;
        }
        .pg-jump-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 6px;
            padding-left: 10px;
            border-left: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .pg-jump-input {
            width: 52px;
            height: 32px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            text-align: center;
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            background: #f8fafc;
        }
        .pg-jump-input:focus {
            outline: none;
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.14);
        }
        @media (max-width: 768px) {
            .pagination-bar-card {
                justify-content: center;
                text-align: center;
            }
            .pg-jump-wrap {
                border-left: none;
                padding-left: 0;
                margin-left: 0;
            }
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
                    <div class="card riwayat-toolbar-card mb-4">
                        <div class="card-body p-4">
                            <div class="row align-items-center g-3">
                                <div class="col-lg-6">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="mb-0 text-uppercase fw-bold" style="color:#0f172a; letter-spacing:0.02em;">Riwayat Kegiatan</h5>
                                        <span class="badge rounded-pill" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:11.5px; font-weight:800; padding:4px 10px;">
                                            <?= number_format($totalActivities) ?> Total
                                        </span>
                                    </div>
                                    <p class="text-sm mb-0 mt-1" style="color:#475569;"><strong><?= htmlspecialchars($customerName) ?></strong></p>
                                </div>
                                <div class="col-lg-6">
                                    <div class="riwayat-search-wrap">
                                        <i class="fas fa-search riwayat-search-icon" aria-hidden="true"></i>
                                        <input type="text" id="searchInput" class="riwayat-search-input" placeholder="Cari kegiatan, no. SO, invoice, teknisi, atau catatan..." autocomplete="off">
                                        <button type="button" id="btnClearSearch" class="search-clear-btn" title="Hapus pencarian" aria-label="Hapus pencarian">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                        <small class="text-muted fw-semibold" id="searchCounter" style="font-size:11.5px;"></small>
                                        <small class="text-muted" style="font-size:11px;"><i class="fa-solid fa-bolt text-warning me-1"></i>Pencarian instan</small>
                                    </div>
                                </div>
                            </div>

                            <?php if ($totalActivities > 0): ?>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 pt-3 border-top">
                                <div class="d-flex flex-wrap align-items-center gap-2" id="statusFilterGroup">
                                    <button type="button" class="filter-pill-btn active" data-filter="all" onclick="setStatusFilter('all', this)">
                                        Semua <span class="filter-pill-count" id="cnt_all"><?= $totalActivities ?></span>
                                    </button>
                                    <button type="button" class="filter-pill-btn" data-filter="has_so" onclick="setStatusFilter('has_so', this)">
                                        Ada No. SO <span class="filter-pill-count" id="cnt_has_so">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn" data-filter="belum_input" onclick="setStatusFilter('belum_input', this)">
                                        Belum Input <span class="filter-pill-count" id="cnt_belum_input">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn" data-filter="belum_lunas" onclick="setStatusFilter('belum_lunas', this)">
                                        Belum Lunas <span class="filter-pill-count" id="cnt_belum_lunas">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn" data-filter="lunas" onclick="setStatusFilter('lunas', this)">
                                        Lunas <span class="filter-pill-count" id="cnt_lunas">0</span>
                                    </button>
                                    <button type="button" class="filter-pill-btn" data-filter="no_pay" onclick="setStatusFilter('no_pay', this)">
                                        No Payment <span class="filter-pill-count" id="cnt_no_pay">0</span>
                                    </button>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="ctrl-chip-wrap">
                                        <i class="fa-solid fa-arrow-down-wide-short text-secondary"></i>
                                        <label for="sortOrderSelect" class="mb-0">Urutkan:</label>
                                        <select id="sortOrderSelect" class="pg-select" onchange="changeSortOrder(this.value)">
                                            <option value="newest">Terbaru</option>
                                            <option value="oldest">Terlama</option>
                                        </select>
                                    </div>
                                    <div class="ctrl-chip-wrap">
                                        <i class="fa-solid fa-list-ol text-primary"></i>
                                        <label for="pageSizeTop" class="mb-0">Tampilkan:</label>
                                        <select id="pageSizeTop" class="pg-select" onchange="changePageSize(this.value)">
                                            <option value="10">10 baris</option>
                                            <option value="25">25 baris</option>
                                            <option value="50">50 baris</option>
                                            <option value="100">100 baris</option>
                                            <option value="all">Semua</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="activityListAnchor"></div>
                    <div id="activityList"></div>

                    <!-- Professional Pagination Bar -->
                    <div id="paginationBar" class="pagination-bar-card" style="display:none;">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="pg-info-text" id="pgInfoText"></div>
                            <div class="ctrl-chip-wrap">
                                <label for="pageSizeBottom" class="mb-0">Per halaman:</label>
                                <select id="pageSizeBottom" class="pg-select" onchange="changePageSize(this.value)">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="all">Semua</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="pg-nav-group" id="pgNavGroup"></div>
                            <div class="pg-jump-wrap" id="pgJumpWrap" style="display:none;">
                                <span>Ke hal.</span>
                                <input type="number" id="pgJumpInput" class="pg-jump-input" min="1" placeholder="1" title="Ketik nomor halaman lalu tekan Enter">
                            </div>
                        </div>
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

    const PAGE_SIZE_STORAGE_KEY = 'loewix_cust_detail_page_size';
    let pageSize = 10; // Default 10 baris per halaman (bisa pilih 10 / 25 / 50 / 100 / Semua)
    let currentPage = 1;
    let sortOrder = 'newest';
    let activeStatusFilter = 'all';
    let filteredList = [];
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

    function updateFilterBadgeCounts() {
        const counts = { all: ACTIVITIES_DATA.length, has_so: 0, belum_input: 0, belum_lunas: 0, lunas: 0, no_pay: 0 };
        for (let i = 0; i < ACTIVITIES_DATA.length; i++) {
            const it = ACTIVITIES_DATA[i];
            if (it.no_so) counts.has_so++;
            if (counts[it._statusKey] !== undefined) counts[it._statusKey]++;
        }
        Object.keys(counts).forEach(k => {
            const el = document.getElementById('cnt_' + k);
            if (el) el.textContent = counts[k];
        });
    }

    // Bangun index pencarian 1 kali di memori (instan < 2ms bahkan untuk ribuan kegiatan)
    for (let i = 0; i < ACTIVITIES_DATA.length; i++) {
        buildSearchIndex(ACTIVITIES_DATA[i]);
    }

    function renderCardHtml(act) {
        const kodeEsc = escapeHtml(act.kode);
        const soEsc = escapeHtml(act.no_so);

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

    function getTotalPages() {
        if (filteredList.length === 0) return 1;
        if (pageSize === 'all') return 1;
        return Math.max(1, Math.ceil(filteredList.length / pageSize));
    }

    function buildPageNumbers(current, total) {
        if (total <= 7) {
            const arr = [];
            for (let p = 1; p <= total; p++) arr.push(p);
            return arr;
        }
        const pages = [1];
        let start = Math.max(2, current - 1);
        let end = Math.min(total - 1, current + 1);
        if (current <= 3) { start = 2; end = 4; }
        if (current >= total - 2) { start = total - 3; end = total - 1; }
        if (start > 2) pages.push('...');
        for (let p = start; p <= end; p++) pages.push(p);
        if (end < total - 1) pages.push('...');
        pages.push(total);
        return pages;
    }

    function renderCurrentPage(shouldScrollTop) {
        const container = document.getElementById('activityList');
        const pgBar = document.getElementById('paginationBar');
        const pgInfo = document.getElementById('pgInfoText');
        const pgNav = document.getElementById('pgNavGroup');
        const pgJumpWrap = document.getElementById('pgJumpWrap');
        const pgJumpInput = document.getElementById('pgJumpInput');
        const counter = document.getElementById('searchCounter');
        const emptyBox = document.getElementById('emptyStateBox');
        const noResBox = document.getElementById('noResultsMessage');
        const term = (document.getElementById('searchInput').value || '').trim();

        if (ACTIVITIES_DATA.length === 0) {
            container.innerHTML = '';
            emptyBox.style.display = 'block';
            noResBox.style.display = 'none';
            pgBar.style.display = 'none';
            counter.textContent = '0 kegiatan';
            return;
        }

        emptyBox.style.display = 'none';

        if (filteredList.length === 0) {
            container.innerHTML = '';
            noResBox.style.display = 'block';
            pgBar.style.display = 'none';
            counter.textContent = `Ditemukan 0 dari ${ACTIVITIES_DATA.length} kegiatan`;
            return;
        }

        noResBox.style.display = 'none';

        const totalPages = getTotalPages();
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const isAll = (pageSize === 'all');
        const startIdx = isAll ? 0 : (currentPage - 1) * pageSize;
        const endIdx = isAll ? filteredList.length : Math.min(startIdx + pageSize, filteredList.length);

        let html = '';
        for (let i = startIdx; i < endIdx; i++) {
            html += renderCardHtml(filteredList[i]);
        }
        container.innerHTML = html;

        const startHuman = startIdx + 1;
        const endHuman = endIdx;
        if (term !== '' || activeStatusFilter !== 'all') {
            counter.textContent = `Menampilkan ${startHuman}–${endHuman} dari ${filteredList.length} hasil (total ${ACTIVITIES_DATA.length} kegiatan)`;
        } else {
            counter.textContent = `Menampilkan ${startHuman}–${endHuman} dari ${filteredList.length} kegiatan`;
        }

        pgInfo.innerHTML = `Menampilkan <strong>${startHuman}–${endHuman}</strong> dari <strong>${filteredList.length}</strong> kegiatan` +
            (!isAll ? ` <span class="text-muted">(Halaman ${currentPage} dari ${totalPages})</span>` : '');

        // Render tombol pagination
        if (totalPages > 1) {
            const pageItems = buildPageNumbers(currentPage, totalPages);
            let navHtml = `
                <button type="button" class="pg-btn" onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''} title="Halaman Pertama">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button type="button" class="pg-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-left"></i> <span class="d-none d-sm-inline">Prev</span>
                </button>`;

            for (let i = 0; i < pageItems.length; i++) {
                const p = pageItems[i];
                if (p === '...') {
                    navHtml += '<span class="pg-ellipsis">…</span>';
                } else {
                    navHtml += `<button type="button" class="pg-btn ${p === currentPage ? 'active' : ''}" onclick="goToPage(${p})">${p}</button>`;
                }
            }

            navHtml += `
                <button type="button" class="pg-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <span class="d-none d-sm-inline">Next</span> <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" class="pg-btn" onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''} title="Halaman Terakhir">
                    <i class="fa-solid fa-angles-right"></i>
                </button>`;

            pgNav.innerHTML = navHtml;
            pgNav.style.display = 'inline-flex';
            if (totalPages > 5) {
                pgJumpWrap.style.display = 'inline-flex';
                pgJumpInput.max = totalPages;
                pgJumpInput.value = '';
                pgJumpInput.placeholder = String(currentPage);
            } else {
                pgJumpWrap.style.display = 'none';
            }
        } else {
            pgNav.innerHTML = '';
            pgNav.style.display = 'none';
            pgJumpWrap.style.display = 'none';
        }

        pgBar.style.display = 'flex';

        if (shouldScrollTop) {
            const anchor = document.getElementById('activityListAnchor');
            if (anchor) {
                anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    function goToPage(pageNum) {
        const total = getTotalPages();
        const target = Math.max(1, Math.min(total, parseInt(pageNum, 10) || 1));
        if (target === currentPage) return;
        currentPage = target;
        renderCurrentPage(true);
    }

    function syncPageSizeDropdowns(val) {
        const topSel = document.getElementById('pageSizeTop');
        const botSel = document.getElementById('pageSizeBottom');
        if (topSel) topSel.value = String(val);
        if (botSel) botSel.value = String(val);
    }

    function changePageSize(val) {
        if (val === 'all') {
            pageSize = 'all';
        } else {
            const num = parseInt(val, 10);
            pageSize = [10, 25, 50, 100].includes(num) ? num : 10;
        }
        try { localStorage.setItem(PAGE_SIZE_STORAGE_KEY, String(pageSize)); } catch (e) {}
        syncPageSizeDropdowns(pageSize);
        currentPage = 1;
        renderCurrentPage(false);
    }

    function changeSortOrder(val) {
        sortOrder = (val === 'oldest') ? 'oldest' : 'newest';
        currentPage = 1;
        applyFilterAndRender();
    }

    function applyFilterAndRender() {
        const rawTerm = (document.getElementById('searchInput').value || '').trim();
        const terms = rawTerm ? rawTerm.toLowerCase().split(/\s+/).filter(Boolean) : [];
        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) {
            clearBtn.style.display = rawTerm ? 'flex' : 'none';
        }

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

        if (sortOrder === 'oldest') {
            filteredList.reverse();
        }

        currentPage = 1;
        renderCurrentPage(false);
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
                    updateFilterBadgeCounts();
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
        // Muat preferensi jumlah baris per halaman dari localStorage (default: 10 baris)
        try {
            const savedSize = localStorage.getItem(PAGE_SIZE_STORAGE_KEY);
            if (savedSize === 'all') {
                pageSize = 'all';
            } else if (savedSize && [10, 25, 50, 100].includes(parseInt(savedSize, 10))) {
                pageSize = parseInt(savedSize, 10);
            }
        } catch (e) {}
        syncPageSizeDropdowns(pageSize);
        updateFilterBadgeCounts();

        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('btnClearSearch');
        const pgJumpInput = document.getElementById('pgJumpInput');

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
        if (pgJumpInput) {
            pgJumpInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    goToPage(pgJumpInput.value);
                }
            });
        }

        applyFilterAndRender();
    });
    </script>
</body>
</html>
