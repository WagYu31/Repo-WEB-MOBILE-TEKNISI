<?php
function shortenTechnicianName($fullName)
{
    if (empty($fullName)) return '-';
    $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
    $words = explode(" ", $fullName);
    if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
    $shortenedName = implode(" ", $words);
    if (strlen($shortenedName) > 15 && count($words) > 2) {
        $lastWordIndex = count($words) - 1;
        if (isset($words[$lastWordIndex][0])) $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
        $shortenedName = implode(" ", $words);
    }
    return $shortenedName;
}
function getInitials($fullName)
{
    if (empty($fullName)) return '-';
    $words = explode(" ", $fullName);
    $initials = "";
    foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
    return $initials;
}
function getAddressFromCoordinates($lat, $lon)
{
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
function getStatusInfo($status)
{
    $statusMap = ['selesai' => ['text' => 'Selesai', 'class' => 'bg-success'], 'berjalan' => ['text' => 'Dikerjakan', 'class' => 'bg-info'], 'menunggu laporan' => ['text' => 'Menunggu Laporan', 'class' => 'bg-warning'], 'Lanjut Nanti' => ['text' => 'Lanjut Nanti', 'class' => 'bg-dark'], 'Lanjutan' => ['text' => 'Dilanjutkan', 'class' => 'bg-primary'], 'dijadwalkan' => ['text' => 'Dijadwalkan', 'class' => 'bg-secondary']];
    return $statusMap[$status] ?? ['text' => 'Dijadwalkan', 'class' => 'bg-secondary'];
}
?>
<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12"><button id="toggleLoadMore1" type="button" class="btn bg-gradient-info font-weight-bold" style="font-size:16px;">Kegiatan Hari Ini</button></div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4" id="loadMoreX1" style="display: block;">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <?php
        $current_date = date("Y-m-d");
        $sql_today = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id, COALESCE(k.alamat_lokasi, (SELECT cc.address FROM cust_coordinate cc WHERE cc.cust_id = c.id AND cc.lat = k.lat AND cc.lon = k.lon LIMIT 1), c.alamat) AS alamat_lokasi FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status NOT IN ('waiting', 'selesai by admin') AND DATE(k.jadwal) = ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";
        $stmt_today = $conn->prepare($sql_today);
        $stmt_today->bind_param("s", $current_date);
        $stmt_today->execute();
        $result_today = $stmt_today->get_result();
        ?>
        <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-2 col-12 p-2 py-0" id="data-tek-today">
                <li class="list-group-item border text-center d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                    <div class="row px-4">
                        <div class="col-md-1">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Kegiatan</h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6>
                        </div>
                        <div class="col-md-2">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi & Status</h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6>
                        </div>
                        <div class="col-md-3 text-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Info</h6>
                        </div>
                    </div>
                </li>
                <?php
                $groupedDataToday = [];
                if ($result_today->num_rows > 0) {
                    while ($row = $result_today->fetch_assoc()) {
                        $groupedDataToday[$row['kode']][] = $row;
                    }
                } else {
                    echo "<div class='ms-4 text-sm'>Tidak ada kegiatan untuk Hari Ini</div>";
                }
                foreach ($groupedDataToday as $kodeTransaksi => $data_group) {
                    $data = $data_group[0];
                ?>
                    <li class="list-group-item border d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
                        <div class="row px-3 w-100 align-items-start">
                            <div class="col-md-1">
                                <span class="badge badge-sm bg-gradient-secondary text-capitalize mb-1"><?= htmlspecialchars($data['kegiatan']) ?></span>
                                <p class="text-sm font-weight-bold mb-0"><?= date("H:i", strtotime($data['jadwal'])) ?> WIB</p>
                                <span class="text-xs text-dark d-block"><?= $kodeTransaksi; ?></span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-dark font-weight-bold mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>"><?= htmlspecialchars($data['nama_customer']); ?></a></h6><span class="text-xs"><a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank"><?= htmlspecialchars($data['cust_nomor']); ?></a></span>
                                <p class="text-xs text-secondary mb-0 fst-italic text-wrap">"<?= !empty($data["keterangan"]) ? htmlspecialchars($data["keterangan"]) : '-'; ?>"</p>
                            </div>
                            <div class="col-md-2">
                                <?php
                                $sqlGetTeknisi = "SELECT t.nama_teknisi, t.teknisi_id FROM team_kegiatan t WHERE t.kegiatan_id = ? AND t.deleted_at IS NULL GROUP BY t.teknisi_id";
                                $stmtTeknisi = $conn->prepare($sqlGetTeknisi);
                                $stmtTeknisi->bind_param("i", $data['id']);
                                $stmtTeknisi->execute();
                                $resultTeknisi = $stmtTeknisi->get_result();
                                while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
                                    $status_pelaksanaan = null;
                                    $stmtStatus = $conn->prepare("SELECT status FROM pelaksanaan_kegiatan WHERE kegiatan_id = ? AND teknisi_id = ? AND DATE(waktu_mulai) = ? ORDER BY id DESC LIMIT 1");
                                    $stmtStatus->bind_param("iis", $data['id'], $rowTeknisi['teknisi_id'], $current_date);
                                    $stmtStatus->execute();
                                    $resultStatus = $stmtStatus->get_result();
                                    if ($rowStatus = $resultStatus->fetch_assoc()) {
                                        $status_pelaksanaan = $rowStatus['status'];
                                    }
                                    $statusInfo = getStatusInfo($status_pelaksanaan);
                                ?>
                                    <div class="d-flex justify-content-between align-items-center mb-1"><a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id']; ?>" class="text-xs font-weight-bold text-dark"><?= shortenTechnicianName($rowTeknisi['nama_teknisi']); ?></a><span class="<?= $statusInfo['class']; ?> text-white rounded-pill px-2" style="font-size:10px;"><?= $statusInfo['text']; ?></span></div>
                                <?php }
                                $stmtTeknisi->close(); ?>
                            </div>
                            <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                    <p class="text-xs text-dark mb-0 me-2"><?= htmlspecialchars(!empty($data['alamat_lokasi']) ? $data['alamat_lokasi'] : ($data['alamat'] ?? '')); ?>
                                        <button class="btn btn-secondary text-light p-0 px-1 m-0 ms-2" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-1 me-1 text-primary p-1 rounded-pill btn btn-outline-primary font-weight-bold" style="font-size:12px;"><?= getInitials($data['request']); ?></p>
                                    <div class="text-right">
                                        <?php
                                        $createdAt = $data['created_at'];
                                        $formattedDatecreatedAt = date("d/m", strtotime($createdAt));
                                        $formattedTimecreatedAt = date("H:i", strtotime($createdAt));
                                        ?>
                                        <h6 class="mb-0 font-weight-bold" style="font-size:12px;"><?php echo $formattedDatecreatedAt; ?></h6>
                                        <span class="text-xs text-uppercase"><?php echo $formattedTimecreatedAt; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if ($pageNow != 'Task') : ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" title="Lihat Detail"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                                        <button class="btn btn-warning edit-btn" data-id="<?= $kodeTransaksi; ?>" title="Edit"><i class="material-icons" style="font-size:12px;">edit</i></button>
                                        <a class="btn btn-danger" href="delete-kegiatan.php?kode=<?= $kodeTransaksi; ?>" title="Hapus"><i class="material-icons" style="font-size:12px;">delete</i></a>
                                    <?php else : ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" title="Lihat Detail"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>
<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12"><button id="toggleLoadMore2" type="button" class="btn bg-gradient-primary font-weight-bold" style="font-size:16px;">Kegiatan Akan Datang</button></div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4" id="loadMoreX2" style="display: block;">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <?php
        $sql_upcoming = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id, COALESCE(k.alamat_lokasi, (SELECT cc.address FROM cust_coordinate cc WHERE cc.cust_id = c.id AND cc.lat = k.lat AND cc.lon = k.lon LIMIT 1), c.alamat) AS alamat_lokasi FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status != 'waiting' AND DATE(k.jadwal) > ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";
        $stmt_upcoming = $conn->prepare($sql_upcoming);
        $stmt_upcoming->bind_param("s", $current_date);
        $stmt_upcoming->execute();
        $result_upcoming = $stmt_upcoming->get_result();
        ?>
        <div class="card-body pb-0 p-0">
            <ul class="list-group m-0 mt-2 col-12 p-2 py-0" id="data-tek-upcoming">
                <li class="list-group-item border d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                    <div class="row px-4">
                        <div class="col-md-1">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Kegiatan</h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6>
                        </div>
                        <div class="col-md-2 text-center">
                            <h6 class="mb-1 text-dark font-weight-bold text-sm">Info</h6>
                        </div>
                    </div>
                </li>
                <?php
                $groupedDataUpcoming = [];
                if ($result_upcoming->num_rows > 0) {
                    while ($row = $result_upcoming->fetch_assoc()) {
                        $groupedDataUpcoming[$row['kode']][] = $row;
                    }
                } else {
                    echo "<div class='ms-4 text-sm'>Tidak ada kegiatan yang akan datang.</div>";
                }
                foreach ($groupedDataUpcoming as $kodeTransaksi => $data_group) {
                    $data = $data_group[0];
                ?>
                    <li class="list-group-item border d-flex flex-column justify-content-between align-items-center ps-0 mb-2 border-radius-lg">
                        <div class="row px-2 w-100 align-items-start">
                            <div class="col-md-1">
                                <span class="badge badge-sm bg-gradient-secondary text-capitalize mb-1"><?= htmlspecialchars($data['kegiatan']) ?></span>
                                <p class="text-sm font-weight-bold mb-0"><?= date("d/m/y H:i", strtotime($data['jadwal'])) ?></p>
                                <span class="text-xs text-dark d-block mt-1"><?= $kodeTransaksi; ?></span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-dark font-weight-bold mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>"><?= htmlspecialchars($data['nama_customer']); ?></a></h6><span class="text-xs"><a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank"><?= htmlspecialchars($data['cust_nomor']); ?></a></span>
                                <p class="text-xs text-secondary mb-0 fst-italic text-wrap">"<?= !empty($data["keterangan"]) ? htmlspecialchars($data["keterangan"]) : '-'; ?>"</p>
                            </div>
                            <div class="col-md-2">
                                <?php
                                $sqlGetTeknisi2 = "SELECT t.nama_teknisi, t.teknisi_id FROM team_kegiatan t WHERE t.kegiatan_id = ? AND t.deleted_at IS NULL GROUP BY t.teknisi_id";
                                $stmtTeknisi2 = $conn->prepare($sqlGetTeknisi2);
                                $stmtTeknisi2->bind_param("i", $data['id']);
                                $stmtTeknisi2->execute();
                                $resultTeknisi2 = $stmtTeknisi2->get_result();
                                while ($rowTeknisi = $resultTeknisi2->fetch_assoc()) {
                                ?>
                                    <div class="d-flex justify-content-between align-items-center mb-1"><a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id']; ?>" class="text-xs font-weight-bold text-dark"><?= shortenTechnicianName($rowTeknisi['nama_teknisi']); ?></a></div>
                                <?php }
                                $stmtTeknisi2->close(); ?>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <p class="text-xs text-dark mb-0 me-2"><?= htmlspecialchars(!empty($data['alamat_lokasi']) ? $data['alamat_lokasi'] : ($data['alamat'] ?? '')); ?><button class="btn btn-secondary text-light p-0 px-1 m-0 ms-2" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button></p>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-1 me-1 text-primary p-1 rounded-pill btn btn-outline-primary font-weight-bold" style="font-size:12px;"><?= getInitials($data['request']); ?></p>
                                    <div class="text-right">
                                        <?php

                                        $createdAt = $data['created_at'];
                                        $formattedDatecreatedAt = date("d/m", strtotime($createdAt));
                                        $formattedTimecreatedAt = date("H:i", strtotime($createdAt));
                                        ?>
                                        <h6 class="mb-0 font-weight-bold" style="font-size:12px;"><?php echo $formattedDatecreatedAt; ?></h6>
                                        <span class="text-xs text-uppercase"><?php echo $formattedTimecreatedAt; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if ($pageNow != 'Task') : ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" title="Lihat Detail"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                                        <button class="btn btn-warning edit-btn" data-id="<?= $kodeTransaksi; ?>" title="Edit"><i class="material-icons" style="font-size:12px;">edit</i></button>
                                        <a class="btn btn-danger" href="delete-kegiatan.php?kode=<?= $kodeTransaksi; ?>" title="Hapus"><i class="material-icons" style="font-size:12px;">delete</i></a>
                                    <?php else : ?>
                                        <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" title="Lihat Detail"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>

<div class="col-lg-12 mt-4 mb-0">
    <div class="row">
        <div class="col-12"><button id="toggleLoadMore2" type="button" class="btn bg-gradient-warning font-weight-bold" style="font-size:16px;">Waiting List</button></div>
    </div>
</div>
<div class="col-lg-12 mt-n3 mb-4">
    <div class="card h-100 py-3" style="border-top-left-radius:0;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        // UPDATE QUERY: 
                        // 1. Ambil jumlah alasan (reason_count)
                        // 2. Ambil tanggal update terakhir (latest_reason_date)
                        $sql_waiting = "
                            SELECT k.*, 
                            c.nama AS nama_customer, 
                            c.telp AS cust_nomor, 
                            c.alamat, 
                            c.id as customer_id,
                            COALESCE(k.alamat_lokasi, (SELECT cc.address FROM cust_coordinate cc WHERE cc.cust_id = c.id AND cc.lat = k.lat AND cc.lon = k.lon LIMIT 1), c.alamat) AS alamat_lokasi,
                            (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count,
                            (SELECT MAX(created_at) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as latest_reason_date
                            FROM kegiatan k 
                            LEFT JOIN customer c ON k.customer_id = c.id 
                            WHERE k.status = 'waiting' AND k.deleted_at IS NULL 
                            ORDER BY k.created_at ASC
                        ";
                        
                        $result_waiting = mysqli_query($conn, $sql_waiting);
                        if (mysqli_num_rows($result_waiting) > 0) {
                            while ($row = mysqli_fetch_assoc($result_waiting)) {
                        ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php
                                        $status_display = "Dilaporkan";
                                        $date_color = "text-dark";
                                        $jadwal_display = date('d-m-y', strtotime($row["created_at"]));
                                        if ($row["jadwal"] != '0000-00-00 00:00:00') {
                                            $status_display = "Dijadwalkan";
                                            $tgl_request = strtotime($row["jadwal"]);
                                            $jadwal_display = date('d-m-y H:i', $tgl_request);
                                            if (date('Y-m-d', $tgl_request) < date('Y-m-d')) $date_color = "text-danger";
                                        }
                                        ?>
                                        <p class="text-xs font-weight-bold mb-0 text-capitalize"><?= htmlspecialchars($status_display); ?></p>
                                        <p class="text-xs text-secondary mb-0 text-capitalize"><?= htmlspecialchars($row["kegiatan"]); ?></p>
                                        <p class="text-xs <?= $date_color ?> font-weight-bold mb-0"><?= $jadwal_display ?></p>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 text-sm text-wrap"><a href="customer-detail.php?id_cust=<?= $row['customer_id']; ?>"><?= htmlspecialchars($row["nama_customer"]); ?></a></h6>
                                        <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['cust_nomor']); ?></p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0 text-capitalize text-wrap"><?= htmlspecialchars(!empty($row['alamat_lokasi']) ? $row['alamat_lokasi'] : ($row['alamat'] ?? '')); ?> <button class="btn btn-secondary text-light p-0 px-1 m-0" onclick='openLocationModal(<?= json_encode($row) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button></p>
                                        <p class="text-xs text-secondary mb-0 fst-italic text-wrap">"<?= !empty($row["keterangan"]) ? htmlspecialchars($row["keterangan"]) : '-'; ?>"</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($row["request"]); ?></p>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        // LOGIKA BARU: Cek Tanggal Update Terakhir
                                        $hasReason = $row['reason_count'] > 0;
                                        $latestDate = $row['latest_reason_date'];
                                        
                                        // Default: Merah (Jika belum ada reason)
                                        $btnReasonClass = "btn-outline-danger"; 
                                        $btnReasonIcon = "add_comment";
                                        $tooltipReason = "Tambah Alasan";

                                        if ($hasReason && !empty($latestDate)) {
                                            // Hitung selisih hari
                                            $dateLast = new DateTime($latestDate);
                                            $dateNow = new DateTime(); // Menggunakan waktu server (WIB)
                                            $diff = $dateNow->diff($dateLast);
                                            $daysDiff = $diff->days;

                                            if ($daysDiff > 7) {
                                                // Jika update terakhir > 7 hari yang lalu -> MERAH
                                                $btnReasonClass = "btn-outline-danger"; 
                                                $btnReasonIcon = "history"; // Icon history tapi merah
                                                $tooltipReason = "Update terakhir " . $daysDiff . " hari lalu (Perlu Update)";
                                            } else {
                                                // Jika update masih baru (<= 7 hari) -> HIJAU
                                                $btnReasonClass = "btn-outline-success";
                                                $btnReasonIcon = "history";
                                                $tooltipReason = "Lihat Riwayat";
                                            }
                                        }
                                        ?>
                                        <button class="btn btn-sm <?= $btnReasonClass ?> reason-btn mb-0" data-id="<?= $row['id'] ?>" title="<?= $tooltipReason ?>">
                                            <i class="material-icons" style="font-size:14px;"><?= $btnReasonIcon ?></i>
                                            <?= $hasReason ? "($row[reason_count])" : "" ?>
                                        </button>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-primary jadwalkan-btn" data-id="<?= $row["id"]; ?>" data-tgl-request="<?= $row["jadwal"]; ?>" title="Jadwalkan"><i class="material-icons" style="font-size:14px;">arrow_upward</i></button>
                                            <button class="btn btn-danger hapus-btn" data-id="<?= $row["id"]; ?>" data-kode="<?= $row["kode"]; ?>" data-nama="<?= htmlspecialchars($nmUser); ?>" title="Hapus"><i class="material-icons" style="font-size:14px;">delete</i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5'>Tidak ada data dalam waiting list.</td></tr>";
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Riwayat Penangguhan Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 border-end">
                        <h6 class="text-sm font-weight-bold mb-3">Tambah Catatan Baru</h6>
                        <form id="reasonForm" enctype="multipart/form-data">
                            <input type="hidden" id="reasonKegiatanId" name="kegiatan_id">
                            
                            <div class="mb-3">
                                <label for="reasonText" class="form-label text-xs">Alasan / Update Status</label>
                                <textarea class="form-control border p-2" id="reasonText" name="reason" rows="4" placeholder="Tulis alasan penangguhan..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="reasonMedia" class="form-label text-xs">Upload Foto/Bukti (Opsional)</label>
                                <input class="form-control form-control-sm border" type="file" id="reasonMedia" name="media" accept="image/*,.pdf">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-sm w-100" id="saveReasonBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Simpan Update
                            </button>
                        </form>
                    </div>

                    <div class="col-md-7">
                         <h6 class="text-sm font-weight-bold mb-3">Riwayat Catatan</h6>
                         
                         <div id="reasonHistoryList" class="p-2 rounded border" 
                              style="max-height: 350px; overflow-y: auto; background-color: #f8f9fa;">
                             
                             <div class="text-center text-muted text-sm py-4">Memuat data...</div>
                         
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="locationForm">
                    <input type="hidden" id="kegiatanId" name="kegiatan_id">
                    <input type="hidden" id="customerId" name="customer_id">
                    <input type="hidden" id="address" name="address">

                    <div class="input-group mb-3">
                        <input type="text" id="addressSearch" class="form-control border p-2" placeholder="Cari alamat...">
                        <button class="btn btn-outline-primary mb-0" type="button" id="addressSearchBtn">Cari</button>
                    </div>

                    <div id="map" style="height: 250px; border-radius: 0.375rem; margin-bottom: 1rem;"></div>

                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text" class="form-control border p-2" id="latitude" name="lat"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text" class="form-control border p-2" id="longitude" name="lon"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Radius (meter)</label><input type="number" class="form-control border p-2" id="radius" name="rad"></div>
                    
                    <div id="saveLocationContainer" class="mt-3 p-3 border rounded" style="display: none;">
                        <input type="hidden" name="save_location" value="on">
                        <div id="location_alias_input_container">
                            <label for="location_alias" class="form-label">Simpan Lokasi Baru Sebagai</label>
                            <input type="text" id="location_alias" name="location_alias" class="form-control border p-2" placeholder="Contoh: Kantor Cabang, Rumah A">
                        </div>
                    </div>
                    
                    <div id="savedLocationsContainer" class="my-3" style="display: none;">
                        <label class="form-label">Pilih dari lokasi tersimpan:</label>
                        <div id="savedLocationsList" class="list-group" style="max-height: 150px; overflow-y: auto;"></div>
                    </div>

                    <div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    let map, marker, radiusCircle;
    const latInput = document.getElementById('latitude'),
        lonInput = document.getElementById('longitude'),
        radInput = document.getElementById('radius'),
        addressInput = document.getElementById('address'),
        kegiatanIdInput = document.getElementById('kegiatanId'),
        customerIdInput = document.getElementById('customerId'),
        locationModal = document.getElementById('locationModal'),
        addressSearchInput = document.getElementById('addressSearch'),
        addressSearchBtn = document.getElementById('addressSearchBtn'),
        savedLocationsContainer = document.getElementById('savedLocationsContainer'),
        savedLocationsList = document.getElementById('savedLocationsList'),
        saveLocationContainer = document.getElementById('saveLocationContainer');

    function initMap() {
        if (!map) {
            map = L.map('map').setView([-6.175110, 106.865036], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
            map.on('click', e => handleNewLocation(e.latlng));
        }
    }

    function reverseGeocode(latlng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`)
            .then(res => res.json())
            .then(data => {
                if (data.display_name) {
                    addressInput.value = data.display_name;
                }
            }).catch(err => console.error("Reverse Geocode Error:", err));
    }
    
    function updateMarkerAndCircle() {
        const lat = parseFloat(latInput.value),
            lon = parseFloat(lonInput.value),
            rad = parseInt(radInput.value) || 50;
        if (!isNaN(lat) && !isNaN(lon)) {
            const latLng = [lat, lon];
            if (!marker) {
                marker = L.marker(latLng, { draggable: true }).addTo(map);
                marker.on('dragend', e => handleNewLocation(e.target.getLatLng()));
            } else {
                marker.setLatLng(latLng);
            }
            if (!radiusCircle) {
                radiusCircle = L.circle(latLng, { radius: rad }).addTo(map);
            } else {
                radiusCircle.setLatLng(latLng).setRadius(rad);
            }
            map.fitBounds(radiusCircle.getBounds(), { padding: [50, 50] });
            reverseGeocode(L.latLng(lat, lon));
        }
    }

    function handleNewLocation(latlng) {
        latInput.value = latlng.lat.toFixed(6);
        lonInput.value = latlng.lng.toFixed(6);
        savedLocationsList.querySelectorAll('.active').forEach(el => el.classList.remove('active'));
        saveLocationContainer.style.display = 'block';
        updateMarkerAndCircle();
    }
    
    function displaySavedLocations(locations) {
        savedLocationsList.innerHTML = '';
        if(locations.length > 0) {
            locations.forEach(loc => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<strong>${loc.alias}</strong><br><small>${loc.address}</small>`;
                item.addEventListener('click', e => {
                    e.preventDefault();
                    latInput.value = loc.lat;
                    lonInput.value = loc.lon;
                    radInput.value = loc.rad;
                    savedLocationsList.querySelectorAll('.active').forEach(el => el.classList.remove('active'));
                    item.classList.add('active');
                    saveLocationContainer.style.display = 'none';
                    updateMarkerAndCircle();
                });
                savedLocationsList.appendChild(item);
            });
            savedLocationsContainer.style.display = 'block';
        } else {
            savedLocationsContainer.style.display = 'none';
        }
    }

    function openLocationModal(data) {
        kegiatanIdInput.value = data.id;
        customerIdInput.value = data.customer_id;
        latInput.value = data.lat || '';
        lonInput.value = data.lon || '';
        radInput.value = data.rad || '';
        saveLocationContainer.style.display = 'none';
        
        fetch(`get_cust_coords.php?customer_id=${data.customer_id}`)
            .then(res => res.json())
            .then(locations => displaySavedLocations(locations))
            .catch(err => console.error("Fetch Saved Coords Error:", err));

        new bootstrap.Modal(locationModal).show();
    }

    addressSearchBtn.addEventListener('click', () => {
        const query = addressSearchInput.value;
        if (!query) return;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    const { lat, lon } = data[0];
                    handleNewLocation(L.latLng(parseFloat(lat), parseFloat(lon)));
                } else {
                    alert('Alamat tidak ditemukan.');
                }
            }).catch(err => console.error("Geocode Search Error:", err));
    });

    locationModal.addEventListener('shown.bs.modal', () => {
        initMap();
        setTimeout(() => {
            map.invalidateSize();
            if(latInput.value && lonInput.value) {
                updateMarkerAndCircle();
            }
        }, 10);
    });

    [latInput, lonInput].forEach(input => input.addEventListener('input', () => {
        savedLocationsList.querySelectorAll('.active').forEach(el => el.classList.remove('active'));
        saveLocationContainer.style.display = 'block';
        updateMarkerAndCircle();
    }));
    radInput.addEventListener('input', updateMarkerAndCircle);

    document.getElementById('locationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        fetch('update_lokasi.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if (data.success) {
                alert('Lokasi berhasil diperbarui.');
                location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Error'));
            }
        }).catch(() => alert('Terjadi kesalahan koneksi.'));
    });
