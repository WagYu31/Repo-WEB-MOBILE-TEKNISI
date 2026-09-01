<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "conn.php";

$kode_transaksi = '';
$result = null;

if (isset($_POST['kode_transaksi'])) {
    $kode_transaksi = $_POST['kode_transaksi'];

    $query = "SELECT pk.id, t.nama AS nama_teknisi, pk.waktu_mulai, pk.waktu_selesai,
                     k.kegiatan, k.jadwal, k.invoice, k.garansi, k.keterangan_garansi,
                     c.nama AS nama_customer
              FROM pelaksanaan_kegiatan pk
              JOIN teknisi t ON pk.teknisi_id = t.id
              JOIN kegiatan k ON pk.kegiatan_id = k.id
              LEFT JOIN customer c ON k.customer_id = c.id
              WHERE pk.kode = ? AND pk.deleted_at IS NULL
              ORDER BY t.nama ASC, pk.waktu_mulai ASC";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        echo "<div class='alert alert-danger'>Prepare Error: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    } else {
        mysqli_stmt_bind_param($stmt, "s", $kode_transaksi);
        $exec = mysqli_stmt_execute($stmt);
        if (!$exec) {
            echo "<div class='alert alert-danger'>Execute Error: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "</div>";
        }
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            echo "<div class='alert alert-danger'>Result Error: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "</div>";
        }
    }
}

// Get defaults from first row
$default_invoice = '';
$default_garansi = '';
$default_ket_garansi = '';
$customer_name = '';
if ($result && mysqli_num_rows($result) > 0) {
    $first_row = mysqli_fetch_assoc($result);
    $default_invoice = $first_row['invoice'];
    $default_garansi = $first_row['garansi'];
    $default_ket_garansi = $first_row['keterangan_garansi'];
    $customer_name = $first_row['nama_customer'] ?? '';
    mysqli_data_seek($result, 0);
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.inv-form-wrap {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    color: #1e293b;
}

/* Header context chip */
.inv-header-meta {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 12px; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
}
.inv-meta-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.inv-code-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #eef2ff; color: #4338ca; font-weight: 800;
    font-size: 12px; padding: 4px 10px; border-radius: 8px; border: 1px solid #c7d2fe;
}
.inv-cust-chip {
    font-size: 13px; font-weight: 700; color: #334155;
    display: inline-flex; align-items: center; gap: 6px;
}
.inv-garansi-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 700; color: #92400e;
    background: #fef3c7; border: 1px solid #fde68a;
    padding: 4px 10px; border-radius: 8px;
}

