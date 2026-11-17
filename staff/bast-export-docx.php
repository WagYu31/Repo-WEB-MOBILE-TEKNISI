<?php
include __DIR__ . '/conn.php';
include __DIR__ . '/session.php';
include __DIR__ . '/bast_init.php';

$kode = trim($_GET['kode'] ?? '');
if ($kode === '') {
    die('Kode kegiatan tidak ditemukan.');
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

$poRows = json_decode($bast['rincian_po_json'] ?? '', true);
if (!is_array($poRows) || empty($poRows)) {
    $poRows = [['no_po' => '-', 'lokasi' => $custNama ?: '-', 'jenis' => $jenisDefault]];
}

$deskRows = json_decode($bast['deskripsi_pekerjaan_json'] ?? '', true);
if (!is_array($deskRows) || empty($deskRows)) {
    $deskRows = [
        ['uraian' => 'Penarikan Kabel Cat6/LAN Untuk CCTV', 'volume' => '...... Tarikan'],
        ['uraian' => 'Pemasangan Kamera CCTV', 'volume' => '...... Titik'],
    ];
}

$devRows = json_decode($bast['device_credentials_json'] ?? '', true);
if (!is_array($devRows) || empty($devRows)) {
    $devRows = [
        ['nama_unit' => 'NVR / DVR Utama', 'username' => 'admin', 'password' => '-'],
    ];
}

// Collect photos: [ ['file' => path, 'caption' => 'Gambar 2.1 (...)'] ]
$photos = [];
$imgSeq = 1;
if ($bast && !empty($bast['foto_display_utama'])) {
    $pFile = __DIR__ . '/uploads/bast/' . $bast['foto_display_utama'];
    if (file_exists($pFile)) {
        $cap = trim($bast['ket_display_utama'] ?? '');
        $photos[] = [
            'path' => $pFile,
            'label' => 'Gambar 2.' . $imgSeq . ' (' . ($cap !== '' ? $cap : 'Display CCTV Keseluruhan') . ')',
            'wide' => true,
        ];
        $imgSeq++;
    }
}

if ($bast) {
    $bastId = intval($bast['id']);
    $resT = mysqli_query($conn, "SELECT * FROM bast_titik_kamera WHERE bast_id = $bastId ORDER BY urutan ASC, id ASC");
    if ($resT) {
        while ($rt = mysqli_fetch_assoc($resT)) {
            $pFile = __DIR__ . '/uploads/bast/' . $rt['foto_titik'];
            if (file_exists($pFile)) {
                $cap = trim($rt['keterangan'] ?? '');
                $photos[] = [
                    'path' => $pFile,
                    'label' => 'Gambar 2.' . $imgSeq . ' (' . ($cap !== '' ? $cap : ('Titik Kamera ' . $rt['urutan'])) . ')',
                    'wide' => false,
                ];
                $imgSeq++;
            }
        }
    }
}

function docxEsc($str) {
    return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function docxParagraph($text, $opts = []) {
    $align = $opts['align'] ?? 'left';
    $bold = !empty($opts['bold']);
    $underline = !empty($opts['underline']);
    $size = $opts['size'] ?? 22; // half-points (22 = 11pt)
    $after = $opts['after'] ?? 100;
    $before = $opts['before'] ?? 0;

    $pPr = '<w:pPr><w:spacing w:before="' . intval($before) . '" w:after="' . intval($after) . '"/><w:jc w:val="' . $align . '"/></w:pPr>';
    $rPr = '<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>'
        . ($bold ? '<w:b/>' : '')
        . ($underline ? '<w:u w:val="single"/>' : '')
        . '<w:sz w:val="' . intval($size) . '"/><w:szCs w:val="' . intval($size) . '"/></w:rPr>';
    return '<w:p>' . $pPr . '<w:r>' . $rPr . '<w:t xml:space="preserve">' . docxEsc($text) . '</w:t></w:r></w:p>';
}

function docxKeyValParagraph($label, $value) {
    $rPrB = '<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr>';
    return '<w:p><w:pPr><w:spacing w:before="0" w:after="40"/></w:pPr>'
        . '<w:r>' . $rPrB . '<w:t xml:space="preserve">' . docxEsc(str_pad($label, 12, ' ')) . ': ' . docxEsc($value) . '</w:t></w:r></w:p>';
}

function docxDrawingXml($rId, $docPrId, $cxEmu, $cyEmu, $name = 'FotoBAST') {
    $safeName = docxEsc($name);
    return '<w:drawing>'
        . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
        . '<wp:extent cx="' . intval($cxEmu) . '" cy="' . intval($cyEmu) . '"/>'
        . '<wp:docPr id="' . intval($docPrId) . '" name="' . $safeName . '"/>'
        . '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
        . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
        . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        . '<pic:nvPicPr><pic:cNvPr id="' . intval($docPrId) . '" name="' . $safeName . '"/><pic:cNvPicPr/></pic:nvPicPr>'
        . '<pic:blipFill><a:blip r:embed="' . $rId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
        . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . intval($cxEmu) . '" cy="' . intval($cyEmu) . '"/></a:xfrm>'
        . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
        . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';
}

function docxCalcImageEmu($filePath, $maxWidthCm = 7.4, $maxHeightCm = 5.4) {
    $emuPerCm = 360000;
    $maxW = $maxWidthCm * $emuPerCm;
    $maxH = $maxHeightCm * $emuPerCm;
    $info = @getimagesize($filePath);
    if (!$info || empty($info[0]) || empty($info[1])) {
        return [intval($maxW), intval($maxH * 0.72)];
    }
    $ratio = $info[0] / $info[1];
    $w = $maxW;
    $h = $w / $ratio;
    if ($h > $maxH) {
        $h = $maxH;
        $w = $h * $ratio;
    }
    return [intval($w), intval($h)];
}

$templatePath = bastActiveTemplatePath();
$tmpDocx = tempnam(sys_get_temp_dir(), 'bast_docx_') . '.docx';
if (file_exists($templatePath)) {
    copy($templatePath, $tmpDocx);
} else {
    die('File template dasar BAST (.docx) belum tersedia di server.');
}

$zip = new ZipArchive();
if ($zip->open($tmpDocx) !== true) {
    die('Gagal membuka arsip .docx.');
}

// Prepare image relationships starting at rId50 so we never collide with template rIds
$extraRelsXml = '';
$docPrCounter = 100;
$embeddedPhotos = [];
$hasPng = false;
$hasJpg = false;
$hasWebp = false;

foreach ($photos as $idx => $ph) {
    $ext = strtolower(pathinfo($ph['path'], PATHINFO_EXTENSION));
    if ($ext === 'png') $hasPng = true;
    elseif ($ext === 'webp') $hasWebp = true;
    else { $ext = 'jpeg'; $hasJpg = true; }

    $mediaName = 'bast_photo_' . ($idx + 1) . '.' . $ext;
    $rId = 'rId' . (50 + $idx);
    $zip->addFile($ph['path'], 'word/media/' . $mediaName);
    $extraRelsXml .= '<Relationship Id="' . $rId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/' . $mediaName . '"/>';

    $maxW = !empty($ph['wide']) ? 14.8 : 7.3;
    $maxH = !empty($ph['wide']) ? 8.5 : 5.2;
    list($cx, $cy) = docxCalcImageEmu($ph['path'], $maxW, $maxH);
    $embeddedPhotos[] = [
        'rId' => $rId,
        'cx' => $cx,
        'cy' => $cy,
        'label' => $ph['label'],
        'wide' => !empty($ph['wide']),
        'docPrId' => $docPrCounter++,
    ];
}

// Update [Content_Types].xml if needed
$ctXml = $zip->getFromName('[Content_Types].xml');
if ($ctXml !== false) {
    if ($hasJpg && strpos($ctXml, 'Extension="jpeg"') === false) {
        $ctXml = str_replace('</Types>', '<Default Extension="jpeg" ContentType="image/jpeg"/></Types>', $ctXml);
    }
    if ($hasPng && strpos($ctXml, 'Extension="png"') === false) {
        $ctXml = str_replace('</Types>', '<Default Extension="png" ContentType="image/png"/></Types>', $ctXml);
    }
    if ($hasWebp && strpos($ctXml, 'Extension="webp"') === false) {
        $ctXml = str_replace('</Types>', '<Default Extension="webp" ContentType="image/webp"/></Types>', $ctXml);
    }
    $zip->addFromString('[Content_Types].xml', $ctXml);
}

// Update word/_rels/document.xml.rels (keep header/footer/styles, remove sample image2/image3 if desired)
$relsXml = $zip->getFromName('word/_rels/document.xml.rels');
if ($relsXml !== false && $extraRelsXml !== '') {
    $relsXml = str_replace('</Relationships>', $extraRelsXml . '</Relationships>', $relsXml);
    $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
}

$tblBorders = '<w:tblBorders>'
    . '<w:top w:val="single" w:sz="6" w:space="0" w:color="334155"/>'
    . '<w:left w:val="single" w:sz="6" w:space="0" w:color="334155"/>'
    . '<w:bottom w:val="single" w:sz="6" w:space="0" w:color="334155"/>'
    . '<w:right w:val="single" w:sz="6" w:space="0" w:color="334155"/>'
    . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="94A3B8"/>'
    . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="94A3B8"/>'
    . '</w:tblBorders>';

// Build clean, structured OOXML body matching NEW TEMPLATE BAST YIMM.docx
$body = '';
$body .= docxParagraph('BERITA ACARA SERAH TERIMA PEKERJAAN', ['align' => 'center', 'bold' => true, 'underline' => true, 'size' => 28, 'after' => 40]);
$body .= docxParagraph($noBast, ['align' => 'center', 'bold' => true, 'size' => 22, 'after' => 220]);
$body .= docxParagraph($hariTanggal, ['after' => 140]);

$body .= docxParagraph('PIHAK PERTAMA (PENERIMA)', ['bold' => true, 'before' => 60, 'after' => 40]);
$body .= docxKeyValParagraph('Nama', $p1Nama ?: '........................................');
$body .= docxKeyValParagraph('Jabatan', $p1Jabatan ?: '........................................');
$body .= docxKeyValParagraph('Mewakili', $p1Perusahaan ?: '-');
$body .= docxKeyValParagraph('Alamat', $p1Alamat ?: '-');

$body .= docxParagraph('PIHAK KEDUA (PELAKSANA)', ['bold' => true, 'before' => 120, 'after' => 40]);
$body .= docxKeyValParagraph('Nama', $p2Nama);
$body .= docxKeyValParagraph('Jabatan', $p2Jabatan);
$body .= docxKeyValParagraph('Mewakili', $p2Perusahaan);
$body .= docxKeyValParagraph('Alamat', $p2Alamat);

$body .= docxParagraph('Dengan rincian pekerjaan sebagai berikut:', ['before' => 140, 'after' => 80]);

// Table 1: Rincian Pekerjaan (No, NO. PO, Lokasi Camera, Jenis Pekerjaan)
$body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $tblBorders . '<w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="110" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="110" w:type="dxa"/></w:tblCellMar></w:tblPr>';
$body .= '<w:tr>'
    . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('No', ['bold' => true, 'align' => 'center', 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="2500" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('NO. PO', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="3126" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Lokasi Camera', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="2700" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Jenis Pekerjaan', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '</w:tr>';
foreach ($poRows as $idx => $pr) {
    $body .= '<w:tr>'
        . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/></w:tcPr>' . docxParagraph((string)($idx + 1), ['align' => 'center', 'after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="2500" w:type="dxa"/></w:tcPr>' . docxParagraph($pr['no_po'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="3126" w:type="dxa"/></w:tcPr>' . docxParagraph($pr['lokasi'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="2700" w:type="dxa"/></w:tcPr>' . docxParagraph($pr['jenis'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '</w:tr>';
}
$body .= '</w:tbl>';

$body .= docxParagraph('Dengan ini PIHAK KEDUA menyatakan bahwa pekerjaan telah selesai dilaksanakan, dan PIHAK PERTAMA telah menerima hasil pekerjaan dalam kondisi Selesai.', ['before' => 160, 'after' => 100]);
$body .= docxParagraph('Demikian berita acara serah terima pekerjaan ini dibuat dan ditandatangani oleh kedua belah pihak sebagai bukti serah terima.', ['after' => 200]);
$body .= docxParagraph($kotaTglTtd, ['align' => 'right', 'bold' => true, 'after' => 120]);

// Signature 2-column borderless table
$sigBorders = '<w:tblBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/><w:insideH w:val="none"/><w:insideV w:val="none"/></w:tblBorders>';
$body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $sigBorders . '</w:tblPr><w:tr>'
    . '<w:tc><w:tcPr><w:tcW w:w="4513" w:type="dxa"/></w:tcPr>'
    . docxParagraph('Pihak Pertama', ['align' => 'center', 'bold' => true, 'after' => 900])
    . docxParagraph('( ' . ($p1Nama ?: '............................') . ' )', ['align' => 'center', 'bold' => true, 'underline' => true, 'after' => 20])
    . docxParagraph($p1Jabatan ?: '', ['align' => 'center', 'size' => 20, 'after' => 0])
    . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="4513" w:type="dxa"/></w:tcPr>'
    . docxParagraph('Pihak Kedua', ['align' => 'center', 'bold' => true, 'after' => 900])
    . docxParagraph('( ' . ($p2Nama ?: '............................') . ' )', ['align' => 'center', 'bold' => true, 'underline' => true, 'after' => 20])
    . docxParagraph($p2Jabatan ?: '', ['align' => 'center', 'size' => 20, 'after' => 0])
    . '</w:tc>'
    . '</w:tr></w:tbl>';

// Page break for Lampiran
$body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
$body .= docxParagraph('Lampiran dan lain-lain', ['bold' => true, 'underline' => true, 'size' => 26, 'after' => 60]);
$body .= docxParagraph('Rincian pekerjaan yang telah selesai dilaksanakan:', ['after' => 80]);

// Table 1.1 Deskripsi Pekerjaan
$body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $tblBorders . '<w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>';
$body .= '<w:tr>'
    . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('No', ['bold' => true, 'align' => 'center', 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="6126" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Deskripsi Pekerjaan', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Keterangan / Volume', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '</w:tr>';
foreach ($deskRows as $idx => $dr) {
    $body .= '<w:tr>'
        . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/></w:tcPr>' . docxParagraph((string)($idx + 1), ['align' => 'center', 'after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="6126" w:type="dxa"/></w:tcPr>' . docxParagraph($dr['uraian'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/></w:tcPr>' . docxParagraph($dr['volume'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '</w:tr>';
}
$body .= '</w:tbl>';
$body .= docxParagraph('Table 1.1 (Job Description)', ['size' => 18, 'before' => 40, 'after' => 140]);

// Table 1.2 Device User & Password
$body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $tblBorders . '<w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>';
$body .= '<w:tr>'
    . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('No', ['bold' => true, 'align' => 'center', 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="3926" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Nama Unit / Device', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Username', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/><w:shd w:val="clear" w:fill="F1F5F9"/></w:tcPr>' . docxParagraph('Password', ['bold' => true, 'after' => 0]) . '</w:tc>'
    . '</w:tr>';
foreach ($devRows as $idx => $dv) {
    $body .= '<w:tr>'
        . '<w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/></w:tcPr>' . docxParagraph((string)($idx + 1), ['align' => 'center', 'after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="3926" w:type="dxa"/></w:tcPr>' . docxParagraph($dv['nama_unit'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/></w:tcPr>' . docxParagraph($dv['username'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/></w:tcPr>' . docxParagraph($dv['password'] ?? '-', ['after' => 0]) . '</w:tc>'
        . '</w:tr>';
}
$body .= '</w:tbl>';
$body .= docxParagraph('Table 1.2 (Device User & Password)', ['size' => 18, 'before' => 40, 'after' => 180]);

// Image Screen Shoot System (Display Keseluruhan + Titik Kamera)
$body .= docxParagraph('Dokumentasi Display CCTV & Titik Kamera', ['bold' => true, 'underline' => true, 'size' => 24, 'before' => 80, 'after' => 100]);

if (empty($embeddedPhotos)) {
    $body .= docxParagraph('(Belum ada foto display / titik kamera yang diupload teknisi)', ['size' => 20, 'after' => 120]);
} else {
    $widePhotos = array_values(array_filter($embeddedPhotos, function ($x) { return $x['wide']; }));
    $gridPhotos = array_values(array_filter($embeddedPhotos, function ($x) { return !$x['wide']; }));

    // Overall CCTV Display (Full-width bordered card)
    foreach ($widePhotos as $wp) {
        $body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $tblBorders . '<w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tr>'
            . '<w:tc><w:tcPr><w:tcW w:w="9026" w:type="dxa"/></w:tcPr>'
            . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="60"/></w:pPr><w:r>'
            . docxDrawingXml($wp['rId'], $wp['docPrId'], $wp['cx'], $wp['cy'], $wp['label'])
            . '</w:r></w:p>'
            . docxParagraph($wp['label'], ['align' => 'center', 'bold' => true, 'size' => 20, 'after' => 20])
            . '</w:tc></w:tr></w:tbl>';
        $body .= docxParagraph('', ['after' => 80]);
    }

    // Individual Camera Points in 2-Column Table
    if (!empty($gridPhotos)) {
        $body .= '<w:tbl><w:tblPr><w:tblW w:w="9026" w:type="dxa"/>' . $tblBorders . '<w:tblCellMar><w:top w:w="90" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr>';
        $chunks = array_chunk($gridPhotos, 2);
        foreach ($chunks as $pair) {
            $body .= '<w:tr>';
            for ($c = 0; $c < 2; $c++) {
                if (isset($pair[$c])) {
                    $gp = $pair[$c];
                    $body .= '<w:tc><w:tcPr><w:tcW w:w="4513" w:type="dxa"/></w:tcPr>'
                        . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="60"/></w:pPr><w:r>'
                        . docxDrawingXml($gp['rId'], $gp['docPrId'], $gp['cx'], $gp['cy'], $gp['label'])
                        . '</w:r></w:p>'
                        . docxParagraph($gp['label'], ['align' => 'center', 'bold' => true, 'size' => 19, 'after' => 20])
                        . '</w:tc>';
                } else {
                    $body .= '<w:tc><w:tcPr><w:tcW w:w="4513" w:type="dxa"/></w:tcPr>' . docxParagraph('', ['after' => 0]) . '</w:tc>';
                }
            }
            $body .= '</w:tr>';
        }
        $body .= '</w:tbl>';
    }
}

// Keep template header/footer references via sectPr
$origDocXml = $zip->getFromName('word/document.xml');
$sectPr = '<w:sectPr><w:headerReference w:type="default" r:id="rId9"/><w:footerReference w:type="default" r:id="rId10"/><w:headerReference w:type="first" r:id="rId11"/><w:footerReference w:type="first" r:id="rId12"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1702" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>';
if ($origDocXml !== false && preg_match('/<w:sectPr[^>]*>.*?<w:headerReference.*?<\/w:sectPr>/s', $origDocXml, $mSect)) {
    $sectPr = $mSect[0];
}

// Preserve root <w:document ...> tag with all OOXML namespaces from template
$docOpenTag = '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
if ($origDocXml !== false && preg_match('/<w:document\b[^>]*>/', $origDocXml, $mOpen)) {
    $docOpenTag = $mOpen[0];
}

$finalDocXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . $docOpenTag
    . '<w:body>' . $body . $sectPr . '</w:body></w:document>';

$zip->addFromString('word/document.xml', $finalDocXml);
$zip->close();

$safeCustFile = preg_replace('/[^a-zA-Z0-9_-]/', '_', $p1Perusahaan ?: $kode);
$downloadName = 'BAST_' . $kode . '_' . trim($safeCustFile, '_') . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($tmpDocx));
header('Cache-Control: max-age=0');
readfile($tmpDocx);
@unlink($tmpDocx);
exit;

