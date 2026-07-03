<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Kegiatan Baru";
$currentPage = "Today";

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <!-- Leaflet Map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    /* ── Premium Form Card Styling ── */
    .card-premium {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.02);
      margin-bottom: 24px;
    }
    
    .section-header-premium {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      background: #1e293b;
      color: #fff;
    }
    
    .section-header-premium h6 {
      margin: 0;
      font-size: 14px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
    }
    
    .card-body-premium {
      padding: 32px;
    }

    /* ── Form Inputs ── */
    .form-group-premium {
      margin-bottom: 20px;
    }
    
    .form-label-premium {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .input-premium {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 11px 16px !important;
      font-size: 13.5px;
      color: #1e293b;
      background-color: #fff;
      transition: all 0.2s ease;
    }
    
    .input-premium:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
      outline: none;
    }

    /* ── Map Container ── */
    #map {
      height: 280px;
      width: 100%;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-top: 10px;
    }

    /* ── Sales Select Card Grid ── */
    .sales-select-card {
      display: block;
      cursor: pointer;
      margin-bottom: 0;
      user-select: none;
    }
    
    .sales-card-content {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      transition: all 0.22s ease;
      position: relative;
    }
    
    .sales-card-content:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
      transform: translateY(-1px);
    }
    
    .sales-select-input:checked + .sales-card-content {
      background: #f0f7ff;
      border-color: #3b82f6;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.05);
    }
    
    .avatar-initials-small {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #f1f5f9;
      color: #64748b;
      font-size: 12px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }
    
    .sales-select-input:checked + .sales-card-content .avatar-initials-small {
      background: #3b82f6;
      color: #fff;
    }
    
    .sales-card-details {
      display: flex;
      flex-direction: column;
    }
    
    .sales-card-name {
      font-size: 13px; font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
    }
    
    .sales-card-role {
      font-size: 10.5px; color: #64748b;
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .sales-card-checkbox {
      margin-left: auto;
      color: #cbd5e1;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }
    
    .sales-select-input:checked + .sales-card-content .sales-card-checkbox {
      color: #3b82f6;
    }

    /* ── Submit Button ── */
    .btn-submit-premium {
      background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
      color: #fff !important;
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-size: 14px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 8px;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
      transition: all 0.22s ease;
      cursor: pointer;
    }
    
    .btn-submit-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    .btn-submit-premium:active {
      transform: translateY(0);
    }
    
    .btn-submit-premium .material-symbols-outlined {
      font-size: 18px;
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
        
        <div class="section-header-premium">
          <h6>
            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add_task</span>
            Form Tambah Kegiatan Sales Baru
          </h6>
        </div>

        <div class="card-body-premium">
          <?php
          // Ambil customer beserta nama wilayahnya
          $customerResult = mysqli_query($conn, "
              SELECT c.id, c.nama, c.id_wilayah, w.nama AS nama_wilayah 
              FROM sales_customer c 
              LEFT JOIN wilayah w ON c.id_wilayah = w.id 
              WHERE c.deleted_at IS NULL 
              ORDER BY c.nama ASC
          ");

          // Ambil sales beserta nama wilayahnya
          $salesResult = mysqli_query($conn, "
              SELECT s.id, s.nama, s.id_wilayah, w.nama AS nama_wilayah 
              FROM sales s 
              LEFT JOIN wilayah w ON s.id_wilayah = w.id 
              WHERE s.deleted_at IS NULL 
              ORDER BY s.nama ASC
          ");

          // Set timezone ke Jakarta
          date_default_timezone_set('Asia/Jakarta');

          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $jadwal = $_POST['jadwal'];
              $visit = $_POST['visit'];
              $id_customer = $_POST['id_customer'];
              
              // Kolom Lokasi
              $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
              $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
              $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
              $location_address = !empty($_POST['location_address']) ? $_POST['location_address'] : NULL;

              $status = 'dijadwalkan';
              $selectedSales = $_POST['sales'] ?? [];

              // Insert ke kegiatan_sales (mendukung kolom koordinat baru)
              $stmt = $conn->prepare("
                  INSERT INTO kegiatan_sales (jadwal, keterangan, id_customer, status, lat, lon, rad, alamat_lokasi, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
              ");
              $stmt->bind_param("ssisssss", $jadwal, $visit, $id_customer, $status, $lat, $lon, $rad, $location_address);
              $stmt->execute();
              $kegiatanId = $stmt->insert_id;
              $stmt->close();

              // Insert ke team_kegiatan_sales
              foreach ($selectedSales as $id_sales) {
                  $getNama = mysqli_query($conn, "SELECT nama FROM sales WHERE id = '$id_sales' LIMIT 1");
                  $namaSales = mysqli_fetch_assoc($getNama)['nama'] ?? '';

                  $stmtTeam = $conn->prepare("
                      INSERT INTO team_kegiatan_sales (id_kegiatan_sales, id_sales, nama_sales, created_at, updated_at) 
                      VALUES (?, ?, ?, NOW(), NOW())
                  ");
                  $stmtTeam->bind_param("iis", $kegiatanId, $id_sales, $namaSales);
                  $stmtTeam->execute();
                  $stmtTeam->close();
              }

              echo "<div class='alert alert-success text-white font-weight-bold mb-4' style='background: #10b981; border: none; border-radius: 10px; padding: 14px 20px;'>
                      <span class='material-symbols-outlined' style='vertical-align: middle; margin-right: 8px;'>check_circle</span>
                      Kegiatan kunjungan sales berhasil ditambahkan!
                    </div>";
          }
          ?>

          <form method="POST">
            <div class="row">
              
              <!-- LEFT COLUMN: Form Fields -->
              <div class="col-lg-7" style="border-right: 1px solid #f1f5f9; padding-right: 28px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                    <div style="width:3px;height:16px;background:#3b82f6;border-radius:2px;"></div>
                    <span style="font-size:13px;font-weight:700;color:#1e293b;text-transform: uppercase;">Informasi Kegiatan</span>
                </div>

                <div class="form-group-premium">
                  <label for="jadwal" class="form-label-premium">Jadwal Visit</label>
                  <input type="datetime-local" class="input-premium" name="jadwal" required>
                </div>

                <div class="form-group-premium">
                  <label for="visit" class="form-label-premium">Keperluan Kunjungan</label>
                  <textarea class="input-premium" name="visit" rows="4" placeholder="Tuliskan keterangan detail rencana kunjungan sales..." required></textarea>
                </div>

                <div class="form-group-premium">
                  <label for="id_customer" class="form-label-premium">Pilih Customer (Toko/Mitra)</label>
                  <select class="input-premium" id="id_customer" name="id_customer" required>
                    <option value="">-- Pilih Customer --</option>
                    <?php while ($c = mysqli_fetch_assoc($customerResult)): ?>
                      <option value="<?php echo $c['id']; ?>" data-id-wilayah="<?php echo $c['id_wilayah']; ?>">
                        <?php echo htmlspecialchars($c['nama'] . ' - [' . ($c['nama_wilayah'] ?? 'Tanpa Wilayah') . ']'); ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>

                <div class="form-group-premium">
                  <label class="form-label-premium">Pilih Sales Agent Terlibat</label>
                  <div class="row g-3" id="sales-list-container">
                    <?php while ($s = mysqli_fetch_assoc($salesResult)): ?>
                      <div class="col-md-6 sales-checkbox-item" data-id-wilayah="<?php echo $s['id_wilayah']; ?>">
                        <label class="sales-select-card" for="sales<?php echo $s['id']; ?>">
                          <input class="sales-select-input d-none" type="checkbox" name="sales[]" value="<?php echo $s['id']; ?>" id="sales<?php echo $s['id']; ?>">
                          <div class="sales-card-content">
                            <div class="avatar-initials-small">
                              <?php 
                                $words = explode(' ', $s['nama']);
                                echo strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                              ?>
                            </div>
                            <div class="sales-card-details">
                              <span class="sales-card-name"><?php echo htmlspecialchars($s['nama']); ?></span>
                              <span class="sales-card-role">
                                Sales Agent 
                                <span class="badge bg-secondary text-capitalize" style="font-size: 8px; padding: 2px 6px; letter-spacing: 0.05em; font-weight: 700;">
                                  <?php echo htmlspecialchars($s['nama_wilayah'] ?? 'Tanpa Wilayah'); ?>
                                </span>
                              </span>
                            </div>
                            <div class="sales-card-checkbox">
                              <span class="material-symbols-outlined">check_circle</span>
                            </div>
                          </div>
                        </label>
                      </div>
                    <?php endwhile; ?>
                  </div>
                </div>
              </div>

              <!-- RIGHT COLUMN: Map Selector -->
              <div class="col-lg-5" style="padding-left: 28px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                    <div style="width:3px;height:16px;background:#10b981;border-radius:2px;"></div>
                    <span style="font-size:13px;font-weight:700;color:#1e293b;text-transform: uppercase;">Lokasi &amp; Peta</span>
                    <span style="font-size:11px;color:#94a3b8;font-weight:400;">(Opsional)</span>
                </div>

                <div class="form-group-premium">
                  <label class="form-label-premium">Cari Koordinat / Alamat</label>
                  <div class="d-flex gap-2">
                    <input type="text" id="gmap_search" class="input-premium" placeholder="Masukkan koordinat atau alamat...">
                    <button type="button" id="gmap_search_btn" class="btn bg-gradient-info text-white font-weight-bold" style="border-radius:10px; padding: 10px 16px; font-size:11px; display:inline-flex; align-items:center; gap:4px; margin-bottom:0;">
                      <span class="material-symbols-outlined" style="font-size:16px;">search</span>CARI
                    </button>
                  </div>
                </div>

                <!-- Leaflet Map widget -->
                <div id="map"></div>

                <div class="row g-2 mt-3">
                  <div class="col-5">
                    <label class="form-label-premium" style="font-size: 9px;">Latitude</label>
                    <input type="text" id="lat_display" class="input-premium" placeholder="-6.xxxxx" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                  </div>
                  <div class="col-5">
                    <label class="form-label-premium" style="font-size: 9px;">Longitude</label>
                    <input type="text" id="lon_display" class="input-premium" placeholder="106.xxxxx" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                  </div>
                  <div class="col-2">
                    <label class="form-label-premium" style="font-size: 9px;">Radius</label>
                    <input type="number" id="radius_input" class="input-premium" value="100" style="font-size:12px; padding: 8px 6px !important; text-align:center;">
                  </div>
                </div>

                <!-- Hidden parameters to submit -->
                <input type="hidden" id="lat" name="lat">
                <input type="hidden" id="lon" name="lon">
                <input type="hidden" id="radius" name="radius" value="100">
                <input type="hidden" id="location_address" name="location_address">
              </div>

            </div>

            <div class="mt-5">
              <button type="submit" class="btn-submit-premium" style="width: 100%; justify-content: center;">
                <span class="material-symbols-outlined">save</span>
                Simpan Rencana Kegiatan &amp; Lokasi
              </button>
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
  
  <!-- Leaflet Map JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Auto-Filter & Map Script -->
  <script>
    // ── Auto-Filter Sales based on Customer Region ──
    document.getElementById('id_customer').addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const customerWilayah = selectedOption.getAttribute('data-id-wilayah');
      
      document.querySelectorAll('.sales-checkbox-item').forEach(item => {
        const salesWilayah = item.getAttribute('data-id-wilayah');
        if (!customerWilayah || customerWilayah === "" || salesWilayah === customerWilayah) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
          const checkbox = item.querySelector('.sales-select-input');
          if (checkbox) checkbox.checked = false;
        }
      });
    });

    // ── Interactive Leaflet Map Logic ──
    document.addEventListener('DOMContentLoaded', function() {
      const defaultLat = -6.13037113;
      const defaultLon = 106.75144230;
      const defaultRad = 100;

      // Init Map
      const map = L.map('map').setView([defaultLat, defaultLon], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      let marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);
      let circle = L.circle([defaultLat, defaultLon], { radius: defaultRad, color: '#10b981', fillColor: '#10b981', fillOpacity: 0.15 }).addTo(map);

      function updateAllData(latlng, rad) {
        const r = parseInt(rad) || defaultRad;
        marker.setLatLng(latlng);
        circle.setLatLng(latlng).setRadius(r);
        map.setView(latlng, 16);

        // Populate Form Fields
        document.getElementById('lat').value = latlng.lat;
        document.getElementById('lon').value = latlng.lng;
        document.getElementById('lat_display').value = latlng.lat.toFixed(6);
        document.getElementById('lon_display').value = latlng.lng.toFixed(6);
        document.getElementById('radius').value = r;
        document.getElementById('radius_input').value = r;

        // Reverse Geocoding via Nominatim API
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=id`)
          .then(res => res.json())
          .then(data => {
            document.getElementById('location_address').value = data?.display_name || '';
          })
          .catch(() => {
            document.getElementById('location_address').value = '';
          });
      }

      // Map click event
      map.on('click', function(e) {
        updateAllData(e.latlng, document.getElementById('radius_input').value);
      });

      // Marker drag event
      marker.on('dragend', function() {
        updateAllData(marker.getLatLng(), document.getElementById('radius_input').value);
      });

      // Radius input change event
      document.getElementById('radius_input').addEventListener('input', function() {
        updateAllData(marker.getLatLng(), this.value);
      });

      // Address / Coordinates Search
      document.getElementById('gmap_search_btn').addEventListener('click', function() {
        const query = document.getElementById('gmap_search').value.trim();
        if (query === "") return;

        // Check if query is latitude, longitude
        const coordsRegex = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/;
        if (coordsRegex.test(query)) {
          const parts = query.split(',');
          const lat = parseFloat(parts[0]);
          const lon = parseFloat(parts[1]);
          updateAllData(L.latLng(lat, lon), document.getElementById('radius_input').value);
        } else {
          // Geocode Search Nominatim
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id&accept-language=id`)
            .then(res => res.json())
            .then(data => {
              if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                updateAllData(L.latLng(lat, lon), document.getElementById('radius_input').value);
              } else {
                alert("Alamat atau koordinat tidak ditemukan.");
              }
            });
        }
      });

      // Init inputs with default values
      updateAllData(L.latLng(defaultLat, defaultLon), defaultRad);
    });
  </script>

</body>

</html>