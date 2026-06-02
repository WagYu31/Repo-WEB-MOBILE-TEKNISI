<?php
include "../conn.php";
include "../session.php";
$pageNow = "Edit Kegiatan";
include "../get-user-data.php";

$kode_transaksi = $_GET['kode_transaksi'] ?? null;
$kegiatanData = null;
$selectedTeknisi = [];

if (!$kode_transaksi) {
    die("Error: Kode transaksi tidak valid.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $kode_update = $_POST['kode_transaksi'];
        $id_kegiatan = $_POST['id_kegiatan'];
        $kegiatan_pilihan = $_POST['kegiatan_pilihan'];
        $jadwal_pilihan = $_POST['jadwal_pilihan'];
        $teknisi_pilihan = $_POST['teknisi'] ?? [];

        $stmt1 = $conn->prepare("UPDATE kegiatan SET kegiatan = ?, jadwal = ? WHERE id = ? AND kode = ?");
        $stmt1->bind_param("ssis", $kegiatan_pilihan, $jadwal_pilihan, $id_kegiatan, $kode_update);
        $stmt1->execute();

        $stmt2 = $conn->prepare("DELETE FROM team_kegiatan WHERE kegiatan_id = ? AND kode = ?");
        $stmt2->bind_param("is", $id_kegiatan, $kode_update);
        $stmt2->execute();

        if (!empty($teknisi_pilihan)) {
            $stmt3 = $conn->prepare("INSERT INTO team_kegiatan (kegiatan_id, teknisi_id, nama_teknisi, kode) VALUES (?, ?, (SELECT nama FROM teknisi WHERE id = ?), ?)");
            foreach ($teknisi_pilihan as $teknisi_id) {
                $stmt3->bind_param("iiss", $id_kegiatan, $teknisi_id, $teknisi_id, $kode_update);
                $stmt3->execute();
            }
        }
        
        $conn->commit();
        header("Location: index-sa.php?success=update");
        exit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        header("Location: apv_kegiatan.php?kode_transaksi=" . urlencode($kode_transaksi) . "&error=update_failed");
        exit();
    }
}

$stmt_kegiatan = $conn->prepare("SELECT k.id, k.kode, k.kegiatan, k.jadwal, k.request, c.nama AS nama_customer, c.telp AS cust_nomor FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id WHERE k.kode = ? ORDER BY k.id DESC LIMIT 1");
$stmt_kegiatan->bind_param("s", $kode_transaksi);
$stmt_kegiatan->execute();
$result_kegiatan = $stmt_kegiatan->get_result();
$kegiatanData = $result_kegiatan->fetch_assoc();

if ($kegiatanData) {
    $id_kegiatan = $kegiatanData['id'];
    $stmt_teknisi = $conn->prepare("SELECT teknisi_id FROM team_kegiatan WHERE kegiatan_id = ?");
    $stmt_teknisi->bind_param("i", $id_kegiatan);
    $stmt_teknisi->execute();
    $result_teknisi = $stmt_teknisi->get_result();
    while($row = $result_teknisi->fetch_assoc()) {
        $selectedTeknisi[] = $row['teknisi_id'];
    }
}

$sqlAllTeknisi = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
$resultAllTeknisi = mysqli_query($conn, $sqlAllTeknisi);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Kegiatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; }
        .technician-list { max-height: 250px; overflow-y: auto; border: 1px solid #dee2e6; padding: 1rem; border-radius: .375rem; }
        .customer-info-box { background-color: #e9ecef; padding: 1rem; border-radius: .375rem; }
    </style>
</head>
<body class="bg-light">
    <?php include "bottom-navbar.php"; ?>
    <main class="main-content">
        <div class="container-fluid py-3">
            <div class="row">
                <div class="col-12">
                    <?php if ($kegiatanData): ?>
                    <h4 class="mb-1"><i class="fa-solid fa-edit me-2"></i>Edit Kegiatan</h4>
                    <p class="text-muted">Kode: <strong><?= htmlspecialchars($kegiatanData['kode']); ?></strong></p>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="apv_kegiatan.php?kode_transaksi=<?= htmlspecialchars($kode_transaksi); ?>">
                                <input type="hidden" name="kode_transaksi" value="<?= htmlspecialchars($kegiatanData['kode']); ?>">
                                <input type="hidden" name="id_kegiatan" value="<?= htmlspecialchars($kegiatanData['id']); ?>">

                                <div class="mb-3">
                                    <label class="form-label">Customer</label>
                                    <div class="customer-info-box">
                                        <h6 class="mb-0"><?= htmlspecialchars($kegiatanData['nama_customer']); ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($kegiatanData['cust_nomor']); ?></small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="kegiatan_pilihan" class="form-label">Jenis Kegiatan</label>
                                    <select class="form-select" id="kegiatan_pilihan" name="kegiatan_pilihan" required>
                                        <option value="service" <?= $kegiatanData['kegiatan'] == 'service' ? 'selected' : ''; ?>>Service</option>
                                        <option value="survey" <?= $kegiatanData['kegiatan'] == 'survey' ? 'selected' : ''; ?>>Survey</option>
                                        <option value="pasang baru" <?= $kegiatanData['kegiatan'] == 'pasang baru' ? 'selected' : ''; ?>>Pasang Baru</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="jadwal_pilihan" class="form-label">Jadwal</label>
                                    <input type="datetime-local" class="form-control" id="jadwal_pilihan" name="jadwal_pilihan" value="<?= date('Y-m-d\TH:i', strtotime($kegiatanData['jadwal'] ?? 'now')); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Teknisi Bertugas</label>
                                    <div class="technician-list">
                                        <?php while ($row = mysqli_fetch_assoc($resultAllTeknisi)) : ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="teknisi[]" value="<?= $row['id'] ?>" id="teknisi<?= $row['id'] ?>" <?= in_array($row['id'], $selectedTeknisi) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="teknisi<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-2"></i>Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger text-center">Data kegiatan tidak ditemukan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>