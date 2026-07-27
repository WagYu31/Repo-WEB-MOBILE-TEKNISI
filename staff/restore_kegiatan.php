<?php
include "conn.php";
include "session.php";
$pageNow = "Restore Kegiatan";
include "get-user-data.php";

$message = '';
$messageType = '';

// Process restore request
if (isset($_GET['action']) && $_GET['action'] === 'restore' && !empty($_GET['kode'])) {
    $kodeRestore = trim($_GET['kode']);
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    $user_display = (!empty($nmUser)) ? $nmUser : "System/Admin";

    // 1. Restore kegiatan
    $stmt1 = $conn->prepare("UPDATE kegiatan SET deleted_at = NULL WHERE kode = ?");
    $stmt1->bind_param("s", $kodeRestore);
    $stmt1->execute();
    $affected = $stmt1->affected_rows;
    $stmt1->close();

    // 2. Restore pelaksanaan_kegiatan
    $stmt2 = $conn->prepare("UPDATE pelaksanaan_kegiatan SET deleted_at = NULL WHERE kode = ?");
    $stmt2->bind_param("s", $kodeRestore);
    $stmt2->execute();
    $stmt2->close();

    // 3. Restore pendapatan_kegiatan
    $stmt3 = $conn->prepare("UPDATE pendapatan_kegiatan SET deleted_at = NULL WHERE kode = ?");
    $stmt3->bind_param("s", $kodeRestore);
    $stmt3->execute();
    $stmt3->close();

    if ($affected > 0) {
        // Log restore
        $jenis_aksi = "Restore";
        $sql_log = "INSERT INTO log_kegiatan (jenis_aksi, nama_user, waktu, kode_transaksi) VALUES (?, ?, ?, ?)";
        if ($stmt_log = $conn->prepare($sql_log)) {
            $stmt_log->bind_param("ssss", $jenis_aksi, $user_display, $now, $kodeRestore);
            $stmt_log->execute();
            $stmt_log->close();
        }

        $message = "Kegiatan <strong>" . htmlspecialchars($kodeRestore) . "</strong> berhasil dipulihkan!";
        $messageType = "success";
    } else {
        $message = "Gagal mempulihkan kegiatan atau data tidak ditemukan.";
        $messageType = "danger";
    }
}

// Fetch soft-deleted kegiatan along with user_penghapus mapped to users table (name & email)
$sql = "SELECT k.id, k.kode, k.kegiatan, k.keterangan, k.created_at, k.deleted_at, c.nama AS nama_customer,
        (SELECT COALESCE(u.name, l.nama_user, 'System/Admin')
         FROM log_kegiatan l 
         LEFT JOIN users u ON (u.name = l.nama_user OR u.email = l.nama_user OR CAST(u.id AS CHAR) = l.nama_user)
         WHERE (l.kode_transaksi = k.kode OR l.kode_transaksi LIKE CONCAT(k.kode, ' - %')) 
         AND (l.jenis_aksi LIKE '%Delete%' OR l.jenis_aksi LIKE '%hapus%')
         ORDER BY l.waktu DESC LIMIT 1) AS user_penghapus
        FROM kegiatan k 
        LEFT JOIN customer c ON k.customer_id = c.id 
        WHERE k.deleted_at IS NOT NULL 
        ORDER BY k.deleted_at DESC";
$result = mysqli_query($conn, $sql);
$deletedKegiatan = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
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
        .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .badge-kegiatan { font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 700; }
        .btn-restore-main {
            background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
            color: #FFFFFF !important;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 12px;
            white-space: nowrap;
        }
        .btn-restore-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
            color: #FFFFFF !important;
        }
        .search-box-restore {
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            width: 100%;
            max-width: 400px;
            outline: none;
            transition: all 0.2s;
        }
        .search-box-restore:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .date-badge {
            background: #FEF2F2;
            color: #EF4444;
            border: 1px solid #FECACA;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
        .user-badge {
            background: #F1F5F9;
            color: #334155;
            border: 1px solid #CBD5E1;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?> text-white alert-dismissible fade show mb-4" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card my-4">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <h6 class="text-white text-capitalize mb-0"><i class="material-icons me-2" style="vertical-align:middle;">restore_from_trash</i> Pulihkan Data Kegiatan Terhapus (Trash)</h6>
                                <span class="badge bg-white text-dark font-weight-bold" id="totalCountBadge"><?php echo count($deletedKegiatan); ?> Data Terhapus</span>
                            </div>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <!-- Search Bar -->
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
                                <input type="text" id="restoreSearch" class="search-box-restore" placeholder="🔍 Cari nama customer, user penghapus, atau kode...">
                                <span class="text-xs text-muted font-weight-bold">Total: <span id="visibleCount"><?php echo count($deletedKegiatan); ?></span> data</span>
                            </div>

                            <div class="table-responsive p-0">
                                <table class="table align-middle mb-0" id="restoreTable">
                                    <thead>
                                        <tr>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 130px;">AKSI</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 150px;">DIHAPUS OLEH</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 150px;">KODE / JENIS</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 160px;">TGL TERHAPUS</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 200px;">CUSTOMER</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">KETERANGAN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($deletedKegiatan)): ?>
                                            <?php foreach ($deletedKegiatan as $row): ?>
                                                <tr class="restore-row">
                                                    <td class="align-middle text-center">
                                                        <a href="restore_kegiatan.php?action=restore&kode=<?php echo urlencode($row['kode']); ?>" 
                                                           class="btn-restore-main" 
                                                           onclick="return confirm('Apakah Anda yakin ingin mempulihkan kegiatan <?php echo htmlspecialchars($row['kode']); ?>?');">
                                                            <i class="material-icons text-sm">settings_backup_restore</i> PULIHKAN
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="user-badge">
                                                            <i class="material-icons text-xs me-1">person</i>
                                                            <?php echo htmlspecialchars($row['user_penghapus'] ?? 'System/Admin'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-kegiatan bg-gradient-info text-white me-1">
                                                            <?php echo strtoupper(htmlspecialchars($row['kegiatan'] ?? 'KEGIATAN')); ?>
                                                        </span>
                                                        <strong class="text-dark text-xs"><?php echo htmlspecialchars($row['kode']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="date-badge">
                                                            <i class="material-icons text-xs me-1">schedule</i>
                                                            <?php echo date('d M Y, H:i', strtotime($row['deleted_at'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0 text-dark"><?php echo htmlspecialchars($row['nama_customer'] ?? 'Unknown'); ?></p>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs text-secondary mb-0 text-truncate" style="max-width:300px;" title="<?php echo htmlspecialchars($row['keterangan'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-secondary">
                                                    <i class="material-icons text-secondary mb-2" style="font-size: 48px;">delete_outline</i>
                                                    <p class="mb-0 font-weight-bold">Tidak ada data kegiatan yang terhapus.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>

    <script>
    document.getElementById('restoreSearch')?.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.restore-row');
        let count = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        document.getElementById('visibleCount').textContent = count;
    });
    </script>
</body>
</html>
