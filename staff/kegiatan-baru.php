<?php
include "conn.php";
include "session.php";
$pageNow = "Kegiatan Baru";
include "get-user-data.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kegiatan'])) {
    $customer_id = $_POST['nama_cust'];
    $kegiatan = $_POST['kegiatan'];
    $jadwal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $kegiatan_relasi = !empty($_POST['kegiatan_relasi']) ? $_POST['kegiatan_relasi'] : NULL;
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
    $status = 'waiting';
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    $kode = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);

    $save_location = isset($_POST['save_location']) && $_POST['save_location'] == 'on';
    $location_alias = !empty($_POST['location_alias']) ? $_POST['location_alias'] : NULL;
    $location_address = !empty($_POST['location_address']) ? $_POST['location_address'] : NULL;

    if ($save_location && $location_alias && $lat && $lon && $customer_id) {
        $sql_save_coord = "INSERT INTO cust_coordinate (cust_id, alias, lat, lon, rad, address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_coord = mysqli_prepare($conn, $sql_save_coord);
        mysqli_stmt_bind_param($stmt_coord, "issssss", $customer_id, $location_alias, $lat, $lon, $rad, $location_address, $now);
        mysqli_stmt_execute($stmt_coord);
        mysqli_stmt_close($stmt_coord);
    }

    $sql = "INSERT INTO kegiatan (customer_id, jadwal, kegiatan, keterangan, request, status, kode, relasi, lat, lon, rad, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssssssssss", $customer_id, $jadwal, $kegiatan, $keterangan, $nmUser, $status, $kode, $kegiatan_relasi, $lat, $lon, $rad, $now, $now);
    if (mysqli_stmt_execute($stmt)) {
        echo '<script>window.location.href = "kegiatan-baru.php?status=sukses";</script>';
        exit();
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        <?php include "css/kegiatan-baru-styles.css"; ?>
        #map { height: 300px; width: 100%; border-radius: .5rem; border: 1px solid #dee2e6; cursor: pointer; }
        .dropdown { position: relative; }
        #dropdownItems { 
            display: none; position: absolute; background: white; border: 1px solid #ccc; 
            width: 100%; z-index: 1000; max-height: 250px; overflow-y: auto; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dropdown-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .dropdown-item:hover { background-color: #f8f9fa; }
        #dropdownSearch { width: 100%; padding: 8px; border: none; border-bottom: 1px solid #ddd; position: sticky; top: 0; }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row d-flex flex-row align-items-start">
                <div class="col-12 col-lg-7 mt-4 mb-4">
                    <div class="card" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                        <!-- Premium Header -->
                        <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#3b82f6 100%);padding:20px 28px;position:relative;overflow:hidden;">
                            <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.08);"></div>
                            <div style="position:absolute;bottom:-30px;right:60px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);"></div>
                            <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:1;">
                                <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;">
                                    <i class="material-icons" style="color:#fff;font-size:20px;">add_task</i>
                                </div>
                                <div>
                                    <h5 style="color:#fff;margin:0;font-size:16px;font-weight:700;letter-spacing:-0.3px;">Tambah Kegiatan Baru</h5>
                                    <p style="color:rgba(255,255,255,0.7);margin:0;font-size:11px;">Buat jadwal kegiatan teknisi</p>
                                </div>
                            </div>
                        </div>
                        <!-- Form Body - 2 Column Landscape -->
                        <div class="card-body" style="padding:24px 28px;">
                            <form method="POST" action="kegiatan-baru.php" id="kegiatanForm">
                                <div class="row">
                                    <!-- LEFT COLUMN: Form Fields -->
                                    <div class="col-12 col-md-6" style="border-right:1px solid #f0f0f0;padding-right:24px;">
                                        <div style="margin-bottom:16px;">
                                            <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                                <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">person</i>Nama Customer
                                            </label>
                                            <div class="dropdown">
                                                <button type="button" class="dropdown-button" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;background:#fff;text-align:left;font-size:13px;color:#6b7280;cursor:pointer;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">Pilih Customer ▼</button>
                                                <div id="dropdownItems">
                                                    <input type="text" id="dropdownSearch" placeholder="Cari customer...">
                                                    <?php 
                                                    $sql_cust = "SELECT id, nama, telp FROM customer WHERE deleted_at IS NULL ORDER BY nama ASC";
                                                    $result_cust = mysqli_query($conn, $sql_cust);
                                                    while ($row = mysqli_fetch_assoc($result_cust)) {
                                                        echo "<div class='dropdown-item' data-id='{$row['id']}'>{$row['nama']} - {$row['telp']}</div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <input type="hidden" id="nama_cust" name="nama_cust" required>
                                        </div>
                                        <div class="row" style="margin-bottom:16px;">
                                            <div class="col-6">
                                                <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                                    <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">category</i>Jenis Kegiatan
                                                </label>
                                                <select class="form-control" id="kegiatan" name="kegiatan" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                                                    <option value="survey">Survey</option>
                                                    <option value="pasang baru">Pasang Baru</option>
                                                    <option value="service">Service</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                                    <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">link</i>Terkait Kegiatan
                                                </label>
                                                <select class="form-control" id="kegiatan_relasi" name="kegiatan_relasi" disabled style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;background:#f9fafb;transition:all 0.2s;">
                                                    <option value="">Pilih Customer Dahulu</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div style="margin-bottom:16px;">
                                            <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                                <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">event</i>Jadwal
                                            </label>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <input type="date" class="form-control" id="tanggal_survey_date" name="tanggal_survey_date" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                                                </div>
                                                <div class="col-3">
                                                    <select class="form-control" id="tanggal_survey_time_hour" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;text-align:center;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'"></select>
                                                </div>
                                                <div class="col-3">
                                                    <select class="form-control" id="tanggal_survey_time_minute" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;text-align:center;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                                                        <option value="00">00</option>
                                                        <option value="15">15</option>
                                                        <option value="30">30</option>
                                                        <option value="45">45</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                        <div style="margin-bottom:0;">
                                            <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                                <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">notes</i>Keterangan
                                            </label>
                                            <textarea class="form-control" id="keterangan" rows="4" name="keterangan" placeholder="Keterangan tambahan..." style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;resize:vertical;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'"></textarea>
                                        </div>
                                    </div>
                                    <!-- RIGHT COLUMN: Map & Location -->
                                    <div class="col-12 col-md-6" style="padding-left:24px;">
                                        <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.5px;">
                                            <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;color:#2563eb;">location_on</i>Lokasi Absen & Radius
                                            <span style="font-weight:400;color:#9ca3af;text-transform:none;letter-spacing:0;">(Opsional)</span>
                                        </label>
                                        <div style="display:flex;gap:8px;margin-bottom:10px;">
                                            <input type="text" id="gmap_search" class="form-control" placeholder="Koordinat atau alamat..." style="flex:1;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                                            <button type="button" id="gmap_search_btn" style="padding:10px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.2s;letter-spacing:0.5px;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(37,99,235,0.35)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                                <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:2px;">search</i>CARI
                                            </button>
                                        </div>
                                        <div id="map" style="border-radius:12px;overflow:hidden;border:1.5px solid #e5e7eb;"></div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-5">
                                                <input type="text" id="lat_display" class="form-control" placeholder="Latitude" style="padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:12px;background:#f9fafb;">
                                            </div>
                                            <div class="col-5">
                                                <input type="text" id="lon_display" class="form-control" placeholder="Longitude" style="padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:12px;background:#f9fafb;">
                                            </div>
                                            <div class="col-2">
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <input type="number" class="form-control" id="radius_input" value="100" style="padding:8px 6px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:12px;text-align:center;background:#f9fafb;">
                                                    <span style="font-size:10px;color:#9ca3af;white-space:nowrap;">m</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="saveLocationContainer" class="mt-3" style="display:none;padding:12px 16px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:10px;">
                                            <div class="form-check" style="margin-bottom:8px;">
                                                <input class="form-check-input" type="checkbox" id="save_location_checkbox" name="save_location" checked>
                                                <label class="form-check-label" for="save_location_checkbox" style="font-size:12px;font-weight:500;color:#166534;">Simpan lokasi baru ini</label>
                                            </div>
                                            <div id="location_alias_input_container">
                                                <input type="text" id="location_alias" name="location_alias" class="form-control" placeholder="Alias Lokasi (Contoh: Rumah)" style="padding:8px 12px;border:1.5px solid #bbf7d0;border-radius:8px;font-size:12px;">
                                            </div>
                                        </div>
                                        <div id="locationRecommendations" class="mt-2" style="display:none;">
                                            <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Lokasi Tersimpan</label>
                                            <div id="recommendationsList" class="list-group" style="max-height:120px;overflow-y:auto;border-radius:10px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden fields & Submit -->
                                <input type="hidden" id="lat" name="lat">
                                <input type="hidden" id="lon" name="lon">
                                <input type="hidden" id="radius" name="radius">
                                <input type="hidden" id="location_address" name="location_address">
                                <div style="margin-top:20px;padding-top:16px;border-top:1px solid #f0f0f0;">
                                    <button type="submit" name="submit_kegiatan" style="width:100%;padding:12px;border:none;border-radius:12px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;font-size:14px;font-weight:700;letter-spacing:0.5px;cursor:pointer;transition:all 0.3s;box-shadow:0 4px 16px rgba(37,99,235,0.25);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(37,99,235,0.35)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 16px rgba(37,99,235,0.25)'">
                                        <i class="material-icons" style="font-size:18px;vertical-align:middle;margin-right:6px;">send</i>SUBMIT KEGIATAN
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5 mt-4 mb-4" id="outputData"></div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownButton = document.querySelector('.dropdown-button'),
                dropdownItems = document.getElementById('dropdownItems'),
                dropdownSearch = document.getElementById('dropdownSearch'),
                outputDataContainer = document.getElementById('outputData'),
                relasiSelect = document.getElementById('kegiatan_relasi'),
                recommendationsContainer = document.getElementById('locationRecommendations'),
                recommendationsList = document.getElementById('recommendationsList'),
                saveLocationContainer = document.getElementById('saveLocationContainer'),
                saveLocationCheckbox = document.getElementById('save_location_checkbox'),
                locationAliasContainer = document.getElementById('location_alias_input_container'),
                namaCustInput = document.getElementById('nama_cust');

            dropdownButton.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownItems.style.display = dropdownItems.style.display === 'block' ? 'none' : 'block';
                if (dropdownItems.style.display === 'block') dropdownSearch.focus();
            });

            dropdownSearch.addEventListener('keyup', () => {
                const filter = dropdownSearch.value.toUpperCase();
                document.querySelectorAll('.dropdown-item').forEach(item => {
                    item.style.display = item.textContent.toUpperCase().includes(filter) ? '' : 'none';
                });
            });

            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    dropdownButton.innerText = this.innerText;
                    namaCustInput.value = this.dataset.id;
                    dropdownItems.style.display = 'none';
                    fetchCustomerData(this.dataset.id);
                });
            });

            window.addEventListener('click', () => dropdownItems.style.display = 'none');
            dropdownItems.addEventListener('click', (e) => e.stopPropagation());

            function fetchCustomerData(customerId) {
                outputDataContainer.innerHTML = '<div class="card card-body"><p>Memuat riwayat...</p></div>';
                relasiSelect.innerHTML = '<option value="">Memuat...</option>';
                relasiSelect.disabled = true;
                
                fetch(`data-kegiatan-cust.php?customer_id=${customerId}`)
                    .then(res => res.json())
                    .then(data => {
                        outputDataContainer.innerHTML = data.displayHtml;
                        relasiSelect.innerHTML = '';
                        if (data.relasiOptions.length > 0) {
                            relasiSelect.disabled = false;
                            relasiSelect.add(new Option('Pilih kegiatan terkait...', ''));
                            data.relasiOptions.forEach(opt => relasiSelect.add(new Option(opt.teks, opt.kode)));
                        } else {
                            relasiSelect.add(new Option('Tidak ada riwayat', ''));
                        }

                        recommendationsList.innerHTML = '';
                        if (data.locationRecommendations.length > 0) {
                            recommendationsContainer.style.display = 'block';
                            data.locationRecommendations.forEach(loc => {
                                const btn = document.createElement('a');
                                btn.className = 'list-group-item list-group-item-action';
                                btn.innerHTML = `<b>${loc.alias}</b><br><small>${loc.address}</small>`;
                                btn.onclick = () => updateAllData(L.latLng(loc.lat, loc.lon), loc.rad);
                                recommendationsList.appendChild(btn);
                            });
                        }

                        if (data.lastKnownLocation) {
                            updateAllData(L.latLng(data.lastKnownLocation.lat, data.lastKnownLocation.lon), data.lastKnownLocation.rad);
                        }
                    });
            }

            const hourSelect = document.getElementById('tanggal_survey_time_hour');
            for (let i = 7; i < 21; i++) {
                let h = i.toString().padStart(2, '0');
                hourSelect.add(new Option(h, h));
            }

            function combineDateTime() {
                const d = document.getElementById('tanggal_survey_date').value,
                    h = document.getElementById('tanggal_survey_time_hour').value,
                    m = document.getElementById('tanggal_survey_time_minute').value;
                document.getElementById('tanggal_survey_datetime_combined').value = d ? `${d} ${h}:${m}:00` : '';
            }
            ['tanggal_survey_date', 'tanggal_survey_time_hour', 'tanggal_survey_time_minute'].forEach(id => {
                document.getElementById(id).addEventListener('change', combineDateTime);
            });

            const defaultLat = -6.13037113, defaultLon = 106.75144230, defaultRad = 100;
            const map = L.map('map').setView([defaultLat, defaultLon], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            let marker = L.marker([defaultLat, defaultLon], {draggable: true}).addTo(map);
            let circle = L.circle([defaultLat, defaultLon], {radius: defaultRad}).addTo(map);

            function updateAllData(latlng, rad) {
                const r = parseInt(rad) || defaultRad;
                marker.setLatLng(latlng);
                circle.setLatLng(latlng).setRadius(r);
                map.setView(latlng, 16);
                document.getElementById('lat').value = latlng.lat;
                document.getElementById('lon').value = latlng.lng;
                document.getElementById('lat_display').value = latlng.lat;
                document.getElementById('lon_display').value = latlng.lng;
                document.getElementById('radius').value = r;
                document.getElementById('radius_input').value = r;

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('location_address').value = data.display_name;
                        } else {
                            document.getElementById('location_address').value = '';
                        }
                    })
                    .catch(() => {
                        document.getElementById('location_address').value = '';
                    });
            }

            map.on('click', e => {
                saveLocationContainer.style.display = 'block';
                updateAllData(e.latlng, document.getElementById('radius_input').value);
            });

            marker.on('dragend', () => updateAllData(marker.getLatLng(), document.getElementById('radius_input').value));

            document.getElementById('lat_display').addEventListener('change', function() {
                const lat = parseFloat(this.value);
                const lon = parseFloat(document.getElementById('lon_display').value);
                if (!isNaN(lat) && !isNaN(lon)) {
                    saveLocationContainer.style.display = 'block';
                    updateAllData(L.latLng(lat, lon), document.getElementById('radius_input').value);
                }
            });

            document.getElementById('lon_display').addEventListener('change', function() {
                const lat = parseFloat(document.getElementById('lat_display').value);
                const lon = parseFloat(this.value);
                if (!isNaN(lat) && !isNaN(lon)) {
                    saveLocationContainer.style.display = 'block';
                    updateAllData(L.latLng(lat, lon), document.getElementById('radius_input').value);
                }
            });

            document.getElementById('gmap_search_btn').addEventListener('click', () => {
                const q = document.getElementById('gmap_search').value.trim();
                // Detect coordinate input: "lat, lng" or "lat lng"
                const coordMatch = q.match(/^(-?\d+\.?\d*)[,\s]+\s*(-?\d+\.?\d*)$/);
                if (coordMatch) {
                    const latStr = coordMatch[1];
                    const lngStr = coordMatch[2];
                    const lat = parseFloat(latStr);
                    const lng = parseFloat(lngStr);
                    if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        const r = parseInt(document.getElementById('radius_input').value) || defaultRad;
                        marker.setLatLng([lat, lng]);
                        circle.setLatLng([lat, lng]).setRadius(r);
                        map.setView([lat, lng], 16);
                        // Use original string to preserve full precision
                        document.getElementById('lat').value = latStr;
                        document.getElementById('lon').value = lngStr;
                        document.getElementById('lat_display').value = latStr;
                        document.getElementById('lon_display').value = lngStr;
                        document.getElementById('radius').value = r;
                        document.getElementById('radius_input').value = r;
                        saveLocationContainer.style.display = 'block';
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                            .then(res => res.json())
                            .then(data => { document.getElementById('location_address').value = data?.display_name || ''; })
                            .catch(() => { document.getElementById('location_address').value = ''; });
                        return;
                    }
                }
                // Fallback: search by address text
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            saveLocationContainer.style.display = 'block';
                            updateAllData(L.latLng(data[0].lat, data[0].lon), document.getElementById('radius_input').value);
                        }
                    });
            });
        });
    </script>
</body>
</html>