<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today";
$role = $jabatan;

// Notifikasi
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $message = 'Terjadi kesalahan tidak diketahui.';
    if ($error_code == 1) $message = 'Gagal memproses data. Silakan coba lagi.';
    elseif ($error_code == 2) $message = 'Gagal. Data yang diperlukan tidak lengkap.';
    elseif ($error_code == 3) $message = 'Permintaan tidak valid. Silakan coba lagi.';
    echo "<script>alert('$message');</script>";
}

// Filter
$search = $_GET['cari'] ?? '';
$filterMonth = $_GET['bulan'] ?? '';

// Main query
$sql_main = "SELECT 
                k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, 
                c.id AS id_cust, c.nama AS nama_cust,
                GROUP_CONCAT(DISTINCT t.nama_teknisi SEPARATOR ', ') as teknisi_list
            FROM kegiatan k
            LEFT JOIN customer c ON k.customer_id = c.id
            LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
            LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
            WHERE k.status != 'waiting' 
              AND k.deleted_at IS NULL 
              AND (
                  p.kode IS NULL 
                  OR (k.paid = 'n/a' AND NOT EXISTS (
                      SELECT 1 FROM pelaksanaan_kegiatan px 
                      WHERE px.kode = k.kode AND px.deleted_at IS NULL 
                      AND px.waktu_mulai IS NOT NULL AND px.waktu_selesai IS NOT NULL
                  ))
              )";

$params = [];
$types = '';

if (!empty($search)) {
    $sql_main .= " AND c.nama LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if (!empty($filterMonth)) {
    $sql_main .= " AND DATE_FORMAT(k.created_at, '%Y-%m') = ?";
    $params[] = $filterMonth;
    $types .= 's';
}
$sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";

$stmt_main = $conn->prepare($sql_main);
if (!empty($types)) {
    $stmt_main->bind_param($types, ...$params);
}
$stmt_main->execute();
$result_main = $stmt_main->get_result();

$allRows = [];
while ($row = $result_main->fetch_assoc()) { $allRows[] = $row; }
$totalLoss = count($allRows);
$stmt_main->close();

// Chart data: monthly trend (last 6 months)
$chartData = [];
$sql_chart = "SELECT 
    DATE_FORMAT(k.created_at, '%Y-%m') AS period,
    COUNT(DISTINCT k.kode) AS total
FROM kegiatan k
LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
WHERE k.status != 'waiting' 
  AND k.deleted_at IS NULL 
  AND (
      p.kode IS NULL 
      OR (k.paid = 'n/a' AND NOT EXISTS (
          SELECT 1 FROM pelaksanaan_kegiatan px 
          WHERE px.kode = k.kode AND px.deleted_at IS NULL 
          AND px.waktu_mulai IS NOT NULL AND px.waktu_selesai IS NOT NULL
      ))
  )
  AND k.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY period
ORDER BY period ASC";
$res_chart = $conn->query($sql_chart);
$chartLabels = [];
$chartValues = [];
if ($res_chart) {
    while ($cr = $res_chart->fetch_assoc()) {
        $dt = DateTime::createFromFormat('Y-m', $cr['period']);
        $chartLabels[] = $dt ? $dt->format('M Y') : $cr['period'];
        $chartValues[] = (int)$cr['total'];
    }
}

