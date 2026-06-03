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
    /* ═══════ PREMIUM DASHBOARD CSS ═══════ */
    body { font-family: 'Roboto', 'Inter', -apple-system, sans-serif !important; }

    /* Alternating row backgrounds */
    ul#data-tek-today li:nth-child(even) .row,
    ul#data-tek-upcoming li:nth-child(even) .row { background-color: #fafbfc; border-radius: 4px; }

    /* Toggles */
    #toggleLoadMore, #toggleLoadMore1, #toggleLoadMore2 { border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    input[type="checkbox"] { -webkit-appearance: checkbox; -moz-appearance: checkbox; appearance: checkbox; }

    /* Scrollbar */
    #reasonHistoryList::-webkit-scrollbar { width: 6px; }
    #reasonHistoryList::-webkit-scrollbar-track { background: #f8fafc; }
    #reasonHistoryList::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    #reasonHistoryList::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ── Section Headers (Dark Slate) ── */
    .section-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; background: #1e293b; border-radius: 10px 10px 0 0;
      cursor: pointer; transition: background 0.2s;
    }
    .section-header:hover { background: #334155; }
    .section-header h6 { margin: 0; font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.04em; text-transform: uppercase; }
    .section-header .material-icons { font-size: 18px; color: #94a3b8; }
    .btn-export {
      font-size: 11px; padding: 6px 14px; background: rgba(255,255,255,0.1); color: #e2e8f0;
      border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; font-weight: 600;
      text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;
    }
    .btn-export:hover { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.3); }

    /* ── Section Card ── */
    .section-card {
      border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; border-top: none;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 4px 16px rgba(0,0,0,0.02);
      background: #fff;
    }

    /* ── Table Header Row ── */
    .tbl-header {
      background: #f8fafc !important; border: none !important;
      border-bottom: 2px solid #e2e8f0 !important; border-radius: 0 !important;
      padding: 12px 16px !important;
    }
    .tbl-th {
      font-size: 10.5px; font-weight: 700; color: #64748b;
      text-transform: uppercase; letter-spacing: 0.06em;
    }

    /* ── Data Rows ── */
    .tbl-row {
      border: none !important; border-bottom: 1px solid #f1f5f9 !important;
      border-radius: 0 !important; padding: 16px !important; transition: background 0.15s;
    }
    .tbl-row:hover { background: #f0f4f8 !important; }
    .tbl-row:last-child { border-bottom: none !important; }

    /* ── Status Badges (Pill Shape) ── */
    .badge-status {
      font-size: 10px; font-weight: 600; padding: 4px 10px;
      border-radius: 20px; letter-spacing: 0.02em; display: inline-block;
    }
    .badge-selesai { background: #dcfce7; color: #166534; }
    .badge-dikerjakan { background: #dbeafe; color: #1e40af; }
    .badge-lanjut { background: #f1f5f9; color: #475569; }
    .badge-dilanjutkan { background: #e0e7ff; color: #3730a3; }
    .badge-dijadwalkan { background: #f1f5f9; color: #64748b; }
    .badge-menunggu { background: #fef3c7; color: #92400e; }

    /* ── Kegiatan Type Badge ── */
    .badge-type {
      font-size: 9px; font-weight: 700; padding: 3px 10px;
      border-radius: 20px; letter-spacing: 0.04em; text-transform: uppercase;
      display: inline-block;
    }
    .badge-survey { background: #fef3c7; color: #92400e; }
    .badge-service { background: #e0e7ff; color: #3730a3; }
    .badge-pasang { background: #dcfce7; color: #166534; }
    .badge-default { background: #f1f5f9; color: #475569; }

    /* ── Action Buttons ── */
    .btn-act {
      width: 30px; height: 30px; padding: 0; display: inline-flex;
      align-items: center; justify-content: center; border-radius: 6px;
      border: none; transition: all 0.15s; cursor: pointer; text-decoration: none;
    }
    .btn-act-view { background: #eff6ff; color: #3b82f6; }
    .btn-act-view:hover { background: #3b82f6; color: #fff; }
    .btn-act-edit { background: #fffbeb; color: #d97706; }
    .btn-act-edit:hover { background: #d97706; color: #fff; }
    .btn-act-delete { background: #fef2f2; color: #dc2626; }
    .btn-act-delete:hover { background: #dc2626; color: #fff; }

    /* ── Initials Avatar ── */
    .avatar-initials {
      width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .avatar-initials span { font-size: 10px; font-weight: 700; color: #4338ca; }

    /* ── Typography ── */
    .text-name { font-size: 13px; font-weight: 700; color: #1e293b; text-decoration: none; margin: 0 0 2px; }
    .text-name:hover { color: #3b82f6; }
    .text-phone { font-size: 11px; color: #3b82f6; text-decoration: none; }
    .text-phone:hover { text-decoration: underline; }
    .text-note { font-size: 10.5px; color: #94a3b8; margin: 3px 0 0; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .text-time { font-size: 13px; font-weight: 600; color: #1e293b; margin: 5px 0 2px; }
    .text-code { font-size: 10px; color: #94a3b8; display: block; }
    .text-addr { font-size: 11px; color: #475569; margin: 0; line-height: 1.6; }
    .text-date { font-size: 12px; font-weight: 600; color: #1e293b; display: block; }
    .text-hour { font-size: 10px; color: #94a3b8; }

    <?php include "css/floating-menu2.css"; ?>
    @media (min-width: 992px) { .w-lg-30 { width: 30% !important; } }
    @media (min-width: 768px) and (max-width: 991px) { .w-md-70 { width: 50% !important; } }
    @media (max-width: 767px) { .w-sm-100 { width: 60% !important; } }

    /* ═══════ MOBILE COMPACT KEGIATAN ═══════ */
    @media (max-width: 768px) {
      /* Compact list items */
      .tbl-row { padding: 10px 12px !important; }
      .tbl-row .row { margin: 0 !important; }
      .tbl-row .row > [class*="col-"] { padding: 0 !important; margin-bottom: 4px !important; }

      /* Smaller fonts */
      .text-time { font-size: 12px !important; margin: 2px 0 !important; }
      .text-code { font-size: 9px !important; }
      .text-name { font-size: 12px !important; margin: 0 !important; }
      .text-phone { font-size: 10px !important; }
      .text-note { font-size: 9px !important; margin: 1px 0 0 !important; max-width: 100%; }
      .text-addr {
        font-size: 10px !important; line-height: 1.4 !important;
        display: -webkit-box !important; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
      }
      .text-date { font-size: 11px !important; }
      .text-hour { font-size: 9px !important; }

      /* Compact badges */
      .badge-type { font-size: 8px !important; padding: 2px 8px !important; }
      .badge-status { font-size: 9px !important; padding: 2px 8px !important; }

      /* Compact action buttons */
      .btn-act { width: 26px !important; height: 26px !important; }
      .btn-act .material-icons { font-size: 13px !important; }
      .avatar-initials { width: 26px !important; height: 26px !important; border-radius: 6px !important; }
      .avatar-initials span { font-size: 8px !important; }

      /* Section header compact */
      .section-header { padding: 10px 14px !important; }
      .section-header h6 { font-size: 11px !important; }
      .btn-export { font-size: 9px !important; padding: 4px 8px !important; }
      .section-header input[type="text"] { width: 140px !important; font-size: 10px !important; padding: 5px 8px 5px 28px !important; }

      /* Scrollable content */
      .section-card { border-radius: 0 0 8px 8px !important; }
      .section-card .card-body { padding: 0 !important; }

      /* Main content padding */
      div[style*="padding:0 24px"] { padding: 0 8px !important; }
      div[style*="padding:0 24px 24px"] { padding: 0 8px 12px !important; }
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" style="display:flex;flex-direction:column;overflow:hidden;">
    <?php
    include "nav-top.php";
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
    ?>

    <!-- Fixed Section: Stat Cards -->
    <div style="flex-shrink:0;padding:0 24px;">
      <?php include 'top-point.php'; ?>
    </div>

    <!-- Scrollable Section: Tables -->
    <div style="flex:1;overflow-y:auto;padding:0 24px 24px;">
      <div class="row mb-4">
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
          $statusMap = ['selesai' => ['text' => 'Selesai', 'class' => 'badge-status badge-selesai'], 'berjalan' => ['text' => 'Dikerjakan', 'class' => 'badge-status badge-dikerjakan'], 'menunggu laporan' => ['text' => 'Menunggu Laporan', 'class' => 'badge-status badge-menunggu'], 'Lanjut Nanti' => ['text' => 'Lanjut Nanti', 'class' => 'badge-status badge-lanjut'], 'Lanjutan' => ['text' => 'Dilanjutkan', 'class' => 'badge-status badge-dilanjutkan'], 'dijadwalkan' => ['text' => 'Dijadwalkan', 'class' => 'badge-status badge-dijadwalkan']];
          return $statusMap[$status] ?? ['text' => 'Dijadwalkan', 'class' => 'badge-status badge-dijadwalkan'];
        }
        ?>
        <div class="col-lg-12 mt-4 mb-0">
          <div class="d-flex justify-content-between align-items-center section-header" id="toggleLoadMore1">
            <div class="d-flex align-items-center gap-2">
              <i class="material-icons">today</i>
              <h6>Kegiatan Hari Ini</h6>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div style="position:relative;" onclick="event.stopPropagation();">
                <i class="material-icons" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;">search</i>
                <input type="text" placeholder="Cari nama, kode, teknisi..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:240px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="event.stopPropagation();filterRows(this.value,'data-tek-today')">
              </div>
              <a href="?export=hari_ini" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4" id="loadMoreX1" style="display: block;">
          <div class="card section-card h-100 py-3">
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
              <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="data-tek-today">
                <li class="list-group-item tbl-header d-md-block d-none">
                  <div class="row px-3">
                    <div class="col-md-2"><span class="tbl-th">Kegiatan</span></div>
                    <div class="col-md-2"><span class="tbl-th">Customer</span></div>
                    <div class="col-md-2"><span class="tbl-th">Teknisi & Status</span></div>
                    <div class="col-md-3"><span class="tbl-th">Alamat</span></div>
                    <div class="col-md-3 text-center"><span class="tbl-th">Info</span></div>
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
                  <li class="list-group-item tbl-row">
                    <div class="row px-3 w-100 align-items-start">
                      <div class="col-md-2">
                        <?php
                          $kegLower = strtolower($data['kegiatan']);
                          $badgeClass = 'badge-default';
                          if (strpos($kegLower, 'survey') !== false) $badgeClass = 'badge-survey';
                          elseif (strpos($kegLower, 'service') !== false) $badgeClass = 'badge-service';
                          elseif (strpos($kegLower, 'pasang') !== false) $badgeClass = 'badge-pasang';
                        ?>
                        <span class="badge-type <?= $badgeClass ?>"><?= htmlspecialchars($data['kegiatan']) ?></span>
                        <p class="text-time"><?= date("H:i", strtotime($data['jadwal'])) ?> WIB</p>
                        <span class="text-code"><?= $kodeTransaksi; ?></span>
                      </div>
                      <div class="col-md-2">
                        <a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>" class="text-name d-block"><?= htmlspecialchars($data['nama_customer']); ?></a>
                        <a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank" class="text-phone"><?= htmlspecialchars($data['cust_nomor']); ?></a>
                        <p class="text-note">"<?= !empty($data["keterangan"]) ? htmlspecialchars($data["keterangan"]) : '-'; ?>"</p>
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
                          <div style="margin-bottom:6px;">
                            <a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id']; ?>" style="font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;"><?= shortenTechnicianName($rowTeknisi['nama_teknisi']); ?></a>
                            <span class="<?= $statusInfo['class']; ?>" style="margin-top:3px;"><?= $statusInfo['text']; ?></span>
                          </div>
                        <?php } $stmtTeknisi->close(); ?>
                      </div>
                      <div class="col-md-3">
                        <p class="text-addr">
                          <?= htmlspecialchars(getAddressFromCoordinates($data['lat'], $data['lon']) ?: $data['alamat']); ?>
                          <button class="btn-act" style="width:22px;height:22px;display:inline-flex;vertical-align:middle;margin-left:4px;background:transparent;" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;color:#3b82f6;">edit_location</i></button>
                        </p>
                      </div>
                      <div class="col-md-3 text-center">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                          <div class="avatar-initials">
                            <span><?= getInitials($data['request']); ?></span>
                          </div>
                          <div style="text-align:left;min-width:40px;">
                            <span class="text-date"><?= date("d/m", strtotime($data['created_at'])); ?></span>
                            <span class="text-hour"><?= date("H:i", strtotime($data['created_at'])); ?></span>
                          </div>
                          <div class="d-flex gap-1 ms-1">
                            <a class="btn-act btn-act-view" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:14px;">visibility</i></a>
                            <?php if ($pageNow != 'Task') : ?>
                              <a class="btn-act btn-act-edit" href="edit_kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:14px;">edit</i></a>
                              <button class="btn-act btn-act-delete btn-delete" data-kode="<?= $kodeTransaksi; ?>" data-customer="<?= htmlspecialchars($data['nama_customer']); ?>"><i class="material-icons" style="font-size:14px;">delete</i></button>
                            <?php endif; ?>
                          </div>
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
          <div class="d-flex justify-content-between align-items-center section-header" id="toggleLoadMore2">
            <div class="d-flex align-items-center gap-2">
              <i class="material-icons">event_upcoming</i>
              <h6>Kegiatan Akan Datang</h6>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div style="position:relative;" onclick="event.stopPropagation();">
                <i class="material-icons" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;">search</i>
                <input type="text" placeholder="Cari nama, kode, teknisi..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:240px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="event.stopPropagation();filterRows(this.value,'data-tek-upcoming')">
              </div>
              <a href="?export=akan_datang" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4" id="loadMoreX2" style="display: block;">
          <div class="card section-card h-100 py-3">
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
              <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="data-tek-upcoming">
                <li class="list-group-item tbl-header d-md-block d-none">
                  <div class="row px-3">
                    <div class="col-md-2"><span class="tbl-th">Kegiatan</span></div>
                    <div class="col-md-2"><span class="tbl-th">Customer</span></div>
                    <div class="col-md-2"><span class="tbl-th">Teknisi</span></div>
                    <div class="col-md-3"><span class="tbl-th">Alamat</span></div>
                    <div class="col-md-3 text-center"><span class="tbl-th">Info</span></div>
                  </div>
                </li>
                <?php
                $groupedDataUpcoming = [];
                if ($result_upcoming->num_rows > 0) {
                  while ($row = $result_upcoming->fetch_assoc()) { $groupedDataUpcoming[$row['kode']][] = $row; }
                } else {
                  echo "<div style='padding:32px 16px;text-align:center;'><i class='material-icons' style='font-size:40px;color:#cbd5e1;'>event_available</i><p style='font-size:13px;color:#94a3b8;margin:8px 0 0;'>Tidak ada kegiatan yang akan datang.</p></div>";
                }
                foreach ($groupedDataUpcoming as $kodeTransaksi => $data_group) {
                  $data = $data_group[0];
                ?>
                  <li class="list-group-item tbl-row">
                    <div class="row px-3 w-100 align-items-start">
                      <div class="col-md-2">
                        <?php
                          $kegLower2 = strtolower($data['kegiatan']);
                          $badgeClass2 = 'badge-default';
                          if (strpos($kegLower2, 'survey') !== false) $badgeClass2 = 'badge-survey';
                          elseif (strpos($kegLower2, 'service') !== false) $badgeClass2 = 'badge-service';
                          elseif (strpos($kegLower2, 'pasang') !== false) $badgeClass2 = 'badge-pasang';
                        ?>
                        <span class="badge-type <?= $badgeClass2 ?>"><?= htmlspecialchars($data['kegiatan']) ?></span>
                        <p class="text-time"><?= date("d/m/y H:i", strtotime($data['jadwal'])) ?></p>
                      </div>
                      <div class="col-md-2">
                        <a href="customer-detail.php?id_cust=<?= $data['customer_id']; ?>" class="text-name d-block"><?= htmlspecialchars($data['nama_customer']); ?></a>
                        <a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $data['cust_nomor']), 1); ?>" target="_blank" class="text-phone"><?= htmlspecialchars($data['cust_nomor']); ?></a>
                      </div>
                      <div class="col-md-2">
                        <?php
                        $sqlGetTeknisi2 = "SELECT t.nama_teknisi, t.teknisi_id FROM team_kegiatan t WHERE t.kegiatan_id = ? AND t.deleted_at IS NULL GROUP BY t.teknisi_id";
                        $stmtTeknisi2 = $conn->prepare($sqlGetTeknisi2);
                        $stmtTeknisi2->bind_param("i", $data['id']);
                        $stmtTeknisi2->execute();
                        $resultTeknisi2 = $stmtTeknisi2->get_result();
                        while ($rowTeknisi = $resultTeknisi2->fetch_assoc()) {
                          echo "<div style='margin-bottom:4px;'><a href='list-kegiatan-teknisi.php?idTek=".$rowTeknisi['teknisi_id']."' style='font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;'>".shortenTechnicianName($rowTeknisi['nama_teknisi'])."</a></div>";
                        }
                        $stmtTeknisi2->close(); ?>
                      </div>
                      <div class="col-md-3">
                        <p class="text-addr">
                          <?= htmlspecialchars(getAddressFromCoordinates($data['lat'], $data['lon']) ?: $data['alamat']); ?>
                          <button class="btn-act" style="width:22px;height:22px;display:inline-flex;vertical-align:middle;margin-left:4px;background:transparent;" onclick='openLocationModal(<?= json_encode($data) ?>)'><i class="material-icons" style="font-size:12px;color:#3b82f6;">edit_location</i></button>
                        </p>
                      </div>
                      <div class="col-md-3 text-center">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                          <div class="avatar-initials">
                            <span><?= getInitials($data['request']); ?></span>
                          </div>
                          <div class="d-flex gap-1 ms-1">
                            <a class="btn-act btn-act-view" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:14px;">visibility</i></a>
                            <?php if ($pageNow != 'Task') : ?>
                              <a class="btn-act btn-act-edit" href="edit_kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>"><i class="material-icons" style="font-size:14px;">edit</i></a>
                              <button class="btn-act btn-act-delete btn-delete" data-kode="<?= $kodeTransaksi; ?>" data-customer="<?= htmlspecialchars($data['nama_customer']); ?>"><i class="material-icons" style="font-size:14px;">delete</i></button>
                            <?php endif; ?>
                          </div>
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
          <div class="d-flex justify-content-between align-items-center section-header" id="toggleLoadMoreWaiting">
            <div class="d-flex align-items-center gap-2">
              <i class="material-icons">hourglass_empty</i>
              <h6>Waiting List</h6>
            </div>
            <a href="?export=waiting" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4">
          <div class="card section-card h-100" style="border-radius:0 0 10px 10px;border-top:none;padding:12px;">
            <?php
            $sql_waiting = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.id as customer_id, (SELECT COUNT(*) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as reason_count, (SELECT MAX(created_at) FROM kegiatan_reasons kr WHERE kr.kegiatan_id = k.id) as latest_reason_date FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.status = 'waiting' AND k.deleted_at IS NULL ORDER BY k.created_at ASC";
            $result_waiting = mysqli_query($conn, $sql_waiting);
            $totalW = mysqli_num_rows($result_waiting);
            if ($totalW > 0) {
              while ($row = mysqli_fetch_assoc($result_waiting)) {
                $status_display = "Dilaporkan";
                $status_css = "background:#fef3c7;color:#92400e;";
                $card_border = "border-left:4px solid #f59e0b;";
                $date_css = "";
                $jadwal_raw = $row["jadwal"];
                $jadwal_display = date('d/m/y', strtotime($row["created_at"]));
                if ($jadwal_raw != '0000-00-00 00:00:00' && !empty($jadwal_raw)) {
                    $status_display = "Dijadwalkan";
                    $status_css = "background:#dbeafe;color:#1e40af;";
                    $card_border = "border-left:4px solid #3b82f6;";
                    $tgl_req = strtotime($jadwal_raw);
                    $jadwal_display = date('d/m/y H:i', $tgl_req);
                    if (date('Y-m-d', $tgl_req) < date('Y-m-d')) {
                        $status_display = "Terlambat";
                        $status_css = "background:#fee2e2;color:#991b1b;";
                        $card_border = "border-left:4px solid #ef4444;";
                        $date_css = "color:#dc2626 !important;";
                    }
                }
                $hasReason = $row['reason_count'] > 0;
                $kegL = strtolower($row['kegiatan']);
                $tCSS = "background:#f1f5f9;color:#475569;";
                if (strpos($kegL, 'survey') !== false) $tCSS = "background:#fef3c7;color:#92400e;";
                elseif (strpos($kegL, 'service') !== false) $tCSS = "background:#e0e7ff;color:#3730a3;";
                elseif (strpos($kegL, 'pasang') !== false) $tCSS = "background:#dcfce7;color:#166534;";
                $fullAddr = getAddressFromCoordinates($row['lat'], $row['lon']) ?: $row['alamat'];
            ?>
            <div style="background:#fff;border:1px solid #e9ecef;border-radius:10px;margin-bottom:10px;overflow:hidden;transition:all 0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.03);<?= $card_border ?>" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)';this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.03)';this.style.transform='none'">
              <div style="padding:14px 18px;">
                <!-- Top: Badges + Customer + Actions -->
                <div class="d-flex align-items-center justify-content-between" style="margin-bottom:10px;">
                  <div class="d-flex align-items-center gap-2" style="flex:1;min-width:0;">
                    <span style="font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;white-space:nowrap;<?= $status_css ?>"><?= $status_display ?></span>
                    <span style="font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;letter-spacing:0.04em;text-transform:uppercase;white-space:nowrap;<?= $tCSS ?>"><?= htmlspecialchars($row['kegiatan']) ?></span>
                    <a href="customer-detail.php?id_cust=<?= $row['customer_id'] ?>" style="font-size:14px;font-weight:700;color:#1e293b;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['nama_customer']) ?></a>
                  </div>
                  <div class="d-flex align-items-center gap-1" style="flex-shrink:0;margin-left:12px;">
                    <button type="button" class="btn-act <?= $hasReason ? 'btn-act-view' : '' ?> reason-btn" data-id="<?= $row['id'] ?>" style="<?= !$hasReason ? 'background:#fff7ed;color:#ea580c;' : '' ?>" title="<?= $hasReason ? $row['reason_count'].' catatan' : 'Tambah' ?>">
                      <i class="material-icons" style="font-size:14px;"><?= $hasReason ? 'history' : 'add_comment' ?></i>
                    </button>
                    <button type="button" class="btn-act btn-act-edit jadwalkan-btn" data-id="<?= $row["id"] ?>" data-tgl-request="<?= $row["jadwal"] ?>" title="Jadwalkan">
                      <i class="material-icons" style="font-size:14px;">calendar_today</i>
                    </button>
                    <button type="button" class="btn-act btn-act-delete hapus-btn" data-id="<?= $row["id"] ?>" data-kode="<?= $row["kode"] ?>" data-nama="<?= htmlspecialchars($nmUser) ?>" title="Hapus" style="background:#f8fafc;color:#94a3b8;">
                      <i class="material-icons" style="font-size:14px;">delete</i>
                    </button>
                  </div>
                </div>
                <!-- Bottom: Details Grid -->
                <div class="row align-items-start">
                  <div class="col-md-2">
                    <a href="https://api.whatsapp.com/send?phone=62<?= substr(preg_replace('/[^0-9]/', '', $row['cust_nomor']), 1) ?>" target="_blank" class="text-phone" style="display:inline-flex;align-items:center;gap:3px;">
                      <i class="material-icons" style="font-size:13px;">phone</i> <?= htmlspecialchars($row['cust_nomor']) ?>
                    </a>
                    <div style="margin-top:5px;">
                      <span style="font-size:12px;font-weight:600;color:#1e293b;<?= $date_css ?>"><?= $jadwal_display ?></span>
                      <span class="text-code" style="margin-left:4px;"><?= $row['kode'] ?></span>
                    </div>
                  </div>
                  <div class="col-md-7">
                    <p style="font-size:11.5px;color:#475569;line-height:1.5;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="<?= htmlspecialchars($fullAddr) ?>">
                      <i class="material-icons" style="font-size:12px;vertical-align:middle;color:#94a3b8;margin-right:2px;">location_on</i>
                      <?= htmlspecialchars($fullAddr) ?>
                      <button class="btn-act" style="width:20px;height:20px;background:transparent;display:inline-flex;vertical-align:middle;margin-left:3px;" onclick='openLocationModal(<?= json_encode($row) ?>)'><i class="material-icons" style="font-size:11px;color:#3b82f6;">edit</i></button>
                    </p>
                    <?php if (!empty($row['keterangan'])): ?>
                    <p style="font-size:10.5px;color:#94a3b8;font-style:italic;margin:4px 0 0;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;">"<?= htmlspecialchars($row['keterangan']) ?>"</p>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-3 text-end">
                    <span style="font-size:11px;font-weight:600;color:#1e293b;background:#f1f5f9;padding:4px 10px;border-radius:6px;"><?= htmlspecialchars($row['request']) ?></span>
                    <?php if ($hasReason): ?>
                    <span style="font-size:10px;color:#16a34a;font-weight:600;display:block;margin-top:4px;"><?= $row['reason_count'] ?> catatan</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php } } else { ?>
            <div style="padding:48px 20px;text-align:center;">
              <i class="material-icons" style="font-size:56px;color:#e2e8f0;">check_circle_outline</i>
              <p style="font-size:14px;color:#94a3b8;margin:12px 0 0;">Semua kegiatan sudah terjadwalkan 🎉</p>
            </div>
            <?php } ?>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        const kode = this.dataset.kode;
        const customer = this.dataset.customer;
        Swal.fire({
          title: 'Hapus Kegiatan?',
          html: `<p style="margin:0;color:#666;">Kode: <strong>${kode}</strong></p><p style="margin:0;color:#666;">Customer: <strong>${customer}</strong></p><p style="margin-top:10px;color:#e74c3c;font-size:13px;">Data yang dihapus tidak dapat dikembalikan!</p>`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#e74c3c',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">delete</i> Ya, Hapus',
          cancelButtonText: 'Batal',
          reverseButtons: true,
          focusCancel: true,
          customClass: { popup: 'shadow-lg' }
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'delete-kegiatan.php?kode=' + kode;
          }
        });
      });
    });
  </script>
  <script>
    function filterRows(query, listId) {
      var list = document.getElementById(listId);
      if (!list) return;
      var rows = list.querySelectorAll('.tbl-row');
      var q = query.toLowerCase().trim();
      var count = 0;
      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        if (q === '' || text.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
      });
      var noResult = list.querySelector('.search-no-result');
      if (count === 0 && q !== '') {
        if (!noResult) { noResult = document.createElement('li'); noResult.className = 'search-no-result list-group-item'; noResult.style.cssText = 'padding:24px 16px;text-align:center;color:#94a3b8;font-size:13px;border:none;'; list.appendChild(noResult); }
        noResult.innerHTML = '<i class="material-icons" style="font-size:32px;color:#cbd5e1;display:block;margin-bottom:8px;">search_off</i>Tidak ditemukan "' + query + '"';
        noResult.style.display = '';
      } else if (noResult) { noResult.style.display = 'none'; }
    }
  </script>
</body>
</html>