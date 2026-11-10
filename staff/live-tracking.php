<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

include_once "menu-access-helper.php";
if (!hasMenuAccess($conn, $idSesi, 'live_tracking', ($role == 'Super Admin' || $role == 'Admin'))) {
    header("Location: index-sa.php");
    exit;
}

$pageNow = "Live Tracking";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Live Tracking Kegiatan & Teknisi - Loewix</title>
  <?php include "head.php"; ?>
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Google Fonts Poppins & Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    body {
        font-family: 'Poppins', 'Inter', -apple-system, sans-serif !important;
    }

    /* Main Content full-height layout */
    .main-content {
        display: flex !important;
        flex-direction: column !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }

    /* Outer Card for Live Tracking */
    .live-tracking-wrapper {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        margin: 0 24px 24px 24px;
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        position: relative;
    }

    /* Left Drawer (Kegiatan List) */
    .tracking-drawer {
        width: 410px;
        min-width: 350px;
        max-width: 430px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e2e8f0;
        height: 100%;
        z-index: 10;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .tracking-drawer-header {
        padding: 14px 18px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 91, 34, 0.1);
        color: #ff5b22;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .live-pill {
        background: #ff5b22;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(255, 91, 34, 0.35);
    }

    .live-dot {
        width: 6px;
        height: 6px;
        background: #ffffff;
        border-radius: 50%;
        animation: livePulse 1.2s infinite;
    }

    @keyframes livePulse {
        0% { opacity: 0.3; }
        50% { opacity: 1; }
        100% { opacity: 0.3; }
    }

    /* Filter & Search */
    .tracking-filter-pane {
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .tracking-search-wrap {
        position: relative;
    }

    .tracking-search-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 12.5px;
    }

    .tracking-search-input {
        width: 100%;
        padding: 8px 12px 8px 34px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 12px;
        outline: none;
        background: #ffffff;
        transition: all 0.2s;
    }

    .tracking-search-input:focus {
        border-color: #ff5b22;
        box-shadow: 0 0 0 3px rgba(255, 91, 34, 0.1);
    }

    .tracking-type-pills {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .tracking-type-pills::-webkit-scrollbar { display: none; }

    .type-pill {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        white-space: nowrap;
        transition: all 0.15s ease;
    }

    .type-pill:hover {
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .type-pill.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
    }

    /* Scrollable Cards */
    .tracking-cards-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f1f5f9;
    }
    .tracking-cards-list::-webkit-scrollbar { width: 5px; }
    .tracking-cards-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* Activity Card */
    .kegiatan-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .kegiatan-card:hover {
        border-color: #ff5b22;
        box-shadow: 0 4px 14px rgba(255, 91, 34, 0.12);
        transform: translateY(-1px);
    }
    .kegiatan-card.selected {
        border-color: #ff5b22;
        background: #fffdfb;
        box-shadow: 0 0 0 2px rgba(255, 91, 34, 0.2);
    }

    .keg-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .badge-type {
        font-size: 9.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .type-pasang { background: #dcfce7; color: #15803d; }
    .type-service { background: #e0f2fe; color: #0369a1; }
    .type-survey  { background: #fef3c7; color: #b45309; }
    .type-default { background: #f1f5f9; color: #475569; }

    .keg-time {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .keg-kode {
        font-size: 10.5px;
        font-weight: 700;
        color: #94a3b8;
    }

    .keg-customer {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 3px;
        line-height: 1.3;
    }
    .keg-keterangan {
        font-size: 11px;
        color: #64748b;
        font-style: italic;
        margin-bottom: 8px;
        background: #f8fafc;
        padding: 4px 8px;
        border-radius: 6px;
        border-left: 2.5px solid #ff5b22;
    }
    .keg-alamat {
        font-size: 11.5px;
        color: #475569;
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .keg-alamat i {
        color: #ef4444;
        font-size: 12px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .keg-teknisi-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 8px;
    }
    .keg-teknisi-title {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
    }
    .tek-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 11.5px;
    }
    .tek-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .tek-name-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .tek-name {
        font-weight: 600;
        color: #1e293b;
    }
    .badge-status-work {
        font-size: 9px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
    }
    .status-dkj { background: #dbeafe; color: #1d4ed8; }
    .status-mlap { background: #ffedd5; color: #c2410c; }
    .status-sls { background: #dcfce7; color: #15803d; }
    .status-jdw { background: #f1f5f9; color: #475569; }

    .dot-online {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
    .dot-offline {
        width: 6px;
        height: 6px;
        background: #94a3b8;
        border-radius: 50%;
        display: inline-block;
    }

    .keg-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 4px;
    }
    .btn-focus-map {
        flex: 1;
        padding: 6px 12px;
        background: #ff5b22;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-focus-map:hover {
        background: #e04b16;
        box-shadow: 0 4px 10px rgba(255, 91, 34, 0.3);
    }
    .btn-call-cust {
        padding: 6px 10px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }
    .btn-call-cust:hover {
        background: #dcfce7;
        color: #15803d;
    }

    /* Right Map Panel */
    .tracking-map-panel {
        flex: 1;
        min-width: 0;
        position: relative;
        height: 100%;
        background: #f8fafc;
    }
    #map {
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    /* Top Floating Bar on Map */
    .map-floating-bar {
        position: absolute;
        top: 14px;
        left: 16px;
        right: 16px;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        pointer-events: none;
        gap: 10px;
    }
    .map-stats-pill {
        pointer-events: auto;
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #ffffff;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    .stat-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        white-space: nowrap;
    }
    .stat-divider {
        width: 1px;
        height: 14px;
        background: rgba(255, 255, 255, 0.18);
    }
    .map-actions-group {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .map-action-btn {
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 30px;
        padding: 8px 14px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .map-action-btn:hover {
        background: #ff5b22;
        border-color: #ff5b22;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 91, 34, 0.35);
    }

    /* Custom Markers */
    .marker-customer {
        background: #ef4444;
        border: 3px solid #ffffff;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.45);
    }
    .marker-teknisi {
        border: 3px solid #ffffff;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        transition: all 0.3s;
    }
    .marker-online {
        background: #ff5b22;
        box-shadow: 0 4px 12px rgba(255, 91, 34, 0.5);
    }
    .marker-offline {
        background: #64748b;
    }

    /* Mobile responsive */
    @media (max-width: 991px) {
        .live-tracking-wrapper {
            margin: 0 12px 16px 12px;
            border-radius: 12px;
        }
        .tracking-drawer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            max-width: 100%;
            height: 100%;
            z-index: 1010;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .tracking-drawer.mobile-hidden {
            transform: translateX(-105%);
            pointer-events: none;
        }
        .map-floating-bar {
            left: 10px;
            right: 10px;
            top: 10px;
            flex-wrap: wrap;
        }
        .map-stats-pill {
            padding: 6px 12px;
            font-size: 11px;
            gap: 8px;
        }
        .map-stats-pill .stat-item {
            font-size: 10.5px;
        }
        .map-action-btn {
            padding: 6px 10px;
            font-size: 11px;
        }
    }
    @media (min-width: 992px) {
        .tracking-drawer.mobile-hidden {
            transform: none !important;
            pointer-events: auto !important;
        }
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>

  <main class="main-content position-relative border-radius-lg">
    <?php include "nav-top.php"; ?>

    <div class="live-tracking-wrapper">
      <!-- Left: Activity Drawer / List -->
      <div class="tracking-drawer" id="trackingDrawer">
        <!-- Header -->
        <div class="tracking-drawer-header">
          <div class="d-flex align-items-center gap-2">
            <div class="header-icon-box">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold" style="font-size: 13.5px; color: #0f172a;">Tracking Kegiatan</h6>
              <span style="font-size: 11px; color: #64748b;">Pantau pergerakan teknisi hari ini</span>
            </div>
          </div>
          <div class="live-pill">
            <span class="live-dot"></span> LIVE
          </div>
        </div>

        <!-- Search & Filters -->
        <div class="tracking-filter-pane">
          <div class="tracking-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" class="tracking-search-input" placeholder="Cari nama customer, teknisi, kode...">
          </div>
          <div class="tracking-type-pills">
            <button type="button" class="type-pill active" data-type="all" onclick="filterType('all', this)">Semua (<span id="countAll">0</span>)</button>
            <button type="button" class="type-pill" data-type="pasang" onclick="filterType('pasang', this)">Pasang Baru (<span id="countPasang">0</span>)</button>
            <button type="button" class="type-pill" data-type="service" onclick="filterType('service', this)">Service (<span id="countService">0</span>)</button>
            <button type="button" class="type-pill" data-type="survey" onclick="filterType('survey', this)">Survey (<span id="countSurvey">0</span>)</button>
          </div>
        </div>

        <!-- Activities List (Scrollable) -->
        <div class="tracking-cards-list" id="kegiatanList">
          <div style="text-align: center; color: #94a3b8; padding: 40px 20px; font-size: 13px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #ff5b22; display:block; margin-bottom: 10px;"></i>
            Memuat data kegiatan hari ini...
          </div>
        </div>
      </div>

      <!-- Right: Map Panel -->
      <div class="tracking-map-panel" id="mapPanel">
        <!-- Floating Top Bar on Map -->
        <div class="map-floating-bar">
          <div class="map-stats-pill">
            <div class="stat-item">
              <i class="fa-solid fa-calendar-check" style="color: #38bdf8;"></i>
              <span id="statKegiatan">0 Kegiatan</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <i class="fa-solid fa-users" style="color: #a78bfa;"></i>
              <span id="statTeknisi">0 Teknisi</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <i class="fa-solid fa-motorcycle" style="color: #ff5b22;"></i>
              <span id="statOnline">0 Online</span>
            </div>
          </div>

          <div class="map-actions-group">
            <button type="button" class="map-action-btn" onclick="fitAllMapBounds()" title="Reset zoom ke semua titik">
              <i class="fa-solid fa-expand"></i> <span class="d-none d-sm-inline">Reset Peta</span>
            </button>
            <button type="button" class="map-action-btn" id="btnRefresh" onclick="manualRefresh()" title="Refresh data seketika">
              <i class="fa-solid fa-arrows-rotate" id="refreshIcon"></i> <span class="d-none d-sm-inline">Refresh</span>
            </button>
            <!-- Mobile Drawer Toggle Button -->
            <button type="button" class="map-action-btn d-lg-none" id="btnToggleDrawer" onclick="toggleDrawerMobile()" title="Lihat Daftar Kegiatan">
              <i class="fa-solid fa-list-ul"></i> <span id="btnToggleDrawerText">Daftar</span>
            </button>
          </div>
        </div>

        <!-- Leaflet Map -->
        <div id="map"></div>
      </div>
    </div>
  </main>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    // Init Leaflet Map with zoomControl at bottomright
    const map = L.map('map', { zoomControl: false }).setView([-6.2088, 106.8456], 12);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Refresh map size once layout stabilizes
    setTimeout(() => { map.invalidateSize(); }, 300);
    window.addEventListener('resize', () => { map.invalidateSize(); });

    let kegiatanData = [];
    let activeTypeFilter = 'all';
    let customerMarkers = {};
    let teknisiMarkers = {};
    let routePolylines = [];
    let selectedKegiatanKode = null;
    let hasInitialFit = false;

    // Icons
    function createCustomerIcon() {
        return L.divIcon({
            className: 'custom-icon',
            html: `<div class="marker-customer"><i class="fa-solid fa-store"></i></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });
    }

    function createTeknisiIcon(isOnline) {
        return L.divIcon({
            className: 'custom-icon',
            html: `<div class="marker-teknisi ${isOnline ? 'marker-online' : 'marker-offline'}">
                     <i class="fa-solid fa-motorcycle"></i>
                   </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });
    }

    // Fetch Live Tracking Data from API
    async function fetchTrackingData() {
        try {
            const response = await fetch('../get_tracking_kegiatan.php');
            const result = await response.json();
            if (result.status && result.data) {
                kegiatanData = result.data;
                updateTopStats(result.summary);
                renderKegiatanSidebar();
                renderMapLayers();
            }
        } catch (err) {
            console.error("Gagal memuat data tracking kegiatan:", err);
        }
    }

    function updateTopStats(summary) {
        if (!summary) return;
        document.getElementById('statKegiatan').innerText = `${summary.total_kegiatan} Kegiatan`;
        document.getElementById('statTeknisi').innerText = `${summary.total_teknisi_terjadwal} Teknisi`;
        document.getElementById('statOnline').innerText = `${summary.teknisi_online} Online`;
    }

    function filterType(type, el) {
        activeTypeFilter = type;
        document.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        renderKegiatanSidebar();
    }

    function getFilteredKegiatan() {
        const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
        return kegiatanData.filter(k => {
            const matchesType = activeTypeFilter === 'all' || 
                (k.kegiatan || '').toLowerCase().includes(activeTypeFilter);

            const matchesSearch = !searchVal || 
                (k.nama_customer || '').toLowerCase().includes(searchVal) ||
                (k.kode || '').toLowerCase().includes(searchVal) ||
                (k.kegiatan || '').toLowerCase().includes(searchVal) ||
                (k.alamat || '').toLowerCase().includes(searchVal) ||
                k.teknisi_list.some(t => (t.nama || '').toLowerCase().includes(searchVal));

            return matchesType && matchesSearch;
        });
    }

    function renderKegiatanSidebar() {
        const listEl = document.getElementById('kegiatanList');
        const filtered = getFilteredKegiatan();

        // Counts
        document.getElementById('countAll').innerText = kegiatanData.length;
        document.getElementById('countPasang').innerText = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('pasang')).length;
        document.getElementById('countService').innerText = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('service')).length;
        document.getElementById('countSurvey').innerText = kegiatanData.filter(k => (k.kegiatan || '').toLowerCase().includes('survey')).length;

        if (filtered.length === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; color: #94a3b8; padding: 40px 20px;">
                    <i class="fa-regular fa-folder-open" style="font-size: 32px; margin-bottom: 10px; display:block;"></i>
                    <p style="font-size: 13px; margin:0;">Tidak ada kegiatan yang cocok</p>
                </div>`;
            return;
        }

        listEl.innerHTML = filtered.map(keg => {
            const kegLower = (keg.kegiatan || '').toLowerCase();
            let typeClass = 'type-default';
            if (kegLower.includes('pasang')) typeClass = 'type-pasang';
            else if (kegLower.includes('service')) typeClass = 'type-service';
            else if (kegLower.includes('survey')) typeClass = 'type-survey';

            const isSelected = selectedKegiatanKode === keg.kode ? 'selected' : '';

            const teknisiHtml = keg.teknisi_list.length > 0 
                ? keg.teknisi_list.map(t => {
                    const statusClass = t.status_kerja.toLowerCase().includes('selesai') ? 'status-sls'
                        : (t.status_kerja.toLowerCase().includes('menunggu') ? 'status-mlap'
                        : (t.status_kerja.toLowerCase().includes('dikerjakan') || t.status_kerja.toLowerCase().includes('berjalan') ? 'status-dkj' : 'status-jdw'));

                    const distText = t.jarak_ke_lokasi_km !== null ? `~${t.jarak_ke_lokasi_km} km` : '';
                    const liveText = t.is_online 
                        ? `<span style="color:#059669; font-weight:600;"><span class="dot-online"></span> ${Math.round(parseFloat(t.speed || 0))} km/h ${distText ? '(' + distText + ')' : ''}</span>`
                        : `<span style="color:#94a3b8;"><span class="dot-offline"></span> ${t.last_seen_seconds ? Math.floor(t.last_seen_seconds/60) + 'm lalu' : 'Offline'}</span>`;

                    return `
                        <div class="tek-row">
                            <div class="tek-name-group">
                                <i class="fa-solid fa-motorcycle" style="color: ${t.is_online ? '#ff5b22' : '#94a3b8'}; font-size: 11px;"></i>
                                <span class="tek-name">${t.nama} ${t.is_ketua ? '👑' : ''}</span>
                                <span class="badge-status-work ${statusClass}">${t.status_kerja}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                ${liveText}
                                ${t.telp ? `<a href="https://wa.me/62${t.telp.replace(/^0/, '')}" target="_blank" style="color:#10b981; font-size:12px; margin-left:3px;" title="WhatsApp ${t.nama}"><i class="fa-brands fa-whatsapp"></i></a>` : ''}
                            </div>
                        </div>
                    `;
                }).join('')
                : `<div style="font-size:11px; color:#94a3b8; font-style:italic;">Belum ada teknisi ditugaskan</div>`;

            const phoneClean = (keg.cust_nomor || '').replace(/[^0-9]/g, '');

            return `
                <div class="kegiatan-card ${isSelected}" onclick="focusKegiatan('${keg.kode}')">
                    <div class="keg-top">
                        <span class="badge-type ${typeClass}">${keg.kegiatan}</span>
                        <div class="keg-time"><i class="fa-regular fa-clock"></i> ${keg.jadwal} WIB</div>
                        <span class="keg-kode">#${keg.kode}</span>
                    </div>
                    <div class="keg-customer">${keg.nama_customer || 'Customer'}</div>
                    ${keg.keterangan ? `<div class="keg-keterangan">"${keg.keterangan}"</div>` : ''}
                    <div class="keg-alamat">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>${keg.alamat || 'Alamat belum diisi'}</div>
                    </div>

                    <div class="keg-teknisi-box">
                        <div class="keg-teknisi-title">
                            <span><i class="fa-solid fa-users-gear"></i> Tim Teknisi (${keg.teknisi_count})</span>
                            <span>Status</span>
                        </div>
                        ${teknisiHtml}
                    </div>

                    <div class="keg-actions">
                        <button type="button" class="btn-focus-map" onclick="event.stopPropagation(); focusKegiatan('${keg.kode}')">
                            <i class="fa-solid fa-crosshairs"></i> Pantau di Peta
                        </button>
                        ${phoneClean ? `
                            <a href="https://wa.me/62${phoneClean.replace(/^0/, '')}" target="_blank" class="btn-call-cust" onclick="event.stopPropagation()">
                                <i class="fa-brands fa-whatsapp"></i> Customer
                            </a>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Render Map Markers & Polylines
    function renderMapLayers() {
        routePolylines.forEach(p => map.removeLayer(p));
        routePolylines = [];

        const boundsList = [];

        kegiatanData.forEach(keg => {
            const isSelected = selectedKegiatanKode === keg.kode;

            // 1. Customer Marker
            if (keg.latitude && keg.longitude) {
                const cLat = parseFloat(keg.latitude);
                const cLon = parseFloat(keg.longitude);
                if (!isNaN(cLat) && !isNaN(cLon)) {
                    boundsList.push([cLat, cLon]);

                    const custPopup = `
                        <div style="font-family: Poppins, sans-serif; font-size: 12.5px; min-width: 200px;">
                            <span class="badge-type type-pasang" style="font-size:9.5px; padding:2px 6px;">${keg.kegiatan}</span>
                            <b style="display:block; font-size: 13.5px; color:#0f172a; margin: 4px 0 2px;">${keg.nama_customer}</b>
                            <div style="color:#64748b; font-size:11px; margin-bottom:6px;"><i class="fa-solid fa-location-dot"></i> ${keg.alamat}</div>
                            <div style="font-size:11.5px; color:#1e293b; border-top:1px solid #e2e8f0; padding-top:4px;">
                                <b>Teknisi:</b> ${keg.teknisi_list.map(t => t.nama).join(', ') || '-'}
                            </div>
                        </div>
                    `;

                    if (customerMarkers[keg.kode]) {
                        customerMarkers[keg.kode].setLatLng([cLat, cLon]);
                        customerMarkers[keg.kode].getPopup().setContent(custPopup);
                    } else {
                        const marker = L.marker([cLat, cLon], { icon: createCustomerIcon() })
                            .addTo(map)
                            .bindPopup(custPopup);
                        marker.on('click', () => focusKegiatan(keg.kode));
                        customerMarkers[keg.kode] = marker;
                    }

                    // 2. Connect each technician with a line to this customer
                    keg.teknisi_list.forEach(tek => {
                        if (tek.live_latitude && tek.live_longitude) {
                            const tLat = parseFloat(tek.live_latitude);
                            const tLon = parseFloat(tek.live_longitude);
                            if (!isNaN(tLat) && !isNaN(tLon)) {
                                boundsList.push([tLat, tLon]);

                                const line = L.polyline([[tLat, tLon], [cLat, cLon]], {
                                    color: isSelected ? '#ff5b22' : '#3b82f6',
                                    weight: isSelected ? 4 : 2,
                                    dashArray: isSelected ? '6, 6' : '4, 8',
                                    opacity: isSelected ? 0.9 : 0.4
                                }).addTo(map);
                                routePolylines.push(line);

                                // If focused, load real road routing geometry from OSRM (with snapping=any to reach complex/ruko)
                                if (isSelected) {
                                    fetch(`https://router.project-osrm.org/route/v1/driving/${tLon},${tLat};${cLon},${cLat}?overview=full&geometries=geojson&snapping=any`)
                                        .then(r => r.json())
                                        .then(res => {
                                            if (res.code === 'Ok' && res.routes && res.routes.length > 0) {
                                                const roadCoords = res.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                                // Sambungkan titik awal teknisi dan titik akhir customer tanpa celah
                                                const fullCoords = [[tLat, tLon], ...roadCoords, [cLat, cLon]];
                                                line.setLatLngs(fullCoords);
                                                line.setStyle({ dashArray: null, weight: 4.5, opacity: 0.95 });
                                            }
                                        })
                                        .catch(() => {});
                                }
                            }
                        }
                    });
                }
            }

            // 3. Teknisi Markers
            keg.teknisi_list.forEach(tek => {
                if (tek.live_latitude && tek.live_longitude) {
                    const tLat = parseFloat(tek.live_latitude);
                    const tLon = parseFloat(tek.live_longitude);
                    if (!isNaN(tLat) && !isNaN(tLon)) {
                        boundsList.push([tLat, tLon]);

                        const tekPopup = `
                            <div style="font-family: Poppins, sans-serif; font-size: 12.5px; min-width: 190px;">
                                <b style="font-size: 13.5px; color:#0f172a;">${tek.nama} ${tek.is_ketua ? '👑' : ''}</b><br>
                                <span style="color: ${tek.is_online ? '#16A34A' : '#94A3B8'}; font-weight: 600;">
                                    ${tek.is_online ? '● Online (Bergerak)' : '○ Terakhir aktif: ' + Math.floor((tek.last_seen_seconds||0)/60) + 'm lalu'}
                                </span><br>
                                <span style="font-size:11.5px; color:#64748b;">Tugas: <b>${keg.nama_customer}</b></span><br>
                                <span style="font-size:11.5px; color:#64748b;">Kecepatan: <b>${Math.round(parseFloat(tek.speed || 0))} km/jam</b></span><br>
                                ${tek.jarak_ke_lokasi_km !== null ? `<span style="font-size:11px; color:#ff5b22; font-weight:600;">Jarak ke tujuan: ~${tek.jarak_ke_lokasi_km} km</span><br>` : ''}
                                ${tek.telp ? `<a href="https://wa.me/62${tek.telp.replace(/^0/, '')}" target="_blank" style="display:inline-block; margin-top:6px; color:#16a34a; font-weight:600; text-decoration:none;"><i class="fa-brands fa-whatsapp"></i> Hubungi WhatsApp</a>` : ''}
                            </div>
                        `;

                        if (teknisiMarkers[tek.teknisi_id]) {
                            teknisiMarkers[tek.teknisi_id].setLatLng([tLat, tLon]);
                            teknisiMarkers[tek.teknisi_id].setIcon(createTeknisiIcon(tek.is_online));
                            teknisiMarkers[tek.teknisi_id].getPopup().setContent(tekPopup);
                        } else {
                            const marker = L.marker([tLat, tLon], { icon: createTeknisiIcon(tek.is_online) })
                                .addTo(map)
                                .bindPopup(tekPopup);
                            teknisiMarkers[tek.teknisi_id] = marker;
                        }
                    }
                }
            });
        });

        // Initial fit bounds only ONCE on first load
        if (!hasInitialFit && !selectedKegiatanKode && boundsList.length > 0) {
            map.fitBounds(boundsList, { padding: [50, 50], maxZoom: 14 });
            hasInitialFit = true;
        }
    }

    // Focus on a specific Kegiatan
    function focusKegiatan(kode) {
        selectedKegiatanKode = kode;
        renderKegiatanSidebar();
        renderMapLayers();

        // On mobile, auto close drawer to show map
        if (window.innerWidth < 992) {
            const drawer = document.getElementById('trackingDrawer');
            if (drawer) drawer.classList.add('mobile-hidden');
            const btnText = document.getElementById('btnToggleDrawerText');
            if (btnText) btnText.innerText = 'Daftar';
        }

        const keg = kegiatanData.find(k => k.kode === kode);
        if (!keg) return;

        const targets = [];
        if (keg.latitude && keg.longitude) {
            targets.push([parseFloat(keg.latitude), parseFloat(keg.longitude)]);
            if (customerMarkers[kode]) {
                customerMarkers[kode].openPopup();
            }
        }

        keg.teknisi_list.forEach(t => {
            if (t.live_latitude && t.live_longitude) {
                targets.push([parseFloat(t.live_latitude), parseFloat(t.live_longitude)]);
            }
        });

        if (targets.length === 1) {
            map.flyTo(targets[0], 16, { duration: 1.2 });
        } else if (targets.length > 1) {
            map.fitBounds(targets, { padding: [80, 80], maxZoom: 16 });
        }
    }

    // Fit all markers in view
    function fitAllMapBounds() {
        selectedKegiatanKode = null;
        renderKegiatanSidebar();
        renderMapLayers();

        const allBounds = [];
        Object.values(customerMarkers).forEach(m => allBounds.push(m.getLatLng()));
        Object.values(teknisiMarkers).forEach(m => allBounds.push(m.getLatLng()));

        if (allBounds.length > 0) {
            map.fitBounds(allBounds, { padding: [80, 80], maxZoom: 15 });
        } else {
            map.setView([-6.2088, 106.8456], 12);
        }
    }

    async function manualRefresh() {
        const icon = document.getElementById('refreshIcon');
        if (icon) icon.classList.add('fa-spin');
        await fetchTrackingData();
        setTimeout(() => {
            if (icon) icon.classList.remove('fa-spin');
        }, 600);
    }

    function toggleDrawerMobile() {
        const drawer = document.getElementById('trackingDrawer');
        const btnText = document.getElementById('btnToggleDrawerText');
        if (!drawer) return;
        const isHidden = drawer.classList.contains('mobile-hidden');
        if (isHidden) {
            drawer.classList.remove('mobile-hidden');
            if (btnText) btnText.innerText = 'Tutup';
        } else {
            drawer.classList.add('mobile-hidden');
            if (btnText) btnText.innerText = 'Daftar';
        }
    }

    document.getElementById('searchInput').addEventListener('input', () => {
        renderKegiatanSidebar();
    });

    // Initial fetch & 5s interval polling
    fetchTrackingData();
    setInterval(fetchTrackingData, 5000);
  </script>
</body>
</html>

