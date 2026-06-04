<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Task";
$currentPage = "Task";

function shortenTechnicianName($fullName) {
    if (empty($fullName)) return '-';
    $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
    $words = explode(" ", $fullName);
    if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
    $shortenedName = implode(" ", $words);
    if (strlen($shortenedName) > 20) {
        $lastWordIndex = count($words) - 1;
        if ($lastWordIndex > 0) {
            $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
        }
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

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$teknisi_id = $_GET['teknisi_id'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$jenis_kegiatan = $_GET['jenis_kegiatan'] ?? '';

$is_search_triggered = !empty($start_date) || !empty($end_date) || !empty($teknisi_id) || !empty($customer_name) || !empty($jenis_kegiatan);
$groupedData = [];

$sql_all_teknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
$result_all_teknisi = mysqli_query($conn, $sql_all_teknisi);

if ($is_search_triggered) {
    $params = [];
    $types = '';

    $sql_kegiatan = "SELECT DISTINCT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, inv.no_invoice, inv.nominal_invoice
                     FROM kegiatan k
                     LEFT JOIN customer c ON k.customer_id = c.id
                     LEFT JOIN team_kegiatan tk ON k.id = tk.kegiatan_id
                     LEFT JOIN pelaksanaan_kegiatan pk ON k.id = pk.kegiatan_id
                     LEFT JOIN (
                         SELECT kode, no_invoice, nominal_invoice 
                         FROM pendapatan_kegiatan 
                         WHERE deleted_at IS NULL 
                         GROUP BY kode
                     ) inv ON k.kode = inv.kode
                     WHERE k.status != 'waiting' AND k.deleted_at IS NULL";

    if (!empty($start_date) && !empty($end_date)) {
        $sql_kegiatan .= " AND DATE(k.jadwal) BETWEEN ? AND ?";
        $types .= 'ss';
        array_push($params, $start_date, $end_date);
    }
    if (!empty($teknisi_id)) {
        $sql_kegiatan .= " AND pk.teknisi_id = ?";
        $types .= 'i';
        $params[] = $teknisi_id;
    }
    if (!empty($customer_id)) {
        $sql_kegiatan .= " AND k.customer_id = ?";
        $types .= 'i';
        $params[] = $customer_id;
    }
    if (!empty($jenis_kegiatan)) {
        $sql_kegiatan .= " AND k.kegiatan = ?";
        $types .= 's';
        $params[] = $jenis_kegiatan;
    }
    $sql_kegiatan .= " ORDER BY k.jadwal DESC";
    
    $stmt = $conn->prepare($sql_kegiatan);
    if ($stmt && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt) {
        $stmt->execute();
        $result_kegiatan = $stmt->get_result();
        if ($result_kegiatan->num_rows > 0) {
            while ($row = $result_kegiatan->fetch_assoc()) {
                $groupedData[$row['kode']][] = $row;
            }
        }
        $stmt->close();
    }
}

if (isset($_GET['export_txt']) && $_GET['export_txt'] == '1' && !empty($groupedData)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Export_Kegiatan_' . date('Y-m-d_H-i-s') . '.txt"');
    
    $output = "DATA LAPORAN KEGIATAN\r\n";
    $output .= "=====================================================\r\n\r\n";
    
    foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
        $latest_kegiatan = $kegiatan_group[0];
        
        $teknisi_list = [];
        $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk WHERE tk.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
        $stmt_tek = $conn->prepare($sqlTeknisi);
        $stmt_tek->bind_param("s", $kodeTransaksi);
        $stmt_tek->execute();
        $resultTeknisi = $stmt_tek->get_result();
        while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
            $teknisi_list[] = shortenTechnicianName($rowTeknisi['nama_teknisi']);
        }
        $stmt_tek->close();
        
        $teknisi_str = !empty($teknisi_list) ? implode(", ", $teknisi_list) : "N/A";
        
        $output .= "Nama Customer    : " . $latest_kegiatan['nama_customer'] . "\r\n";
        $output .= "Nomor Telepon    : " . $latest_kegiatan['cust_nomor'] . "\r\n";
        $output .= "Tanggal Request  : " . date("d/m/Y, H:i", strtotime($latest_kegiatan['created_at'])) . "\r\n";
        $output .= "Teknisi Terlibat : " . $teknisi_str . "\r\n";
        $output .= "Jenis Kegiatan   : " . ucfirst($latest_kegiatan['kegiatan']) . "\r\n";
        $output .= "Status           : " . ucfirst($latest_kegiatan['status']) . "\r\n";
        $output .= "-----------------------------------------------------\r\n";
    }
    echo $output;
    exit;
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Kegiatan</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .lunas-background { position: relative; z-index: 1; }
        .lunas-background::after { content: ''; position: absolute; top: 0; left: 0; width: 80%; height: 80%; background-image: url('assets/img/lunas.png'); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.1; z-index: -1; }
        <?php include "css/floating-menu2.css";?>

        /* ═══ PREMIUM FILTER CARD ═══ */
        .filter-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 18px 24px 0;
        }
        .filter-header .icon-wrap {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        .filter-header .icon-wrap i { color: #fff; font-size: 15px; }
        .filter-header h5 {
            margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;
            letter-spacing: -0.01em;
        }
        .filter-header .badge-count {
            font-size: 11px; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; background: #f1f5f9; color: #64748b;
        }

        .filter-body { padding: 20px 24px 24px; }

        .filter-body label {
            font-size: 11px; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.06em;
            margin-bottom: 6px; display: block;
        }

        .filter-body .form-control, .filter-body select {
            border: 1.5px solid #e5e7eb !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            font-size: 13px !important;
            color: #1e293b !important;
            background: #f8fafc !important;
            transition: all 0.2s ease !important;
            font-weight: 500 !important;
        }
        .filter-body .form-control:focus, .filter-body select:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.08) !important;
            background: #fff !important;
        }
        .filter-body .form-control::placeholder { color: #94a3b8 !important; font-weight: 400 !important; }

        .btn-filter {
            padding: 12px 28px; border: none; border-radius: 10px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s ease; letter-spacing: 0.02em;
        }
        .btn-filter-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }
        .btn-filter-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.35); }
        .btn-filter-export {
            background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0;
        }
        .btn-filter-export:hover { background: #16a34a; color: #fff; border-color: #16a34a; }

        /* ═══ PREMIUM DATA TABLE ═══ */
        .data-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .data-card .table { margin: 0; table-layout: fixed; width: 100%; }
        .data-card .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 14px;
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }
        .data-card .table tbody tr {
            transition: all 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-card .table tbody tr:hover { background: #f8fafc; }
        .data-card .table tbody td { padding: 14px; vertical-align: top; }

        /* Column widths */
        .col-jadwal { width: 120px; }
        .col-customer { width: 30%; }
        .col-invoice { width: 130px; }
        .col-teknisi { width: 22%; }
        .col-request { width: 70px; }
        .col-aksi { width: 60px; }

        /* Address truncate */
        .addr-text {
            font-size: 11px; color: #64748b; line-height: 1.4;
            margin-top: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        /* Teknisi wrap */
        .tek-wrap {
            display: flex; flex-wrap: wrap; gap: 4px;
        }
        .tek-chip {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; color: #475569; font-weight: 500;
            background: #f1f5f9; padding: 3px 8px; border-radius: 6px;
            white-space: nowrap;
        }
        .tek-chip .tek-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            flex-shrink: 0;
        }

        /* Type badges */
        .type-badge {
            display: inline-block; font-size: 10px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.03em;
        }
        .type-survey { background: #fef3c7; color: #92400e; }
        .type-service { background: #e0e7ff; color: #3730a3; }
        .type-pasang { background: #dcfce7; color: #166534; }
        .type-default { background: #f1f5f9; color: #475569; }

        /* Customer link */
        .cust-link { color: #1e293b; font-weight: 700; font-size: 13px; text-decoration: none; transition: color 0.2s; }
        .cust-link:hover { color: #6366f1; }

        /* Technician avatar */
        .tek-avatar {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 4px 0; font-size: 12px; color: #475569; font-weight: 500;
        }
        .tek-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            flex-shrink: 0;
        }

        /* Invoice */
        .inv-number { font-size: 11px; font-weight: 700; color: #1e293b; margin: 0; }
        .inv-amount { font-size: 12px; font-weight: 700; color: #16a34a; margin: 2px 0 0; }
        .inv-none { font-size: 11px; color: #ef4444; font-weight: 600; }

        /* Request badge */
        .req-badge {
            width: 36px; height: 36px; border-radius: 10px;
            background: #1e293b; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; letter-spacing: 0.02em;
        }

        /* Action button */
        .btn-view {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1.5px solid #e5e7eb; background: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            color: #64748b; cursor: pointer; transition: all 0.2s;
        }
        .btn-view:hover { background: #6366f1; color: #fff; border-color: #6366f1; transform: scale(1.08); }

        /* Empty state */
        .empty-state {
            padding: 60px 20px; text-align: center;
        }
        .empty-state i { font-size: 48px; color: #e2e8f0; margin-bottom: 12px; }
        .empty-state p { font-size: 13px; color: #94a3b8; margin: 0; }

        /* Search results dropdown */
        #searchResults .list-group-item {
            font-size: 13px; padding: 10px 14px;
            border: 1px solid #e5e7eb; cursor: pointer;
        }
        #searchResults .list-group-item:hover { background: #f8fafc; }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">

            <!-- ═══ FILTER CARD ═══ -->
            <div class="filter-card">
                <div class="filter-header">
                    <div class="icon-wrap"><i class="fa-solid fa-filter"></i></div>
                    <h5>Filter Laporan Kegiatan</h5>
                    <?php if (!empty($groupedData)): ?>
                    <span class="badge-count"><?= count($groupedData) ?> hasil</span>
                    <?php endif; ?>
                </div>
                <div class="filter-body">
                    <form method="GET">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label>Jenis Kegiatan</label>
                                <select class="form-control" name="jenis_kegiatan">
                                    <option value="">Semua Jenis</option>
                                    <option value="survey" <?= ($jenis_kegiatan == 'survey' ? ' selected' : '') ?>>Survey</option>
                                    <option value="service" <?= ($jenis_kegiatan == 'service' ? ' selected' : '') ?>>Service</option>
                                    <option value="pasang baru" <?= ($jenis_kegiatan == 'pasang baru' ? ' selected' : '') ?>>Pasang Baru</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Teknisi</label>
                                <select class="form-control" name="teknisi_id">
                                    <option value="">Semua Teknisi</option>
                                    <?php mysqli_data_seek($result_all_teknisi, 0); while ($teknisi = mysqli_fetch_assoc($result_all_teknisi)) { echo "<option value='" . $teknisi['id'] . "'" . ($teknisi_id == $teknisi['id'] ? ' selected' : '') . ">" . htmlspecialchars($teknisi['nama']) . "</option>"; } ?>
                                </select>
                            </div>
                            <div class="col-md-4 position-relative">
                                <label>Nama Customer</label>
                                <input type="text" id="customerSearchInput" name="nama_customer_display" class="form-control" placeholder="Ketik nama customer..." autocomplete="off">
                                <input type="hidden" id="customerIdInput" name="customer_id">
                                <div id="searchResults" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <div class="col-md-4">
                                <label>Dari Tanggal</label>
                                <input type="date" class="form-control" name="start_date" id="startDate" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-md-4">
                                <label>Sampai Tanggal</label>
                                <input type="date" class="form-control" name="end_date" id="endDate" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn-filter btn-filter-primary flex-fill">
                                        <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                                    </button>
                                    <button type="submit" name="export_txt" value="1" class="btn-filter btn-filter-export flex-fill">
                                        <i class="fa-solid fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ DATA TABLE ═══ -->
            <div class="data-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="col-jadwal" style="padding-left:18px;">Jadwal & Jenis</th>
                                <th class="col-customer">Customer & Alamat</th>
                                <th class="col-invoice">Invoice</th>
                                <th class="col-teknisi">Teknisi</th>
                                <th class="col-request text-center">Request</th>
                                <th class="col-aksi text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$is_search_triggered): ?>
                            <tr><td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-filter" style="display:block;"></i>
                                    <p>Gunakan filter di atas untuk menampilkan data kegiatan.</p>
                                </div>
                            </td></tr>
                        <?php elseif (empty($groupedData)): ?>
                            <tr><td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox" style="display:block;"></i>
                                    <p>Tidak ada kegiatan yang cocok dengan kriteria filter.</p>
                                </div>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($groupedData as $kodeTransaksi => $kegiatan_group):
                                $latest_kegiatan = $kegiatan_group[0];
                                $lunas_class = (!empty($latest_kegiatan['lunas']) && $latest_kegiatan['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                                $kegL = strtolower($latest_kegiatan['kegiatan']);
                                $typeClass = 'type-default';
                                if (strpos($kegL, 'survey') !== false) $typeClass = 'type-survey';
                                elseif (strpos($kegL, 'service') !== false) $typeClass = 'type-service';
                                elseif (strpos($kegL, 'pasang') !== false) $typeClass = 'type-pasang';
                            ?>
                            <tr>
                                <!-- Jadwal & Jenis -->
                                <td style="padding-left:18px;">
                                    <span class="type-badge <?= $typeClass ?>"><?= htmlspecialchars($latest_kegiatan['kegiatan']) ?></span>
                                    <div style="margin-top:5px;">
                                        <div style="font-size:12px;font-weight:700;color:#1e293b;white-space:nowrap;"><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])) ?></div>
                                        <div style="font-size:10px;color:#94a3b8;font-weight:500;"><?= date("H:i", strtotime($latest_kegiatan['jadwal'])) ?> WIB</div>
                                    </div>
                                </td>

                                <!-- Customer & Alamat -->
                                <td>
                                    <a href="customer-detail.php?id_cust=<?= $latest_kegiatan['customer_id'] ?>" target="_blank" class="cust-link" style="font-size:12px;"><?= htmlspecialchars($latest_kegiatan['nama_customer']) ?></a>
                                    <?php
                                        $nomorHandphone = $latest_kegiatan['cust_nomor'];
                                        if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                    ?>
                                    <div style="margin-top:2px;">
                                        <a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone ?>" target="_blank" style="font-size:10px;color:#3b82f6;text-decoration:none;font-weight:500;">
                                            <i class="fa-brands fa-whatsapp" style="margin-right:2px;"></i><?= htmlspecialchars($latest_kegiatan['cust_nomor']) ?>
                                        </a>
                                    </div>
                                    <div class="addr-text"><?= htmlspecialchars($latest_kegiatan['alamat']) ?></div>
                                </td>

                                <!-- Invoice -->
                                <td class="<?= $lunas_class ?>">
                                    <?php if (!empty($latest_kegiatan['no_invoice'])) : ?>
                                        <p class="inv-number"><?= htmlspecialchars($latest_kegiatan['no_invoice']) ?></p>
                                        <p class="inv-amount">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.') ?></p>
                                    <?php else: ?>
                                        <span class="inv-none">Belum Ada Invoice</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Teknisi -->
                                <td>
                                    <div class="tek-wrap">
                                    <?php
                                    $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk WHERE tk.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
                                    $stmt_tek = $conn->prepare($sqlTeknisi);
                                    $stmt_tek->bind_param("s", $kodeTransaksi);
                                    $stmt_tek->execute();
                                    $resultTeknisi = $stmt_tek->get_result();
                                    if($resultTeknisi->num_rows > 0) {
                                        while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
                                            echo "<span class='tek-chip'><span class='tek-dot'></span>" . shortenTechnicianName(htmlspecialchars($rowTeknisi['nama_teknisi'])) . "</span>";
                                        }
                                    } else {
                                        echo "<span style='font-size:11px;color:#94a3b8;'>N/A</span>";
                                    }
                                    $stmt_tek->close();
                                    ?>
                                    </div>
                                </td>

                                <!-- Request -->
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div class="req-badge"><?= getInitials($latest_kegiatan['request']) ?></div>
                                        <span style="font-size:10px;color:#94a3b8;"><?= date("d/m/y", strtotime($latest_kegiatan['created_at'])) ?></span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <a class="btn-view" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi ?>">
                                        <i class="fa-solid fa-eye" style="font-size:12px;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>
    <?php include "js-include.php"; ?>
    <script>
        function setDateRange(days) {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - (days - 1));
            const formatDate = (date) => date.toISOString().slice(0, 10);
            document.getElementById('endDate').value = formatDate(endDate);
            document.getElementById('startDate').value = formatDate(startDate);
        }
        document.getElementById('btn7Days').addEventListener('click', () => setDateRange(7));
        document.getElementById('btn30Days').addEventListener('click', () => setDateRange(30));
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customerSearchInput');
    const customerIdInput = document.getElementById('customerIdInput');
    const resultsContainer = document.getElementById('searchResults');
    let debounceTimer;

    searchInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        const searchTerm = searchInput.value;

        if (searchTerm.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`search_customer.php?term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(customer => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.classList.add('list-group-item', 'list-group-item-action');
                            item.textContent = customer.nama;
                            item.setAttribute('data-id', customer.id);
                            
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                searchInput.value = this.textContent;
                                customerIdInput.value = this.getAttribute('data-id');
                                resultsContainer.innerHTML = '';
                            });
                            
                            resultsContainer.appendChild(item);
                        });
                    } else {
                        resultsContainer.innerHTML = '<span class="list-group-item">Customer tidak ditemukan.</span>';
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput) {
            resultsContainer.innerHTML = '';
        }
    });
});
</script>
</body>
</html>