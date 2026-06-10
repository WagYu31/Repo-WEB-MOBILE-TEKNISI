<?php
include "conn.php";
include "session.php";

if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    header("Location: lap-kegiatan.php?error=3");
    exit();
}

$kode_kegiatan = $_GET['kode'];
$nilai_invoice = null;

$query_get_nilai = "SELECT nilai FROM noinv WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1";
$stmt_get = $conn->prepare($query_get_nilai);

if ($stmt_get) {
    $stmt_get->execute();
    $stmt_get->bind_result($fetched_nilai);
    if ($stmt_get->fetch()) {
        $nilai_invoice = $fetched_nilai;
    }
    $stmt_get->close();
} else {
    header("Location: lap-kegiatan.php?error=1");
    exit();
}

if ($nilai_invoice === null) {
    header("Location: lap-kegiatan.php?error=no_nilai"); 
    exit();
}

$jumlah_teknisi = 0;
$query_count_teknisi = "SELECT COUNT(DISTINCT teknisi_id) FROM team_kegiatan WHERE kode = ? AND deleted_at IS NULL";
$stmt_count = $conn->prepare($query_count_teknisi);

if ($stmt_count) {
    $stmt_count->bind_param("s", $kode_kegiatan);
    $stmt_count->execute();
    $stmt_count->bind_result($tech_count);
    if ($stmt_count->fetch()) {
        $jumlah_teknisi = $tech_count;
    }
    $stmt_count->close();
} else {
    header("Location: lap-kegiatan.php?error=1");
    exit();
}

// Fallback: cek dari pelaksanaan_kegiatan jika team_kegiatan kosong/dihapus
if ($jumlah_teknisi == 0) {
    $query_fallback = "SELECT COUNT(DISTINCT teknisi_id) FROM pelaksanaan_kegiatan WHERE kode = ? AND deleted_at IS NULL";
    $stmt_fb = $conn->prepare($query_fallback);
    if ($stmt_fb) {
        $stmt_fb->bind_param("s", $kode_kegiatan);
        $stmt_fb->execute();
        $stmt_fb->bind_result($fb_count);
        if ($stmt_fb->fetch()) {
            $jumlah_teknisi = $fb_count;
        }
        $stmt_fb->close();
    }
}

if ($jumlah_teknisi == 0) {
    header("Location: lap-kegiatan.php?error=no_technicians");
    exit();
}

$nilai_dibagi = $nilai_invoice / $jumlah_teknisi;

$query_update_kegiatan = "UPDATE kegiatan SET invoice = 'n/a', paid = ? WHERE kode = ?";
$stmt_update = $conn->prepare($query_update_kegiatan);

if ($stmt_update) {
    $stmt_update->bind_param("ds", $nilai_dibagi, $kode_kegiatan);
    
    if ($stmt_update->execute()) {
        header("Location: lap-kegiatan.php?success=na_updated");
        exit();
    } else {
        header("Location: lap-kegiatan.php?error=1");
        exit();
    }
    $stmt_update->close();
} else {
    header("Location: lap-kegiatan.php?error=1");
    exit();
}

$conn->close();
?>