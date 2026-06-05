<?php
include "conn.php";
include "session.php";
$pageNow = "Edit Kegiatan";
include "get-user-data.php";

// Get kegiatan data
if (!isset($_GET["kode_transaksi"])) {
    echo "<script>alert('Kode transaksi tidak ditemukan.'); window.location.href='index-sa.php';</script>";
    exit;
}
$kode_transaksi = mysqli_real_escape_string($conn, $_GET["kode_transaksi"]);

// Include update handler (processes POST if applicable)
include "get_update_kegiatan.php";

// Fetch kegiatan data
$sql = "SELECT k.*, k.id AS id_kegiatan, c.nama AS nama_customer, c.telp AS cust_nomor, c.id AS customer_id
        FROM kegiatan k
        LEFT JOIN customer c ON k.customer_id = c.id
        WHERE k.kode = ? AND k.deleted_at IS NULL
        ORDER BY k.id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $kode_transaksi);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "<script>alert('Data kegiatan tidak ditemukan.'); window.location.href='index-sa.php';</script>";
    exit;
}

$id_kegiatan = $data['id_kegiatan'];
$kodeTransaksi = $data['kode'];

// Fetch assigned technicians with is_ketua
$sqlTeam = "SELECT teknisi_id, nama_teknisi, is_ketua FROM team_kegiatan 
            WHERE kegiatan_id = ? AND kode = ? AND deleted_at IS NULL";
$stmtTeam = $conn->prepare($sqlTeam);
$stmtTeam->bind_param("is", $id_kegiatan, $kodeTransaksi);
$stmtTeam->execute();
$resTeam = $stmtTeam->get_result();
$assignedTeknisi = [];
$currentKetuaId = 0;
while ($rt = $resTeam->fetch_assoc()) {
    $assignedTeknisi[$rt['teknisi_id']] = $rt;
    if ($rt['is_ketua']) $currentKetuaId = $rt['teknisi_id'];
}
$stmtTeam->close();

