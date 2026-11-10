<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking Teknisi Loewix</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
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
            width: 360px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            z-index: 1000;
        }
        .sidebar-header {
            padding: 20px;
            background: #11141b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sidebar-header h2 {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .sidebar-header .live-badge {
            background: #ff5b22;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
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
        .search-box {
            padding: 14px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .search-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-input:focus {
            border-color: #ff5b22;
        }
        .teknisi-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
        }
        .teknisi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .teknisi-card:hover, .teknisi-card.active {
            border-color: #ff5b22;
            box-shadow: 0 4px 14px rgba(255, 91, 34, 0.12);
            transform: translateY(-2px);
        }
        .teknisi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .teknisi-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .status-pill {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
        }
        .status-online {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-offline {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .teknisi-info {
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .teknisi-actions {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .wa-btn {
            font-size: 11px;
            color: #16a34a;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .focus-btn {
            font-size: 11px;
            color: #ff5b22;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
        }

        /* Map */
        #map-container {
            flex: 1;
            position: relative;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        .map-stats-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 999;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 10px 18px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            font-size: 12.5px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* Custom Marker */
        .teknisi-marker-pin {
            width: 38px;
            height: 38px;
            background: #ff5b22;
            border: 3px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 91, 34, 0.4);
            color: #ffffff;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Sidebar -->
        <div id="sidebar">
            <div class="sidebar-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="staff/index-sa.php" style="color: #94a3b8; text-decoration: none; font-size: 14px; padding: 5px 9px; border-radius: 8px; background: rgba(255,255,255,0.08); transition: all 0.2s;" title="Kembali ke Dashboard Admin">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h2><i class="fa-solid fa-map-location-dot"></i> Loewix Tracking</h2>
                </div>
                <div class="live-badge">
                    <span class="live-dot"></span> LIVE
                </div>
            </div>
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari nama teknisi...">
            </div>
            <div class="teknisi-list" id="teknisiList">
                <p style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">Memuat data teknisi...</p>
            </div>
        </div>

        <!-- Map Container -->
        <div id="map-container">
            <div class="map-stats-badge" id="statsBadge">
                <i class="fa-solid fa-satellite-dish" style="color: #ff5b22;"></i>
                <span id="activeCount">0 Teknisi Aktif</span>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Init Map
        const map = L.map('map').setView([-6.2088, 106.8456], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let markers = {};
        let teknisiData = [];

        function createTeknisiIcon(isOnline) {
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div class="teknisi-marker-pin" style="background: ${isOnline ? '#FF5B22' : '#94A3B8'}">
                         <i class="fa-solid fa-motorcycle"></i>
                       </div>`,
                iconSize: [38, 38],
                iconAnchor: [19, 19],
                popupAnchor: [0, -20]
            });
        }

        async function fetchLocations() {
            try {
                const response = await fetch('get_teknisi_location.php');
                const result = await response.json();
                if (result.status && result.data) {
                    teknisiData = result.data;
                    renderSidebar(teknisiData);
                    updateMapMarkers(teknisiData);
                }
            } catch (err) {
                console.error("Gagal memuat lokasi:", err);
            }
        }

        function updateMapMarkers(data) {
            let onlineCount = 0;
            const currentIds = new Set();

            data.forEach(tek => {
                currentIds.add(tek.teknisi_id);
                const lat = parseFloat(tek.latitude);
                const lon = parseFloat(tek.longitude);
                if (isNaN(lat) || isNaN(lon)) return;

                if (tek.is_online) onlineCount++;

                const popupContent = `
                    <div style="font-family: Poppins, sans-serif; font-size: 13px;">
                        <b style="font-size: 14px; color: #0F172A;">${tek.nama || 'Teknisi #' + tek.teknisi_id}</b><br>
                        <span style="color: ${tek.is_online ? '#16A34A' : '#94A3B8'}; font-weight: 600;">
                            ${tek.is_online ? '● Online (Bergerak)' : '○ Terakhir aktif: ' + formatTimeAgo(tek.last_seen_seconds)}
                        </span><br>
                        <span style="font-size: 11.5px; color: #64748B;">Kecepatan: ${tek.speed ? Math.round(parseFloat(tek.speed)) + ' km/jam' : '-'}</span><br>
                        <span style="font-size: 11px; color: #94A3B8;">Update: ${tek.updated_at}</span>
                    </div>
                `;

                if (markers[tek.teknisi_id]) {
                    markers[tek.teknisi_id].setLatLng([lat, lon]);
                    markers[tek.teknisi_id].setIcon(createTeknisiIcon(tek.is_online));
                    markers[tek.teknisi_id].getPopup().setContent(popupContent);
                } else {
                    const marker = L.marker([lat, lon], {
                        icon: createTeknisiIcon(tek.is_online)
                    }).addTo(map).bindPopup(popupContent);
                    markers[tek.teknisi_id] = marker;
                }
            });

            document.getElementById('activeCount').innerText = `${onlineCount} Teknisi Online`;
        }

        function renderSidebar(data) {
            const listEl = document.getElementById('teknisiList');
            const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();

            const filtered = data.filter(t => 
                (t.nama || '').toLowerCase().includes(searchVal) ||
                (t.teknisi_id + '').includes(searchVal)
            );

            if (filtered.length === 0) {
                listEl.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">Tidak ada teknisi aktif</p>';
                return;
            }

            listEl.innerHTML = filtered.map(tek => {
                const hasPos = tek.latitude && tek.longitude && !isNaN(parseFloat(tek.latitude)) && !isNaN(parseFloat(tek.longitude));
                const nLat = hasPos ? parseFloat(tek.latitude).toFixed(5) : '-';
                const nLon = hasPos ? parseFloat(tek.longitude).toFixed(5) : '-';
                const clickAttr = hasPos ? `onclick="flyToTeknisi(${tek.teknisi_id}, ${tek.latitude}, ${tek.longitude})"` : '';
                const cardCursor = hasPos ? 'cursor: pointer;' : 'cursor: default;';
                const posText = hasPos ? `${nLat}, ${nLon}` : 'Belum kirim GPS';
                const focusBtn = hasPos 
                    ? `<button class="focus-btn"><i class="fa-solid fa-crosshairs"></i> Lihat di Peta</button>` 
                    : `<span style="font-size: 11px; color: #94a3b8; font-style: italic;">Menunggu GPS</span>`;

                return `
                <div class="teknisi-card" style="${cardCursor}" ${clickAttr}>
                    <div class="teknisi-top">
                        <span class="teknisi-name">${tek.nama || 'Teknisi #' + tek.teknisi_id}</span>
                        <span class="status-pill ${tek.is_online ? 'status-online' : 'status-offline'}">
                            ${tek.is_online ? '● Online' : 'Offline'}
                        </span>
                    </div>
                    <div class="teknisi-info">
                        <div><i class="fa-regular fa-clock"></i> ${formatTimeAgo(tek.last_seen_seconds)}</div>
                        <div><i class="fa-solid fa-gauge-high"></i> Kecepatan: ${tek.speed ? Math.round(parseFloat(tek.speed)) + ' km/h' : '0 km/h'}</div>
                        <div><i class="fa-solid fa-location-dot"></i> ${posText}</div>
                    </div>
                    <div class="teknisi-actions">
                        ${tek.telp ? `<a href="https://wa.me/62${tek.telp.replace(/^0/, '')}" target="_blank" class="wa-btn"><i class="fa-brands fa-whatsapp"></i> Hubungi</a>` : '<span></span>'}
                        ${focusBtn}
                    </div>
                </div>
                `;
            }).join('');
        }

        function flyToTeknisi(id, lat, lon) {
            const nLat = parseFloat(lat);
            const nLon = parseFloat(lon);
            if (!isNaN(nLat) && !isNaN(nLon)) {
                map.flyTo([nLat, nLon], 16, { duration: 1.2 });
                if (markers[id]) markers[id].openPopup();
            }
        }

        function formatTimeAgo(seconds) {
            if (seconds === null || seconds === undefined) return 'Belum ada data';
            if (seconds < 60) return `${seconds} detik lalu`;
            if (seconds < 3600) return `${Math.floor(seconds / 60)} menit lalu`;
            return `${Math.floor(seconds / 3600)} jam lalu`;
        }

        document.getElementById('searchInput').addEventListener('input', () => {
            renderSidebar(teknisiData);
        });

        // Polling loop every 5 seconds
        fetchLocations();
        setInterval(fetchLocations, 5000);
    </script>
</body>
</html>

