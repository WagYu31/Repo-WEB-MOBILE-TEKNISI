<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include __DIR__ . '/conn.php';
include __DIR__ . '/bast_init.php';

$action = $_REQUEST['action'] ?? 'get_detail';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
$host = $_SERVER['HTTP_HOST'] ?? 'jadwal.id-giti.com';
$baseUploadUrl = $scheme . '://' . $host . '/staff/uploads/bast/';

function bastSaveUploadedFile($fileField, $prefix = 'bast') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmp = $_FILES[$fileField]['tmp_name'];
    $orig = $_FILES[$fileField]['name'] ?? 'image.jpg';
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $ext = 'jpg';
    }
    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/uploads/bast/' . $filename;
    if (move_uploaded_file($tmp, $dest)) {
        return $filename;
    }
    return null;
}

// ─── 1. GET BAST DETAIL BY KODE ────────────────────────────────────
if ($action === 'get_detail') {
    $kode = trim($_GET['kode'] ?? '');
    $customerId = intval($_GET['customer_id'] ?? 0);

    if ($kode === '') {
        echo json_encode(['success' => false, 'message' => 'Kode kegiatan kosong']);
        exit;
    }

    // Resolve customer + wajib_bast from kegiatan if customer_id not passed
    $wajibBast = false;
    $custName = '';
    $stmtK = $conn->prepare("SELECT k.id, k.customer_id, c.nama AS nama_cust, COALESCE(c.wajib_bast, 0) AS wajib_bast
                             FROM kegiatan k
                             LEFT JOIN customer c ON k.customer_id = c.id
                             WHERE k.kode = ? AND k.deleted_at IS NULL
                             ORDER BY k.id DESC LIMIT 1");
    if ($stmtK) {
        $stmtK->bind_param('s', $kode);
        $stmtK->execute();
        if ($rk = $stmtK->get_result()->fetch_assoc()) {
            $wajibBast = intval($rk['wajib_bast']) === 1;
            $custName = $rk['nama_cust'] ?? '';
            if ($customerId <= 0) {
                $customerId = intval($rk['customer_id']);
            }
        }
        $stmtK->close();
    } elseif ($customerId > 0) {
        $resC = mysqli_query($conn, "SELECT nama, COALESCE(wajib_bast, 0) AS wajib_bast FROM customer WHERE id = $customerId LIMIT 1");
        if ($resC && ($rc = mysqli_fetch_assoc($resC))) {
            $wajibBast = intval($rc['wajib_bast']) === 1;
            $custName = $rc['nama'] ?? '';
        }
    }

    $stmtB = $conn->prepare("SELECT * FROM bast_laporan WHERE kode = ? LIMIT 1");
    $stmtB->bind_param('s', $kode);
    $stmtB->execute();
    $bast = $stmtB->get_result()->fetch_assoc();
    $stmtB->close();

    $titikList = [];
    if ($bast) {
        $bast['foto_display_url'] = !empty($bast['foto_display_utama'])
            ? ($baseUploadUrl . $bast['foto_display_utama'])
            : null;
        $bastId = intval($bast['id']);
        $resT = mysqli_query($conn, "SELECT id, urutan, foto_titik, keterangan FROM bast_titik_kamera WHERE bast_id = $bastId ORDER BY urutan ASC, id ASC");
        if ($resT) {
            while ($rt = mysqli_fetch_assoc($resT)) {
                $rt['foto_url'] = $baseUploadUrl . $rt['foto_titik'];
                $titikList[] = $rt;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'wajib_bast' => $wajibBast,
        'customer_name' => $custName,
        'has_bast' => $bast !== null,
        'bast' => $bast,
        'titik_kamera' => $titikList,
    ]);
    exit;
}

// ─── 2. GET PENDING / RECENT BAST TASKS FOR TEKNISI (FOR HOME / WORK FROM HOME) ───
if ($action === 'pending_for_teknisi') {
    $teknisiId = intval($_GET['teknisi_id'] ?? 0);
    if ($teknisiId <= 0) {
        echo json_encode(['success' => true, 'pending' => [], 'completed' => []]);
        exit;
    }

    $sql = "SELECT k.id AS kegiatan_id, k.kode, k.kegiatan, k.keterangan, k.jadwal,
                   c.id AS customer_id, c.nama AS nama_customer, c.alamat AS alamat_customer,
                   COALESCE(c.wajib_bast, 0) AS wajib_bast,
                   b.id AS bast_id, b.foto_display_utama, b.updated_at AS bast_updated_at,
                   (SELECT COUNT(*) FROM bast_titik_kamera bt WHERE bt.bast_id = b.id) AS jumlah_titik
            FROM pelaksanaan_kegiatan pk
            JOIN kegiatan k ON pk.kegiatan_id = k.id
            JOIN customer c ON k.customer_id = c.id
            LEFT JOIN bast_laporan b ON b.kode = k.kode
            WHERE pk.teknisi_id = ?
              AND pk.deleted_at IS NULL
              AND k.deleted_at IS NULL
              AND pk.status IN ('menunggu laporan', 'selesai')
              AND (c.wajib_bast = 1 OR b.id IS NOT NULL)
            GROUP BY k.kode
            ORDER BY MAX(pk.waktu_selesai) DESC
            LIMIT 30";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $teknisiId);
    $stmt->execute();
    $res = $stmt->get_result();

    $pending = [];
    $completed = [];
    while ($r = $res->fetch_assoc()) {
        $hasBast = !empty($r['bast_id']) && !empty($r['foto_display_utama']);
        $item = [
            'kegiatan_id' => intval($r['kegiatan_id']),
            'kode' => $r['kode'],
            'kegiatan' => $r['kegiatan'],
            'keterangan' => $r['keterangan'] ?? '',
            'jadwal' => $r['jadwal'],
            'customer_id' => intval($r['customer_id']),
            'nama_customer' => $r['nama_customer'],
            'alamat_customer' => $r['alamat_customer'] ?? '',
            'wajib_bast' => intval($r['wajib_bast']) === 1,
            'has_bast' => $hasBast,
            'jumlah_titik' => intval($r['jumlah_titik']),
            'bast_updated_at' => $r['bast_updated_at'],
        ];
        if ($hasBast) {
            $completed[] = $item;
        } else {
            $pending[] = $item;
        }
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'pending' => $pending,
        'completed' => $completed,
    ]);
    exit;
}

// ─── 3. SUBMIT / UPDATE BAST FROM MOBILE APP ────────────────────────
if ($action === 'submit_mobile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode'] ?? '');
    $kegiatanId = intval($_POST['kegiatan_id'] ?? 0);
    $teknisiId = intval($_POST['teknisi_id'] ?? 0);
    $ketDisplay = trim($_POST['ket_display_utama'] ?? '');
    $existingDisplay = trim($_POST['existing_foto_display'] ?? '');

    if ($kode === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kode kegiatan wajib diisi']);
        exit;
    }

    // Lookup kegiatan & customer info for smart defaults
    $custId = intval($_POST['customer_id'] ?? 0);
    $custNama = '';
    $custAlamat = '';
    $jenisKeg = 'Pemasangan dan Instalasi CCTV';
    $stmtK = $conn->prepare("SELECT k.id, k.customer_id, k.kegiatan, k.keterangan, c.nama AS nama_cust, c.alamat AS alamat_cust
                             FROM kegiatan k
                             LEFT JOIN customer c ON k.customer_id = c.id
                             WHERE k.kode = ? AND k.deleted_at IS NULL
                             ORDER BY k.id DESC LIMIT 1");
    if ($stmtK) {
        $stmtK->bind_param('s', $kode);
        $stmtK->execute();
        if ($rk = $stmtK->get_result()->fetch_assoc()) {
            if ($kegiatanId <= 0) $kegiatanId = intval($rk['id']);
            if ($custId <= 0) $custId = intval($rk['customer_id']);
            $custNama = $rk['nama_cust'] ?? '';
            $custAlamat = $rk['alamat_cust'] ?? '';
            if (!empty($rk['keterangan'])) {
                $jenisKeg = $rk['keterangan'];
            } elseif (!empty($rk['kegiatan'])) {
                $jenisKeg = ucwords($rk['kegiatan']) . ' CCTV';
            }
        }
        $stmtK->close();
    }

    // Upload overall display photo if provided
    $uploadedDisplay = bastSaveUploadedFile('foto_display_utama', 'display_' . preg_replace('/[^a-zA-Z0-9]/', '', $kode));
    $finalDisplayFoto = $uploadedDisplay ?: $existingDisplay;

    // Check if bast_laporan already exists for this kode
    $stmtChk = $conn->prepare("SELECT id, foto_display_utama FROM bast_laporan WHERE kode = ? LIMIT 1");
    $stmtChk->bind_param('s', $kode);
    $stmtChk->execute();
    $existingBast = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    if (!$finalDisplayFoto && $existingBast && !empty($existingBast['foto_display_utama'])) {
        $finalDisplayFoto = $existingBast['foto_display_utama'];
    }

    if (!$finalDisplayFoto) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Foto display CCTV keseluruhan wajib diupload']);
        exit;
    }

    if ($existingBast) {
        $bastId = intval($existingBast['id']);
        $stmtUp = $conn->prepare("UPDATE bast_laporan SET kegiatan_id = ?, customer_id = ?, teknisi_id = ?, foto_display_utama = ?, ket_display_utama = ?, updated_at = NOW() WHERE id = ?");
        $stmtUp->bind_param('iiissi', $kegiatanId, $custId, $teknisiId, $finalDisplayFoto, $ketDisplay, $bastId);
        $stmtUp->execute();
        $stmtUp->close();
    } else {
        // Count existing BAST this month to generate neat default No. BAST
        $resCnt = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bast_laporan WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $seq = intval(($resCnt ? mysqli_fetch_assoc($resCnt)['cnt'] : 0)) + 1;
        $defaultNoBast = sprintf('No.%03d-%s/GRAV-BA/%s', $seq, bastRomanMonth(), date('y'));
        $defaultHariTgl = 'Pada hari ini, ' . bastIndonesianDay() . ' ' . bastIndonesianDate() . ', telah dilakukan serah terima pekerjaan antara:';
        $defaultKotaTgl = 'Jakarta, ' . bastIndonesianDate();
        $defaultPihak2Alamat = 'Harco Mangga Dua Plaza Blok A3-156 Jakarta – Indonesia 10730';

        $defaultPoJson = json_encode([
            [
                'no_po' => '-',
                'lokasi' => $custNama ?: '-',
                'jenis' => $jenisKeg,
            ]
        ], JSON_UNESCAPED_UNICODE);

        $defaultDeskJson = json_encode([
            ['uraian' => 'Penarikan Kabel Cat6/LAN Untuk CCTV', 'volume' => '- Tarikan'],
            ['uraian' => 'Pemasangan Kamera CCTV', 'volume' => '- Titik'],
        ], JSON_UNESCAPED_UNICODE);

        $defaultDevJson = json_encode([
            ['nama_unit' => 'NVR / DVR Utama', 'username' => 'admin', 'password' => '-'],
            ['nama_unit' => 'Kamera CCTV', 'username' => 'admin', 'password' => '-'],
        ], JSON_UNESCAPED_UNICODE);

        $stmtIns = $conn->prepare("INSERT INTO bast_laporan
            (kegiatan_id, kode, customer_id, teknisi_id, foto_display_utama, ket_display_utama,
             no_bast, hari_tanggal_teks, pihak1_nama, pihak1_jabatan, pihak1_perusahaan, pihak1_alamat,
             pihak2_nama, pihak2_jabatan, pihak2_perusahaan, pihak2_alamat,
             rincian_po_json, deskripsi_pekerjaan_json, device_credentials_json, kota_tanggal_ttd, status_bast)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, 'Resmina', 'Manager Umum', 'GraViTTi Technology', ?, ?, ?, ?, ?, 'submitted')");
        $stmtIns->bind_param(
            'isiisssssssssss',
            $kegiatanId,
            $kode,
            $custId,
            $teknisiId,
            $finalDisplayFoto,
            $ketDisplay,
            $defaultNoBast,
            $defaultHariTgl,
            $custNama,
            $custAlamat,
            $defaultPihak2Alamat,
            $defaultPoJson,
            $defaultDeskJson,
            $defaultDevJson,
            $defaultKotaTgl
        );
        if (!$stmtIns->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan BAST: ' . $stmtIns->error]);
            exit;
        }
        $bastId = intval($stmtIns->insert_id);
        $stmtIns->close();
    }

    // Save camera points (bast_titik_kamera)
    $titikCount = intval($_POST['titik_count'] ?? 0);
    $newTitikRows = [];
    for ($i = 0; $i < $titikCount; $i++) {
        $ketTitik = trim($_POST["ket_titik_$i"] ?? '');
        $existingTitikFoto = trim($_POST["existing_foto_titik_$i"] ?? '');
        $uploadedTitik = bastSaveUploadedFile("foto_titik_$i", 'cam' . ($i + 1) . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $kode));
        $finalTitikFoto = $uploadedTitik ?: $existingTitikFoto;
        if ($finalTitikFoto !== '') {
            $newTitikRows[] = [
                'urutan' => count($newTitikRows) + 1,
                'foto_titik' => $finalTitikFoto,
                'keterangan' => $ketTitik,
            ];
        }
    }

    // Replace camera point rows cleanly if titik_count was sent
    if (isset($_POST['titik_count'])) {
        mysqli_query($conn, "DELETE FROM bast_titik_kamera WHERE bast_id = $bastId");
        $stmtPt = $conn->prepare("INSERT INTO bast_titik_kamera (bast_id, urutan, foto_titik, keterangan) VALUES (?, ?, ?, ?)");
        foreach ($newTitikRows as $pt) {
            $stmtPt->bind_param('iiss', $bastId, $pt['urutan'], $pt['foto_titik'], $pt['keterangan']);
            $stmtPt->execute();
        }
        $stmtPt->close();

        // Also update default Pemasangan Kamera CCTV titik count if still default "- Titik"
        if (count($newTitikRows) > 0) {
            $resDesk = mysqli_query($conn, "SELECT deskripsi_pekerjaan_json FROM bast_laporan WHERE id = $bastId LIMIT 1");
            if ($resDesk && ($rd = mysqli_fetch_assoc($resDesk))) {
                $deskArr = json_decode($rd['deskripsi_pekerjaan_json'] ?? '[]', true);
                if (is_array($deskArr)) {
                    $changed = false;
                    foreach ($deskArr as &$dRow) {
                        if (stripos($dRow['uraian'] ?? '', 'Kamera') !== false && ($dRow['volume'] ?? '') === '- Titik') {
                            $dRow['volume'] = count($newTitikRows) . ' Titik';
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        $newJson = json_encode($deskArr, JSON_UNESCAPED_UNICODE);
                        $stUpD = $conn->prepare("UPDATE bast_laporan SET deskripsi_pekerjaan_json = ? WHERE id = ?");
                        $stUpD->bind_param('si', $newJson, $bastId);
                        $stUpD->execute();
                        $stUpD->close();
                    }
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data BAST berhasil disimpan',
        'bast_id' => $bastId,
        'jumlah_titik' => count($newTitikRows),
    ]);
    exit;
}

// ─── 4. TOGGLE CUSTOMER WAJIB BAST (WEB ADMIN) ──────────────────────
if ($action === 'toggle_customer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/session.php';
    $custId = intval($_POST['customer_id'] ?? 0);
    $wajib = intval($_POST['wajib_bast'] ?? 0) ? 1 : 0;
    if ($custId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID customer tidak valid']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE customer SET wajib_bast = ? WHERE id = ?");
    $stmt->bind_param('ii', $wajib, $custId);
    $ok = $stmt->execute();
    $stmt->close();

    $resTot = mysqli_query($conn, "SELECT COUNT(*) AS tot FROM customer WHERE deleted_at IS NULL AND wajib_bast = 1");
    $totWajib = intval(($resTot ? mysqli_fetch_assoc($resTot)['tot'] : 0));

    echo json_encode([
        'success' => $ok,
        'wajib_bast' => $wajib,
        'total_wajib' => $totWajib,
    ]);
    exit;
}

// ─── 5. SAVE EDITED DOCUMENT FIELDS FROM WEB EDITOR ─────────────────
if ($action === 'save_web_doc' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/session.php';
    $kode = trim($_POST['kode'] ?? '');
    if ($kode === '') {
        echo json_encode(['success' => false, 'message' => 'Kode tidak valid']);
        exit;
    }

    $noBast = trim($_POST['no_bast'] ?? '');
    $hariTanggal = trim($_POST['hari_tanggal_teks'] ?? '');
    $p1Nama = trim($_POST['pihak1_nama'] ?? '');
    $p1Jabatan = trim($_POST['pihak1_jabatan'] ?? '');
    $p1Perusahaan = trim($_POST['pihak1_perusahaan'] ?? '');
    $p1Alamat = trim($_POST['pihak1_alamat'] ?? '');
    $p2Nama = trim($_POST['pihak2_nama'] ?? '');
    $p2Jabatan = trim($_POST['pihak2_jabatan'] ?? '');
    $p2Perusahaan = trim($_POST['pihak2_perusahaan'] ?? '');
    $p2Alamat = trim($_POST['pihak2_alamat'] ?? '');
    $kotaTgl = trim($_POST['kota_tanggal_ttd'] ?? '');
    $ketDisplay = trim($_POST['ket_display_utama'] ?? '');
    $poJson = $_POST['rincian_po_json'] ?? '[]';
    $deskJson = $_POST['deskripsi_pekerjaan_json'] ?? '[]';
    $devJson = $_POST['device_credentials_json'] ?? '[]';

    // Ensure record exists even if Admin opens editor before technician uploads
    $stmtChk = $conn->prepare("SELECT id FROM bast_laporan WHERE kode = ? LIMIT 1");
    $stmtChk->bind_param('s', $kode);
    $stmtChk->execute();
    $rowB = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    if ($rowB) {
        $bastId = intval($rowB['id']);
        $stmtUp = $conn->prepare("UPDATE bast_laporan SET
            no_bast = ?, hari_tanggal_teks = ?,
            pihak1_nama = ?, pihak1_jabatan = ?, pihak1_perusahaan = ?, pihak1_alamat = ?,
            pihak2_nama = ?, pihak2_jabatan = ?, pihak2_perusahaan = ?, pihak2_alamat = ?,
            rincian_po_json = ?, deskripsi_pekerjaan_json = ?, device_credentials_json = ?,
            kota_tanggal_ttd = ?, ket_display_utama = ?, status_bast = 'reviewed', updated_at = NOW()
            WHERE id = ?");
        $stmtUp->bind_param(
            'sssssssssssssssi',
            $noBast, $hariTanggal,
            $p1Nama, $p1Jabatan, $p1Perusahaan, $p1Alamat,
            $p2Nama, $p2Jabatan, $p2Perusahaan, $p2Alamat,
            $poJson, $deskJson, $devJson,
            $kotaTgl, $ketDisplay, $bastId
        );
        $stmtUp->execute();
        $stmtUp->close();
    } else {
        $kegId = 0;
        $custId = 0;
        $stK = $conn->prepare("SELECT id, customer_id FROM kegiatan WHERE kode = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $stK->bind_param('s', $kode);
        $stK->execute();
        if ($rk = $stK->get_result()->fetch_assoc()) {
            $kegId = intval($rk['id']);
            $custId = intval($rk['customer_id']);
        }
        $stK->close();

        $stmtIns = $conn->prepare("INSERT INTO bast_laporan
            (kegiatan_id, kode, customer_id, no_bast, hari_tanggal_teks,
             pihak1_nama, pihak1_jabatan, pihak1_perusahaan, pihak1_alamat,
             pihak2_nama, pihak2_jabatan, pihak2_perusahaan, pihak2_alamat,
             rincian_po_json, deskripsi_pekerjaan_json, device_credentials_json,
             kota_tanggal_ttd, ket_display_utama, status_bast)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'reviewed')");
        $stmtIns->bind_param(
            'isisssssssssssssss',
            $kegId, $kode, $custId, $noBast, $hariTanggal,
            $p1Nama, $p1Jabatan, $p1Perusahaan, $p1Alamat,
            $p2Nama, $p2Jabatan, $p2Perusahaan, $p2Alamat,
            $poJson, $deskJson, $devJson,
            $kotaTgl, $ketDisplay
        );
        $stmtIns->execute();
        $bastId = intval($stmtIns->insert_id);
        $stmtIns->close();
    }

    // Update individual titik kamera captions if edited on web
    if (!empty($_POST['titik_captions_json'])) {
        $caps = json_decode($_POST['titik_captions_json'], true);
        if (is_array($caps)) {
            $stCap = $conn->prepare("UPDATE bast_titik_kamera SET keterangan = ? WHERE id = ? AND bast_id = ?");
            foreach ($caps as $cItem) {
                $tid = intval($cItem['id'] ?? 0);
                $ket = trim($cItem['keterangan'] ?? '');
                if ($tid > 0) {
                    $stCap->bind_param('sii', $ket, $tid, $bastId);
                    $stCap->execute();
                }
            }
            $stCap->close();
        }
    }

    echo json_encode(['success' => true, 'bast_id' => $bastId, 'message' => 'Perubahan dokumen BAST berhasil disimpan']);
    exit;
}

// ─── 6. UPLOAD NEW DOCX TEMPLATE (WEB ADMIN) ────────────────────────
if ($action === 'upload_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/session.php';
    if (!isset($_FILES['template_docx']) || $_FILES['template_docx']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Pilih file .docx terlebih dahulu']);
        exit;
    }
    $ext = strtolower(pathinfo($_FILES['template_docx']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        echo json_encode(['success' => false, 'message' => 'Format file harus .docx (Microsoft Word)']);
        exit;
    }
    $dest = __DIR__ . '/assets/templates/CUSTOM_TEMPLATE_BAST.docx';
    if (move_uploaded_file($_FILES['template_docx']['tmp_name'], $dest)) {
        // Also extract header image if present in word/media/image1.png
        if (class_exists('ZipArchive')) {
            $za = new ZipArchive();
            if ($za->open($dest) === true) {
                $imgData = $za->getFromName('word/media/image1.png');
                if ($imgData !== false) {
                    file_put_contents(__DIR__ . '/assets/templates/bast_kop_header.png', $imgData);
                }
                $za->close();
            }
        }
        echo json_encode(['success' => true, 'message' => 'Template BAST (.docx) terbaru berhasil dipasang!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file template']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