// Fetch all active technicians
$allTeknisi = [];
$resTek = $conn->query("SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
while ($t = $resTek->fetch_assoc()) { $allTeknisi[] = $t; }

// Format date/time
$jadwal = $data['jadwal'];
$formattedDate = date("Y-m-d", strtotime($jadwal));
$formattedTime = date("H:i", strtotime($jadwal));

// Phone number formatting
$nomorHP = $data['cust_nomor'];
if (substr($nomorHP, 0, 1) === '0') $nomorHP = '62' . substr($nomorHP, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .edit-container { max-width: 900px; margin: 0 auto; }
        .edit-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;
        }
        .edit-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            padding: 20px 28px; display: flex; align-items: center; gap: 12px;
        }
        .edit-header-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(99,102,241,0.2); display: flex; align-items: center; justify-content: center;
        }
        .edit-header-icon i { font-size: 18px; color: #a5b4fc; }
        .edit-header h5 { color: #fff; font-size: 16px; font-weight: 700; margin: 0; }
        .edit-header .kode-badge {
            font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 12px;
            background: rgba(255,255,255,0.1); color: #94a3b8; margin-left: auto;
        }
        .edit-body { padding: 24px 28px; }
        .edit-section { margin-bottom: 20px; }
        .edit-label {
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;
            letter-spacing: 0.05em; margin-bottom: 6px; display: block;
        }
        .edit-input {
            width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px;
            padding: 10px 14px; font-size: 13px; font-weight: 500; color: #1e293b;
            transition: border-color 0.2s; outline: none; background: #fff;
        }
        .edit-input:focus { border-color: #6366f1; }
        .edit-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .customer-info {
            background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 14px 18px;
        }
        .customer-name { font-size: 14px; font-weight: 700; color: #1e293b; }
        .customer-phone { font-size: 12px; color: #3b82f6; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; }
        .customer-phone:hover { text-decoration: underline; }
        .request-badge {
            font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 10px;
            background: #f1f5f9; color: #64748b;
        }
        .tek-list { max-height: 320px; overflow-y: auto; }
        .tek-item {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            border: 1px solid #f1f5f9; border-radius: 10px; margin-bottom: 8px;
            cursor: pointer; transition: all 0.15s; background: #fff;
        }
        .tek-item:hover { border-color: #6366f1; background: #fafafe; }
        .tek-item.checked { border-color: #6366f1; background: #f5f3ff; }
        .tek-item input[type="checkbox"] {
            flex-shrink: 0; width: 16px; height: 16px; border-radius: 4px;
            border: 1.5px solid #cbd5e1; accent-color: #6366f1;
        }
        .tek-name { font-size: 13px; font-weight: 600; color: #1e293b; flex: 1; }
        .ketua-pill {
            display: none; align-items: center; gap: 3px; cursor: pointer;
            padding: 2px 8px; border-radius: 12px; background: #fef3c7;
            border: 1px solid #fde68a; font-size: 10px; font-weight: 700; color: #92400e;
        }
        .ketua-pill input[type="radio"] { width: 12px; height: 12px; accent-color: #f59e0b; }
        .tek-item.checked .ketua-pill { display: inline-flex; }
        .edit-footer {
            padding: 16px 28px; border-top: 1px solid #f1f5f9;
            display: flex; gap: 10px; justify-content: flex-end;
        }
        .btn-cancel {
            background: #f1f5f9; color: #64748b; border: none; border-radius: 10px;
            padding: 10px 24px; font-size: 12px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-cancel:hover { background: #e2e8f0; color: #475569; }
        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff;
            border: none; border-radius: 10px; padding: 10px 28px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-submit:hover { box-shadow: 0 4px 16px rgba(99,102,241,0.4); }
        .kegiatan-type {
            display: inline-block; font-size: 10px; font-weight: 700; padding: 3px 10px;
            border-radius: 12px; text-transform: uppercase; letter-spacing: 0.03em;
        }
        .kegiatan-type.service { background: #e0e7ff; color: #3730a3; }
        .kegiatan-type.survey { background: #fef3c7; color: #92400e; }
        .kegiatan-type.pasang { background: #dcfce7; color: #166534; }
        @media (max-width: 768px) {
            .edit-grid { grid-template-columns: 1fr; }
            .edit-body { padding: 16px 18px; }
            .edit-footer { padding: 14px 18px; }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="edit-container">
                <form method="POST" action="edit_data_kegiatan.php" id="editForm">
                    <input type="hidden" name="kode_transaksi" value="<?= htmlspecialchars($kodeTransaksi) ?>">
                    <input type="hidden" name="kegiatan_id" value="<?= $id_kegiatan ?>">

                    <div class="edit-card">
                        <!-- Header -->
                        <div class="edit-header">
                            <div class="edit-header-icon"><i class="material-icons">edit_note</i></div>
                            <h5>Edit Kegiatan</h5>
                            <span class="kode-badge"><?= htmlspecialchars($kodeTransaksi) ?></span>
                        </div>

                        <div class="edit-body">
                            <!-- Info Row -->
                            <div class="edit-section">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                                    <?php
                                    $kegLower = strtolower($data['kegiatan']);
                                    $typeClass = 'service';
                                    if (strpos($kegLower, 'survey') !== false) $typeClass = 'survey';
                                    elseif (strpos($kegLower, 'pasang') !== false) $typeClass = 'pasang';
                                    ?>
                                    <span class="kegiatan-type <?= $typeClass ?>"><?= htmlspecialchars($data['kegiatan']) ?></span>
                                    <span class="request-badge">Request by <?= htmlspecialchars($data['request']) ?></span>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div class="edit-section">
                                <label class="edit-label">Customer</label>
                                <div class="customer-info">
                                    <div class="customer-name"><?= htmlspecialchars($data['nama_customer']) ?></div>
                                    <a href="https://api.whatsapp.com/send?phone=<?= $nomorHP ?>" target="_blank" class="customer-phone">
                                        <i class="material-icons" style="font-size:14px;">phone</i>
                                        <?= htmlspecialchars($data['cust_nomor']) ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Date/Time + Kegiatan Type -->
                            <div class="edit-grid">
                                <div class="edit-section">
                                    <label class="edit-label">Tanggal</label>
                                    <input type="date" class="edit-input" name="tanggal_pilihan" value="<?= $formattedDate ?>" required>
                                </div>
                                <div class="edit-section">
                                    <label class="edit-label">Jam</label>
                                    <input type="time" class="edit-input" name="waktu_pilihan" value="<?= $formattedTime ?>" required>
                                </div>
                            </div>

                            <div class="edit-section">
                                <label class="edit-label">Jenis Kegiatan</label>
                                <select class="edit-input edit-select" name="kegiatan_pilihan">
                                    <option value="survey" <?= ($data['kegiatan'] == 'survey') ? 'selected' : '' ?>>Survey</option>
                                    <option value="pasang baru" <?= ($data['kegiatan'] == 'pasang baru') ? 'selected' : '' ?>>Pasang Baru</option>
                                    <option value="service" <?= ($data['kegiatan'] == 'service') ? 'selected' : '' ?>>Service</option>
                                </select>
                            </div>

                            <!-- Keterangan -->
                            <div class="edit-section">
                                <label class="edit-label">Keterangan</label>
                                <textarea class="edit-input" name="keterangan" rows="3" style="resize:vertical;"><?= htmlspecialchars($data['keterangan'] ?? '') ?></textarea>
                            </div>

                            <!-- Radius Lokasi -->
                            <div class="edit-section">
                                <label class="edit-label">Radius Lokasi (meter)</label>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <input type="number" class="edit-input" name="radius" 
                                           value="<?= htmlspecialchars($data['rad'] ?? '100') ?>" 
                                           min="50" max="5000" step="50" 
                                           style="max-width:200px;" required>
                                    <span style="font-size:12px;color:#94a3b8;">meter (min: 50, max: 5000)</span>
                                </div>
                            </div>

                            <!-- Teknisi Selection -->
                            <div class="edit-section">
                                <label class="edit-label">Pilih Teknisi & Ketua Tim</label>
                                <div class="tek-list">
                                    <?php foreach ($allTeknisi as $t):
                                        $isChecked = isset($assignedTeknisi[$t['id']]);
                                        $isKetua = ($t['id'] == $currentKetuaId);
                                    ?>
                                    <label class="tek-item <?= $isChecked ? 'checked' : '' ?>" id="tek-label-<?= $t['id'] ?>">
                                        <input type="checkbox" name="teknisi[]" value="<?= $t['id'] ?>"
                                               <?= $isChecked ? 'checked' : '' ?>
                                               onchange="toggleTekItem(this)">
                                        <span class="tek-name"><?= htmlspecialchars($t['nama']) ?></span>
                                        <span class="ketua-pill" onclick="event.stopPropagation()">
                                            <input type="radio" name="ketua_id" value="<?= $t['id'] ?>"
                                                   <?= $isKetua ? 'checked' : '' ?>> 👑 Ketua
                                        </span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="edit-footer">
                            <a href="index-sa.php" class="btn-cancel">
                                <i class="material-icons" style="font-size:14px;">arrow_back</i> Batal
                            </a>
                            <button type="submit" name="update_kegiatan" class="btn-submit" onclick="return validateEdit()">
                                <i class="material-icons" style="font-size:14px;">save</i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleTekItem(cb) {
            const label = cb.closest('.tek-item');
            label.classList.toggle('checked', cb.checked);
            // Auto-select ketua if only 1 checked
            const allChecked = document.querySelectorAll('.tek-item input[type="checkbox"]:checked');
            if (allChecked.length === 1) {
                const id = allChecked[0].value;
                const radio = document.querySelector('.ketua-pill input[value="' + id + '"]');
                if (radio) radio.checked = true;
            }
            // Uncheck ketua radio if checkbox unchecked
            if (!cb.checked) {
                const radio = label.querySelector('.ketua-pill input[type="radio"]');
                if (radio) radio.checked = false;
            }
        }

        function validateEdit() {
            const checked = document.querySelectorAll('.tek-item input[type="checkbox"]:checked');
            if (checked.length === 0) { alert('Pilih minimal 1 teknisi!'); return false; }
            let ketuaId = document.querySelector('input[name="ketua_id"]:checked');
            if (checked.length === 1) {
                // Auto-set
                const radio = document.querySelector('.ketua-pill input[value="' + checked[0].value + '"]');
                if (radio) radio.checked = true;
                return true;
            }
            if (!ketuaId) { alert('Pilih satu teknisi sebagai Ketua Tim!'); return false; }
            return true;
        }
    </script>
</body>
</html>