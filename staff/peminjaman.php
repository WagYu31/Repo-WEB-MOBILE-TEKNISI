<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Peminjaman";
$role = $jabatan;

// Fetch data
$sql = "SELECT 
            pb.id AS id_peminjaman, pb.barang_id, pb.teknisi_id, pb.status, t.nama AS nama_teknisi,
            b.nama_barang, pb.qty, pb.qty_akhir, pb.tgl_pinjam, pb.tgl_kembali, pb.keterangan
        FROM peminjaman_barang pb
        JOIN barang b ON pb.barang_id = b.id
        JOIN teknisi t ON pb.teknisi_id = t.id
        WHERE pb.deleted_at IS NULL
        ORDER BY 
            CASE pb.status 
                WHEN 'pengembalian' THEN 1 
                WHEN 'dipinjam' THEN 2 
                WHEN 'persetujuan' THEN 3 
                WHEN 'selesai' THEN 4 
                ELSE 5 
            END, t.nama ASC";
$result = mysqli_query($conn, $sql);

$allRows = [];
$counts = ['semua' => 0, 'dipinjam' => 0, 'pengembalian' => 0, 'selesai' => 0];
while ($row = mysqli_fetch_assoc($result)) {
    $allRows[] = $row;
    $counts['semua']++;
    if (isset($counts[$row['status']])) $counts[$row['status']]++;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        /* ═══ PREMIUM PEMINJAMAN ═══ */
        .pjm-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden; display: flex; flex-direction: column;
            max-height: calc(100vh - 140px);
        }
        .pjm-header { padding: 24px 24px 0; flex-shrink: 0; }
        .pjm-title-row {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
        }
        .pjm-title-left { display: flex; align-items: center; gap: 14px; }
        .pjm-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }
        .pjm-icon i { color: #fff; font-size: 16px; }
        .pjm-title-left h5 { margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; }
        .pjm-title-left p { margin: 2px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500; }
        .pjm-actions { display: flex; gap: 8px; }
        .pjm-btn {
            padding: 9px 18px; border: none; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .pjm-btn-add {
            background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.25);
        }
        .pjm-btn-add:hover { transform: translateY(-1px); color: #fff; }
        .pjm-btn-print {
            background: #f8fafc; color: #475569; border: 1.5px solid #e5e7eb;
        }
        .pjm-btn-print:hover { background: #f1f5f9; }

        /* Stats */
        .pjm-stats {
            display: flex; gap: 12px; padding: 16px 24px; flex-shrink: 0; flex-wrap: wrap;
        }
        .pjm-stat-card {
            flex: 1; min-width: 110px; padding: 12px 16px; border-radius: 12px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            border: 1.5px solid transparent;
        }
        .pjm-stat-card:hover { transform: translateY(-2px); }
        .pjm-stat-card.active { border-color: currentColor; }
        .pjm-stat-card.s-all { background: #f8fafc; color: #475569; }
        .pjm-stat-card.s-all.active { border-color: #475569; }
        .pjm-stat-card.s-dipinjam { background: #fef3c7; color: #92400e; }
        .pjm-stat-card.s-dipinjam.active { border-color: #d97706; }
        .pjm-stat-card.s-pengembalian { background: #eef2ff; color: #3730a3; }
        .pjm-stat-card.s-pengembalian.active { border-color: #6366f1; }
        .pjm-stat-card.s-selesai { background: #f0fdf4; color: #166534; }
        .pjm-stat-card.s-selesai.active { border-color: #22c55e; }
        .pjm-stat-num { font-size: 22px; font-weight: 800; line-height: 1; }
        .pjm-stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

        /* Filter bar */
        .pjm-filter-bar {
            display: flex; justify-content: flex-end; align-items: center; gap: 12px;
            padding: 0 24px 12px; flex-shrink: 0;
        }
        .pjm-search {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 9px 14px 9px 36px;
            font-size: 13px; color: #1e293b; background: #f8fafc; font-weight: 500;
            transition: all 0.2s; width: 260px;
        }
        .pjm-search:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); outline: none; background: #fff; }
        .pjm-search-wrap { position: relative; }
        .pjm-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; }

        /* Table */
        .pjm-scroll { flex: 1; overflow-y: auto; overflow-x: auto; }
        .pjm-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
        .pjm-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .pjm-table tbody tr { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
        .pjm-table tbody tr:hover { background: #fafbfc; }
        .pjm-table tbody td { padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: middle; }

        .pjm-avatar {
            width: 34px; height: 34px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: 13px; flex-shrink: 0;
        }
        .pjm-tek-cell { display: flex; align-items: center; gap: 10px; }
        .pjm-tek-name { font-size: 13px; font-weight: 700; color: #1e293b; }

        .pjm-barang-name { font-size: 13px; font-weight: 600; color: #1e293b; text-transform: capitalize; }
        .pjm-barang-qty { font-size: 11px; color: #94a3b8; margin-top: 1px; }

        .pjm-badge {
            display: inline-block; font-size: 10px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px; text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .pjm-badge.b-dipinjam { background: #fef3c7; color: #92400e; }
        .pjm-badge.b-pengembalian { background: #eef2ff; color: #4338ca; }
        .pjm-badge.b-selesai { background: #dcfce7; color: #166534; }
        .pjm-badge.b-dialihkan { background: #f1f5f9; color: #64748b; }
        .pjm-badge.b-persetujuan { background: #fef2f2; color: #991b1b; }

        .pjm-date { font-size: 12px; color: #64748b; font-weight: 500; white-space: nowrap; }

        .pjm-act-btn {
            padding: 6px 16px; font-size: 11px; font-weight: 700; border-radius: 8px;
            border: none; cursor: pointer; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 4px; text-decoration: none;
        }
        .pjm-act-kembalikan { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; box-shadow: 0 2px 8px rgba(245,158,11,0.2); }
        .pjm-act-kembalikan:hover { transform: translateY(-1px); color: #fff; }
        .pjm-act-terima { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; box-shadow: 0 2px 8px rgba(34,197,94,0.2); }
        .pjm-act-terima:hover { transform: translateY(-1px); color: #fff; }
        .pjm-act-hapus {
            background: #fff; color: #ef4444; border: 1.5px solid #fecaca;
        }
        .pjm-act-hapus:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

        @media (max-width: 768px) {
            .pjm-card { max-height: none; }
            .pjm-stats { overflow-x: auto; flex-wrap: nowrap; }
            .pjm-stat-card { min-width: 100px; }
            .pjm-search { width: 100%; }
        }
        @media print {
            body * { visibility: hidden; }
            #printable-content, #printable-content * { visibility: visible; }
            #printable-content { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            .pjm-card { max-height: none; box-shadow: none; border: none; }
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; setlocale(LC_TIME, 'id_ID.utf8'); ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12" id="printable-content">
                    <div class="pjm-card">
                        <!-- Header -->
                        <div class="pjm-header">
                            <div class="pjm-title-row">
                                <div class="pjm-title-left">
                                    <div class="pjm-icon"><i class="fa-solid fa-hand-holding-hand"></i></div>
                                    <div>
                                        <h5>Peminjaman Barang</h5>
                                        <p>Kelola dan pantau semua item yang dipinjam</p>
                                    </div>
                                </div>
                                <div class="pjm-actions no-print">
                                    <a href="pinjam.php" class="pjm-btn pjm-btn-add">
                                        <i class="fa-solid fa-plus"></i> Peminjaman Baru
                                    </a>
                                    <button class="pjm-btn pjm-btn-print" onclick="window.print()">
                                        <i class="fa-solid fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="pjm-stats no-print">
                            <div class="pjm-stat-card s-all active" data-status="semua">
                                <div class="pjm-stat-num"><?= $counts['semua'] ?></div>
                                <div class="pjm-stat-label">Semua</div>
                            </div>
                            <div class="pjm-stat-card s-dipinjam" data-status="dipinjam">
                                <div class="pjm-stat-num"><?= $counts['dipinjam'] ?></div>
                                <div class="pjm-stat-label">Masih Dipinjam</div>
                            </div>
                            <div class="pjm-stat-card s-pengembalian" data-status="pengembalian">
                                <div class="pjm-stat-num"><?= $counts['pengembalian'] ?></div>
                                <div class="pjm-stat-label">Pengembalian</div>
                            </div>
                            <div class="pjm-stat-card s-selesai" data-status="selesai">
                                <div class="pjm-stat-num"><?= $counts['selesai'] ?></div>
                                <div class="pjm-stat-label">Selesai</div>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="pjm-filter-bar no-print">
                            <div class="pjm-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="searchInput" class="pjm-search" placeholder="Cari nama barang/teknisi...">
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="pjm-scroll">
                            <table class="pjm-table" id="peminjamanTable">
                                <thead>
                                    <tr>
                                        <th style="padding-left:24px;">Teknisi</th>
                                        <th>Barang</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="text-align:center;">Tgl Pinjam</th>
                                        <th style="text-align:center;">Tgl Kembali</th>
                                        <th style="text-align:center;" class="no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($allRows) > 0) {
                                        $colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#22c55e','#06b6d4','#ef4444','#14b8a6'];
                                        $status_map = [
                                            'dipinjam'     => ['text' => 'Dipinjam',     'class' => 'b-dipinjam'],
                                            'pengembalian' => ['text' => 'Pengembalian', 'class' => 'b-pengembalian'],
                                            'selesai'      => ['text' => 'Selesai',      'class' => 'b-selesai'],
                                            'dialihkan'    => ['text' => 'Pengalihan',   'class' => 'b-dialihkan'],
                                            'persetujuan'  => ['text' => 'Menunggu',     'class' => 'b-persetujuan']
                                        ];

                                        foreach ($allRows as $row) {
                                            $status = $row['status'];
                                            $info = $status_map[$status] ?? $status_map['dipinjam'];
                                            $initials = strtoupper(substr($row['nama_teknisi'], 0, 1));
                                            $bgColor = $colors[crc32($row['nama_teknisi']) % count($colors)];
                                    ?>
                                    <tr data-status="<?= $status ?>" data-searchable="<?= strtolower(htmlspecialchars($row['nama_teknisi'] . ' ' . $row['nama_barang'])) ?>">
                                        <td style="padding-left:24px;">
                                            <div class="pjm-tek-cell">
                                                <div class="pjm-avatar" style="background:<?= $bgColor ?>;"><?= $initials ?></div>
                                                <span class="pjm-tek-name"><?= htmlspecialchars($row['nama_teknisi']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="pjm-barang-name"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                            <div class="pjm-barang-qty">Qty: <?= htmlspecialchars($row['qty']) ?></div>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="pjm-badge <?= $info['class'] ?>"><?= $info['text'] ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="pjm-date"><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="pjm-date"><?= $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '<span style="color:#cbd5e1;">—</span>' ?></span>
                                        </td>
                                        <td style="text-align:center;" class="no-print">
                                            <?php if ($status == 'selesai'): ?>
                                                <a href="proses_delete_peminjaman.php?id=<?= $row['id_peminjaman'] ?>" class="pjm-act-btn pjm-act-hapus" onclick="return confirm('Yakin hapus riwayat ini?')">
                                                    <i class="fa-solid fa-trash"></i> Hapus
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="pjm-act-btn <?= $status == 'pengembalian' ? 'pjm-act-terima' : 'pjm-act-kembalikan' ?>"
                                                    data-bs-toggle="modal" data-bs-target="#pengembalianModal"
                                                    data-peminjaman-id="<?= $row['id_peminjaman'] ?>"
                                                    data-barang-id="<?= $row['barang_id'] ?>"
                                                    data-nama-barang="<?= htmlspecialchars($row['nama_barang']) ?>"
                                                    data-qty-pinjam="<?= $row['qty'] ?>">
                                                    <?php if ($status == 'pengembalian'): ?>
                                                        <i class="fa-solid fa-check"></i> Terima
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-rotate-left"></i> Kembalikan
                                                    <?php endif; ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" style="text-align:center; padding:60px 20px; color:#94a3b8;">
                                                <i class="fa-solid fa-inbox" style="font-size:36px; display:block; margin-bottom:12px;"></i>
                                                Belum ada data peminjaman.
                                              </td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php include "footer.php"; ?>
        </div>
    </main>

    <!-- Modal Pengembalian -->
    <div class="modal fade" id="pengembalianModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h6 style="font-weight:700; font-size:14px; margin:0;">Pengembalian Barang</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <form action="proses_pengembalian.php" method="POST">
                    <div class="modal-body" style="padding:20px;">
                        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:12px; margin-bottom:16px; text-align:center;">
                            <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">Barang</div>
                            <div id="modalNamaBarang" style="font-size:15px; font-weight:800; color:#1e293b; margin-top:4px;"></div>
                        </div>
                        <input type="hidden" name="peminjaman_id" id="modal_peminjaman_id">
                        <input type="hidden" name="barang_id" id="modal_barang_id">
                        <div style="margin-bottom:14px;">
                            <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px; display:block;">Jumlah Kembali</label>
                            <input type="number" id="modal_qty_akhir" name="qty_akhir" min="0" required
                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:10px; padding:10px 14px; font-size:13px; background:#f8fafc;">
                            <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Jumlah dipinjam: <strong id="modalQtyPinjam"></strong></div>
                        </div>
                        <div style="margin-bottom:6px;">
                            <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px; display:block;">Keterangan</label>
                            <textarea id="modal_keterangan" name="keterangan" rows="2" placeholder="Contoh: Kondisi baik"
                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:10px; padding:10px 14px; font-size:13px; background:#f8fafc; resize:none;"></textarea>
                        </div>
                    </div>
                    <div style="padding:0 20px 20px;">
                        <button type="submit" style="width:100%; padding:11px; border:none; border-radius:10px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 4px 12px rgba(99,102,241,0.25);">
                            <i class="fa-solid fa-check"></i> Proses Pengembalian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statCards = document.querySelectorAll('.pjm-stat-card');
            const tableRows = document.querySelectorAll('#peminjamanTable tbody tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeCard = document.querySelector('.pjm-stat-card.active');
                const activeStatus = activeCard ? activeCard.dataset.status : 'semua';

                tableRows.forEach(row => {
                    const isStatusMatch = activeStatus === 'semua' || row.dataset.status === activeStatus;
                    const isSearchMatch = !row.dataset.searchable || row.dataset.searchable.includes(searchTerm);
                    row.style.display = (isStatusMatch && isSearchMatch) ? '' : 'none';
                });
            }

            searchInput.addEventListener('keyup', filterTable);

            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    statCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    filterTable();
                });
            });

            const pengembalianModal = document.getElementById('pengembalianModal');
            if (pengembalianModal) {
                pengembalianModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    this.querySelector('#modal_peminjaman_id').value = button.dataset.peminjamanId;
                    this.querySelector('#modal_barang_id').value = button.dataset.barangId;
                    this.querySelector('#modalNamaBarang').textContent = button.dataset.namaBarang;
                    this.querySelector('#modalQtyPinjam').textContent = button.dataset.qtyPinjam;
                    const qtyInput = this.querySelector('#modal_qty_akhir');
                    qtyInput.value = button.dataset.qtyPinjam;
                    qtyInput.max = button.dataset.qtyPinjam;
                });
            }
        });
    </script>
</body>
</html>