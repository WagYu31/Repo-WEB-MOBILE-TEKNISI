<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today"; // Anda bisa sesuaikan ini
$role = $jabatan;

// Notifikasi (jika ada)
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $message = 'Terjadi kesalahan tidak diketahui.';
    if ($error_code == 1) $message = 'Gagal memproses data. Silakan coba lagi.';
    elseif ($error_code == 2) $message = 'Gagal. Data yang diperlukan tidak lengkap.';
    elseif ($error_code == 3) $message = 'Permintaan tidak valid. Silakan coba lagi.';
    echo "<script>alert('$message');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .table th, .table td { vertical-align: middle !important; }
        .table .customer-info h6 { font-size: 0.9rem; color: #1e293b; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; border-radius: 10px 10px 0 0 !important; }
        .card-header h5 { font-size: 0.95rem; color: #1e293b; letter-spacing: 0.02em; }
        thead th { background: #f8fafc !important; color: #64748b !important; font-size: 10.5px !important; letter-spacing: 0.06em; padding: 10px 16px !important; border-bottom: 2px solid #e2e8f0 !important; }
        tbody tr { transition: background 0.15s; border-bottom: 1px solid #f1f5f9 !important; }
        tbody tr:hover { background: #f8fafc !important; }
        .search-box { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 14px; font-size: 13px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
        .search-box:focus { border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.1); outline: none; }
        .btn-search { background: #1e293b; border: none; border-radius: 8px; padding: 10px 16px; }
        .btn-search:hover { background: #334155; }
        .btn-detail { font-size: 11px; padding: 6px 14px; border-radius: 6px; font-weight: 600; background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-detail:hover { background: #1e293b; color: #fff; border-color: #1e293b; }
        .status-warning { font-size: 11px; color: #b45309; background: #fef3c7; padding: 4px 10px; border-radius: 4px; display: inline-block; }
        <?php include "css/floating-menu2.css"; ?>
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        setlocale(LC_TIME, 'id_ID.utf8');
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                    <?php include 'nav-laporan.php'; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3 px-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Kegiatan Tidak Dikerjakan</h5>
                                <form method="GET" action="" class="d-flex gap-2" style="max-width:380px;width:100%;">
                                    <input type="text" name="cari" class="search-box flex-grow-1" placeholder="Cari nama customer..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                    <button class="btn btn-search mb-0 text-white" type="submit"><i class="material-icons" style="font-size:16px;vertical-align:middle;">search</i></button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 40%;">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 45%;">Teknisi Ditugaskan</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4" style="width: 15%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query Utama untuk mengambil kegiatan TANPA pelaksanaan atau yang di-set N/A
                                        $search = $_GET['cari'] ?? '';
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
                                                      AND (p.kode IS NULL OR k.paid = 'n/a')"; // <-- INI BAGIAN YANG DIPERBARUI
                                        
                                        if (!empty($search)) {
                                            $sql_main .= " AND c.nama LIKE ?";
                                        }
                                        $sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";
                                        
                                        $stmt_main = $conn->prepare($sql_main);
                                        if (!empty($search)) {
                                            $search_param = "%$search%";
                                            $stmt_main->bind_param("s", $search_param);
                                        }
                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();

                                        if ($result_main->num_rows > 0) {
                                            while ($row_main = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                        ?>
                                                <tr>
                                                    <td class="ps-4 customer-info text-wrap">
                                                        <h6 class="font-weight-bold mb-1" style="font-size:0.9rem;color:#1e293b;"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                        <p class="mb-1" style="font-size:12px;color:#64748b;font-style:italic;">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="mb-0" style="font-size:11px;color:#94a3b8;">Request dibuat: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
                                                    </td>

                                                    <td>
                                                        <p class="mb-1" style="font-size:13px;font-weight:600;color:#1e293b;">
                                                            <?= !empty($row_main['teknisi_list']) ? htmlspecialchars($row_main['teknisi_list']) : '<span style="color:#94a3b8;">Belum ada teknisi</span>'; ?>
                                                        </p>
                                                        <span class="status-warning">
                                                            <i class="material-icons" style="font-size:12px;vertical-align:middle;margin-right:2px;">warning_amber</i>
                                                            Data pelaksanaan tidak ditemukan / tidak lengkap
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="text-center pe-4">
                                                        <a class="btn-detail" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                            <i class="material-icons" style="font-size:13px;vertical-align:middle;margin-right:3px;">visibility</i>
                                                            Lihat Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center py-5'>Tidak ada data kegiatan yang belum dikerjakan.</td></tr>";
                                        }
                                        $stmt_main->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>
    
    <?php include "js-include.php"; ?>
</body>
</html>