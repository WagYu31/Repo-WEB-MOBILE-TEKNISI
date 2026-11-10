<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";

// Auto-ensure no_so column in database is VARCHAR(100) to prevent truncation of long SO numbers
$checkNoSoCol = mysqli_query($conn, "SHOW COLUMNS FROM `kegiatan` LIKE 'no_so'");
if ($checkNoSoCol && mysqli_num_rows($checkNoSoCol) > 0) {
    $rCol = mysqli_fetch_assoc($checkNoSoCol);
    if (!empty($rCol['Type']) && strpos(strtolower($rCol['Type']), 'varchar(100)') === false) {
        mysqli_query($conn, "ALTER TABLE `kegiatan` MODIFY COLUMN `no_so` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nomor Sales Order (SO)'");
    }
}
$checkNoSoProg = mysqli_query($conn, "SHOW COLUMNS FROM `progress_kegiatan` LIKE 'no_so'");
if ($checkNoSoProg && mysqli_num_rows($checkNoSoProg) > 0) {
    $rColProg = mysqli_fetch_assoc($checkNoSoProg);
    if (!empty($rColProg['Type']) && strpos(strtolower($rColProg['Type']), 'varchar(100)') === false) {
        mysqli_query($conn, "ALTER TABLE `progress_kegiatan` MODIFY COLUMN `no_so` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nomor Sales Order (SO)'");
    }
}

$customer_id = $_GET["id_cust"] ?? 0;
if (!filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
    die("ID Customer tidak valid.");
}