/* Form input cards */
.inv-card-section {
    background: #ffffff; border: 1px solid #e2e8f0;
    border-radius: 14px; padding: 18px; margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.inv-grid-2 {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
}
.inv-form-group {
    margin-bottom: 12px;
}
.inv-form-group:last-child { margin-bottom: 0; }
.inv-label {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px;
}
.inv-label i { font-size: 13px; color: #6366f1; }
.inv-input-box {
    position: relative; display: flex; align-items: center;
}
.inv-input-box i.input-icon {
    position: absolute; left: 14px; font-size: 14px; color: #94a3b8; pointer-events: none;
}
.inv-input {
    width: 100%; padding: 11px 14px 11px 38px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13.5px; color: #0f172a; font-weight: 600;
    background: #f8fafc; transition: all 0.2s ease;
    font-family: inherit;
}
.inv-input:focus {
    border-color: #6366f1; background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(99,102,241,0.12);
    outline: none;
}
.inv-input.nominal-highlight {
    font-size: 15px; font-weight: 800; color: #4338ca;
}
.inv-feedback { font-size: 11.5px; font-weight: 600; margin-top: 5px; }

/* Fee Toggle Card */
.inv-toggle-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: #f8fafc; border: 1.5px dashed #cbd5e1;
    border-radius: 12px; margin-top: 10px; transition: all 0.2s;
}
.inv-toggle-card:hover { background: #f1f5f9; border-color: #94a3b8; }
.inv-toggle-info { display: flex; align-items: center; gap: 10px; }
.inv-toggle-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: #e0e7ff; color: #4f46e5;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.inv-toggle-title { font-size: 13px; font-weight: 700; color: #1e293b; }
.inv-toggle-sub { font-size: 11px; color: #64748b; font-weight: 500; }

/* Teknisi List Section */
.inv-tek-section {
    background: #ffffff; border: 1px solid #e2e8f0;
    border-radius: 14px; padding: 18px; margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.inv-tek-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1.5px solid #f1f5f9;
    flex-wrap: wrap; gap: 10px;
}
.inv-tek-title {
    font-size: 12.5px; font-weight: 800; color: #334155;
    text-transform: uppercase; letter-spacing: 0.05em;
    display: flex; align-items: center; gap: 8px;
}
.inv-tek-title i { font-size: 15px; color: #6366f1; }
.inv-tek-actions { display: flex; align-items: center; gap: 8px; }
.inv-btn-quick {
    background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;
    font-size: 11.5px; font-weight: 700; padding: 5px 12px;
    border-radius: 8px; cursor: pointer; transition: all 0.15s;
}
.inv-btn-quick:hover { background: #e2e8f0; color: #1e293b; }
.inv-count-badge {
    background: #6366f1; color: #ffffff; font-size: 11px; font-weight: 800;
    padding: 3px 9px; border-radius: 20px;
}

/* Distribution summary alert */
.inv-distrib-summary {
    display: none; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
    border: 1.5px solid #86efac; border-radius: 12px;
    padding: 12px 16px; margin-bottom: 14px; font-size: 12.5px; color: #166534;
}
.inv-distrib-left { display: flex; align-items: center; gap: 8px; font-weight: 700; }
.inv-distrib-left i { font-size: 16px; color: #15803d; }
.inv-distrib-formula { font-size: 11.5px; color: #15803d; font-weight: 600; }

/* Technician List Cards */
.inv-tek-list {
    display: flex; flex-direction: column; gap: 10px;
    max-height: 400px; overflow-y: auto; padding-right: 4px;
}
.inv-tek-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border: 1.5px solid #e2e8f0;
    border-radius: 12px; background: #ffffff;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer; position: relative; user-select: none;
}
.inv-tek-card:hover {
    border-color: #a5b4fc; background: #fafbff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.06);
}
.inv-tek-card.active-selected {
    border-color: #6366f1; background: #f5f3ff;
    box-shadow: 0 2px 8px rgba(99,102,241,0.08);
}
.inv-tek-card.inv-late {
    border-color: #fdba74; background: #fffaf5;
}
.inv-tek-card.inv-late.active-selected {
    border-color: #f97316; background: #fff7ed;
}

/* Card Left Section */
.inv-card-left {
    display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;
}
.inv-check {
    width: 20px; height: 20px; border-radius: 6px;
    border: 2px solid #cbd5e1; cursor: pointer;
    flex-shrink: 0; accent-color: #6366f1;
}
.inv-avatar {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4338ca; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800; flex-shrink: 0;
}
.inv-tek-card.inv-late .inv-avatar {
    background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #c2410c;
}
.inv-card-info { min-width: 0; }
.inv-name-row {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;
}
.inv-tek-name {
    font-size: 13.5px; font-weight: 700; color: #0f172a;
}
.inv-role-badge {
    font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: 0.03em;
}
.role-survey { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
.role-pasang-baru { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.role-service { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

.inv-time-pills {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11.5px; color: #64748b; font-weight: 600;
}
.inv-time-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 11px;
}
.inv-time-pill.pill-absen {
    background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
}
.inv-time-pill.pill-noabsen {
    background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
}
.inv-late-tag {
    background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6;
    font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 5px;
}

/* Card Right Section (Fee Badge) */
.inv-card-right {
    display: flex; flex-direction: column; align-items: flex-end;
    margin-left: 14px; flex-shrink: 0;
}
.inv-fee-badge {
    font-size: 13.5px; font-weight: 800; color: #047857;
    background: #ecfdf5; border: 1.5px solid #a7f3d0;
    padding: 6px 12px; border-radius: 10px;
    font-variant-numeric: tabular-nums; box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    display: none; align-items: center; gap: 4px;
}
.inv-fee-label {
    font-size: 10px; font-weight: 600; color: #64748b; margin-top: 2px;
}

/* Submit Action Button */
.inv-submit-container {
    margin-top: 10px;
}
.inv-submit-btn {
    width: 100%; padding: 14px 20px; border: none;
    border-radius: 12px; font-size: 14.5px; font-weight: 800;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #ffffff; cursor: pointer;
    box-shadow: 0 4px 16px rgba(79,70,229,0.3);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: inherit;
}
.inv-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(79,70,229,0.4);
    background: linear-gradient(135deg, #4338ca, #6d28d9);
}
.inv-submit-btn:disabled {
    opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none;
}

/* Responsive */
@media (max-width: 768px) {
    .inv-grid-2 { grid-template-columns: 1fr; }
    .inv-tek-card { flex-direction: column; align-items: flex-start; gap: 10px; }
    .inv-card-right { margin-left: 32px; align-items: flex-start; }
}
</style>

<div class="inv-form-wrap">
    <form action="submit_invoice.php" method="post" id="formSubmitInvoice">
        <input type="hidden" name="kode_transaksi" value="<?php echo htmlspecialchars($kode_transaksi); ?>">

        <!-- Header Info Bar -->
        <div class="inv-header-meta">
            <div class="inv-meta-left">
                <span class="inv-code-chip"><i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($kode_transaksi) ?></span>
                <?php if (!empty($customer_name)) : ?>
                    <span class="inv-cust-chip"><i class="fa-solid fa-user-tie" style="color:#6366f1;"></i> <?= htmlspecialchars($customer_name) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($default_garansi) || !empty($default_ket_garansi)) : ?>
                <span class="inv-garansi-chip">
                    <i class="fa-solid fa-shield-halved"></i>
                    Garansi: <?= htmlspecialchars($default_garansi) ?><?= !empty($default_ket_garansi) ? ' (' . htmlspecialchars($default_ket_garansi) . ')' : '' ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Section 1: Informasi Invoice -->
        <div class="inv-card-section">
            <div class="inv-grid-2">
                <div class="inv-form-group">
                    <label class="inv-label"><i class="fa-solid fa-receipt"></i> Kode Invoice</label>
                    <div class="inv-input-box">
                        <i class="fa-solid fa-file-invoice input-icon"></i>
                        <input type="text" class="inv-input" placeholder="Contoh: 2607.INV.05993" id="kodeInvoice" name="kode_invoice" value="<?php echo htmlspecialchars((strtolower($default_invoice) == 'no') ? '' : $default_invoice); ?>" required autocomplete="off">
                    </div>
                    <div id="invoiceFeedback" class="inv-feedback"></div>
                </div>
                <div class="inv-form-group">
                    <label class="inv-label"><i class="fa-solid fa-money-bill-wave"></i> Nominal Invoice</label>
                    <div class="inv-input-box">
                        <i class="fa-solid fa-rupiah-sign input-icon"></i>
                        <input type="text" class="inv-input nominal-highlight" id="nominalInvoice" placeholder="Rp 0" name="nominal_invoice" oninput="formatRupiah(this); calculateShares();" required>
                    </div>
                </div>
            </div>

            <div class="inv-grid-2" style="margin-top: 12px;">
                <div class="inv-form-group">
                    <label class="inv-label"><i class="fa-regular fa-calendar-check"></i> Tanggal Invoice</label>
                    <div class="inv-input-box">
                        <i class="fa-regular fa-calendar input-icon"></i>
                        <input type="date" class="inv-input" id="tanggalInvoice" name="tanggal_invoice" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="inv-form-group">
                    <label class="inv-label"><i class="fa-regular fa-circle-check"></i> Tanggal Lunas <span style="font-weight:500;color:#94a3b8;text-transform:none;letter-spacing:0;font-size:11px;">(opsional)</span></label>
                    <div class="inv-input-box">
                        <i class="fa-solid fa-calendar-day input-icon"></i>
                        <input type="date" class="inv-input" id="tanggalLunas" name="tanggal_lunas" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>

            <!-- Fee Khusus Switch -->
            <div class="inv-toggle-card">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <div class="inv-toggle-title">Tambahkan Fee Teknisi Standar</div>
                        <div class="inv-toggle-sub">Gunakan tarif fee standar dari master data jika tanpa invoice</div>
                    </div>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="feeToggle" name="tambah_fee" style="width:42px;height:22px;cursor:pointer;">
                </div>
            </div>
        </div>

        <!-- Section 2: Pilih Teknisi Pelaksana -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="inv-tek-section">
            <div class="inv-tek-head">
                <div class="inv-tek-title">
                    <i class="fa-solid fa-users-gear"></i> Pilih Teknisi Pelaksana
                    <span class="inv-count-badge" id="selectedCountBadge">0 Terpilih</span>
                </div>
                <div class="inv-tek-actions">
                    <button type="button" class="inv-btn-quick" onclick="toggleAllTechs(true)"><i class="fa-solid fa-check-double"></i> Pilih Semua</button>
                    <button type="button" class="inv-btn-quick" onclick="toggleAllTechs(false)"><i class="fa-solid fa-xmark"></i> Batal Semua</button>
                </div>
            </div>

            <!-- Live Distribution Summary -->
            <div class="inv-distrib-summary" id="distribSummaryBox">
                <div class="inv-distrib-left">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="distribTotalText">Total Dibagikan: Rp 0</span>
                </div>
                <div class="inv-distrib-formula" id="distribFormulaText">
                    Skema: Proporsional
                </div>
            </div>

            <!-- Teknisi List Cards -->
            <div class="inv-tek-list">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $waktu_mulai_valid = ($row['waktu_mulai'] != NULL && $row['waktu_mulai'] != '0000-00-00 00:00:00');
                    $waktu_selesai_valid = ($row['waktu_selesai'] != NULL && $row['waktu_selesai'] != '0000-00-00 00:00:00');
                    $jadwal_valid = ($row['jadwal'] != NULL && $row['jadwal'] != '0000-00-00 00:00:00');
                    $isLate = false;
                    $lateMinutes = 0;

                    if ($waktu_mulai_valid && $jadwal_valid) {
                        try {
                            $waktu_mulai_dt = new DateTime($row['waktu_mulai']);
                            $jadwal_dt = new DateTime($row['jadwal']);
                            if ($waktu_mulai_dt > $jadwal_dt) {
                                $diff_minutes = ($waktu_mulai_dt->getTimestamp() - $jadwal_dt->getTimestamp()) / 60;
                                if ($diff_minutes > 30) {
                                    $isLate = true;
                                    $lateMinutes = round($diff_minutes);
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error parsing date: " . $e->getMessage());
                        }
                    }

                    $initials = strtoupper(substr($row['nama_teknisi'], 0, 1));
                    $cleanRole = strtolower(trim($row['kegiatan']));
                    $roleClass = 'role-survey';
                    if (strpos($cleanRole, 'pasang') !== false) {
                        $roleClass = 'role-pasang-baru';
                    } elseif (strpos($cleanRole, 'service') !== false) {
                        $roleClass = 'role-service';
                    }
                    ?>
                    <div class="inv-tek-card active-selected <?= $isLate ? 'inv-late' : '' ?>" onclick="handleCardClick(event, this)">
                        <div class="inv-card-left">
                            <input type="checkbox" name="selected_kegiatan[]" value="<?= $row['id'].'|'.$row['kegiatan'] ?>" class="inv-check inv-tek-check" checked onchange="syncCardState(this)">
                            <div class="inv-avatar"><?= $initials ?></div>
                            <div class="inv-card-info">
                                <div class="inv-name-row">
                                    <span class="inv-tek-name"><?= htmlspecialchars($row['nama_teknisi']) ?></span>
                                    <span class="inv-role-badge <?= $roleClass ?>"><?= ucwords(htmlspecialchars($row['kegiatan'])) ?></span>
                                    <?php if ($isLate) : ?>
                                        <span class="inv-late-tag"><i class="fa-solid fa-clock-rotate-left"></i> Terlambat <?= $lateMinutes ?>m</span>
                                    <?php endif; ?>
                                </div>
                                <div class="inv-time-pills">
                                    <?php if ($jadwal_valid) : ?>
                                        <span class="inv-time-pill"><i class="fa-regular fa-calendar"></i> <?= date("d/m H:i", strtotime($row['jadwal'])) ?></span>
                                    <?php endif; ?>

                                    <?php if ($waktu_mulai_valid && $waktu_selesai_valid) : ?>
                                        <span class="inv-time-pill pill-absen">
                                            <i class="fa-solid fa-check"></i> <?= date("d/m H:i", strtotime($row['waktu_mulai'])) ?> - <?= date("H:i", strtotime($row['waktu_selesai'])) ?>
                                        </span>
                                    <?php elseif ($waktu_mulai_valid) : ?>
                                        <span class="inv-time-pill pill-absen">
                                            <i class="fa-solid fa-right-to-bracket"></i> In: <?= date("d/m H:i", strtotime($row['waktu_mulai'])) ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="inv-time-pill pill-noabsen">
                                            <i class="fa-solid fa-xmark"></i> Belum Absen
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="inv-card-right">
                            <div class="inv-fee-badge"><i class="fa-solid fa-wallet"></i> <span class="fee-val">Rp 0</span></div>
                            <span class="inv-fee-label">Hak Fee Teknisi</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php elseif (isset($_POST['kode_transaksi'])): ?>
            <div style="text-align:center;padding:36px;color:#94a3b8;background:#f8fafc;border-radius:14px;border:1px dashed #cbd5e1;margin-bottom:18px;">
                <i class="fa-solid fa-inbox" style="font-size:36px;opacity:0.4;margin-bottom:10px;display:block;"></i>
                <p style="font-size:13.5px;font-weight:700;margin:0;color:#64748b;">Tidak ada data pelaksanaan teknisi untuk transaksi ini.</p>
            </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <div class="inv-submit-container">
            <button type="submit" class="inv-submit-btn" id="btnSubmitInvoice">
                <i class="fa-solid fa-paper-plane"></i> <span id="submitBtnText">Submit Invoice</span>
            </button>
        </div>
    </form>
</div>

<script>
function formatRupiah(input) {
    var nominal = input.value.replace(/\D/g, "");
    nominal = nominal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    input.value = nominal ? ("Rp " + nominal) : "";
}

function handleCardClick(e, card) {
    // If the click was directly on the checkbox, let native change event handle it
    if (e.target.classList.contains('inv-tek-check')) {
        return;
    }
    const cb = card.querySelector('.inv-tek-check');
    if (cb) {
        cb.checked = !cb.checked;
        syncCardState(cb);
    }
}

function syncCardState(cb) {
    const card = cb.closest('.inv-tek-card');
    if (card) {
        if (cb.checked) {
            card.classList.add('active-selected');
        } else {
            card.classList.remove('active-selected');
        }
    }
    updateSelectedCount();
    calculateShares();
}

function toggleAllTechs(check) {
    document.querySelectorAll('.inv-tek-check').forEach(cb => {
        cb.checked = check;
        const card = cb.closest('.inv-tek-card');
        if (card) {
            if (check) card.classList.add('active-selected');
            else card.classList.remove('active-selected');
        }
    });
    updateSelectedCount();
    calculateShares();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.inv-tek-check:checked').length;
    const total = document.querySelectorAll('.inv-tek-check').length;
    const badge = document.getElementById('selectedCountBadge');
    if (badge) {
        badge.textContent = checked + ' / ' + total + ' Terpilih';
    }

    const btnText = document.getElementById('submitBtnText');
    if (btnText) {
        btnText.textContent = 'Submit Invoice (' + checked + ' Teknisi Terpilih)';
    }
}

function calculateShares() {
    const nominalInput = document.getElementById('nominalInvoice');
    if (!nominalInput) return;

    const rawVal = nominalInput.value.replace(/\D/g, '');
    const nominal = parseInt(rawVal) || 0;

    const checkedBoxes = [];
    document.querySelectorAll('.inv-tek-check:checked').forEach(cb => {
        const parts = cb.value.split('|');
        if (parts.length === 2) {
            checkedBoxes.push({
                element: cb,
                id: parts[0],
                type: parts[1].toLowerCase().trim()
            });
        }
    });

    const summaryBox = document.getElementById('distribSummaryBox');
    const totalText = document.getElementById('distribTotalText');
    const formulaText = document.getElementById('distribFormulaText');

    // Reset share badge for all cards first
    document.querySelectorAll('.inv-fee-badge').forEach(el => {
        el.style.display = 'none';
        el.querySelector('.fee-val').textContent = 'Rp 0';
    });
    document.querySelectorAll('.inv-fee-label').forEach(el => el.style.display = 'none');

    if (nominal <= 0 || checkedBoxes.length === 0) {
        if (summaryBox) summaryBox.style.display = 'none';
        return;
    }

    const survey_ids = checkedBoxes.filter(x => x.type === 'survey');
    const pasang_baru_ids = checkedBoxes.filter(x => x.type === 'pasang baru');
    const service_ids = checkedBoxes.filter(x => x.type === 'service');

    const survey_count = survey_ids.length;
    const pasang_baru_count = pasang_baru_ids.length;
    const service_count = service_ids.length;

    function formatRp(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    let formulaDesc = 'Dibagi Rata';

    // Calculation Logic matching submit_invoice.php
    if (survey_count > 0 && pasang_baru_count === 0 && service_count === 0) {
        const share = nominal / survey_count;
        formulaDesc = '100% Survey (Rata)';
        survey_ids.forEach(x => showShare(x.element, share));
    } else if (survey_count === 0 && pasang_baru_count > 0 && service_count === 0) {
        const share = nominal / pasang_baru_count;
        formulaDesc = '100% Pasang Baru (Rata)';
        pasang_baru_ids.forEach(x => showShare(x.element, share));
    } else if (survey_count === 0 && pasang_baru_count === 0 && service_count > 0) {
        const share = nominal / service_count;
        formulaDesc = '100% Service (Rata)';
        service_ids.forEach(x => showShare(x.element, share));
    }
    // Mix of survey and pasang baru
    else if (survey_count > 0 && pasang_baru_count > 0 && service_count === 0) {
        const survey_share = (0.1 * nominal) / survey_count;
        const pasang_baru_share = (0.9 * nominal) / pasang_baru_count;
        formulaDesc = '10% Survey (' + formatRp(0.1 * nominal) + ') • 90% Pasang Baru (' + formatRp(0.9 * nominal) + ')';
        survey_ids.forEach(x => showShare(x.element, survey_share));
        pasang_baru_ids.forEach(x => showShare(x.element, pasang_baru_share));
    }
    // Mix of all three
    else if (survey_count > 0 && pasang_baru_count > 0 && service_count > 0) {
        const survey_share = (0.05 * nominal) / survey_count;
        const pasang_baru_share = (0.85 * nominal) / pasang_baru_count;
        const service_share = (0.10 * nominal) / service_count;
        formulaDesc = '5% Survey • 85% Pasang Baru • 10% Service';
        survey_ids.forEach(x => showShare(x.element, survey_share));
        pasang_baru_ids.forEach(x => showShare(x.element, pasang_baru_share));
        service_ids.forEach(x => showShare(x.element, service_share));
    }
    // Other combinations
    else {
        const share = nominal / checkedBoxes.length;
        formulaDesc = 'Dibagi Rata (' + checkedBoxes.length + ' Teknisi)';
        checkedBoxes.forEach(x => showShare(x.element, share));
    }

    if (summaryBox && totalText && formulaText) {
        totalText.textContent = 'Total Dialokasikan: ' + formatRp(nominal) + ' (' + checkedBoxes.length + ' Teknisi)';
        formulaText.textContent = formulaDesc;
        summaryBox.style.display = 'flex';
    }

    function showShare(checkboxEl, amount) {
        const card = checkboxEl.closest('.inv-tek-card');
        if (card) {
            const feeBadge = card.querySelector('.inv-fee-badge');
            const feeLabel = card.querySelector('.inv-fee-label');
            if (feeBadge) {
                feeBadge.querySelector('.fee-val').textContent = formatRp(amount);
                feeBadge.style.display = 'inline-flex';
            }
            if (feeLabel) feeLabel.style.display = 'block';
        }
    }
}

// Initial calculation and counter update
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    calculateShares();
});
setTimeout(() => {
    updateSelectedCount();
    calculateShares();
}, 200);

(function() {
    const input = document.getElementById('kodeInvoice');
    const feedback = document.getElementById('invoiceFeedback');
    const btn = document.getElementById('btnSubmitInvoice');
    const currentKode = '<?= htmlspecialchars($kode_transaksi) ?>';
    let debounceTimer = null;

    if (input) {
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 2) {
                feedback.innerHTML = '';
                btn.disabled = false;
                input.style.borderColor = '';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetch(`check_invoice_code.php?kode_invoice=${encodeURIComponent(val)}&current_kode=${encodeURIComponent(currentKode)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.exists) {
                            feedback.innerHTML = `<span style="color:#dc2626;"><i class="fa-solid fa-circle-exclamation"></i> ${data.message}</span>`;
                            btn.disabled = true;
                            input.style.borderColor = '#dc2626';
                        } else {
                            feedback.innerHTML = `<span style="color:#059669;"><i class="fa-solid fa-circle-check"></i> Kode tersedia</span>`;
                            btn.disabled = false;
                            input.style.borderColor = '#059669';
                        }
                    })
                    .catch(() => {
                        feedback.innerHTML = '';
                        btn.disabled = false;
                        input.style.borderColor = '';
                    });
            }, 400);
        });

        input.closest('form').addEventListener('submit', function(e) {
            if (btn.disabled) {
                e.preventDefault();
                alert('Kode Invoice sudah digunakan! Silakan gunakan kode yang berbeda.');
            }
        });
    }
})();
</script>