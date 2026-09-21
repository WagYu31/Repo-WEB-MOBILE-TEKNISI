<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

$pageNow = "TIP TOK";
$currentPage = "Today";

$idSesi = $_SESSION["id"] ?? 0;
$role = $_SESSION["jabatan"] ?? 'Sales';
$namaSesi = $nmUser ?? ($_SESSION["nama"] ?? 'Sales');

// Ambil Statistik Live untuk Bento KPI
$filterSales = "";
if ($role === 'Sales') {
    $filterSales = " AND p.id_sales = '$idSesi' ";
}

// 1. Total Toko Penitipan Aktif
$qTokoAktif = $conn->query("SELECT COUNT(DISTINCT p.id_customer) as total FROM tiptok_penitipan p WHERE p.status = 'aktif' $filterSales");
$totalTokoAktif = $qTokoAktif ? ($qTokoAktif->fetch_assoc()['total'] ?? 0) : 0;

// 2. Total Unit Dititip (Stok Awal) & Total Stok Sisa di Toko
$qUnit = $conn->query("SELECT SUM(i.qty_titip) as total_titip, SUM(i.qty_sisa) as total_sisa, SUM(i.qty_terjual) as total_terjual, SUM(i.total_insentif) as total_insentif 
                      FROM tiptok_items i 
                      JOIN tiptok_penitipan p ON i.id_penitipan = p.id 
                      WHERE 1=1 $filterSales");
$dataUnit = $qUnit ? $qUnit->fetch_assoc() : [];
$totalUnitTitip = intval($dataUnit['total_titip'] ?? 0);
$totalUnitSisa = intval($dataUnit['total_sisa'] ?? 0);
$totalUnitTerjual = intval($dataUnit['total_terjual'] ?? 0);
$totalInsentifPool = floatval($dataUnit['total_insentif'] ?? 0);

// 3. Total Unit Terjual yang Belum Diklaim (Unclaimed Eligible)
$filterSalesKunjungan = ($role === 'Sales') ? " AND k.id_sales = '$idSesi' " : "";
$qUnclaimed = $conn->query("SELECT SUM(k.qty_terjual_kunjungan) as total_unclaimed, SUM(k.insentif_didapat) as nominal_unclaimed 
                           FROM tiptok_kunjungan k 
                           WHERE k.id_claim IS NULL AND k.qty_terjual_kunjungan > 0 $filterSalesKunjungan");
$dataUnclaimed = $qUnclaimed ? $qUnclaimed->fetch_assoc() : [];
$unclaimedUnits = intval($dataUnclaimed['total_unclaimed'] ?? 0);
$unclaimedNominal = floatval($dataUnclaimed['nominal_unclaimed'] ?? 0);

$claimTarget = 50;
$claimProgress = min(100, round(($unclaimedUnits / $claimTarget) * 100, 1));
$isClaimEligible = ($unclaimedUnits >= $claimTarget);
$sisaTarget = max(0, $claimTarget - $unclaimedUnits);

// Query Data Master Penitipan
$sqlPenitipan = "SELECT p.*, c.nama AS nama_toko, c.kategori AS kategori_customer, c.telp_pribadi AS telp_toko, 
                        c.alamat AS alamat_toko, c.kota AS kota_toko, c.alamat_lokasi,
                        COUNT(i.id) AS total_jenis_barang,
                        SUM(i.qty_titip) AS sum_titip,
                        SUM(i.qty_sisa) AS sum_sisa,
                        SUM(i.qty_terjual) AS sum_terjual,
                        SUM(i.total_insentif) AS sum_insentif,
                        (SELECT k.no_inv FROM tiptok_kunjungan k WHERE k.id_penitipan = p.id AND k.no_inv IS NOT NULL AND k.no_inv != '' ORDER BY k.tgl_kunjungan DESC, k.id DESC LIMIT 1) AS last_no_inv,
                        (SELECT k.tgl_kunjungan FROM tiptok_kunjungan k WHERE k.id_penitipan = p.id ORDER BY k.tgl_kunjungan DESC, k.id DESC LIMIT 1) AS last_kunjungan
                 FROM tiptok_penitipan p 
                 LEFT JOIN sales_customer c ON p.id_customer = c.id 
                 LEFT JOIN tiptok_items i ON p.id = i.id_penitipan 
                 WHERE 1=1 $filterSales 
                 GROUP BY p.id 
                 ORDER BY p.id DESC";
$resPenitipan = $conn->query($sqlPenitipan);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>TIP TOK (Titip Barang di Toko) | LOEWIX</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-dark: #1e293b;
            --surface-card: #ffffff;
            --surface-bg: #f8fafc;
            --border-light: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            color: var(--text-main);
        }

        /* Hero Header Banner */
        .tiptok-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #2563eb 100%);
            border-radius: 20px;
            padding: 28px 32px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 30px -10px rgba(37, 99, 235, 0.4);
            margin-bottom: 24px;
        }
        .tiptok-hero::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            pointer-events: none;
        }

        /* Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 1200px) {
            .bento-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .bento-grid { grid-template-columns: 1fr; }
        }

        .bento-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .bento-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }
        .bento-card .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 12px;
        }
        .bento-card .stat-value {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .bento-card .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Claim Progress Card */
        .claim-card-featured {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .claim-card-featured .stat-value { color: #ffffff; }
        .claim-card-featured .stat-label { color: #c7d2fe; }

        .progress-container {
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            height: 10px;
            overflow: hidden;
            margin: 10px 0 6px;
        }
        .progress-bar-custom {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 20px;
        }
        .tab-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .tab-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .tab-badge {
            background: rgba(0,0,0,0.06);
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .tab-btn.active .tab-badge {
            background: rgba(255,255,255,0.25);
            color: #ffffff;
        }

        /* Main Table Container */
        .content-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .table-custom {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-custom th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .table-custom td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
            color: #1e293b;
        }
        .table-custom tr:hover td {
            background-color: #f8fafc;
        }

        /* Badges & Pills */
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .badge-dealer {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .badge-active {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-selesai {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .badge-ditarik {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .badge-invoice {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            font-family: monospace;
            font-weight: 700;
        }

        /* Item Row Pill */
        .item-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            margin: 2px;
        }
        .item-chip .stock-counter {
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 6px;
        }
        .stock-sisa { background: #dcfce7; color: #166534; }
        .stock-terjual { background: #fee2e2; color: #991b1b; }

        /* Action Buttons */
        .btn-action-primary {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .btn-action-primary:hover {
            background: #1d4ed8;
            color: #ffffff;
            transform: translateY(-1px);
        }
        .btn-action-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .btn-action-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Modal Customizations */
        .modal-custom .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        .modal-custom .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 18px 24px;
        }
        .form-control-custom {
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control-custom:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            outline: none;
        }
        .item-input-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            position: relative;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">

    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>

        <div class="container-fluid py-4">

            <!-- Hero Welcome Header -->
            <div class="tiptok-hero">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index: 2;">
                    <div>
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-20 text-white text-xs font-weight-bold mb-2">
                            <i class="fa-solid fa-boxes-packing"></i> SISTEM KONSINYASI & MONITORING STOK
                        </div>
                        <h2 class="text-white font-weight-bolder mb-1" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                            TIP TOK (Titip Barang Di Toko)
                        </h2>
                        <p class="text-white text-opacity-80 mb-0 text-sm" style="max-width: 650px;">
                            Kelola penitipan barang di toko dealer, input hasil audit cek sisa stok, catat nomor invoice penjualan, dan pantau klaim insentif min. 50 unit.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light font-weight-bold text-primary shadow-sm px-3 py-2 text-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px;" onclick="openModalTambahPenitipan()">
                            <i class="fa-solid fa-plus-circle"></i> Titip Barang Baru
                        </button>
                        <button class="btn btn-warning font-weight-bold text-dark shadow-sm px-3 py-2 text-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px;" onclick="openTabKlaimInsentif()">
                            <i class="fa-solid fa-hand-holding-dollar"></i> Klaim Insentif
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bento Summary KPI Grid -->
            <div class="bento-grid">
                <!-- Card 1: Toko Aktif -->
                <div class="bento-card">
                    <div class="icon-box bg-blue-100 text-primary">
                        <i class="fa-solid fa-shop"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalTokoAktif, 0, ',', '.'); ?></div>
                    <div class="stat-label">Toko Dealer Aktif</div>
                    <div class="text-xs text-muted mt-2">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> Mitra dengan stok konsinyasi
                    </div>
                </div>

                <!-- Card 2: Total Unit Dititip & Sisa -->
                <div class="bento-card">
                    <div class="icon-box bg-indigo-100 text-indigo">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalUnitSisa, 0, ',', '.'); ?> <span class="text-xs text-muted font-weight-normal">/ <?php echo number_format($totalUnitTitip, 0, ',', '.'); ?> Unit</span></div>
                    <div class="stat-label">Sisa Stok di Toko</div>
                    <div class="text-xs text-muted mt-2">
                        <span class="text-success font-weight-bold"><?php echo number_format($totalUnitTerjual, 0, ',', '.'); ?> unit</span> telah terjual
                    </div>
                </div>

                <!-- Card 3: Akumulasi Insentif Terkumpul -->
                <div class="bento-card">
                    <div class="icon-box bg-emerald-100 text-success">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="stat-value text-success" style="font-size: 1.45rem;">Rp <?php echo number_format($totalInsentifPool, 0, ',', '.'); ?></div>
                    <div class="stat-label">Akumulasi Insentif</div>
                    <div class="text-xs text-muted mt-2">
                        <i class="fa-solid fa-arrow-trend-up text-success me-1"></i> Dari total seluruh penjualan toko
                    </div>
                </div>

                <!-- Card 4: Claim Progress Threshold (Min 50 Unit) -->
                <div class="bento-card claim-card-featured">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="stat-label">Progres Klaim Insentif</span>
                        <span class="badge <?php echo $isClaimEligible ? 'bg-success' : 'bg-warning text-dark'; ?> text-xxs font-weight-bold">
                            <?php echo $isClaimEligible ? 'SIAP KLAIM' : 'MIN. 50 UNIT'; ?>
                        </span>
                    </div>
                    <div class="stat-value"><?php echo $unclaimedUnits; ?> <span class="text-xs font-weight-normal text-white-50">/ 50 Unit</span></div>
                    
                    <div class="progress-container">
                        <div class="progress-bar-custom" style="width: <?php echo $claimProgress; ?>%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between text-xxs text-white-50 mt-1">
                        <span><?php echo $claimProgress; ?>% Tercapai</span>
                        <span><?php echo ($isClaimEligible ? 'Target Terpenuhi!' : "Kurang $sisaTarget Unit"); ?></span>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs & Universal Search -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="filter-tabs mb-0">
                    <button class="tab-btn active" onclick="filterTable('all', this)">
                        <i class="fa-solid fa-list"></i> Semua Penitipan <span class="tab-badge" id="badgeCountAll">0</span>
                    </button>
                    <button class="tab-btn" onclick="filterTable('aktif', this)">
                        <i class="fa-solid fa-boxes-packing text-success"></i> Stok Aktif <span class="tab-badge" id="badgeCountAktif">0</span>
                    </button>
                    <button class="tab-btn" onclick="filterTable('terjual', this)">
                        <i class="fa-solid fa-receipt text-warning"></i> Ada Penjualan <span class="tab-badge" id="badgeCountTerjual">0</span>
                    </button>
                    <button class="tab-btn" onclick="filterTable('selesai', this)">
                        <i class="fa-solid fa-clock-rotate-left"></i> Selesai / Ditarik <span class="tab-badge" id="badgeCountSelesai">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchViewToClaims()">
                        <i class="fa-solid fa-hand-holding-dollar text-primary"></i> Tab Klaim Insentif
                    </button>
                </div>

                <!-- Live Search Bar -->
                <div class="position-relative" style="min-width: 280px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 12px; font-size: 0.85rem;"></i>
                    <input type="text" id="tiptokSearchInput" class="form-control-custom w-100 ps-5" placeholder="Cari nama toko, alamat, invoice, barang..." onkeyup="searchTiptokTable()">
                </div>
            </div>

            <!-- View 1: Main Table Penitipan -->
            <div id="viewPenitipanTable" class="content-card">
                <div class="table-responsive">
                    <table class="table table-custom" id="mainTiptokTable">
                        <thead>
                            <tr>
                                <th style="width: 5%;">NO</th>
                                <th style="width: 25%;">TOKO / DEALER</th>
                                <th style="width: 15%;">KODE & TGL TITIP</th>
                                <th style="width: 25%;">BARANG & MONITORING STOK</th>
                                <th style="width: 12%;">INVOICE & INSENTIF</th>
                                <th style="width: 8%;">STATUS</th>
                                <th style="width: 10%; text-align: center;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $countAll = 0;
                            $countAktif = 0;
                            $countTerjual = 0;
                            $countSelesai = 0;

                            if ($resPenitipan && $resPenitipan->num_rows > 0) {
                                while ($row = $resPenitipan->fetch_assoc()) {
                                    $countAll++;
                                    $idPen = $row['id'];
                                    $statusPen = $row['status'];
                                    $sumSisa = intval($row['sum_sisa']);
                                    $sumTerjual = intval($row['sum_terjual']);
                                    $sumTitip = intval($row['sum_titip']);
                                    $sumInsentif = floatval($row['sum_insentif']);

                                    if ($statusPen === 'aktif' && $sumSisa > 0) $countAktif++;
                                    if ($sumTerjual > 0) $countTerjual++;
                                    if ($statusPen === 'selesai' || $statusPen === 'ditarik' || $sumSisa === 0) $countSelesai++;

                                    // Ambil barang-barang dalam penitipan ini
                                    $qItems = $conn->query("SELECT * FROM tiptok_items WHERE id_penitipan = $idPen ORDER BY id ASC");
                                    $itemList = [];
                                    while ($it = $qItems->fetch_assoc()) {
                                        $itemList[] = $it;
                                    }

                                    // Filter category string for JS filter
                                    $filterCat = 'all';
                                    if ($statusPen === 'aktif' && $sumSisa > 0) $filterCat .= ' aktif';
                                    if ($sumTerjual > 0) $filterCat .= ' terjual';
                                    if ($statusPen === 'selesai' || $statusPen === 'ditarik' || $sumSisa === 0) $filterCat .= ' selesai';

                                    // Format no telp untuk WA
                                    $telpRaw = preg_replace('/\D/', '', $row['telp_toko'] ?? '');
                                    if (substr($telpRaw, 0, 1) === '0') $telpRaw = '62' . substr($telpRaw, 1);
                                    ?>
                                    <tr class="tiptok-row" data-category="<?php echo $filterCat; ?>">
                                        <td class="text-center font-weight-bold text-muted"><?php echo $no++; ?></td>
                                        
                                        <!-- Toko / Dealer -->
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 mt-1" style="min-width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa-solid fa-store" style="font-size: 0.85rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="font-weight-bold text-dark text-sm"><?php echo htmlspecialchars($row['nama_toko'] ?? 'Toko Tidak Ditemukan'); ?></span>
                                                        <span class="badge-pill badge-dealer"><?php echo htmlspecialchars($row['kategori_customer'] ?? 'Dealer'); ?></span>
                                                    </div>
                                                    
                                                    <div class="text-xs text-muted mt-1" style="line-height: 1.4;">
                                                        <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                                        <?php echo htmlspecialchars($row['alamat_toko'] ?? '-'); ?>, <?php echo htmlspecialchars($row['kota_toko'] ?? ''); ?>
                                                    </div>

                                                    <?php if (!empty($telpRaw)) : ?>
                                                        <div class="mt-1">
                                                            <a href="https://wa.me/<?php echo $telpRaw; ?>" target="_blank" class="text-success text-xxs font-weight-bold text-decoration-none">
                                                                <i class="fa-brands fa-whatsapp me-1"></i><?php echo htmlspecialchars($row['telp_toko']); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kode & Tanggal Titip -->
                                        <td>
                                            <span class="badge-pill bg-light text-dark font-monospace mb-1">
                                                <i class="fa-solid fa-hashtag text-primary me-1"></i><?php echo htmlspecialchars($row['kode_titip']); ?>
                                            </span>
                                            <div class="text-xs text-muted">
                                                <i class="fa-solid fa-calendar text-secondary me-1"></i><?php echo date('d M Y', strtotime($row['tgl_titip'])); ?>
                                            </div>
                                            <div class="text-xxs text-muted mt-1">
                                                <i class="fa-solid fa-user-tie me-1"></i>Sales: <strong><?php echo htmlspecialchars($row['nama_sales'] ?? 'Sales'); ?></strong>
                                            </div>
                                        </td>

                                        <!-- Daftar Barang & Stok -->
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <?php foreach ($itemList as $it) : 
                                                    $sisa = intval($it['qty_sisa']);
                                                    $terjual = intval($it['qty_terjual']);
                                                    $titip = intval($it['qty_titip']);
                                                    $insPerUnit = floatval($it['insentif_per_unit']);
                                                ?>
                                                    <div class="item-chip">
                                                        <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($it['nama_barang']); ?></span>
                                                        <span class="text-xxs text-muted">(Insentif: Rp <?php echo number_format($insPerUnit, 0, ',', '.'); ?>/unit)</span>
                                                        <span class="ms-auto stock-counter stock-sisa" title="Sisa Stok di Toko">Sisa: <?php echo $sisa; ?></span>
                                                        <?php if ($terjual > 0) : ?>
                                                            <span class="stock-counter stock-terjual" title="Unit Terjual">Laku: <?php echo $terjual; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>

                                        <!-- Invoice & Estimasi Insentif -->
                                        <td>
                                            <?php if (!empty($row['last_no_inv'])) : ?>
                                                <span class="badge-pill badge-invoice mb-1">
                                                    <i class="fa-solid fa-file-invoice me-1"></i><?php echo htmlspecialchars($row['last_no_inv']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xxs text-muted fst-italic">Belum ada invoice</span>
                                            <?php endif; ?>

                                            <div class="text-xs font-weight-bold text-success mt-1">
                                                Rp <?php echo number_format($sumInsentif, 0, ',', '.'); ?>
                                            </div>
                                            <div class="text-xxs text-muted">
                                                (Total Terjual: <?php echo $sumTerjual; ?> unit)
                                            </div>
                                        </td>

                                        <!-- Status Penitipan -->
                                        <td>
                                            <?php if ($statusPen === 'aktif' && $sumSisa > 0) : ?>
                                                <span class="badge-pill badge-active"><i class="fa-solid fa-circle text-success" style="font-size: 6px;"></i> Aktif</span>
                                            <?php elseif ($statusPen === 'selesai' || $sumSisa === 0) : ?>
                                                <span class="badge-pill badge-selesai"><i class="fa-solid fa-check text-secondary"></i> Selesai</span>
                                            <?php else : ?>
                                                <span class="badge-pill badge-ditarik"><i class="fa-solid fa-ban text-danger"></i> Ditarik</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="text-center">
                                            <div class="d-flex flex-column gap-1">
                                                <?php if ($statusPen === 'aktif' && $sumSisa > 0) : ?>
                                                    <button class="btn-action-primary w-100 justify-content-center" onclick="openModalLaporKunjungan(<?php echo $idPen; ?>)">
                                                        <i class="fa-solid fa-clipboard-check"></i> Cek Sisa
                                                    </button>
                                                <?php endif; ?>

                                                <button class="btn-action-secondary w-100 justify-content-center" onclick="openModalDetailTiptok(<?php echo $idPen; ?>)">
                                                    <i class="fa-solid fa-eye"></i> Detail
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                        <p class="mb-2 font-weight-bold">Belum Ada Data Penitipan Barang di Toko (TIP TOK)</p>
                                        <button class="btn btn-sm btn-primary" onclick="openModalTambahPenitipan()">
                                            <i class="fa-solid fa-plus me-1"></i> Buat Penitipan Baru Sekarang
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 2: Tab Khusus Klaim Insentif (Min 50 Unit) -->
            <div id="viewKlaimInsentif" class="content-card d-none p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <h4 class="font-weight-bold text-dark mb-1"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Klaim Insentif Penjualan Toko</h4>
                        <p class="text-muted text-xs mb-0">Akumulasi unit terjual dari seluruh kunjungan toko mitra. Syarat klaim minimal <strong>50 Unit Terjual</strong>.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="switchViewToTable()">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Data Penitipan
                    </button>
                </div>

                <!-- Bento Info Status Klaim -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="p-3 rounded-3 border bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-xs font-weight-bold text-uppercase text-muted">Unit Terjual Siap Klaim</span>
                                <span class="badge <?php echo $isClaimEligible ? 'bg-success' : 'bg-warning text-dark'; ?> text-xs">
                                    <?php echo $isClaimEligible ? 'SYARAT TERPENUHI (>= 50)' : 'BELUM MEMENUHI SYARAT (< 50)'; ?>
                                </span>
                            </div>
                            <h3 class="font-weight-bolder text-dark mb-1"><?php echo $unclaimedUnits; ?> <span class="text-sm font-weight-normal text-muted">/ 50 Unit Minimal</span></h3>
                            <div class="progress mb-2" style="height: 10px; border-radius: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $claimProgress; ?>%"></div>
                            </div>
                            <div class="text-xs text-muted">
                                <?php if ($isClaimEligible) : ?>
                                    <span class="text-success font-weight-bold"><i class="fa-solid fa-check-circle me-1"></i>Selamat! Anda telah memenuhi syarat 50 unit terjual dan siap mengajukan klaim.</span>
                                <?php else : ?>
                                    <span class="text-danger font-weight-bold"><i class="fa-solid fa-info-circle me-1"></i>Perlu <?php echo $sisaTarget; ?> unit terjual lagi untuk dapat mengajukan klaim insentif.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="p-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <span class="text-xs font-weight-bold text-uppercase text-muted">Total Nominal Insentif Siap Cair</span>
                                <h2 class="font-weight-bolder text-success mb-1">Rp <?php echo number_format($unclaimedNominal, 0, ',', '.'); ?></h2>
                                <p class="text-xs text-muted mb-0">Total akumulasi rupiah dari seluruh unit barang yang telah terverifikasi dengan No. Invoice.</p>
                            </div>
                            <div class="mt-3">
                                <?php if ($isClaimEligible) : ?>
                                    <button class="btn btn-success w-100 font-weight-bold py-2" onclick="openModalSubmitClaim()">
                                        <i class="fa-solid fa-paper-plane me-2"></i>Ajukan Klaim Insentif Sekarang
                                    </button>
                                <?php else : ?>
                                    <button class="btn btn-secondary w-100 font-weight-bold py-2" disabled>
                                        <i class="fa-solid fa-lock me-2"></i>Klaim Terkunci (Min. 50 Unit)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Rincian Unit Terjual yang Belum Diklaim -->
                <h6 class="font-weight-bold text-dark mb-3"><i class="fa-solid fa-receipt text-primary me-2"></i>Rincian Unit Terjual yang Belum Masuk Klaim</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm text-sm" id="tableUnclaimedItems">
                        <thead class="table-light">
                            <tr>
                                <th>TGL KUNJUNGAN</th>
                                <th>TOKO / DEALER</th>
                                <th>NAMA BARANG</th>
                                <th>NO. INVOICE</th>
                                <th class="text-center">QTY TERJUAL</th>
                                <th class="text-end">INSENTIF / UNIT</th>
                                <th class="text-end">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="bodyUnclaimedItems">
                            <tr><td colspan="7" class="text-center py-3 text-muted">Memuat data rincian unit...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Riwayat Pengajuan Klaim Insentif -->
                <h6 class="font-weight-bold text-dark mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Riwayat Pengajuan Klaim Insentif</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-sm" id="tableClaimHistory">
                        <thead class="table-light">
                            <tr>
                                <th>KODE KLAIM</th>
                                <th>NAMA SALES</th>
                                <th>TGL KLAIM</th>
                                <th class="text-center">TOTAL UNIT</th>
                                <th class="text-end">NOMINAL (RP)</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="bodyClaimHistory">
                            <tr><td colspan="7" class="text-center py-3 text-muted">Memuat riwayat klaim...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php include "footer.php"; ?>
    </main>

    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH PENITIPAN BARU                                           -->
    <!-- ========================================================================= -->
    <div class="modal fade modal-custom" id="modalTambahPenitipan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0">
                            <i class="fa-solid fa-boxes-packing text-primary me-2"></i>Titip Barang Baru di Toko (TIP TOK)
                        </h5>
                        <small class="text-muted">Daftarkan barang yang dititipkan (konsinyasi) ke toko mitra / dealer</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formTambahPenitipan" onsubmit="submitTambahPenitipan(event)">
                    <div class="modal-body p-4">
                        
                        <!-- Toko Dealer Selection -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Pilih Toko / Dealer Tujuan <span class="text-danger">*</span></label>
                                <select name="id_customer" id="selectDealer" class="form-control-custom w-100" required onchange="onDealerSelected()">
                                    <option value="">-- Cari / Pilih Toko Customer --</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Tanggal Penitipan <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_titip" class="form-control-custom w-100" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <!-- Store details preview -->
                        <div id="dealerPreview" class="p-3 mb-3 rounded-3 bg-light border d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-dark text-sm" id="prevNamaToko">-</span>
                                <span class="badge bg-primary text-xxs" id="prevKategoriToko">Dealer</span>
                            </div>
                            <div class="text-xs text-muted mt-1" id="prevAlamatToko">-</div>
                            <div class="text-xs text-success mt-1" id="prevTelpToko">-</div>
                        </div>

                        <!-- Multi-item Rows -->
                        <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                            <label class="form-label font-weight-bold text-xs text-uppercase text-dark mb-0">
                                <i class="fa-solid fa-list-check me-1 text-primary"></i> Daftar Barang yang Dititipkan <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-xs btn-outline-primary mb-0 font-weight-bold" onclick="tambahBarisBarang()">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Barang
                            </button>
                        </div>

                        <div id="containerItemRows">
                            <!-- Rows will be dynamically injected here -->
                        </div>

                        <!-- Catatan -->
                        <div class="mt-3">
                            <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Catatan Penitipan / Perjanjian (Opsional)</label>
                            <textarea name="catatan" class="form-control-custom w-100" rows="2" placeholder="Contoh: Barang dititip display toko 30 hari, tagihan dibayar jika laku..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-secondary mb-0 px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnSimpanPenitipan" class="btn btn-primary mb-0 px-4 font-weight-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Penitipan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: LAPORAN KUNJUNGAN & CEK STOK SISA                                -->
    <!-- ========================================================================= -->
    <div class="modal fade modal-custom" id="modalLaporKunjungan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0">
                            <i class="fa-solid fa-clipboard-check text-success me-2"></i>Laporan Kunjungan & Cek Stok Sisa
                        </h5>
                        <small class="text-muted">Input kondisi sisa fisik barang di toko. Wajib input No. Invoice jika ada barang terjual!</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formLaporKunjungan" onsubmit="submitLaporKunjungan(event)" enctype="multipart/form-data">
                    <input type="hidden" name="id_penitipan" id="kunjunganIdPenitipan">
                    <div class="modal-body p-4">
                        
                        <div class="p-3 mb-3 rounded-3 bg-light border">
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-dark" id="kunjunganNamaToko">-</span>
                                <span class="badge bg-dark font-monospace text-xxs" id="kunjunganKodeTitip">-</span>
                            </div>
                            <div class="text-xs text-muted mt-1" id="kunjunganAlamatToko">-</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Tanggal Kunjungan / Audit <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_kunjungan" class="form-control-custom w-100" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Foto Bukti Display / Stok (Opsional)</label>
                                <input type="file" name="foto_kunjungan" class="form-control-custom w-100" accept="image/*">
                            </div>
                        </div>

                        <!-- Cek Stok Sisa Table -->
                        <h6 class="font-weight-bold text-dark text-xs text-uppercase mt-4 mb-2">
                            <i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Audit Fisik Stok Sisa & Penjualan
                        </h6>
                        <div class="table-responsive border rounded-3 bg-white mb-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-xs text-uppercase">
                                        <th style="width: 30%;">NAMA BARANG</th>
                                        <th style="width: 15%; text-align: center;">STOK SEBELUMNYA</th>
                                        <th style="width: 18%;">STOK SISA FISIK <span class="text-danger">*</span></th>
                                        <th style="width: 12%; text-align: center;">TERJUAL</th>
                                        <th style="width: 25%;">NO. INVOICE <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody id="kunjunganItemsBody">
                                    <!-- Dynamic Rows per Item -->
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info py-2 px-3 text-xs text-dark border-0 bg-blue-50 d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-circle-info text-primary fa-lg"></i>
                            <div>
                                <strong>Catatan Validasi:</strong> Jika ada unit terjual (Stok Sisa < Stok Sebelumnya), Anda <strong>WAJIB</strong> mengisi No. Invoice untuk klaim insentif.
                            </div>
                        </div>

                        <div>
                            <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Catatan Hasil Kunjungan / Toko</label>
                            <textarea name="catatan_kunjungan" class="form-control-custom w-100" rows="2" placeholder="Tuliskan respon toko, rencana restock, atau kendala..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-secondary mb-0 px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnSimpanKunjungan" class="btn btn-success mb-0 px-4 font-weight-bold">
                            <i class="fa-solid fa-check-circle me-1"></i> Simpan Laporan Kunjungan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: DETAIL LENGKAP & RIWAYAT KUNJUNGAN TOKO                          -->
    <!-- ========================================================================= -->
    <div class="modal fade modal-custom" id="modalDetailTiptok" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0">
                            <i class="fa-solid fa-circle-info text-primary me-2"></i>Detail Konsinyasi & Histori Audit Toko
                        </h5>
                        <small class="text-muted" id="detailKodeTitip">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div id="detailLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted text-xs mt-2">Memuat riwayat konsinyasi...</p>
                    </div>

                    <div id="detailContent" class="d-none">
                        <!-- Toko Info Banner -->
                        <div class="p-3 mb-4 rounded-3 bg-light border">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="font-weight-bold text-dark mb-1" id="detNamaToko">-</h5>
                                    <div class="text-xs text-muted" id="detAlamatToko">-</div>
                                    <div class="text-xs text-success mt-1" id="detTelpToko">-</div>
                                </div>
                                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                    <span class="badge bg-primary text-xs font-monospace" id="detKodeTitip">-</span>
                                    <div class="text-xs text-muted mt-1">Tgl Titip: <strong id="detTglTitip">-</strong></div>
                                    <div class="text-xs text-muted">Sales: <strong id="detNamaSales">-</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Stok Saat Ini -->
                        <h6 class="font-weight-bold text-dark text-xs text-uppercase mb-2"><i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Rincian Stok Barang</h6>
                        <div class="table-responsive border rounded-3 mb-4">
                            <table class="table table-sm mb-0">
                                <thead class="table-light text-xs text-uppercase">
                                    <tr>
                                        <th>NAMA BARANG</th>
                                        <th class="text-center">STOK AWAL</th>
                                        <th class="text-center">SISA STOK</th>
                                        <th class="text-center">TERJUAL</th>
                                        <th class="text-end">INSENTIF / UNIT</th>
                                        <th class="text-end">TOTAL INSENTIF</th>
                                    </tr>
                                </thead>
                                <tbody id="detItemsBody"></tbody>
                            </table>
                        </div>

                        <!-- Timeline Riwayat Kunjungan -->
                        <h6 class="font-weight-bold text-dark text-xs text-uppercase mb-2"><i class="fa-solid fa-clock-rotate-left text-primary me-1"></i> Riwayat Kunjungan & Laporan Sisa Stok</h6>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light text-xs text-uppercase">
                                    <tr>
                                        <th>TGL KUNJUNGAN</th>
                                        <th>SALES</th>
                                        <th>BARANG DIAUDIT</th>
                                        <th class="text-center">SISA FISIK</th>
                                        <th class="text-center">TERJUAL</th>
                                        <th>NO. INVOICE</th>
                                        <th class="text-end">INSENTIF</th>
                                        <th>CATATAN</th>
                                    </tr>
                                </thead>
                                <tbody id="detLogsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: PENGAJUAN KLAIM INSENTIF (MIN 50 UNIT)                           -->
    <!-- ========================================================================= -->
    <div class="modal fade modal-custom" id="modalSubmitClaim" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0">
                            <i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Ajukan Klaim Insentif
                        </h5>
                        <small class="text-muted">Konfirmasi pengajuan klaim insentif minimal 50 unit</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formSubmitClaim" onsubmit="submitKlaimInsentif(event)">
                    <div class="modal-body p-4">
                        <div class="p-3 mb-3 rounded-3 bg-emerald-50 border border-success text-center">
                            <div class="text-xs text-success font-weight-bold text-uppercase">Total Unit Siap Klaim</div>
                            <h2 class="font-weight-bolder text-success my-1"><?php echo $unclaimedUnits; ?> Unit</h2>
                            <div class="text-sm font-weight-bold text-dark">Estimasi Nominal: Rp <?php echo number_format($unclaimedNominal, 0, ',', '.'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-xs text-uppercase text-dark">Catatan Pengajuan Klaim (Opsional)</label>
                            <textarea name="catatan_claim" class="form-control-custom w-100" rows="3" placeholder="Contoh: Pengajuan klaim periode penjualan bulan ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-secondary mb-0 px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnProsesClaim" class="btn btn-success mb-0 px-4 font-weight-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan Klaim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 5: DETAIL KLAIM & APPROVAL (ADMIN / MANAGER)                         -->
    <!-- ========================================================================= -->
    <div class="modal fade modal-custom" id="modalClaimApproval" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0">
                            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Rincian Pengajuan Klaim Insentif
                        </h5>
                        <small class="text-muted" id="claimKodeTitle">-</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="p-3 mb-3 rounded-3 bg-light border">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="font-weight-bold text-dark mb-0" id="claimSalesName">-</h6>
                                <span class="text-xs text-muted" id="claimTgl">-</span>
                            </div>
                            <div class="text-end">
                                <span class="badge" id="claimStatusBadge">-</span>
                                <h5 class="font-weight-bolder text-success mt-1 mb-0" id="claimNominal">-</h5>
                            </div>
                        </div>
                    </div>

                    <h6 class="font-weight-bold text-dark text-xs text-uppercase mb-2"><i class="fa-solid fa-list text-primary me-1"></i> Detail Item Penjualan dalam Klaim</h6>
                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-sm mb-0">
                            <thead class="table-light text-xs text-uppercase">
                                <tr>
                                    <th>TOKO DEALER</th>
                                    <th>NAMA BARANG</th>
                                    <th>NO. INVOICE</th>
                                    <th class="text-center">QTY</th>
                                    <th class="text-end">INSENTIF / UNIT</th>
                                    <th class="text-end">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody id="claimDetailItemsBody"></tbody>
                        </table>
                    </div>

                    <?php if ($role === 'Super Admin' || $role === 'Admin' || $role === 'Sales Manager') : ?>
                        <div class="p-3 rounded-3 bg-light border mt-3">
                            <h6 class="font-weight-bold text-dark text-xs text-uppercase mb-2"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Proses Persetujuan Klaim</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label text-xs font-weight-bold text-uppercase text-dark">Ubah Status</label>
                                    <select id="updateClaimStatusSelect" class="form-control-custom w-100">
                                        <option value="menunggu_approval">Menunggu Approval</option>
                                        <option value="disetujui">Disetujui</option>
                                        <option value="cair">Cair (Selesai Dibayarkan)</option>
                                        <option value="ditolak">Ditolak</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-xs font-weight-bold text-uppercase text-dark">Catatan Admin / Payout</label>
                                    <input type="text" id="updateClaimAdminNote" class="form-control-custom w-100" placeholder="No referensi transfer / catatan...">
                                </div>
                            </div>
                            <div class="text-end mt-2">
                                <button class="btn btn-primary btn-sm font-weight-bold mb-0" onclick="submitUpdateClaimStatus()">
                                    <i class="fa-solid fa-check me-1"></i> Simpan Status Klaim
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer bg-light border-top p-3">
                    <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript & Logic -->
    <script>
        let dealersList = [];
        let currentLoadedPenitipan = null;
        let currentClaimId = 0;

        // Init page & load dealer customer dropdown
        document.addEventListener('DOMContentLoaded', function() {
            updateBadgeCounts();
            loadDealers();
            tambahBarisBarang(); // Baris pertama default
        });

        function updateBadgeCounts() {
            document.getElementById('badgeCountAll').textContent = '<?php echo $countAll; ?>';
            document.getElementById('badgeCountAktif').textContent = '<?php echo $countAktif; ?>';
            document.getElementById('badgeCountTerjual').textContent = '<?php echo $countTerjual; ?>';
            document.getElementById('badgeCountSelesai').textContent = '<?php echo $countSelesai; ?>';
        }

        // 1. Filter Table by Category
        function filterTable(category, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const rows = document.querySelectorAll('#mainTiptokTable tbody tr.tiptok-row');
            rows.forEach(row => {
                const cats = row.getAttribute('data-category') || '';
                if (category === 'all' || cats.includes(category)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // 2. Search in table
        function searchTiptokTable() {
            const query = document.getElementById('tiptokSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#mainTiptokTable tbody tr.tiptok-row');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // 3. Switch between Main Table & Claim Tab
        function switchViewToClaims() {
            document.getElementById('viewPenitipanTable').classList.add('d-none');
            document.getElementById('viewKlaimInsentif').classList.remove('d-none');
            loadClaimSummary();
        }

        function switchViewToTable() {
            document.getElementById('viewKlaimInsentif').classList.add('d-none');
            document.getElementById('viewPenitipanTable').classList.remove('d-none');
        }

        function openTabKlaimInsentif() {
            switchViewToClaims();
        }

        // 4. Load Dealers for Autocomplete
        function loadDealers() {
            fetch('tiptok-ajax.php?action=search_dealer')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        dealersList = res.data;
                        const sel = document.getElementById('selectDealer');
                        sel.innerHTML = '<option value="">-- Pilih Toko Customer / Dealer --</option>';
                        dealersList.forEach(d => {
                            const katBadge = d.kategori ? `[${d.kategori}] ` : '';
                            sel.innerHTML += `<option value="${d.id}">${katBadge}${d.nama} - ${d.kota || ''}</option>`;
                        });
                    }
                });
        }

        function onDealerSelected() {
            const id = document.getElementById('selectDealer').value;
            const dealer = dealersList.find(d => d.id == id);
            const prev = document.getElementById('dealerPreview');
            if (dealer) {
                document.getElementById('prevNamaToko').textContent = dealer.nama;
                document.getElementById('prevKategoriToko').textContent = dealer.kategori || 'Dealer';
                document.getElementById('prevAlamatToko').textContent = (dealer.alamat || '') + (dealer.kota ? ', ' + dealer.kota : '');
                document.getElementById('prevTelpToko').textContent = dealer.telp_pribadi ? 'WA / Telp: ' + dealer.telp_pribadi : '';
                prev.classList.remove('d-none');
            } else {
                prev.classList.add('d-none');
            }
        }

        // 5. Dynamic Items Rows for Tambah Penitipan
        let itemRowIndex = 0;
        function tambahBarisBarang() {
            itemRowIndex++;
            const container = document.getElementById('containerItemRows');
            const rowHtml = `
                <div class="item-input-row" id="itemRow_${itemRowIndex}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary text-xxs font-weight-bold">Item #${itemRowIndex}</span>
                        <button type="button" class="btn btn-xs btn-link text-danger p-0 mb-0 font-weight-bold" onclick="hapusBarisBarang(${itemRowIndex})">
                            <i class="fa-solid fa-trash-can me-1"></i> Hapus Baris
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label text-xxs font-weight-bold text-dark mb-1">NAMA BARANG / MODEL <span class="text-danger">*</span></label>
                            <input type="text" name="items[${itemRowIndex}][nama_barang]" class="form-control-custom w-100" placeholder="Contoh: CCTV Loewix 2MP Outdoor" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xxs font-weight-bold text-dark mb-1">TIPE / KATEGORI</label>
                            <input type="text" name="items[${itemRowIndex}][tipe_barang]" class="form-control-custom w-100" placeholder="CCTV / NVR / DVR">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xxs font-weight-bold text-dark mb-1">QTY TITIP <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemRowIndex}][qty_titip]" min="1" class="form-control-custom w-100" placeholder="Jml" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xxs font-weight-bold text-dark mb-1">INSENTIF PER UNIT (RP) <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemRowIndex}][insentif_per_unit]" min="0" step="500" class="form-control-custom w-100" placeholder="Contoh: 15000" required>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        function hapusBarisBarang(idx) {
            const el = document.getElementById(`itemRow_${idx}`);
            if (el) el.remove();
        }

        function openModalTambahPenitipan() {
            document.getElementById('formTambahPenitipan').reset();
            document.getElementById('dealerPreview').classList.add('d-none');
            document.getElementById('containerItemRows').innerHTML = '';
            itemRowIndex = 0;
            tambahBarisBarang();
            new bootstrap.Modal(document.getElementById('modalTambahPenitipan')).show();
        }

        function submitTambahPenitipan(e) {
            e.preventDefault();
            const form = document.getElementById('formTambahPenitipan');
            const formData = new FormData(form);
            formData.append('action', 'simpan_penitipan');

            const btn = document.getElementById('btnSimpanPenitipan');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...';

            fetch('tiptok-ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Penitipan';

                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', html: res.message });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Penitipan';
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
                });
        }

        // 6. Modal Lapor Kunjungan & Cek Sisa
        function openModalLaporKunjungan(idPenitipan) {
            document.getElementById('kunjunganIdPenitipan').value = idPenitipan;
            const tbody = document.getElementById('kunjunganItemsBody');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat barang...</td></tr>';

            new bootstrap.Modal(document.getElementById('modalLaporKunjungan')).show();

            fetch(`tiptok-ajax.php?action=get_detail&id=${idPenitipan}`)
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const m = res.data.master;
                        document.getElementById('kunjunganNamaToko').textContent = m.nama_toko;
                        document.getElementById('kunjunganKodeTitip').textContent = m.kode_titip;
                        document.getElementById('kunjunganAlamatToko').textContent = m.alamat_toko + (m.kota_toko ? ', ' + m.kota_toko : '');

                        tbody.innerHTML = '';
                        res.data.items.forEach((it, idx) => {
                            const sisaCur = parseInt(it.qty_sisa);
                            tbody.innerHTML += `
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[${idx}][id_item]" value="${it.id}">
                                        <strong>${it.nama_barang}</strong>
                                        <div class="text-xxs text-muted">Insentif: Rp ${new Intl.NumberFormat('id-ID').format(it.insentif_per_unit)}/unit</div>
                                    </td>
                                    <td class="text-center font-weight-bold text-dark">
                                        <span class="badge bg-light text-dark font-monospace">${sisaCur} Unit</span>
                                    </td>
                                    <td>
                                        <input type="number" name="items[${idx}][stok_sisa]" 
                                               id="stokSisa_${idx}" 
                                               min="0" max="${sisaCur}" 
                                               class="form-control-custom w-100 text-center" 
                                               value="${sisaCur}" 
                                               required 
                                               oninput="hitungTerjualRow(${idx}, ${sisaCur})">
                                    </td>
                                    <td class="text-center font-weight-bold" id="terjualDisplay_${idx}">
                                        <span class="badge bg-light text-muted">0</span>
                                    </td>
                                    <td>
                                        <input type="text" name="items[${idx}][no_inv]" 
                                               id="noInv_${idx}" 
                                               class="form-control-custom w-100 font-monospace text-xs" 
                                               placeholder="Wajib jika laku...">
                                    </td>
                                </tr>
                            `;
                        });
                    }
                });
        }

        function hitungTerjualRow(idx, stokPrev) {
            const valInput = document.getElementById(`stokSisa_${idx}`).value;
            const sisa = parseInt(valInput) || 0;
            const terjual = Math.max(0, stokPrev - sisa);
            const disp = document.getElementById(`terjualDisplay_${idx}`);
            const inv = document.getElementById(`noInv_${idx}`);

            if (terjual > 0) {
                disp.innerHTML = `<span class="badge bg-danger">${terjual} Laku</span>`;
                inv.setAttribute('required', 'required');
                inv.classList.add('border-danger');
            } else {
                disp.innerHTML = `<span class="badge bg-light text-muted">0</span>`;
                inv.removeAttribute('required');
                inv.classList.remove('border-danger');
            }
        }

        function submitLaporKunjungan(e) {
            e.preventDefault();
            const form = document.getElementById('formLaporKunjungan');
            const formData = new FormData(form);
            formData.append('action', 'simpan_kunjungan');

            const btn = document.getElementById('btnSimpanKunjungan');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...';

            fetch('tiptok-ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Simpan Laporan Kunjungan';

                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Laporan Tersimpan!',
                            html: res.message,
                            confirmButtonText: 'OK'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: res.message });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Simpan Laporan Kunjungan';
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
                });
        }

        // 7. Modal Detail & Riwayat Kunjungan
        function openModalDetailTiptok(idPenitipan) {
            document.getElementById('detailLoading').classList.remove('d-none');
            document.getElementById('detailContent').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('modalDetailTiptok')).show();

            fetch(`tiptok-ajax.php?action=get_detail&id=${idPenitipan}`)
                .then(r => r.json())
                .then(res => {
                    document.getElementById('detailLoading').classList.add('d-none');
                    if (res.status === 'success') {
                        document.getElementById('detailContent').classList.remove('d-none');
                        const m = res.data.master;
                        document.getElementById('detailKodeTitip').textContent = 'Kode: ' + m.kode_titip;
                        document.getElementById('detNamaToko').textContent = m.nama_toko;
                        document.getElementById('detAlamatToko').textContent = m.alamat_toko + (m.kota_toko ? ', ' + m.kota_toko : '');
                        document.getElementById('detTelpToko').textContent = m.telp_toko ? 'WA / Telp: ' + m.telp_toko : '';
                        document.getElementById('detKodeTitip').textContent = m.kode_titip;
                        document.getElementById('detTglTitip').textContent = m.tgl_titip;
                        document.getElementById('detNamaSales').textContent = m.nama_sales || 'Sales';

                        // Render items
                        const itemBody = document.getElementById('detItemsBody');
                        itemBody.innerHTML = '';
                        res.data.items.forEach(it => {
                            itemBody.innerHTML += `
                                <tr>
                                    <td><strong>${it.nama_barang}</strong> <span class="text-xxs text-muted">(${it.tipe_barang || '-'})</span></td>
                                    <td class="text-center">${it.qty_titip}</td>
                                    <td class="text-center font-weight-bold text-success">${it.qty_sisa}</td>
                                    <td class="text-center font-weight-bold text-danger">${it.qty_terjual}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(it.insentif_per_unit)}</td>
                                    <td class="text-end font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(it.total_insentif)}</td>
                                </tr>
                            `;
                        });

                        // Render Logs
                        const logBody = document.getElementById('detLogsBody');
                        logBody.innerHTML = '';
                        if (res.data.logs.length === 0) {
                            logBody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Belum ada riwayat kunjungan audit.</td></tr>';
                        } else {
                            res.data.logs.forEach(l => {
                                const invBadge = l.no_inv ? `<span class="badge-pill badge-invoice">${l.no_inv}</span>` : '-';
                                logBody.innerHTML += `
                                    <tr>
                                        <td>${l.tgl_kunjungan}</td>
                                        <td>${l.nama_sales || '-'}</td>
                                        <td><strong>${l.nama_barang}</strong></td>
                                        <td class="text-center">${l.stok_sisa}</td>
                                        <td class="text-center font-weight-bold text-danger">${l.qty_terjual_kunjungan}</td>
                                        <td>${invBadge}</td>
                                        <td class="text-end font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(l.insentif_didapat)}</td>
                                        <td class="text-xs">${l.catatan_kunjungan || '-'}</td>
                                    </tr>
                                `;
                            });
                        }
                    }
                });
        }

        // 8. Load Claim Summary (Min 50 Unit)
        function loadClaimSummary() {
            fetch('tiptok-ajax.php?action=get_claim_summary')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        const bodyUnclaimed = document.getElementById('bodyUnclaimedItems');
                        bodyUnclaimed.innerHTML = '';

                        if (d.unclaimed_items.length === 0) {
                            bodyUnclaimed.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada unit terjual yang menunggu klaim.</td></tr>';
                        } else {
                            d.unclaimed_items.forEach(u => {
                                bodyUnclaimed.innerHTML += `
                                    <tr>
                                        <td>${u.tgl_kunjungan}</td>
                                        <td><strong>${u.nama_toko}</strong></td>
                                        <td>${u.nama_barang}</td>
                                        <td><span class="badge-pill badge-invoice">${u.no_inv || '-'}</span></td>
                                        <td class="text-center font-weight-bold text-danger">${u.qty_terjual_kunjungan}</td>
                                        <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(u.insentif_per_unit)}</td>
                                        <td class="text-end font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(u.insentif_didapat)}</td>
                                    </tr>
                                `;
                            });
                        }

                        // Claim History
                        const bodyClaim = document.getElementById('bodyClaimHistory');
                        bodyClaim.innerHTML = '';
                        if (d.claim_history.length === 0) {
                            bodyClaim.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">Belum ada riwayat pengajuan klaim.</td></tr>';
                        } else {
                            d.claim_history.forEach(c => {
                                let stBadge = 'bg-secondary';
                                if (c.status_claim === 'disetujui') stBadge = 'bg-info text-white';
                                else if (c.status_claim === 'cair') stBadge = 'bg-success';
                                else if (c.status_claim === 'menunggu_approval') stBadge = 'bg-warning text-dark';
                                else if (c.status_claim === 'ditolak') stBadge = 'bg-danger';

                                bodyClaim.innerHTML += `
                                    <tr>
                                        <td class="font-monospace font-weight-bold">${c.kode_claim}</td>
                                        <td>${c.nama_sales}</td>
                                        <td>${c.tgl_claim}</td>
                                        <td class="text-center font-weight-bold">${c.total_unit_terjual} Unit</td>
                                        <td class="text-end font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(c.total_nominal_insentif)}</td>
                                        <td class="text-center"><span class="badge ${stBadge} text-xxs">${c.status_claim.toUpperCase()}</span></td>
                                        <td class="text-center">
                                            <button class="btn btn-xs btn-outline-primary mb-0 font-weight-bold" onclick="openModalDetailClaim(${c.id})">
                                                <i class="fa-solid fa-eye me-1"></i> Rincian
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                    }
                });
        }

        // 9. Submit Claim Modal
        function openModalSubmitClaim() {
            new bootstrap.Modal(document.getElementById('modalSubmitClaim')).show();
        }

        function submitKlaimInsentif(e) {
            e.preventDefault();
            const form = document.getElementById('formSubmitClaim');
            const formData = new FormData(form);
            formData.append('action', 'ajukan_claim');

            const btn = document.getElementById('btnProsesClaim');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...';

            fetch('tiptok-ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan Klaim';

                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Klaim Diajukan!',
                            text: res.message,
                            confirmButtonText: 'OK'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', html: res.message });
                    }
                });
        }

        // 10. Detail & Approval Claim Modal
        function openModalDetailClaim(idClaim) {
            currentClaimId = idClaim;
            new bootstrap.Modal(document.getElementById('modalClaimApproval')).show();

            fetch(`tiptok-ajax.php?action=get_claim_detail&id_claim=${idClaim}`)
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const cl = res.data.claim;
                        document.getElementById('claimKodeTitle').textContent = 'Kode Klaim: ' + cl.kode_claim;
                        document.getElementById('claimSalesName').textContent = cl.nama_sales;
                        document.getElementById('claimTgl').textContent = 'Tgl Klaim: ' + cl.tgl_claim + ' (' + cl.total_unit_terjual + ' Unit)';
                        document.getElementById('claimNominal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(cl.total_nominal_insentif);

                        const badge = document.getElementById('claimStatusBadge');
                        badge.className = 'badge ' + (cl.status_claim === 'cair' ? 'bg-success' : (cl.status_claim === 'disetujui' ? 'bg-info' : 'bg-warning text-dark'));
                        badge.textContent = cl.status_claim.toUpperCase();

                        const selStatus = document.getElementById('updateClaimStatusSelect');
                        if (selStatus) selStatus.value = cl.status_claim;

                        const noteAdmin = document.getElementById('updateClaimAdminNote');
                        if (noteAdmin) noteAdmin.value = cl.catatan_admin || '';

                        const tbody = document.getElementById('claimDetailItemsBody');
                        tbody.innerHTML = '';
                        res.data.details.forEach(d => {
                            tbody.innerHTML += `
                                <tr>
                                    <td><strong>${d.nama_toko}</strong></td>
                                    <td>${d.nama_barang}</td>
                                    <td><span class="badge-pill badge-invoice">${d.no_inv || '-'}</span></td>
                                    <td class="text-center font-weight-bold">${d.qty_terjual}</td>
                                    <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(d.insentif_per_unit)}</td>
                                    <td class="text-end font-weight-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(d.subtotal_insentif)}</td>
                                </tr>
                            `;
                        });
                    }
                });
        }

        function submitUpdateClaimStatus() {
            const status = document.getElementById('updateClaimStatusSelect').value;
            const note = document.getElementById('updateClaimAdminNote').value;

            const formData = new FormData();
            formData.append('action', 'update_status_claim');
            formData.append('id_claim', currentClaimId);
            formData.append('status_claim', status);
            formData.append('catatan_admin', note);

            fetch('tiptok-ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Sukses', text: res.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                bootstrap.Modal.getInstance(document.getElementById('modalClaimApproval')).hide();
                                loadClaimSummary();
                            });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                });
        }
    </script>
</body>
</html>
