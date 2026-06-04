<?php
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Use prepared statements to prevent SQL injection
if (!empty($keyword)) {
    $sql = "SELECT c.*, COUNT(p.id) AS total_kegiatan
    FROM customer c
    LEFT JOIN kegiatan k ON c.id = k.customer_id
    LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
    WHERE (c.nama LIKE ? 
        OR c.telp LIKE ? 
        OR c.alamat LIKE ? 
        OR c.kota LIKE ? 
        OR c.kodepos LIKE ? 
        OR c.provinsi LIKE ?)
        AND c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY total_kegiatan DESC";
    $stmt = $conn->prepare($sql);
    $kw = "%$keyword%";
    $stmt->bind_param("ssssss", $kw, $kw, $kw, $kw, $kw, $kw);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT c.*, COUNT(p.id) AS total_kegiatan
    FROM customer c
    LEFT JOIN kegiatan k ON c.id = k.customer_id
    LEFT JOIN pelaksanaan_kegiatan p ON p.kegiatan_id = k.id
    WHERE c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY total_kegiatan DESC";
    $result = mysqli_query($conn, $sql);
}

$allRows = [];
while ($row = mysqli_fetch_assoc($result)) { $allRows[] = $row; }
$totalCustomers = count($allRows);

function makeLinksClickable($text) {
    $pattern = '/(http|https|ftp):\/\/[^\s]+/i';
    return preg_replace($pattern, '<a href="$0" target="_blank" style="color:#2563eb; word-break:break-all;">$0</a>', $text);
}
?>
<style>
    /* ═══ PREMIUM CUSTOMER ═══ */
    .cust-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
        overflow: hidden; display: flex; flex-direction: column;
        max-height: calc(100vh - 140px);
    }
    .cust-header { padding: 24px 24px 0; flex-shrink: 0; }
    .cust-title-row {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 16px; margin-bottom: 16px;
    }
    .cust-title-left { display: flex; align-items: center; gap: 14px; }
    .cust-icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    }
    .cust-icon i { color: #fff; font-size: 16px; }
    .cust-title-left h5 { margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; }
    .cust-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }
    .cust-actions { display: flex; gap: 8px; }
    .cust-btn {
        padding: 9px 18px; border: none; border-radius: 10px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        transition: all 0.2s; text-decoration: none;
    }
    .cust-btn-add {
        background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff;
        box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    }
    .cust-btn-add:hover { transform: translateY(-1px); color: #fff; }
    .cust-btn-refresh { background: #f8fafc; color: #475569; border: 1.5px solid #e5e7eb; }
    .cust-btn-refresh:hover { background: #f1f5f9; color: #1e293b; }

    /* Stats */
    .cust-stats {
        display: flex; gap: 12px; padding: 0 24px 16px; flex-shrink: 0;
    }
    .cust-stat {
        padding: 10px 18px; border-radius: 10px; background: #f8fafc;
        border: 1px solid #e5e7eb;
    }
    .cust-stat-num { font-size: 18px; font-weight: 800; color: #2563eb; }
    .cust-stat-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }

    /* Filter */
    .cust-filter {
        display: flex; justify-content: flex-end; padding: 0 24px 12px; flex-shrink: 0;
    }
    .cust-search-form { display: flex; gap: 8px; align-items: center; }
    .cust-search {
        border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px 9px 36px;
        font-size: 13px; color: #1e293b; background: #f8fafc; font-weight: 500;
        transition: all 0.2s; width: 320px;
    }
    .cust-search:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); outline: none; background: #fff; }
    .cust-search-wrap { position: relative; }
    .cust-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; }
    .cust-btn-cari {
        padding: 9px 20px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff;
        font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
        box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    }

    /* Table */
    .cust-scroll { flex: 1; overflow-y: auto; overflow-x: auto; }
    .cust-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 780px; }
    .cust-table thead th {
        background: #f8fafc; border-bottom: 2px solid #e5e7eb;
        padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
        position: sticky; top: 0; z-index: 2;
    }
    .cust-table tbody tr { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
    .cust-table tbody tr:hover { background: #fafbfc; }
    .cust-table tbody td { padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: top; }

    .cust-num { font-size: 12px; font-weight: 700; color: #94a3b8; }
    .cust-name {
        font-size: 14px; font-weight: 700; color: #2563eb; text-decoration: none;
        display: block; line-height: 1.3;
    }
    .cust-name:hover { text-decoration: underline; color: #1d4ed8; }
    .cust-phone {
        font-size: 13px; font-weight: 600; color: #1e293b; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .cust-phone:hover { color: #22c55e; }
    .cust-phone .wa-icon {
        width: 20px; height: 20px; border-radius: 6px; background: #22c55e;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 10px;
    }
    .cust-addr { font-size: 12px; color: #64748b; line-height: 1.5; word-break: break-word; }

    .cust-act-btn {
        width: 30px; height: 30px; border-radius: 8px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.15s; font-size: 12px; text-decoration: none;
    }
    .cust-act-edit { background: #fef3c7; color: #d97706; }
    .cust-act-edit:hover { background: #f59e0b; color: #fff; }
    .cust-act-del { background: #fef2f2; color: #ef4444; }
    .cust-act-del:hover { background: #ef4444; color: #fff; }

    @media (max-width: 768px) {
        .cust-card { max-height: none; }
        .cust-search { width: 100%; }
        .cust-title-row { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="cust-card">
    <!-- Header -->
    <div class="cust-header">
        <div class="cust-title-row">
            <div class="cust-title-left">
                <div class="cust-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <h5>Data Customer</h5>
                    <p>Kelola semua data pelanggan</p>
                </div>
            </div>
            <div class="cust-actions">
                <a href="tambah-customer.php" class="cust-btn cust-btn-add">
                    <i class="fa-solid fa-plus"></i> Tambah Customer
                </a>
                <a href="customer.php" class="cust-btn cust-btn-refresh">
                    <i class="fa-solid fa-arrows-rotate"></i> Refresh
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="cust-stats">
        <div class="cust-stat">
            <div class="cust-stat-num"><?= $totalCustomers ?></div>
            <div class="cust-stat-label">Total Customer</div>
        </div>
    </div>

    <!-- Search -->
    <div class="cust-filter">
        <form method="GET" action="" class="cust-search-form">
            <div class="cust-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="keyword" class="cust-search" placeholder="Cari nama, telepon, atau alamat..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <button type="submit" class="cust-btn-cari">
                <i class="fa-solid fa-magnifying-glass"></i> Cari
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="cust-scroll">
        <table class="cust-table">
            <thead>
                <tr>
                    <th style="width:45px; text-align:center; padding-left:20px;">#</th>
                    <th style="width:22%;">Nama</th>
                    <th style="width:14%;">No Handphone</th>
                    <th>Alamat</th>
                    <th style="width:80px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalCustomers > 0): ?>
                    <?php $no = 1; foreach ($allRows as $row):
                        $nomor_tlp = $row['telp'];
                        $nomor_clean = preg_replace('/[^0-9]/', '', $nomor_tlp);
                        $nomor_wa = '62' . substr($nomor_clean, 1);
                        $url_wa = 'https://api.whatsapp.com/send?phone=' . $nomor_wa;
                        $alamat = makeLinksClickable($row['alamat'] ?? '');
                    ?>
                    <tr>
                        <td style="text-align:center; padding-left:20px;">
                            <span class="cust-num"><?= $no ?></span>
                        </td>
                        <td>
                            <a href="customer-detail.php?id_cust=<?= $row['id'] ?>" class="cust-name">
                                <?= htmlspecialchars($row['nama']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= $url_wa ?>" target="_blank" class="cust-phone">
                                <span class="wa-icon"><i class="fa-brands fa-whatsapp"></i></span>
                                <?= htmlspecialchars($nomor_tlp) ?>
                            </a>
                        </td>
                        <td>
                            <div class="cust-addr"><?= $alamat ?></div>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:4px; justify-content:center;">
                                <a href="edit-customer.php?id=<?= $row['id'] ?>" class="cust-act-btn cust-act-edit" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="delete.php?id=<?= $row['id'] ?>" class="cust-act-btn cust-act-del" title="Hapus" onclick="return confirm('Yakin hapus customer ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php $no++; endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:60px 20px; color:#94a3b8;">
                            <i class="fa-solid fa-user-slash" style="font-size:36px; display:block; margin-bottom:12px;"></i>
                            Tidak ada data customer.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>