<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
include "bast_init.php";

$pageNow = "BAST";
$kode = trim($_GET['kode'] ?? '');
if ($kode === '') {
    header("Location: bast.php");
    exit;
}

// Fetch kegiatan + customer
$stmtK = $conn->prepare("SELECT k.*, c.nama AS nama_cust, c.alamat AS alamat_cust
                         FROM kegiatan k
                         LEFT JOIN customer c ON k.customer_id = c.id
                         WHERE k.kode = ? AND k.deleted_at IS NULL
                         ORDER BY k.id DESC LIMIT 1");
$stmtK->bind_param('s', $kode);
$stmtK->execute();
$keg = $stmtK->get_result()->fetch_assoc();
$stmtK->close();

// Fetch BAST record
$stmtB = $conn->prepare("SELECT * FROM bast_laporan WHERE kode = ? LIMIT 1");
$stmtB->bind_param('s', $kode);
$stmtB->execute();
$bast = $stmtB->get_result()->fetch_assoc();
$stmtB->close();

$titikKamera = [];
if ($bast) {
    $bastId = intval($bast['id']);
    $resT = mysqli_query($conn, "SELECT * FROM bast_titik_kamera WHERE bast_id = $bastId ORDER BY urutan ASC, id ASC");
    if ($resT) {
        while ($rt = mysqli_fetch_assoc($resT)) {
            $titikKamera[] = $rt;
        }
    }
}

$custNama = $keg['nama_cust'] ?? '';
$custAlamat = $keg['alamat_cust'] ?? '';
$jenisDefault = !empty($keg['keterangan']) ? $keg['keterangan'] : 'Pemasangan dan Instalasi CCTV';

$noBast = !empty($bast['no_bast']) ? $bast['no_bast'] : sprintf('No.001-%s/GRAV-BA/%s', bastRomanMonth(), date('y'));
$hariTanggal = !empty($bast['hari_tanggal_teks'])
    ? $bast['hari_tanggal_teks']
    : ('Pada hari ini, ' . bastIndonesianDay() . ' ' . bastIndonesianDate() . ', telah dilakukan serah terima pekerjaan antara:');

$p1Nama = $bast['pihak1_nama'] ?? '';
$p1Jabatan = $bast['pihak1_jabatan'] ?? '';
$p1Perusahaan = !empty($bast['pihak1_perusahaan']) ? $bast['pihak1_perusahaan'] : $custNama;
$p1Alamat = !empty($bast['pihak1_alamat']) ? $bast['pihak1_alamat'] : $custAlamat;

$p2Nama = !empty($bast['pihak2_nama']) ? $bast['pihak2_nama'] : 'Resmina';
$p2Jabatan = !empty($bast['pihak2_jabatan']) ? $bast['pihak2_jabatan'] : 'Manager Umum';
$p2Perusahaan = !empty($bast['pihak2_perusahaan']) ? $bast['pihak2_perusahaan'] : 'GraViTTi Technology';
$p2Alamat = !empty($bast['pihak2_alamat']) ? $bast['pihak2_alamat'] : 'Harco Mangga Dua Plaza Blok A3-156 Jakarta – Indonesia 10730';
$kotaTglTtd = !empty($bast['kota_tanggal_ttd']) ? $bast['kota_tanggal_ttd'] : ('Jakarta, ' . bastIndonesianDate());
$ketDisplay = $bast['ket_display_utama'] ?? 'Display CCTV Keseluruhan';
$fotoDisplay = $bast['foto_display_utama'] ?? '';

$poRows = json_decode($bast['rincian_po_json'] ?? '', true);
if (!is_array($poRows) || empty($poRows)) {
    $poRows = [['no_po' => '-', 'lokasi' => $custNama ?: '-', 'jenis' => $jenisDefault]];
}

$deskRows = json_decode($bast['deskripsi_pekerjaan_json'] ?? '', true);
if (!is_array($deskRows) || empty($deskRows)) {
    $jmlTitikInit = count($titikKamera) > 0 ? (count($titikKamera) . ' Titik') : '...... Titik';
    $deskRows = [
        ['uraian' => 'Penarikan Kabel Cat6/LAN Untuk CCTV', 'volume' => '...... Tarikan'],
        ['uraian' => 'Pemasangan Kamera CCTV', 'volume' => $jmlTitikInit],
    ];
}

$devRows = json_decode($bast['device_credentials_json'] ?? '', true);
if (!is_array($devRows) || empty($devRows)) {
    $devRows = [
        ['nama_unit' => 'NVR / DVR Utama', 'username' => 'admin', 'password' => '-'],
        ['nama_unit' => 'Kamera CCTV', 'username' => 'admin', 'password' => '-'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editor BAST #<?= htmlspecialchars($kode) ?> - <?= htmlspecialchars($custNama) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }

        .editor-topbar {
            position: sticky; top: 0; z-index: 1000;
            background: #0f172a; color: #fff;
            padding: 12px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .tb-btn {
            padding: 9px 16px; border-radius: 10px; border: none; font-size: 12.5px; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
            font-family: inherit; transition: all 0.18s;
        }
        .tb-back { background: #1e293b; color: #e2e8f0; border: 1px solid #334155; }
        .tb-back:hover { background: #334155; color: #fff; }
        .tb-save { background: #10b981; color: #fff; }
        .tb-save:hover { background: #059669; }
        .tb-docx { background: linear-gradient(135deg, #ff5b22, #ff7a00); color: #fff; box-shadow: 0 4px 12px rgba(255,91,34,0.35); }
        .tb-docx:hover { opacity: 0.95; transform: translateY(-1px); }
        .tb-print { background: #3b82f6; color: #fff; }
        .tb-print:hover { background: #2563eb; }

        .editor-hint {
            max-width: 210mm; margin: 16px auto 0; background: #fff7ed; border: 1px solid #fed7aa;
            color: #9a3412; padding: 10px 16px; border-radius: 12px; font-size: 12.5px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }

        .a4-sheet {
            width: 210mm; min-height: 297mm; margin: 16px auto 36px;
            background: #fff; padding: 18mm 20mm;
            box-shadow: 0 12px 40px rgba(15,23,42,0.14);
            border-radius: 4px; font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-size: 11pt; line-height: 1.45; color: #111827;
        }
        .kop-wrap { border-bottom: 2.5px solid #1e293b; padding-bottom: 10px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }
        .kop-wrap img { max-height: 46px; object-fit: contain; }

        .doc-input {
            border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 5px;
            padding: 4px 8px; font-family: inherit; font-size: inherit; color: inherit; width: 100%;
            transition: border-color 0.15s, background 0.15s;
        }
        .doc-input:focus { outline: none; border: 1.5px solid #ff5b22; background: #fffaf7; }
        .doc-title { text-align: center; font-size: 14pt; font-weight: 700; text-decoration: underline; margin: 8px 0 4px; }

        .kv-grid { display: grid; grid-template-columns: 110px 14px 1fr; gap: 5px 4px; align-items: center; margin-bottom: 12px; }
        .doc-table { width: 100%; border-collapse: collapse; margin: 10px 0 6px; font-size: 10.5pt; }
        .doc-table th, .doc-table td { border: 1px solid #334155; padding: 6px 8px; vertical-align: middle; }
        .doc-table th { background: #f1f5f9; font-weight: 700; }

        .row-add-btn {
            background: #f1f5f9; border: 1px dashed #94a3b8; color: #334155;
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer; margin-bottom: 12px;
        }
        .row-del-btn { background: #fee2e2; border: none; color: #dc2626; border-radius: 5px; width: 24px; height: 24px; cursor: pointer; font-weight: 700; }

        .photo-wide-box { border: 1px solid #334155; padding: 10px; text-align: center; margin-bottom: 14px; border-radius: 4px; }
        .photo-wide-box img { max-width: 100%; max-height: 290px; object-fit: contain; border-radius: 4px; }
        .photo-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .photo-cell { border: 1px solid #334155; padding: 10px; text-align: center; border-radius: 4px; break-inside: avoid; }
        .photo-cell img { width: 100%; height: 195px; object-fit: contain; background: #f8fafc; border-radius: 4px; }

        .page-divider { border-top: 2px dashed #cbd5e1; margin: 28px 0; position: relative; }
        .page-divider span { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 12px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .a4-sheet { margin: 0; padding: 10mm 14mm; box-shadow: none; width: 100%; }
            .doc-input { border: none !important; background: transparent !important; padding: 0 !important; }
            .page-divider { border: none; page-break-before: always; margin: 0; }
            .page-divider span { display: none; }
        }
    </style>
</head>
<body>

    <div class="editor-topbar no-print">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="bast.php" class="tb-btn tb-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            <div>
                <div style="font-size:14px;font-weight:800;">Editor Dokumen BAST • #<?= htmlspecialchars($kode) ?></div>
                <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($custNama) ?></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span id="saveStatusText" style="font-size:11.5px;color:#94a3b8;margin-right:6px;"></span>
            <button type="button" class="tb-btn tb-save" onclick="saveBastDoc(false)"><i class="fa-solid fa-floppy-disk"></i> Simpan Draft</button>
            <button type="button" class="tb-btn tb-docx" onclick="saveBastDoc(true)"><i class="fa-solid fa-file-word"></i> Simpan &amp; Unduh Word (.docx)</button>
            <button type="button" class="tb-btn tb-print" onclick="saveThenPrint()"><i class="fa-solid fa-print"></i> Cetak / PDF</button>
        </div>
    </div>

    <div class="editor-hint no-print">
        <i class="fa-solid fa-circle-info" style="font-size:16px;color:#ff5b22;"></i>
        <span>Semua kotak bergaris putus-putus di bawah dapat langsung diketik/diedit sebelum Anda klik <strong>Simpan &amp; Unduh Word (.docx)</strong> atau <strong>Cetak</strong>.</span>
    </div>

    <!-- A4 INTERACTIVE PAPER -->
    <div class="a4-sheet">
        <div class="kop-wrap">
            <?php if (file_exists(__DIR__ . '/assets/templates/bast_kop_header.png')): ?>
                <img src="assets/templates/bast_kop_header.png" alt="GraViTTi Technology">
            <?php else: ?>
                <strong style="font-size:18pt;color:#f59e0b;">GraViTTi Technology</strong>
            <?php endif; ?>
            <div style="text-align:right;font-size:9pt;color:#64748b;">Dokumen Resmi Serah Terima Pekerjaan</div>
        </div>

        <div class="doc-title">BERITA ACARA SERAH TERIMA PEKERJAAN</div>
        <div style="max-width:260px;margin:0 auto 16px;">
            <input type="text" id="no_bast" class="doc-input" style="text-align:center;font-weight:700;" value="<?= htmlspecialchars($noBast) ?>">
        </div>

        <div style="margin-bottom:14px;">
            <input type="text" id="hari_tanggal_teks" class="doc-input" value="<?= htmlspecialchars($hariTanggal) ?>">
        </div>

        <div style="font-weight:700;margin-bottom:6px;">PIHAK PERTAMA (PENERIMA)</div>
        <div class="kv-grid">
            <div>Nama</div><div>:</div><div><input type="text" id="pihak1_nama" class="doc-input" placeholder="Contoh: Bpk. Joko Purnomo" value="<?= htmlspecialchars($p1Nama) ?>"></div>
            <div>Jabatan</div><div>:</div><div><input type="text" id="pihak1_jabatan" class="doc-input" placeholder="Contoh: Manager Produksi" value="<?= htmlspecialchars($p1Jabatan) ?>"></div>
            <div>Mewakili</div><div>:</div><div><input type="text" id="pihak1_perusahaan" class="doc-input" value="<?= htmlspecialchars($p1Perusahaan) ?>"></div>
            <div>Alamat</div><div>:</div><div><input type="text" id="pihak1_alamat" class="doc-input" value="<?= htmlspecialchars($p1Alamat) ?>"></div>
        </div>

        <div style="font-weight:700;margin-bottom:6px;">PIHAK KEDUA (PELAKSANA)</div>
        <div class="kv-grid">
            <div>Nama</div><div>:</div><div><input type="text" id="pihak2_nama" class="doc-input" value="<?= htmlspecialchars($p2Nama) ?>"></div>
            <div>Jabatan</div><div>:</div><div><input type="text" id="pihak2_jabatan" class="doc-input" value="<?= htmlspecialchars($p2Jabatan) ?>"></div>
            <div>Mewakili</div><div>:</div><div><input type="text" id="pihak2_perusahaan" class="doc-input" value="<?= htmlspecialchars($p2Perusahaan) ?>"></div>
            <div>Alamat</div><div>:</div><div><input type="text" id="pihak2_alamat" class="doc-input" value="<?= htmlspecialchars($p2Alamat) ?>"></div>
        </div>

        <div style="margin:12px 0 6px;">Dengan rincian pekerjaan sebagai berikut:</div>
        <table class="doc-table" id="tblPo">
            <thead>
                <tr>
                    <th style="width:42px;text-align:center;">No</th>
                    <th style="width:28%;">NO. PO</th>
                    <th>Lokasi Camera</th>
                    <th style="width:30%;">Jenis Pekerjaan</th>
                    <th class="no-print" style="width:34px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($poRows as $idx => $pr): ?>
                <tr>
                    <td class="row-no" style="text-align:center;"><?= $idx + 1 ?></td>
                    <td><input type="text" class="doc-input po-no" value="<?= htmlspecialchars($pr['no_po'] ?? '') ?>"></td>
                    <td><input type="text" class="doc-input po-lok" value="<?= htmlspecialchars($pr['lokasi'] ?? '') ?>"></td>
                    <td><input type="text" class="doc-input po-jns" value="<?= htmlspecialchars($pr['jenis'] ?? '') ?>"></td>
                    <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="row-add-btn no-print" onclick="addPoRow()">+ Tambah Baris Rincian</button>

        <p style="margin:12px 0 8px;">Dengan ini PIHAK KEDUA menyatakan bahwa pekerjaan telah selesai dilaksanakan, dan PIHAK PERTAMA telah menerima hasil pekerjaan dalam kondisi Selesai.</p>
        <p style="margin:0 0 16px;">Demikian berita acara serah terima pekerjaan ini dibuat dan ditandatangani oleh kedua belah pihak sebagai bukti serah terima.</p>

        <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
            <input type="text" id="kota_tanggal_ttd" class="doc-input" style="max-width:240px;text-align:right;font-weight:700;" value="<?= htmlspecialchars($kotaTglTtd) ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;text-align:center;margin-top:10px;">
            <div>
                <div style="font-weight:700;margin-bottom:68px;">Pihak Pertama</div>
                <div style="font-weight:700;text-decoration:underline;" id="previewP1Sign">( <?= htmlspecialchars($p1Nama ?: '............................') ?> )</div>
                <div style="font-size:10pt;color:#334155;" id="previewP1Jab"><?= htmlspecialchars($p1Jabatan) ?></div>
            </div>
            <div>
                <div style="font-weight:700;margin-bottom:68px;">Pihak Kedua</div>
                <div style="font-weight:700;text-decoration:underline;" id="previewP2Sign">( <?= htmlspecialchars($p2Nama ?: '............................') ?> )</div>
                <div style="font-size:10pt;color:#334155;" id="previewP2Jab"><?= htmlspecialchars($p2Jabatan) ?></div>
            </div>
        </div>

        <!-- PAGE 2: LAMPIRAN -->
        <div class="page-divider"><span>Halaman Lampiran</span></div>

        <div style="font-size:13pt;font-weight:700;text-decoration:underline;margin-bottom:4px;">Lampiran dan lain-lain</div>
        <div style="margin-bottom:8px;">Rincian pekerjaan yang telah selesai dilaksanakan:</div>

        <table class="doc-table" id="tblDesk">
            <thead>
                <tr>
                    <th style="width:42px;text-align:center;">No</th>
                    <th>Deskripsi Pekerjaan</th>
                    <th style="width:28%;">Volume / Keterangan</th>
                    <th class="no-print" style="width:34px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deskRows as $idx => $dr): ?>
                <tr>
                    <td class="row-no" style="text-align:center;"><?= $idx + 1 ?></td>
                    <td><input type="text" class="doc-input desk-ur" value="<?= htmlspecialchars($dr['uraian'] ?? '') ?>"></td>
                    <td><input type="text" class="doc-input desk-vol" value="<?= htmlspecialchars($dr['volume'] ?? '') ?>"></td>
                    <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:9pt;color:#64748b;">Table 1.1 (Job Description)</span>
            <button type="button" class="row-add-btn no-print" onclick="addDeskRow()">+ Tambah Deskripsi</button>
        </div>

        <table class="doc-table" id="tblDev" style="margin-top:12px;">
            <thead>
                <tr>
                    <th style="width:42px;text-align:center;">No</th>
                    <th>Nama Unit / Device</th>
                    <th style="width:25%;">Username</th>
                    <th style="width:25%;">Password</th>
                    <th class="no-print" style="width:34px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devRows as $idx => $dv): ?>
                <tr>
                    <td class="row-no" style="text-align:center;"><?= $idx + 1 ?></td>
                    <td><input type="text" class="doc-input dev-nm" value="<?= htmlspecialchars($dv['nama_unit'] ?? '') ?>"></td>
                    <td><input type="text" class="doc-input dev-us" value="<?= htmlspecialchars($dv['username'] ?? '') ?>"></td>
                    <td><input type="text" class="doc-input dev-pw" value="<?= htmlspecialchars($dv['password'] ?? '') ?>"></td>
                    <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <span style="font-size:9pt;color:#64748b;">Table 1.2 (Device User &amp; Password)</span>
            <button type="button" class="row-add-btn no-print" onclick="addDevRow()">+ Tambah Device</button>
        </div>

        <div style="font-size:12pt;font-weight:700;text-decoration:underline;margin-bottom:10px;">Dokumentasi Display CCTV &amp; Titik Kamera</div>

        <?php if (!empty($fotoDisplay)): ?>
            <div class="photo-wide-box">
                <img src="uploads/bast/<?= htmlspecialchars($fotoDisplay) ?>" alt="Display Utama">
                <div style="margin-top:8px;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <strong>Gambar 2.1:</strong>
                    <input type="text" id="ket_display_utama" class="doc-input" style="max-width:420px;font-weight:700;text-align:center;" value="<?= htmlspecialchars($ketDisplay) ?>">
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" id="ket_display_utama" value="<?= htmlspecialchars($ketDisplay) ?>">
            <div style="padding:18px;border:1.5px dashed #cbd5e1;border-radius:8px;text-align:center;color:#64748b;margin-bottom:14px;">
                Belum ada foto Display CCTV Keseluruhan yang diinput oleh Teknisi dari aplikasi.
            </div>
        <?php endif; ?>

        <?php if (!empty($titikKamera)): ?>
            <div class="photo-grid-2">
                <?php foreach ($titikKamera as $idx => $tk): ?>
                <div class="photo-cell">
                    <img src="uploads/bast/<?= htmlspecialchars($tk['foto_titik']) ?>" alt="Titik Kamera">
                    <div style="margin-top:8px;">
                        <div style="font-size:9.5pt;font-weight:700;color:#475569;margin-bottom:3px;">Gambar 2.<?= $idx + 2 ?></div>
                        <input type="text" class="doc-input titik-cap-input" data-id="<?= intval($tk['id']) ?>" style="text-align:center;font-weight:600;" value="<?= htmlspecialchars($tk['keterangan'] ?: ('Kamera Titik ' . ($idx + 1))) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    const KODE_BAST = <?= json_encode($kode) ?>;

    ['pihak1_nama', 'pihak1_jabatan', 'pihak2_nama', 'pihak2_jabatan'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            document.getElementById('previewP1Sign').textContent = '( ' + (document.getElementById('pihak1_nama').value || '............................') + ' )';
            document.getElementById('previewP1Jab').textContent = document.getElementById('pihak1_jabatan').value;
            document.getElementById('previewP2Sign').textContent = '( ' + (document.getElementById('pihak2_nama').value || '............................') + ' )';
            document.getElementById('previewP2Jab').textContent = document.getElementById('pihak2_jabatan').value;
        });
    });

    function renumberTable(tblId) {
        document.querySelectorAll('#' + tblId + ' tbody tr').forEach((tr, i) => {
            const c = tr.querySelector('.row-no');
            if (c) c.textContent = i + 1;
        });
    }

    function removeRow(btn) {
        const tbody = btn.closest('tbody');
        const tblId = btn.closest('table').id;
        if (tbody.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
        renumberTable(tblId);
    }

    function addPoRow() {
        const tb = document.querySelector('#tblPo tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="row-no" style="text-align:center;"></td>
            <td><input type="text" class="doc-input po-no" value="-"></td>
            <td><input type="text" class="doc-input po-lok" value=""></td>
            <td><input type="text" class="doc-input po-jns" value="Pemasangan CCTV"></td>
            <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>`;
        tb.appendChild(tr);
        renumberTable('tblPo');
    }

    function addDeskRow() {
        const tb = document.querySelector('#tblDesk tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="row-no" style="text-align:center;"></td>
            <td><input type="text" class="doc-input desk-ur" value=""></td>
            <td><input type="text" class="doc-input desk-vol" value=""></td>
            <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>`;
        tb.appendChild(tr);
        renumberTable('tblDesk');
    }

    function addDevRow() {
        const tb = document.querySelector('#tblDev tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="row-no" style="text-align:center;"></td>
            <td><input type="text" class="doc-input dev-nm" value=""></td>
            <td><input type="text" class="doc-input dev-us" value="admin"></td>
            <td><input type="text" class="doc-input dev-pw" value=""></td>
            <td class="no-print" style="text-align:center;"><button type="button" class="row-del-btn" onclick="removeRow(this)">&times;</button></td>`;
        tb.appendChild(tr);
        renumberTable('tblDev');
    }

    function collectPayload() {
        const fd = new FormData();
        fd.append('action', 'save_web_doc');
        fd.append('kode', KODE_BAST);
        ['no_bast','hari_tanggal_teks','pihak1_nama','pihak1_jabatan','pihak1_perusahaan','pihak1_alamat',
         'pihak2_nama','pihak2_jabatan','pihak2_perusahaan','pihak2_alamat','kota_tanggal_ttd','ket_display_utama'].forEach(k => {
            fd.append(k, document.getElementById(k)?.value || '');
        });

        const po = [];
        document.querySelectorAll('#tblPo tbody tr').forEach(tr => {
            po.push({
                no_po: tr.querySelector('.po-no')?.value || '',
                lokasi: tr.querySelector('.po-lok')?.value || '',
                jenis: tr.querySelector('.po-jns')?.value || '',
            });
        });
        fd.append('rincian_po_json', JSON.stringify(po));

        const desk = [];
        document.querySelectorAll('#tblDesk tbody tr').forEach(tr => {
            desk.push({
                uraian: tr.querySelector('.desk-ur')?.value || '',
                volume: tr.querySelector('.desk-vol')?.value || '',
            });
        });
        fd.append('deskripsi_pekerjaan_json', JSON.stringify(desk));

        const dev = [];
        document.querySelectorAll('#tblDev tbody tr').forEach(tr => {
            dev.push({
                nama_unit: tr.querySelector('.dev-nm')?.value || '',
                username: tr.querySelector('.dev-us')?.value || '',
                password: tr.querySelector('.dev-pw')?.value || '',
            });
        });
        fd.append('device_credentials_json', JSON.stringify(dev));

        const caps = [];
        document.querySelectorAll('.titik-cap-input').forEach(inp => {
            caps.push({ id: inp.getAttribute('data-id'), keterangan: inp.value });
        });
        fd.append('titik_captions_json', JSON.stringify(caps));
        return fd;
    }

    async function saveBastDoc(downloadAfter) {
        const st = document.getElementById('saveStatusText');
        st.textContent = 'Menyimpan...';
        try {
            const r = await fetch('api_bast.php', { method: 'POST', body: collectPayload() });
            const res = await r.json();
            if (res.success) {
                st.textContent = '✓ Tersimpan';
                if (downloadAfter) {
                    window.location.href = 'bast-export-docx.php?kode=' + encodeURIComponent(KODE_BAST);
                }
            } else {
                st.textContent = '';
                alert(res.message || 'Gagal menyimpan');
            }
        } catch (e) {
            st.textContent = '';
            alert('Gagal terhubung ke server');
        }
    }

    async function saveThenPrint() {
        await saveBastDoc(false);
        window.print();
    }
    </script>
</body>
</html>

