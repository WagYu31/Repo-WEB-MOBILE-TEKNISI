<div class="container mt-4">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header pb-0 p-3">
                    <h6 class="mb-0 text-uppercase"><i class="material-icons opacity-10" style="vertical-align: middle;">add_box</i> Tambah Barang Baru</h6>
                </div>
                <div class="card-body">
                    <form class="row g-3" method="POST" action="proses_tambah_barang.php" enctype="multipart/form-data">
                        <div class="col-12"><label class="form-label">Nama Barang</label><input type="text" class="form-control border p-2" name="nama_barang" placeholder="Contoh: Tang Potong" required></div>
                        <div class="col-12"><label class="form-label">Gambar Barang</label><input type="file" class="form-control border p-2" name="image_barang" accept="image/*"></div>
                        <div class="col-12"><label class="form-label">Deskripsi</label><textarea class="form-control border p-2" name="deskripsi" placeholder="Deskripsi singkat"></textarea></div>
                        <div class="col-12"><label class="form-label">Stok Awal</label><input type="number" class="form-control border p-2" name="stok" placeholder="Jumlah Stok" required min="0"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary w-100 mt-2"><i class="material-icons opacity-10" style="vertical-align: middle;">add</i> Tambah</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header pb-0 p-3"><h6 class="mb-0 text-uppercase"><i class="material-icons opacity-10" style="vertical-align: middle;">inventory_2</i> Data Stok Barang</h6></div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Query komprehensif (tetap sama)
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
                                $has_image = !empty($row['image_barang']);
                                $image_path = $has_image ? "uploads/" . $row['image_barang'] : "https://via.placeholder.com/300x200.png?text=No+Image";
                        ?>
                        <div class="col-md-6 col-lg-6 mb-4">
                            <div class="card inventory-card h-100">
                                <div class="image-container">
                                    <img src="<?= $image_path ?>" class="card-img-top" alt="<?= htmlspecialchars($row['nama_barang']) ?>">
                                    <?php if ($has_image): ?>
                                    <button class="image-popup-button" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image-url="<?= $image_path ?>">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-dark font-weight-bold text-capitalize"><?= htmlspecialchars($row['nama_barang']) ?></h5>
                                    <?php if(!empty($row['deskripsi'])): ?>
                                        <p class="card-text text-sm text-muted"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="stock-info mt-auto">
                                        <div><div class="stat-value text-success"><?= $row['stok_tersisa'] ?></div><div class="stat-label">Tersedia</div></div>
                                        <div><div class="stat-value text-warning"><?= $row['dipinjam'] ?></div><div class="stat-label">Dipinjam</div></div>
                                        <div><div class="stat-value text-primary"><?= $row['total_stok'] ?></div><div class="stat-label">Total</div></div>
                                    </div>

                                    <?php if ($row['dipinjam'] > 0): ?>
                                    <div class="mt-3 text-center">
                                        <div class="borrower-list-toggler" data-bs-target="#borrowers-<?= $row['id'] ?>" aria-expanded="false">
                                            Lihat Peminjam <i class="fas fa-chevron-down fa-xs"></i>
                                        </div>
                                        <div class="collapse" id="borrowers-<?= $row['id'] ?>"><div class="alert alert-secondary text-start py-2 px-3 text-xs mt-2 mb-0 text-light"><strong>Dipinjam oleh:</strong><br>
                                        <?= $row['peminjam'] ?></div></div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-center gap-2 mt-4 pt-3 border-top">
                                        <button class="btn btn-outline-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addStockModal<?= $row['id'] ?>"><i class="material-icons opacity-10 fs-6">add</i> Stok</button>
                                        <button class="btn btn-outline-warning btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="material-icons opacity-10 fs-6">edit</i> Edit</button>
                                        <a href="hapus_barang.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm mb-0" onclick="return confirm('Anda yakin?')"><i class="material-icons opacity-10 fs-6">delete</i> Hapus</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                            }
                        } else {
                            echo "<div class='col-12 text-center py-5'><p class='text-muted'>Belum ada data barang.</p></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <!--<button type="button" class="btn-close-lightbox" data-bs-dismiss="modal" aria-label="Close">-->
    <!--    <i class="fas fa-times"></i>-->
    <!--</button>-->
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><img src="" id="previewedImage" alt="Full Size Preview">
            </div>
        </div>
    </div>
</div>


<?php foreach ($barangData as $rowBarang): ?>
    
    <div class="modal fade" id="editModal<?= $rowBarang['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $rowBarang['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= $rowBarang['id'] ?>">Edit Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_edit_barang.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $rowBarang['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $rowBarang['image_barang'] ?>">
                        
                        <div class="mb-3">
                            <label for="edit_nama_barang_<?= $rowBarang['id'] ?>" class="form-label">Nama Barang</label>
                            <input type="text" class="form-control border p-2" id="edit_nama_barang_<?= $rowBarang['id'] ?>" name="nama_barang" value="<?= htmlspecialchars($rowBarang['nama_barang']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_image_barang_<?= $rowBarang['id'] ?>" class="form-label">Gambar Barang (Opsional)</label>
                            <br>
                            <?php 
                                $current_image = "uploads/" . $rowBarang['image_barang'];
                                if (empty($rowBarang['image_barang']) || !file_exists($current_image)) {
                                    $current_image = "https://via.placeholder.com/200";
                                }
                            ?>
                            <img src="<?= $current_image ?>" class="img-preview mb-2" style="width:100px; height:auto; display:block;">
                            <input type="file" class="form-control border p-2" id="edit_image_barang_<?= $rowBarang['id'] ?>" name="image_barang" accept="image/*">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah gambar.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_deskripsi_<?= $rowBarang['id'] ?>" class="form-label">Deskripsi</label>
                            <textarea class="form-control border p-2" id="edit_deskripsi_<?= $rowBarang['id'] ?>" name="deskripsi"><?= htmlspecialchars($rowBarang['deskripsi']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_stok_<?= $rowBarang['id'] ?>" class="form-label">Stok Tersedia</label>
                            <input type="number" class="form-control border p-2" id="edit_stok_<?= $rowBarang['id'] ?>" name="stok" value="<?= htmlspecialchars($rowBarang['stok_tersisa']) ?>" required min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addStockModal<?= $rowBarang['id'] ?>" tabindex="-1" aria-labelledby="addStockLabel<?= $rowBarang['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStockLabel<?= $rowBarang['id'] ?>">Tambah Stok - <?= htmlspecialchars($rowBarang['nama_barang']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_tambah_stock.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $rowBarang['id'] ?>">
                        <div class="mb-3">
                            <p>Stok saat ini: <strong><?= $rowBarang['stok_tersisa'] ?></strong></p>
                            <label for="qty_<?= $rowBarang['id'] ?>" class="form-label">Jumlah Stok yang Ditambahkan</label>
                            <input type="number" class="form-control border p-2" id="qty_<?= $rowBarang['id'] ?>" name="qty" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Skrip untuk Modal Preview Gambar
        const imagePreviewModal = document.getElementById('imagePreviewModal');
        if (imagePreviewModal) {
            imagePreviewModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const imageUrl = button.getAttribute('data-image-url');
                const previewedImage = imagePreviewModal.querySelector('#previewedImage');
                previewedImage.src = imageUrl;
            });
        }

        // Skrip untuk manual toggle collapse "Lihat Peminjam"
        const togglerElements = document.querySelectorAll('.borrower-list-toggler');
        togglerElements.forEach(function(toggler) {
            const targetId = toggler.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                const collapseInstance = new bootstrap.Collapse(targetElement, {
                    toggle: false
                });

                toggler.addEventListener('click', function() {
                    collapseInstance.toggle();
                    // Update atribut aria-expanded untuk styling
                    const isExpanded = toggler.getAttribute('aria-expanded') === 'true';
                    toggler.setAttribute('aria-expanded', !isExpanded);
                });
            }
        });
    });
</script>