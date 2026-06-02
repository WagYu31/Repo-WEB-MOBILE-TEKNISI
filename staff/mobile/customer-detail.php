<?php
include "../conn.php";
include "../session.php";
include "../get-user-data.php";
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
            p.status, p.waktu_mulai, p.waktu_selesai, p.permasalahan, p.solusi,
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .sticky-top { top: -1px; background: #f0f2f5; padding-top: .5rem; padding-bottom: .5rem; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .table-responsive { font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">
    <?php include "bottom-navbar.php"; ?>
    <main class="main-content mb-5">
        <div class="container-fluid pt-3 mb-5">
            <div class="row">
                <div class="col-12"><h4 class="mb-1"><i class="fa-solid fa-user-clock me-2"></i>Riwayat Kegiatan</h4><p class="text-muted">Pelanggan: <strong><?= htmlspecialchars($customerName) ?></strong></p></div>
            </div>
            <div class="sticky-top"><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="text" id="searchInput" class="form-control" placeholder="Cari riwayat..."></div></div>
            <div id="activityList" class="mt-3">
                <?php if (empty($grouped_activities)): ?>
                    <div class="alert alert-info text-center">Tidak ada riwayat kegiatan untuk pelanggan ini.</div>
                <?php else: ?>
                    <?php foreach ($grouped_activities as $kode => $activity): ?>
                        <div class="card mb-3 activity-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div><h6 class="mb-0 text-primary text-capitalize"><?= htmlspecialchars($activity['info']['jenis']) ?></h6><small class="text-muted"><?= date("d F Y", strtotime($activity['info']['jadwal'])) ?></small></div>
                                <a href="view-kegiatan.php?kode_transaksi=<?= htmlspecialchars($kode) ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-0"><i class="fa-solid fa-eye"></i></a>
                            </div>
                            <div class="card-body">
                                <div class="p-2 rounded mb-3" style="background-color: #f8f9fa;">
                                    <p class="text-sm text-muted mb-2"><?= !empty($activity['info']['keterangan']) ? nl2br(htmlspecialchars($activity['info']['keterangan'])) : 'Tidak ada keterangan umum.' ?></p>
                                    <?php if(!empty($activity['info']['no_invoice'])): ?>
                                        <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                            <span class="text-sm"><strong>Inv:</strong> <?= htmlspecialchars($activity['info']['no_invoice']) ?></span>
                                            <?php if(!empty($activity['info']['lunas']) && $activity['info']['lunas'] != '0000-00-00'): ?><span class="badge text-bg-success">LUNAS</span><?php else: ?><span class="badge text-bg-danger">BELUM LUNAS</span><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-2">Absensi Teknisi</h6>
                                <?php if (empty($activity['pelaksanaan'])): ?>
                                    <p class="text-center text-muted small">Belum ada data absensi.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <?php foreach ($activity['pelaksanaan'] as $task): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($task['teknisi_name']) ?></td>
                                                        <td class="text-nowrap"><?= date("d/m H:i", strtotime($task['waktu_mulai'])) ?></td>
                                                        <td class="text-nowrap"><?= date("d/m H:i", strtotime($task['waktu_selesai'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div id="noResultsMessage" class="alert alert-warning text-center" style="display: none;">Pencarian tidak menemukan riwayat.</div>
            </div>
        </div>
    </main>
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