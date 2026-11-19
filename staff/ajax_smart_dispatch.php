<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir. Silakan login ulang.']);
    exit;
}

include 'conn.php';
include_once __DIR__ . '/include/geo_dispatch_helper.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'load';

if ($action === 'load') {
    $selectedDate = $_GET['tanggal'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $selectedDate = date('Y-m-d');
    }

    // 1. Fetch all active technicians
    $technicians = [];
    $tekMap = [];
    $resTek = $conn->query("SELECT id, nama, telp FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC");
    if ($resTek) {
        while ($t = $resTek->fetch_assoc()) {
            $tid = intval($t['id']);
            $item = [
                'id' => $tid,
                'nama' => $t['nama'],
                'telp' => $t['telp'] ?? '',
                'live_lat' => null,
                'live_lon' => null,
                'is_online' => false,
            ];
            $technicians[] = $item;
            $tekMap[$tid] = count($technicians) - 1;
        }
        $resTek->free();
    }

    // Attach live GPS if selected date is today
    if ($selectedDate === date('Y-m-d')) {
        $resLive = @$conn->query("SELECT teknisi_id, latitude, longitude, COALESCE(gps_disabled, 0) AS gps_disabled, TIMESTAMPDIFF(SECOND, updated_at, NOW()) AS age_sec FROM teknisi_live_location WHERE TIMESTAMPDIFF(HOUR, updated_at, NOW()) <= 8");
        if ($resLive) {
            while ($lv = $resLive->fetch_assoc()) {
                $tid = intval($lv['teknisi_id']);
                if (isset($tekMap[$tid]) && intval($lv['gps_disabled']) === 0 && dispatchIsValidIndoCoord($lv['latitude'], $lv['longitude'])) {
                    $idx = $tekMap[$tid];
                    $technicians[$idx]['live_lat'] = round(floatval($lv['latitude']), 6);
                    $technicians[$idx]['live_lon'] = round(floatval($lv['longitude']), 6);
                    $technicians[$idx]['is_online'] = (intval($lv['age_sec']) <= 300);
                }
            }
            $resLive->free();
        }
    }

    // 2. Fetch Waiting List items + Scheduled items on selectedDate to batch-load cust_coordinate
    $waitingRaw = [];
    $custIds = [];
    $sqlWaiting = "SELECT k.id, k.kode, k.customer_id, k.kegiatan, k.jadwal, k.keterangan, k.lat, k.lon, k.alamat_lokasi, k.created_at,
                          c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat AS cust_alamat
                   FROM kegiatan k
                   LEFT JOIN customer c ON k.customer_id = c.id
                   WHERE k.status = 'waiting' AND k.deleted_at IS NULL
                   ORDER BY k.created_at ASC";
    $resW = $conn->query($sqlWaiting);
    if ($resW) {
        while ($r = $resW->fetch_assoc()) {
            $waitingRaw[] = $r;
            if (!empty($r['customer_id'])) {
                $custIds[intval($r['customer_id'])] = true;
            }
        }
        $resW->free();
    }

    $schedRaw = [];
    $schedKegIds = [];
    $stmtSched = $conn->prepare("SELECT k.id, k.kode, k.customer_id, k.kegiatan, k.jadwal, k.status, k.lat, k.lon, k.alamat_lokasi,
                                        c.nama AS nama_customer, c.alamat AS cust_alamat
                                 FROM kegiatan k
                                 LEFT JOIN customer c ON k.customer_id = c.id
                                 WHERE k.status NOT IN ('waiting', 'selesai by admin')
                                   AND DATE(k.jadwal) = ?
                                   AND k.deleted_at IS NULL
                                 ORDER BY k.jadwal ASC");
    if ($stmtSched) {
        $stmtSched->bind_param("s", $selectedDate);
        $stmtSched->execute();
        $resS = $stmtSched->get_result();
        while ($r = $resS->fetch_assoc()) {
            $schedRaw[] = $r;
            $schedKegIds[intval($r['id'])] = true;
            if (!empty($r['customer_id'])) {
                $custIds[intval($r['customer_id'])] = true;
            }
        }
        $stmtSched->close();
    }

    // Bulk load fallback customer coordinates
    $custCoordsMap = [];
    if (!empty($custIds)) {
        $cList = implode(',', array_keys($custIds));
        $resCC = @$conn->query("SELECT cust_id, lat, lon, address FROM cust_coordinate WHERE cust_id IN ($cList) AND deleted_at IS NULL ORDER BY id DESC");
        if ($resCC) {
            while ($cc = $resCC->fetch_assoc()) {
                $cid = intval($cc['cust_id']);
                if (!isset($custCoordsMap[$cid]) && dispatchIsValidIndoCoord($cc['lat'], $cc['lon'])) {
                    $custCoordsMap[$cid] = $cc;
                }
            }
            $resCC->free();
        }
    }

    // Format waiting items
    $waitingItems = [];
    foreach ($waitingRaw as $r) {
        $cid = intval($r['customer_id'] ?? 0);
        $z = dispatchResolveZoneAndCoords(
            $r['lat'],
            $r['lon'],
            $r['alamat_lokasi'] ?? '',
            $r['cust_alamat'] ?? '',
            '',
            $custCoordsMap[$cid] ?? null
        );
        $addr = !empty($r['alamat_lokasi']) ? $r['alamat_lokasi'] : ($r['cust_alamat'] ?? '');
        $reqTime = null;
        if (!empty($r['jadwal']) && $r['jadwal'] !== '0000-00-00 00:00:00') {
            $reqTime = date('H:i', strtotime($r['jadwal']));
            if ($reqTime === '00:00') $reqTime = null;
        }
        $waitingItems[] = [
            'id' => intval($r['id']),
            'kode' => $r['kode'] ?? '',
            'customer_id' => $cid,
            'customer' => $r['nama_customer'] ?? 'Customer',
            'telp' => $r['cust_nomor'] ?? '',
            'kegiatan' => $r['kegiatan'] ?? 'service',
            'keterangan' => $r['keterangan'] ?? '',
            'alamat' => $addr,
            'zone' => $z['zone'],
            'sub_area' => $z['sub_area'],
            'zone_label' => $z['label'],
            'zone_bg' => $z['bg'],
            'zone_color' => $z['color'],
            'zone_border' => $z['border'],
            'lat' => $z['lat'],
            'lon' => $z['lon'],
            'is_exact' => $z['is_exact'],
            'coord_source' => $z['coord_source'],
            'duration_min' => dispatchJobDurationMin($r['kegiatan'] ?? ''),
            'requested_jam' => $reqTime,
        ];
    }

    // Group existing teams & stops on selectedDate (deduplicate by kode keeping latest id)
    $schedByKode = [];
    foreach ($schedRaw as $sr) {
        $kd = $sr['kode'];
        if (!isset($schedByKode[$kd]) || intval($sr['id']) > intval($schedByKode[$kd]['id'])) {
            $schedByKode[$kd] = $sr;
        }
    }

    $teamKegRows = [];
    if (!empty($schedKegIds)) {
        $kIdStr = implode(',', array_keys($schedKegIds));
        $resTK = $conn->query("SELECT kegiatan_id, teknisi_id, nama_teknisi, is_ketua FROM team_kegiatan WHERE kegiatan_id IN ($kIdStr) AND deleted_at IS NULL GROUP BY kegiatan_id, teknisi_id");
        if ($resTK) {
            while ($tk = $resTK->fetch_assoc()) {
                $teamKegRows[intval($tk['kegiatan_id'])][] = [
                    'teknisi_id' => intval($tk['teknisi_id']),
                    'nama' => $tk['nama_teknisi'],
                    'is_ketua' => intval($tk['is_ketua']),
                ];
            }
            $resTK->free();
        }
    }

    $existingTeamsMap = [];
    foreach ($schedByKode as $sr) {
        $kid = intval($sr['id']);
        $members = $teamKegRows[$kid] ?? [];
        if (empty($members)) continue;

        $tekIds = array_map(fn($m) => $m['teknisi_id'], $members);
        sort($tekIds);
        $sig = implode('-', $tekIds);

        $ketuaId = $tekIds[0];
        foreach ($members as $m) {
            if ($m['is_ketua'] === 1) {
                $ketuaId = $m['teknisi_id'];
                break;
            }
        }

        $cid = intval($sr['customer_id'] ?? 0);
        $z = dispatchResolveZoneAndCoords(
            $sr['lat'],
            $sr['lon'],
            $sr['alamat_lokasi'] ?? '',
            $sr['cust_alamat'] ?? '',
            '',
            $custCoordsMap[$cid] ?? null
        );

        if (!isset($existingTeamsMap[$sig])) {
            $existingTeamsMap[$sig] = [
                'signature' => $sig,
                'ketua_id' => $ketuaId,
                'teknisi_ids' => $tekIds,
                'existing_stops' => [],
            ];
        }

        $existingTeamsMap[$sig]['existing_stops'][] = [
            'kegiatan_id' => $kid,
            'kode' => $sr['kode'],
            'customer' => $sr['nama_customer'] ?? 'Customer',
            'kegiatan' => $sr['kegiatan'] ?? '',
            'waktu' => date('H:i', strtotime($sr['jadwal'])),
            'duration_min' => dispatchJobDurationMin($sr['kegiatan'] ?? ''),
            'zone' => $z['zone'],
            'zone_label' => $z['label'],
            'zone_bg' => $z['bg'],
            'zone_color' => $z['color'],
            'lat' => $z['lat'],
            'lon' => $z['lon'],
            'is_exact' => $z['is_exact'],
        ];
    }

    // Recent team templates (from last 14 days) so empty dates can auto-fill familiar technician pairs
    $recentTemplates = [];
    $resRecent = @$conn->query("SELECT tk.kegiatan_id, tk.teknisi_id, tk.is_ketua
                                FROM team_kegiatan tk
                                JOIN kegiatan k ON tk.kegiatan_id = k.id
                                WHERE tk.deleted_at IS NULL AND k.deleted_at IS NULL
                                  AND k.jadwal >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                                ORDER BY k.jadwal DESC LIMIT 120");
    if ($resRecent) {
        $byKeg = [];
        while ($rr = $resRecent->fetch_assoc()) {
            $byKeg[intval($rr['kegiatan_id'])][] = [
                'tid' => intval($rr['teknisi_id']),
                'is_ketua' => intval($rr['is_ketua']),
            ];
        }
        $resRecent->free();

        $usedTechInTemplate = [];
        foreach ($byKeg as $mems) {
            $tids = [];
            $ketua = 0;
            $overlap = false;
            foreach ($mems as $m) {
                if (!isset($tekMap[$m['tid']])) continue;
                if (isset($usedTechInTemplate[$m['tid']])) {
                    $overlap = true;
                    break;
                }
                $tids[$m['tid']] = true;
                if ($m['is_ketua'] === 1) $ketua = $m['tid'];
            }
            if ($overlap || empty($tids)) continue;
            $tidList = array_keys($tids);
            sort($tidList);
            if ($ketua === 0 || !in_array($ketua, $tidList, true)) {
                $ketua = $tidList[0];
            }
            foreach ($tidList as $u) {
                $usedTechInTemplate[$u] = true;
            }
            $recentTemplates[] = [
                'ketua_id' => $ketua,
                'teknisi_ids' => $tidList,
            ];
            if (count($recentTemplates) >= 6) break;
        }
    }

    $conn->close();
    echo json_encode([
        'status' => 'success',
        'tanggal' => $selectedDate,
        'waiting_items' => $waitingItems,
        'technicians' => $technicians,
        'existing_teams' => array_values($existingTeamsMap),
        'recent_templates' => $recentTemplates,
    ]);
    exit;
}

if ($action === 'save_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Format data tidak valid.']);
        exit;
    }

    $defaultTanggal = $payload['tanggal'] ?? '';
    $assignments = $payload['assignments'] ?? [];

    if (empty($assignments) || !is_array($assignments)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada kegiatan yang dipilih untuk dijadwalkan.']);
        exit;
    }

    // Load active technician names
    $tekNames = [];
    $resT = $conn->query("SELECT id, nama FROM teknisi WHERE deleted_at IS NULL");
    if ($resT) {
        while ($r = $resT->fetch_assoc()) {
            $tekNames[intval($r['id'])] = $r['nama'];
        }
        $resT->free();
    }

    $now = date('Y-m-d H:i:s');
    $stat = 'dijadwalkan';
    $savedItems = [];

    $conn->begin_transaction();
    try {
        $stmtGetKode = $conn->prepare("SELECT kode FROM kegiatan WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtUpdateKeg = $conn->prepare("UPDATE kegiatan SET status = ?, jadwal = ?, updated_at = ? WHERE id = ?");
        $stmtClearOldTeam = $conn->prepare("UPDATE team_kegiatan SET deleted_at = ? WHERE kegiatan_id = ? AND deleted_at IS NULL");
        $stmtInsertTeam = $conn->prepare("INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, is_ketua, kode, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($assignments as $idx => $item) {
            $kegiatanId = intval($item['kegiatan_id'] ?? 0);
            $tanggal = trim($item['tanggal'] ?? $defaultTanggal);
            $jam = trim($item['jam'] ?? '');
            $tekIdsRaw = $item['teknisi_ids'] ?? [];
            $ketuaId = intval($item['ketua_id'] ?? 0);

            if ($kegiatanId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !preg_match('/^\d{2}:\d{2}/', $jam)) {
                throw new Exception("Data jadwal baris #" . ($idx + 1) . " belum lengkap (tanggal/jam).");
            }

            $jamClean = substr($jam, 0, 5);
            $tekIds = [];
            foreach ((array)$tekIdsRaw as $tidVal) {
                $tidInt = intval($tidVal);
                if ($tidInt > 0 && isset($tekNames[$tidInt])) {
                    $tekIds[$tidInt] = $tidInt;
                }
            }
            $tekIds = array_values($tekIds);
            if (empty($tekIds)) {
                throw new Exception("Kegiatan #" . $kegiatanId . " belum memiliki teknisi.");
            }
            if (count($tekIds) === 1) {
                $ketuaId = $tekIds[0];
            } elseif (!in_array($ketuaId, $tekIds, true)) {
                $ketuaId = $tekIds[0];
            }

            $stmtGetKode->bind_param("i", $kegiatanId);
            $stmtGetKode->execute();
            $resKd = $stmtGetKode->get_result();
            $rowKd = $resKd ? $resKd->fetch_assoc() : null;
            if (!$rowKd) {
                throw new Exception("Kegiatan ID #$kegiatanId tidak ditemukan.");
            }
            $kodeKegiatan = $rowKd['kode'];

            $jadwalFull = $tanggal . ' ' . $jamClean;
            $stmtUpdateKeg->bind_param("sssi", $stat, $jadwalFull, $now, $kegiatanId);
            if (!$stmtUpdateKeg->execute()) {
                throw new Exception("Gagal mengupdate jadwal kegiatan #$kegiatanId.");
            }

            $stmtClearOldTeam->bind_param("si", $now, $kegiatanId);
            $stmtClearOldTeam->execute();

            foreach ($tekIds as $tid) {
                $namaTek = $tekNames[$tid];
                $isKetua = ($tid === $ketuaId) ? 1 : 0;
                $stmtInsertTeam->bind_param("iisisss", $kegiatanId, $tid, $namaTek, $isKetua, $kodeKegiatan, $now, $now);
                if (!$stmtInsertTeam->execute()) {
                    throw new Exception("Gagal menyimpan tim teknisi untuk kegiatan #$kegiatanId.");
                }
            }

            $savedItems[] = [
                'kegiatan_id' => $kegiatanId,
                'tanggal' => $tanggal,
                'jam' => $jamClean,
                'teknisi_ids' => $tekIds,
                'ketua_id' => $ketuaId,
            ];
        }

        $stmtGetKode->close();
        $stmtUpdateKeg->close();
        $stmtClearOldTeam->close();
        $stmtInsertTeam->close();

        $conn->commit();
        $conn->close();

        echo json_encode([
            'status' => 'success',
            'saved_count' => count($savedItems),
            'saved_items' => $savedItems,
        ]);
        exit;
    } catch (Exception $ex) {
        $conn->rollback();
        $conn->close();
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $ex->getMessage(),
        ]);
        exit;
    }
}

$conn->close();
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);

