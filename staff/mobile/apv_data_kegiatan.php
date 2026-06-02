<?php
include '../conn.php';
include '../session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak sah. Metode request harus POST.");
}

$kodeTransaksi = $_POST['kode_transaksi'] ?? null;
$tanggalPilihan = $_POST['tanggal_pilihan'] ?? null;
$waktuPilihan = $_POST['waktu_pilihan'] ?? null;
$kegiatanDipilih = $_POST['kegiatan_pilihan'] ?? null;
$teknisiDipilihNames = $_POST['teknisi'] ?? [];

if (empty($kodeTransaksi) || empty($tanggalPilihan) || empty($waktuPilihan) || empty($kegiatanDipilih)) {
    $_SESSION['error_message'] = "Semua field (tanggal, waktu, kegiatan) wajib diisi.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

$jadwalBaru = $tanggalPilihan . ' ' . $waktuPilihan . ':00';
$now = date("Y-m-d H:i:s");

$conn->begin_transaction();

try {
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

    $sqlUpdateKegiatan = "UPDATE kegiatan SET approval = 'yes', jadwal = ?, kegiatan = ?, updated_at = ? WHERE id = ?";
    $stmtUpdateKegiatan = $conn->prepare($sqlUpdateKegiatan);
    $stmtUpdateKegiatan->bind_param("sssi", $jadwalBaru, $kegiatanDipilih, $now, $kegiatanId);
    if (!$stmtUpdateKegiatan->execute()) {
        throw new Exception("Gagal mengupdate data kegiatan utama.");
    }
    $stmtUpdateKegiatan->close();

    $sqlUpdateTeam = "UPDATE team_kegiatan SET deleted_at = ? WHERE kegiatan_id = ?";
    $stmtUpdateTeam = $conn->prepare($sqlUpdateTeam);
    $stmtUpdateTeam->bind_param("si", $now, $kegiatanId);
    if (!$stmtUpdateTeam->execute()) {
        throw new Exception("Gagal memperbarui tim teknisi lama.");
    }
    $stmtUpdateTeam->close();

    if (!empty($teknisiDipilihNames)) {
        $allTeknisiResult = $conn->query("SELECT id, nama FROM teknisi WHERE deleted_at IS NULL");
        $namaToIdMap = [];
        while ($teknisi = $allTeknisiResult->fetch_assoc()) {
            $namaToIdMap[$teknisi['nama']] = $teknisi['id'];
        }

        $sqlInsertTeam = "INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsertTeam = $conn->prepare($sqlInsertTeam);

        foreach ($teknisiDipilihNames as $namaTeknisi) {
            if (isset($namaToIdMap[$namaTeknisi])) {
                $teknisiId = $namaToIdMap[$namaTeknisi];
                $stmtInsertTeam->bind_param("iissss", $kegiatanId, $teknisiId, $namaTeknisi, $kodeTransaksi, $now, $now);
                if (!$stmtInsertTeam->execute()) {
                    throw new Exception("Gagal menambahkan teknisi baru.");
                }
            }
        }
        $stmtInsertTeam->close();
    }

    $conn->commit();
    $_SESSION['success_message'] = "Kegiatan berhasil disetujui dan tim teknisi telah diperbarui.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

$conn->close();
header("Location: index.php");
exit();
?>