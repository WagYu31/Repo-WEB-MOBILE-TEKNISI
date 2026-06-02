<?php
include "conn.php";
include "session.php";
$pageNow = "Kegiatan Baru";
include "get-user-data.php";

// Validasi kode dari URL
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    die("Error: Kode kegiatan tidak ditemukan.");
}
$kode_kegiatan = $_GET['kode'];

// Proses form UPDATE jika tombol submit ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_lokasi'])) {
    $kode_to_update = $_POST['kode'];
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;

    // Query UPDATE untuk menambahkan data lokasi
    $sql = "UPDATE kegiatan SET lat = ?, lon = ?, rad = ? WHERE kode = ?";
    $stmt = mysqli_prepare($conn, $sql);
    // Tipe data lat, lon, rad adalah string (s) sesuai struktur tabel
    mysqli_stmt_bind_param($stmt, "ssss", $lat, $lon, $rad, $kode_to_update);

    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, arahkan ke waiting list
        echo '<script>window.location.href = "waiting-list.php?status=sukses";</script>';
        exit();
    } else {
        echo "Error updating record: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 400px; width: 100%; border-radius: .5rem; border: 1px solid #dee2e6; cursor: pointer; }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8 mt-4">
                    <div class="card">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-info shadow-info border-radius-lg py-3 pe-1">
                                <h5 class="text-white text-bold text-center mb-0">Langkah 2: Tentukan Lokasi & Radius Absen</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-center">Untuk kegiatan dengan kode: <strong><?= htmlspecialchars($kode_kegiatan); ?></strong></p>
                            <form method="POST" action="pilih-lokasi.php?kode=<?= htmlspecialchars($kode_kegiatan); ?>" id="lokasiForm">
                                <input type="hidden" name="kode" value="<?= htmlspecialchars($kode_kegiatan); ?>">
                                
                                <div class="form-group my-4">
                                    <div class="input-group mb-2">
                                        <input type="text" id="gmap_search" class="form-control" placeholder="Ketik alamat untuk dicari...">
                                        <button class="btn btn-outline-primary mb-0" type="button" id="gmap_search_btn">Cari di Google Maps</button>
                                    </div>
                                    <div id="map"></div>
                                    <div class="row mt-2">
                                        <div class="col-6"><input type="text" id="lat_display" class="form-control" placeholder="Latitude"></div>
                                        <div class="col-6"><input type="text" id="lon_display" class="form-control" placeholder="Longitude"></div>
                                    </div>
                                    <div class="d-flex align-items-center mt-2">
                                        <button class="btn btn-dark btn-sm me-2" type="button" id="apply_coords_btn">Terapkan Koordinat</button>
                                        <div class="input-group">
                                            <span class="input-group-text">Radius</span>
                                            <input type="number" class="form-control" id="radius_input" value="50" aria-label="Radius dalam meter">
                                            <span class="input-group-text">m</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="lat" name="lat">
                                <input type="hidden" id="lon" name="lon">
                                <input type="hidden" id="radius" name="radius">

                                <div class="text-center">
                                    <button type="submit" name="submit_lokasi" class="btn btn-lg bg-gradient-success w-100 mt-3">Selesai & Simpan Kegiatan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>
    <?php include "js-include.php"; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.getElementById('map'),
              latInput = document.getElementById('lat'),
              lonInput = document.getElementById('lon'),
              latDisplay = document.getElementById('lat_display'),
              lonDisplay = document.getElementById('lon_display'),
              radiusInput = document.getElementById('radius'),
              radiusValueInput = document.getElementById('radius_input');
        
        const defaultLat = -6.9929, defaultLon = 110.4203;

        const map = L.map(mapContainer).setView([defaultLat, defaultLon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        let marker = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(map);
        let circle = L.circle([defaultLat, defaultLon], { radius: 50 }).addTo(map);
        
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

        document.getElementById('gmap_search_btn').addEventListener('click', () => {
            const query = document.getElementById('gmap_search').value;
            if (query) window.open(`http://googleusercontent.com/maps.google.com/9{encodeURIComponent(query)}`, '_blank');
        });

        document.getElementById('apply_coords_btn').addEventListener('click', () => {
            const lat = parseFloat(latDisplay.value.replace(",", "."));
            const lon = parseFloat(lonDisplay.value.replace(",", "."));
            if (!isNaN(lat) && !isNaN(lon)) {
                updateAllData(L.latLng(lat, lon), parseInt(radiusValueInput.value, 10) || 50);
            } else {
                alert('Format Latitude atau Longitude tidak valid.');
            }
        });

        map.on('click', e => updateAllData(e.latlng, parseInt(radiusValueInput.value, 10) || 50));
        marker.on('dragend', () => updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50));
        radiusValueInput.addEventListener('input', () => updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 0));

        updateAllData(marker.getLatLng(), parseInt(radiusValueInput.value, 10) || 50);
    });
    </script>
</body>
</html>