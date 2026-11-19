<!-- ═══════════════════════════════════════════════════════════════════════
     SMART DISPATCH MODAL: Hybrid Auto-Susun Rute Searah + Manual Override
     Shared partial for staff/waiting-list.php & staff/index-sa.php
     ═══════════════════════════════════════════════════════════════════════ -->
<style>
  /* ── Waiting List Checkbox & Trigger Button ── */
  .sd-btn-trigger {
    background: linear-gradient(135deg, #4f46e5, #0ea5e9);
    color: #fff !important;
    border: none;
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28);
    transition: transform 0.15s, box-shadow 0.15s;
    white-space: nowrap;
  }
  .sd-btn-trigger:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.38);
  }
  .sd-btn-trigger .sd-sel-count {
    background: rgba(255, 255, 255, 0.22);
    padding: 1px 7px;
    border-radius: 999px;
    font-size: 10.5px;
  }
  .sd-wl-check {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #4f46e5;
    flex-shrink: 0;
  }
  .sd-zone-filter-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding: 8px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
  }
  .sd-zone-pill-btn {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.15s;
  }
  .sd-zone-pill-btn.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
  }

  /* ── Smart Dispatch Modal Layout ── */
  #smartDispatchModal .modal-dialog {
    max-width: min(1420px, 97vw);
    margin: 12px auto;
  }
  #smartDispatchModal .modal-content {
    border-radius: 16px !important;
    overflow: hidden;
    border: none !important;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28) !important;
    height: calc(100vh - 24px);
    display: flex;
    flex-direction: column;
  }
  .sd-modal-header {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    flex-shrink: 0;
  }
  .sd-header-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .sd-ctrl-chip {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 9px;
    padding: 4px 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #e2e8f0;
    font-size: 11.5px;
    font-weight: 600;
  }
  .sd-ctrl-chip input {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    outline: none;
    color-scheme: dark;
  }
  .sd-btn-bar {
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-radius: 8px;
    padding: 6px 11px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.15s;
  }
  .sd-btn-bar:hover { background: rgba(255, 255, 255, 0.22); }
  .sd-btn-bar-accent {
    background: linear-gradient(135deg, #4f46e5, #0ea5e9);
    border-color: transparent;
  }
  .sd-btn-bar-accent:hover { opacity: 0.93; }

  /* ── Split Body ── */
  .sd-split-body {
    flex: 1;
    min-height: 0;
    display: flex;
    background: #f1f5f9;
  }
  .sd-left-pane {
    width: 50%;
    min-width: 360px;
    height: 100%;
    overflow-y: auto;
    padding: 14px 16px;
    border-right: 1px solid #cbd5e1;
  }
  .sd-right-pane {
    width: 50%;
    height: 100%;
    position: relative;
    background: #e2e8f0;
  }
  #sdRouteMap {
    width: 100%;
    height: 100%;
  }
  @media (max-width: 991px) {
    .sd-split-body { flex-direction: column; overflow-y: auto; }
    .sd-left-pane { width: 100%; min-width: 0; height: auto; overflow-y: visible; border-right: none; }
    .sd-right-pane { width: 100%; height: 360px; flex-shrink: 0; }
  }

  /* ── Team Route Card ── */
  .sd-team-card {
    background: #fff;
    border-radius: 13px;
    border: 1.5px solid #e2e8f0;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .sd-team-head {
    padding: 10px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .sd-team-toprow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
  }
  .sd-team-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
  }
  .sd-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }
  .sd-team-stats {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    background: #fff;
    border: 1px solid #cbd5e1;
    padding: 2px 9px;
    border-radius: 999px;
  }
  .sd-member-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  .sd-ketua-select, .sd-add-member-select {
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    padding: 3px 8px;
    font-size: 11.5px;
    font-weight: 600;
    background: #fff;
    color: #1e293b;
  }
  .sd-member-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #eef2ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
  }
  .sd-member-chip button {
    border: none;
    background: transparent;
    color: #6366f1;
    font-weight: 800;
    padding: 0 2px;
    cursor: pointer;
    line-height: 1;
  }

  /* ── Stop Cards & Leg Connectors ── */
  .sd-stops-list {
    padding: 10px 12px;
  }
  .sd-existing-stop {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 9px;
    padding: 7px 10px;
    margin-bottom: 6px;
    font-size: 11.5px;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .sd-leg-connector {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    margin: 2px 0 4px 12px;
    border-left: 2px dashed #cbd5e1;
    font-size: 10.5px;
    font-weight: 700;
  }
  .sd-leg-ok { color: #059669; border-left-color: #10b981; }
  .sd-leg-mid { color: #b45309; border-left-color: #f59e0b; }
  .sd-leg-warn {
    color: #be123c;
    border-left-color: #e11d48;
    background: #fff1f2;
    border-radius: 0 7px 7px 0;
  }
  .sd-stop-item {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 9px 11px;
    margin-bottom: 6px;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .sd-stop-item:hover, .sd-stop-item.sd-highlight {
    border-color: #6366f1;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.14);
  }
  .sd-stop-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    margin-bottom: 5px;
    flex-wrap: wrap;
  }
  .sd-stop-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .sd-time-input {
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    width: 92px;
  }
  .sd-move-btn {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #334155;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }
  .sd-move-btn:hover { background: #e2e8f0; }
  .sd-team-move-select {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #1e293b;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    cursor: pointer;
  }

  /* ── Map Floating Controls & Markers ── */
  .sd-map-Floating {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 900;
    display: flex;
    gap: 6px;
  }
  .sd-map-btn {
    background: rgba(15, 23, 42, 0.9);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }
  .sd-map-legend {
    position: absolute;
    bottom: 14px;
    left: 14px;
    z-index: 900;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 7px 11px;
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    max-width: 85%;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  /* ── Modal Footer ── */
  .sd-modal-footer {
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: 11px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    flex-shrink: 0;
  }

  /* ── Floating Draggable Jadwalkan Kegiatan Modal ── */
  #jadwalkanModal {
    pointer-events: none !important;
  }
  #jadwalkanModal .modal-dialog {
    pointer-events: none !important;
    transition: transform 0.08s ease-out;
  }
  #jadwalkanModal .modal-dialog.sd-is-dragging {
    transition: none !important;
  }
  #jadwalkanModal .modal-content {
    pointer-events: auto !important;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.34), 0 0 0 1.5px rgba(99, 102, 241, 0.38) !important;
  }
  .sd-draggable-header {
    cursor: grab !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    touch-action: none !important;
  }
  .sd-draggable-header.sd-grabbing {
    cursor: grabbing !important;
  }
  .sd-drag-hint-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.2);
    color: inherit;
    opacity: 0.85;
    margin-left: auto;
    margin-right: 8px;
    pointer-events: none;
    white-space: nowrap;
  }
</style>

