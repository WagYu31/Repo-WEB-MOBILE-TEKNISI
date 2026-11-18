<?php
@error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
@ini_set('display_errors', '0');

include "conn.php";
include "session.php";

if (!isset($_GET['bulan']) || !isset($_GET['tahun']) || !is_numeric($_GET['bulan']) || !is_numeric($_GET['tahun'])) {
    http_response_code(400);
    die("Error: Bulan dan Tahun tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$daftar_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$nama_bulan = $daftar_bulan[$bulan] ?? (string)$bulan;

// ── Native OpenXML (.xlsx) Builder via ZipArchive (Zero Composer dependency) ──
function xlsxColName(int $colIndex1Based): string {
    $name = '';
    while ($colIndex1Based > 0) {
        $mod = ($colIndex1Based - 1) % 26;
        $name = chr(65 + $mod) . $name;
        $colIndex1Based = intdiv($colIndex1Based - 1, 26);
    }
    return $name;
}

function xlsxEsc($val): string {
    $s = (string)($val ?? '');
    // Strip invalid XML control chars
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxCell(int $col, int $row, $value, int $styleIdx = 0, string $type = 'str'): string {
    $ref = xlsxColName($col) . $row;
    if ($type === 'num' && is_numeric($value)) {
        return '<c r="' . $ref . '" s="' . $styleIdx . '"><v>' . (0 + $value) . '</v></c>';
    }
    return '<c r="' . $ref . '" s="' . $styleIdx . '" t="inlineStr"><is><t xml:space="preserve">' . xlsxEsc($value) . '</t></is></c>';
}

function xlsxFormulaCell(int $col, int $row, string $formula, $cachedVal, int $styleIdx = 0): string {
    $ref = xlsxColName($col) . $row;
    return '<c r="' . $ref . '" s="' . $styleIdx . '"><f>' . xlsxEsc($formula) . '</f><v>' . (0 + $cachedVal) . '</v></c>';
}

// ── Fetch Report Data ──
$sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.lunas, k.paid, c.nama AS nama_cust
            FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
            WHERE (
                (EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode AND MONTH(pk.tanggal) = ? AND YEAR(pk.tanggal) = ? AND pk.deleted_at IS NULL))
                OR
                (NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode AND pk.deleted_at IS NULL) AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ?)
            )
            AND k.deleted_at IS NULL
            GROUP BY k.kode ORDER BY k.created_at ASC";

$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("iiii", $bulan, $tahun, $bulan, $tahun);
$stmt_main->execute();
$result_main = $stmt_main->get_result();

// Styles:
// 0 = default
// 1 = Title (13pt bold)
// 2 = Table Header (white bold on #0F172A, centered, thin border)
// 3 = Data Text Left (wrapped, vertical center, thin border)
// 4 = Data Text Center (vertical center, thin border)
// 5 = Data Number #,##0 Right (vertical center, thin border)
// 6 = Data Muted Red Center ("Tidak ada data pelaksanaan", thin border)

$rowsXml = '';
$merges = [];

// Row 1: Title
$rowsXml .= '<row r="1" ht="24" customHeight="1">'
    . xlsxCell(1, 1, "Laporan Kegiatan Lengkap - Periode: $nama_bulan $tahun", 1, 'str')
    . '</row>';
$merges[] = 'A1:M1';

// Row 2: Blank spacer
$rowsXml .= '<row r="2" ht="8" customHeight="1"></row>';

// Row 3 & Row 4: 2-level Header
$rowsXml .= '<row r="3" ht="22" customHeight="1">'
    . xlsxCell(1, 3, 'No', 2)
    . xlsxCell(2, 3, 'Customer', 2)
    . xlsxCell(3, 3, 'Kode & Request', 2)
    . xlsxCell(4, 3, 'Tgl Request', 2)
    . xlsxCell(5, 3, 'No. Invoice', 2)
    . xlsxCell(6, 3, 'Nominal Invoice', 2)
    . xlsxCell(7, 3, 'Status Bayar', 2)
    . xlsxCell(8, 3, 'Rincian Teknisi & Pelaksanaan', 2)
    . xlsxCell(9, 3, '', 2)
    . xlsxCell(10, 3, '', 2)
    . xlsxCell(11, 3, '', 2)
    . xlsxCell(12, 3, '', 2)
    . xlsxCell(13, 3, '', 2)
    . '</row>';

$rowsXml .= '<row r="4" ht="22" customHeight="1">'
    . xlsxCell(1, 4, '', 2)
    . xlsxCell(2, 4, '', 2)
    . xlsxCell(3, 4, '', 2)
    . xlsxCell(4, 4, '', 2)
    . xlsxCell(5, 4, '', 2)
    . xlsxCell(6, 4, '', 2)
    . xlsxCell(7, 4, '', 2)
    . xlsxCell(8, 4, 'Teknisi', 2)
    . xlsxCell(9, 4, 'Target Tercapai', 2)
    . xlsxCell(10, 4, 'Tanggal Kerja', 2)
    . xlsxCell(11, 4, 'Jam Janjian', 2)
    . xlsxCell(12, 4, 'Absen Mulai', 2)
    . xlsxCell(13, 4, 'Absen Selesai', 2)
    . '</row>';

$merges[] = 'A3:A4';
$merges[] = 'B3:B4';
$merges[] = 'C3:C4';
$merges[] = 'D3:D4';
$merges[] = 'E3:E4';
$merges[] = 'F3:F4';
$merges[] = 'G3:G4';
$merges[] = 'H3:M3';

$curRow = 5;
$jobNo = 0;
$cntLunas = 0;
$cntBelum = 0;
$sumNominalAll = 0;
$sumNominalLunas = 0;
$sumNominalBelum = 0;
$sumTargetAll = 0;
$sumTargetLunas = 0;
$sumTargetBelum = 0;

if ($result_main && $result_main->num_rows > 0) {
    $stmt_invoice = $conn->prepare("SELECT GROUP_CONCAT(DISTINCT no_invoice ORDER BY tanggal ASC, id ASC SEPARATOR ', ') AS no_invoice,
                                           MAX(nominal_invoice) AS nominal_invoice
                                    FROM pendapatan_kegiatan
                                    WHERE kode = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND deleted_at IS NULL
                                    GROUP BY kode");
    $stmt_count = $conn->prepare("SELECT COUNT(DISTINCT teknisi_id) as total_aktif FROM pelaksanaan_kegiatan WHERE kode = ? AND waktu_mulai IS NOT NULL AND deleted_at IS NULL");
    $stmt_team = $conn->prepare("SELECT t.id, t.nama AS nama_teknisi
                     FROM (
                         SELECT tk.teknisi_id
                         FROM team_kegiatan tk
                         JOIN kegiatan k ON tk.kegiatan_id = k.id
                         JOIN teknisi t0 ON tk.teknisi_id = t0.id
                         WHERE k.kode = ? AND k.deleted_at IS NULL AND tk.deleted_at IS NULL AND t0.deleted_at IS NULL
                         UNION
                         SELECT pk.teknisi_id
                         FROM pelaksanaan_kegiatan pk
                         WHERE pk.kode = ? AND pk.deleted_at IS NULL
                         UNION
                         SELECT pd.teknisi_id
                         FROM pendapatan_kegiatan pd
                         WHERE pd.kode = ? AND pd.deleted_at IS NULL
                     ) all_tek
                     JOIN teknisi t ON t.id = all_tek.teknisi_id
                     ORDER BY t.nama ASC");
    include_once __DIR__ . '/include/format_tanggal.php';
    ensurePorsiKerjaColumn($conn);
    $stmt_p = $conn->prepare("SELECT SUM(pendapatan) as total, MIN(porsi_kerja) as min_porsi FROM pendapatan_kegiatan WHERE kode = ? AND teknisi_id = ? AND deleted_at IS NULL");
    $stmt_a = $conn->prepare("SELECT DATE(pk.waktu_mulai) as tgl,
                            COALESCE(MIN(k.jadwal), (SELECT MIN(k2.jadwal) FROM kegiatan k2 WHERE k2.kode = pk.kode AND DATE(k2.jadwal) = DATE(pk.waktu_mulai) AND k2.deleted_at IS NULL)) as jadwal,
                            MIN(pk.waktu_mulai) as mulai,
                            MAX(pk.waktu_selesai) as selesai
                            FROM pelaksanaan_kegiatan pk
                            LEFT JOIN kegiatan k ON pk.kegiatan_id = k.id AND k.deleted_at IS NULL
                            WHERE pk.kode = ? AND pk.teknisi_id = ? AND pk.waktu_mulai IS NOT NULL AND pk.deleted_at IS NULL
                            GROUP BY tgl ORDER BY tgl ASC");

    while ($row_main = $result_main->fetch_assoc()) {
        $jobNo++;
        $kodeTransaksi = $row_main['kode_transaksi'];
        $is_manual_fee = is_numeric($row_main['paid']);

        $stmt_invoice->bind_param("sii", $kodeTransaksi, $bulan, $tahun);
        $stmt_invoice->execute();
        $invoice_data = $stmt_invoice->get_result()->fetch_assoc();

        $stmt_count->bind_param("s", $kodeTransaksi);
        $stmt_count->execute();
        $jumlah_teknisi_aktif = (int)($stmt_count->get_result()->fetch_assoc()['total_aktif'] ?? 0);

        $stmt_team->bind_param("sss", $kodeTransaksi, $kodeTransaksi, $kodeTransaksi);
        $stmt_team->execute();
        $res_team = $stmt_team->get_result();

        $grouped_data = [];
        $total_rows_for_job = 0;

        while ($t = $res_team->fetch_assoc()) {
            $tid = (int)$t['id'];

            $stmt_p->bind_param("si", $kodeTransaksi, $tid);
            $stmt_p->execute();
            $row_p = $stmt_p->get_result()->fetch_assoc();
            $pendapatan_db = (float)($row_p['total'] ?? 0);
            $min_porsi_db = isset($row_p['min_porsi']) && $row_p['min_porsi'] !== null ? (float)$row_p['min_porsi'] : 1.0;

            $stmt_a->bind_param("si", $kodeTransaksi, $tid);
            $stmt_a->execute();
            $res_a = $stmt_a->get_result();

            $absensi_list = [];
            while ($a = $res_a->fetch_assoc()) {
                $absensi_list[] = $a;
            }

            $final_pendapatan = $pendapatan_db;
            if ($pendapatan_db == 0 && $is_manual_fee) {
                $final_pendapatan = (count($absensi_list) > 0 && $jumlah_teknisi_aktif > 0)
                    ? (30000 / $jumlah_teknisi_aktif)
                    : 0;
            }

            $rowspan_tech = max(1, count($absensi_list));
            $grouped_data[] = [
                'nama' => ($t['nama_teknisi'] ?? '-') . ($min_porsi_db < 1 ? ' (½ Hari 50%)' : ''),
                'pendapatan' => round((float)$final_pendapatan),
                'absensi' => $absensi_list,
                'rowspan_tech' => $rowspan_tech,
            ];
            $total_rows_for_job += $rowspan_tech;
        }

        $custName = (string)($row_main['nama_cust'] ?? '-');
        $ketReq = trim((string)($row_main['keterangan'] ?? ''));
        $kodeKet = '#' . $kodeTransaksi . ($ketReq !== '' ? ' - ' . $ketReq : '');
        $tglReq = !empty($row_main['created_at']) ? date("d/m/Y", strtotime($row_main['created_at'])) : '-';
        $noInv = (string)($invoice_data['no_invoice'] ?? ($is_manual_fee ? 'Tidak ada Invoice (Fee Manual)' : '-'));
        $hasNominal = ($invoice_data && isset($invoice_data['nominal_invoice']) && is_numeric($invoice_data['nominal_invoice']));
        $nomVal = $hasNominal ? round((float)$invoice_data['nominal_invoice']) : null;
        $isLunasJob = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00');
        $statusBayar = $isLunasJob
            ? ('Lunas ' . date("d/m/Y", strtotime($row_main['lunas'])))
            : 'Belum Lunas';

        $jobTargetSum = 0;
        foreach ($grouped_data as $gt) {
            $jobTargetSum += (float)$gt['pendapatan'];
        }
        $nomNumeric = $nomVal !== null ? (float)$nomVal : 0;
        $sumNominalAll += $nomNumeric;
        $sumTargetAll += $jobTargetSum;
        if ($isLunasJob) {
            $cntLunas++;
            $sumNominalLunas += $nomNumeric;
            $sumTargetLunas += $jobTargetSum;
        } else {
            $cntBelum++;
            $sumNominalBelum += $nomNumeric;
            $sumTargetBelum += $jobTargetSum;
        }

        if (empty($grouped_data)) {
            $r = $curRow++;
            $rowsXml .= '<row r="' . $r . '" ht="20" customHeight="1">'
                . xlsxCell(1, $r, $jobNo, 4, 'num')
                . xlsxCell(2, $r, $custName, 3)
                . xlsxCell(3, $r, $kodeKet, 3)
                . xlsxCell(4, $r, $tglReq, 4)
                . xlsxCell(5, $r, $noInv, 3)
                . ($nomVal !== null ? xlsxCell(6, $r, $nomVal, 5, 'num') : xlsxCell(6, $r, '-', 4))
                . xlsxCell(7, $r, $statusBayar, 4)
                . xlsxCell(8, $r, 'Belum ada teknisi ditugaskan', 6)
                . xlsxCell(9, $r, '', 6)
                . xlsxCell(10, $r, '', 6)
                . xlsxCell(11, $r, '', 6)
                . xlsxCell(12, $r, '', 6)
                . xlsxCell(13, $r, '', 6)
                . '</row>';
            $merges[] = "H{$r}:M{$r}";
        } else {
            $jobStartRow = $curRow;
            $jobEndRow = $jobStartRow + $total_rows_for_job - 1;
            if ($jobEndRow > $jobStartRow) {
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $colLtr) {
                    $merges[] = "{$colLtr}{$jobStartRow}:{$colLtr}{$jobEndRow}";
                }
            }

            $isFirstJobRow = true;
            foreach ($grouped_data as $g) {
                $techStartRow = $curRow;
                $loopCount = max(1, count($g['absensi']));
                $techEndRow = $techStartRow + $loopCount - 1;
                if ($techEndRow > $techStartRow) {
                    $merges[] = "H{$techStartRow}:H{$techEndRow}";
                    $merges[] = "I{$techStartRow}:I{$techEndRow}";
                }

                for ($i = 0; $i < $loopCount; $i++) {
                    $r = $curRow++;
                    $rowCells = '';

                    if ($isFirstJobRow) {
                        $rowCells .= xlsxCell(1, $r, $jobNo, 4, 'num')
                            . xlsxCell(2, $r, $custName, 3)
                            . xlsxCell(3, $r, $kodeKet, 3)
                            . xlsxCell(4, $r, $tglReq, 4)
                            . xlsxCell(5, $r, $noInv, 3)
                            . ($nomVal !== null ? xlsxCell(6, $r, $nomVal, 5, 'num') : xlsxCell(6, $r, '-', 4))
                            . xlsxCell(7, $r, $statusBayar, 4);
                        $isFirstJobRow = false;
                    } else {
                        for ($c = 1; $c <= 7; $c++) {
                            $rowCells .= xlsxCell($c, $r, '', 3);
                        }
                    }

                    if ($i === 0) {
                        $rowCells .= xlsxCell(8, $r, $g['nama'], 3)
                            . xlsxCell(9, $r, $g['pendapatan'], 5, 'num');
                    } else {
                        $rowCells .= xlsxCell(8, $r, '', 3)
                            . xlsxCell(9, $r, '', 5);
                    }

                    if (!empty($g['absensi'])) {
                        $abs = $g['absensi'][$i];
                        $tglKerja = !empty($abs['tgl']) ? date("d/m/Y", strtotime($abs['tgl'])) : '-';
                        $jamJadwal = (!empty($abs['jadwal']) && $abs['jadwal'] != '0000-00-00 00:00:00') ? date("H:i", strtotime($abs['jadwal'])) : '-';
                        $jamMulai = (!empty($abs['mulai']) && $abs['mulai'] != '0000-00-00 00:00:00') ? date("H:i", strtotime($abs['mulai'])) : '-';
                        $jamSelesai = (!empty($abs['selesai']) && $abs['selesai'] != '0000-00-00 00:00:00') ? date("H:i", strtotime($abs['selesai'])) : '-';
                        $rowCells .= xlsxCell(10, $r, $tglKerja, 4)
                            . xlsxCell(11, $r, $jamJadwal, 4)
                            . xlsxCell(12, $r, $jamMulai, 4)
                            . xlsxCell(13, $r, $jamSelesai, 4);
                    } else {
                        $rowCells .= xlsxCell(10, $r, 'Tidak ada data pelaksanaan', 6)
                            . xlsxCell(11, $r, '', 6)
                            . xlsxCell(12, $r, '', 6)
                            . xlsxCell(13, $r, '', 6);
                        $merges[] = "J{$r}:M{$r}";
                    }

                    $rowsXml .= '<row r="' . $r . '" ht="20" customHeight="1">' . $rowCells . '</row>';
                }
            }
        }
    }

    $stmt_invoice->close();
    $stmt_count->close();
    $stmt_team->close();
    $stmt_p->close();
    $stmt_a->close();

    // ── Summary / Grand Total Rows at Bottom ──
    $lastDataRow = $curRow - 1;
    if ($lastDataRow >= 5) {
        // 1. TOTAL KESELURUHAN
        $rTot = $curRow++;
        $rowsXml .= '<row r="' . $rTot . '" ht="24" customHeight="1">'
            . xlsxCell(1, $rTot, "TOTAL KESELURUHAN ($jobNo Kegiatan)", 7)
            . xlsxCell(2, $rTot, '', 7)
            . xlsxCell(3, $rTot, '', 7)
            . xlsxCell(4, $rTot, '', 7)
            . xlsxCell(5, $rTot, '', 7)
            . xlsxFormulaCell(6, $rTot, "SUM(F5:F{$lastDataRow})", $sumNominalAll, 8)
            . xlsxCell(7, $rTot, 'TOTAL TARGET TEKNISI', 7)
            . xlsxCell(8, $rTot, '', 7)
            . xlsxFormulaCell(9, $rTot, "SUM(I5:I{$lastDataRow})", $sumTargetAll, 8)
            . xlsxCell(10, $rTot, '', 7)
            . xlsxCell(11, $rTot, '', 7)
            . xlsxCell(12, $rTot, '', 7)
            . xlsxCell(13, $rTot, '', 7)
            . '</row>';
        $merges[] = "A{$rTot}:E{$rTot}";
        $merges[] = "G{$rTot}:H{$rTot}";
        $merges[] = "J{$rTot}:M{$rTot}";

        // 2. TOTAL SUDAH LUNAS
        $rLun = $curRow++;
        $rowsXml .= '<row r="' . $rLun . '" ht="21" customHeight="1">'
            . xlsxCell(1, $rLun, "Sudah Lunas ($cntLunas Kegiatan)", 9)
            . xlsxCell(2, $rLun, '', 9)
            . xlsxCell(3, $rLun, '', 9)
            . xlsxCell(4, $rLun, '', 9)
            . xlsxCell(5, $rLun, '', 9)
            . xlsxFormulaCell(6, $rLun, "SUMIF(G5:G{$lastDataRow},\"Lunas*\",F5:F{$lastDataRow})", $sumNominalLunas, 10)
            . xlsxCell(7, $rLun, 'Target Teknisi (Lunas)', 9)
            . xlsxCell(8, $rLun, '', 9)
            . xlsxCell(9, $rLun, $sumTargetLunas, 10, 'num')
            . xlsxCell(10, $rLun, '', 9)
            . xlsxCell(11, $rLun, '', 9)
            . xlsxCell(12, $rLun, '', 9)
            . xlsxCell(13, $rLun, '', 9)
            . '</row>';
        $merges[] = "A{$rLun}:E{$rLun}";
        $merges[] = "G{$rLun}:H{$rLun}";
        $merges[] = "J{$rLun}:M{$rLun}";

        // 3. TOTAL BELUM LUNAS
        $rBel = $curRow++;
        $rowsXml .= '<row r="' . $rBel . '" ht="21" customHeight="1">'
            . xlsxCell(1, $rBel, "Belum Lunas ($cntBelum Kegiatan)", 11)
            . xlsxCell(2, $rBel, '', 11)
            . xlsxCell(3, $rBel, '', 11)
            . xlsxCell(4, $rBel, '', 11)
            . xlsxCell(5, $rBel, '', 11)
            . xlsxFormulaCell(6, $rBel, "SUMIF(G5:G{$lastDataRow},\"Belum Lunas\",F5:F{$lastDataRow})", $sumNominalBelum, 12)
            . xlsxCell(7, $rBel, 'Target Teknisi (Belum Lunas)', 11)
            . xlsxCell(8, $rBel, '', 11)
            . xlsxCell(9, $rBel, $sumTargetBelum, 12, 'num')
            . xlsxCell(10, $rBel, '', 11)
            . xlsxCell(11, $rBel, '', 11)
            . xlsxCell(12, $rBel, '', 11)
            . xlsxCell(13, $rBel, '', 11)
            . '</row>';
        $merges[] = "A{$rBel}:E{$rBel}";
        $merges[] = "G{$rBel}:H{$rBel}";
        $merges[] = "J{$rBel}:M{$rBel}";
    }
}

$stmt_main->close();
$conn->close();

// ── Assemble .xlsx Archive ──
$colWidths = [1 => 6, 2 => 28, 3 => 32, 4 => 13, 5 => 22, 6 => 18, 7 => 18, 8 => 24, 9 => 17, 10 => 14, 11 => 13, 12 => 13, 13 => 13];
$colsXml = '<cols>';
foreach ($colWidths as $idx => $w) {
    $colsXml .= '<col min="' . $idx . '" max="' . $idx . '" width="' . $w . '" customWidth="1"/>';
}
$colsXml .= '</cols>';

$mergeXml = '';
if (!empty($merges)) {
    $mergeXml = '<mergeCells count="' . count($merges) . '">';
    foreach ($merges as $mRef) {
        $mergeXml .= '<mergeCell ref="' . $mRef . '"/>';
    }
    $mergeXml .= '</mergeCells>';
}

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
    . $colsXml
    . '<sheetData>' . $rowsXml . '</sheetData>'
    . $mergeXml
    . '</worksheet>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
    . '<fonts count="6">'
    . '<font><sz val="10"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
    . '<font><b/><sz val="13"/><name val="Calibri"/><color rgb="FF0F172A"/></font>'
    . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
    . '<font><i/><sz val="10"/><name val="Calibri"/><color rgb="FFDC2626"/></font>'
    . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FF065F46"/></font>'
    . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FF9F1239"/></font>'
    . '</fonts>'
    . '<fills count="5">'
    . '<fill><patternFill patternType="none"/></fill>'
    . '<fill><patternFill patternType="gray125"/></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FFECFDF5"/></patternFill></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF1F2"/></patternFill></fill>'
    . '</fills>'
    . '<borders count="2">'
    . '<border><left/><right/><top/><bottom/><diagonal/></border>'
    . '<border>'
    . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
    . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
    . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
    . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
    . '<diagonal/>'
    . '</border>'
    . '</borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="13">'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
    . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="164" fontId="2" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="164" fontId="4" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '<xf numFmtId="164" fontId="5" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
    . '</cellXfs>'
    . '</styleSheet>';

$workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Laporan ' . xlsxEsc($nama_bulan . ' ' . $tahun) . '" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

$wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

$tmpBase = tempnam(sys_get_temp_dir(), 'lap_xlsx_');
@unlink($tmpBase);
$tmpXlsx = $tmpBase . '.xlsx';
$zip = new ZipArchive();
if ($zip->open($tmpXlsx, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    die("Error: Gagal membuat arsip Excel (.xlsx).");
}
$zip->addFromString('[Content_Types].xml', $contentTypesXml);
$zip->addFromString('_rels/.rels', $rootRelsXml);
$zip->addFromString('xl/workbook.xml', $workbookXml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);
$zip->addFromString('xl/styles.xml', $stylesXml);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

while (ob_get_level() > 0) {
    ob_end_clean();
}

$safeFilename = 'Laporan_Kegiatan_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_bulan) . '_' . $tahun . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($tmpXlsx));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: public');

readfile($tmpXlsx);
@unlink($tmpXlsx);
exit;