$sql = "SELECT
            k.id AS kegiatan_id, k.kode AS kegiatan_kode, k.kegiatan AS jenis_kegiatan, 
            k.jadwal AS jadwal_kegiatan, k.keterangan AS keterangan_kegiatan, k.lunas,
            k.invoice AS kegiatan_invoice, k.paid AS kegiatan_paid, k.catatan_admin,
            COALESCE(NULLIF(k.no_so, ''), pk_so.no_so) AS no_so,
            c.nama AS customer_name,
            (SELECT no_invoice FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as no_invoice,
            (SELECT nominal_invoice FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as nominal_invoice,
            p.teknisi_id, p.status, p.waktu_mulai, p.waktu_selesai, p.permasalahan, p.solusi,
            p.image_1, p.image_2, p.image_3, p.image_4, p.image_5,
            t.nama AS teknisi_name
        FROM kegiatan k
        INNER JOIN (
            SELECT customer_id, kode, MAX(id) AS max_id 
            FROM kegiatan 
            WHERE deleted_at IS NULL 
            GROUP BY customer_id, kode
        ) latest ON k.id = latest.max_id AND k.customer_id = latest.customer_id
        LEFT JOIN (
            SELECT kode, MAX(no_so) AS no_so
            FROM progress_kegiatan
            WHERE no_so IS NOT NULL AND no_so != ''
            GROUP BY kode
        ) pk_so ON k.kode = pk_so.kode
        LEFT JOIN customer c ON k.customer_id = c.id
        LEFT JOIN (
            SELECT p1.* FROM pelaksanaan_kegiatan p1
            INNER JOIN (
                SELECT kode, teknisi_id, waktu_mulai, MAX(id) AS max_id
                FROM pelaksanaan_kegiatan
                WHERE deleted_at IS NULL
                GROUP BY kode, teknisi_id, waktu_mulai
            ) p2 ON p1.id = p2.max_id
        ) p ON k.kode = p.kode
        LEFT JOIN teknisi t ON p.teknisi_id = t.id
        WHERE k.customer_id = ? AND k.deleted_at IS NULL
        ORDER BY k.jadwal DESC, p.waktu_mulai ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$grouped_activities = [];
$customerName = "Tidak Diketahui";

while ($row = $result->fetch_assoc()) {
    $customerName = $row['customer_name'] ?? $customerName;
    $kode = $row['kegiatan_kode'];

    if (!isset($grouped_activities[$kode])) {
        $grouped_activities[$kode] = [
            'info' => [
                'jenis' => $row['jenis_kegiatan'], 'jadwal' => $row['jadwal_kegiatan'],
                'keterangan' => $row['keterangan_kegiatan'], 'no_invoice' => $row['no_invoice'],
                'nominal_invoice' => $row['nominal_invoice'], 'lunas' => $row['lunas'],
                'invoice' => $row['kegiatan_invoice'], 'paid' => $row['kegiatan_paid'],
                'catatan_admin' => $row['catatan_admin'],
                'no_so' => $row['no_so'],
                'id' => $row['kegiatan_id']
            ], 'pelaksanaan' => []
        ];
    }
    if (!empty($row['teknisi_name'])) {
        $grouped_activities[$kode]['pelaksanaan'][] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Riwayat Pelanggan - <?= htmlspecialchars($customerName) ?></title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .so-badge-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.02em;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            line-height: 1.2;
        }
        .so-badge-btn:hover {
            background: #dcfce7;
            border-color: #86efac;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(22, 101, 52, 0.12);
        }
        .btn-so-add {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 3px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.2;
        }
        .btn-so-add:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-xl-12 col-lg-12">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6"><h5 class="mb-1 text-uppercase">Riwayat Kegiatan</h5><p class="text-sm mb-md-0"><strong><?= htmlspecialchars($customerName) ?></strong></p></div>
                                <div class="col-md-6"><div class="input-group"><span class="input-group-text text-body"><i class="fas fa-search me-2" aria-hidden="true"></i></span><input type="text" id="searchInput" class="form-control p-2 border" placeholder="Cari kegiatan, no. SO, teknisi, atau catatan..."></div></div>
                            </div>
                        </div>
                    </div>
                    <div id="activityList">
                        <?php if (empty($grouped_activities)): ?>
                            <div class="alert alert-info text-white text-center">Tidak ada riwayat kegiatan untuk pelanggan ini.</div>
                        <?php else: ?>
                            <?php foreach ($grouped_activities as $kode => $activity): ?>
                                <div class="card mb-4 activity-card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 text-dark text-capitalize d-flex align-items-center flex-wrap gap-2">
                                                <span><i class="fa-solid fa-bolt text-primary me-2"></i><?= htmlspecialchars($activity['info']['jenis']) ?></span>
                                                <span id="header_so_<?= htmlspecialchars($kode) ?>">
                                                    <?php if (!empty($activity['info']['no_so'])): ?>
                                                        <span class="badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:0.02em;padding:3px 8px;">
                                                            <i class="fa-solid fa-receipt me-1"></i><?= htmlspecialchars($activity['info']['no_so']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </h6>
                                            <span class="text-xs text-muted"><?= date("d M Y", strtotime($activity['info']['jadwal'])) ?> (<?= htmlspecialchars($kode) ?>)</span>
                                        </div>
                                        <a href="view-kegiatan.php?kode_transaksi=<?= htmlspecialchars($kode) ?>" target="_blank" class="btn btn-sm btn-outline-info mb-0">Lihat Detail Kegiatan</a>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6 class="font-weight-bolder">Ringkasan</h6><p class="text-sm text-muted"><?= !empty($activity['info']['keterangan']) ? nl2br(htmlspecialchars($activity['info']['keterangan'])) : 'Tidak ada keterangan umum.' ?></p>
                                                <hr class="horizontal dark my-2">
                                                <h6 class="font-weight-bolder">Informasi Keuangan</h6>
                                                
                                                <p class="text-sm mb-1 d-flex align-items-center flex-wrap">
                                                    <strong class="me-1">No. SO:</strong>
                                                    <span id="wrap_so_<?= htmlspecialchars($kode) ?>">
                                                        <?php if (!empty($activity['info']['no_so'])): ?>
                                                            <button type="button" class="so-badge-btn" onclick="openInputSO('<?= htmlspecialchars($kode) ?>', '<?= htmlspecialchars($activity['info']['no_so'], ENT_QUOTES) ?>', '<?= htmlspecialchars($customerName, ENT_QUOTES) ?>')" title="Klik untuk edit No. SO">
                                                                <i class="fa-solid fa-receipt text-success me-1"></i>
                                                                <span class="so-txt" id="txt_so_<?= htmlspecialchars($kode) ?>"><?= htmlspecialchars($activity['info']['no_so']) ?></span>
                                                                <i class="fa-solid fa-pen ms-1 opacity-50" style="font-size: 8px;"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn-so-add" onclick="openInputSO('<?= htmlspecialchars($kode) ?>', '', '<?= htmlspecialchars($customerName, ENT_QUOTES) ?>')" title="Input Nomor SO">
                                                                <i class="fa-solid fa-plus"></i> Input SO
                                                            </button>
                                                        <?php endif; ?>
                                                    </span>
                                                </p>
                                                <?php if(!empty($activity['info']['no_invoice'])): ?>
                                                    <p class="text-sm mb-1"><strong>No. Invoice:</strong> <?= htmlspecialchars($activity['info']['no_invoice']) ?></p>
                                                    <p class="text-sm mb-1"><strong>Nominal:</strong> <span class="font-weight-bold text-success">Rp <?= number_format($activity['info']['nominal_invoice'], 0, ',', '.') ?></span></p>
                                                    <p class="text-sm mb-0"><strong>Status:</strong>
                                                        <?php if(!empty($activity['info']['lunas']) && $activity['info']['lunas'] != '0000-00-00'): ?><span class="badge badge-sm bg-gradient-success">LUNAS</span><?php else: ?><span class="badge badge-sm bg-gradient-danger">BELUM LUNAS</span><?php endif; ?>
                                                    </p>
                                                <?php else: 
                                                    $isNoPay = (!empty($activity['info']['invoice']) && $activity['info']['invoice'] === 'n/a');
                                                ?>
                                                    <p class="text-sm mb-1"><strong>No. Invoice:</strong> <span class="text-muted">-</span></p>
                                                    <p class="text-sm mb-1"><strong>Nominal:</strong> <span class="text-muted"><?= $isNoPay ? 'Rp ' . number_format(intval($activity['info']['paid'] ?? 0), 0, ',', '.') . ' (fee)' : 'Rp 0' ?></span></p>
                                                    <p class="text-sm mb-1"><strong>Status:</strong>
                                                        <?php if ($isNoPay) : ?>
                                                            <span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;">NO PAYMENT</span>
                                                        <?php else : ?>
                                                            <span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;">BELUM INPUT</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if ($isNoPay && !empty($activity['info']['catatan_admin'])) : ?>
                                                        <div style="margin-top:8px;padding:8px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:10px;color:#991b1b;">
                                                            <?= htmlspecialchars($activity['info']['catatan_admin']) ?>
                                                        </div>
                                                    <?php elseif (!$isNoPay) : ?>
                                                        <div style="margin-top:8px;padding:8px 12px;background:#fefce8;border:1px solid #fde68a;border-radius:6px;font-size:10px;color:#92400e;">
                                                            ⚠️ Invoice belum di-input. Silakan input melalui <strong>Laporan Kegiatan</strong>.
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-8 border-start ps-md-4 mt-4 mt-md-0">
                                                <h6 class="font-weight-bolder">Absensi Teknisi</h6>
                                                <?php if (empty($activity['pelaksanaan'])): ?>
                                                    <p class="text-sm text-muted">Belum ada data absensi.</p>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-borderless align-items-center mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder">Teknisi</th>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Mulai</th>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Selesai</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($activity['pelaksanaan'] as $task): ?>
                                                                    <tr style="border-bottom:1px solid #bfbfbf;">
                                                                        <td>
                                                                            <a class="text-sm font-weight-bold mb-0" href="list-kegiatan-teknisi.php?idTek=<?= htmlspecialchars($task['teknisi_id']); ?>">
                                                                                <?= htmlspecialchars($task['teknisi_name']) ?>
                                                                            </a>
                                                                        </td>
                                                                        <td>
                                                                            <p class="text-sm mb-0 <?= (!empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00') ? 'text-dark' : 'text-danger fw-bold'; ?>">
                                                                                <?= (!empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00') ? date("d/m/y H:i", strtotime($task['waktu_mulai'])) : 'n/a'; ?>
                                                                            </p>
                                                                        </td>
                                                                        <td>
                                                                            <p class="text-sm mb-0 <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? 'text-dark' : 'text-danger fw-bold'; ?>">
                                                                                <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? date("d/m/y H:i", strtotime($task['waktu_selesai'])) : 'n/a'; ?>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div id="noResultsMessage" class="alert alert-warning text-white text-center" style="display: none;">Pencarian tidak menemukan riwayat kegiatan.</div>
                    </div>
                </div>
            </div>

            <!-- Modal Input / Edit No SO -->
            <div class="modal fade" id="modalInputSO" tabindex="-1" aria-labelledby="modalInputSOLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                    <div class="modal-content" style="border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
                        <div class="modal-header pb-0 border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="modal-title fw-bold text-dark mb-0" id="modalInputSOLabel">
                                    <i class="fa-solid fa-receipt text-success me-2"></i>Nomor SO (Sales Order)
                                </h6>
                                <small class="text-secondary text-xxs" id="modalSOCustName"></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formInputSO" onsubmit="submitSOForm(event)">
                            <div class="modal-body px-4 py-3">
                                <input type="hidden" id="soKodeInput" name="kode">
                                
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-xs text-secondary text-uppercase mb-1">Nomor Sales Order (SO)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="fa-solid fa-hashtag text-muted"></i></span>
                                        <input type="text" class="form-control rounded-end-3" id="soNumberInput" name="no_so" placeholder="Contoh: 2608.SOL.06406" autocomplete="off" style="font-family: monospace; font-size: 13px; font-weight: 700;">
                                    </div>
                                    <div class="form-text text-xxs text-muted mt-1">Kosongkan jika ingin menghapus nomor SO.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                                <button type="button" class="btn btn-sm btn-light rounded-3 fw-bold px-3 py-2 text-xs" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-sm btn-primary rounded-3 fw-bold px-4 py-2 text-xs shadow-sm" id="btnSaveSO">
                                    <i class="fa-solid fa-check me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openInputSO(kode, currentSO, custName) {
        document.getElementById('soKodeInput').value = kode;
        document.getElementById('soNumberInput').value = currentSO || '';
        document.getElementById('modalSOCustName').textContent = custName ? 'Customer: ' + custName + ' (#' + kode + ')' : '#' + kode;
        
        const modalEl = document.getElementById('modalInputSO');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        
        setTimeout(() => {
            const input = document.getElementById('soNumberInput');
            input.focus();
            input.select();
        }, 400);
    }

    function submitSOForm(e) {
        e.preventDefault();
        const kode = document.getElementById('soKodeInput').value;
        const noSO = document.getElementById('soNumberInput').value.trim();
        const btn = document.getElementById('btnSaveSO');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...';
        
        const formData = new FormData();
        formData.append('kode', kode);
        formData.append('no_so', noSO);
        
        fetch('ajax-update-so.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            if (res.status === 'success') {
                const wrap = document.getElementById('wrap_so_' + kode);
                if (wrap) {
                    if (noSO) {
                        wrap.innerHTML = `
                            <button type="button" class="so-badge-btn" onclick="openInputSO('${kode}', '${escapeHtml(noSO)}', '')" title="Klik untuk edit No. SO">
                                <i class="fa-solid fa-receipt text-success me-1"></i>
                                <span class="so-txt" id="txt_so_${kode}">${escapeHtml(noSO)}</span>
                                <i class="fa-solid fa-pen ms-1 opacity-50" style="font-size: 8px;"></i>
                            </button>
                        `;
                    } else {
                        wrap.innerHTML = `
                            <button type="button" class="btn-so-add" onclick="openInputSO('${kode}', '', '')" title="Input Nomor SO">
                                <i class="fa-solid fa-plus"></i> Input SO
                            </button>
                        `;
                    }
                }

                const headerWrap = document.getElementById('header_so_' + kode);
                if (headerWrap) {
                    if (noSO) {
                        headerWrap.innerHTML = `
                            <span class="badge" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:0.02em;padding:3px 8px;">
                                <i class="fa-solid fa-receipt me-1"></i>${escapeHtml(noSO)}
                            </span>
                        `;
                    } else {
                        headerWrap.innerHTML = '';
                    }
                }
                
                const modalEl = document.getElementById('modalInputSO');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            } else {
                alert(res.message || 'Terjadi kesalahan');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Gagal menghubungi server: ' + err.message);
        });
    }

    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            let visibleCards = 0;
            $('#activityList .activity-card').each(function() {
                let cardText = $(this).text().toLowerCase();
                if (cardText.includes(searchTerm)) {
                    $(this).slideDown('fast');
                    visibleCards++;
                } else {
                    $(this).slideUp('fast');
                }
            });
            if (visibleCards > 0) { $('#noResultsMessage').hide(); } else { $('#noResultsMessage').show(); }
        });
    });
    </script>
</body>
</html>