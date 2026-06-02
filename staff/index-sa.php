<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Dashboard";
$currentPage = "Today";


if (isset($_GET['export'])) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Export_' . ucfirst($_GET['export']) . '_' . date('Y-m-d_H-i-s') . '.txt"');

    $output = "DATA LAPORAN KEGIATAN - " . strtoupper(str_replace('_', ' ', $_GET['export'])) . "\r\n";
    $output .= "=====================================================\r\n\r\n";

    $current_date = date("Y-m-d");
    $export_type = $_GET['export'];

    if ($export_type === 'hari_ini' || $export_type === 'akan_datang') {
        if ($export_type === 'hari_ini') {
            $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status NOT IN ('waiting', 'selesai by admin') AND DATE(k.jadwal) = ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";
        } else {
            $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status != 'waiting' AND DATE(k.jadwal) > ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $current_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $groupedData = [];
        while ($row = $result->fetch_assoc()) {
            $groupedData[$row['kode']][] = $row;
        }

        foreach ($groupedData as $kodeTransaksi => $data_group) {
            $data = $data_group[0];

            $teknisi_list = [];
            $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk WHERE tk.kegiatan_id = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
            $stmt_tek = $conn->prepare($sqlTeknisi);
            $stmt_tek->bind_param("i", $data['id']);
            $stmt_tek->execute();
            $resTek = $stmt_tek->get_result();
            while ($rt = $resTek->fetch_assoc()) {
                $teknisi_list[] = $rt['nama_teknisi'];
            }
            $stmt_tek->close();
            $teknisi_str = !empty($teknisi_list) ? implode(", ", $teknisi_list) : "N/A";

            $output .= "Nama Customer    : " . $data['nama_customer'] . "\r\n";
            $output .= "Nomor Telepon    : " . $data['cust_nomor'] . "\r\n";
            $output .= "Tanggal Request  : " . date("d/m/Y, H:i", strtotime($data['created_at'])) . "\r\n";
            $output .= "Teknisi Terlibat : " . $teknisi_str . "\r\n";
            $output .= "Jenis Kegiatan   : " . ucfirst($data['kegiatan']) . "\r\n";
            $output .= "-----------------------------------------------------\r\n";
        }
        $stmt->close();
    } elseif ($export_type === 'waiting') {
        $sql = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status = 'waiting' AND k.deleted_at IS NULL ORDER BY k.created_at ASC";
        $result = mysqli_query($conn, $sql);
        while ($data = mysqli_fetch_assoc($result)) {
            $output .= "Nama Customer    : " . $data['nama_customer'] . "\r\n";
            $output .= "Nomor Telepon    : " . $data['cust_nomor'] . "\r\n";
            $output .= "Tanggal Request  : " . date("d/m/Y, H:i", strtotime($data['created_at'])) . "\r\n";
            $output .= "Teknisi Terlibat : N/A\r\n";
            $output .= "Jenis Kegiatan   : " . ucfirst($data['kegiatan']) . "\r\n";
            $output .= "-----------------------------------------------------\r\n";
        }
    }
    echo $output;
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    ul#data-tek li:nth-child(odd) { background-color: white; }
    ul#data-tek li:nth-child(even) { background-color: #efefef; border-radius: 0; }
    #toggleLoadMore, #toggleLoadMore1, #toggleLoadMore2 { border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    input[type="checkbox"] { -webkit-appearance: checkbox; -moz-appearance: checkbox; appearance: checkbox; }
    #reasonHistoryList::-webkit-scrollbar { width: 6px; }
    #reasonHistoryList::-webkit-scrollbar-track { background: #f1f1f1; }
    #reasonHistoryList::-webkit-scrollbar-thumb { background: #bbb; border-radius: 10px; }
    #reasonHistoryList::-webkit-scrollbar-thumb:hover { background: #888; }
    <?php include "css/floating-menu2.css"; ?>
    @media (min-width: 992px) { .w-lg-30 { width: 30% !important; } }
@media (min-width: 768px) and (max-width: 991px) { .w-md-70 { width: 50% !important; } }
@media (max-width: 767px) { .w-sm-100 { width: 60% !important; } }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <?php
    include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>

    <div class="container-fluid py-4">
      <?php include 'top-point.php'; ?>
      <div class="row mb-4 mt-4">
        <?php
        function shortenTechnicianName($fullName) {
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
        function getInitials($fullName) {
          if (empty($fullName)) return '-';
          $words = explode(" ", $fullName);
          $initials = "";
          foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
          return $initials;
        }
        function getAddressFromCoordinates($lat, $lon) {
          if (empty($lat) || empty($lon)) return null;
          $cacheKey = "geo_" . md5($lat . $lon);
          if (isset($_SESSION[$cacheKey])) return $_SESSION[$cacheKey];
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
        function getStatusInfo($status) {
          $statusMap = ['selesai' => ['text' => 'Selesai', 'class' => 'bg-success'], 'berjalan' => ['text' => 'Dikerjakan', 'class' => 'bg-info'], 'menunggu laporan' => ['text' => 'Menunggu Laporan', 'class' => 'bg-warning'], 'Lanjut Nanti' => ['text' => 'Lanjut Nanti', 'class' => 'bg-dark'], 'Lanjutan' => ['text' => 'Dilanjutkan', 'class' => 'bg-primary'], 'dijadwalkan' => ['text' => 'Dijadwalkan', 'class' => 'bg-secondary']];
          return $statusMap[$status] ?? ['text' => 'Dijadwalkan', 'class' => 'bg-secondary'];
        }
        ?>
        <div class="col-lg-12 mt-4 mb-0">
          <div class="row">
            <div class="col-12 d-flex flex-wrap gap-2">
  <button id="toggleLoadMore1" type="button" class="btn bg-gradient-info font-weight-bold w-sm-100 w-md-70 w-lg-30" style="font-size:16px;">Kegiatan Hari Ini</button>
  <a href="?export=hari_ini" class="btn btn-success w-auto d-flex align-items-center"><i class="material-icons me-2">download</i>TXT</a>
</div>
          </div>
        </div>
        <div class="col-lg-12 mt-n3 mb-4" id="loadMoreX1" style="display: block;">
          <div class="card h-100 py-3" style="border-top-left-radius:0;">
            <?php
            $current_date = date("Y-m-d");
            
            // $sql_today = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id 
            //               FROM kegiatan k 
            //               LEFT JOIN customer c ON k.customer_id = c.id 
            //               WHERE k.status NOT IN ('waiting', 'selesai by admin') 
            //                 AND DATE(k.jadwal) = ? 
            //                 AND k.deleted_at IS NULL 
            //                 AND NOT EXISTS (
            //                     SELECT 1 
            //                     FROM kegiatan k2 
            //                     WHERE k2.kode = k.kode 
            //                       AND k2.id > k.id 
            //                       AND k2.deleted_at IS NULL
            //                 )
            //               ORDER BY k.jadwal ASC";
            
            // $stmt_today = $conn->prepare($sql_today);
            // $stmt_today->bind_param("s", $current_date);
            // $stmt_today->execute();
            // $result_today = $stmt_today->get_result();
            
            

            $sql_today = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status NOT IN ('waiting', 'selesai by admin') AND DATE(k.jadwal) = ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";

            $stmt_today = $conn->prepare($sql_today);

            $stmt_today->bind_param("s", $current_date);

            $stmt_today->execute();

            $result_today = $stmt_today->get_result();
            ?>
            <div class="card-body pb-0 p-0">
              <ul class="list-group m-0 mt-2 col-12 p-2 py-0" id="data-tek-today">
                <li class="list-group-item border text-center d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                  <div class="row px-4">
                    <div class="col-md-1"><h6 class="mb-1 text-dark font-weight-bold text-sm">Kegiatan</h6></div>
                    <div class="col-md-3"><h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6></div>
                    <div class="col-md-2"><h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi & Status</h6></div>
                    <div class="col-md-3"><h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6></div>
                    <div class="col-md-3 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Info</h6></div>
                  </div>
                </li>
                <?php
                $groupedDataToday = [];
                if ($result_today->num_rows > 0) {
                  while ($row = $result_today->fetch_assoc()) { $groupedDataToday[$row['kode']][] = $row; }
                } else {
                  echo "<div class='ms-4 text-sm'>Tidak ada kegiatan untuk Hari Ini</div>";
                }
                foreach ($groupedDataToday as $kodeTransaksi => $data_group) {
                  usort($data_group, fn($a, $b) => $b['id'] - $a['id']);
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
                        <h6 class="text-dark font-weight-bold mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>"><?= htmlspecialchars($data['nama_customer']); ?></a></h6>
                        <span class="text-xs"><a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank"><?= htmlspecialchars($data['cust_nomor']); ?></a></span>
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
                          $stmtStatus = $conn->prepare("SELECT status FROM pelaksanaan_kegiatan WHERE kode = ? AND teknisi_id = ? AND DATE(waktu_mulai) = ? ORDER BY id DESC LIMIT 1");
                          $stmtStatus->bind_param("sis", $kodeTransaksi, $rowTeknisi['teknisi_id'], $current_date);
                          $stmtStatus->execute();
                          $resultStatus = $stmtStatus->get_result();
                          if ($rowStatus = $resultStatus->fetch_assoc()) { $status_pelaksanaan = $rowStatus['status']; }
                          $statusInfo = getStatusInfo($status_pelaksanaan);
                        ?>
                          <div class="d-flex justify-content-between align-items-center mb-1"><a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id']; ?>" class="text-xs font-weight-bold text-dark"><?= shortenTechnicianName($rowTeknisi['nama_teknisi']); ?></a><span class="<?= $statusInfo['class']; ?> text-white rounded-pill px-2" style="font-size:10px;"><?= $statusInfo['text']; ?></span></div>
                        <?php } $stmtTeknisi->close(); ?>
                      </div>
                      <div class="col-md-4">
                        <div class="d-flex align-items-center">
                          <p class="text-xs text-dark mb-0 me-2"><?= htmlspecialchars(getAddressFromCoordinates($data['lat'], $data['lon']) ?: $data['alamat']); ?>
                            <button class="btn btn-secondary text-light p-0 px-1 m-0 ms-2" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button>
                          </p>
                        </div>
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="d-flex align-items-center justify-content-between">
                          <p class="mb-1 me-1 text-primary p-1 rounded-pill btn btn-outline-primary font-weight-bold" style="font-size:12px;"><?= getInitials($data['request']); ?></p>
                          <div class="text-right">
                            <h6 class="mb-0 font-weight-bold" style="font-size:12px;"><?= date("d/m", strtotime($data['created_at'])); ?></h6>
                            <span class="text-xs text-uppercase"><?= date("H:i", strtotime($data['created_at'])); ?></span>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="btn-group btn-group-sm">
                          <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                          <?php if ($pageNow != 'Task') : ?>
                            <!--<button class="btn btn-warning edit-btn" data-id="<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">edit</i></button>-->
                            <a class="btn btn-warning edit-btn" href="edit_kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">edit</i></a>
                            <a class="btn btn-danger" href="delete-kegiatan.php?kode=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">delete</i></a>
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
            <div class="col-12 d-flex flex-wrap gap-2">
  <button id="toggleLoadMore2" type="button" class="btn bg-gradient-primary font-weight-bold w-sm-100 w-md-70 w-lg-30" style="font-size:16px;">Kegiatan Akan Datang</button>
  <a href="?export=akan_datang" class="btn btn-success w-auto d-flex align-items-center"><i class="material-icons me-2">download</i>TXT</a>
</div>
          </div>
        </div>
        <div class="col-lg-12 mt-n3 mb-4" id="loadMoreX2" style="display: block;">
          <div class="card h-100 py-3" style="border-top-left-radius:0;">
            <?php
            // $sql_upcoming = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status != 'waiting' AND DATE(k.jadwal) > ? AND k.deleted_at IS NULL ORDER BY k.jadwal ASC";
            // $stmt_upcoming = $conn->prepare($sql_upcoming);
            // $stmt_upcoming->bind_param("s", $current_date);
            // $stmt_upcoming->execute();
            // $result_upcoming = $stmt_upcoming->get_result();
            ?>
            <?php
            $sql_upcoming = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id AS customer_id 
                             FROM kegiatan k 
                             LEFT JOIN customer c ON k.customer_id = c.id 
                             WHERE k.id IN (
                                 SELECT MAX(id) 
                                 FROM kegiatan 
                                 WHERE deleted_at IS NULL 
                                 GROUP BY kode
                             ) 
                             AND k.status != 'waiting' 
                             AND DATE(k.jadwal) > ? 
                             ORDER BY k.jadwal ASC";
            
            $stmt_upcoming = $conn->prepare($sql_upcoming);
            $stmt_upcoming->bind_param("s", $current_date);
            $stmt_upcoming->execute();
            $result_upcoming = $stmt_upcoming->get_result();
            ?>
            <div class="card-body pb-0 p-0">
              <ul class="list-group m-0 mt-2 col-12 p-2 py-0" id="data-tek-upcoming">
                <li class="list-group-item border d-flex flex-column justify-content-between ps-0 mb-2 border-radius-lg d-md-block d-none">
                  <div class="row px-4">
                    <div class="col-md-1"><h6 class="mb-1 text-dark font-weight-bold text-sm">Kegiatan</h6></div>
                    <div class="col-md-3"><h6 class="mb-1 text-dark font-weight-bold text-sm">Customer</h6></div>
                    <div class="col-md-3"><h6 class="mb-1 text-dark font-weight-bold text-sm">Teknisi</h6></div>
                    <div class="col-md-3"><h6 class="mb-1 text-dark font-weight-bold text-sm">Alamat</h6></div>
                    <div class="col-md-2 text-center"><h6 class="mb-1 text-dark font-weight-bold text-sm">Info</h6></div>
                  </div>
                </li>
                <?php
                $groupedDataUpcoming = [];
                if ($result_upcoming->num_rows > 0) {
                  while ($row = $result_upcoming->fetch_assoc()) { $groupedDataUpcoming[$row['kode']][] = $row; }
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
                      </div>
                      <div class="col-md-3">
                        <h6 class="text-dark font-weight-bold mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>"><?= htmlspecialchars($data['nama_customer']); ?></a></h6>
                        <span class="text-xs"><a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank"><?= htmlspecialchars($data['cust_nomor']); ?></a></span>
                      </div>
                      <div class="col-md-2">
                        <?php
                        $sqlGetTeknisi2 = "SELECT t.nama_teknisi, t.teknisi_id FROM team_kegiatan t WHERE t.kegiatan_id = ? AND t.deleted_at IS NULL GROUP BY t.teknisi_id";
                        $stmtTeknisi2 = $conn->prepare($sqlGetTeknisi2);
                        $stmtTeknisi2->bind_param("i", $data['id']);
                        $stmtTeknisi2->execute();
                        $resultTeknisi2 = $stmtTeknisi2->get_result();
                        while ($rowTeknisi = $resultTeknisi2->fetch_assoc()) {
                          echo "<div class='d-flex justify-content-between mb-1'><a href='list-kegiatan-teknisi.php?idTek=".$rowTeknisi['teknisi_id']."' class='text-xs font-weight-bold text-dark'>".shortenTechnicianName($rowTeknisi['nama_teknisi'])."</a></div>";
                        }
                        $stmtTeknisi2->close(); ?>
                      </div>
                      <div class="col-md-4">
                        <p class="text-xs text-dark mb-0"><?= htmlspecialchars(getAddressFromCoordinates($data['lat'], $data['lon']) ?: $data['alamat']); ?><button class="btn btn-secondary text-light p-0 px-1 m-0 ms-2" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button></p>
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="d-flex align-items-center justify-content-between">
                          <p class="mb-1 text-primary p-1 rounded-pill btn btn-outline-primary font-weight-bold" style="font-size:12px;"><?= getInitials($data['request']); ?></p>
                        </div>
                      </div>
                      <div class="col-md-1 text-center">
                        <div class="btn-group btn-group-sm">
                          <a class="btn btn-info" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">visibility</i></a>
                          <?php if ($pageNow != 'Task') : ?>
                            <!--<button class="btn btn-warning edit-btn" data-id="<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">edit</i></button>-->
                            <a class="btn btn-warning edit-btn" href="edit_kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">edit</i></a>
                            <a class="btn btn-danger" href="delete-kegiatan.php?kode=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:12px;">delete</i></a>
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
            <div class="col-12 d-flex flex-wrap gap-2">
  <button id="toggleLoadMoreWaiting" type="button" class="btn bg-gradient-warning font-weight-bold w-sm-100 w-md-70 w-lg-30" style="font-size:16px;">Waiting List</button>
  <a href="?export=waiting" class="btn btn-success w-auto d-flex align-items-center"><i class="material-icons me-2">download</i>TXT</a>
</div>
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
                    $sql_waiting = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id as customer_id, (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count, (SELECT MAX(created_at) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as latest_reason_date FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status = 'waiting' AND k.deleted_at IS NULL ORDER BY k.created_at ASC";
                    $result_waiting = mysqli_query($conn, $sql_waiting);
                    if (mysqli_num_rows($result_waiting) > 0) {
                      while ($row = mysqli_fetch_assoc($result_waiting)) {
                        $status_display = ($row["jadwal"] != '0000-00-00 00:00:00') ? "Dijadwalkan" : "Dilaporkan";
                        $jadwal_display = ($row["jadwal"] != '0000-00-00 00:00:00') ? date('d-m-y H:i', strtotime($row["jadwal"])) : date('d-m-y', strtotime($row["created_at"]));
                        $date_color = ($row["jadwal"] != '0000-00-00 00:00:00' && date('Y-m-d', strtotime($row["jadwal"])) < date('Y-m-d')) ? "text-danger" : "text-dark";
                    ?>
                        <tr>
                          <td class="ps-4">
                            <p class="text-xs font-weight-bold mb-0"><?= $status_display ?></p>
                            <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row["kegiatan"]) ?></p>
                            <p class="text-xs <?= $date_color ?> font-weight-bold mb-0"><?= $jadwal_display ?></p>
                          </td>
                          <td>
                            <h6 class="mb-0 text-sm"><a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>"><?= htmlspecialchars($row["nama_customer"]) ?></a></h6>
                            <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['cust_nomor']) ?></p>
                          </td>
                          <td class="text-wrap">
                            <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars(getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['alamat']) ?> <button class="btn btn-secondary p-0 px-1 m-0" onclick='openLocationModal(<?= json_encode($row) ?>)'><i class="material-icons" style="font-size:12px;">edit</i></button></p>
                            <p class="text-xs text-secondary mb-0 fst-italic">"<?= !empty($row["keterangan"]) ? htmlspecialchars($row["keterangan"]) : '-' ?>"</p>
                          </td>
                          <td class="text-center"><p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($row["request"]) ?></p></td>
                          <td class="text-center">
                            <?php
                            $hasReason = $row['reason_count'] > 0;
                            $btnClass = "btn-outline-danger";
                            if ($hasReason && !empty($row['latest_reason_date'])) {
                              $daysDiff = (new DateTime())->diff(new DateTime($row['latest_reason_date']))->days;
                              if ($daysDiff <= 7) $btnClass = "btn-outline-success";
                            }
                            ?>
                            <button class="btn btn-sm <?= $btnClass ?> reason-btn mb-0" data-id="<?= $row['id'] ?>"><i class="material-icons" style="font-size:14px;">history</i> <?= $hasReason ? "($row[reason_count])" : "" ?></button>
                          </td>
                          <td class="text-center pe-4">
                            <div class="btn-group btn-group-sm">
                              <button class="btn btn-primary jadwalkan-btn" data-id="<?= $row["id"] ?>" data-tgl-request="<?= $row["jadwal"] ?>"><i class="material-icons" style="font-size:14px;">arrow_upward</i></button>
                              <button class="btn btn-danger hapus-btn" data-id="<?= $row["id"] ?>" data-kode="<?= $row["kode"] ?>" data-nama="<?= htmlspecialchars($nmUser) ?>"><i class="material-icons" style="font-size:14px;">delete</i></button>
                            </div>
                          </td>
                        </tr>
                    <?php } } else { echo "<tr><td colspan='6' class='text-center py-5'>Tidak ada data.</td></tr>"; } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="reasonModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header"><h5>Riwayat Penangguhan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-5 border-end">
                    <form id="reasonForm">
                      <input type="hidden" id="reasonKegiatanId" name="kegiatan_id">
                      <div class="mb-3"><label class="form-label text-xs">Alasan Update</label><textarea class="form-control border p-2" id="reasonText" name="reason" rows="4" required></textarea></div>
                      <div class="mb-3"><label class="form-label text-xs">Upload Bukti</label><input class="form-control form-control-sm border" type="file" name="media" accept="image/*,.pdf"></div>
                      <button type="submit" class="btn btn-primary btn-sm w-100" id="saveReasonBtn">Simpan Update</button>
                    </form>
                  </div>
                  <div class="col-md-7"><div id="reasonHistoryList" class="p-2 rounded border" style="max-height: 350px; overflow-y: auto; background-color: #f8f9fa;"></div></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="locationModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5>Edit Data Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <form id="locationForm">
                  <input type="hidden" id="kegiatanId" name="kegiatan_id"><input type="hidden" id="customerId" name="customer_id"><input type="hidden" id="address" name="address">
                  <div class="input-group mb-3"><input type="text" id="addressSearch" class="form-control border p-2" placeholder="Cari alamat..."><button class="btn btn-outline-primary mb-0" type="button" id="addressSearchBtn">Cari</button></div>
                  <div id="map" style="height: 250px; border-radius: 0.375rem; margin-bottom: 1rem;"></div>
                  <div class="row">
                    <div class="col-md-6 mb-3"><label>Latitude</label><input type="text" class="form-control border p-2" id="latitude" name="lat"></div>
                    <div class="col-md-6 mb-3"><label>Longitude</label><input type="text" class="form-control border p-2" id="longitude" name="lon"></div>
                  </div>
                  <div class="mb-3"><label>Radius (m)</label><input type="number" class="form-control border p-2" id="radius" name="rad"></div>
                  <div id="saveLocationContainer" class="mt-3 p-3 border rounded" style="display: none;">
                    <input type="hidden" name="save_location" value="on">
                    <label>Simpan Lokasi Baru Sebagai</label><input type="text" name="location_alias" class="form-control border p-2">
                  </div>
                  <div id="savedLocationsContainer" class="my-3" style="display: none;">
                    <label>Lokasi Tersimpan:</label><div id="savedLocationsList" class="list-group" style="max-height: 150px; overflow-y: auto;"></div>
                  </div>
                  <div class="modal-footer px-0 pb-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="jadwalkanModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5>Jadwalkan Kegiatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <form id="jadwalkanForm">
                  <div class="form-group"><label>Tanggal:</label><input type="date" class="form-control border px-2" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required></div>
                  <div class="form-group mt-2"><label>Jam:</label><input type="time" class="form-control border px-2" id="jam" name="jam" required></div>
                  <div class="form-group mt-2">
                    <label>Pilih Teknisi</label>
                    <div id="technician-list-container">
                      <?php
                      $res_tek = mysqli_query($conn, "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
                      while ($t = mysqli_fetch_assoc($res_tek)) {
                        echo "<div class='form-check mt-2'><input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='".$t['id']."' id='tek".$t['id']."'><label class='form-check-label' for='tek".$t['id']."'>".htmlspecialchars($t['nama'])."</label><div class='text-muted text-xs ms-4' id='jadwal-teknisi-".$t['id']."'></div></div>";
                      }
                      ?>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="button" class="btn btn-primary" id="submitJadwalkan">Jadwalkan</button></div>
            </div>
          </div>
        </div>

      </div>
      <?php 
    //   include "floating-menu.php"; 
      include "footer.php"; ?>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>

  <script>
    let map, marker, radiusCircle;
    const latInput = $('#latitude')[0], lonInput = $('#longitude')[0], radInput = $('#radius')[0], addressInput = $('#address')[0];

    function initMap() {
      if (!map) {
        map = L.map('map').setView([-6.175110, 106.865036], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        map.on('click', e => handleNewLocation(e.latlng));
      }
    }

    function handleNewLocation(latlng) {
      latInput.value = latlng.lat.toFixed(6);
      lonInput.value = latlng.lng.toFixed(6);
      $('#saveLocationContainer').show();
      updateMarkerAndCircle();
    }

    function updateMarkerAndCircle() {
      const lat = parseFloat(latInput.value), lon = parseFloat(lonInput.value), rad = parseInt(radInput.value) || 50;
      if (!isNaN(lat) && !isNaN(lon)) {
        const latLng = [lat, lon];
        if (!marker) { marker = L.marker(latLng, {draggable: true}).addTo(map); marker.on('dragend', e => handleNewLocation(e.target.getLatLng())); }
        else { marker.setLatLng(latLng); }
        if (!radiusCircle) { radiusCircle = L.circle(latLng, {radius: rad}).addTo(map); }
        else { radiusCircle.setLatLng(latLng).setRadius(rad); }
        map.fitBounds(radiusCircle.getBounds());
      }
    }

    function openLocationModal(data) {
      $('#kegiatanId').val(data.id);
      $('#customerId').val(data.customer_id);
      latInput.value = data.lat || '';
      lonInput.value = data.lon || '';
      radInput.value = data.rad || '';
      $('#saveLocationContainer').hide();
      fetch(`get_cust_coords.php?customer_id=${data.customer_id}`).then(res => res.json()).then(locs => {
        $('#savedLocationsList').empty();
        if(locs.length > 0) {
          locs.forEach(l => {
            const item = $(`<a href="#" class="list-group-item list-group-item-action"><strong>${l.alias}</strong><br><small>${l.address}</small></a>`);
            item.click(e => { e.preventDefault(); latInput.value = l.lat; lonInput.value = l.lon; radInput.value = l.rad; updateMarkerAndCircle(); });
            $('#savedLocationsList').append(item);
          });
          $('#savedLocationsContainer').show();
        } else { $('#savedLocationsContainer').hide(); }
      });
      new bootstrap.Modal($('#locationModal')[0]).show();
    }

    $('#locationModal').on('shown.bs.modal', function() { initMap(); setTimeout(() => { map.invalidateSize(); if(latInput.value) updateMarkerAndCircle(); }, 100); });

    $('#addressSearchBtn').click(() => {
      const q = $('#addressSearch').val();
      if(q) fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`).then(res => res.json()).then(d => {
        if(d.length > 0) handleNewLocation(L.latLng(parseFloat(d[0].lat), parseFloat(d[0].lon)));
      });
    });

    $('#locationForm').submit(function(e) {
      e.preventDefault();
      $.ajax({ url: 'update_lokasi.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, success: r => { alert('Berhasil'); location.reload(); } });
    });

    $(document).on('click', '.reason-btn', function() {
      const id = $(this).data('id');
      $('#reasonKegiatanId').val(id);
      $('#reasonHistoryList').html('Memuat...');
      $.getJSON('get_reasons.php', {id: id}, function(data) {
        let h = '';
        if(data.length > 0) {
          data.forEach(i => {
            h += `<div class="bg-white p-2 mb-2 border rounded shadow-sm"><small class="badge bg-light text-dark">${i.created_at}</small><p class="text-sm mb-1">${i.reason}</p>${i.media ? `<a href="uploads/reasons/${i.media}" target="_blank" class="text-xs text-primary">Lihat Bukti</a>` : ''}</div>`;
          });
        } else { h = 'Belum ada catatan.'; }
        $('#reasonHistoryList').html(h);
      });
      new bootstrap.Modal($('#reasonModal')[0]).show();
    });

    $('#reasonForm').submit(function(e) {
      e.preventDefault();
      $.ajax({ url: 'save_reason.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, success: () => { alert('Tersimpan'); location.reload(); } });
    });

    $(document).on('click', '.jadwalkan-btn', function() {
      $('#jadwalkanForm').data('id', $(this).data('id'));
      const tglReq = $(this).data('tgl-request');
      if(tglReq && tglReq != '0000-00-00 00:00:00') {
        const p = tglReq.split(' ');
        $('#tanggal').val(p[0]);
        $('#jam').val(p[1]);
      }
      checkJadwal();
      new bootstrap.Modal($('#jadwalkanModal')[0]).show();
    });

    async function checkJadwal() {
      const tgl = $('#tanggal').val();
      if(!tgl) return;
      const res = await fetch(`cek_jadwal_teknisi.php?tanggal=${tgl}`);
      const data = await res.json();
      $('[id^="jadwal-teknisi-"]').html('');
      for (const id in data) {
        const txt = data[id].map(i => `(${i.customer} | ${i.waktu})`).join(', ');
        $(`#jadwal-teknisi-${id}`).html(`<span class="text-danger fw-bold">${txt}</span>`);
      }
    }

    $('#tanggal').change(checkJadwal);

    $('#submitJadwalkan').click(function() {
      const id = $('#jadwalkanForm').data('id'), tgl = $('#tanggal').val(), jam = $('#jam').val(), tek = $(".teknisi-checkbox:checked").map(function(){return this.value;}).get();
      if(!tgl || !jam || tek.length == 0) return alert("Lengkapi data");
      $.post("proses_jadwalkan.php", {kegiatanId: id, teknisi: tek, tanggal: tgl, jam: jam}, r => {
        if(r == "success") { $.post("wa-msg.php", {teknisi: tek, kegiatanId: id, tanggal: tgl, jam: jam}, () => { location.reload(); }); }
      });
    });

    $(document).on('click', '.hapus-btn', function() {
      if(confirm("Hapus kegiatan?")) {
        $.post("proses_hapus_kegiatan.php", {kegiatanId: $(this).data('id'), nama: $(this).data('nama'), kode: $(this).data('kode')}, () => location.reload());
      }
    });

    $("#toggleLoadMore1").click(() => $("#loadMoreX1").toggle());
    $("#toggleLoadMore2").click(() => $("#loadMoreX2").toggle());
    $("#toggleLoadMoreWaiting").click(() => $(".table-responsive").parent().parent().toggle());

    $('#jam').on('input', function() {
      if(this.value < "07:00" || this.value > "20:00") { alert("Jam operasional 07:00 - 20:00"); this.value = ""; }
    });
  </script>
</body>
</html>