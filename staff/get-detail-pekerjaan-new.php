<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "conn.php";

$kode_transaksi = '';
$result = null;

if (isset($_POST['kode_transaksi'])) {
    $kode_transaksi = $_POST['kode_transaksi'];

    $query = "SELECT pk.id, t.nama AS nama_teknisi, pk.waktu_mulai, pk.waktu_selesai,
                     k.kegiatan, k.jadwal, k.invoice, k.garansi, k.keterangan_garansi
              FROM pelaksanaan_kegiatan pk
              JOIN teknisi t ON pk.teknisi_id = t.id
              JOIN kegiatan k ON pk.kegiatan_id = k.id
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
if ($result && mysqli_num_rows($result) > 0) {
    $first_row = mysqli_fetch_assoc($result);
    $default_invoice = $first_row['invoice'];
    $default_garansi = $first_row['garansi'];
    $default_ket_garansi = $first_row['keterangan_garansi'];
    mysqli_data_seek($result, 0);
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.inv-form-wrap {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
}

/* Info badges */
.inv-info-row {
    display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
}
.inv-info-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 600;
    background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;
}
.inv-info-badge i { font-size: 14px; color: #6366f1; }
.inv-info-badge strong { color: #1e293b; font-weight: 700; }

/* Form inputs */
.inv-form-group {
    margin-bottom: 16px;
}
.inv-form-label {
    display: block; font-size: 11px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;
}
.inv-form-input {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: 13px; color: #1e293b;
    background: #f8fafc; transition: all 0.2s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.inv-form-input:focus {
    border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    outline: none; background: #fff;
}
.inv-form-input::placeholder { color: #94a3b8; }
.inv-form-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}

/* Toggle switch */
.inv-toggle-wrap {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 10px; margin-bottom: 20px;
}
.inv-toggle-wrap label {
    font-size: 13px; font-weight: 600; color: #475569; margin: 0; cursor: pointer;
}

/* Section heading */
.inv-section-label {
    font-size: 11px; font-weight: 800; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.06em;
    margin-bottom: 12px; padding-bottom: 8px;
    border-bottom: 2px solid #f1f5f9;
    display: flex; align-items: center; gap: 6px;
}
.inv-section-label i { font-size: 14px; }

/* Teknisi cards */
.inv-tek-grid {
    display: flex; flex-direction: column; gap: 8px;
    max-height: 360px; overflow-y: auto;
    padding-right: 4px;
}
.inv-tek-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border: 1.5px solid #e2e8f0;
    border-radius: 12px; background: #fff;
    transition: all 0.2s; cursor: pointer;
}
.inv-tek-card:hover {
    border-color: #c7d2fe; background: #fafafe;
}
.inv-tek-card.inv-late {
    border-color: #fed7aa; background: #fffbeb;
}
.inv-tek-card.inv-late:hover {
    border-color: #fdba74; background: #fff7ed;
}

/* Checkbox styling */
.inv-tek-check {
    width: 20px; height: 20px; border-radius: 6px;
    border: 2px solid #cbd5e1; cursor: pointer;
    flex-shrink: 0; accent-color: #6366f1;
}
.inv-tek-card.inv-late .inv-tek-check {
    border-color: #f59e0b;
}

/* Avatar */
.inv-tek-avatar {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4338ca; display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; flex-shrink: 0;
}
.inv-tek-card.inv-late .inv-tek-avatar {
    background: linear-gradient(135deg, #ffedd5, #fed7aa);
    color: #c2410c;
}

/* Info */
.inv-tek-info { flex: 1; min-width: 0; }
.inv-tek-name {
    font-size: 13px; font-weight: 700; color: #1e293b;
    margin-bottom: 3px;
}
.inv-tek-card.inv-late .inv-tek-name { color: #9a3412; }

.inv-tek-meta {
    display: flex; gap: 6px; flex-wrap: wrap;
}
.inv-tek-tag {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 6px;
}
.inv-tek-tag.tag-kegiatan {
    background: #e0e7ff; color: #3730a3;
}
.inv-tek-card.inv-late .inv-tek-tag.tag-kegiatan {
    background: #ffedd5; color: #9a3412;
}

/* Time badges */
.inv-tek-times {
    display: flex; gap: 6px; align-items: center;
    flex-shrink: 0;
}
.inv-tek-time {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 700; padding: 4px 8px;
    border-radius: 6px;
}
.inv-tek-time i { font-size: 10px; }
.inv-tek-time.t-request { background: #f1f5f9; color: #475569; }
.inv-tek-time.t-start { background: #ecfdf5; color: #059669; }
.inv-tek-time.t-end { background: #fef2f2; color: #dc2626; }
.inv-tek-time.t-absent { background: #fef2f2; color: #dc2626; font-style: italic; }

.inv-tek-card.inv-late .inv-tek-time.t-request,
.inv-tek-card.inv-late .inv-tek-time.t-start,
.inv-tek-card.inv-late .inv-tek-time.t-end {
    background: #fff7ed; color: #c2410c;
}

/* Late indicator */
.inv-late-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.04em; padding: 3px 8px; border-radius: 6px;
    background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
}

/* Submit button */
.inv-submit-btn {
    width: 100%; padding: 12px; border: none;
    border-radius: 12px; font-size: 14px; font-weight: 700;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; cursor: pointer;
    box-shadow: 0 4px 14px rgba(99,102,241,0.25);
    transition: all 0.2s; margin-top: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.inv-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(99,102,241,0.35);
}
.inv-submit-btn:disabled {
    opacity: 0.5; cursor: not-allowed; transform: none;
}

/* Invoice feedback */
.inv-feedback { font-size: 11px; margin-top: 4px; font-weight: 600; }

/* Responsive */
@media (max-width: 768px) {
    .inv-form-row { grid-template-columns: 1fr; }
    .inv-tek-card { flex-wrap: wrap; }
    .inv-tek-times { width: 100%; margin-top: 6px; padding-left: 68px; }
}
</style>

<div class="inv-form-wrap">
    <form action="submit_invoice.php" method="post">
        <input type="hidden" name="kode_transaksi" value="<?php echo htmlspecialchars($kode_transaksi); ?>">

        <!-- Info badges -->
        <?php if (!empty($default_garansi) || !empty($default_ket_garansi)) : ?>
        <div class="inv-info-row">
            <?php if (!empty($default_garansi)) : ?>
            <span class="inv-info-badge">
                <i class="fa-solid fa-shield-halved"></i>
                Garansi: <strong><?= htmlspecialchars($default_garansi) ?></strong>
            </span>
            <?php endif; ?>
            <?php if (!empty($default_ket_garansi)) : ?>
            <span class="inv-info-badge">
                <i class="fa-solid fa-circle-info"></i>
                Ket: <strong><?= htmlspecialchars($default_ket_garansi) ?></strong>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Form fields -->
        <div class="inv-form-row">
            <div class="inv-form-group">
                <label class="inv-form-label">Kode Invoice</label>
                <input type="text" class="inv-form-input" placeholder="Masukkan kode invoice" id="kodeInvoice" name="kode_invoice" value="<?php echo htmlspecialchars((strtolower($default_invoice) == 'no') ? '' : $default_invoice); ?>" required autocomplete="off">
                <div id="invoiceFeedback" class="inv-feedback"></div>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Nominal Invoice</label>
                <input type="text" class="inv-form-input" id="nominalInvoice" placeholder="Rp 0" name="nominal_invoice" oninput="formatRupiah(this)" required>
            </div>
        </div>

        <div class="inv-form-row">
            <div class="inv-form-group">
                <label class="inv-form-label">Tanggal Invoice</label>
                <input type="date" class="inv-form-input" id="tanggalInvoice" name="tanggal_invoice" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Tanggal Lunas <span style="font-weight:500;color:#94a3b8;text-transform:none;letter-spacing:0">(opsional)</span></label>
                <input type="date" class="inv-form-input" id="tanggalLunas" name="tanggal_lunas" max="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Fee toggle -->
        <div class="inv-toggle-wrap">
            <input class="form-check-input" type="checkbox" role="switch" id="feeToggle" name="tambah_fee" style="width:36px;height:20px;cursor:pointer;">
            <label for="feeToggle">Tambahkan Fee Teknisi</label>
        </div>

        <!-- Teknisi list -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="inv-section-label">
            <i class="fa-solid fa-users"></i> Pilih Teknisi Pelaksana
        </div>
        <div class="inv-tek-grid">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                $checkboxDisabled = false;
                $waktu_mulai_valid = ($row['waktu_mulai'] != NULL && $row['waktu_mulai'] != '0000-00-00 00:00:00');
                $jadwal_valid = ($row['jadwal'] != NULL && $row['jadwal'] != '0000-00-00 00:00:00');
                $isLate = false;

                if ($waktu_mulai_valid && $jadwal_valid) {
                    try {
                        $waktu_mulai_dt = new DateTime($row['waktu_mulai']);
                        $jadwal_dt = new DateTime($row['jadwal']);
                        if ($waktu_mulai_dt > $jadwal_dt) {
                            $diff_minutes = ($waktu_mulai_dt->getTimestamp() - $jadwal_dt->getTimestamp()) / 60;
                            if ($diff_minutes > 60) {
                                $checkboxDisabled = true;
                                $isLate = true;
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error parsing date: " . $e->getMessage() . " for row id " . $row['id']);
                    }
                }
                $initials = strtoupper(substr($row['nama_teknisi'], 0, 1));
                $cardClass = $isLate ? 'inv-tek-card inv-late' : 'inv-tek-card';
                ?>
                <label class="<?= $cardClass ?>">
                    <input type="checkbox" name="selected_kegiatan[]" value="<?= $row['id'].'|'.$row['kegiatan'] ?>" class="inv-tek-check" <?= $checkboxDisabled ? '' : '' ?>>
                    <span class="inv-tek-avatar"><?= $initials ?></span>
                    <div class="inv-tek-info">
                        <div class="inv-tek-name">
                            <?= htmlspecialchars($row['nama_teknisi']) ?>
                            <?php if ($isLate) : ?>
                                <span class="inv-late-badge"><i class="fa-solid fa-triangle-exclamation"></i> Terlambat</span>
                            <?php endif; ?>
                        </div>
                        <div class="inv-tek-meta">
                            <span class="inv-tek-tag tag-kegiatan"><?= ucwords(htmlspecialchars($row['kegiatan'])) ?></span>
                        </div>
                    </div>
                    <div class="inv-tek-times">
                        <span class="inv-tek-time t-request">
                            <i class="fa-regular fa-calendar"></i>
                            <?= $jadwal_valid ? date("d/m H:i", strtotime($row['jadwal'])) : 'N/A' ?>
                        </span>
                        <?php if ($waktu_mulai_valid) : ?>
                            <span class="inv-tek-time t-start">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <?= date("d/m H:i", strtotime($row['waktu_mulai'])) ?>
                            </span>
                        <?php else : ?>
                            <span class="inv-tek-time t-absent">
                                <i class="fa-solid fa-xmark"></i> No Absen
                            </span>
                        <?php endif; ?>
                        <?php
                        $waktu_selesai_valid = ($row['waktu_selesai'] != NULL && $row['waktu_selesai'] != '0000-00-00 00:00:00');
                        if ($waktu_selesai_valid) : ?>
                            <span class="inv-tek-time t-end">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <?= date("d/m H:i", strtotime($row['waktu_selesai'])) ?>
                            </span>
                        <?php else : ?>
                            <span class="inv-tek-time t-absent">
                                <i class="fa-solid fa-xmark"></i> No Absen
                            </span>
                        <?php endif; ?>
                    </div>
                </label>
            <?php endwhile; ?>
        </div>
        <?php elseif (isset($_POST['kode_transaksi'])): ?>
            <div style="text-align:center;padding:30px;color:#94a3b8;">
                <i class="fa-solid fa-inbox" style="font-size:32px;opacity:0.3;margin-bottom:10px;display:block;"></i>
                <p style="font-size:13px;font-weight:600;margin:0;">Tidak ada pelaksanaan kegiatan ditemukan.</p>
            </div>
        <?php endif; ?>

        <button type="submit" class="inv-submit-btn" id="btnSubmitInvoice">
            <i class="fa-solid fa-paper-plane"></i> Submit Invoice
        </button>
    </form>
</div>

<script>
function formatRupiah(input) {
    var nominal = input.value.replace(/\D/g, "");
    nominal = nominal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    input.value = "Rp " + nominal;
}

(function() {
    const input = document.getElementById('kodeInvoice');
    const feedback = document.getElementById('invoiceFeedback');
    const btn = document.getElementById('btnSubmitInvoice');
    const currentKode = '<?= htmlspecialchars($kode_transaksi) ?>';
    let debounceTimer = null;

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
})();
</script>