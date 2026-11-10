<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking Kegiatan & Teknisi - Loewix</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body, html {
            height: 100%;
            overflow: hidden;
            background: #0f172a;
        }
        #app {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        /* Sidebar */
        #sidebar {
            width: 420px;
            min-width: 420px;
            max-width: 420px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(0,0,0,0.12);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .sidebar-header {
            padding: 16px 20px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
        }
        .sidebar-header h2 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-header .live-badge {
            background: #ff5b22;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 0 12px rgba(255, 91, 34, 0.4);
        }
        .live-dot {
            width: 7px;
            height: 7px;
            background: #ffffff;
            border-radius: 50%;
            animation: pulse 1.2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.3; }
            50% { opacity: 1; }
            100% { opacity: 0.3; }
        }

        /* Filter Section */
        .filter-section {
            padding: 12px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }
        .search-input {
            width: 100%;
            padding: 9px 12px 9px 34px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 12.5px;
            outline: none;
            transition: all 0.2s;
            background: #ffffff;
        }
        .search-input:focus {
            border-color: #ff5b22;
            box-shadow: 0 0 0 3px rgba(255, 91, 34, 0.1);
        }
        .type-pills {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 2px;
        }
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

        /* Kegiatan List */
        .kegiatan-list {
            flex: 1;
            overflow-y: auto;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f1f5f9;
        }
        .kegiatan-list::-webkit-scrollbar {
            width: 6px;
        }
        .kegiatan-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 6px;
        }

        /* Kartu Kegiatan */
        .kegiatan-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            position: relative;
        }
        .kegiatan-card:hover {
            border-color: #ff5b22;
            box-shadow: 0 6px 18px rgba(255, 91, 34, 0.12);
            transform: translateY(-2px);
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
            margin-bottom: 8px;
        }
        .badge-type {
            font-size: 10px;
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
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
        }

        .keg-customer {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .keg-keterangan {
            font-size: 11.5px;
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
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .keg-alamat i {
            color: #ef4444;
            font-size: 12px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Teknisi Group inside Card */
        .keg-teknisi-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .keg-teknisi-title {
            font-size: 10.5px;
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
            padding: 5px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 12px;
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
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
        }
        .status-dkj { background: #dbeafe; color: #1d4ed8; } /* Dikerjakan */
        .status-mlap { background: #ffedd5; color: #c2410c; } /* Menunggu Lap */
        .status-sls { background: #dcfce7; color: #15803d; } /* Selesai */
        .status-jdw { background: #f1f5f9; color: #475569; } /* Dijadwalkan */

        .tek-live-badge {
            font-size: 10.5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .dot-online {
            width: 7px;
            height: 7px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-offline {
            width: 7px;
            height: 7px;
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
            font-size: 11.5px;
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
            font-size: 11.5px;
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

        /* Map Container */
        #map-container {
            flex: 1;
            position: relative;
            height: 100vh;
        }
        #map {
            width: 100%;
            height: 100%;
        }

        /* Top Floating Stats on Map */
        .map-top-bar {
            position: absolute;
            top: 18px;
            left: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }
        .map-stats-pill {
            pointer-events: auto;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #ffffff;
            padding: 9px 20px;
            border-radius: 40px;
            font-size: 12.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            letter-spacing: -0.1px;
            white-space: nowrap;
        }
        .stat-divider {
            width: 1px;
            height: 16px;
            background: rgba(255, 255, 255, 0.15);
        }
        .map-tool-btn {
            pointer-events: auto;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 30px;
            padding: 9px 15px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .map-tool-btn:hover {
            background: #ff5b22;
            border-color: #ff5b22;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(255, 91, 34, 0.4);
        }

        /* Custom Markers */
        .marker-customer {
            background: #ef4444;
            border: 3px solid #ffffff;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.45);
        }
        .marker-teknisi {
            border: 3px solid #ffffff;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        .marker-online {
            background: #ff5b22;
            box-shadow: 0 4px 14px rgba(255, 91, 34, 0.5);
        }
        .marker-offline {
            background: #64748b;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Sidebar -->
        <div id="sidebar">
            <div class="sidebar-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="staff/index-sa.php" style="color: #94a3b8; text-decoration: none; font-size: 14px; padding: 5px 9px; border-radius: 8px; background: rgba(255,255,255,0.08); transition: all 0.2s;" title="Kembali ke Dashboard">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h2><i class="fa-solid fa-map-location-dot" style="color:#ff5b22;"></i> Tracking Kegiatan</h2>
                </div>
                <div class="live-badge">
                    <span class="live-dot"></span> LIVE
                </div>
            </div>

            <!-- Filter & Search -->
            <div class="filter-section">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari nama customer, teknisi, kode...">
                </div>
                <div class="type-pills">
                    <span class="type-pill active" data-type="all" onclick="filterType('all', this)">Semua (<span id="countAll">0</span>)</span>
                    <span class="type-pill" data-type="pasang" onclick="filterType('pasang', this)">Pasang Baru (<span id="countPasang">0</span>)</span>
                    <span class="type-pill" data-type="service" onclick="filterType('service', this)">Service (<span id="countService">0</span>)</span>
                    <span class="type-pill" data-type="survey" onclick="filterType('survey', this)">Survey (<span id="countSurvey">0</span>)</span>
                </div>
            </div>

            <!-- List Kegiatan -->
            <div class="kegiatan-list" id="kegiatanList">
                <p style="text-align: center; color: #94a3b8; padding: 30px; font-size: 13px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 20px; display:block; margin-bottom: 8px;"></i>
                    Memuat data kegiatan hari ini...
                </p>
            </div>
        </div>

        <!-- Map Container -->
        <div id="map-container">
            <div class="map-top-bar">
                <div class="map-stats-pill">
                    <div class="stat-item">
                        <i class="fa-solid fa-calendar-check" style="color: #38bdf8; font-size: 13px;"></i>
                        <span id="statKegiatan">0 Kegiatan Hari Ini</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-users" style="color: #a78bfa; font-size: 13px;"></i>
                        <span id="statTeknisi">0 Teknisi Terjadwal</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fa-solid fa-motorcycle" style="color: #ff5b22; font-size: 13px;"></i>
                        <span id="statOnline">0 Teknisi Online</span>
                    </div>
                </div>

                <button class="map-tool-btn" onclick="fitAllMapBounds()" title="Lihat semua titik kegiatan dan teknisi di peta">
                    <i class="fa-solid fa-expand"></i> Reset Peta
                </button>
                <button class="map-tool-btn" id="btnRefresh" onclick="manualRefresh()" title="Refresh data seketika">
                    <i class="fa-solid fa-arrows-rotate" id="refreshIcon"></i> Refresh
                </button>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Init Leaflet Map with zoomControl moved to bottomright
        const map = L.map('map', { zoomControl: false }).setView([-6.2088, 106.8456], 12);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

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
                iconSize: [38, 38],
                iconAnchor: [19, 19],
                popupAnchor: [0, -20]
            });
        }

        function createTeknisiIcon(isOnline) {
            return L.divIcon({
                className: 'custom-icon',
                html: `<div class="marker-teknisi ${isOnline ? 'marker-online' : 'marker-offline'}">
                         <i class="fa-solid fa-motorcycle"></i>
                       </div>`,
                iconSize: [38, 38],
                iconAnchor: [19, 19],
                popupAnchor: [0, -20]
            });
        }

        // Fetch Live Tracking Data
        async function fetchTrackingData() {
            try {
                const response = await fetch('get_tracking_kegiatan.php');
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
            document.getElementById('statKegiatan').innerText = `${summary.total_kegiatan} Kegiatan Hari Ini`;
            document.getElementById('statTeknisi').innerText = `${summary.total_teknisi_terjadwal} Teknisi Terjadwal`;
            document.getElementById('statOnline').innerText = `${summary.teknisi_online} Teknisi Online`;
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
                        <i class="fa-regular fa-folder-open" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <p style="font-size: 13px;">Tidak ada kegiatan hari ini yang cocok</p>
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
                            <button class="btn-focus-map" onclick="event.stopPropagation(); focusKegiatan('${keg.kode}')">
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
            // Clear existing markers & polylines
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

            // Initial fit bounds only ONCE on first load so user pan & zoom are not interrupted by auto-refresh
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

        document.getElementById('searchInput').addEventListener('input', () => {
            renderKegiatanSidebar();
        });

        // Polling every 5 seconds
        fetchTrackingData();
        setInterval(fetchTrackingData, 5000);
    </script>
</body>
</html>
