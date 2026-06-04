<style>
    /* ═══ PREMIUM INVENTORY ═══ */
    .inv-layout { display: grid; grid-template-columns: 340px 1fr; gap: 24px; padding: 0; }

    /* Add form */
    .inv-add-card {
        background: #fff; border-radius: 16px; border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
        padding: 24px; position: sticky; top: 90px; align-self: start;
    }
    .inv-add-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .inv-add-icon {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(34,197,94,0.2);
    }
    .inv-add-icon i { color: #fff; font-size: 15px; }
    .inv-add-header h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
    .inv-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block; }
    .inv-input {
        width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: 10px 14px; font-size: 13px; color: #1e293b; background: #f8fafc;
        transition: all 0.2s; font-weight: 500;
    }
    .inv-input:focus { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.08); outline: none; background: #fff; }
    .inv-input-file { padding: 8px 12px; }
    .inv-btn-add {
        width: 100%; padding: 11px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff;
        font-size: 13px; font-weight: 700; cursor: pointer; margin-top: 8px;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        box-shadow: 0 4px 12px rgba(34,197,94,0.25); transition: all 0.2s;
    }
    .inv-btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(34,197,94,0.35); }
    .inv-field + .inv-field { margin-top: 14px; }

    /* Right side: items grid */
    .inv-items-section {}
    .inv-items-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .inv-items-icon {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(99,102,241,0.2);
    }
    .inv-items-icon i { color: #fff; font-size: 15px; }
    .inv-items-header h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
    .inv-items-header .item-count {
        font-size: 11px; font-weight: 700; color: #6366f1; background: #eef2ff;
        padding: 3px 10px; border-radius: 20px; margin-left: 8px;
    }
    .inv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }

    /* Item card */
    .inv-item {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        overflow: hidden; transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .inv-item:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); border-color: #d1d5db; }
    .inv-item-img {
        height: 160px; background: #f1f5f9; overflow: hidden; position: relative;
    }
    .inv-item-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .inv-item:hover .inv-item-img img { transform: scale(1.05); }
    .inv-zoom-btn {
        position: absolute; bottom: 8px; right: 8px;
        width: 30px; height: 30px; border-radius: 8px;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        border: none; color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.2s; font-size: 12px;
    }
    .inv-item:hover .inv-zoom-btn { opacity: 1; }

    .inv-item-body { padding: 16px; }
    .inv-item-name { font-size: 15px; font-weight: 800; color: #1e293b; margin: 0 0 4px; text-transform: capitalize; }
    .inv-item-desc { font-size: 12px; color: #94a3b8; line-height: 1.4; margin: 0; }

    .inv-stats {
        display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;
        margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9;
    }
    .inv-stat { text-align: center; }
    .inv-stat-val { font-size: 20px; font-weight: 800; line-height: 1; }
    .inv-stat-val.s-green { color: #16a34a; }
    .inv-stat-val.s-amber { color: #d97706; }
    .inv-stat-val.s-blue { color: #2563eb; }
    .inv-stat-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }

    .inv-borrower-toggle {
        display: block; text-align: center; font-size: 12px; font-weight: 600;
        color: #6366f1; cursor: pointer; margin-top: 10px; transition: color 0.2s;
    }
    .inv-borrower-toggle:hover { color: #4f46e5; }
    .inv-borrower-toggle i { transition: transform 0.3s; font-size: 10px; }
    .inv-borrower-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
    .inv-borrower-box {
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px;
        padding: 8px 12px; margin-top: 8px; font-size: 11px; color: #475569; line-height: 1.6;
    }

    .inv-actions {
        display: flex; gap: 6px; justify-content: center;
        padding: 12px 16px; border-top: 1px solid #f1f5f9;
    }
    .inv-act-btn {
        padding: 6px 14px; font-size: 11px; font-weight: 700; border-radius: 8px;
        border: 1.5px solid; cursor: pointer; transition: all 0.15s;
        display: inline-flex; align-items: center; gap: 4px; text-decoration: none;
    }
    .inv-act-btn.act-stok { border-color: #6366f1; color: #6366f1; background: #fff; }
    .inv-act-btn.act-stok:hover { background: #6366f1; color: #fff; }
    .inv-act-btn.act-edit { border-color: #f59e0b; color: #f59e0b; background: #fff; }
    .inv-act-btn.act-edit:hover { background: #f59e0b; color: #fff; }
    .inv-act-btn.act-hapus { border-color: #ef4444; color: #ef4444; background: #fff; }
    .inv-act-btn.act-hapus:hover { background: #ef4444; color: #fff; }

    @media (max-width: 992px) {
        .inv-layout { grid-template-columns: 1fr; }
        .inv-add-card { position: static; }
    }
    @media (max-width: 576px) {
        .inv-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="inv-layout">
    <!-- Left: Add Form -->
    <div>
        <div class="inv-add-card">
            <div class="inv-add-header">
                <div class="inv-add-icon"><i class="fa-solid fa-plus"></i></div>
                <h6>Tambah Barang Baru</h6>
            </div>
            <form method="POST" action="proses_tambah_barang.php" enctype="multipart/form-data">
                <div class="inv-field">
                    <label class="inv-label">Nama Barang</label>
                    <input type="text" class="inv-input" name="nama_barang" placeholder="Contoh: Tang Potong" required>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Gambar Barang</label>
                    <input type="file" class="inv-input inv-input-file" name="image_barang" accept="image/*">
                </div>
                <div class="inv-field">
                    <label class="inv-label">Deskripsi</label>
                    <textarea class="inv-input" name="deskripsi" placeholder="Deskripsi singkat" rows="2"></textarea>
                </div>
                <div class="inv-field">
                    <label class="inv-label">Stok Awal</label>
                    <input type="number" class="inv-input" name="stok" placeholder="Jumlah Stok" required min="0">
                </div>
                <button type="submit" class="inv-btn-add">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Items Grid -->
    <div class="inv-items-section">
        <?php
        $sqlBarang = "
            SELECT
                b.id, b.nama_barang, b.image_barang, b.deskripsi, b.stok AS stok_tersisa, b.updated_at,
                COALESCE(p.borrowed_count, 0) AS dipinjam,
                (b.stok + COALESCE(p.borrowed_count, 0)) AS total_stok,
                COALESCE(p.borrowers, 'Tidak ada peminjam') AS peminjam
            FROM barang b
            LEFT JOIN (
                SELECT
                    pb.barang_id, COUNT(pb.id) AS borrowed_count,
                    GROUP_CONCAT(DISTINCT t.nama SEPARATOR '<br>') AS borrowers
                FROM peminjaman_barang pb
                JOIN teknisi t ON pb.teknisi_id = t.id
                WHERE pb.tgl_kembali IS NULL AND pb.deleted_at IS NULL
                GROUP BY pb.barang_id
            ) AS p ON b.id = p.barang_id
            WHERE b.deleted_at IS NULL
            ORDER BY b.updated_at DESC
        ";
        $resultBarang = $conn->query($sqlBarang);
        
        $barangData = [];
        if ($resultBarang->num_rows > 0) {
            while ($row = $resultBarang->fetch_assoc()) {
                $barangData[] = $row;
            }
        }
        $totalItems = count($barangData);
        ?>

        <div class="inv-items-header">
            <div class="inv-items-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <h6>Data Stok Barang</h6>
            <span class="item-count"><?= $totalItems ?> item</span>
        </div>

        <div class="inv-grid">
            <?php if ($totalItems > 0): ?>
                <?php foreach ($barangData as $row):
                    $has_image = !empty($row['image_barang']);
                    $image_path = $has_image ? "uploads/" . $row['image_barang'] : "https://via.placeholder.com/300x200.png?text=No+Image";
                ?>
                <div class="inv-item">
                    <div class="inv-item-img">
                        <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>">
                        <?php if ($has_image): ?>
                        <button class="inv-zoom-btn" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image-url="<?= $image_path ?>">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="inv-item-body">
                        <h6 class="inv-item-name"><?= htmlspecialchars($row['nama_barang']) ?></h6>
                        <?php if(!empty($row['deskripsi'])): ?>
                            <p class="inv-item-desc"><?= htmlspecialchars($row['deskripsi']) ?></p>
                        <?php endif; ?>

                        <div class="inv-stats">
                            <div class="inv-stat">
                                <div class="inv-stat-val s-green"><?= $row['stok_tersisa'] ?></div>
                                <div class="inv-stat-label">Tersedia</div>
                            </div>
                            <div class="inv-stat">
                                <div class="inv-stat-val s-amber"><?= $row['dipinjam'] ?></div>
                                <div class="inv-stat-label">Dipinjam</div>
                            </div>
                            <div class="inv-stat">
                                <div class="inv-stat-val s-blue"><?= $row['total_stok'] ?></div>
                                <div class="inv-stat-label">Total</div>
                            </div>
                        </div>

                        <?php if ($row['dipinjam'] > 0): ?>
                        <div class="inv-borrower-toggle" data-bs-target="#borrowers-<?= $row['id'] ?>" aria-expanded="false">
                            Lihat Peminjam <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="collapse" id="borrowers-<?= $row['id'] ?>">
                            <div class="inv-borrower-box">
                                <strong>Dipinjam oleh:</strong><br>
                                <?= $row['peminjam'] ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="inv-actions">
                        <button class="inv-act-btn act-stok" data-bs-toggle="modal" data-bs-target="#addStockModal<?= $row['id'] ?>">
                            <i class="fa-solid fa-plus"></i> Stok
                        </button>
                        <button class="inv-act-btn act-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <a href="hapus_barang.php?id=<?= $row['id'] ?>" class="inv-act-btn act-hapus" onclick="return confirm('Anda yakin?')">
                            <i class="fa-solid fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#94a3b8;">
                    <i class="fa-solid fa-box-open" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                    Belum ada data barang.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:transparent; border:none; box-shadow:none;">
            <div class="modal-body p-0 text-center">
                <img src="" id="previewedImage" alt="Preview" style="max-height:85vh; max-width:100%; border-radius:12px; box-shadow:0 12px 40px rgba(0,0,0,0.3);">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 bg-white" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>
</div>

<!-- Edit & Stock Modals -->
<?php foreach ($barangData as $rowBarang): ?>
    <div class="modal fade" id="editModal<?= $rowBarang['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h6 style="font-weight:700; font-size:14px;">Edit Barang</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <form action="proses_edit_barang.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body" style="padding:20px;">
                        <input type="hidden" name="id" value="<?= $rowBarang['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $rowBarang['image_barang'] ?>">
                        <div class="inv-field">
                            <label class="inv-label">Nama Barang</label>
                            <input type="text" class="inv-input" name="nama_barang" value="<?= htmlspecialchars($rowBarang['nama_barang']) ?>" required>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Gambar (Opsional)</label>
                            <?php
                                $current_image = "uploads/" . $rowBarang['image_barang'];
                                if (empty($rowBarang['image_barang']) || !file_exists($current_image)) {
                                    $current_image = "https://via.placeholder.com/200";
                                }
                            ?>
                            <img src="<?= $current_image ?>" style="width:80px; height:60px; object-fit:cover; border-radius:8px; margin-bottom:8px; display:block;">
                            <input type="file" class="inv-input inv-input-file" name="image_barang" accept="image/*">
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Deskripsi</label>
                            <textarea class="inv-input" name="deskripsi" rows="2"><?= htmlspecialchars($rowBarang['deskripsi']) ?></textarea>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Stok Tersedia</label>
                            <input type="number" class="inv-input" name="stok" value="<?= $rowBarang['stok_tersisa'] ?>" required min="0">
                        </div>
                    </div>
                    <div style="padding:0 20px 20px; display:flex; gap:8px;">
                        <button type="button" class="inv-act-btn act-edit" data-bs-dismiss="modal" style="flex:1; justify-content:center;">Batal</button>
                        <button type="submit" class="inv-btn-add" style="flex:2; margin-top:0;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addStockModal<?= $rowBarang['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:16px 20px;">
                    <h6 style="font-weight:700; font-size:14px;">Tambah Stok</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:10px;"></button>
                </div>
                <form action="proses_tambah_stock.php" method="POST">
                    <div class="modal-body" style="padding:20px;">
                        <input type="hidden" name="id" value="<?= $rowBarang['id'] ?>">
                        <p style="font-size:13px; color:#64748b; margin-bottom:12px;">
                            <strong style="color:#1e293b;"><?= htmlspecialchars($rowBarang['nama_barang']) ?></strong><br>
                            Stok saat ini: <span style="font-weight:700; color:#16a34a;"><?= $rowBarang['stok_tersisa'] ?></span>
                        </p>
                        <label class="inv-label">Jumlah Ditambahkan</label>
                        <input type="number" class="inv-input" name="qty" required min="1" placeholder="0">
                    </div>
                    <div style="padding:0 20px 20px;">
                        <button type="submit" class="inv-btn-add" style="margin-top:0;">
                            <i class="fa-solid fa-plus"></i> Tambah Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const imagePreviewModal = document.getElementById('imagePreviewModal');
    if (imagePreviewModal) {
        imagePreviewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const imageUrl = button.getAttribute('data-image-url');
            document.getElementById('previewedImage').src = imageUrl;
        });
    }

    document.querySelectorAll('.inv-borrower-toggle').forEach(function(toggler) {
        const targetId = toggler.getAttribute('data-bs-target');
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            const collapseInstance = new bootstrap.Collapse(targetElement, { toggle: false });
            toggler.addEventListener('click', function() {
                collapseInstance.toggle();
                const isExpanded = toggler.getAttribute('aria-expanded') === 'true';
                toggler.setAttribute('aria-expanded', !isExpanded);
            });
        }
    });
});
</script>