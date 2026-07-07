<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Kegiatan Baru";
$currentPage = "Today";

if (!isset($_GET['id'])) {
    echo "ID kegiatan tidak ditemukan.";
    exit();
}

$kegiatan_id = $_GET['id'];

// Ambil data kegiatan
$sql = "SELECT ks.*, sc.nama AS nama_customer FROM kegiatan_sales ks
        LEFT JOIN sales_customer sc ON ks.id_customer = sc.id
        WHERE ks.id = ? AND ks.deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kegiatan_id);
$stmt->execute();
$result = $stmt->get_result();
$kegiatan = $result->fetch_assoc();

if (!$kegiatan) {
    echo "Kegiatan tidak ditemukan.";
    exit();
}

// Ambil tim yang sudah dipilih
$existing_team = [];
$res = mysqli_query($conn, "SELECT id_sales FROM team_kegiatan_sales WHERE id_kegiatan_sales = '$kegiatan_id' AND deleted_at IS NULL");
while ($r = mysqli_fetch_assoc($res)) {
    $existing_team[] = $r['id_sales'];
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jadwal = $_POST['jadwal'];
    $keterangan = $_POST['keterangan'];
    $team_sales = $_POST['sales'] ?? [];
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
    $alamat_lokasi = !empty($_POST['location_address']) ? $_POST['location_address'] : NULL;

    // Update kegiatan_sales (termasuk lokasi geofence)
    $stmt = $conn->prepare("UPDATE kegiatan_sales SET jadwal = ?, keterangan = ?, lat = ?, lon = ?, rad = ?, alamat_lokasi = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssi", $jadwal, $keterangan, $lat, $lon, $rad, $alamat_lokasi, $kegiatan_id);
    $stmt->execute();

    // Hitung perubahan tim sales
    $team_sales_ids = array_map('intval', $team_sales);
    $sales_to_soft_delete = array_diff($existing_team, $team_sales_ids);
    $sales_to_insert = array_diff($team_sales_ids, $existing_team);

    // Soft delete yang tidak dipilih lagi
    foreach ($sales_to_soft_delete as $id_sales) {
        $conn->query("UPDATE team_kegiatan_sales SET deleted_at = NOW(), updated_at = NOW() WHERE id_kegiatan_sales = '$kegiatan_id' AND id_sales = '$id_sales'");
    }

    // Insert yang baru dipilih
    foreach ($sales_to_insert as $id_sales) {
        $getNama = mysqli_query($conn, "SELECT nama FROM sales WHERE id = '$id_sales' LIMIT 1");
        $namaSales = mysqli_fetch_assoc($getNama)['nama'] ?? '';

        $stmt = $conn->prepare("INSERT INTO team_kegiatan_sales (id_kegiatan_sales, id_sales, nama_sales, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("iis", $kegiatan_id, $id_sales, $namaSales);
        $stmt->execute();
    }

    header("Location: index-sa.php");
    exit();
}

// Ambil semua sales
$sales = mysqli_query($conn, "SELECT * FROM sales WHERE deleted_at IS NULL");

// Ambil tim yang sudah dipilih (refresh after POST processing)
$existing_team = [];
$res = mysqli_query($conn, "SELECT id_sales FROM team_kegiatan_sales WHERE id_kegiatan_sales = '$kegiatan_id' AND deleted_at IS NULL");
while ($r = mysqli_fetch_assoc($res)) {
    $existing_team[] = $r['id_sales'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    #map { height: 300px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 12px; }
    .card-premium {
      background: #fff;
      border: none;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
      margin-bottom: 24px;
    }

    .card-body-premium {
      padding: 40px;
    }

    /* ── Section Divider ── */
    .section-divider {
      height: 1px;
      background: linear-gradient(to right, transparent, #e2e8f0, transparent);
      margin: 32px 0;
    }

    /* ── Form Labels ── */
    .form-group-premium {
      margin-bottom: 24px;
    }

    .form-label-premium {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .form-label-premium .material-symbols-outlined {
      font-size: 16px;
      color: #94a3b8;
    }

    /* ── Form Inputs ── */
    .input-premium {
      width: 100%;
      height: 48px !important;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 16px !important;
      font-size: 14px;
      color: #1e293b;
      background-color: #fff;
      transition: all 0.2s ease-in-out;
      box-sizing: border-box;
    }

    .input-premium:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
      outline: none;
      background-color: #fff;
    }

    .input-premium:disabled {
      background-color: #f8fafc;
      color: #94a3b8;
      cursor: not-allowed;
      border-style: dashed;
    }

    textarea.input-premium {
      height: auto !important;
      min-height: 120px;
      line-height: 1.6;
      resize: vertical;
    }

    /* ── Sales Select Card Grid ── */
    .sales-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 12px;
    }

    .sales-select-card {
      display: block;
      cursor: pointer;
      margin-bottom: 0;
      user-select: none;
    }

    .sales-select-input {
      display: none;
    }

    .sales-card-content {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      background: #fff;
      border: 1.5px solid #e2e8f0;
      border-radius: 14px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .sales-card-content:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    .sales-select-input:checked + .sales-card-content {
      background: #f0f7ff;
      border-color: #3b82f6;
      box-shadow: 0 6px 16px rgba(59, 130, 246, 0.08);
    }

    .avatar-initials-small {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
      color: #4338ca;
      font-size: 13px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.25s ease;
      flex-shrink: 0;
    }

    .sales-select-input:checked + .sales-card-content .avatar-initials-small {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      color: #fff;
    }

    .sales-card-details {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .sales-card-name {
      font-size: 13.5px; font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sales-card-role {
      font-size: 10.5px; color: #64748b;
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .sales-card-checkbox {
      margin-left: auto;
      color: #cbd5e1;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }

    .sales-card-checkbox span {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      font-size: 22px;
    }

    .sales-select-input:checked + .sales-card-content .sales-card-checkbox {
      color: #3b82f6;
    }

    .sales-select-input:checked + .sales-card-content .sales-card-checkbox span {
      font-variation-settings: 'FILL' 1, 'wght' 700, 'GRAD' 0, 'opsz' 24;
    }

    /* ── Action Buttons ── */
    .btn-submit-premium {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%);
      color: #fff !important;
      border: none;
      border-radius: 12px;
      padding: 14px 32px;
      font-size: 14px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 20px rgba(37, 99, 235, 0.25);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      text-decoration: none;
    }

    .btn-submit-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(37, 99, 235, 0.4);
      color: #fff !important;
    }

    .btn-submit-premium:active {
      transform: translateY(0);
    }

    .btn-back-premium {
      background: #f1f5f9;
      color: #475569 !important;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 14px 28px;
      font-size: 14px; font-weight: 600;
      display: inline-flex; align-items: center; gap: 8px;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      text-decoration: none;
    }

    .btn-back-premium:hover {
      background: #e2e8f0;
      border-color: #cbd5e1;
      transform: translateY(-1px);
      color: #334155 !important;
    }

    /* ── Counter Badge ── */
    .counter-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      border: 1px solid #bfdbfe;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      color: #1d4ed8;
    }

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }

    <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php
    include "nav-top.php";
    $todayDate = formatTanggal('dd MMMM yyyy');
    ?>

    <div class="container-fluid py-4">

      <div class="card-premium">
        
        <!-- ═══ Premium Gradient Header ═══ -->
        <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2563eb 100%);padding:28px 36px;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-40px;right:-20px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.04);"></div>
          <div style="position:absolute;bottom:-50px;right:100px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.03);"></div>
          <div style="position:absolute;top:10px;right:30px;width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.2);"></div>
          <div style="display:flex;align-items:center;gap:14px;position:relative;z-index:1;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.12);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.1);">
              <span class="material-symbols-outlined" style="color:#fff;font-size:22px;">edit_note</span>
            </div>
            <div>
              <h5 style="color:#fff;margin:0;font-size:18px;font-weight:700;letter-spacing:-0.3px;">Edit Kegiatan Sales</h5>
              <p style="color:rgba(255,255,255,0.6);margin:0;font-size:12px;margin-top:2px;">Ubah jadwal, keterangan, dan tim sales untuk kunjungan ini</p>
            </div>
          </div>
        </div>

        <div class="card-body-premium">
          <form method="POST" id="editForm">

            <!-- ═══ Customer (Disabled) ═══ -->
            <div class="form-group-premium">
              <label class="form-label-premium">
                <span class="material-symbols-outlined">storefront</span>
                Customer
              </label>
              <input type="text" class="input-premium" value="<?php echo htmlspecialchars($kegiatan['nama_customer']); ?>" disabled>
            </div>

            <!-- ═══ Jadwal ═══ -->
            <div class="form-group-premium">
              <label class="form-label-premium">
                <span class="material-symbols-outlined">calendar_month</span>
                Jadwal Kunjungan
              </label>
              <input type="datetime-local" name="jadwal" class="input-premium" value="<?php echo date('Y-m-d\TH:i', strtotime($kegiatan['jadwal'])); ?>" required>
            </div>

            <!-- ═══ Keterangan ═══ -->
            <div class="form-group-premium">
              <label class="form-label-premium">
                <span class="material-symbols-outlined">description</span>
                Keterangan
              </label>
              <textarea name="keterangan" class="input-premium" rows="4"><?php echo htmlspecialchars($kegiatan['keterangan']); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- ═══ Lokasi & Peta Geofence ═══ -->
            <div class="form-group-premium">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <div style="width:3px;height:16px;background:#10b981;border-radius:2px;"></div>
                <span style="font-size:12px;font-weight:800;color:#1e293b;text-transform:uppercase;letter-spacing:0.05em;">Lokasi & Peta Geofence</span>
                <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px;">(Opsional)</span>
              </div>

              <div class="form-group-premium">
                <label class="form-label-premium">
                  <i class="fa-solid fa-magnifying-glass text-xs me-1 text-primary"></i> Cari Koordinat / Alamat
                </label>
                <div class="d-flex gap-2">
                  <input type="text" id="gmap_search" class="input-premium" placeholder="Contoh: Jawa Timur atau -6.175, 106.827...">
                  <button type="button" id="gmap_search_btn" class="btn bg-gradient-info text-white font-weight-bold" style="border-radius:10px; padding: 12px 18px; font-size:11px; display:inline-flex; align-items:center; gap:4px; margin-bottom:0;">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i> CARI
                  </button>
                </div>
              </div>

              <div id="map"></div>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" id="btn_get_location" class="btn btn-outline-primary btn-sm mb-0 d-flex align-items-center gap-1 font-weight-bold" style="border-radius: 8px; font-size: 11px;">
                  <i class="fa-solid fa-location-crosshairs text-xs"></i> Dapatkan Lokasi Saya
                </button>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-xs text-secondary font-weight-bold">Radius:</span>
                  <input type="number" id="radius_input" class="input-premium" value="<?php echo $kegiatan['rad'] ?? 100; ?>" style="font-size:12px; padding: 6px 8px !important; text-align:center; width: 60px; border-radius: 8px; margin-bottom: 0;">
                  <span class="text-xs text-secondary font-weight-bold">Meter</span>
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label-premium d-flex justify-content-between mb-1" style="font-size: 10px; color:#64748b;">
                  <span>Sesuaikan Geofence Radius (Meter)</span>
                  <span id="slider_val" class="text-primary font-weight-bold"><?php echo ($kegiatan['rad'] ?? 100); ?>m</span>
                </label>
                <input type="range" id="radius_slider" min="10" max="1000" step="10" value="<?php echo $kegiatan['rad'] ?? 100; ?>" class="form-range w-100" style="accent-color: #3b82f6;">
              </div>

              <div class="row g-2 mt-2">
                <div class="col-6">
                  <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Latitude</label>
                  <input type="text" id="lat_display" class="input-premium" placeholder="-6.xxxxx" value="<?php echo $kegiatan['lat'] ?? ''; ?>" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc; border-radius: 8px;" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Longitude</label>
                  <input type="text" id="lon_display" class="input-premium" placeholder="106.xxxxx" value="<?php echo $kegiatan['lon'] ?? ''; ?>" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc; border-radius: 8px;" readonly>
                </div>
              </div>

              <input type="hidden" id="lat" name="lat" value="<?php echo $kegiatan['lat'] ?? ''; ?>">
              <input type="hidden" id="lon" name="lon" value="<?php echo $kegiatan['lon'] ?? ''; ?>">
              <input type="hidden" id="radius" name="radius" value="<?php echo $kegiatan['rad'] ?? 100; ?>">
              <input type="hidden" id="location_address" name="location_address" value="<?php echo htmlspecialchars($kegiatan['alamat_lokasi'] ?? ''); ?>">
            </div>

            <div class="section-divider"></div>

            <!-- ═══ Pilih Sales (Cards) ═══ -->
            <div class="form-group-premium">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <label class="form-label-premium" style="margin-bottom:0;">
                  <span class="material-symbols-outlined">groups</span>
                  Pilih Tim Sales
                </label>
                <div class="counter-badge" id="selectedCounter">
                  <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>
                  <span id="counterText"><?php echo count($existing_team); ?> dipilih</span>
                </div>
              </div>

              <div class="sales-grid">
                <?php while ($s = mysqli_fetch_assoc($sales)) : 
                  $nama = $s['nama'];
                  $initials = '';
                  $parts = explode(' ', trim($nama));
                  $initials = strtoupper(substr($parts[0], 0, 1));
                  if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
                  $isChecked = in_array($s['id'], $existing_team);
                ?>
                  <label class="sales-select-card">
                    <input class="sales-select-input" type="checkbox" name="sales[]" value="<?php echo $s['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?> onchange="updateCounter()">
                    <div class="sales-card-content">
                      <div class="avatar-initials-small"><?php echo $initials; ?></div>
                      <div class="sales-card-details">
                        <div class="sales-card-name"><?php echo htmlspecialchars($nama); ?></div>
                        <div class="sales-card-role">
                          <span class="material-symbols-outlined" style="font-size:12px;">badge</span>
                          Sales
                        </div>
                      </div>
                      <div class="sales-card-checkbox">
                        <span class="material-symbols-outlined">check_circle</span>
                      </div>
                    </div>
                  </label>
                <?php endwhile; ?>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- ═══ Action Buttons ═══ -->
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <button type="submit" class="btn-submit-premium">
                <span class="material-symbols-outlined" style="font-size:20px;">save</span>
                Simpan Perubahan
              </button>
              <a href="index-sa.php" class="btn-back-premium">
                <span class="material-symbols-outlined" style="font-size:20px;">arrow_back</span>
                Kembali
              </a>
            </div>

          </form>
        </div>
      </div>

      <?php
      include "floating-menu.php";
      include "footer.php";
      ?>
    </div>

  </main>
  <?php include "js-include.php"; ?>
  <script>
    // ── Counter untuk sales yang dipilih ──
    function updateCounter() {
      const checked = document.querySelectorAll('.sales-select-input:checked').length;
      document.getElementById('counterText').textContent = checked + ' dipilih';
    }

    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = { damping: '0.5' }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }

    // ── Leaflet Map Initialization ──
    document.addEventListener('DOMContentLoaded', function() {
      const existLat = <?php echo !empty($kegiatan['lat']) ? $kegiatan['lat'] : '-6.13037'; ?>;
      const existLon = <?php echo !empty($kegiatan['lon']) ? $kegiatan['lon'] : '106.75144'; ?>;
      const existRad = <?php echo !empty($kegiatan['rad']) ? $kegiatan['rad'] : '100'; ?>;

      const map = L.map('map').setView([existLat, existLon], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      let marker = L.marker([existLat, existLon], { draggable: true }).addTo(map);
      let circle = L.circle([existLat, existLon], {
        radius: existRad,
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.12,
        weight: 1.5,
        dashArray: '5, 5'
      }).addTo(map);

      const radInput = document.getElementById('radius_input');
      const radSlider = document.getElementById('radius_slider');
      const sliderVal = document.getElementById('slider_val');

      function syncRadius(value) {
        const r = parseInt(value) || 100;
        radInput.value = r;
        radSlider.value = r;
        sliderVal.innerText = r + 'm';
        circle.setRadius(r);
        document.getElementById('radius').value = r;
      }

      radInput.addEventListener('input', function() { syncRadius(this.value); });
      radSlider.addEventListener('input', function() { syncRadius(this.value); });

      function updateAllData(latlng, rad) {
        const r = parseInt(rad) || 100;
        marker.setLatLng(latlng);
        circle.setLatLng(latlng).setRadius(r);
        map.setView(latlng, 16);
        document.getElementById('lat').value = latlng.lat;
        document.getElementById('lon').value = latlng.lng;
        document.getElementById('lat_display').value = latlng.lat.toFixed(6);
        document.getElementById('lon_display').value = latlng.lng.toFixed(6);
        syncRadius(r);
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=id`)
          .then(res => res.json())
          .then(data => { document.getElementById('location_address').value = data?.display_name || ''; })
          .catch(() => { document.getElementById('location_address').value = ''; });
      }

      map.on('click', function(e) { updateAllData(e.latlng, radInput.value); });
      marker.on('dragend', function() { updateAllData(marker.getLatLng(), radInput.value); });

      document.getElementById('btn_get_location').addEventListener('click', function() {
        const btn = this;
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mencari Lokasi...';
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            function(pos) {
              updateAllData(L.latLng(pos.coords.latitude, pos.coords.longitude), radInput.value);
              btn.disabled = false;
              btn.innerHTML = orig;
            },
            function(err) {
              alert('Gagal mendapatkan lokasi: ' + err.message);
              btn.disabled = false;
              btn.innerHTML = orig;
            },
            { enableHighAccuracy: true, timeout: 5000 }
          );
        } else {
          alert('Browser tidak mendukung Geolocation.');
          btn.disabled = false;
          btn.innerHTML = orig;
        }
      });

      document.getElementById('gmap_search_btn').addEventListener('click', function() {
        const query = document.getElementById('gmap_search').value.trim();
        if (!query) return;
        const coordsRegex = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/;
        if (coordsRegex.test(query)) {
          const parts = query.split(',');
          updateAllData(L.latLng(parseFloat(parts[0]), parseFloat(parts[1])), radInput.value);
        } else {
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id&accept-language=id`)
            .then(res => res.json())
            .then(data => {
              if (data && data.length > 0) {
                updateAllData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), radInput.value);
              } else {
                alert('Alamat tidak ditemukan.');
              }
            });
        }
      });
    });
  </script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>