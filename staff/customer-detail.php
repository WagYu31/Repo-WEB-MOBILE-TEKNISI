<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";

$customer_id = $_GET["id_cust"] ?? 0;
if (!filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
    die("ID Customer tidak valid.");
}

$sql = "SELECT
            k.id AS kegiatan_id, k.kode AS kegiatan_kode, k.kegiatan AS jenis_kegiatan, 
            k.jadwal AS jadwal_kegiatan, k.keterangan AS keterangan_kegiatan, k.lunas,
            c.nama AS customer_name,
            (SELECT no_invoice FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as no_invoice,
            (SELECT nominal_invoice FROM pendapatan_kegiatan WHERE kode = k.kode LIMIT 1) as nominal_invoice,
            p.teknisi_id, p.status, p.waktu_mulai, p.waktu_selesai, p.permasalahan, p.solusi,
            p.image_1, p.image_2, p.image_3, p.image_4, p.image_5,
            t.nama AS teknisi_name
        FROM kegiatan k
        LEFT JOIN customer c ON k.customer_id = c.id
        LEFT JOIN pelaksanaan_kegiatan p ON k.id = p.kegiatan_id
        LEFT JOIN teknisi t ON p.teknisi_id = t.id
        WHERE k.customer_id = ? AND k.deleted_at IS NULL AND p.id IS NOT NULL
        ORDER BY k.jadwal DESC, p.waktu_mulai ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$grouped_activities = [];
$customerName = "Tidak Diketahui";

while ($row = $result->fetch_assoc()) {
    $customerName = $row['customer_name'] ?? $customerName;
    $kode = $row['kegiatan_kode'];

    if (!isset($grouped_activities[$kode])) {
        $grouped_activities[$kode] = [
            'info' => [
                'jenis' => $row['jenis_kegiatan'], 'jadwal' => $row['jadwal_kegiatan'],
                'keterangan' => $row['keterangan_kegiatan'], 'no_invoice' => $row['no_invoice'],
                'nominal_invoice' => $row['nominal_invoice'], 'lunas' => $row['lunas'],
                'id' => $row['kegiatan_id']
            ], 'pelaksanaan' => []
        ];
    }
    if (!empty($row['teknisi_name'])) {
        $grouped_activities[$kode]['pelaksanaan'][] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Riwayat Pelanggan - <?= htmlspecialchars($customerName) ?></title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-xl-12 col-lg-12">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6"><h5 class="mb-1 text-uppercase">Riwayat Kegiatan</h5><p class="text-sm mb-md-0"><strong><?= htmlspecialchars($customerName) ?></strong></p></div>
                                <div class="col-md-6"><div class="input-group"><span class="input-group-text text-body"><i class="fas fa-search me-2" aria-hidden="true"></i></span><input type="text" id="searchInput" class="form-control p-2 border" placeholder="Cari kegiatan, teknisi, atau catatan..."></div></div>
                            </div>
                        </div>
                    </div>
                    <div id="activityList">
                        <?php if (empty($grouped_activities)): ?>
                            <div class="alert alert-info text-white text-center">Tidak ada riwayat kegiatan untuk pelanggan ini.</div>
                        <?php else: ?>
                            <?php foreach ($grouped_activities as $kode => $activity): ?>
                                <div class="card mb-4 activity-card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <div><h6 class="mb-0 text-dark text-capitalize"><i class="fa-solid fa-bolt text-primary me-2"></i><?= htmlspecialchars($activity['info']['jenis']) ?></h6><span class="text-xs text-muted"><?= date("d M Y", strtotime($activity['info']['jadwal'])) ?> (<?= htmlspecialchars($kode) ?>)</span></div>
                                        <a href="view-kegiatan.php?kode_transaksi=<?= htmlspecialchars($kode) ?>" target="_blank" class="btn btn-sm btn-outline-info mb-0">Lihat Detail Kegiatan</a>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6 class="font-weight-bolder">Ringkasan</h6><p class="text-sm text-muted"><?= !empty($activity['info']['keterangan']) ? nl2br(htmlspecialchars($activity['info']['keterangan'])) : 'Tidak ada keterangan umum.' ?></p>
                                                <hr class="horizontal dark my-2">
                                                <h6 class="font-weight-bolder">Informasi Keuangan</h6>
                                                <?php if(!empty($activity['info']['no_invoice'])): ?>
                                                    <p class="text-sm mb-1"><strong>No. Invoice:</strong> <?= htmlspecialchars($activity['info']['no_invoice']) ?></p>
                                                    <p class="text-sm mb-1"><strong>Nominal:</strong> <span class="font-weight-bold text-success">Rp <?= number_format($activity['info']['nominal_invoice'], 0, ',', '.') ?></span></p>
                                                    <p class="text-sm mb-0"><strong>Status:</strong>
                                                        <?php if(!empty($activity['info']['lunas']) && $activity['info']['lunas'] != '0000-00-00'): ?><span class="badge badge-sm bg-gradient-success">LUNAS</span><?php else: ?><span class="badge badge-sm bg-gradient-danger">BELUM LUNAS</span><?php endif; ?>
                                                    </p>
                                                <?php else: ?><p class="text-sm text-muted">Invoice belum dibuat.</p><?php endif; ?>
                                            </div>
                                            <div class="col-md-8 border-start ps-md-4 mt-4 mt-md-0">
                                                <h6 class="font-weight-bolder">Absensi Teknisi</h6>
                                                <?php if (empty($activity['pelaksanaan'])): ?>
                                                    <p class="text-sm text-muted">Belum ada data absensi.</p>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-borderless align-items-center mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder">Teknisi</th>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Mulai</th>
                                                                    <th class="text-uppercase text-dark text-xs font-weight-bolder ps-2">Waktu Selesai</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($activity['pelaksanaan'] as $task): ?>
                                                                    <tr style="border-bottom:1px solid #bfbfbf;">
                                                                        <td>
                                                                            <a class="text-sm font-weight-bold mb-0" href="list-kegiatan-teknisi.php?idTek=<?= htmlspecialchars($task['teknisi_id']); ?>">
                                                                                <?= htmlspecialchars($task['teknisi_name']) ?>
                                                                            </a>
                                                                        </td>
                                                                        <td>
                                                                            <p class="text-sm mb-0 <?= (!empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00') ? 'text-dark' : 'text-danger fw-bold'; ?>">
                                                                                <?= (!empty($task['waktu_mulai']) && substr($task['waktu_mulai'], 0, 10) != '0000-00-00') ? date("d/m/y H:i", strtotime($task['waktu_mulai'])) : 'n/a'; ?>
                                                                            </p>
                                                                        </td>
                                                                        <td>
                                                                            <p class="text-sm mb-0 <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? 'text-dark' : 'text-danger fw-bold'; ?>">
                                                                                <?= (!empty($task['waktu_selesai']) && substr($task['waktu_selesai'], 0, 10) != '0000-00-00') ? date("d/m/y H:i", strtotime($task['waktu_selesai'])) : 'n/a'; ?>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div id="noResultsMessage" class="alert alert-warning text-white text-center" style="display: none;">Pencarian tidak menemukan riwayat kegiatan.</div>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            let visibleCards = 0;
            $('#activityList .activity-card').each(function() {
                let cardText = $(this).text().toLowerCase();
                if (cardText.includes(searchTerm)) {
                    $(this).slideDown('fast');
                    visibleCards++;
                } else {
                    $(this).slideUp('fast');
                }
            });
            if (visibleCards > 0) { $('#noResultsMessage').hide(); } else { $('#noResultsMessage').show(); }
        });
    });
    </script>
</body>
</html>