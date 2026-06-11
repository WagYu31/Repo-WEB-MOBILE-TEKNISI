<?php
include "conn.php";
include "session.php";
$pageNow = "Kegiatan Baru";
include "get-user-data.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kegiatan'])) {
    $customer_id = $_POST['nama_cust'];
    $kegiatan = $_POST['kegiatan'];
    $jadwal = !empty($_POST['tanggal']) ? $_POST['tanggal'] : '0000-00-00 00:00:00';
    $keterangan = $_POST['keterangan'];
    $kegiatan_relasi = !empty($_POST['kegiatan_relasi']) ? $_POST['kegiatan_relasi'] : NULL;
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
    $status = 'waiting';
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    // Jika ada relasi (Terkait), pakai kode yang sama. Jika tidak, buat kode baru.
    $kode = !empty($kegiatan_relasi) ? $kegiatan_relasi : substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);

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

    $sql = "INSERT INTO kegiatan (customer_id, jadwal, kegiatan, keterangan, request, status, kode, relasi, lat, lon, rad, alamat_lokasi, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssssssssssss", $customer_id, $jadwal, $kegiatan, $keterangan, $nmUser, $status, $kode, $kegiatan_relasi, $lat, $lon, $rad, $location_address, $now, $now);
    if (mysqli_stmt_execute($stmt)) {
        echo '<script>window.location.href = "kegiatan-baru.php?status=sukses";</script>';
        exit();
    } else {
        echo '<script>window.location.href = "kegiatan-baru.php?status=gagal&pesan=' . urlencode(mysqli_error($conn)) . '";</script>';
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
                <div class="col-12 mt-4 mb-4">
                    <div class="card" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                        <!-- Premium Header -->
                        <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2563eb 100%);padding:24px 32px;position:relative;overflow:hidden;">
                            <div style="position:absolute;top:-40px;right:-20px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.04);"></div>
                            <div style="position:absolute;bottom:-50px;right:100px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.03);"></div>
                            <div style="position:absolute;top:10px;right:30px;width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.2);"></div>
                            <div style="display:flex;align-items:center;gap:14px;position:relative;z-index:1;">
                                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.12);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.1);">
                                    <i class="material-icons" style="color:#fff;font-size:22px;">add_task</i>
                                </div>
                                <div>
                                    <h5 style="color:#fff;margin:0;font-size:18px;font-weight:700;letter-spacing:-0.3px;">Tambah Kegiatan Baru</h5>
                                    <p style="color:rgba(255,255,255,0.6);margin:0;font-size:12px;margin-top:2px;">Buat dan jadwalkan kegiatan teknisi</p>
                                </div>
                            </div>
                        </div>
                        <!-- Form Body - 2 Column Landscape -->
                        <div class="card-body" style="padding:28px 32px;">
                            <form method="POST" action="kegiatan-baru.php" id="kegiatanForm">
                                <div class="row" style="gap:0;">
                                    <!-- LEFT COLUMN: Form Fields -->
                                    <div class="col-12 col-lg-5" style="padding-right:28px;">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                                            <div style="width:3px;height:16px;background:#2563eb;border-radius:2px;"></div>
                                            <span style="font-size:13px;font-weight:700;color:#1e293b;">Informasi Kegiatan</span>
                                        </div>
                                        <div style="margin-bottom:18px;">
                                            <label style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.8px;">
                                                <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;color:#2563eb;">person</i>Nama Customer
                                            </label>
                                            <div class="dropdown">
                                                <button type="button" class="dropdown-button" style="width:100%;padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;text-align:left;font-size:13px;color:#475569;cursor:pointer;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">Pilih Customer ▼</button>
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
                                        <div class="row g-3" style="margin-bottom:18px;">
                                            <div class="col-6">
                                                <label style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.8px;">
                                                    <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;color:#2563eb;">category</i>Jenis
                                                </label>
                                                <select class="form-control" id="kegiatan" name="kegiatan" style="padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                                                    <option value="survey">Survey</option>
                                                    <option value="pasang baru">Pasang Baru</option>
                                                    <option value="service">Service</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.8px;">
                                                    <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;color:#2563eb;">link</i>Terkait
                                                </label>
                                                <select class="form-control" id="kegiatan_relasi" name="kegiatan_relasi" disabled style="padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;background:#f8fafc;transition:all 0.2s;">
                                                    <option value="">Pilih Customer Dahulu</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div style="margin-bottom:18px;">
                                            <label style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.8px;">
                                                <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;color:#2563eb;">event</i>Jadwal
                                            </label>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <input type="date" class="form-control" id="tanggal_survey_date" name="tanggal_survey_date" style="padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                                                </div>
                                                <div class="col-3">
                                                    <select class="form-control" id="tanggal_survey_time_hour" style="padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;text-align:center;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'"></select>
                                                </div>
                                                <div class="col-3">
                                                    <select class="form-control" id="tanggal_survey_time_minute" style="padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;text-align:center;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                                                        <option value="00">00</option>
                                                        <option value="15">15</option>
                                                        <option value="30">30</option>
                                                        <option value="45">45</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                        <div>
                                            <label style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:0.8px;">
                                                <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:4px;color:#2563eb;">notes</i>Keterangan
                                            </label>
                                            <textarea class="form-control" id="keterangan" rows="5" name="keterangan" placeholder="Keterangan tambahan..." style="padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;resize:vertical;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'"></textarea>
                                        </div>
                                    </div>
                                    <!-- GRADIENT DIVIDER -->
                                    <div class="d-none d-lg-flex col-lg-auto" style="padding:0;align-items:stretch;">
                                        <div style="width:1px;background:linear-gradient(to bottom,transparent,#e2e8f0 15%,#e2e8f0 85%,transparent);"></div>
                                    </div>
                                    <!-- RIGHT COLUMN: Map & Location -->
                                    <div class="col-12 col-lg mt-4 mt-lg-0" style="padding-left:28px;">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                                            <div style="width:3px;height:16px;background:#10b981;border-radius:2px;"></div>
                                            <span style="font-size:13px;font-weight:700;color:#1e293b;">Lokasi & Peta</span>
                                            <span style="font-size:11px;color:#94a3b8;font-weight:400;">(Opsional)</span>
                                        </div>
                                        <div style="display:flex;gap:8px;margin-bottom:12px;">
                                            <input type="text" id="gmap_search" class="form-control" placeholder="Masukkan koordinat atau alamat..." style="flex:1;padding:11px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;transition:all 0.2s;" onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'" onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                                            <button type="button" id="gmap_search_btn" style="padding:11px 20px;border:none;border-radius:10px;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.2s;display:flex;align-items:center;gap:4px;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(37,99,235,0.35)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                                <i class="material-icons" style="font-size:16px;">search</i>CARI
                                            </button>
                                        </div>
                                        <div id="map" style="height:280px;border-radius:12px;overflow:hidden;border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.04);"></div>
                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;margin-top:12px;">
                                            <div>
                                                <label style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block;">Latitude</label>
                                                <input type="text" id="lat_display" class="form-control" placeholder="-6.xxxxx" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:'Courier New',monospace;background:#f8fafc;">
                                            </div>
                                            <div>
                                                <label style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block;">Longitude</label>
                                                <input type="text" id="lon_display" class="form-control" placeholder="106.xxxxx" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:'Courier New',monospace;background:#f8fafc;">
                                            </div>
                                            <div>
                                                <label style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block;">Radius</label>
                                                <div style="display:flex;align-items:center;gap:4px;">
                                                    <input type="number" class="form-control" id="radius_input" value="100" style="padding:9px 8px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;text-align:center;width:72px;background:#f8fafc;">
                                                    <span style="font-size:11px;color:#94a3b8;">m</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="saveLocationContainer" class="mt-3" style="display:none;padding:14px 16px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:10px;">
                                            <div class="form-check" style="margin-bottom:8px;">
                                                <input class="form-check-input" type="checkbox" id="save_location_checkbox" name="save_location" checked>
                                                <label class="form-check-label" for="save_location_checkbox" style="font-size:12px;font-weight:500;color:#166534;">Simpan lokasi baru ini</label>
                                            </div>
                                            <div id="location_alias_input_container">
                                                <input type="text" id="location_alias" name="location_alias" class="form-control" placeholder="Alias Lokasi (Contoh: Rumah)" style="padding:9px 12px;border:1.5px solid #bbf7d0;border-radius:8px;font-size:12px;">
                                            </div>
                                        </div>
                                        <div id="locationRecommendations" class="mt-2" style="display:none;">
                                            <label style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block;">Lokasi Tersimpan</label>
                                            <div id="recommendationsList" class="list-group" style="max-height:100px;overflow-y:auto;border-radius:8px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden fields & Submit -->
                                <input type="hidden" id="lat" name="lat">
                                <input type="hidden" id="lon" name="lon">
                                <input type="hidden" id="radius" name="radius">
                                <input type="hidden" id="location_address" name="location_address">
                                <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
                                    <button type="submit" name="submit_kegiatan" style="width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#2563eb 100%);color:#fff;font-size:14px;font-weight:700;letter-spacing:0.5px;cursor:pointer;transition:all 0.3s;box-shadow:0 4px 20px rgba(37,99,235,0.25);display:flex;align-items:center;justify-content:center;gap:8px;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 28px rgba(37,99,235,0.4)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 20px rgba(37,99,235,0.25)'">
                                        <i class="material-icons" style="font-size:20px;">send</i>SUBMIT KEGIATAN
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-0 mb-4" id="outputData"></div>
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
            for (let i = 0; i < 24; i++) {
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

            // Clean Indonesian address for better Nominatim search
            function cleanAddress(addr) {
                return addr
                    .replace(/RT\.?\s*\d+\s*\/?\s*RW\.?\s*\d+\s*,?/gi, '')
                    .replace(/\b(Kec\.|Kecamatan|Kel\.|Kelurahan|Kota|Daerah Khusus|Ibukota)\b/gi, '')
                    .replace(/\d{5}/g, '') // remove postal code
                    .replace(/No\.?\s*\d+\w*/gi, '') // remove house numbers
                    .replace(/,\s*,/g, ',')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .replace(/^,|,$/g, '')
                    .trim();
            }

            function nominatimSearch(query) {
                return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id&accept-language=id`)
                    .then(res => res.json());
            }

            function nominatimReverse(lat, lng) {
                return fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=id`)
                    .then(res => res.json());
            }

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

                nominatimReverse(latlng.lat, latlng.lng)
                    .then(data => {
                        document.getElementById('location_address').value = data?.display_name || '';
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

            document.getElementById('gmap_search_btn').addEventListener('click', async () => {
                const q = document.getElementById('gmap_search').value.trim();
                if (!q) return;
                const btn = document.getElementById('gmap_search_btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="material-icons" style="font-size:16px;">hourglass_top</i>Mencari...';

                try {
                    // Detect coordinate input: "lat, lng" or "lat lng"
                    const coordMatch = q.match(/^(-?\d+\.?\d*)[,\s]+\s*(-?\d+\.?\d*)$/);
                    if (coordMatch) {
                        const latStr = coordMatch[1], lngStr = coordMatch[2];
                        const lat = parseFloat(latStr), lng = parseFloat(lngStr);
                        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                            const r = parseInt(document.getElementById('radius_input').value) || defaultRad;
                            marker.setLatLng([lat, lng]);
                            circle.setLatLng([lat, lng]).setRadius(r);
                            map.setView([lat, lng], 16);
                            document.getElementById('lat').value = latStr;
                            document.getElementById('lon').value = lngStr;
                            document.getElementById('lat_display').value = latStr;
                            document.getElementById('lon_display').value = lngStr;
                            document.getElementById('radius').value = r;
                            document.getElementById('radius_input').value = r;
                            saveLocationContainer.style.display = 'block';
                            nominatimReverse(lat, lng)
                                .then(data => {
                                    const addr = data?.display_name || '';
                                    document.getElementById('location_address').value = addr;
                                    if (addr) document.getElementById('gmap_search').value = addr;
                                })
                                .catch(() => { document.getElementById('location_address').value = ''; });
                            return;
                        }
                    }

                    // Strategy 1: Search original text
                    let data = await nominatimSearch(q);
                    if (data.length > 0) {
                        saveLocationContainer.style.display = 'block';
                        document.getElementById('gmap_search').value = data[0].display_name;
                        updateAllData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('radius_input').value);
                        return;
                    }

                    // Strategy 2: Search cleaned address (remove RT/RW/Kec etc)
                    const cleaned = cleanAddress(q);
                    if (cleaned && cleaned !== q) {
                        data = await nominatimSearch(cleaned);
                        if (data.length > 0) {
                            saveLocationContainer.style.display = 'block';
                            document.getElementById('gmap_search').value = data[0].display_name;
                            updateAllData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('radius_input').value);
                            return;
                        }
                    }

                    // Strategy 3: Try just street name + city
                    const parts = q.split(',').map(p => p.trim()).filter(p => p.length > 2);
                    if (parts.length >= 2) {
                        const shortQ = parts[0] + ', ' + parts[parts.length - 2];
                        data = await nominatimSearch(shortQ);
                        if (data.length > 0) {
                            saveLocationContainer.style.display = 'block';
                            document.getElementById('gmap_search').value = data[0].display_name;
                            updateAllData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('radius_input').value);
                            return;
                        }
                    }

                    // Strategy 4: Try just the first part (street name)
                    if (parts.length >= 1) {
                        data = await nominatimSearch(parts[0] + ' Jakarta');
                        if (data.length > 0) {
                            saveLocationContainer.style.display = 'block';
                            document.getElementById('gmap_search').value = data[0].display_name;
                            updateAllData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('radius_input').value);
                            return;
                        }
                    }

                    alert('Alamat tidak ditemukan. Coba ketik nama jalan saja (contoh: "Jl Tanjung Duren Raya Jakarta")');

                } catch(e) {
                    alert('Gagal mencari alamat. Periksa koneksi internet.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="material-icons" style="font-size:16px;">search</i>CARI';
                }
            });

            // Enter key support for search
            document.getElementById('gmap_search').addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); document.getElementById('gmap_search_btn').click(); }
            });
        });
    </script>
    <script>
    // ─── Popup Notifikasi Sukses/Gagal ───
    (function() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get('status');
        if (!status) return;

        const isSuccess = status === 'sukses';
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;animation:popFadeIn 0.3s ease';

        const card = document.createElement('div');
        card.style.cssText = 'background:#fff;border-radius:20px;padding:36px 32px;text-align:center;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2);animation:popScaleIn 0.35s cubic-bezier(0.34,1.56,0.64,1)';

        card.innerHTML = `
            <div style="width:72px;height:72px;border-radius:50%;background:${isSuccess ? 'linear-gradient(135deg,#dcfce7,#bbf7d0)' : 'linear-gradient(135deg,#fef2f2,#fecaca)'};display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                <i class="material-icons" style="font-size:36px;color:${isSuccess ? '#16a34a' : '#dc2626'};">${isSuccess ? 'check_circle' : 'error'}</i>
            </div>
            <h5 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#0f172a;">${isSuccess ? 'Berhasil! 🎉' : 'Gagal Submit'}</h5>
            <p style="margin:0 0 24px;font-size:13px;color:#64748b;line-height:1.5;">${isSuccess ? 'Kegiatan baru berhasil ditambahkan.<br>Teknisi akan menerima notifikasi.' : 'Terjadi kesalahan saat menyimpan kegiatan.<br>' + (params.get('pesan') || 'Silakan coba lagi.')}</p>
            <button id="popupOkBtn" style="width:100%;padding:13px;border:none;border-radius:12px;background:${isSuccess ? 'linear-gradient(135deg,#16a34a,#22c55e)' : 'linear-gradient(135deg,#dc2626,#ef4444)'};color:#fff;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px ${isSuccess ? 'rgba(22,163,74,0.3)' : 'rgba(220,38,38,0.3)'};transition:all 0.2s;">
                ${isSuccess ? 'Tambah Kegiatan Lagi' : 'Coba Lagi'}
            </button>
        `;

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        // Add animation keyframes
        if (!document.getElementById('popupAnimStyle')) {
            const style = document.createElement('style');
            style.id = 'popupAnimStyle';
            style.textContent = '@keyframes popFadeIn{from{opacity:0}to{opacity:1}}@keyframes popScaleIn{from{opacity:0;transform:scale(0.85)}to{opacity:1;transform:scale(1)}}';
            document.head.appendChild(style);
        }

        document.getElementById('popupOkBtn').addEventListener('click', function() {
            overlay.style.animation = 'popFadeIn 0.2s ease reverse';
            setTimeout(() => { overlay.remove(); window.history.replaceState({}, '', 'kegiatan-baru.php'); }, 200);
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.style.animation = 'popFadeIn 0.2s ease reverse';
                setTimeout(() => { overlay.remove(); window.history.replaceState({}, '', 'kegiatan-baru.php'); }, 200);
            }
        });
    })();
    </script>
</body>
</html>