</script>

<script>
$(document).ready(function() {
    
    // Fungsi untuk load history alasan
    function loadReasons(kegiatanId) {
        var listContainer = $('#reasonHistoryList');
        listContainer.html('<div class="text-center text-muted text-sm py-4">Memuat data...</div>');
        
        $.ajax({
            url: 'get_reasons.php',
            type: 'GET',
            data: { id: kegiatanId },
            dataType: 'json',
            success: function(data) {
                listContainer.empty();
                if (data.length > 0) {
                    var html = '<ul class="list-group list-group-flush bg-transparent">';
                    $.each(data, function(index, item) {
                        // Format Tanggal
                        var dateObj = new Date(item.created_at);
                        var dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                        var timeStr = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        
                        var mediaHtml = '';
                        if (item.media) {
                            mediaHtml = `<a href="uploads/reasons/${item.media}" target="_blank" class="badge bg-secondary text-white mt-1 text-decoration-none"><i class="material-icons align-middle" style="font-size:12px;">attach_file</i> Lihat Lampiran</a>`;
                        }

                        html += `
                        <li class="list-group-item bg-white mb-2 border rounded shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-dark border">${dateStr} ${timeStr}</span>
                            </div>
                            <p class="text-sm text-dark mb-1" style="white-space: pre-wrap;">${item.reason}</p>
                            ${mediaHtml}
                        </li>`;
                    });
                    html += '</ul>';
                    listContainer.html(html);
                } else {
                    listContainer.html('<div class="text-center text-muted text-sm py-4"><i class="material-icons mb-2" style="font-size: 32px; color: #ccc;">history_toggle_off</i><br>Belum ada catatan alasan.</div>');
                }
            },
            error: function() {
                listContainer.html('<div class="text-center text-danger text-sm py-4">Gagal memuat data.</div>');
            }
        });
    }

    // Event saat tombol Reason diklik
    $(document).on('click', '.reason-btn', function() {
        var id = $(this).data('id');
        $('#reasonForm')[0].reset();
        $('#reasonKegiatanId').val(id);
        loadReasons(id);
        $('#reasonModal').modal('show');
    });

    $('#reasonForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var btn = $('#saveReasonBtn');
        var spinner = btn.find('.spinner-border');
        
        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: 'save_reason.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    var currentId = $('#reasonKegiatanId').val();
                    $('#reasonForm')[0].reset();
                    $('#reasonKegiatanId').val(currentId);
                    
                    loadReasons(currentId);
                    
                } else {
                    alert('Gagal: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan server.');
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Refresh halaman saat modal ditutup agar status tombol di tabel utama terupdate
    // $('#reasonModal').on('hidden.bs.modal', function () {
        // Opsional: Uncomment baris di bawah jika ingin tabel utama refresh otomatis
        // location.reload(); 
    // });
});
</script>