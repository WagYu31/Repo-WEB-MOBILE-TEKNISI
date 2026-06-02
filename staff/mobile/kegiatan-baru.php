<?php
include "../conn.php";
include "../session.php";
$pageNow = "Kegiatan Baru";
include "../get-user-data.php";

// Proses form HANYA jika tombol submit ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kegiatan'])) {
    // Ambil data dari form
    $customer_id = $_POST['nama_cust'];
    $kegiatan = $_POST['kegiatan'];
    $jadwal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $kegiatan_relasi = !empty($_POST['kegiatan_relasi']) ? $_POST['kegiatan_relasi'] : NULL;
    
    // Ambil data dari peta
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
    
    $status = 'waiting';
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    $kode = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);

    // Menambahkan lat, lon, rad ke dalam query
    $sql = "INSERT INTO kegiatan (customer_id, jadwal, kegiatan, keterangan, request, status, kode, relasi, lat, lon, rad, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
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
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    
    <style>
        <?php include "../css/floating-menu2.css"; ?>
        <?php include "../css/kegiatan-baru-styles.css"; ?>
        #map {
            height: 300px;
            width: 100%;
            border-radius: .5rem;
            border: 1px solid #dee2e6;
            cursor: pointer;
        }
        .accordion-button:not(.collapsed) {
            /*background-color: #e91e63;*/
            /*color: white;*/
        }
        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php
    // include "menu-bottom.php"; 
    ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php include "../nav-top.php"; setlocale(LC_TIME, 'id_ID'); ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12 mt-2">
                    <div class="card z-index-2">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-2 ps-1">
                                <div class="chart px-3"><h5 class="text-dark text-center text-bold bg-white pt-2">Tambah Kegiatan Baru</h5></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="kegiatanForm">
                                <div class="accordion" id="kegiatanAccordion">
                                
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingOne">
                                            <button class="accordion-button btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                Langkah 1: Pilih Customer
                                            </button>
                                        </h2>
                                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne">
                                            <div class="accordion-body">
                                                <div class="form-group">
                                                    <div class="dropdown">
                                                        <label for="nama_customer">Nama Customer</label>
                                                        <button type="button" class="dropdown-button btn btn-primary">Pilih Customer ▼</button>
                                                        <div id="dropdownItems">
                                                            <input type="text" id="dropdownSearch" placeholder="Cari customer...">
                                                            <?php
                                                            $sql_cust = "SELECT id, nama, telp FROM customer WHERE deleted_at IS NULL ORDER BY nama ASC";
                                                            $result_cust = mysqli_query($conn, $sql_cust);
                                                            if ($result_cust && mysqli_num_rows($result_cust) > 0) {
                                                                while ($row = mysqli_fetch_assoc($result_cust)) {
                                                                    echo "<div class='dropdown-item' data-id='{$row['id']}'>{$row['nama']} - {$row['telp']}</div>";
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" id="nama_cust" name="nama_cust" required>
                                                </div>
                                                <div class="mt-3" id="outputData" style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingTwo">
                                            <button class="accordion-button collapsed btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                Langkah 2: Detail Kegiatan
                                            </button>
                                        </h2>
                                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo">
                                            <div class="accordion-body">
                                                <div class="form-group mt-2"><label for="kegiatan">Jenis Kegiatan</label><select class="form-control border p-2" id="kegiatan" name="kegiatan"><option value="survey">Survey</option><option value="pasang baru">Pasang Baru</option><option value="service">Service</option></select></div>
                                                <div class="form-group mt-2"><label for="kegiatan_relasi">Terkait Kegiatan (Opsional)</label><select class="form-control border p-2" id="kegiatan_relasi" name="kegiatan_relasi" disabled><option value="">Pilih Customer Dahulu</option></select></div>
                                                <div class="form-row-container mt-2">
                                                    <div class="form-group"><label for="tanggal_survey_date">Tanggal</label><input type="date" class="form-control border p-2" id="tanggal_survey_date" name="tanggal_survey_date"></div>
                                                    <div class="form-group"><label for="tanggal_survey_time_hour">Jam</label><select class="form-control border p-2" id="tanggal_survey_time_hour" name="tanggal_survey_time_hour"></select></div>
                                                    <div class="form-group"><label for="tanggal_survey_time_minute">Menit</label><select class="form-control border p-2" id="tanggal_survey_time_minute" name="tanggal_survey_time_minute"><option value="00">00</option><option value="15">15</option><option value="30">30</option><option value="45">45</option></select></div>
                                                </div>
                                                <input type="hidden" id="tanggal_survey_datetime_combined" name="tanggal">
                                                <div class="form-group my-4"><label for="keterangan">Keterangan</label><textarea class="form-control border p-2" id="keterangan" rows="3" name="keterangan" placeholder="Keterangan tambahan"></textarea></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingThree">
                                            <button class="accordion-button collapsed btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                Langkah 3: Tentukan Lokasi (Opsional)
                                            </button>
                                        </h2>
                                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree">
                                            <div class="accordion-body">
                                                <div id="map"></div>
                                                <div class="row mt-2">
                                                    <div class="col-6"><input type="text" id="lat_display" class="form-control" placeholder="Latitude"></div>
                                                    <div class="col-6"><input type="text" id="lon_display" class="form-control" placeholder="Longitude"></div>
                                                </div>
                                                <div class="d-flex align-items-center mt-2">
                                                    <button class="btn btn-dark btn-sm me-2" type="button" id="apply_coords_btn">Terapkan</button>
                                                    <div class="input-group"><span class="input-group-text">Radius</span><input type="number" class="form-control" id="radius_input" value="80" aria-label="Radius meter"><span class="input-group-text">m</span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="lat" name="lat"><input type="hidden" id="lon" name="lon"><input type="hidden" id="radius" name="radius">
                                <button type="submit" name="submit_kegiatan" class="btn btn-outline-primary w-100 mt-4">Submit Kegiatan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "../footer.php"; ?>
        </div>
        <?php include "bottom-navbar.php"; ?>
    </main>
    <?php include "../js-include.php"; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isMobile = /Android|webOS|iPhone|iPod/i.test(navigator.userAgent);
        const outputDataContainer = document.getElementById('outputData');

        if (isMobile) {
            outputDataContainer.style.display = 'none';
        }

        const dropdownContainer = document.querySelector('.dropdown'),
              dropdownButton = document.querySelector('.dropdown-button'),
              dropdownItems = document.getElementById('dropdownItems'),
              dropdownSearch = document.getElementById('dropdownSearch'),
              relasiSelect = document.getElementById('kegiatan_relasi');
        
        if (!dropdownContainer) return;

        dropdownButton.addEventListener('click', e => { e.stopPropagation(); dropdownItems.style.display = dropdownItems.style.display === 'block' ? 'none' : 'block'; if(dropdownItems.style.display === 'block') dropdownSearch.focus(); });
        dropdownSearch.addEventListener('keyup', () => { const filter = dropdownSearch.value.toUpperCase(); dropdownItems.querySelectorAll('.dropdown-item').forEach(item => item.style.display = (item.textContent || item.innerText).toUpperCase().indexOf(filter) > -1 ? '' : 'none'); });
        dropdownItems.addEventListener('click', e => { if (e.target.classList.contains('dropdown-item')) { dropdownButton.innerText = e.target.innerText; document.getElementById('nama_cust').value = e.target.dataset.id; dropdownItems.style.display = 'none'; fetchCustomerData(e.target.dataset.id); } });
        window.addEventListener('click', e => { if (!dropdownContainer.contains(e.target)) dropdownItems.style.display = 'none'; });
        
        function fetchCustomerData(customerId) {
            if (!isMobile) { outputDataContainer.innerHTML = '<div class="card card-body"><p>Memuat riwayat...</p></div>'; }
            relasiSelect.innerHTML = '<option value="">Memuat...</option>';
            relasiSelect.disabled = true;

            fetch(`../data-kegiatan-cust.php?customer_id=${customerId}`)
                .then(response => response.ok ? response.json() : Promise.reject('Gagal mengambil data.'))
                .then(data => {
                    if (!isMobile) { outputDataContainer.innerHTML = data.displayHtml; }
                    relasiSelect.innerHTML = '';
                    if (data.relasiOptions.length > 0) {
                        relasiSelect.disabled = false;
                        relasiSelect.add(new Option('Pilih kegiatan terkait...', ''));
                        data.relasiOptions.forEach(opt => relasiSelect.add(new Option(opt.teks, opt.kode)));
                    } else {
                        relasiSelect.disabled = true;
                        relasiSelect.add(new Option('Tidak ada riwayat kegiatan', ''));
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    if (!isMobile) { outputDataContainer.innerHTML = "<div class='alert alert-danger text-white'>Gagal memuat riwayat.</div>"; }
                    relasiSelect.innerHTML = '<option value="">Gagal memuat</option>';
                });
        }
        
        const hourSelect = document.getElementById('tanggal_survey_time_hour');
        for (let i = 7; i < 21; i++) { const hour = i.toString().padStart(2, '0'); hourSelect.add(new Option(hour, hour)); }
        function combineDateTime() { const date = document.getElementById('tanggal_survey_date').value, hour = document.getElementById('tanggal_survey_time_hour').value, minute = document.getElementById('tanggal_survey_time_minute').value; document.getElementById('tanggal_survey_datetime_combined').value = (date && hour && minute) ? `${date}T${hour}:${minute}` : ''; }
        ['tanggal_survey_date', 'tanggal_survey_time_hour', 'tanggal_survey_time_minute'].forEach(id => document.getElementById(id).addEventListener('change', combineDateTime));

        // --- Skrip Peta dengan Inisialisasi yang Diperbaiki ---
        const mapContainer = document.getElementById('map'),
              latInput = document.getElementById('lat'),
              lonInput = document.getElementById('lon'),
              latDisplay = document.getElementById('lat_display'),
              lonDisplay = document.getElementById('lon_display'),
              radiusInput = document.getElementById('radius'),
              radiusValueInput = document.getElementById('radius_input'),
              mapAccordion = document.getElementById('collapseThree');
        
        const defaultLat = -6.130161684767826, defaultLon = 106.75141257877027;
        let map, marker, circle; // Deklarasikan di luar
        let mapInitialized = false;

        function initMap() {
            if (mapInitialized) return;
            map = L.map(mapContainer).setView([defaultLat, defaultLon], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);
            circle = L.circle([defaultLat, defaultLon], { radius: 80 }).addTo(map);
            
            map.on('click', e => updateAllData(e.latlng, parseInt(radiusValueInput.value, 10) || 50));
            marker.on('dragend', () => updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50));
            
            updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50);
            mapInitialized = true;
        }

        // [PERBAIKAN] Inisialisasi peta saat akordeon dibuka
        mapAccordion.addEventListener('shown.bs.collapse', function () {
            initMap();
            // Perbaiki ukuran peta setelah elemen terlihat
            setTimeout(() => map.invalidateSize(), 10); 
        });

        function updateAllData(latlng, radiusVal) {
            latInput.value = latlng.lat;
            lonInput.value = latlng.lng;
            radiusInput.value = radiusVal;
            latDisplay.value = latlng.lat.toFixed(6);
            lonDisplay.value = latlng.lng.toFixed(6);
            radiusValueInput.value = radiusVal;
            marker.setLatLng(latlng);
            circle.setLatLng(latlng).setRadius(radiusVal);
            map.panTo(latlng);
        }

        document.getElementById('apply_coords_btn').addEventListener('click', () => {
            if (!mapInitialized) initMap(); // Inisialisasi jika belum
            const lat = parseFloat(latDisplay.value.replace(",", "."));
            const lon = parseFloat(lonDisplay.value.replace(",", "."));
            if (!isNaN(lat) && !isNaN(lon)) {
                updateAllData(L.latLng(lat, lon), parseInt(radiusValueInput.value, 10) || 50);
            } else {
                alert('Format Latitude atau Longitude tidak valid.');
            }
        });
        radiusValueInput.addEventListener('input', () => {
            if(mapInitialized) updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 0);
        });
    });
    </script>
</body>
</html>