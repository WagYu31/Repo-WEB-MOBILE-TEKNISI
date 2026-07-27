<?php
include "conn.php";
include "session.php";
$pageNow = "Restore Kegiatan";
include "get-user-data.php";

// Passcode verification logic (PIN: 312226)
$passcodeError = '';
if (isset($_GET['action']) && $_GET['action'] === 'lock') {
    unset($_SESSION['restore_unlocked']);
    header("Location: restore_kegiatan.php");
    exit;
}

if (isset($_POST['submit_passcode'])) {
    $inputPasscode = trim($_POST['passcode'] ?? '');
    if ($inputPasscode === '312226') {
        $_SESSION['restore_unlocked'] = true;
    } else {
        $passcodeError = 'Sandi / PIN Keamanan salah! Silakan coba lagi.';
    }
}

$isUnlocked = isset($_SESSION['restore_unlocked']) && $_SESSION['restore_unlocked'] === true;

$message = '';
$messageType = '';

// Process single restore if unlocked
if ($isUnlocked && isset($_GET['action']) && $_GET['action'] === 'restore' && !empty($_GET['kode'])) {
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

// Process bulk restore request
if ($isUnlocked && isset($_POST['submit_bulk_restore']) && !empty($_POST['selected_kodes']) && is_array($_POST['selected_kodes'])) {
    $selectedKodes = array_map('trim', $_POST['selected_kodes']);
    date_default_timezone_set('Asia/Jakarta');
    $now = date('Y-m-d H:i:s');
    $user_display = (!empty($nmUser)) ? $nmUser : "System/Admin";
    
    $restoredCount = 0;
    foreach ($selectedKodes as $kode) {
        if (empty($kode)) continue;
        
        $stmt1 = $conn->prepare("UPDATE kegiatan SET deleted_at = NULL WHERE kode = ?");
        $stmt1->bind_param("s", $kode);
        $stmt1->execute();
        if ($stmt1->affected_rows > 0) $restoredCount++;
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE pelaksanaan_kegiatan SET deleted_at = NULL WHERE kode = ?");
        $stmt2->bind_param("s", $kode);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("UPDATE pendapatan_kegiatan SET deleted_at = NULL WHERE kode = ?");
        $stmt3->bind_param("s", $kode);
        $stmt3->execute();
        $stmt3->close();

        $jenis_aksi = "Bulk Restore";
        if ($stmt_log = $conn->prepare("INSERT INTO log_kegiatan (jenis_aksi, nama_user, waktu, kode_transaksi) VALUES (?, ?, ?, ?)")) {
            $stmt_log->bind_param("ssss", $jenis_aksi, $user_display, $now, $kode);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }

    if ($restoredCount > 0) {
        $message = "Berhasil mempulihkan <strong>$restoredCount</strong> kegiatan terpilih!";
        $messageType = "success";
    } else {
        $message = "Tidak ada kegiatan yang dipulihkan.";
        $messageType = "warning";
    }
}

// Fetch soft-deleted kegiatan only if unlocked
$deletedKegiatan = [];
if ($isUnlocked) {
    $sql = "SELECT k.id, k.kode, k.kegiatan, k.keterangan, k.created_at, k.deleted_at, c.nama AS nama_customer,
            (SELECT u.name FROM log_kegiatan l 
             LEFT JOIN users u ON (u.name = l.nama_user OR u.email = l.nama_user OR CAST(u.id AS CHAR) = l.nama_user)
             WHERE (l.kode_transaksi = k.kode OR l.kode_transaksi LIKE CONCAT(k.kode, ' - %')) 
             AND (l.jenis_aksi LIKE '%Delete%' OR l.jenis_aksi LIKE '%hapus%')
             ORDER BY l.waktu DESC LIMIT 1) AS user_penghapus_name,
            (SELECT u.email FROM log_kegiatan l 
             LEFT JOIN users u ON (u.name = l.nama_user OR u.email = l.nama_user OR CAST(u.id AS CHAR) = l.nama_user)
             WHERE (l.kode_transaksi = k.kode OR l.kode_transaksi LIKE CONCAT(k.kode, ' - %')) 
             AND (l.jenis_aksi LIKE '%Delete%' OR l.jenis_aksi LIKE '%hapus%')
             ORDER BY l.waktu DESC LIMIT 1) AS user_penghapus_email,
            (SELECT l.nama_user FROM log_kegiatan l 
             WHERE (l.kode_transaksi = k.kode OR l.kode_transaksi LIKE CONCAT(k.kode, ' - %')) 
             AND (l.jenis_aksi LIKE '%Delete%' OR l.jenis_aksi LIKE '%hapus%')
             ORDER BY l.waktu DESC LIMIT 1) AS user_penghapus_raw
            FROM kegiatan k 
            LEFT JOIN customer c ON k.customer_id = c.id 
            WHERE k.deleted_at IS NOT NULL 
            ORDER BY k.deleted_at DESC";
    $result = mysqli_query($conn, $sql);
    $deletedKegiatan = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
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
        .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .badge-kegiatan { font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 700; }
        .btn-restore-main {
            background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);
            color: #FFFFFF !important;
            font-weight: 700;
            padding: 7px 12px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 11.5px;
            white-space: nowrap;
        }
        .btn-restore-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
            color: #FFFFFF !important;
        }
        .btn-bulk-restore {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #FFFFFF !important;
            font-weight: 700;
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            white-space: nowrap;
            cursor: pointer;
        }
        .btn-bulk-restore:disabled {
            background: #CBD5E1 !important;
            box-shadow: none !important;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .btn-bulk-restore:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
        }
        .search-box-restore {
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            padding: 9px 16px;
            font-size: 13.5px;
            width: 100%;
            max-width: 380px;
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
        .email-text {
            color: #64748B;
            font-size: 10.5px;
            font-weight: 600;
        }
        .custom-chk {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #10B981;
        }

        /* Lock Screen Styling */
        .lock-card {
            max-width: 460px;
            margin: 60px auto;
            border-radius: 20px;
            border: none;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.15);
            overflow: hidden;
            background: #ffffff;
        }
        .lock-header {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            padding: 36px 24px;
            text-align: center;
            color: white;
            position: relative;
        }
        .lock-icon-box {
            width: 72px;
            height: 72px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border: 2px solid rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
        }
        .it-info-box {
            background: #F0F9FF;
            border: 1px solid #BAE6FD;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .lock-input {
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            width: 100%;
            outline: none;
            transition: all 0.2s;
            background: #F8FAFC;
        }
        .lock-input:focus {
            border-color: #0284C7;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
        }
        .btn-unlock {
            background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
            color: white;
            font-weight: 700;
            padding: 14px;
            border-radius: 12px;
            border: none;
            width: 100%;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
            transition: all 0.2s;
        }
        .btn-unlock:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        
        <div class="container-fluid py-4">
            <?php if (!$isUnlocked): ?>
                <!-- LOCK SCREEN / PASSCODE FORM -->
                <div class="card lock-card">
                    <div class="lock-header">
                        <div class="lock-icon-box">
                            <i class="material-icons text-info" style="font-size: 36px;">security</i>
                        </div>
                        <h5 class="text-white font-weight-bold mb-1">Akses Terkunci 🔒</h5>
                        <p class="text-xs text-slate-300 mb-0 opacity-8">Menu pemulihan data kegiatan terhapus</p>
                    </div>
                    <div class="card-body p-4">
                        <!-- Professional IT Info Alert -->
                        <div class="it-info-box mb-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="material-icons text-info text-sm">info</i>
                                <strong class="text-xs text-dark font-weight-bold">Bantuan Akses:</strong>
                            </div>
                            <p class="text-xs text-secondary mb-0">Silakan hubungi <strong>Tim IT / Administrator System</strong> untuk membuka menu ini.</p>
                        </div>

                        <?php if (!empty($passcodeError)): ?>
                            <div class="alert alert-danger text-white text-xs text-center py-2 px-3 mb-3 border-radius-lg" role="alert">
                                <i class="material-icons text-xs me-1" style="vertical-align:middle;">error</i> <?php echo $passcodeError; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="restore_kegiatan.php">
                            <div class="mb-4">
                                <label class="form-label text-xs font-weight-bold text-secondary text-uppercase mb-2">MASUKKAN SANDI KEAMANAN</label>
                                <input type="password" name="passcode" class="lock-input" placeholder="••••••" maxlength="10" required autofocus autocomplete="off">
                            </div>
                            <button type="submit" name="submit_passcode" class="btn-unlock">
                                Verifikasi & Buka Akses 🔓
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- UNLOCKED CONTENT (RESTORE FORM & TABLE) -->
                <form method="POST" action="restore_kegiatan.php" id="bulkForm">
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
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-white text-dark font-weight-bold" id="totalCountBadge"><?php echo count($deletedKegiatan); ?> Data Terhapus</span>
                                            <a href="restore_kegiatan.php?action=lock" class="btn btn-xs btn-outline-white mb-0 text-white" style="border: 1px solid rgba(255,255,255,0.4);" title="Kunci Kembali Halaman">
                                                <i class="material-icons text-xs me-1">lock</i> Kunci
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body px-4 pb-4">
                                    <!-- Search & Bulk Action Controls -->
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 mt-2">
                                        <input type="text" id="restoreSearch" class="search-box-restore" placeholder="🔍 Cari nama customer, email, user penghapus, atau kode...">
                                        
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="submit" name="submit_bulk_restore" id="bulkRestoreBtn" class="btn-bulk-restore" disabled onclick="return confirmBulkRestore();">
                                                <i class="material-icons text-sm">settings_backup_restore</i> Pulihkan (<span id="selectedCount">0</span>) Terpilih
                                            </button>
                                            <span class="text-xs text-muted font-weight-bold ms-2">Total: <span id="visibleCount"><?php echo count($deletedKegiatan); ?></span> data</span>
                                        </div>
                                    </div>

                                    <div class="table-responsive p-0">
                                        <table class="table align-middle mb-0" id="restoreTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">
                                                        <input type="checkbox" id="selectAll" class="custom-chk" title="Pilih Semua Data">
                                                    </th>
                                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 110px;">AKSI</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 170px;">DIHAPUS OLEH</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 140px;">KODE / JENIS</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 150px;">TGL TERHAPUS</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 200px;">CUSTOMER</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">KETERANGAN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($deletedKegiatan)): ?>
                                                    <?php foreach ($deletedKegiatan as $row): ?>
                                                        <?php 
                                                        $userName = $row['user_penghapus_name'] ?? $row['user_penghapus_raw'] ?? 'System/Admin';
                                                        $userEmail = $row['user_penghapus_email'] ?? '';
                                                        ?>
                                                        <tr class="restore-row">
                                                            <td class="text-center">
                                                                <input type="checkbox" name="selected_kodes[]" value="<?php echo htmlspecialchars($row['kode']); ?>" class="row-checkbox custom-chk">
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <a href="restore_kegiatan.php?action=restore&kode=<?php echo urlencode($row['kode']); ?>" 
                                                                   class="btn-restore-main" 
                                                                   onclick="return confirm('Apakah Anda yakin ingin mempulihkan kegiatan <?php echo htmlspecialchars($row['kode']); ?>?');">
                                                                    <i class="material-icons text-xs">settings_backup_restore</i> PULIHKAN
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-column">
                                                                    <?php if ($userName === 'System/Admin' || empty($userName)): ?>
                                                                        <span class="user-badge mb-1" style="background:#F8FAFC;color:#64748B;border-color:#E2E8F0;">
                                                                            <i class="material-icons text-xs me-1">settings_suggest</i> System / Otomatis
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="user-badge mb-1">
                                                                            <i class="material-icons text-xs me-1">person</i>
                                                                            <?php echo htmlspecialchars($userName); ?>
                                                                        </span>
                                                                        <?php if (!empty($userEmail)): ?>
                                                                            <span class="email-text">
                                                                                <i class="material-icons text-xxs me-1" style="font-size:11px;vertical-align:middle;">email</i><?php echo htmlspecialchars($userEmail); ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </div>
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
                                                                <p class="text-xs text-secondary mb-0 text-truncate" style="max-width:280px;" title="<?php echo htmlspecialchars($row['keterangan'] ?? ''); ?>">
                                                                    <?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?>
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-5 text-secondary">
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
                </form>
            <?php endif; ?>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>

    <script>
    // Selection and search handler
    const selectAllChk = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkBtn = document.getElementById('bulkRestoreBtn');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateSelectionState() {
        const checkedRows = Array.from(document.querySelectorAll('.row-checkbox:checked'));
        const visibleRows = Array.from(document.querySelectorAll('.restore-row')).filter(r => r.style.display !== 'none');
        const checkedVisibleRows = visibleRows.filter(r => r.querySelector('.row-checkbox').checked);
        
        const count = checkedRows.length;
        selectedCountSpan.textContent = count;
        bulkBtn.disabled = (count === 0);

        if (selectAllChk) {
            selectAllChk.checked = (visibleRows.length > 0 && checkedVisibleRows.length === visibleRows.length);
            selectAllChk.indeterminate = (checkedVisibleRows.length > 0 && checkedVisibleRows.length < visibleRows.length);
        }
    }

    selectAllChk?.addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.restore-row').forEach(row => {
            if (row.style.display !== 'none') {
                const chk = row.querySelector('.row-checkbox');
                if (chk) chk.checked = isChecked;
            }
        });
        updateSelectionState();
    });

    rowCheckboxes.forEach(chk => {
        chk.addEventListener('change', updateSelectionState);
    });

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
        updateSelectionState();
    });

    function confirmBulkRestore() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        if (count === 0) return false;
        return confirm(`Apakah Anda yakin ingin mempulihkan ${count} kegiatan terpilih sekaligus?`);
    }
    </script>
</body>
</html>
