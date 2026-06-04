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
      font-size: 9px; font-weight: 700; padding: 2px 7px;
      border-radius: 4px; letter-spacing: 0.04em; display: inline-flex;
      align-items: center; gap: 3px; white-space: nowrap;
    }
    .badge-status .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .badge-selesai { background: #dcfce7; color: #166534; }
    .badge-selesai .dot { background: #16a34a; }
    .badge-dikerjakan { background: #dbeafe; color: #1e40af; }
    .badge-dikerjakan .dot { background: #2563eb; animation: pulse-dot 1.5s infinite; }
    .badge-lanjut { background: #fef2f2; color: #991b1b; }
    .badge-lanjut .dot { background: #dc2626; }
    .badge-dilanjutkan { background: #e0e7ff; color: #3730a3; }
    .badge-dilanjutkan .dot { background: #6366f1; }
    .badge-dijadwalkan { background: #f1f5f9; color: #64748b; }
    .badge-dijadwalkan .dot { background: #94a3b8; }
    .badge-menunggu { background: #fef3c7; color: #92400e; }
    .badge-menunggu .dot { background: #f59e0b; }
    @keyframes pulse-dot { 0%,100% { opacity:1; } 50% { opacity:0.3; } }

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

    /* ── Filter Bar ── */
    .filter-bar {
      display: flex; align-items: center; gap: 8px; padding: 10px 16px;
      background: #fff; border-bottom: 1px solid #f1f5f9;
      overflow-x: auto; -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }
    .filter-bar::-webkit-scrollbar { display: none; }
    .filter-pill {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 600;
      border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
      cursor: pointer; white-space: nowrap; transition: all 0.2s;
      user-select: none;
    }
    .filter-pill:hover { border-color: #94a3b8; color: #475569; }
    .filter-pill.active { background: #1e293b; color: #fff; border-color: #1e293b; }
    .filter-pill.active.pill-service { background: #3730a3; border-color: #3730a3; }
    .filter-pill.active.pill-survey { background: #92400e; border-color: #92400e; }
    .filter-pill.active.pill-pasang { background: #166534; border-color: #166534; }
    .filter-pill .pill-count {
      font-size: 9px; font-weight: 700; min-width: 18px; height: 18px;
      display: inline-flex; align-items: center; justify-content: center;
      border-radius: 10px; background: #f1f5f9; color: #64748b;
    }
    .filter-pill.active .pill-count { background: rgba(255,255,255,0.2); color: #fff; }

    <?php include "css/floating-menu2.css"; ?>
    @media (min-width: 992px) { .w-lg-30 { width: 30% !important; } }
    @media (min-width: 768px) and (max-width: 991px) { .w-md-70 { width: 50% !important; } }
    @media (max-width: 767px) { .w-sm-100 { width: 60% !important; } }

    /* ═══════ MOBILE RESPONSIVE (≤991px) ═══════ */
    @media (max-width: 991px) {
      /* Reduce outer padding */
      div[style*="padding:0 24px"] { padding: 0 12px !important; }
      div[style*="padding:0 24px 24px"] { padding: 0 12px 16px !important; }
    }

    /* ═══════ TABLET / FOLD UNFOLDED (≤991px) ═══════ */
    @media (max-width: 991px) {
      .section-header { padding: 10px 14px !important; }
      .section-header h6 { font-size: 12px !important; }
    }

    /* ═══════ MOBILE: Horizontal Scroll Table (same as desktop) ═══════ */
    @media (max-width: 767px) {
      /* Make the list + card container horizontally scrollable */
      .section-card,
      .section-card .card-body,
      .section-card .list-group {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
      }
      .section-card::-webkit-scrollbar { height: 4px; }
      .section-card::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

      /* Force EVERY data row to stay horizontal — override Bootstrap .row */
      li.list-group-item.tbl-row > .row,
      li.tbl-row > .row,
      .tbl-row > .row,
      .tbl-row .row.px-3,
      .tbl-row .row.w-100 {
        min-width: 850px !important;
        flex-wrap: nowrap !important;
        display: flex !important;
        flex-direction: row !important;
      }

      .tbl-row {
        padding: 10px 12px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        overflow: visible !important;
      }

      /* Column widths matching desktop proportions */
      .tbl-row .col-md-2 {
        flex: 0 0 18% !important;
        max-width: 18% !important;
        width: 18% !important;
        padding: 0 6px !important;
      }
      .tbl-row .col-md-3 {
        flex: 0 0 23% !important;
        max-width: 23% !important;
        width: 23% !important;
        padding: 0 6px !important;
      }

      /* Table header row same treatment */
      .tbl-header > .row,
      .tbl-header .row {
        min-width: 850px !important;
        flex-wrap: nowrap !important;
        display: flex !important;
      }
      .tbl-header .col-md-2 {
        flex: 0 0 18% !important; max-width: 18% !important; width: 18% !important;
      }
      .tbl-header .col-md-3 {
        flex: 0 0 23% !important; max-width: 23% !important; width: 23% !important;
      }

      /* Slightly smaller fonts for density */
      .badge-type { font-size: 8px !important; padding: 2px 8px !important; }
      .text-time { font-size: 12px !important; font-weight: 700 !important; margin: 2px 0 !important; }
      .text-code { font-size: 9px !important; }
      .text-name { font-size: 12px !important; margin: 0 0 2px !important; }
      .text-phone { font-size: 10px !important; }
      .text-note { font-size: 9px !important; margin: 2px 0 0 !important; -webkit-line-clamp: 1; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; }
      .text-addr { font-size: 10px !important; line-height: 1.4 !important; }
      .badge-status { font-size: 8px !important; padding: 2px 6px !important; }
      .text-date { font-size: 10px !important; }
      .text-hour { font-size: 9px !important; }

      /* Compact elements */
      .btn-act { width: 26px !important; height: 26px !important; }
      .btn-act .material-icons { font-size: 13px !important; }
      .avatar-initials { width: 26px !important; height: 26px !important; border-radius: 6px !important; }
      .avatar-initials span { font-size: 9px !important; }

      /* Section header: stack title / search on mobile */
      .section-header {
        padding: 10px 12px !important;
        border-radius: 8px 8px 0 0 !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
      }
      .section-header > div:first-child {
        width: 100% !important;
        flex: 0 0 100% !important;
      }
      .section-header > div:last-child {
        width: 100% !important;
        flex: 0 0 100% !important;
        justify-content: space-between !important;
      }
      .section-header h6 { font-size: 12px !important; }
      .section-header input[type="text"] {
        flex: 1 !important;
        width: auto !important;
        min-width: 0 !important;
        font-size: 11px !important;
        padding: 6px 10px 6px 30px !important;
      }
      .btn-export { font-size: 9px !important; padding: 5px 12px !important; flex-shrink: 0; }

      /* Padding */
      div[style*="padding:0 24px"] { padding: 0 8px !important; }
      div[style*="padding:0 24px 24px"] { padding: 0 8px 12px !important; }
    }

    /* ═══════ EXTRA SMALL: Samsung Fold folded (≤400px) ═══════ */
    @media (max-width: 400px) {
      .tbl-row .row.px-3 { min-width: 800px !important; }
      .section-header input[type="text"] { font-size: 10px !important; padding: 5px 8px 5px 26px !important; }
      .btn-export { font-size: 8px !important; padding: 4px 8px !important; }
      div[style*="padding:0 24px"] { padding: 0 6px !important; }
      div[style*="padding:0 24px 24px"] { padding: 0 6px 10px !important; }
    }

    /* ═══════ LANDSCAPE MOBILE FIX ═══════ */
    @media (max-width: 991px) {
      .main-content {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        max-height: none !important;
        height: auto !important;
        min-height: 100vh !important;
      }
      /* Let stat cards shrink in landscape */
      .stat-desktop .card-body { padding: 8px !important; }
      .stat-desktop h3 { font-size: 24px !important; }
    }

    /* Landscape on phone: use mobile strip + allow scroll */
    @media (max-height: 500px) and (orientation: landscape) {
      .stat-desktop { display: none !important; }
      .stat-mobile { display: block !important; }
      .main-content {
        overflow-y: auto !important;
        max-height: none !important;
        height: auto !important;
      }
      body { padding-bottom: 60px !important; }
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <?php include "cek-menu.php"; ?>
  <main class="main-content position-relative border-radius-lg" style="display:flex;flex-direction:column;overflow-y:auto;overflow-x:hidden;">
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
          $statusMap = [
            'selesai' => ['text' => '<span class="dot"></span>SLS', 'class' => 'badge-status badge-selesai'],
            'berjalan' => ['text' => '<span class="dot"></span>DKJ', 'class' => 'badge-status badge-dikerjakan'],
            'menunggu laporan' => ['text' => '<span class="dot"></span>M.Lap', 'class' => 'badge-status badge-menunggu'],
            'Lanjut Nanti' => ['text' => '<span class="dot"></span>LN', 'class' => 'badge-status badge-lanjut'],
            'Lanjutan' => ['text' => '<span class="dot"></span>DLJ', 'class' => 'badge-status badge-dilanjutkan'],
            'dijadwalkan' => ['text' => '<span class="dot"></span>JDW', 'class' => 'badge-status badge-dijadwalkan']
          ];
          return $statusMap[$status] ?? ['text' => '<span class="dot"></span>JDW', 'class' => 'badge-status badge-dijadwalkan'];
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
                <input type="text" placeholder="Cari nama, kode, teknisi..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:240px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="event.stopPropagation();filterRows(this.value,'data-tek-today','today')">
              </div>
              <a href="?export=hari_ini" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4" id="loadMoreX1" style="display: block;">
          <div class="card section-card h-100 py-3">
            <!-- Filter Pills -->
            <div class="filter-bar" id="filterBar">
              <span class="filter-pill active" data-filter="all" onclick="applyTypeFilter('all',this,'data-tek-today')">
                <i class="material-icons" style="font-size:13px;">apps</i> Semua
              </span>
              <span class="filter-pill pill-service" data-filter="service" onclick="applyTypeFilter('service',this,'data-tek-today')">
                <i class="material-icons" style="font-size:13px;">build</i> Service <span class="pill-count" id="count-service">0</span>
              </span>
              <span class="filter-pill pill-survey" data-filter="survey" onclick="applyTypeFilter('survey',this,'data-tek-today')">
                <i class="material-icons" style="font-size:13px;">search</i> Survey <span class="pill-count" id="count-survey">0</span>
              </span>
              <span class="filter-pill pill-pasang" data-filter="pasang" onclick="applyTypeFilter('pasang',this,'data-tek-today')">
                <i class="material-icons" style="font-size:13px;">router</i> Pasang Baru <span class="pill-count" id="count-pasang">0</span>
              </span>
            </div>
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

            // ── BULK PREFETCH: teknisi + pelaksanaan for today (eliminates N+1 queries) ──
            $allTodayRows = [];
            $allKegiatanIds = [];
            $allKodes = [];
            while ($r = $result_today->fetch_assoc()) {
              $allTodayRows[] = $r;
              $allKegiatanIds[$r['id']] = true;
              $allKodes[$r['kode']] = true;
            }

            // Bulk fetch ALL teknisi for today's kegiatan IDs
            $teknisiByKegiatan = [];
            if (!empty($allKegiatanIds)) {
              $idList = implode(',', array_map('intval', array_keys($allKegiatanIds)));
              $resTek = $conn->query("SELECT kegiatan_id, nama_teknisi, teknisi_id FROM team_kegiatan WHERE kegiatan_id IN ($idList) AND deleted_at IS NULL GROUP BY kegiatan_id, teknisi_id");
              if ($resTek) { while ($rt = $resTek->fetch_assoc()) { $teknisiByKegiatan[$rt['kegiatan_id']][] = $rt; } $resTek->free(); }
            }

            // Bulk fetch ALL pelaksanaan for today's kodes
            $pelaksanaanByKodeTek = [];
            if (!empty($allKodes)) {
              $kodeList = implode(',', array_map(function($k) use ($conn) { return "'" . $conn->real_escape_string($k) . "'"; }, array_keys($allKodes)));
              $resPel = $conn->query("SELECT kode, teknisi_id, status, waktu_mulai, waktu_selesai, latitude, longitude, latitude_s, longitude_s FROM pelaksanaan_kegiatan WHERE kode IN ($kodeList) AND DATE(waktu_mulai) = '$current_date' ORDER BY id DESC");
              if ($resPel) {
                while ($rp = $resPel->fetch_assoc()) {
                  $key = $rp['kode'] . '_' . $rp['teknisi_id'];
                  if (!isset($pelaksanaanByKodeTek[$key])) { $pelaksanaanByKodeTek[$key] = $rp; } // keep latest (ORDER BY id DESC)
                }
                $resPel->free();
              }
            }
            ?>
            <div class="card-body pb-0 p-0">
              <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="data-tek-today">
                <li class="list-group-item tbl-header">
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
                if (!empty($allTodayRows)) {
                  foreach ($allTodayRows as $row) { $groupedDataToday[$row['kode']][] = $row; }
                } else {
                  echo "<div class='ms-4 text-sm'>Tidak ada kegiatan untuk Hari Ini</div>";
                }
                foreach ($groupedDataToday as $kodeTransaksi => $data_group) {
                  usort($data_group, fn($a, $b) => $b['id'] - $a['id']);
                  $data = $data_group[0];
                ?>
                  <li class="list-group-item tbl-row" data-type="<?= strtolower($data['kegiatan']) ?>">
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
                        $teknisiList = $teknisiByKegiatan[$data['id']] ?? [];
                        foreach ($teknisiList as $rowTeknisi) {
                          $pelKey = $kodeTransaksi . '_' . $rowTeknisi['teknisi_id'];
                          $rowStatus = $pelaksanaanByKodeTek[$pelKey] ?? null;
                          $status_pelaksanaan = $rowStatus['status'] ?? null;
                          $waktu_mulai_tek = $rowStatus['waktu_mulai'] ?? null;
                          $lat_mulai = $rowStatus['latitude'] ?? null;
                          $lon_mulai = $rowStatus['longitude'] ?? null;
                          $waktu_selesai_tek = $rowStatus['waktu_selesai'] ?? null;
                          $lat_selesai = $rowStatus['latitude_s'] ?? null;
                          $lon_selesai = $rowStatus['longitude_s'] ?? null;
                          $statusInfo = getStatusInfo($status_pelaksanaan);
                          $hasMulai = !empty($waktu_mulai_tek) && $waktu_mulai_tek !== '0000-00-00 00:00:00';
                          $hasSelesai = !empty($waktu_selesai_tek) && substr($waktu_selesai_tek, 0, 10) !== '0000-00-00';
                        ?>
                          <div style="margin-bottom:6px;">
                            <a href="list-kegiatan-teknisi.php?idTek=<?= $rowTeknisi['teknisi_id']; ?>" style="font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;"><?= shortenTechnicianName($rowTeknisi['nama_teknisi']); ?></a>
                            <span class="<?= $statusInfo['class']; ?>" style="margin-top:3px;"><?= $statusInfo['text']; ?></span>
                            <?php if ($hasMulai): ?>
                            <div style="margin-top:4px;font-size:10px;color:#64748b;display:flex;align-items:center;gap:3px;flex-wrap:wrap;">
                              <span style="color:#059669;font-weight:600;">▶ <?= date("H:i", strtotime($waktu_mulai_tek)); ?></span>
                              <?php if (!empty($lat_mulai) && !empty($lon_mulai)): ?>
                              <a href="https://www.google.com/maps?q=<?= $lat_mulai ?>,<?= $lon_mulai ?>" target="_blank" style="color:#3b82f6;text-decoration:none;font-size:11px;" title="Lokasi Mulai">📍</a>
                              <?php endif; ?>
                              <?php if ($hasSelesai): ?>
                              <span style="color:#94a3b8;margin:0 1px;">→</span>
                              <span style="color:#dc2626;font-weight:600;">⏹ <?= date("H:i", strtotime($waktu_selesai_tek)); ?></span>
                              <?php if (!empty($lat_selesai) && !empty($lon_selesai)): ?>
                              <a href="https://www.google.com/maps?q=<?= $lat_selesai ?>,<?= $lon_selesai ?>" target="_blank" style="color:#3b82f6;text-decoration:none;font-size:11px;" title="Lokasi Selesai">📍</a>
                              <?php endif; ?>
                              <?php endif; ?>
                            </div>
                            <?php endif; ?>
                          </div>
                        <?php } ?>
                      </div>
                      <div class="col-md-3">
                        <p class="text-addr">
                          <?= htmlspecialchars($data['alamat'] ?? ''); ?>
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
                <input type="text" placeholder="Cari nama, kode, teknisi..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:240px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="event.stopPropagation();filterRows(this.value,'data-tek-upcoming','upcoming')">
              </div>
              <a href="?export=akan_datang" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4" id="loadMoreX2" style="display: block;">
          <div class="card section-card h-100 py-3">
            <!-- Filter Pills Upcoming -->
            <div class="filter-bar" id="filterBarUpcoming">
              <span class="filter-pill active" data-filter="all" onclick="applyTypeFilter('all',this,'data-tek-upcoming','upcoming')">
                <i class="material-icons" style="font-size:13px;">apps</i> Semua
              </span>
              <span class="filter-pill pill-service" data-filter="service" onclick="applyTypeFilter('service',this,'data-tek-upcoming','upcoming')">
                <i class="material-icons" style="font-size:13px;">build</i> Service <span class="pill-count" id="count-service-upcoming">0</span>
              </span>
              <span class="filter-pill pill-survey" data-filter="survey" onclick="applyTypeFilter('survey',this,'data-tek-upcoming','upcoming')">
                <i class="material-icons" style="font-size:13px;">search</i> Survey <span class="pill-count" id="count-survey-upcoming">0</span>
              </span>
              <span class="filter-pill pill-pasang" data-filter="pasang" onclick="applyTypeFilter('pasang',this,'data-tek-upcoming','upcoming')">
                <i class="material-icons" style="font-size:13px;">router</i> Pasang Baru <span class="pill-count" id="count-pasang-upcoming">0</span>
              </span>
            </div>
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

            // ── BULK PREFETCH: teknisi for upcoming ──
            $allUpcomingRows = [];
            $upcomingKegIds = [];
            while ($r = $result_upcoming->fetch_assoc()) {
              $allUpcomingRows[] = $r;
              $upcomingKegIds[$r['id']] = true;
            }
            $teknisiByKegUpcoming = [];
            if (!empty($upcomingKegIds)) {
              $idList2 = implode(',', array_map('intval', array_keys($upcomingKegIds)));
              $resTek2 = $conn->query("SELECT kegiatan_id, nama_teknisi, teknisi_id FROM team_kegiatan WHERE kegiatan_id IN ($idList2) AND deleted_at IS NULL GROUP BY kegiatan_id, teknisi_id");
              if ($resTek2) { while ($rt = $resTek2->fetch_assoc()) { $teknisiByKegUpcoming[$rt['kegiatan_id']][] = $rt; } $resTek2->free(); }
            }
            ?>
            <div class="card-body pb-0 p-0">
              <ul class="list-group m-0 mt-0 col-12 p-0 py-0" id="data-tek-upcoming">
                <li class="list-group-item tbl-header">
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
                if (!empty($allUpcomingRows)) {
                  foreach ($allUpcomingRows as $row) { $groupedDataUpcoming[$row['kode']][] = $row; }
                } else {
                  echo "<div style='padding:32px 16px;text-align:center;'><i class='material-icons' style='font-size:40px;color:#cbd5e1;'>event_available</i><p style='font-size:13px;color:#94a3b8;margin:8px 0 0;'>Tidak ada kegiatan yang akan datang.</p></div>";
                }
                foreach ($groupedDataUpcoming as $kodeTransaksi => $data_group) {
                  $data = $data_group[0];
                ?>
                  <li class="list-group-item tbl-row" data-type="<?= strtolower($data['kegiatan']) ?>">
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
                        <p class="text-note">"<?= !empty($data["keterangan"]) ? htmlspecialchars($data["keterangan"]) : '-'; ?>"</p>
                      </div>
                      <div class="col-md-2">
                        <?php
                        $teknisiListUp = $teknisiByKegUpcoming[$data['id']] ?? [];
                        foreach ($teknisiListUp as $rowTeknisi) {
                          echo "<div style='margin-bottom:4px;'><a href='list-kegiatan-teknisi.php?idTek=".$rowTeknisi['teknisi_id']."' style='font-size:12px;font-weight:600;color:#1e293b;text-decoration:none;display:block;line-height:1.3;'>".shortenTechnicianName($rowTeknisi['nama_teknisi'])."</a></div>";
                        } ?>
                      </div>
                      <div class="col-md-3">
                        <p class="text-addr">
                          <?= htmlspecialchars($data['alamat'] ?? ''); ?>
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
            <div class="d-flex align-items-center gap-2">
              <div style="position:relative;" onclick="event.stopPropagation();">
                <i class="material-icons" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;">search</i>
                <input type="text" placeholder="Cari..." style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:7px 12px 7px 32px;font-size:12px;color:#1e293b;outline:none;width:200px;transition:border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" oninput="event.stopPropagation();filterRows(this.value,'data-waiting','waiting')">
              </div>
              <a href="?export=waiting" class="btn-export" onclick="event.stopPropagation();"><i class="material-icons" style="font-size:14px;">download</i> Export TXT</a>
            </div>
          </div>
        </div>
        <div class="col-lg-12 mt-0 mb-4">
          <div class="card section-card h-100" style="border-radius:0 0 10px 10px;border-top:none;padding:0;">
            <!-- Filter Pills Waiting -->
            <div class="filter-bar" id="filterBarWaiting">
              <span class="filter-pill active" data-filter="all" onclick="applyTypeFilter('all',this,'data-waiting','waiting')">
                <i class="material-icons" style="font-size:13px;">apps</i> Semua
              </span>
              <span class="filter-pill pill-service" data-filter="service" onclick="applyTypeFilter('service',this,'data-waiting','waiting')">
                <i class="material-icons" style="font-size:13px;">build</i> Service <span class="pill-count" id="count-service-waiting">0</span>
              </span>
              <span class="filter-pill pill-survey" data-filter="survey" onclick="applyTypeFilter('survey',this,'data-waiting','waiting')">
                <i class="material-icons" style="font-size:13px;">search</i> Survey <span class="pill-count" id="count-survey-waiting">0</span>
              </span>
              <span class="filter-pill pill-pasang" data-filter="pasang" onclick="applyTypeFilter('pasang',this,'data-waiting','waiting')">
                <i class="material-icons" style="font-size:13px;">router</i> Pasang Baru <span class="pill-count" id="count-pasang-waiting">0</span>
              </span>
            </div>
            <div id="data-waiting" style="padding:12px;">
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
                $fullAddr = $row['alamat'] ?? '';
            ?>
            <div class="waiting-card" data-type="<?= strtolower($row['kegiatan']) ?>" style="background:#fff;border:1px solid #e9ecef;border-radius:10px;margin-bottom:10px;overflow:hidden;transition:all 0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.03);<?= $card_border ?>" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)';this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.03)';this.style.transform='none'">
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
            </div><!-- close data-waiting -->
          </div>
        </div>

        <div class="modal fade" id="reasonModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
              <div class="modal-header" style="background:linear-gradient(135deg,#1e293b,#334155);border:none;padding:18px 24px;">
                <h5 style="color:#fff;font-size:16px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;">
                  <span style="background:rgba(251,191,36,0.2);width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="material-icons" style="font-size:16px;color:#fcd34d;">history</i>
                  </span>
                  Riwayat Penangguhan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity:0.7;"></button>
              </div>
              <div class="modal-body" style="padding:0;">
                <div class="row g-0">
                  <div class="col-md-5" style="padding:20px 24px;border-right:1px solid #f1f5f9;">
                    <form id="reasonForm">
                      <input type="hidden" id="reasonKegiatanId" name="kegiatan_id">
                      <div style="margin-bottom:14px;">
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;display:block;">Alasan Update</label>
                        <textarea class="form-control" id="reasonText" name="reason" rows="4" required style="border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;color:#1e293b;resize:vertical;"></textarea>
                      </div>
                      <div style="margin-bottom:16px;">
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;display:block;">Upload Bukti</label>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px dashed #cbd5e1;border-radius:10px;cursor:pointer;transition:all 0.15s;background:#fafbfc;" onmouseover="this.style.borderColor='#6366f1';this.style.background='#f5f3ff'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fafbfc'">
                          <i class="material-icons" style="font-size:20px;color:#94a3b8;">cloud_upload</i>
                          <span style="font-size:12px;color:#64748b;font-weight:500;">Pilih file gambar atau PDF</span>
                          <input type="file" name="media" accept="image/*,.pdf" style="display:none;">
                        </label>
                      </div>
                      <button type="submit" class="btn w-100" id="saveReasonBtn" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-size:12px;font-weight:600;border:none;border-radius:10px;padding:10px;box-shadow:0 2px 8px rgba(99,102,241,0.3);">
                        <i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">save</i> Simpan Update
                      </button>
                    </form>
                  </div>
                  <div class="col-md-7" style="padding:20px 24px;background:#f8fafc;">
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;display:block;">Riwayat Catatan</label>
                    <div id="reasonHistoryList" style="max-height:300px;overflow-y:auto;border-radius:10px;border:1px solid #e2e8f0;background:#fff;padding:12px;"></div>
                  </div>
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
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
              <div class="modal-header" style="background:linear-gradient(135deg,#1e293b,#334155);border:none;padding:18px 24px;">
                <h5 style="color:#fff;font-size:16px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;">
                  <span style="background:rgba(99,102,241,0.2);width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i class="material-icons" style="font-size:16px;color:#a5b4fc;">event</i>
                  </span>
                  Jadwalkan Kegiatan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity:0.7;"></button>
              </div>
              <div class="modal-body" style="padding:20px 24px;max-height:60vh;overflow-y:auto;">
                <form id="jadwalkanForm">
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div>
                      <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Tanggal</label>
                      <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required style="border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;color:#1e293b;">
                    </div>
                    <div>
                      <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;">Jam</label>
                      <input type="time" class="form-control" id="jam" name="jam" required style="border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;color:#1e293b;">
                    </div>
                  </div>
                  <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;display:block;">Pilih Teknisi</label>
                    <div id="technician-list-container">
                      <?php
                      $res_tek = mysqli_query($conn, "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
                      while ($t = mysqli_fetch_assoc($res_tek)) {
                        echo "<label for='tek".$t['id']."' style='display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid #f1f5f9;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:all 0.15s;background:#fff;' onmouseover=\"this.style.borderColor='#6366f1';this.style.background='#fafafe'\" onmouseout=\"this.style.borderColor='#f1f5f9';this.style.background='#fff'\">";
                        echo "<input class='form-check-input teknisi-checkbox' type='checkbox' name='teknisi[]' value='".$t['id']."' id='tek".$t['id']."' style='margin-top:2px;flex-shrink:0;width:16px;height:16px;border-radius:4px;border:1.5px solid #cbd5e1;'>";
                        echo "<div style='flex:1;min-width:0;'>";
                        echo "<div style='font-size:13px;font-weight:600;color:#1e293b;'>".htmlspecialchars($t['nama'])."</div>";
                        echo "<div class='text-xs' id='jadwal-teknisi-".$t['id']."' style='margin-top:3px;line-height:1.4;'></div>";
                        echo "</div></label>";
                      }
                      ?>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:14px 24px;gap:8px;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="background:#f1f5f9;color:#64748b;font-size:12px;font-weight:600;border:none;border-radius:8px;padding:8px 20px;">Tutup</button>
                <button type="button" class="btn" id="submitJadwalkan" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-size:12px;font-weight:600;border:none;border-radius:8px;padding:8px 20px;box-shadow:0 2px 8px rgba(99,102,241,0.3);">Jadwalkan</button>
              </div>
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
        const badges = data[id].map(i => `<span style="display:inline-block;background:#fef3c7;color:#92400e;font-size:10px;font-weight:600;padding:2px 8px;border-radius:4px;margin:2px 3px 2px 0;">${i.customer} | ${i.waktu}</span>`).join('');
        $(`#jadwal-teknisi-${id}`).html(badges);
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
    // Active filter state per section
    var activeFilters = { today: 'all', upcoming: 'all', waiting: 'all' };

    function filterRows(query, listId, section) {
      section = section || 'today';
      var list = document.getElementById(listId);
      if (!list) return;
      var filter = activeFilters[section] || 'all';
      // Support both tbl-row and waiting-card
      var rows = list.querySelectorAll('.tbl-row, .waiting-card');
      var q = query.toLowerCase().trim();
      var count = 0;
      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var type = (row.getAttribute('data-type') || '').toLowerCase();
        var matchText = (q === '' || text.indexOf(q) > -1);
        var matchType = (filter === 'all' || type.indexOf(filter) > -1);
        if (matchText && matchType) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
      });
      var noResult = list.querySelector('.search-no-result');
      if (count === 0) {
        if (!noResult) { noResult = document.createElement('div'); noResult.className = 'search-no-result'; noResult.style.cssText = 'padding:24px 16px;text-align:center;color:#94a3b8;font-size:13px;'; list.appendChild(noResult); }
        noResult.innerHTML = '<i class="material-icons" style="font-size:32px;color:#cbd5e1;display:block;margin-bottom:8px;">search_off</i>Tidak ditemukan';
        noResult.style.display = '';
      } else if (noResult) { noResult.style.display = 'none'; }
    }

    function applyTypeFilter(type, el, listId, section) {
      section = section || 'today';
      activeFilters[section] = type;
      // Toggle active pill
      var bar = el.parentElement;
      bar.querySelectorAll('.filter-pill').forEach(function(p) { p.classList.remove('active'); });
      el.classList.add('active');
      // Find the search input for this section
      var searchMap = { today: '#toggleLoadMore1', upcoming: '#toggleLoadMore2', waiting: '#toggleLoadMoreWaiting' };
      var searchInput = document.querySelector((searchMap[section] || '#toggleLoadMore1') + ' input[type="text"]');
      filterRows(searchInput ? searchInput.value : '', listId, section);
    }

    // Count types on load for all sections
    document.addEventListener('DOMContentLoaded', function() {
      var sections = [
        { listId: 'data-tek-today', suffix: '', selector: '.tbl-row' },
        { listId: 'data-tek-upcoming', suffix: '-upcoming', selector: '.tbl-row' },
        { listId: 'data-waiting', suffix: '-waiting', selector: '.waiting-card' }
      ];
      sections.forEach(function(sec) {
        var list = document.getElementById(sec.listId);
        if (!list) return;
        var rows = list.querySelectorAll(sec.selector);
        var counts = { service: 0, survey: 0, pasang: 0 };
        rows.forEach(function(row) {
          var type = (row.getAttribute('data-type') || '').toLowerCase();
          if (type.indexOf('service') > -1) counts.service++;
          else if (type.indexOf('survey') > -1) counts.survey++;
          else if (type.indexOf('pasang') > -1) counts.pasang++;
        });
        var cs = document.getElementById('count-service' + sec.suffix); if(cs) cs.textContent = counts.service;
        var cv = document.getElementById('count-survey' + sec.suffix); if(cv) cv.textContent = counts.survey;
        var cp = document.getElementById('count-pasang' + sec.suffix); if(cp) cp.textContent = counts.pasang;
      });
    });
  </script>
</body>
</html>