// Stats: top teknisi with most losses
$sql_top = "SELECT t.nama_teknisi, COUNT(DISTINCT k.kode) AS total
FROM kegiatan k
LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
WHERE k.status != 'waiting' AND k.deleted_at IS NULL 
AND (
    p.kode IS NULL 
    OR (k.paid = 'n/a' AND NOT EXISTS (
        SELECT 1 FROM pelaksanaan_kegiatan px 
        WHERE px.kode = k.kode AND px.deleted_at IS NULL 
        AND px.waktu_mulai IS NOT NULL AND px.waktu_selesai IS NOT NULL
    ))
)
AND t.nama_teknisi IS NOT NULL
GROUP BY t.nama_teknisi ORDER BY total DESC LIMIT 5";
$res_top = $conn->query($sql_top);
$topTeknisi = [];
if ($res_top) { while ($r = $res_top->fetch_assoc()) { $topTeknisi[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ═══ PREMIUM LAP LOSS ═══ */
        .loss-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px; margin-bottom: 20px;
        }
        .loss-title-left { display: flex; align-items: center; gap: 14px; }
        .loss-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(239,68,68,0.3);
        }
        .loss-icon i { color: #fff; font-size: 18px; }
        .loss-title-left h4 { margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; }
        .loss-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }

        /* Stats row */
        .loss-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .loss-stat-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .loss-stat-card.s-total { border-left: 4px solid #ef4444; }
        .loss-stat-card.s-month { border-left: 4px solid #f59e0b; }
        .loss-stat-card.s-top { border-left: 4px solid #6366f1; }
        .loss-stat-num { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1; }
        .loss-stat-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 4px; }

        /* Filter */
        .loss-filter-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 16px 20px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .loss-filter-label { font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; display: flex; align-items: center; gap: 6px; }
        .loss-input {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px;
            font-size: 13px; font-weight: 600; color: #1e293b; background: #f8fafc;
            transition: all 0.2s;
        }
        .loss-input:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.08); outline: none; }
        .loss-search { width: 240px; padding-left: 34px; }
        .loss-search-wrap { position: relative; }
        .loss-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; }
        .loss-filter-btn {
            padding: 9px 20px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(239,68,68,0.2); transition: all 0.2s;
        }
        .loss-filter-btn:hover { transform: translateY(-1px); }
        .loss-reset-btn {
            padding: 9px 16px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            background: #fff; color: #64748b; font-size: 12px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .loss-reset-btn:hover { background: #f1f5f9; color: #1e293b; }

        /* Charts row */
        .loss-charts { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px; }
        .loss-chart-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .loss-chart-header { display: flex; align-items: center; gap: 12px; padding: 18px 20px 0; }
        .loss-chart-hicon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .loss-chart-hicon.h-red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .loss-chart-hicon.h-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .loss-chart-hicon i { color: #fff; font-size: 13px; }
        .loss-chart-header h6 { margin: 0; font-size: 13px; font-weight: 800; color: #1e293b; }
        .loss-chart-body { padding: 14px 18px 18px; }
        .loss-top-list { list-style: none; padding: 0; margin: 0; }
        .loss-top-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #f1f5f9;
        }
        .loss-top-item:last-child { border-bottom: none; }
        .loss-top-rank {
            width: 24px; height: 24px; border-radius: 6px; font-size: 10px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            background: #f1f5f9; color: #64748b; margin-right: 10px; flex-shrink: 0;
        }
        .loss-top-rank.r1 { background: #fef3c7; color: #92400e; }
        .loss-top-name { font-size: 13px; font-weight: 600; color: #1e293b; flex: 1; }
        .loss-top-count {
            font-size: 12px; font-weight: 800; color: #ef4444;
            background: #fef2f2; padding: 3px 10px; border-radius: 20px;
        }

        /* Table card */
        .loss-table-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden; display: flex; flex-direction: column;
            max-height: calc(100vh - 200px);
        }
        .loss-table-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 24px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
        }
        .loss-table-left { display: flex; align-items: center; gap: 10px; }
        .loss-table-hicon {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(239,68,68,0.15);
        }
        .loss-table-hicon i { color: #fff; font-size: 13px; }
        .loss-table-left h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .loss-count-badge {
            font-size: 11px; font-weight: 700; color: #ef4444; background: #fef2f2;
            padding: 4px 12px; border-radius: 20px; border: 1px solid #fecaca;
        }

        .loss-table-scroll { flex: 1; overflow-y: auto; overflow-x: auto; }
        .loss-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 700px; }
        .loss-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .loss-table tbody tr { transition: background 0.15s; }
        .loss-table tbody tr:hover { background: #fafbfc; }
        .loss-table tbody td { padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: top; border-bottom: 1px solid #f1f5f9; }

        .loss-cust-name { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .loss-cust-desc { font-size: 12px; color: #94a3b8; font-style: italic; margin-bottom: 2px; }
        .loss-cust-date { font-size: 11px; color: #cbd5e1; }

        .loss-tek-name { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .loss-warning-pill {
            font-size: 10px; font-weight: 700; color: #92400e; background: #fef3c7;
            padding: 3px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;
        }

        .loss-detail-btn {
            padding: 6px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
            font-size: 11px; font-weight: 700; color: #475569; background: #fff;
            text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
            transition: all 0.15s;
        }
        .loss-detail-btn:hover { background: #1e293b; color: #fff; border-color: #1e293b; }

        @media (max-width: 992px) {
            .loss-charts { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .loss-header { flex-direction: column; align-items: flex-start; }
            .loss-filter-card { flex-direction: column; }
            .loss-search { width: 100%; }
            .loss-table-card { max-height: none; }
        }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; setlocale(LC_TIME, 'id_ID.utf8'); ?>
        <div class="container-fluid py-4">
            <div class="row">
                <?php include 'nav-laporan.php'; ?>
            </div>

            <!-- Header -->
            <div class="loss-header" style="margin-top:16px;">
                <div class="loss-title-left">
                    <div class="loss-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h4>Kegiatan Tidak Dikerjakan</h4>
                        <p>Laporan kegiatan tanpa pelaksanaan / data tidak lengkap</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="loss-stats">
                <div class="loss-stat-card s-total">
                    <div class="loss-stat-num"><?= $totalLoss ?></div>
                    <div class="loss-stat-label">Total Loss<?= !empty($filterMonth) ? ' (filtered)' : '' ?></div>
                </div>
                <div class="loss-stat-card s-month">
                    <div class="loss-stat-num"><?= !empty($chartValues) ? end($chartValues) : 0 ?></div>
                    <div class="loss-stat-label">Bulan Ini</div>
                </div>
                <div class="loss-stat-card s-top">
                    <div class="loss-stat-num"><?= !empty($topTeknisi) ? $topTeknisi[0]['total'] : 0 ?></div>
                    <div class="loss-stat-label">Loss Tertinggi <?= !empty($topTeknisi) ? '(' . $topTeknisi[0]['nama_teknisi'] . ')' : '' ?></div>
                </div>
            </div>

            <!-- Filter -->
            <form method="GET" action="" class="loss-filter-card">
                <div class="loss-filter-label"><i class="fa-solid fa-filter"></i> Filter</div>
                <input type="month" name="bulan" class="loss-input" value="<?= htmlspecialchars($filterMonth) ?>" placeholder="Pilih bulan">
                <div class="loss-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="cari" class="loss-input loss-search" placeholder="Cari nama customer..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="loss-filter-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Terapkan
                </button>
                <?php if (!empty($search) || !empty($filterMonth)): ?>
                    <a href="lap-loss.php" class="loss-reset-btn"><i class="fa-solid fa-xmark"></i> Reset</a>
                <?php endif; ?>
            </form>

            <!-- Charts -->
            <div class="loss-charts">
                <!-- Line Chart: Monthly Trend -->
                <div class="loss-chart-card">
                    <div class="loss-chart-header">
                        <div class="loss-chart-hicon h-red"><i class="fa-solid fa-chart-line"></i></div>
                        <h6>Tren Bulanan (6 Bulan Terakhir)</h6>
                    </div>
                    <div class="loss-chart-body"><div style="height:220px;"><canvas id="lossChart"></canvas></div></div>
                </div>

                <!-- Top Teknisi -->
                <div class="loss-chart-card">
                    <div class="loss-chart-header">
                        <div class="loss-chart-hicon h-purple"><i class="fa-solid fa-ranking-star"></i></div>
                        <h6>Top Teknisi Loss</h6>
                    </div>
                    <div class="loss-chart-body" style="padding-top:10px;">
                        <?php if (!empty($topTeknisi)): ?>
                        <ul class="loss-top-list">
                            <?php foreach ($topTeknisi as $i => $tek): ?>
                            <li class="loss-top-item">
                                <span class="loss-top-rank <?= $i === 0 ? 'r1' : '' ?>"><?= $i + 1 ?></span>
                                <span class="loss-top-name"><?= htmlspecialchars($tek['nama_teknisi']) ?></span>
                                <span class="loss-top-count"><?= $tek['total'] ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                            Tidak ada data
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="loss-table-card">
                <div class="loss-table-header">
                    <div class="loss-table-left">
                        <div class="loss-table-hicon"><i class="fa-solid fa-list"></i></div>
                        <h6>Daftar Kegiatan</h6>
                    </div>
                    <span class="loss-count-badge"><?= $totalLoss ?> kegiatan</span>
                </div>
                <div class="loss-table-scroll">
                    <table class="loss-table">
                        <thead>
                            <tr>
                                <th style="padding-left:24px; width:40%;">Customer</th>
                                <th style="width:40%;">Teknisi Ditugaskan</th>
                                <th style="text-align:center; width:20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalLoss > 0): ?>
                                <?php foreach ($allRows as $row_main): ?>
                                <tr>
                                    <td style="padding-left:24px;">
                                        <div class="loss-cust-name"><?= htmlspecialchars($row_main['nama_cust']); ?></div>
                                        <div class="loss-cust-desc">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</div>
                                        <div class="loss-cust-date">Request: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="loss-tek-name">
                                            <?= !empty($row_main['teknisi_list']) ? htmlspecialchars($row_main['teknisi_list']) : '<span style="color:#cbd5e1;">Belum ada teknisi</span>'; ?>
                                        </div>
                                        <span class="loss-warning-pill">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Pelaksanaan tidak lengkap
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a class="loss-detail-btn" href="view-kegiatan.php?kode_transaksi=<?= $row_main['kode_transaksi']; ?>" target="_blank">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding:60px 20px; color:#94a3b8;">
                                        <i class="fa-solid fa-circle-check" style="font-size:36px; display:block; margin-bottom:12px; color:#22c55e;"></i>
                                        Tidak ada kegiatan yang belum dikerjakan. 🎉
                                    </td>
                                </tr>
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
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('lossChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, 'rgba(239,68,68,0.2)');
            gradient.addColorStop(1, 'rgba(239,68,68,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [{
                        label: 'Kegiatan Tidak Dikerjakan',
                        data: <?= json_encode($chartValues) ?>,
                        borderColor: '#ef4444',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            cornerRadius: 8,
                            titleFont: { size: 11, weight: '700' },
                            bodyFont: { size: 12, weight: '600' },
                            padding: 10,
                            callbacks: {
                                label: function(ctx) { return ctx.parsed.y + ' kegiatan'; }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10, weight: '600' }, stepSize: 1 } },
                        x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } }
                    }
                }
            });
        });
    </script>
</body>
</html>