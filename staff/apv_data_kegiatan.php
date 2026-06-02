<?php
include 'conn.php';
include 'session.php'; // Jika Anda memerlukan data sesi

// --- 1. PENGAMBILAN & VALIDASI DATA INPUT ---

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah. Metode request harus POST.");
}

// Ambil data dari form
$kodeTransaksi = $_POST['kode_transaksi'] ?? null;
$tanggalPilihan = $_POST['tanggal_pilihan'] ?? null;
$waktuPilihan = $_POST['waktu_pilihan'] ?? null;
$kegiatanDipilih = $_POST['kegiatan_pilihan'] ?? null;
$teknisiDipilihNames = $_POST['teknisi'] ?? []; // Ini adalah array NAMA teknisi

// Validasi input dasar
if (empty($kodeTransaksi) || empty($tanggalPilihan) || empty($waktuPilihan) || empty($kegiatanDipilih)) {
    $_SESSION['error_message'] = "Semua field (tanggal, waktu, kegiatan) wajib diisi.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Gabungkan tanggal dan waktu menjadi format datetime
$jadwalBaru = $tanggalPilihan . ' ' . $waktuPilihan . ':00';
$now = date("Y-m-d H:i:s");


// --- 2. PROSES DATABASE DALAM TRANSAKSI ---

$conn->begin_transaction();

try {
    // --- Langkah A: Dapatkan ID Kegiatan Terakhir berdasarkan Kode ---
    $sqlGetKegiatan = "SELECT id FROM kegiatan WHERE kode = ? ORDER BY id DESC LIMIT 1";
    $stmtGetKegiatan = $conn->prepare($sqlGetKegiatan);
    $stmtGetKegiatan->bind_param("s", $kodeTransaksi);
    $stmtGetKegiatan->execute();
    $resultGetKegiatan = $stmtGetKegiatan->get_result();
    
    if ($resultGetKegiatan->num_rows === 0) {
        throw new Exception("Kegiatan dengan kode '$kodeTransaksi' tidak ditemukan.");
    }
    $kegiatan = $resultGetKegiatan->fetch_assoc();
    $kegiatanId = $kegiatan['id'];
    $stmtGetKegiatan->close();

    // --- Langkah B: Update Kegiatan Utama (Approval, Jadwal, Jenis Kegiatan) ---
    $sqlUpdateKegiatan = "UPDATE kegiatan SET approval = 'yes', jadwal = ?, kegiatan = ?, updated_at = ? WHERE id = ?";
    $stmtUpdateKegiatan = $conn->prepare($sqlUpdateKegiatan);
    $stmtUpdateKegiatan->bind_param("sssi", $jadwalBaru, $kegiatanDipilih, $now, $kegiatanId);
    if (!$stmtUpdateKegiatan->execute()) {
        throw new Exception("Gagal mengupdate data kegiatan utama.");
    }
    $stmtUpdateKegiatan->close();


    // =========================================================================
    // [PERBAIKAN] Langkah C: Logika Baru untuk Sinkronisasi Tim Teknisi
    // =========================================================================

    // 1. Dapatkan peta lookup Nama Teknisi -> ID dari database
    $allTeknisiResult = $conn->query("SELECT id, nama FROM teknisi WHERE deleted_at IS NULL");
    $namaToIdMap = [];
    while ($teknisi = $allTeknisiResult->fetch_assoc()) {
        $namaToIdMap[$teknisi['nama']] = $teknisi['id'];
    }

    // 2. Ubah array nama teknisi yang dipilih dari form menjadi array ID
    $submittedTeknisiIds = [];
    foreach ($teknisiDipilihNames as $nama) {
        if (isset($namaToIdMap[$nama])) {
            $submittedTeknisiIds[] = $namaToIdMap[$nama];
        }
    }
    
    // 3. Dapatkan SEMUA teknisi (aktif dan tidak aktif) yang pernah terkait dengan kegiatan ini
    $sqlGetExisting = "SELECT teknisi_id, deleted_at FROM team_kegiatan WHERE kegiatan_id = ?";
    $stmtGetExisting = $conn->prepare($sqlGetExisting);
    $stmtGetExisting->bind_param("i", $kegiatanId);
    $stmtGetExisting->execute();
    $resultExisting = $stmtGetExisting->get_result();
    $existingTeamMap = []; // Peta [teknisi_id] => status deleted_at
    while ($row = $resultExisting->fetch_assoc()) {
        $existingTeamMap[$row['teknisi_id']] = $row['deleted_at'];
    }
    $stmtGetExisting->close();

    // 4. Tentukan teknisi mana yang perlu di-nonaktifkan (soft delete)
    $teknisiToRemove = [];
    foreach ($existingTeamMap as $teknisiId => $deleted_at) {
        // Jika teknisi saat ini aktif (deleted_at is NULL) TAPI tidak ada di daftar yang disubmit
        if ($deleted_at === NULL && !in_array($teknisiId, $submittedTeknisiIds)) {
            $teknisiToRemove[] = $teknisiId;
        }
    }

    // 5. Tentukan teknisi mana yang perlu ditambahkan atau diaktifkan kembali
    $teknisiToUpsert = []; // "Upsert" = Update atau Insert
    foreach ($submittedTeknisiIds as $teknisiId) {
        // Jika teknisi tidak ada sama sekali di tim, atau ada tapi statusnya terhapus (deleted_at is NOT NULL)
        if (!isset($existingTeamMap[$teknisiId]) || $existingTeamMap[$teknisiId] !== NULL) {
            $teknisiToUpsert[] = $teknisiId;
        }
    }

    // 6. Eksekusi query untuk MENONAKTIFKAN teknisi
    if (!empty($teknisiToRemove)) {
        $placeholders = implode(',', array_fill(0, count($teknisiToRemove), '?'));
        $sqlDelete = "UPDATE team_kegiatan SET deleted_at = ? WHERE kegiatan_id = ? AND teknisi_id IN ($placeholders)";
        $stmtDelete = $conn->prepare($sqlDelete);
        $types = "si" . str_repeat('i', count($teknisiToRemove));
        $params = array_merge([$now, $kegiatanId], $teknisiToRemove);
        $stmtDelete->bind_param($types, ...$params);
        if (!$stmtDelete->execute()) { throw new Exception("Gagal menonaktifkan teknisi lama."); }
        $stmtDelete->close();
    }

    // 7. Eksekusi query untuk MENAMBAHKAN atau MENGAKTIFKAN KEMBALI teknisi
    if (!empty($teknisiToUpsert)) {
        // Query ini akan meng-INSERT jika kombinasi (kegiatan_id, teknisi_id) belum ada,
        // atau meng-UPDATE (mengaktifkan kembali) jika sudah ada.
        // **CATATAN PENTING**: Pastikan tabel `team_kegiatan` memiliki UNIQUE KEY pada (`kegiatan_id`, `teknisi_id`)
        $sqlUpsert = "INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode, created_at, updated_at, deleted_at) 
                      VALUES (?, ?, (SELECT nama FROM teknisi WHERE id = ?), ?, ?, ?, NULL)
                      ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at), deleted_at = NULL";
        $stmtUpsert = $conn->prepare($sqlUpsert);
        foreach ($teknisiToUpsert as $teknisiId) {
            $stmtUpsert->bind_param("iiisss", $kegiatanId, $teknisiId, $teknisiId, $kodeTransaksi, $now, $now);
            if (!$stmtUpsert->execute()) { throw new Exception("Gagal menambahkan/mengaktifkan teknisi."); }
        }
        $stmtUpsert->close();
    }

    // --- Langkah D: Selesaikan Transaksi ---
    $conn->commit();
    $_SESSION['success_message'] = "Kegiatan berhasil disetujui dan tim teknisi telah disinkronkan.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

// Tutup koneksi dan alihkan kembali
$conn->close();
header("Location: index-sa.php"); // Ganti dengan halaman asal yang sesuai
exit();
?>