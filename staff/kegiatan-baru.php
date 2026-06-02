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
                <div class="col-12 col-md-5 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-9 col-md-7 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
                                <div class="chart px-3">
                                    <h5 class="text-light text-bold">Tambah Kegiatan Baru</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="kegiatan-baru.php" id="kegiatanForm">
                                <div class="form-group">
                                    <label>Nama Customer</label>
                                    <div class="dropdown">
                                        <button type="button" class="dropdown-button btn btn-outline-info w-100 border text-info">Pilih Customer ▼</button>
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
                                <div class="form-group mt-2">
                                    <label for="kegiatan">Jenis Kegiatan</label>
                                    <select class="form-control border p-2" id="kegiatan" name="kegiatan">
                                        <option value="survey">Survey</option>
                                        <option value="pasang baru">Pasang Baru</option>
                                        <option value="service">Service</option>
                                    </select>
                                </div>
                                <div class="form-group mt-2">
                                    <label for="kegiatan_relasi">Terkait dengan Kegiatan (Opsional)</label>
                                    <select class="form-control border p-2" id="kegiatan_relasi" name="kegiatan_relasi" disabled>
                                        <option value="">Pilih Customer Dahulu</option>
                                    </select>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <label>Tanggal</label>
                                        <input type="date" class="form-control border p-2" id="tanggal_survey_date" name="tanggal_survey_date">
                                    </div>
                                    <div class="col-3">
                                        <label>Jam</label>
                                        <select class="form-control border p-2" id="tanggal_survey_time_hour"></select>
                                    </div>
                                    <div class="col-3">
                                        <label>Menit</label>
                                        <select class="form-control border p-2" id="tanggal_survey_time_minute">
                                            <option value="00">00</option>
                                            <option value="15">15</option>
                                            <option value="30">30</option>
                                            <option value="45">45</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                <div class="form-group my-4">
                                    <label>Keterangan</label>
                                    <textarea class="form-control border p-2" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea>
                                </div>
                                <div class="form-group my-4">
                                    <label>Lokasi Absen & Radius (Opsional)</label>
                                    <div class="input-group mb-2">
                                        <input type="text" id="gmap_search" class="form-control border p-2" placeholder="Ketik alamat untuk dicari...">
                                        <button class="btn btn-outline-primary mb-0" type="button" id="gmap_search_btn">Cari</button>
                                    </div>
                                    <div id="map"></div>
                                    <div class="row mt-2">
                                        <div class="col-6"><input type="text" id="lat_display" class="form-control border p-2" placeholder="Latitude"></div>
                                        <div class="col-6"><input type="text" id="lon_display" class="form-control border p-2" placeholder="Longitude"></div>
                                    </div>
                                    <div class="mt-2 d-flex align-items-center">
                                        <label class="me-2 mb-0">Radius</label>
                                        <input type="number" class="form-control border p-2" id="radius_input" value="100" style="width: 80px;">
                                        <span class="ms-2">meter</span>
                                    </div>
                                </div>
                                <div id="saveLocationContainer" class="mt-3 p-3 border rounded" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="save_location_checkbox" name="save_location" checked>
                                        <label class="form-check-label" for="save_location_checkbox">Simpan lokasi baru ini</label>
                                    </div>
                                    <div id="location_alias_input_container" class="mt-2">
                                        <input type="text" id="location_alias" name="location_alias" class="form-control border p-2" placeholder="Alias Lokasi (Contoh: Rumah)">
                                    </div>
                                </div>
                                <div id="locationRecommendations" class="mt-2 mb-4" style="display: none;">
                                    <label>Lokasi Tersimpan:</label>
                                    <div id="recommendationsList" class="list-group"></div>
                                </div>
                                <input type="hidden" id="lat" name="lat">
                                <input type="hidden" id="lon" name="lon">
                                <input type="hidden" id="radius" name="radius">
                                <input type="hidden" id="location_address" name="location_address">
                                <button type="submit" name="submit_kegiatan" class="btn bg-gradient-info w-100 mt-4">Submit Kegiatan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-7 mt-4 mb-4" id="outputData"></div>
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
                document.getElementById('lat_display').value = latlng.lat.toFixed(8);
                document.getElementById('lon_display').value = latlng.lng.toFixed(8);
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
                    const lat = parseFloat(coordMatch[1]);
                    const lng = parseFloat(coordMatch[2]);
                    if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                        saveLocationContainer.style.display = 'block';
                        updateAllData(L.latLng(lat, lng), document.getElementById('radius_input').value);
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