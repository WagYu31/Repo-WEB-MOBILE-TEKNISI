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

    $sql = "INSERT INTO kegiatan (customer_id, jadwal, kegiatan, keterangan, request, status, kode, relasi, lat, lon, rad, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssssssssss", $customer_id, $jadwal, $kegiatan, $keterangan, $nmUser, $status, $kode, $kegiatan_relasi, $lat, $lon, $rad, $now, $now);
    if (mysqli_stmt_execute($stmt)) {
        echo '<script>window.location.href = "kegiatan-baru.php?status=sukses";</script>';
        exit();
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kegiatan Baru</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        <?php include "css/floating-menu2.css"; ?>
        <?php include "css/kegiatan-baru-styles.css"; ?>
        #map { height: 300px; width: 100%; border-radius: .5rem; border: 1px solid #dee2e6; cursor: pointer; }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php include "nav-top.php"; setlocale(LC_TIME, 'id_ID'); ?>
        <div class="container-fluid py-4">
            <div class="row d-flex flex-row align-items-start">
                <div class="col-12 col-md-5 mt-4 mb-4">
                    <div class="card z-index-2">
                        <div class="card-header col-9 col-md-7 p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1"><div class="chart px-3"><h5 class="text-light text-bold">Tambah Kegiatan Baru</h5></div></div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="kegiatan-baru.php" id="kegiatanForm">
                                <div class="form-group"><div class="dropdown"><label for="nama_customer">Nama Customer</label><button type="button" class="dropdown-button btn btn-outline-info w-100 border text-info">Pilih Customer ▼</button><div id="dropdownItems"><input type="text" id="dropdownSearch" placeholder="Cari customer..."><?php $sql_cust = "SELECT id, nama, telp FROM customer WHERE deleted_at IS NULL ORDER BY nama ASC"; $result_cust = mysqli_query($conn, $sql_cust); if ($result_cust && mysqli_num_rows($result_cust) > 0) { while ($row = mysqli_fetch_assoc($result_cust)) { echo "<div class='dropdown-item' data-id='{$row['id']}'>{$row['nama']} - {$row['telp']}</div>"; } } ?></div></div><input type="hidden" id="nama_cust" name="nama_cust" required></div>
                                <div class="form-group mt-2"><label for="kegiatan">Jenis Kegiatan</label><select class="form-control border p-2" id="kegiatan" name="kegiatan"><option value="survey">Survey</option><option value="pasang baru">Pasang Baru</option><option value="service">Service</option></select></div>
                                <div class="form-group mt-2"><label for="kegiatan_relasi">Terkait dengan Kegiatan (Opsional)</label><select class="form-control border p-2" id="kegiatan_relasi" name="kegiatan_relasi" disabled><option value="">Pilih Customer Dahulu</option></select></div>
                                <div class="form-row-container mt-2"><div class="form-group"><label for="tanggal_survey_date">Tanggal</label><input type="date" class="form-control border p-2" id="tanggal_survey_date" name="tanggal_survey_date"></div><div class="form-group"><label for="tanggal_survey_time_hour">Jam</label><select class="form-control border p-2" id="tanggal_survey_time_hour" name="tanggal_survey_time_hour"></select></div><div class="form-group"><label for="tanggal_survey_time_minute">Menit</label><select class="form-control border p-2" id="tanggal_survey_time_minute" name="tanggal_survey_time_minute"><option value="00">00</option><option value="15">15</option><option value="30">30</option><option value="45">45</option></select></div></div>
                                <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                <div class="form-group my-4"><label for="keterangan">Keterangan</label><textarea class="form-control border p-2" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea></div>
                                <div class="form-group my-4"><label>Lokasi Absen & Radius (Opsional)</label><div class="input-group mb-2"><input type="text" id="gmap_search" class="form-control border p-2" placeholder="Ketik alamat untuk dicari..."><button class="btn btn-outline-primary mb-0 py-1" type="button" id="gmap_search_btn">Cari</button></div><div id="map"></div><div class="row mt-2"><div class="col-6"><input type="text" id="lat_display" class="form-control border p-2" placeholder="Latitude"></div><div class="col-6"><input type="text" id="lon_display" class="form-control border p-2" placeholder="Longitude"></div></div><div class="mt-2"><div class="d-flex align-items-center input-group"><span class="input-group">Radius (meter)</span><br><input type="number" class="form-control border p-2" id="radius_input" value="80" aria-label="Radius dalam meter"></div></div></div>
                                <div id="locationRecommendations" class="mt-2 mb-5" style="display: none;"><label class="form-label">Rekomendasi Lokasi Sebelumnya:</label><div id="recommendationsList" class="list-group"></div></div>
                                <input type="hidden" id="lat" name="lat"><input type="hidden" id="lon" name="lon"><input type="hidden" id="radius" name="radius">
                                <button type="submit" name="submit_kegiatan" class="btn bg-gradient-info">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-7 mt-4 mb-4" id="outputData"></div>
            </div>
            <?php include "floating-menu.php"; ?>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownContainer = document.querySelector('.dropdown'), dropdownButton = document.querySelector('.dropdown-button'), dropdownItems = document.getElementById('dropdownItems'), dropdownSearch = document.getElementById('dropdownSearch'), outputDataContainer = document.getElementById('outputData'), relasiSelect = document.getElementById('kegiatan_relasi'), recommendationsContainer = document.getElementById('locationRecommendations'), recommendationsList = document.getElementById('recommendationsList');
        if (!dropdownContainer) return;
        dropdownButton.addEventListener('click', e => { e.stopPropagation(); dropdownItems.style.display = dropdownItems.style.display === 'block' ? 'none' : 'block'; if(dropdownItems.style.display === 'block') dropdownSearch.focus(); });
        dropdownSearch.addEventListener('keyup', () => { const filter = dropdownSearch.value.toUpperCase(); dropdownItems.querySelectorAll('.dropdown-item').forEach(item => item.style.display = (item.textContent || item.innerText).toUpperCase().indexOf(filter) > -1 ? '' : 'none'); });
        dropdownItems.addEventListener('click', e => { if (e.target.classList.contains('dropdown-item')) { dropdownButton.innerText = e.target.innerText; document.getElementById('nama_cust').value = e.target.dataset.id; dropdownItems.style.display = 'none'; fetchCustomerData(e.target.dataset.id); } });
        window.addEventListener('click', e => { if (!dropdownContainer.contains(e.target)) dropdownItems.style.display = 'none'; });
        
        function fetchCustomerData(customerId) {
            outputDataContainer.innerHTML = '<div class="card card-body"><p>Memuat riwayat...</p></div>';
            relasiSelect.innerHTML = '<option value="">Memuat...</option>';
            relasiSelect.disabled = true;
            recommendationsContainer.style.display = 'none';
            recommendationsList.innerHTML = '';
            fetch(`data-kegiatan-cust.php?customer_id=${customerId}`)
                .then(response => response.ok ? response.json() : Promise.reject('Gagal mengambil data.'))
                .then(data => {
                    outputDataContainer.innerHTML = /Android|webOS|iPhone/i.test(navigator.userAgent) ? '<div class="card card-body"><p class="text-center">Riwayat tidak ditampilkan di mobile.</p></div>' : data.displayHtml;
                    relasiSelect.innerHTML = '';
                    if (data.relasiOptions.length > 0) {
                        relasiSelect.disabled = false;
                        relasiSelect.add(new Option('Pilih kegiatan terkait...', ''));
                        data.relasiOptions.forEach(opt => relasiSelect.add(new Option(opt.teks, opt.kode)));
                    } else {
                        relasiSelect.disabled = true;
                        relasiSelect.add(new Option('Tidak ada riwayat kegiatan', ''));
                    }
                    if (data.locationRecommendations && data.locationRecommendations.length > 0) {
                        recommendationsContainer.style.display = 'block';
                        data.locationRecommendations.forEach(loc => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action list-group-item-light';
                            item.dataset.lat = loc.lat; 
                            item.dataset.lon = loc.lon; 
                            item.dataset.rad = loc.rad;
                            item.innerHTML = `<i class="fa-solid fa-location-dot me-2"></i> ${loc.address}`;
                            recommendationsList.appendChild(item);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    outputDataContainer.innerHTML = "<div class='alert alert-danger text-white mx-4'>Gagal memuat riwayat.</div>";
                    relasiSelect.innerHTML = '<option value="">Gagal memuat</option>';
                });
        }
        recommendationsList.addEventListener('click', function(e) {
            e.preventDefault();
            const target = e.target.closest('.list-group-item-action');
            if (target) {
                const lat = parseFloat(target.dataset.lat);
                const lon = parseFloat(target.dataset.lon);
                const rad = parseInt(target.dataset.rad, 10);
                updateAllData(L.latLng(lat, lon), rad);
                document.querySelectorAll('#recommendationsList .list-group-item-action').forEach(el => el.classList.remove('active'));
                target.classList.add('active');
            }
        });
        
        const hourSelect = document.getElementById('tanggal_survey_time_hour');
        for (let i = 7; i < 21; i++) { const hour = i.toString().padStart(2, '0'); hourSelect.add(new Option(hour, hour)); }
        function combineDateTime() { const date = document.getElementById('tanggal_survey_date').value, hour = document.getElementById('tanggal_survey_time_hour').value, minute = document.getElementById('tanggal_survey_time_minute').value; document.getElementById('tanggal_survey_datetime_combined').value = (date && hour && minute) ? `${date} ${hour}:${minute}:00` : ''; }
        ['tanggal_survey_date', 'tanggal_survey_time_hour', 'tanggal_survey_time_minute'].forEach(id => document.getElementById(id).addEventListener('change', combineDateTime));

        const mapContainer = document.getElementById('map'), latInput = document.getElementById('lat'), lonInput = document.getElementById('lon'), latDisplay = document.getElementById('lat_display'), lonDisplay = document.getElementById('lon_display'), radiusInput = document.getElementById('radius'), radiusValueInput = document.getElementById('radius_input');
        const defaultLat = -6.13016, defaultLon = 106.75141;
        const map = L.map(mapContainer).setView([defaultLat, defaultLon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        let marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);
        let circle = L.circle([defaultLat, defaultLon], { radius: 80 }).addTo(map);
        
        function updateAllData(latlng, radiusVal) {
            latInput.value = latlng.lat; lonInput.value = latlng.lng; radiusInput.value = radiusVal;
            latDisplay.value = latlng.lat.toFixed(6); lonDisplay.value = latlng.lng.toFixed(6); radiusValueInput.value = radiusVal;
            marker.setLatLng(latlng); circle.setLatLng(latlng).setRadius(radiusVal); map.panTo(latlng);
        }
        document.getElementById('gmap_search_btn').addEventListener('click', () => { const query = document.getElementById('gmap_search').value; if (query) window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`, '_blank'); });
        map.on('click', e => updateAllData(e.latlng, parseInt(radiusValueInput.value, 10) || 50));
        marker.on('dragend', () => updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50));
        radiusValueInput.addEventListener('input', () => updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 0));
        
        updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50);
    });
    </script>
</body>
</html>