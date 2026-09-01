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
    if (strlen($shortenedName) > 18) {
        $lastWordIndex = count($words) - 1;
        if ($lastWordIndex > 0) {
            $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
            $shortenedName = implode(" ", $words);
        }
    }
    return $shortenedName;
}

function getInitials($fullName) {
    if (empty($fullName)) return '-';
    $words = explode(" ", trim($fullName));
    $initials = "";
    foreach ($words as $word) {
        if (!empty($word)) $initials .= strtoupper($word[0]);
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

function formatAlamatWithMaps($rawAlamat) {
    if (empty($rawAlamat)) return '-';
    $pattern = '/(https?:\/\/[^\s]+)/i';
    if (preg_match($pattern, $rawAlamat, $matches)) {
        $url = $matches[0];
        $textOnly = trim(str_replace($url, '', $rawAlamat));
        $cleanText = htmlspecialchars($textOnly);
        $mapsBtn = '<a href="' . htmlspecialchars($url) . '" target="_blank" class="maps-pill-link" title="Buka di Google Maps"><i class="fa-solid fa-map-location-dot"></i> Maps</a>';
        if (!empty($cleanText)) {
            return $cleanText . ' ' . $mapsBtn;
        } else {
            return $mapsBtn;
        }
    }
    return htmlspecialchars($rawAlamat);
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$teknisi_id = $_GET['teknisi_id'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$nama_customer_display = $_GET['nama_customer_display'] ?? '';
$jenis_kegiatan = $_GET['jenis_kegiatan'] ?? '';
$kode_transaksi_filter = $_GET['kode_transaksi_filter'] ?? '';
$no_so_filter = $_GET['no_so_filter'] ?? '';
$alamat_filter = $_GET['alamat_filter'] ?? '';
$status_invoice = $_GET['status_invoice'] ?? '';
$quick_search = $_GET['quick_search'] ?? '';

$is_search_triggered = !empty($start_date) || !empty($end_date) || !empty($teknisi_id) || !empty($customer_id) || !empty($nama_customer_display) || !empty($jenis_kegiatan) || !empty($kode_transaksi_filter) || !empty($no_so_filter) || !empty($alamat_filter) || !empty($status_invoice) || !empty($quick_search);
$groupedData = [];

$sql_all_teknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
$result_all_teknisi = mysqli_query($conn, $sql_all_teknisi);

$params = [];
$types = '';

// Optimized: query with COALESCE to capture SO numbers from both sources
$sql_kegiatan = "SELECT k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat, c.kota, c.provinsi, c.kodepos, inv.no_invoice, inv.nominal_invoice, req_inv.max_req_invoice_at AS req_invoice_at,
                 COALESCE(k.no_so, pk_so.no_so) AS no_so
                 FROM kegiatan k
                 LEFT JOIN customer c ON k.customer_id = c.id
                 LEFT JOIN (
                     SELECT kode, no_invoice, nominal_invoice 
                     FROM pendapatan_kegiatan 
                     WHERE deleted_at IS NULL 
                     GROUP BY kode
                 ) inv ON k.kode = inv.kode
                 LEFT JOIN (
                     SELECT kode, MAX(req_invoice_at) AS max_req_invoice_at
                     FROM kegiatan
                     WHERE deleted_at IS NULL
                     GROUP BY kode
                 ) req_inv ON k.kode = req_inv.kode
                 LEFT JOIN (
                     SELECT kode, no_so 
                     FROM progress_kegiatan 
                     WHERE no_so IS NOT NULL AND no_so != ''
                     GROUP BY kode
                 ) pk_so ON k.kode = pk_so.kode
                 WHERE k.status != 'waiting' AND k.deleted_at IS NULL";

// Universal search filter
if (!empty($quick_search)) {
    $trimmed_qs = trim($quick_search);
    $qs_no_hash = ltrim($trimmed_qs, '#');
    $qs = "%" . $trimmed_qs . "%";
    $qs_hash = "%" . $qs_no_hash . "%";
    
    $clean_digits = preg_replace('/[^0-9]/', '', $trimmed_qs);
    
    $conditions = [
        "c.nama LIKE ?",
        "c.telp LIKE ?",
        "c.alamat LIKE ?",
        "c.kota LIKE ?",
        "c.provinsi LIKE ?",
        "c.kodepos LIKE ?",
        "k.kode LIKE ?",
        "k.no_so LIKE ?",
        "pk_so.no_so LIKE ?",
        "inv.no_invoice LIKE ?"
    ];
    $search_params = [$qs, $qs, $qs, $qs, $qs, $qs, $qs_hash, $qs, $qs, $qs];
    $search_types = "ssssssssss";

    // Multi-word matching for address and customer (e.g. "Serang Pandeglang", "PIK Sonata", "Gudang Rajeg", "Tangerang Banten")
    $words = preg_split('/\s+/', $trimmed_qs);
    if (count($words) > 1 && count($words) <= 5) {
        $word_conditions_alamat = [];
        $word_conditions_nama = [];
        $word_params = [];
        $word_types = "";
        
        foreach ($words as $w) {
            $w = trim($w);
            if (strlen($w) >= 2) {
                $word_conditions_alamat[] = "c.alamat LIKE ?";
                $word_conditions_nama[] = "c.nama LIKE ?";
                $w_param = "%" . $w . "%";
                $word_params[] = $w_param;
                $word_params[] = $w_param;
                $word_types .= "ss";
            }
        }
        if (!empty($word_conditions_alamat)) {
            $conditions[] = "(" . implode(" AND ", $word_conditions_alamat) . ")";
            $conditions[] = "(" . implode(" AND ", $word_conditions_nama) . ")";
            foreach ($word_params as $wp) {
                $search_params[] = $wp;
            }
            $search_types .= $word_types;
        }
    }

    // If search term contains digits (like phone number or SO/Invoice number)
    if (!empty($clean_digits) && strlen($clean_digits) >= 4) {
        $conditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(c.telp, '-', ''), ' ', ''), '+', ''), '.', '') LIKE ?";
        $search_params[] = "%" . $clean_digits . "%";
        $search_types .= "s";
        
        // Also check variations between 08xxx and 62xxx
        if (substr($clean_digits, 0, 2) === '62') {
            $conditions[] = "c.telp LIKE ?";
            $search_params[] = "%0" . substr($clean_digits, 2) . "%";
            $search_types .= "s";
        } elseif (substr($clean_digits, 0, 1) === '0') {
            $conditions[] = "c.telp LIKE ?";
            $search_params[] = "%62" . substr($clean_digits, 1) . "%";
            $search_types .= "s";
        }
    }

    $sql_kegiatan .= " AND (" . implode(" OR ", $conditions) . ")";
    $types .= $search_types;
    foreach ($search_params as $sp) {
        $params[] = $sp;
    }
}

// Filtering by teknisi
if (!empty($teknisi_id)) {
    $sql_kegiatan .= " AND EXISTS (SELECT 1 FROM pelaksanaan_kegiatan pk WHERE pk.kegiatan_id = k.id AND pk.teknisi_id = ?)";
    $types .= 'i';
    $params[] = $teknisi_id;
}
if (!empty($start_date) && !empty($end_date)) {
    $sql_kegiatan .= " AND k.jadwal BETWEEN ? AND ?";
    $types .= 'ss';
    array_push($params, $start_date . ' 00:00:00', $end_date . ' 23:59:59');
}
if (!empty($customer_id)) {
    $sql_kegiatan .= " AND k.customer_id = ?";
    $types .= 'i';
    $params[] = $customer_id;
} elseif (!empty($nama_customer_display)) {
    $sql_kegiatan .= " AND c.nama LIKE ?";
    $types .= 's';
    $params[] = "%" . $nama_customer_display . "%";
}
if (!empty($alamat_filter)) {
    $sql_kegiatan .= " AND (c.alamat LIKE ? OR c.kota LIKE ? OR c.provinsi LIKE ? OR c.kodepos LIKE ?)";
    $types .= 'ssss';
    $af = "%" . trim($alamat_filter) . "%";
    array_push($params, $af, $af, $af, $af);
}
if (!empty($jenis_kegiatan)) {
    $sql_kegiatan .= " AND k.kegiatan = ?";
    $types .= 's';
    $params[] = $jenis_kegiatan;
}
if (!empty($kode_transaksi_filter)) {
    $sql_kegiatan .= " AND k.kode LIKE ?";
    $types .= 's';
    $params[] = "%" . $kode_transaksi_filter . "%";
}
if (!empty($no_so_filter)) {
    $sql_kegiatan .= " AND (k.no_so LIKE ? OR pk_so.no_so LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $no_so_filter . "%";
    $params[] = "%" . $no_so_filter . "%";
}
if ($status_invoice === 'ada_invoice') {
    $sql_kegiatan .= " AND inv.no_invoice IS NOT NULL";
} elseif ($status_invoice === 'belum_input') {
    $sql_kegiatan .= " AND inv.no_invoice IS NULL AND (k.paid IS NULL OR k.paid != 'n/a') AND (k.invoice IS NULL OR k.invoice != 'n/a')";
} elseif ($status_invoice === 'no_pay') {
    $sql_kegiatan .= " AND (k.paid = 'n/a' OR k.invoice = 'n/a')";
}
$sql_kegiatan .= " ORDER BY k.jadwal DESC LIMIT 200";

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

// Batch load all technician data
$teknisiMap = [];
if (!empty($groupedData)) {
    $allKodes = array_keys($groupedData);
    $placeholders = implode(',', array_fill(0, count($allKodes), '?'));
    $typesStr = str_repeat('s', count($allKodes));
    
    $sqlBatchTeknisi = "SELECT kode, nama_teknisi FROM team_kegiatan 
                        WHERE kode IN ($placeholders) AND deleted_at IS NULL 
                        GROUP BY kode, teknisi_id";
    $stmtBatch = $conn->prepare($sqlBatchTeknisi);
    if ($stmtBatch) {
        $stmtBatch->bind_param($typesStr, ...$allKodes);
        $stmtBatch->execute();
        $resBatch = $stmtBatch->get_result();
        while ($r = $resBatch->fetch_assoc()) {
            $teknisiMap[$r['kode']][] = $r['nama_teknisi'];
        }
        $stmtBatch->close();
    }
}

// Calculate live dataset stats for Bento KPI Cards
$statTotal = count($groupedData);
$statPasangBaru = 0;
$statService = 0;
$statSurvey = 0;
$statBelumInvoice = 0;
$statAdaInvoice = 0;

foreach ($groupedData as $kList) {
    $item = $kList[0];
    $kType = strtolower($item['kegiatan'] ?? '');
    if (strpos($kType, 'pasang') !== false) {
        $statPasangBaru++;
    } elseif (strpos($kType, 'service') !== false) {
        $statService++;
    } elseif (strpos($kType, 'survey') !== false) {
        $statSurvey++;
    }
    
    if (!empty($item['no_invoice'])) {
        $statAdaInvoice++;
    } elseif ($item['paid'] !== 'n/a' && $item['invoice'] !== 'n/a') {
        $statBelumInvoice++;
    }
}

// Export handling
if (isset($_GET['export_txt']) && $_GET['export_txt'] == '1' && !empty($groupedData)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="Export_Kegiatan_' . date('Y-m-d_H-i-s') . '.txt"');
    
    $output = "DATA LAPORAN KEGIATAN TEKNISI\r\n";
    $output .= "=====================================================\r\n\r\n";
    
    foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
        $latest_kegiatan = $kegiatan_group[0];
        $teknisi_names = $teknisiMap[$kodeTransaksi] ?? [];
        $teknisi_list = array_map(function($n) { return shortenTechnicianName($n); }, $teknisi_names);
        $teknisi_str = !empty($teknisi_list) ? implode(", ", $teknisi_list) : "N/A";
        
        $output .= "ID Transaksi     : " . $kodeTransaksi . "\r\n";
        $output .= "Nama Customer    : " . $latest_kegiatan['nama_customer'] . "\r\n";
        $output .= "Nomor Telepon    : " . $latest_kegiatan['cust_nomor'] . "\r\n";
        $output .= "Nomor SO         : " . (!empty($latest_kegiatan['no_so']) ? $latest_kegiatan['no_so'] : '-') . "\r\n";
        $output .= "No. Invoice      : " . (!empty($latest_kegiatan['no_invoice']) ? $latest_kegiatan['no_invoice'] : 'Belum Ada') . "\r\n";
        $output .= "Nominal Invoice  : Rp " . number_format($latest_kegiatan['nominal_invoice'] ?? 0, 0, ',', '.') . "\r\n";
        $output .= "Jadwal Kegiatan  : " . date("d/m/Y, H:i", strtotime($latest_kegiatan['jadwal'])) . " WIB\r\n";
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
    <title>Laporan Kegiatan Teknisi</title>
    <?php include "head.php"; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --emerald: #059669;
            --emerald-light: #ecfdf5;
            --indigo: #4338ca;
            --indigo-light: #e0e7ff;
            --amber: #d97706;
            --amber-light: #fffbeb;
            --rose: #e11d48;
            --rose-light: #fff1f2;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-100: #f1f5f9;
            --border-color: #e2e8f0;
        }

        body, .main-content {
            font-family: var(--font-main) !important;
            color: var(--slate-800);
            background-color: #f8fafc;
        }

        /* ═══ BENTO STATS CARDS (Taste-Skill Double-Bezel) ═══ */
        .stats-bento {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .stats-bento { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .stats-bento { grid-template-columns: 1fr; }
        }

        .stat-bezel {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 5px;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.04), 0 2px 6px -2px rgba(15, 23, 42, 0.02);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .stat-bezel:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px -5px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .stat-core {
            border-radius: 15px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }
        .stat-core.primary { background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); }
        .stat-core.emerald { background: linear-gradient(135deg, #f8fafc 0%, #ecfdf5 100%); }
        .stat-core.indigo { background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); }
        .stat-core.rose { background: linear-gradient(135deg, #f8fafc 0%, #fff1f2 100%); }

        .stat-info .stat-label {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 4px;
        }
        .stat-info .stat-num {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            font-family: var(--font-main);
        }
        .stat-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }
        .stat-icon-wrap.primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; }
        .stat-icon-wrap.emerald { background: linear-gradient(135deg, #059669, #10b981); color: #fff; }
        .stat-icon-wrap.indigo { background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; }
        .stat-icon-wrap.rose { background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff; }

        /* ═══ QUICK FILTER TABS ═══ */
        .quick-filter-scroll {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 18px;
            scrollbar-width: none;
        }
        .quick-filter-scroll::-webkit-scrollbar { display: none; }
        
        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 30px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            font-size: 12.5px;
            font-weight: 700;
            color: #475569;
            text-decoration: none !important;
            white-space: nowrap;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .quick-pill:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }
        .quick-pill.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
        }
        .quick-pill .pill-badge {
            font-size: 10.5px;
            padding: 2px 7px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 800;
        }
        .quick-pill.active .pill-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* ═══ MODERN SMART TOOLBAR CARD ═══ */
        .toolbar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px -3px rgba(15, 23, 42, 0.04);
        }
        .search-input-group {
            position: relative;
            flex-grow: 1;
        }
        .search-input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .search-input-group input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .search-input-group input:focus {
            outline: none;
            background: #fff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .select-filter-pill {
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 9px 14px;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .select-filter-pill:focus {
            outline: none;
            background: #fff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none !important;
            white-space: nowrap;
        }
        .btn-modern-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .btn-modern-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
            color: #ffffff;
        }
        .btn-modern-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }
        .btn-modern-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .btn-modern-export {
            background: #ecfdf5;
            color: #059669;
            border: 1.5px solid #a7f3d0;
        }
        .btn-modern-export:hover {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        /* ═══ ADVANCED FILTER COLLAPSIBLE ═══ */
        .advanced-filter-panel {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 18px;
            margin-top: 16px;
        }
        .advanced-filter-panel label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        /* ═══ LUXURY DATA TABLE CARD ═══ */
        .table-bezel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.04), 0 2px 6px -2px rgba(15, 23, 42, 0.02);
            overflow: hidden;
            width: 100%;
        }
        .table-luxury {
            margin: 0;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .table-luxury thead th {
            background: #f8fafc;
            border-bottom: 1.5px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 10.5px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table-luxury tbody tr {
            transition: all 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-luxury tbody tr:last-child {
            border-bottom: none;
        }
        .table-luxury tbody tr:hover {
            background: #f8fafc !important;
        }
        .table-luxury tbody td {
            padding: 9px 12px;
            vertical-align: middle;
            font-size: 12px;
            overflow: hidden;
        }

        /* Column widths - 100% Balanced Grid */
        .col-jadwal { width: 13%; min-width: 125px; }
        .col-customer { width: 33%; min-width: 240px; }
        .col-so { width: 9%; min-width: 85px; }
        .col-invoice { width: 14%; min-width: 120px; }
        .col-teknisi { width: 17%; min-width: 130px; }
        .col-request { width: 8%; min-width: 55px; }
        .col-aksi { width: 6%; min-width: 40px; }

        /* Compact Customer elements */
        .cust-name-link {
            font-weight: 700;
            font-size: 12.5px;
            color: #0f172a;
            text-decoration: none !important;
            transition: color 0.15s;
            display: inline-block;
        }
        .cust-name-link:hover {
            color: #4f46e5 !important;
        }
        .wa-pill-inline {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #047857;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 1px 6px;
            border-radius: 12px;
            text-decoration: none !important;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .wa-pill-inline:hover {
            background: #22c55e;
            color: #ffffff;
            border-color: #22c55e;
        }
        .addr-full {
            color: #475569;
            font-size: 11px;
            line-height: 1.35;
            word-break: break-word;
            white-space: normal;
        }
        .maps-pill-link {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 9.5px;
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1px 5px;
            border-radius: 5px;
            text-decoration: none !important;
            vertical-align: middle;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .maps-pill-link:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        /* Type Badges */
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 9.5px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .badge-type.pasang-baru {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-type.service {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-type.survey {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-type.default {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Monospace ID Tag */
        .id-mono-tag {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 1px 5px;
            border-radius: 5px;
            display: inline-block;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        /* SO Badge */
        .so-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 2px 6px;
            border-radius: 5px;
            letter-spacing: 0.02em;
            font-family: var(--font-mono);
            word-break: break-all;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        /* Invoice styling */
        .inv-box .inv-num {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1px;
            word-break: break-all;
        }
        .inv-box .inv-val {
            font-size: 11.5px;
            font-weight: 800;
            color: #059669;
            font-family: var(--font-main);
        }
        .inv-badge-none {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: #e11d48;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            padding: 2px 6px;
            border-radius: 5px;
            white-space: nowrap;
        }
        .pulse-badge {
            font-size: 10.5px;
            color: #ffffff;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
            animation: pulse-animation 1.5s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); transform: scale(1); }
            50% { transform: scale(1.04); }
            100% { box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); transform: scale(1); }
        }

        /* Technician Chips */
        .tek-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .tek-chip-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 3px 9px;
            border-radius: 8px;
            white-space: nowrap;
        }
        .tek-chip-modern .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
        }

        /* User Request Initials Badge */
        .req-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0f172a, #334155);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        /* View Button */
        .btn-action-view {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        .btn-action-view:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        /* Empty State */
        .empty-luxury {
            padding: 70px 20px;
            text-align: center;
        }
        .empty-luxury .empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
        }

        .lunas-background { position: relative; }
        .lunas-background::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('assets/img/lunas.png');
            background-size: contain;
            background-position: right center;
            background-repeat: no-repeat;
            opacity: 0.12;
            pointer-events: none;
        }
        <?php include "css/floating-menu2.css";?>
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">

            <!-- ═══ TOP HEADER & LIVE STATS BENTO CARDS ═══ -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="mb-1 fw-bold text-dark" style="letter-spacing: -0.02em;">Laporan Kegiatan Teknisi</h4>
                    <p class="text-secondary text-xs mb-0 fw-medium">Live monitoring jadwal, teknisi lapangan, nomor SO, dan invoice real-time.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-bold text-xs shadow-sm">
                        <i class="fa-solid fa-signal text-success me-1"></i> <?= $statTotal ?> Kegiatan Terkini
                    </span>
                </div>
            </div>

            <!-- Bento Stats -->
            <div class="stats-bento">
                <div class="stat-bezel">
                    <div class="stat-core primary">
                        <div class="stat-info">
                            <div class="stat-label">Total Task</div>
                            <div class="stat-num"><?= $statTotal ?></div>
                        </div>
                        <div class="stat-icon-wrap primary"><i class="fa-solid fa-list-check"></i></div>
                    </div>
                </div>
                <div class="stat-bezel">
                    <div class="stat-core emerald">
                        <div class="stat-info">
                            <div class="stat-label">Pasang Baru</div>
                            <div class="stat-num"><?= $statPasangBaru ?></div>
                        </div>
                        <div class="stat-icon-wrap emerald"><i class="fa-solid fa-bolt"></i></div>
                    </div>
                </div>
                <div class="stat-bezel">
                    <div class="stat-core indigo">
                        <div class="stat-info">
                            <div class="stat-label">Service &amp; Survey</div>
                            <div class="stat-num"><?= ($statService + $statSurvey) ?></div>
                        </div>
                        <div class="stat-icon-wrap indigo"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    </div>
                </div>
                <div class="stat-bezel">
                    <div class="stat-core rose">
                        <div class="stat-info">
                            <div class="stat-label">Perlu Invoice</div>
                            <div class="stat-num"><?= $statBelumInvoice ?></div>
                        </div>
                        <div class="stat-icon-wrap rose"><i class="fa-solid fa-receipt"></i></div>
                    </div>
                </div>
            </div>

            <!-- ═══ QUICK FILTER TABS ═══ -->
            <div class="quick-filter-scroll">
                <a href="task.php" class="quick-pill <?= (empty($jenis_kegiatan) && empty($status_invoice) && empty($quick_search)) ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> Semua Kegiatan
                    <span class="pill-badge"><?= $statTotal ?></span>
                </a>
                <a href="task.php?jenis_kegiatan=pasang+baru" class="quick-pill <?= $jenis_kegiatan === 'pasang baru' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bolt" style="color:#10b981;"></i> Pasang Baru
                    <span class="pill-badge"><?= $statPasangBaru ?></span>
                </a>
                <a href="task.php?jenis_kegiatan=service" class="quick-pill <?= $jenis_kegiatan === 'service' ? 'active' : '' ?>">
                    <i class="fa-solid fa-wrench" style="color:#3b82f6;"></i> Service
                    <span class="pill-badge"><?= $statService ?></span>
                </a>
                <a href="task.php?jenis_kegiatan=survey" class="quick-pill <?= $jenis_kegiatan === 'survey' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clipboard-list" style="color:#f59e0b;"></i> Survey
                    <span class="pill-badge"><?= $statSurvey ?></span>
                </a>
                <a href="task.php?status_invoice=belum_input" class="quick-pill <?= $status_invoice === 'belum_input' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bell" style="color:#f43f5e;"></i> Perlu Invoice
                    <span class="pill-badge"><?= $statBelumInvoice ?></span>
                </a>
                <a href="task.php?status_invoice=ada_invoice" class="quick-pill <?= $status_invoice === 'ada_invoice' ? 'active' : '' ?>">
                    <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Ada Invoice
                    <span class="pill-badge"><?= $statAdaInvoice ?></span>
                </a>
            </div>

            <!-- ═══ MODERN SMART TOOLBAR CARD ═══ -->
            <div class="toolbar-card">
                <form method="GET" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Universal Quick Search Input -->
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="quick_search" placeholder="Cari nama, alamat, nomor HP, SO, invoice, atau ID..." value="<?= htmlspecialchars($quick_search) ?>">
                        </div>

                        <!-- Quick Teknisi Dropdown -->
                        <select name="teknisi_id" class="select-filter-pill">
                            <option value="">Semua Teknisi</option>
                            <?php 
                            mysqli_data_seek($result_all_teknisi, 0); 
                            while ($tek = mysqli_fetch_assoc($result_all_teknisi)) { 
                                $sel = ($teknisi_id == $tek['id']) ? 'selected' : '';
                                echo "<option value='{$tek['id']}' {$sel}>" . htmlspecialchars($tek['nama']) . "</option>"; 
                            } 
                            ?>
                        </select>

                        <!-- Quick Jenis Dropdown -->
                        <select name="jenis_kegiatan" class="select-filter-pill">
                            <option value="">Semua Jenis</option>
                            <option value="survey" <?= ($jenis_kegiatan == 'survey' ? ' selected' : '') ?>>Survey</option>
                            <option value="service" <?= ($jenis_kegiatan == 'service' ? ' selected' : '') ?>>Service</option>
                            <option value="pasang baru" <?= ($jenis_kegiatan == 'pasang baru' ? ' selected' : '') ?>>Pasang Baru</option>
                        </select>

                        <!-- Toggle Advanced Filter Button -->
                        <button type="button" class="btn-modern btn-modern-secondary" id="btnToggleAdvanced">
                            <i class="fa-solid fa-sliders"></i> Filter Lanjutan
                        </button>

                        <!-- Search Button -->
                        <button type="submit" class="btn-modern btn-modern-primary">
                            <i class="fa-solid fa-magnifying-glass"></i> Cari
                        </button>

                        <!-- Reset Button -->
                        <?php if ($is_search_triggered): ?>
                        <a href="task.php" class="btn-modern btn-modern-secondary" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>

                        <!-- Export Button -->
                        <button type="submit" name="export_txt" value="1" class="btn-modern btn-modern-export">
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                    </div>

                    <!-- Collapsible Advanced Filter Panel -->
                    <div class="advanced-filter-panel <?= (!empty($start_date) || !empty($end_date) || !empty($kode_transaksi_filter) || !empty($no_so_filter) || !empty($alamat_filter) || !empty($nama_customer_display) || !empty($status_invoice)) ? '' : 'd-none' ?>" id="advancedPanel">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 position-relative">
                                <label>Nama Customer</label>
                                <input type="text" id="customerSearchInput" name="nama_customer_display" class="form-control form-control-sm rounded-3" placeholder="Ketik nama customer..." autocomplete="off" value="<?= htmlspecialchars($_GET['nama_customer_display'] ?? '') ?>">
                                <input type="hidden" id="customerIdInput" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
                                <div id="searchResults" class="list-group position-absolute w-100 shadow-lg" style="z-index: 1050;"></div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>Alamat / Wilayah</label>
                                <input type="text" name="alamat_filter" class="form-control form-control-sm rounded-3" placeholder="Contoh: PIK, Serang, Cikupa..." value="<?= htmlspecialchars($alamat_filter) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>Nomor SO</label>
                                <input type="text" name="no_so_filter" class="form-control form-control-sm rounded-3" placeholder="Contoh: 2608.SOL.06406" value="<?= htmlspecialchars($no_so_filter) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>ID Transaksi</label>
                                <input type="text" name="kode_transaksi_filter" class="form-control form-control-sm rounded-3" placeholder="Contoh: YAK7NU" value="<?= htmlspecialchars($kode_transaksi_filter) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>Dari Tanggal</label>
                                <input type="date" class="form-control form-control-sm rounded-3" name="start_date" id="startDate" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>Sampai Tanggal</label>
                                <input type="date" class="form-control form-control-sm rounded-3" name="end_date" id="endDate" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label>Status Invoice</label>
                                <select class="form-control form-control-sm rounded-3" name="status_invoice">
                                    <option value="">Semua Status</option>
                                    <option value="ada_invoice" <?= ($status_invoice == 'ada_invoice' ? ' selected' : '') ?>>Ada Invoice</option>
                                    <option value="belum_input" <?= ($status_invoice == 'belum_input' ? ' selected' : '') ?>>Belum Input Invoice</option>
                                    <option value="no_pay" <?= ($status_invoice == 'no_pay' ? ' selected' : '') ?>>No Pay</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ═══ LUXURY DATA TABLE ═══ -->
            <div class="table-bezel">
                <div class="table-responsive">
                    <table class="table table-luxury align-middle">
                        <thead>
                            <tr>
                                <th class="col-jadwal" style="padding-left: 18px;">Jadwal &amp; Jenis</th>
                                <th class="col-customer">Customer &amp; Alamat</th>
                                <th class="col-so">No. SO</th>
                                <th class="col-invoice">Invoice</th>
                                <th class="col-teknisi">Teknisi</th>
                                <th class="col-request text-center">Request</th>
                                <th class="col-aksi text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($groupedData)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-luxury">
                                        <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                        <h6 class="fw-bold text-dark mb-1">Tidak Ada Kegiatan Ditemukan</h6>
                                        <p class="text-secondary text-xs mb-3">Coba ubah kata kunci pencarian atau reset filter untuk melihat data lainnya.</p>
                                        <a href="task.php" class="btn-modern btn-modern-primary btn-sm">Reset Filter</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            foreach ($groupedData as $kodeTransaksi => $kegiatan_group):
                                $latest_kegiatan = $kegiatan_group[0];
                                $lunas_class = (!empty($latest_kegiatan['lunas']) && $latest_kegiatan['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                                
                                $kegL = strtolower($latest_kegiatan['kegiatan']);
                                $typeClass = 'default';
                                $typeIcon = 'fa-tag';
                                if (strpos($kegL, 'survey') !== false) {
                                    $typeClass = 'survey'; $typeIcon = 'fa-clipboard-list';
                                } elseif (strpos($kegL, 'service') !== false) {
                                    $typeClass = 'service'; $typeIcon = 'fa-wrench';
                                } elseif (strpos($kegL, 'pasang') !== false) {
                                    $typeClass = 'pasang-baru'; $typeIcon = 'fa-bolt';
                                }
                            ?>
                            <tr>
                                <!-- Jadwal & Jenis -->
                                <td class="col-jadwal" style="padding-left: 14px;">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <span class="badge-type <?= $typeClass ?>">
                                            <i class="fa-solid <?= $typeIcon ?>" style="font-size: 8.5px;"></i>
                                            <?= htmlspecialchars($latest_kegiatan['kegiatan']) ?>
                                        </span>
                                        <span class="id-mono-tag">#<?= htmlspecialchars($kodeTransaksi) ?></span>
                                    </div>
                                    <div class="fw-bold text-dark text-xxs" style="white-space: nowrap;">
                                        <i class="fa-regular fa-calendar text-muted me-1"></i><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])) ?> · <?= date("H:i", strtotime($latest_kegiatan['jadwal'])) ?>
                                    </div>
                                </td>

                                <!-- Customer & Alamat -->
                                <td class="col-customer">
                                    <div style="min-width: 0;">
                                        <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                            <a href="customer-detail.php?id_cust=<?= $latest_kegiatan['customer_id'] ?>" target="_blank" class="cust-name-link" title="<?= htmlspecialchars($latest_kegiatan['nama_customer']) ?>">
                                                <?= htmlspecialchars($latest_kegiatan['nama_customer']) ?>
                                            </a>
                                            <?php
                                                $nomorHandphone = $latest_kegiatan['cust_nomor'];
                                                if (!empty($nomorHandphone)) {
                                                    if (substr($nomorHandphone, 0, 1) === '0') $nomorHandphone = '62' . substr($nomorHandphone, 1);
                                            ?>
                                            <a href="https://api.whatsapp.com/send?phone=<?= $nomorHandphone ?>" target="_blank" class="wa-pill-inline" title="WhatsApp: <?= htmlspecialchars($latest_kegiatan['cust_nomor']) ?>">
                                                <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($latest_kegiatan['cust_nomor']) ?>
                                            </a>
                                            <?php } ?>
                                        </div>
                                        <div class="addr-full">
                                            <i class="fa-solid fa-location-dot text-muted me-1" style="font-size: 9px;"></i><?= formatAlamatWithMaps($latest_kegiatan['alamat']) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- No. SO -->
                                <td class="col-so">
                                    <?php if (!empty($latest_kegiatan['no_so'])) : ?>
                                        <div class="so-badge" title="<?= htmlspecialchars($latest_kegiatan['no_so']) ?>">
                                            <i class="fa-solid fa-receipt text-success" style="font-size: 10px;"></i>
                                            <?= htmlspecialchars($latest_kegiatan['no_so']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fw-semibold text-xs">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Invoice -->
                                <td class="col-invoice <?= $lunas_class ?>">
                                    <div class="inv-box" style="min-width: 0;">
                                        <?php if (!empty($latest_kegiatan['no_invoice'])) : ?>
                                            <div class="inv-num fw-bold text-truncate" title="<?= htmlspecialchars($latest_kegiatan['no_invoice']) ?>"><?= htmlspecialchars($latest_kegiatan['no_invoice']) ?></div>
                                            <div class="inv-val">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.') ?></div>
                                        <?php elseif ($latest_kegiatan['paid'] === 'n/a' || $latest_kegiatan['invoice'] === 'n/a') : ?>
                                            <span class="badge bg-light text-secondary border fw-bold text-xxs">No Pay</span>
                                        <?php else: ?>
                                            <span class="inv-badge-none">Belum Input Invoice</span>
                                            <?php if (!empty($latest_kegiatan['req_invoice_at']) && (strtolower($latest_kegiatan['status']) == 'selesai' || strtolower($latest_kegiatan['status']) == 'selesai by admin')) : ?>
                                                <div class="mt-1">
                                                    <span class="pulse-badge"><i class="fa-solid fa-bell"></i> Minta Invoice</span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Teknisi -->
                                <td class="col-teknisi">
                                    <div class="tek-chips-wrap">
                                    <?php
                                    $tekNames = $teknisiMap[$kodeTransaksi] ?? [];
                                    if (!empty($tekNames)) {
                                        foreach ($tekNames as $tName) {
                                            echo "<span class='tek-chip-modern' title='" . htmlspecialchars($tName) . "'><span class='dot'></span><span class='text-truncate' style='max-width:130px;'>" . shortenTechnicianName(htmlspecialchars($tName)) . "</span></span>";
                                        }
                                    } else {
                                        echo "<span class='text-muted text-xxs'>-</span>";
                                    }
                                    ?>
                                    </div>
                                </td>

                                <!-- Request -->
                                <td class="col-request text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div class="req-avatar" title="Requester: <?= htmlspecialchars($latest_kegiatan['request'] ?? '-') ?>">
                                            <?= getInitials($latest_kegiatan['request'] ?? '') ?>
                                        </div>
                                        <span class="text-muted text-xxs fw-medium"><?= date("d/m/y", strtotime($latest_kegiatan['created_at'])) ?></span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="col-aksi text-center pe-3">
                                    <a class="btn-action-view" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi ?>" title="Lihat Detail Kegiatan">
                                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px;"></i>
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
        // Toggle advanced filters
        document.getElementById('btnToggleAdvanced').addEventListener('click', function() {
            const panel = document.getElementById('advancedPanel');
            panel.classList.toggle('d-none');
        });

        // Autocomplete customer search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('customerSearchInput');
            const customerIdInput = document.getElementById('customerIdInput');
            const resultsContainer = document.getElementById('searchResults');
            let debounceTimer;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    customerIdInput.value = '';
                });

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
                                        item.classList.add('list-group-item', 'list-group-item-action', 'text-xs', 'fw-bold', 'py-2');
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
                                    resultsContainer.innerHTML = '<span class="list-group-item text-xs text-muted py-2">Customer tidak ditemukan.</span>';
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
            }
        });
    </script>
</body>
</html>