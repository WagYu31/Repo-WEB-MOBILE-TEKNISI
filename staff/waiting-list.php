<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

if (!function_exists('getAddressFromCoordinates')) {
    function getAddressFromCoordinates($lat, $lon) {
        if (empty($lat) || empty($lon)) {
            return null;
        }
        $cacheKey = "geo_" . md5($lat . $lon);
        if (isset($_SESSION[$cacheKey])) {
            return $_SESSION[$cacheKey];
        }
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
        $options = ['http' => ['header' => "User-Agent: LoewixApp/1.0\r\n"]];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            $address = $data['display_name'] ?? null;
            if ($address) {
                $_SESSION[$cacheKey] = $address;
                return $address;
            }
        }
        return null;
    }
}

$pageNow = "Waiting List";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
    <title>LOEWIX | <?php echo $pageNow; ?></title>
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .nav-link i.material-icons { font-size: 2em; }
        .btm-nav { position: fixed; bottom: 15px; left: 0; right: 0; margin: 0 auto; border-radius: 15px; background-color: rgba(0, 0, 0, 0.7); width: 94%; margin-left: 3%; z-index: 1050; }
        #map { height: 300px; width: 100%; border-radius: 8px; z-index: 1; }
        .table-responsive { overflow-x: auto; }
        .modal { z-index: 1060 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php 
        include "nav-top.php"; 
        $todayDate = date('d F Y');
        ?>
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-lg-12 mt-4 mb-0">
                    <button id="toggleLoadMore2" type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Waiting List</button>
                </div>

                <div class="col-lg-12 mt-n3 mb-4">
                    <div class="card h-100 py-3" style="border-top-left-radius:0;">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Status & Jadwal</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alamat & Keterangan</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Request</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">History Alasan</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id as customer_id,
                                                (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count,
                                                (SELECT MAX(created_at) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as latest_reason_date
                                                FROM kegiatan k 
                                                LEFT JOIN customer c ON k.customer_id = c.id 
                                                WHERE k.status = 'waiting' AND k.deleted_at IS NULL 
                                                ORDER BY k.created_at ASC";
                                        $result = mysqli_query($conn, $sql);
                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $status_display = "Dilaporkan";
                                                $date_color = "text-dark";
                                                $jadwal_raw = $row["jadwal"];
                                                $jadwal_display = date('d-m-y', strtotime($row["created_at"]));

                                                if ($jadwal_raw != '0000-00-00 00:00:00' && !empty($jadwal_raw)) {
                                                    $status_display = "Dijadwalkan";
                                                    $tgl_request = strtotime($jadwal_raw);
                                                    $jadwal_display = date('d-m-y H:i', $tgl_request);
                                                    if (date('Y-m-d', $tgl_request) < date('Y-m-d')) $date_color = "text-danger";
                                                }

                                                $hasReason = $row['reason_count'] > 0;
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <p class="text-xs font-weight-bold mb-0"><?= $status_display; ?></p>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row["kegiatan"]); ?></p>
                                                <p class="text-xs <?= $date_color ?> font-weight-bold mb-0"><?= $jadwal_display ?></p>
                                            </td>
                                            <td>
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($row["nama_customer"]); ?></h6>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['cust_nomor']); ?></p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0 text-wrap">
                                                    <?= htmlspecialchars(getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['alamat']); ?>
                                                    <button type="button" class="btn btn-link text-info p-0 m-0 ms-1 edit-loc-btn" 
                                                            data-id="<?= $row['id'] ?>" 
                                                            data-cust="<?= $row['customer_id'] ?>" 
                                                            data-lat="<?= $row['lat'] ?>" 
                                                            data-lon="<?= $row['lon'] ?>" 
                                                            data-rad="<?= $row['rad'] ?>">
                                                        <i class="material-icons" style="font-size:14px;">edit</i>
                                                    </button>
                                                </p>
                                            </td>
                                            <td class="text-center">
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($row["request"]); ?></p>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm <?= $hasReason ? 'btn-outline-success' : 'btn-outline-danger' ?> reason-btn mb-0" data-id="<?= $row['id'] ?>">
                                                    <i class="material-icons" style="font-size:14px;"><?= $hasReason ? 'history' : 'add_comment' ?></i>
                                                    <?= $hasReason ? "({$row['reason_count']})" : "" ?>
                                                </button>
                                            </td>
                                            <td class="text-center pe-4">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary btn-sm jadwalkan-btn" 
                                                            data-id="<?= $row["id"]; ?>" 
                                                            data-tgl="<?= $row["jadwal"]; ?>">
                                                        <i class="material-icons" style="font-size:14px;">calendar_today</i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm hapus-btn" 
                                                            data-id="<?= $row["id"]; ?>" 
                                                            data-kode="<?= $row["kode"]; ?>">
                                                        <i class="material-icons" style="font-size:14px;">delete</i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            // include "floating-menu.php"; 
            include "footer.php"; ?>
        </div>
    </main>

    <div class="modal fade" id="jadwalkanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Jadwalkan Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="jadwalkanForm">
                        <input type="hidden" id="modalKegiatanId">
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control border p-2" id="tanggal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam</label>
                            <input type="time" class="form-control border p-2" id="jam" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Teknisi</label>
                            <div id="technician-list" class="border p-2 rounded" style="max-height:200px; overflow-y:auto;">
                                <?php
                                $st = mysqli_query($conn, "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
                                while ($rt = mysqli_fetch_assoc($st)) {
                                    echo "<div class='form-check'>
                                            <input class='form-check-input tek-check' type='checkbox' value='{$rt['id']}' id='tk{$rt['id']}'>
                                            <label class='form-check-label' for='tk{$rt['id']}'>{$rt['nama']}</label>
                                            <div class='text-xs text-danger' id='info-tk-{$rt['id']}'></div>
                                          </div>";
                                }
                                ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitJadwal">Simpan Jadwal</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="locationForm">
                        <input type="hidden" name="kegiatan_id" id="locKegId">
                        <input type="hidden" name="customer_id" id="locCustId">
                        <div class="input-group mb-2">
                            <input type="text" id="addressSearch" class="form-control border p-2" placeholder="Cari alamat...">
                            <button class="btn btn-primary mb-0" type="button" id="btnSearchLoc">Cari</button>
                        </div>
                        <div id="map"></div>
                        <div class="row mt-2">
                            <div class="col-6"><label class="text-xs">Latitude</label><input type="text" name="lat" id="lat" class="form-control border p-2" readonly></div>
                            <div class="col-6"><label class="text-xs">Longitude</label><input type="text" name="lon" id="lon" class="form-control border p-2" readonly></div>
                        </div>
                        <div class="mt-2">
                            <label class="text-xs">Radius (meter)</label>
                            <input type="number" name="rad" id="rad" class="form-control border p-2">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="save_location" id="saveLocCheck" checked>
                            <label class="form-check-label" for="saveLocCheck">Update Master Customer</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="btnSaveLoc">Update Lokasi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Riwayat Penangguhan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <form id="reasonForm">
                                <input type="hidden" name="kegiatan_id" id="reKegId">
                                <textarea name="reason" class="form-control border p-2 mb-2" rows="4" placeholder="Tulis alasan..." required></textarea>
                                <input type="file" name="media" class="form-control border mb-2">
                                <button type="submit" class="btn btn-info w-100">Simpan Catatan</button>
                            </form>
                        </div>
                        <div class="col-md-7 border-start">
                            <div id="reasonList" style="max-height:300px; overflow-y:auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Inisialisasi Modal BS5 secara manual
        const modalJadwal = new bootstrap.Modal(document.getElementById('jadwalkanModal'));
        const modalLokasi = new bootstrap.Modal(document.getElementById('locationModal'));
        const modalReason = new bootstrap.Modal(document.getElementById('reasonModal'));

        let map, marker, circle;

        $(document).ready(function() {
            // Trigger Jadwal
            $(document).on('click', '.jadwalkan-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const tglFull = $(this).data('tgl');
                $('#modalKegiatanId').val(id);
                if(tglFull && tglFull !== '0000-00-00 00:00:00') {
                    const parts = tglFull.split(' ');
                    $('#tanggal').val(parts[0]);
                    $('#jam').val(parts[1].substring(0,5));
                }
                fetchSchedules();
                modalJadwal.show();
            });

            // Trigger Lokasi
            $(document).on('click', '.edit-loc-btn', function(e) {
                e.preventDefault();
                $('#locKegId').val($(this).data('id'));
                $('#locCustId').val($(this).data('cust'));
                $('#lat').val($(this).data('lat'));
                $('#lon').val($(this).data('lon'));
                $('#rad').val($(this).data('rad') || 50);
                modalLokasi.show();
                setTimeout(initMap, 400);
            });

            // Trigger Reason
            $(document).on('click', '.reason-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                $('#reKegId').val(id);
                loadReasons(id);
                modalReason.show();
            });

            // Hapus
            $(document).on('click', '.hapus-btn', function(e) {
                e.preventDefault();
                if(confirm('Apakah Anda yakin ingin menghapus kegiatan ini?')) {
                    $.post('proses_hapus_kegiatan.php', { 
                        kegiatanId: $(this).data('id'), 
                        kode: $(this).data('kode') 
                    }, function() {
                        location.reload();
                    });
                }
            });

            // Submit Jadwal
            $('#btnSubmitJadwal').click(function() {
                const id = $('#modalKegiatanId').val();
                const tgl = $('#tanggal').val();
                const jam = $('#jam').val();
                const teks = $('.tek-check:checked').map(function() { return this.value; }).get();
                if(!tgl || !jam || teks.length === 0) return alert('Data tidak lengkap!');

                $.post('proses_jadwalkan.php', { 
                    kegiatanId: id, 
                    teknisi: teks, 
                    tanggal: tgl, 
                    jam: jam 
                }, function(res) {
                    if(res.trim() === 'success') {
                        $.post('wa-msg.php', { teknisi: teks, kegiatanId: id, tanggal: tgl, jam: jam });
                        location.reload();
                    } else {
                        alert('Gagal menjadwalkan.');
                    }
                });
            });
        });

        function fetchSchedules() {
            const tgl = $('#tanggal').val();
            if(!tgl) return;
            $.getJSON('cek_jadwal_teknisi.php?tanggal=' + tgl, function(data) {
                $('[id^="info-tk-"]').html('');
                for(let id in data) {
                    let info = data[id].map(i => `${i.customer} (${i.waktu})`).join(', ');
                    $(`#info-tk-${id}`).html(info);
                }
            });
        }

        function initMap() {
            const lt = parseFloat($('#lat').val()) || -6.175;
            const ln = parseFloat($('#lon').val()) || 106.865;
            if(!map) {
                map = L.map('map').setView([lt, ln], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                marker = L.marker([lt, ln], {draggable:true}).addTo(map);
                circle = L.circle([lt, ln], {radius:$('#rad').val()}).addTo(map);
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    $('#lat').val(pos.lat.toFixed(6));
                    $('#lon').val(pos.lng.toFixed(6));
                    circle.setLatLng(pos);
                });
            } else {
                const pos = [lt, ln];
                map.setView(pos, 15);
                marker.setLatLng(pos);
                circle.setLatLng(pos).setRadius($('#rad').val());
            }
            map.invalidateSize();
        }

        $('#btnSearchLoc').click(function() {
            const q = $('#addressSearch').val();
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${q}&limit=1`, function(data) {
                if(data.length > 0) {
                    const lt = data[0].lat, ln = data[0].lon;
                    $('#lat').val(lt); $('#lon').val(ln);
                    const pos = [lt, ln];
                    map.setView(pos, 15);
                    marker.setLatLng(pos);
                    circle.setLatLng(pos);
                }
            });
        });

        $('#btnSaveLoc').click(function() {
            $.post('update_lokasi.php', $('#locationForm').serialize(), function() {
                location.reload();
            });
        });

        function loadReasons(id) {
            $('#reasonList').html('Memuat...');
            $.getJSON('get_reasons.php?id=' + id, function(data) {
                let h = '';
                data.forEach(i => {
                    h += `<div class="p-2 border-bottom mb-2 text-sm bg-light">
                            <div class="fw-bold">${i.formatted_date}</div>
                            <div>${i.reason}</div>
                            ${i.media ? `<a href="uploads/reasons/${i.media}" target="_blank" class="text-xs text-info">Lihat Lampiran</a>` : ''}
                          </div>`;
                });
                $('#reasonList').html(h || 'Tidak ada catatan.');
            });
        }

        $('#reasonForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'save_reason.php', 
                type: 'POST', 
                data: new FormData(this), 
                processData: false, 
                contentType: false,
                success: function() { 
                    loadReasons($('#reKegId').val()); 
                    $('#reasonForm')[0].reset(); 
                }
            });
        });
    </script>
</body>
</html>