<div class="modal fade" id="smartDispatchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Top Control Bar -->
      <div class="sd-modal-header">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#4f46e5,#0ea5e9);display:flex;align-items:center;justify-content:center;">
            <i class="material-icons" style="color:#fff;font-size:20px;">alt_route</i>
          </div>
          <div>
            <div style="font-size:15px;font-weight:800;letter-spacing:0.01em;">Auto-Susun Rute Searah & Manual Override</div>
            <div style="font-size:11px;color:#94a3b8;">Kelompokkan jadwal satu arah otomatis, lalu geser tim/urutan/jam bebas sebelum disimpan</div>
          </div>
        </div>

        <div class="sd-header-controls">
          <label class="sd-ctrl-chip" title="Tanggal Jadwal">
            <i class="material-icons" style="font-size:15px;color:#38bdf8;">event</i>
            <span>Tanggal:</span>
            <input type="date" id="sdTanggal" value="<?= date('Y-m-d') ?>">
          </label>
          <label class="sd-ctrl-chip" title="Jam mulai default untuk titik pertama">
            <i class="material-icons" style="font-size:15px;color:#a5b4fc;">schedule</i>
            <span>Mulai:</span>
            <input type="time" id="sdStartTime" value="09:00">
          </label>
          <button type="button" class="sd-btn-bar" onclick="SmartDispatchApp.addEmptyTeam()">
            <i class="material-icons" style="font-size:15px;">group_add</i> Tambah Tim
          </button>
          <button type="button" class="sd-btn-bar sd-btn-bar-accent" onclick="SmartDispatchApp.runAutoSusun(true)" title="Susun ulang klaster searah otomatis">
            <i class="material-icons" style="font-size:15px;">auto_fix_high</i> Susun Searah Otomatis
          </button>
          <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <!-- Split Body: Left Editable Draft + Right Leaflet Map -->
      <div class="sd-split-body">
        <div class="sd-left-pane" id="sdLeftPane">
          <div style="text-align:center;padding:48px 16px;color:#64748b;">Memuat data rute & teknisi...</div>
        </div>
        <div class="sd-right-pane">
          <div class="sd-map-Floating">
            <button type="button" class="sd-map-btn" onclick="SmartDispatchApp.cycleTileLayer()">🗺️ <span id="sdTileLabel">Google Maps</span></button>
            <button type="button" class="sd-map-btn" onclick="SmartDispatchApp.fitAllBounds()">⛶ Semua Titik</button>
          </div>
          <div id="sdMapLegend" class="sd-map-legend"></div>
          <div id="sdRouteMap"></div>
        </div>
      </div>

      <!-- Footer -->
      <div class="sd-modal-footer">
        <div id="sdFooterSummary" style="font-size:12.5px;font-weight:600;color:#334155;">Siap menyusun jadwal.</div>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
          <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#334155;cursor:pointer;margin:0;">
            <input type="checkbox" id="sdSendWaCheck" checked style="width:15px;height:15px;accent-color:#4f46e5;">
            Kirim Notifikasi WA ke Teknisi
          </label>
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="font-size:12px;font-weight:700;padding:8px 16px;border-radius:9px;">Batal</button>
          <button type="button" id="sdBtnSaveBatch" onclick="SmartDispatchApp.saveAll()" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:9px;padding:8px 20px;font-size:12.5px;font-weight:800;box-shadow:0 4px 12px rgba(16,185,129,0.3);cursor:pointer;">
            <i class="material-icons" style="font-size:15px;vertical-align:middle;margin-right:4px;">check_circle</i> Terapkan & Simpan Jadwal
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const TEAM_COLORS = ['#2563eb', '#059669', '#ea580c', '#7c3aed', '#e11d48', '#0891b2', '#ca8a04'];
  const JKT_PIVOT = { lat: -6.2088, lon: 106.8456 };
  const osrmCache = {};

  function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function estimateLeg(lat1, lon1, lat2, lon2) {
    if (lat1 == null || lon1 == null || lat2 == null || lon2 == null) return null;
    const cacheKey = `${Number(lat1).toFixed(4)},${Number(lon1).toFixed(4)}->${Number(lat2).toFixed(4)},${Number(lon2).toFixed(4)}`;
    if (osrmCache[cacheKey]) {
      const c = osrmCache[cacheKey];
      return {
        km: c.km,
        minutes: c.minutes,
        status: c.km <= 8.5 ? 'searah' : (c.km <= 16.5 ? 'sedang' : 'lawan_arah'),
        coords: c.coords,
        fromOsrm: true
      };
    }
    const roadKm = Math.round(haversineKm(lat1, lon1, lat2, lon2) * 1.35 * 10) / 10;
    const minutes = Math.max(6, Math.round((roadKm / 24) * 60 + 4));
    return {
      km: roadKm,
      minutes,
      status: roadKm <= 8.5 ? 'searah' : (roadKm <= 16.5 ? 'sedang' : 'lawan_arah'),
      coords: null,
      fromOsrm: false
    };
  }

  function parseMinutes(hhmm) {
    if (!hhmm || typeof hhmm !== 'string' || !hhmm.includes(':')) return 9 * 60;
    const [h, m] = hhmm.split(':').map(n => parseInt(n, 10) || 0);
    return h * 60 + m;
  }

  function formatMinutes(totalMin) {
    const clamped = Math.min(22 * 60 + 45, Math.max(6 * 60, totalMin));
    const hh = String(Math.floor(clamped / 60)).padStart(2, '0');
    const mm = String(clamped % 60).padStart(2, '0');
    return `${hh}:${mm}`;
  }

  /* ═══════════════════════════════════════════════════════════════════════
     1. WAITING LIST CHECKBOXES + WILAYAH FILTER PILLS
     ═══════════════════════════════════════════════════════════════════════ */
  window.updateSdSelectedBadge = function() {
    const checked = document.querySelectorAll('.sd-wl-check:checked').length;
    document.querySelectorAll('.sd-sel-count').forEach(el => {
      el.textContent = checked > 0 ? `${checked} Dipilih` : 'Semua';
    });
  };

  window.filterWaitingByZone = function(zone, btnEl, containerSelector, cardSelector) {
    const bar = btnEl.parentElement;
    bar.querySelectorAll('.sd-zone-pill-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    const wrap = document.querySelector(containerSelector);
    if (!wrap) return;
    wrap.querySelectorAll(cardSelector).forEach(card => {
      const z = card.getAttribute('data-zone') || '';
      card.style.display = (zone === 'ALL' || z === zone) ? '' : 'none';
    });
  };

  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('sd-wl-check')) {
      updateSdSelectedBadge();
    }
  });

  /* ═══════════════════════════════════════════════════════════════════════
     2. HYBRID AUTO-SUSUN RUTE SEARAH + MANUAL OVERRIDE APP
     ═══════════════════════════════════════════════════════════════════════ */
  const state = {
    modalInstance: null,
    tanggal: '',
    technicians: [],
    waitingMap: {},
    teams: [],         // [{ uid, ketua_id, teknisi_ids: [], existing_stops: [], stops: [id, ...] }]
    postponedIds: [],  // waiting item IDs excluded from active teams
    stopTimes: {},     // { [kegiatanId]: '09:00' }
    manualLockedTime: {}, // { [kegiatanId]: true }
    map: null,
    tileIdx: 0,
    tileLayer: null,
    mapLayers: [],
    osrmTimer: null
  };

  const TILES = [
    { name: 'Google Maps', url: 'https://{s}.google.com/vt/lyrs=m&hl=id&x={x}&y={y}&z={z}', subdomains: ['mt0','mt1','mt2','mt3'] },
    { name: 'Jalan OSM', url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', subdomains: ['a','b','c'] }
  ];

  function ensureMap() {
    if (!state.map) {
      state.map = L.map('sdRouteMap', { zoomControl: true }).setView([JKT_PIVOT.lat, JKT_PIVOT.lon], 11);
      state.tileLayer = L.tileLayer(TILES[0].url, { subdomains: TILES[0].subdomains, maxZoom: 20 }).addTo(state.map);
    }
    setTimeout(() => state.map && state.map.invalidateSize(), 180);
  }

  function tekName(tid) {
    const t = state.technicians.find(x => x.id === Number(tid));
    return t ? t.nama : `Teknisi #${tid}`;
  }

  function initDefaultTeams(apiData, activeIds) {
    state.teams = [];
    let uidCounter = 1;

    // 1) Load teams already scheduled on selected date
    if (Array.isArray(apiData.existing_teams) && apiData.existing_teams.length > 0) {
      apiData.existing_teams.forEach(et => {
        state.teams.push({
          uid: uidCounter++,
          ketua_id: Number(et.ketua_id),
          teknisi_ids: et.teknisi_ids.map(Number),
          existing_stops: et.existing_stops || [],
          stops: []
        });
      });
    }

    // Desired team count based on waiting volume (roughly 2-3 stops per team, up to 4 default teams)
    const desiredTeams = Math.min(4, Math.max(1, Math.ceil(activeIds.length / 2.5)));
    const usedTechs = new Set();
    state.teams.forEach(tm => tm.teknisi_ids.forEach(id => usedTechs.add(id)));

    // 2) Fill remaining slots from recent 2-week team templates first
    if (state.teams.length < desiredTeams && Array.isArray(apiData.recent_templates)) {
      for (const tpl of apiData.recent_templates) {
        if (state.teams.length >= desiredTeams) break;
        const tids = (tpl.teknisi_ids || []).map(Number).filter(id => !usedTechs.has(id));
        if (tids.length === 0) continue;
        tids.forEach(id => usedTechs.add(id));
        const ketua = tids.includes(Number(tpl.ketua_id)) ? Number(tpl.ketua_id) : tids[0];
        state.teams.push({
          uid: uidCounter++,
          ketua_id: ketua,
          teknisi_ids: tids,
          existing_stops: [],
          stops: []
        });
      }
    }

    // 3) Pair remaining available technicians if more teams still needed
    const freeTechs = state.technicians.map(t => t.id).filter(id => !usedTechs.has(id));
    let ptr = 0;
    while (state.teams.length < desiredTeams && ptr < freeTechs.length) {
      const pair = freeTechs.slice(ptr, ptr + 2);
      ptr += 2;
      state.teams.push({
        uid: uidCounter++,
        ketua_id: pair[0],
        teknisi_ids: pair,
        existing_stops: [],
        stops: []
      });
    }

    if (state.teams.length === 0 && state.technicians.length > 0) {
      const first = state.technicians[0].id;
      state.teams.push({ uid: 1, ketua_id: first, teknisi_ids: [first], existing_stops: [], stops: [] });
    }
  }

  // 2-opt local refinement to remove any crossing legs within a single team's corridor
  function optimizeTeamOrder(stopIds, startAnchor) {
    if (stopIds.length <= 1) return stopIds.slice();
    const remaining = stopIds.slice();
    const ordered = [];

    let curLat = startAnchor ? startAnchor.lat : null;
    let curLon = startAnchor ? startAnchor.lon : null;

    // If no existing anchor, start from the outermost stop relative to central Jakarta so path flows one-way
    if (curLat == null || curLon == null) {
      let bestIdx = 0;
      let maxPivotDist = -1;
      remaining.forEach((id, idx) => {
        const it = state.waitingMap[id];
        if (it && it.lat != null) {
          const d = haversineKm(JKT_PIVOT.lat, JKT_PIVOT.lon, it.lat, it.lon);
          if (d > maxPivotDist) {
            maxPivotDist = d;
            bestIdx = idx;
          }
        }
      });
      const firstId = remaining.splice(bestIdx, 1)[0];
      ordered.push(firstId);
      const firstIt = state.waitingMap[firstId];
      curLat = firstIt ? firstIt.lat : null;
      curLon = firstIt ? firstIt.lon : null;
    }

    // Nearest-neighbor chain
    while (remaining.length > 0) {
      if (curLat == null || curLon == null) {
        ordered.push(...remaining);
        break;
      }
      let bestIdx = 0;
      let bestDist = Infinity;
      remaining.forEach((id, idx) => {
        const it = state.waitingMap[id];
        const d = (it && it.lat != null) ? haversineKm(curLat, curLon, it.lat, it.lon) : 9999;
        if (d < bestDist) {
          bestDist = d;
          bestIdx = idx;
        }
      });
      const nextId = remaining.splice(bestIdx, 1)[0];
      ordered.push(nextId);
      const nextIt = state.waitingMap[nextId];
      if (nextIt && nextIt.lat != null) {
        curLat = nextIt.lat;
        curLon = nextIt.lon;
      }
    }

    // Quick open-path 2-opt pass for 3+ stops (eliminates any crossover including tail segment)
    if (ordered.length >= 3) {
      const pts = ordered.map(id => state.waitingMap[id]);
      if (pts.every(p => p && p.lat != null)) {
        let improved = true;
        let guard = 0;
        while (improved && guard++ < 20) {
          improved = false;
          for (let i = 0; i < ordered.length - 1; i++) {
            if (i === 0 && !startAnchor) continue;
            for (let k = i + 1; k < ordered.length; k++) {
              const a1 = (i === 0) ? startAnchor : pts[i - 1];
              const b1 = pts[i];
              const a2 = pts[k];
              const b2 = (k + 1 < ordered.length) ? pts[k + 1] : null;
              const curCost = haversineKm(a1.lat, a1.lon, b1.lat, b1.lon) + (b2 ? haversineKm(a2.lat, a2.lon, b2.lat, b2.lon) : 0);
              const swapCost = haversineKm(a1.lat, a1.lon, a2.lat, a2.lon) + (b2 ? haversineKm(b1.lat, b1.lon, b2.lat, b2.lon) : 0);
              if (swapCost + 0.15 < curCost) {
                const rev = ordered.slice(i, k + 1).reverse();
                ordered.splice(i, k - i + 1, ...rev);
                const revPts = pts.slice(i, k + 1).reverse();
                pts.splice(i, k - i + 1, ...revPts);
                improved = true;
              }
            }
          }
        }
      }
    }

    return ordered;
  }

  function getTeamStartAnchor(team) {
    if (team.existing_stops && team.existing_stops.length > 0) {
      for (let i = team.existing_stops.length - 1; i >= 0; i--) {
        const es = team.existing_stops[i];
        if (es.lat != null && es.lon != null) {
          return { lat: es.lat, lon: es.lon, label: `${es.customer} (${es.waktu})` };
        }
      }
    }
    // Check live GPS if today
    for (const tid of team.teknisi_ids) {
      const tk = state.technicians.find(x => x.id === tid);
      if (tk && tk.live_lat != null && tk.live_lon != null) {
        return { lat: tk.live_lat, lon: tk.live_lon, label: `GPS ${tk.nama.split(' ')[0]}` };
      }
    }
    return null;
  }

  function recomputeAutoTimesForTeam(team, forceOverwriteManual = false) {
    const startInput = document.getElementById('sdStartTime')?.value || '09:00';
    let cursorMin = parseMinutes(startInput);
    let prevCoord = null;

    if (team.existing_stops && team.existing_stops.length > 0) {
      const lastEx = team.existing_stops[team.existing_stops.length - 1];
      cursorMin = Math.max(cursorMin, parseMinutes(lastEx.waktu) + (lastEx.duration_min || 105));
      if (lastEx.lat != null && lastEx.lon != null) {
        prevCoord = { lat: lastEx.lat, lon: lastEx.lon };
      }
    } else {
      const anchor = getTeamStartAnchor(team);
      if (anchor) prevCoord = { lat: anchor.lat, lon: anchor.lon };
    }

    team.stops.forEach((id, idx) => {
      const item = state.waitingMap[id];
      if (!item) return;
      let legMin = 0;
      if (prevCoord && item.lat != null && item.lon != null) {
        const leg = estimateLeg(prevCoord.lat, prevCoord.lon, item.lat, item.lon);
        if (leg) legMin = leg.minutes;
      }
      if (idx > 0 || (team.existing_stops && team.existing_stops.length > 0)) {
        cursorMin += legMin;
        cursorMin = Math.ceil(cursorMin / 15) * 15;
      }
      if (forceOverwriteManual || !state.manualLockedTime[id] || !state.stopTimes[id]) {
        state.stopTimes[id] = formatMinutes(cursorMin);
        if (forceOverwriteManual) delete state.manualLockedTime[id];
      } else {
        cursorMin = parseMinutes(state.stopTimes[id]);
      }
      cursorMin += (item.duration_min || 105);
      if (item.lat != null && item.lon != null) {
        prevCoord = { lat: item.lat, lon: item.lon };
      }
    });
  }

  function runAutoSusun(resetManualTimes = false) {
    if (state.teams.length === 0) return;

    // Collect all active item IDs currently in teams (not postponed)
    const activeIds = [];
    state.teams.forEach(t => {
      activeIds.push(...t.stops);
      t.stops = [];
    });
    if (activeIds.length === 0) {
      renderAll();
      return;
    }

    const locatedIds = activeIds.filter(id => state.waitingMap[id]?.lat != null && state.waitingMap[id]?.lon != null);
    const unlocatedIds = activeIds.filter(id => state.waitingMap[id]?.lat == null || state.waitingMap[id]?.lon == null);

    const K = state.teams.length;
    const centroids = new Array(K).fill(null);
    const lockedSeed = new Array(K).fill(false);

    // 1) Lock anchors for teams that already have existing stops/live GPS
    state.teams.forEach((tm, idx) => {
      const anc = getTeamStartAnchor(tm);
      if (anc) {
        centroids[idx] = { lat: anc.lat, lon: anc.lon };
        lockedSeed[idx] = !!(tm.existing_stops && tm.existing_stops.length > 0);
      }
    });

    // 2) Deterministic Farthest-Point (k-means++) seeding for unanchored teams so West & East never mix
    if (locatedIds.length > 0) {
      for (let tIdx = 0; tIdx < K; tIdx++) {
        if (centroids[tIdx]) continue;
        const existingSeeds = centroids.filter(Boolean);
        let chosenItem = state.waitingMap[locatedIds[0]];

        if (existingSeeds.length === 0) {
          // Pick westernmost/outermost point as first clean seed
          let bestScore = -Infinity;
          locatedIds.forEach(id => {
            const it = state.waitingMap[id];
            const score = haversineKm(JKT_PIVOT.lat, JKT_PIVOT.lon, it.lat, it.lon) - it.lon * 2;
            if (score > bestScore) { bestScore = score; chosenItem = it; }
          });
        } else {
          // Pick item maximizing minimum distance to all existing seeds
          let maxMinDist = -1;
          locatedIds.forEach(id => {
            const it = state.waitingMap[id];
            const minD = Math.min(...existingSeeds.map(s => haversineKm(s.lat, s.lon, it.lat, it.lon)));
            if (minD > maxMinDist) { maxMinDist = minD; chosenItem = it; }
          });
        }
        centroids[tIdx] = { lat: chosenItem.lat, lon: chosenItem.lon };
      }

      // 3) Two-pass directional clustering with gentle duration balance penalty
      let buckets = Array.from({ length: K }, () => []);
      for (let pass = 0; pass < 2; pass++) {
        buckets = Array.from({ length: K }, () => []);
        const loadMins = state.teams.map(tm =>
          (tm.existing_stops || []).reduce((acc, s) => acc + (s.duration_min || 105), 0)
        );

        locatedIds.forEach(id => {
          const it = state.waitingMap[id];
          let bestT = 0;
          let bestCost = Infinity;
          for (let tIdx = 0; tIdx < K; tIdx++) {
            const c = centroids[tIdx];
            const roadKm = haversineKm(c.lat, c.lon, it.lat, it.lon) * 1.35;
            const overloadKmPenalty = Math.max(0, loadMins[tIdx] - 180) * 0.045;
            const cost = roadKm + overloadKmPenalty;
            if (cost < bestCost) {
              bestCost = cost;
              bestT = tIdx;
            }
          }
          buckets[bestT].push(id);
          loadMins[bestT] += (it.duration_min || 105);
        });

        // Re-center unlocked centroids
        for (let tIdx = 0; tIdx < K; tIdx++) {
          if (!lockedSeed[tIdx] && buckets[tIdx].length > 0) {
            const sumLat = buckets[tIdx].reduce((s, id) => s + state.waitingMap[id].lat, 0);
            const sumLon = buckets[tIdx].reduce((s, id) => s + state.waitingMap[id].lon, 0);
            centroids[tIdx] = { lat: sumLat / buckets[tIdx].length, lon: sumLon / buckets[tIdx].length };
          }
        }
      }

      // 4) Order stops within each team along a 1-way corridor
      state.teams.forEach((tm, tIdx) => {
        tm.stops = optimizeTeamOrder(buckets[tIdx], getTeamStartAnchor(tm));
      });
    }

    // Distribute any unlocated items to lightest team
    unlocatedIds.forEach(id => {
      let lightest = state.teams[0];
      state.teams.forEach(tm => {
        if (tm.stops.length < lightest.stops.length) lightest = tm;
      });
      lightest.stops.push(id);
    });

    state.teams.forEach(tm => recomputeAutoTimesForTeam(tm, resetManualTimes));
    renderAll();
    fetchOsrmRoutesAsync();
  }

  async function fetchOsrmRoutesAsync() {
    clearTimeout(state.osrmTimer);
    state.osrmTimer = setTimeout(async () => {
      let updatedAny = false;
      for (const tm of state.teams) {
        const pts = [];
        const anc = getTeamStartAnchor(tm);
        if (anc) pts.push({ lat: anc.lat, lon: anc.lon });
        tm.stops.forEach(id => {
          const it = state.waitingMap[id];
          if (it && it.lat != null && it.lon != null) pts.push({ lat: it.lat, lon: it.lon });
        });
        for (let i = 0; i < pts.length - 1; i++) {
          const A = pts[i], B = pts[i + 1];
          const key = `${Number(A.lat).toFixed(4)},${Number(A.lon).toFixed(4)}->${Number(B.lat).toFixed(4)},${Number(B.lon).toFixed(4)}`;
          if (osrmCache[key]) continue;
          try {
            const ctrl = new AbortController();
            const tid = setTimeout(() => ctrl.abort(), 2500);
            const url = `https://router.project-osrm.org/route/v1/driving/${A.lon},${A.lat};${B.lon},${B.lat}?overview=full&geometries=geojson`;
            const res = await fetch(url, { signal: ctrl.signal });
            clearTimeout(tid);
            if (res.ok) {
              const j = await res.json();
              if (j.routes && j.routes[0]) {
                const r = j.routes[0];
                osrmCache[key] = {
                  km: Math.round((r.distance / 1000) * 10) / 10,
                  minutes: Math.max(5, Math.round((r.duration / 60) * 1.25)), // +25% Jakarta traffic factor
                  coords: r.geometry.coordinates.map(c => [c[1], c[0]])
                };
                updatedAny = true;
              }
            }
          } catch (_) {
            // Silent fallback to Haversine road estimate
          }
        }
      }
      if (updatedAny) {
        renderLeftPane();
        renderMap();
        renderFooterSummary();
      }
    }, 120);
  }

  function renderLeftPane() {
    const pane = document.getElementById('sdLeftPane');
    if (!pane) return;

    // Detect duplicate technician assignment across active teams
    const tekUsage = {};
    state.teams.forEach(tm => {
      if (tm.stops.length === 0 && (!tm.existing_stops || tm.existing_stops.length === 0)) return;
      tm.teknisi_ids.forEach(tid => { tekUsage[tid] = (tekUsage[tid] || 0) + 1; });
    });

    let html = '';

    state.teams.forEach((tm, tIdx) => {
      const color = TEAM_COLORS[tIdx % TEAM_COLORS.length];
      let totalKm = 0;
      let totalDriveMin = 0;
      let prevPt = getTeamStartAnchor(tm);

      tm.stops.forEach(id => {
        const it = state.waitingMap[id];
        if (prevPt && it && it.lat != null) {
          const leg = estimateLeg(prevPt.lat, prevPt.lon, it.lat, it.lon);
          if (leg) { totalKm += leg.km; totalDriveMin += leg.minutes; }
        }
        if (it && it.lat != null) prevPt = { lat: it.lat, lon: it.lon };
      });

      const dupMembers = tm.teknisi_ids.filter(tid => tekUsage[tid] > 1);
      const anggotaIds = tm.teknisi_ids.filter(tid => tid !== tm.ketua_id);

      // Ketua options
      const ketuaOpts = state.technicians.map(tk =>
        `<option value="${tk.id}" ${tk.id === tm.ketua_id ? 'selected' : ''}>👑 ${esc(tk.nama)}</option>`
      ).join('');

      // Add-member options
      const addMemberOpts = state.technicians
        .filter(tk => !tm.teknisi_ids.includes(tk.id))
        .map(tk => `<option value="${tk.id}">${esc(tk.nama)}</option>`)
        .join('');

      html += `
      <div class="sd-team-card" style="border-top: 4px solid ${color};">
        <div class="sd-team-head">
          <div class="sd-team-toprow">
            <div class="sd-team-badge">
              <span class="sd-color-dot" style="background:${color};"></span>
              <span>Tim ${tIdx + 1}</span>
              <span class="sd-team-stats">${tm.stops.length} Jadwal Baru · ${totalKm.toFixed(1)} km (~${totalDriveMin} mnt jalan)</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;">
              <button type="button" class="sd-move-btn" style="width:auto;padding:0 8px;font-size:10.5px;" title="Hitung ulang jam otomatis berurutan" onclick="SmartDispatchApp.autoTimeSingleTeam(${tm.uid})">⏱ Urut Jam</button>
              ${state.teams.length > 1 ? `<button type="button" class="sd-move-btn" style="color:#dc2626;" title="Hapus Tim ini (jadwal dipindah ke daftar tunda)" onclick="SmartDispatchApp.removeTeam(${tm.uid})">&times;</button>` : ''}
            </div>
          </div>

          <div class="sd-member-bar">
            <select class="sd-ketua-select" onchange="SmartDispatchApp.changeKetua(${tm.uid}, this.value)" title="Pilih Ketua Tim">
              ${ketuaOpts}
            </select>
            ${anggotaIds.map(aid => `
              <span class="sd-member-chip">
                ${esc(tekName(aid))}
                <button type="button" onclick="SmartDispatchApp.removeMember(${tm.uid}, ${aid})" title="Lepas anggota">&times;</button>
              </span>
            `).join('')}
            ${addMemberOpts ? `
              <select class="sd-add-member-select" onchange="if(this.value){SmartDispatchApp.addMember(${tm.uid}, this.value); this.value='';}">
                <option value="">+ Anggota Tim...</option>
                ${addMemberOpts}
              </select>
            ` : ''}
          </div>
          ${dupMembers.length > 0 ? `<div style="font-size:10.5px;color:#b45309;background:#fef3c7;padding:3px 8px;border-radius:6px;font-weight:700;">⚠️ Teknisi ${esc(dupMembers.map(tekName).join(', '))} juga terdaftar di tim lain pada tanggal ini.</div>` : ''}
        </div>

        <div class="sd-stops-list">
      `;

      // Render existing scheduled stops (locked reference context)
      if (tm.existing_stops && tm.existing_stops.length > 0) {
        tm.existing_stops.forEach(es => {
          html += `
            <div class="sd-existing-stop">
              <div>
                <span style="font-weight:800;color:#0f172a;">🔒 ${esc(es.waktu)}</span> ·
                <strong>${esc(es.customer)}</strong>
                <span style="font-size:10px;background:${es.zone_bg};color:${es.zone_color};padding:1px 7px;border-radius:999px;font-weight:700;margin-left:4px;">${esc(es.zone_label)}</span>
              </div>
              <span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;">Jadwal Eksisting</span>
            </div>
          `;
        });
      }

      if (tm.stops.length === 0) {
        html += `<div style="padding:14px;text-align:center;font-size:11.5px;color:#94a3b8;border:1px dashed #e2e8f0;border-radius:8px;">Belum ada jadwal baru di tim ini. Pindahkan dari tim lain atau daftar tunda di bawah.</div>`;
      } else {
        let legOrigin = getTeamStartAnchor(tm);
        tm.stops.forEach((id, sIdx) => {
          const it = state.waitingMap[id];
          if (!it) return;

          if (legOrigin && it.lat != null && it.lon != null) {
            const leg = estimateLeg(legOrigin.lat, legOrigin.lon, it.lat, it.lon);
            if (leg) {
              const cls = leg.status === 'searah' ? 'sd-leg-ok' : (leg.status === 'sedang' ? 'sd-leg-mid' : 'sd-leg-warn');
              const icon = leg.status === 'searah' ? '🟢 Searah' : (leg.status === 'sedang' ? '🟡 Cukup Dekat' : '🔴 ⚠️ Lintas Arah / Jauh');
              const fromTxt = sIdx === 0 && legOrigin.label ? ` dari ${esc(legOrigin.label)}` : '';
              html += `<div class="sd-leg-connector ${cls}">${icon} · +${leg.km} km (~${leg.minutes} mnt)${fromTxt}</div>`;
            }
          }

          const moveOpts = state.teams.map((otherTm, oIdx) =>
            `<option value="${otherTm.uid}" ${otherTm.uid === tm.uid ? 'selected' : ''}>Tim ${oIdx + 1} (${esc(tekName(otherTm.ketua_id).split(' ')[0])})</option>`
          ).join('') + `<option value="POSTPONE">⏸ Tunda / Keluarkan</option>`;

          const jamVal = state.stopTimes[id] || '09:00';
          const estTag = (!it.is_exact && it.lat != null)
            ? `<span style="font-size:9.5px;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;font-weight:700;" title="Titik koordinat belum dipin presisi, diestimasi dari nama wilayah">~Estimasi Alamat</span>`
            : (it.lat == null ? `<span style="font-size:9.5px;background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:4px;font-weight:700;">Tanpa Koordinat</span>` : '');

          html += `
            <div class="sd-stop-item" id="sd-stop-card-${id}" onmouseenter="SmartDispatchApp.focusPin(${id})" style="border-left: 3.5px solid ${color};">
              <div class="sd-stop-top">
                <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                  <span class="sd-stop-num" style="background:${color};">${sIdx + 1}</span>
                  <input type="time" class="sd-time-input" value="${esc(jamVal)}" onchange="SmartDispatchApp.updateStopTime(${id}, this.value)" title="Ubah jam manual">
                  <span style="font-size:10px;font-weight:800;text-transform:uppercase;background:#f1f5f9;color:#334155;padding:2px 7px;border-radius:6px;">${esc(it.kegiatan)}</span>
                  <span style="font-size:10px;font-weight:700;background:${it.zone_bg};color:${it.zone_color};border:1px solid ${it.zone_border};padding:2px 8px;border-radius:999px;">📍 ${esc(it.zone_label)}</span>
                  ${estTag}
                </div>
                <div style="display:flex;align-items:center;gap:4px;">
                  <button type="button" class="sd-move-btn" ${sIdx === 0 ? 'disabled style="opacity:0.35;"' : ''} onclick="SmartDispatchApp.moveStopOrder(${tm.uid}, ${sIdx}, -1)" title="Geser naik lebih pagi">&uarr;</button>
                  <button type="button" class="sd-move-btn" ${sIdx === tm.stops.length - 1 ? 'disabled style="opacity:0.35;"' : ''} onclick="SmartDispatchApp.moveStopOrder(${tm.uid}, ${sIdx}, 1)" title="Geser turun lebih siang">&darr;</button>
                  <select class="sd-team-move-select" onchange="SmartDispatchApp.reassignStop(${id}, ${tm.uid}, this.value)" title="Pindahkan ke tim lain secara manual">
                    ${moveOpts}
                  </select>
                </div>
              </div>
              <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:2px;">${esc(it.customer)} <span style="font-size:10.5px;font-weight:500;color:#94a3b8;">#${esc(it.kode)}</span></div>
              <div style="font-size:11px;color:#64748b;line-height:1.35;">${esc(it.alamat || '-')}</div>
              ${it.keterangan ? `<div style="font-size:10.5px;color:#475569;font-style:italic;margin-top:3px;">"${esc(it.keterangan)}"</div>` : ''}
            </div>
          `;

          if (it.lat != null && it.lon != null) {
            legOrigin = { lat: it.lat, lon: it.lon, label: it.customer };
          }
        });
      }

      html += `</div></div>`;
    });

    // Render Postponed / Unselected Waiting Items Pool
    if (state.postponedIds.length > 0) {
      html += `
        <div style="background:#f8fafc;border:1.5px dashed #94a3b8;border-radius:12px;padding:12px;margin-top:10px;">
          <div style="font-size:12px;font-weight:800;color:#475569;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
            <span>⏸ Belum Masuk Rute / Ditunda (${state.postponedIds.length})</span>
            <button type="button" class="sd-move-btn" style="width:auto;padding:2px 10px;font-size:11px;" onclick="SmartDispatchApp.includeAllPostponed()">+ Masukkan Semua Searah</button>
          </div>
      `;
      state.postponedIds.forEach(id => {
        const it = state.waitingMap[id];
        if (!it) return;
        const assignOpts = state.teams.map((tm, idx) => {
          let distHint = '';
          const lastId = tm.stops[tm.stops.length - 1];
          const ref = lastId ? state.waitingMap[lastId] : getTeamStartAnchor(tm);
          if (ref && ref.lat != null && it.lat != null) {
            const leg = estimateLeg(ref.lat, ref.lon, it.lat, it.lon);
            if (leg) distHint = ` · +${leg.km} km`;
          }
          return `<option value="${tm.uid}">+ Masukkan ke Tim ${idx + 1} (${esc(tekName(tm.ketua_id).split(' ')[0])}${distHint})</option>`;
        }).join('');

        html += `
          <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <div style="min-width:0;flex:1;">
              <div style="font-size:12.5px;font-weight:700;color:#1e293b;">
                ${esc(it.customer)}
                <span style="font-size:10px;font-weight:700;background:${it.zone_bg};color:${it.zone_color};padding:1.5px 7px;border-radius:999px;margin-left:4px;">📍 ${esc(it.zone_label)}</span>
              </div>
              <div style="font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(it.kegiatan.toUpperCase())} · ${esc(it.alamat || '-')}</div>
            </div>
            <select class="sd-team-move-select" onchange="if(this.value){SmartDispatchApp.restoreFromPostponed(${id}, Number(this.value));}">
              <option value="">+ Pilih Tim...</option>
              ${assignOpts}
            </select>
          </div>
        `;
      });
      html += `</div>`;
    }

    pane.innerHTML = html;
  }

  function renderMap() {
    if (!state.map) return;
    state.mapLayers.forEach(l => state.map.removeLayer(l));
    state.mapLayers = [];
    state.pinByStopId = {};

    const legendEl = document.getElementById('sdMapLegend');
    let legendHtml = '';

    state.teams.forEach((tm, tIdx) => {
      const color = TEAM_COLORS[tIdx % TEAM_COLORS.length];
      const hasPoints = tm.stops.length > 0 || (tm.existing_stops && tm.existing_stops.length > 0);
      if (hasPoints) {
        legendHtml += `<span style="display:inline-flex;align-items:center;gap:5px;"><span class="sd-color-dot" style="background:${color};"></span>Tim ${tIdx + 1}: ${esc(tekName(tm.ketua_id).split(' ')[0])} (${tm.stops.length})</span>`;
      }

      const chainPts = [];

      // Existing stops markers
      (tm.existing_stops || []).forEach(es => {
        if (es.lat == null || es.lon == null) return;
        chainPts.push({ lat: es.lat, lon: es.lon });
        const icon = L.divIcon({
          className: '',
          html: `<div style="width:24px;height:24px;border-radius:50%;background:#334155;color:#fff;border:2px solid ${color};font-size:11px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);">🔒</div>`,
          iconSize: [24, 24],
          iconAnchor: [12, 12]
        });
        const m = L.marker([es.lat, es.lon], { icon }).bindPopup(
          `<div style="font-size:12px;"><strong>🔒 Jadwal Eksisting (${esc(es.waktu)})</strong><br>${esc(es.customer)}<br><span style="color:#64748b;">${esc(es.zone_label)}</span></div>`
        ).addTo(state.map);
        state.mapLayers.push(m);
      });

      // If no existing stops, check live GPS start anchor
      if (chainPts.length === 0) {
        const anc = getTeamStartAnchor(tm);
        if (anc) {
          chainPts.push({ lat: anc.lat, lon: anc.lon });
          const m = L.circleMarker([anc.lat, anc.lon], { radius: 6, color, fillColor: '#fff', fillOpacity: 1, weight: 3 })
            .bindPopup(`<div style="font-size:11.5px;"><strong>🏍️ Posisi Awal:</strong> ${esc(anc.label)}</div>`)
            .addTo(state.map);
          state.mapLayers.push(m);
        }
      }

      // Draft stop markers
      tm.stops.forEach((id, sIdx) => {
        const it = state.waitingMap[id];
        if (!it || it.lat == null || it.lon == null) return;
        chainPts.push({ lat: it.lat, lon: it.lon });

        const icon = L.divIcon({
          className: '',
          html: `<div style="position:relative;display:flex;flex-direction:column;align-items:center;">
                   <div style="width:28px;height:28px;border-radius:50%;background:${color};color:#fff;border:2.5px solid #fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,0,0,0.28);">${sIdx + 1}</div>
                   <span style="margin-top:2px;background:rgba(15,23,42,0.86);color:#fff;font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:4px;white-space:nowrap;">${esc(it.customer.slice(0, 14))}</span>
                 </div>`,
          iconSize: [30, 44],
          iconAnchor: [15, 14],
          popupAnchor: [0, -12]
        });

        const quickBtns = state.teams.map((otherTm, oIdx) => {
          if (otherTm.uid === tm.uid) return '';
          const c2 = TEAM_COLORS[oIdx % TEAM_COLORS.length];
          return `<button type="button" onclick="SmartDispatchApp.reassignStop(${id}, ${tm.uid}, '${otherTm.uid}')" style="border:none;background:${c2};color:#fff;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;cursor:pointer;margin:3px 3px 0 0;">Pindah ke Tim ${oIdx + 1}</button>`;
        }).join('');

        const m = L.marker([it.lat, it.lon], { icon }).bindPopup(`
          <div style="min-width:190px;font-size:12px;">
            <div style="font-weight:800;color:${color};margin-bottom:2px;">Tim ${tIdx + 1} · Stop #${sIdx + 1} (${esc(state.stopTimes[id] || '')})</div>
            <div style="font-weight:700;color:#0f172a;">${esc(it.customer)}</div>
            <div style="font-size:11px;color:#475569;margin-bottom:4px;">${esc(it.zone_label)} · ${esc(it.kegiatan)}</div>
            ${quickBtns ? `<div style="border-top:1px solid #e2e8f0;padding-top:4px;margin-top:4px;">${quickBtns}</div>` : ''}
          </div>
        `).addTo(state.map);

        state.pinByStopId[id] = m;
        state.mapLayers.push(m);
      });

      // Draw route polylines (OSRM road geometry if cached, else dashed corridor line)
      for (let i = 0; i < chainPts.length - 1; i++) {
        const A = chainPts[i], B = chainPts[i + 1];
        const leg = estimateLeg(A.lat, A.lon, B.lat, B.lon);
        const lineColor = (leg && leg.status === 'lawan_arah') ? '#e11d48' : color;
        if (leg && leg.coords && leg.coords.length > 1) {
          const poly = L.polyline(leg.coords, { color: lineColor, weight: 4.5, opacity: 0.85 }).addTo(state.map);
          state.mapLayers.push(poly);
        } else {
          const poly = L.polyline([[A.lat, A.lon], [B.lat, B.lon]], { color: lineColor, weight: 3.5, opacity: 0.8, dashArray: '7, 7' }).addTo(state.map);
          state.mapLayers.push(poly);
        }
      }
    });

    if (legendEl) {
      legendEl.innerHTML = legendHtml || '<span>Belum ada titik rute aktif</span>';
    }
  }

  function renderFooterSummary() {
    const el = document.getElementById('sdFooterSummary');
    const saveBtn = document.getElementById('sdBtnSaveBatch');
    if (!el) return;

    let totalAssigned = 0;
    let activeTeamCount = 0;
    let warnCount = 0;

    state.teams.forEach(tm => {
      if (tm.stops.length > 0) activeTeamCount++;
      totalAssigned += tm.stops.length;
      let prev = getTeamStartAnchor(tm);
      tm.stops.forEach(id => {
        const it = state.waitingMap[id];
        if (prev && it && it.lat != null) {
          const leg = estimateLeg(prev.lat, prev.lon, it.lat, it.lon);
          if (leg && leg.status === 'lawan_arah') warnCount++;
        }
        if (it && it.lat != null) prev = { lat: it.lat, lon: it.lon };
      });
    });

    let warnHtml = warnCount > 0
      ? ` · <span style="color:#be123c;background:#fff1f2;padding:2px 8px;border-radius:999px;font-weight:700;">⚠️ ${warnCount} loncatan jauh (&gt;16.5 km)</span>`
      : ` · <span style="color:#059669;font-weight:700;">✅ Semua rute searah</span>`;

    el.innerHTML = `Siap menjadwalkan <strong>${totalAssigned} kegiatan</strong> ke <strong>${activeTeamCount} tim</strong>${totalAssigned > 0 ? warnHtml : ''}`;
    if (saveBtn) saveBtn.disabled = (totalAssigned === 0);
  }

  function renderAll() {
    renderLeftPane();
    renderMap();
    renderFooterSummary();
  }

  window.SmartDispatchApp = {
    async openModal() {
      const modalEl = document.getElementById('smartDispatchModal');
      if (!state.modalInstance) state.modalInstance = new bootstrap.Modal(modalEl);
      state.modalInstance.show();
      ensureMap();
      await this.loadForDate(document.getElementById('sdTanggal').value, true);
    },

    async loadForDate(tgl, initialOpen = false) {
      state.tanggal = tgl;
      document.getElementById('sdLeftPane').innerHTML = `<div style="text-align:center;padding:48px 16px;color:#64748b;">Memuat jadwal & koordinat...</div>`;

      try {
        const res = await fetch(`ajax_smart_dispatch.php?action=load&tanggal=${encodeURIComponent(tgl)}`);
        const data = await res.json();
        if (data.status !== 'success') throw new Error(data.message || 'Gagal memuat data');

        state.technicians = data.technicians || [];
        state.waitingMap = {};
        (data.waiting_items || []).forEach(it => { state.waitingMap[it.id] = it; });

        const allIds = (data.waiting_items || []).map(it => it.id);
        let selectedIds = allIds;

        if (initialOpen) {
          const checkedEls = Array.from(document.querySelectorAll('.sd-wl-check:checked'));
          if (checkedEls.length > 0) {
            const set = new Set(checkedEls.map(c => Number(c.value)));
            selectedIds = allIds.filter(id => set.has(id));
          }
          state.stopTimes = {};
          state.manualLockedTime = {};
        } else {
          // Preserve current active selection when switching date
          const prevActive = new Set();
          state.teams.forEach(t => t.stops.forEach(id => prevActive.add(id)));
          if (prevActive.size > 0) selectedIds = allIds.filter(id => prevActive.has(id));
        }

        const selSet = new Set(selectedIds);
        state.postponedIds = allIds.filter(id => !selSet.has(id));

        initDefaultTeams(data, selectedIds);
        if (state.teams.length > 0) {
          state.teams[0].stops = selectedIds.slice();
        }
        runAutoSusun(false);
        setTimeout(() => this.fitAllBounds(), 260);
      } catch (err) {
        document.getElementById('sdLeftPane').innerHTML = `<div style="padding:28px;color:#dc2626;text-align:center;font-weight:700;">Gagal memuat: ${esc(err.message)}</div>`;
      }
    },

    runAutoSusun(resetManual) {
      runAutoSusun(!!resetManual);
      this.fitAllBounds();
    },

    addEmptyTeam() {
      const used = new Set();
      state.teams.forEach(t => t.teknisi_ids.forEach(id => used.add(id)));
      const free = state.technicians.find(t => !used.has(t.id)) || state.technicians[0];
      if (!free) return alert('Tidak ada data teknisi aktif.');
      const nextUid = state.teams.reduce((m, t) => Math.max(m, t.uid), 0) + 1;
      state.teams.push({
        uid: nextUid,
        ketua_id: free.id,
        teknisi_ids: [free.id],
        existing_stops: [],
        stops: []
      });
      renderAll();
    },

    removeTeam(uid) {
      const idx = state.teams.findIndex(t => t.uid === uid);
      if (idx === -1 || state.teams.length <= 1) return;
      const removed = state.teams.splice(idx, 1)[0];
      removed.stops.forEach(id => {
        if (!state.postponedIds.includes(id)) state.postponedIds.push(id);
      });
      renderAll();
    },

    changeKetua(uid, newKetuaId) {
      const tm = state.teams.find(t => t.uid === uid);
      if (!tm) return;
      const kid = Number(newKetuaId);
      tm.ketua_id = kid;
      if (!tm.teknisi_ids.includes(kid)) tm.teknisi_ids.unshift(kid);
      renderAll();
      fetchOsrmRoutesAsync();
    },

    addMember(uid, tekId) {
      const tm = state.teams.find(t => t.uid === uid);
      const tid = Number(tekId);
      if (!tm || !tid || tm.teknisi_ids.includes(tid)) return;
      tm.teknisi_ids.push(tid);
      renderAll();
    },

    removeMember(uid, tekId) {
      const tm = state.teams.find(t => t.uid === uid);
      const tid = Number(tekId);
      if (!tm || tm.teknisi_ids.length <= 1) return;
      tm.teknisi_ids = tm.teknisi_ids.filter(x => x !== tid);
      if (tm.ketua_id === tid) tm.ketua_id = tm.teknisi_ids[0];
      renderAll();
    },

    moveStopOrder(uid, stopIdx, delta) {
      const tm = state.teams.find(t => t.uid === uid);
      if (!tm) return;
      const targetIdx = stopIdx + delta;
      if (targetIdx < 0 || targetIdx >= tm.stops.length) return;
      const tmp = tm.stops[stopIdx];
      tm.stops[stopIdx] = tm.stops[targetIdx];
      tm.stops[targetIdx] = tmp;
      recomputeAutoTimesForTeam(tm, false);
      renderAll();
      fetchOsrmRoutesAsync();
    },

    updateStopTime(kegiatanId, newTime) {
      if (!newTime) return;
      state.stopTimes[kegiatanId] = newTime;
      state.manualLockedTime[kegiatanId] = true;
      renderMap();
    },

    autoTimeSingleTeam(uid) {
      const tm = state.teams.find(t => t.uid === uid);
      if (!tm) return;
      recomputeAutoTimesForTeam(tm, true);
      renderAll();
    },

    reassignStop(kegiatanId, fromUid, toValue) {
      const fromTm = state.teams.find(t => t.uid === Number(fromUid));
      if (fromTm) {
        fromTm.stops = fromTm.stops.filter(id => id !== Number(kegiatanId));
        recomputeAutoTimesForTeam(fromTm, false);
      }
      if (toValue === 'POSTPONE') {
        if (!state.postponedIds.includes(Number(kegiatanId))) {
          state.postponedIds.push(Number(kegiatanId));
        }
      } else {
        const toTm = state.teams.find(t => t.uid === Number(toValue));
        if (toTm && !toTm.stops.includes(Number(kegiatanId))) {
          toTm.stops.push(Number(kegiatanId));
          recomputeAutoTimesForTeam(toTm, false);
        }
      }
      renderAll();
      fetchOsrmRoutesAsync();
    },

    restoreFromPostponed(kegiatanId, targetUid) {
      state.postponedIds = state.postponedIds.filter(id => id !== Number(kegiatanId));
      const tm = state.teams.find(t => t.uid === Number(targetUid)) || state.teams[0];
      if (tm) {
        tm.stops.push(Number(kegiatanId));
        recomputeAutoTimesForTeam(tm, false);
      }
      renderAll();
      fetchOsrmRoutesAsync();
    },

    includeAllPostponed() {
      if (state.teams.length === 0) return;
      state.teams[0].stops.push(...state.postponedIds);
      state.postponedIds = [];
      runAutoSusun(false);
    },

    focusPin(kegiatanId) {
      const m = state.pinByStopId && state.pinByStopId[kegiatanId];
      if (m) m.openPopup();
    },

    fitAllBounds() {
      if (!state.map) return;
      const latlngs = [];
      state.teams.forEach(tm => {
        (tm.existing_stops || []).forEach(es => { if (es.lat != null) latlngs.push([es.lat, es.lon]); });
        tm.stops.forEach(id => {
          const it = state.waitingMap[id];
          if (it && it.lat != null) latlngs.push([it.lat, it.lon]);
        });
      });
      if (latlngs.length > 0) {
        state.map.fitBounds(L.latLngBounds(latlngs), { padding: [36, 36], maxZoom: 14 });
      }
    },

    cycleTileLayer() {
      if (!state.map) return;
      state.tileIdx = (state.tileIdx + 1) % TILES.length;
      state.map.removeLayer(state.tileLayer);
      state.tileLayer = L.tileLayer(TILES[state.tileIdx].url, { subdomains: TILES[state.tileIdx].subdomains, maxZoom: 20 }).addTo(state.map);
      const lbl = document.getElementById('sdTileLabel');
      if (lbl) lbl.textContent = TILES[state.tileIdx].name;
    },

    async saveAll() {
      const btn = document.getElementById('sdBtnSaveBatch');
      const tgl = document.getElementById('sdTanggal').value;
      const sendWa = document.getElementById('sdSendWaCheck').checked;
      if (!tgl) return alert('Pilih tanggal terlebih dahulu!');

      const assignments = [];
      state.teams.forEach(tm => {
        tm.stops.forEach(id => {
          assignments.push({
            kegiatan_id: id,
            tanggal: tgl,
            jam: state.stopTimes[id] || '09:00',
            teknisi_ids: tm.teknisi_ids,
            ketua_id: tm.ketua_id
          });
        });
      });

      if (assignments.length === 0) return alert('Belum ada kegiatan yang dimasukkan ke tim.');

      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<i class="material-icons" style="font-size:15px;vertical-align:middle;">hourglass_top</i> Menyimpan ${assignments.length} Jadwal...`;

      try {
        const res = await fetch('ajax_smart_dispatch.php?action=save_batch', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tanggal: tgl, assignments })
        });
        const j = await res.json();
        if (j.status !== 'success') throw new Error(j.message || 'Gagal menyimpan jadwal.');

        if (sendWa && Array.isArray(j.saved_items)) {
          for (let i = 0; i < j.saved_items.length; i++) {
            const sv = j.saved_items[i];
            btn.innerHTML = `<i class="material-icons" style="font-size:15px;vertical-align:middle;">send</i> Mengirim WA (${i + 1}/${j.saved_items.length})...`;
            try {
              const fd = new URLSearchParams();
              fd.append('kegiatanId', sv.kegiatan_id);
              fd.append('tanggal', sv.tanggal);
              fd.append('jam', sv.jam);
              sv.teknisi_ids.forEach(tid => fd.append('teknisi[]', tid));
              await fetch('wa-msg.php', { method: 'POST', body: fd });
            } catch (_) {}
          }
        }

        btn.innerHTML = `<i class="material-icons" style="font-size:15px;vertical-align:middle;">check_circle</i> Tersimpan (${j.saved_count})!`;
        setTimeout(() => location.reload(), 500);
      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Gagal menyimpan jadwal: ' + err.message);
      }
    }
  };

  document.getElementById('sdTanggal')?.addEventListener('change', function() {
    if (this.value) SmartDispatchApp.loadForDate(this.value, false);
  });

  document.getElementById('sdStartTime')?.addEventListener('change', function() {
    state.teams.forEach(tm => recomputeAutoTimesForTeam(tm, false));
    renderAll();
  });

  /* ═══════════════════════════════════════════════════════════════════════
     3. SINGLE-TICKET JADWALKAN MODAL PROXIMITY & DIRECTION ENHANCER
     ═══════════════════════════════════════════════════════════════════════ */
  window.SmartDispatchSingle = {
    lastProximity: {},

    async loadProximity(opts) {
      // opts: { tanggal, kegiatanId, listContainerSelector, itemSelector, infoPrefix, checkboxSelector, ketuaRadioName, updateKetuaFn, filterFn }
      if (!opts.tanggal) return;
      const url = `cek_jadwal_teknisi.php?tanggal=${encodeURIComponent(opts.tanggal)}&with_proximity=1&kegiatan_id=${encodeURIComponent(opts.kegiatanId || 0)}`;
      const res = await fetch(url);
      const payload = await res.json();
      const schedules = payload.schedules || {};
      const proximity = payload.proximity || {};
      const target = payload.target || null;
      this.lastProximity = proximity;

      // Render Target Zone Banner inside single modal
      let banner = document.getElementById(opts.bannerId);
      if (banner) {
        if (target) {
          banner.style.display = 'flex';
          banner.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:8px;flex-wrap:wrap;">
              <div style="font-size:12px;font-weight:700;color:#0f172a;">
                Target: <span>${esc(target.customer)}</span>
                <span style="font-size:10.5px;font-weight:600;color:#64748b;">(${esc(target.kegiatan)})</span>
              </div>
              <span style="font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:999px;background:${target.zone_bg};color:${target.zone_color};border:1px solid ${target.zone_border};">
                📍 ${esc(target.zone_label)}${!target.is_exact && target.lat != null ? ' (~Est)' : ''}
              </span>
            </div>
          `;
        } else {
          banner.style.display = 'none';
        }
      }

      const container = document.querySelector(opts.listContainerSelector);
      if (!container) return;

      container.querySelectorAll(opts.itemSelector).forEach(el => {
        const tid = Number(el.getAttribute('data-tek-id'));
        const infoEl = document.getElementById(opts.infoPrefix + tid);
        const jobs = schedules[tid] || [];
        const prox = proximity[tid] || { status: 'kosong', has_jobs: false };

        el.setAttribute('data-prox-status', prox.status || 'kosong');
        el.setAttribute('data-has-jobs', jobs.length > 0 ? '1' : '0');

        let html = '';

        // Proximity pill first so Admin immediately sees Searah vs Lawan Arah
        if (prox.status === 'searah') {
          html += `<span style="display:inline-block;background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:10px;font-weight:800;padding:1.5px 7px;border-radius:999px;margin:2px 4px 2px 0;">🟢 Searah · ~${prox.minutes} mnt (${prox.km} km dari ${esc(prox.from_label)})</span>`;
        } else if (prox.status === 'sedang') {
          html += `<span style="display:inline-block;background:#fef9c3;color:#854d0e;border:1px solid #fde047;font-size:10px;font-weight:700;padding:1.5px 7px;border-radius:999px;margin:2px 4px 2px 0;">🟡 Cukup Dekat · ~${prox.minutes} mnt (${prox.km} km dari ${esc(prox.from_label)})</span>`;
        } else if (prox.status === 'lawan_arah') {
          html += `<span style="display:inline-block;background:#ffe4e6;color:#9f1239;border:1px solid #fda4af;font-size:10px;font-weight:700;padding:1.5px 7px;border-radius:999px;margin:2px 4px 2px 0;">🔴 Lawan Arah · ~${prox.minutes} mnt (${prox.km} km dari ${esc(prox.from_label)})</span>`;
        }

        // Scheduled jobs chips
        if (jobs.length > 0) {
          html += jobs.map(j =>
            `<span style="display:inline-block;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;font-size:10px;font-weight:600;padding:1.5px 7px;border-radius:5px;margin:2px 3px 2px 0;">⏰ ${esc(j.waktu)} · ${esc(j.customer)} <strong style="color:${j.zone_color};">[${esc(j.zone_label)}]</strong></span>`
          ).join('');
        }

        // 1-tap Select Team Partner button if technician is already paired on that date
        if (prox.partner_ids && prox.partner_ids.length > 0) {
          const allTeamIds = [tid, ...prox.partner_ids];
          const ketuaHint = prox.was_ketua ? tid : prox.partner_ids[0];
          const jamHint = prox.suggested_jam || '';
          html += `<button type="button" onclick="event.preventDefault();event.stopPropagation();SmartDispatchSingle.quickPickTeam([${allTeamIds.join(',')}], ${ketuaHint}, '${jamHint}', '${opts.checkboxSelector}', '${opts.ketuaRadioName}', '${opts.jamInputId}')" style="border:1px solid #c7d2fe;background:#eef2ff;color:#4338ca;font-size:9.5px;font-weight:700;padding:1px 7px;border-radius:999px;cursor:pointer;margin-top:2px;">👥 Pilih Se-Tim (+${esc(prox.partner_names.map(n => n.split(' ')[0]).join(', '))}${jamHint ? ' · saran ' + jamHint : ''})</button>`;
        }

        if (infoEl) infoEl.innerHTML = html;
      });

      // Sort items: Searah (0) -> Kosong (1) -> Sedang (2) -> Unknown (3) -> Lawan Arah (4)
      const rank = { searah: 0, kosong: 1, sedang: 2, unknown: 3, lawan_arah: 4 };
      const items = Array.from(container.querySelectorAll(opts.itemSelector));
      const noResEl = opts.noResultId ? document.getElementById(opts.noResultId) : null;

      items.sort((a, b) => {
        const tidA = Number(a.getAttribute('data-tek-id'));
        const tidB = Number(b.getAttribute('data-tek-id'));
        const pA = proximity[tidA] || { status: 'kosong' };
        const pB = proximity[tidB] || { status: 'kosong' };
        const rA = rank[pA.status] ?? 3;
        const rB = rank[pB.status] ?? 3;
        if (rA !== rB) return rA - rB;
        if (pA.km != null && pB.km != null && pA.km !== pB.km) return pA.km - pB.km;
        return (a.getAttribute('data-nama') || '').localeCompare(b.getAttribute('data-nama') || '');
      });

      items.forEach(el => {
        if (noResEl) container.insertBefore(el, noResEl);
        else container.appendChild(el);
      });

      if (typeof opts.filterFn === 'function') opts.filterFn();
    },

    quickPickTeam(teamIds, ketuaId, suggestedJam, checkboxSelector, ketuaRadioName, jamInputId) {
      document.querySelectorAll(checkboxSelector).forEach(cb => {
        cb.checked = teamIds.includes(Number(cb.value));
      });
      // Trigger change on first checked checkbox so Ketua radios refresh
      const firstCb = document.querySelector(`${checkboxSelector}:checked`);
      if (firstCb) firstCb.dispatchEvent(new Event('change'));
      setTimeout(() => {
        const radio = document.querySelector(`input[name="${ketuaRadioName}"][value="${ketuaId}"]`);
        if (radio) radio.checked = true;
      }, 20);
      if (suggestedJam && jamInputId) {
        const jamEl = document.getElementById(jamInputId);
        if (jamEl && !jamEl.value) jamEl.value = suggestedJam;
      }
    }
  };

  /* ═══════════════════════════════════════════════════════════════════════
     4. DRAGGABLE FLOATING POPUP MODAL (Geser Bebas + Daftar Belakang Terlihat)
     ═══════════════════════════════════════════════════════════════════════ */
  function makeModalDraggable(modalSelector, nonBlocking = false) {
    const modalEl = document.querySelector(modalSelector);
    if (!modalEl) return;
    const dialogEl = modalEl.querySelector('.modal-dialog');
    const headerEl = modalEl.querySelector('.modal-header');
    if (!dialogEl || !headerEl) return;

    headerEl.classList.add('sd-draggable-header');
    headerEl.setAttribute('title', 'Tahan & geser untuk memindahkan posisi kotak (klik 2x untuk kembali ke tengah)');

    if (!headerEl.querySelector('.sd-drag-hint-pill')) {
      const hint = document.createElement('span');
      hint.className = 'sd-drag-hint-pill';
      hint.innerHTML = '&#8942;&#8942; Geser';
      const closeBtn = headerEl.querySelector('.btn-close');
      if (closeBtn) headerEl.insertBefore(hint, closeBtn);
      else headerEl.appendChild(hint);
    }

    let offsetX = 0;
    let offsetY = 0;
    let startX = 0;
    let startY = 0;
    let baseLeft = 0;
    let baseTop = 0;
    let width = 0;
    let headerHeight = 52;
    let dragging = false;

    function applyOffset(x, y) {
      offsetX = x;
      offsetY = y;
      dialogEl.style.transform = `translate3d(${Math.round(offsetX)}px, ${Math.round(offsetY)}px, 0)`;
    }

    headerEl.addEventListener('pointerdown', (e) => {
      if (e.button !== 0 && e.pointerType === 'mouse') return;
      if (e.target.closest('button, a, input, select, textarea, .btn-close')) return;

      const rect = dialogEl.getBoundingClientRect();
      baseLeft = rect.left - offsetX;
      baseTop = rect.top - offsetY;
      width = rect.width;
      headerHeight = headerEl.getBoundingClientRect().height || 52;

      startX = e.clientX - offsetX;
      startY = e.clientY - offsetY;
      dragging = true;

      dialogEl.classList.add('sd-is-dragging');
      headerEl.classList.add('sd-grabbing');
      try { headerEl.setPointerCapture(e.pointerId); } catch (_) {}
      e.preventDefault();
    });

    headerEl.addEventListener('pointermove', (e) => {
      if (!dragging) return;
      const rawX = e.clientX - startX;
      const rawY = e.clientY - startY;

      // Keep at least 100px horizontally & header vertically inside viewport
      const minX = 100 - (baseLeft + width);
      const maxX = window.innerWidth - 100 - baseLeft;
      const minY = 6 - baseTop;
      const maxY = window.innerHeight - headerHeight - 6 - baseTop;

      applyOffset(
        Math.min(maxX, Math.max(minX, rawX)),
        Math.min(maxY, Math.max(minY, rawY))
      );
    });

    function stopDrag(e) {
      if (!dragging) return;
      dragging = false;
      dialogEl.classList.remove('sd-is-dragging');
      headerEl.classList.remove('sd-grabbing');
      try { headerEl.releasePointerCapture(e.pointerId); } catch (_) {}
    }

    headerEl.addEventListener('pointerup', stopDrag);
    headerEl.addEventListener('pointercancel', stopDrag);

    // Double-click header resets back to center
    headerEl.addEventListener('dblclick', (e) => {
      if (e.target.closest('button, a, input, .btn-close')) return;
      applyOffset(0, 0);
    });

    if (nonBlocking) {
      modalEl.addEventListener('shown.bs.modal', () => {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
      });
    }
  }

  makeModalDraggable('#jadwalkanModal', true);
  makeModalDraggable('#locationModal', false);
  makeModalDraggable('#reasonModal', false);
})();
</script>
