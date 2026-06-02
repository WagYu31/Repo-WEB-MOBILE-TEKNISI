<div class="container mt-n4">
    <div class="card p-4 col-12 col-md-11">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Peminjaman Barang</h6>
                </div>
            </div>
        </div>
        <div class="card-body pb-0 p-0 mt-3">
            <?php

            // Ambil data teknisi
            $teknisiQuery = "SELECT id, nama FROM teknisi";
            $teknisiResult = $conn->query($teknisiQuery);

            // Ambil data barang dengan stok > 0
            $barangQuery = "SELECT id, nama_barang, stok FROM barang WHERE stok > 0 AND deleted_at IS NULL";
            $barangResult = $conn->query($barangQuery);
            ?>

            <form method="POST" action="proses_peminjaman.php">
                <h3>Pilih Teknisi</h3>
                <?php while ($teknisi = $teknisiResult->fetch_assoc()) : ?>
                    <label>
                        <input type="checkbox" name="teknisi[]" value="<?= $teknisi['id'] ?>"> <?= $teknisi['nama'] ?>
                    </label><br>
                <?php endwhile; ?>

                <h3>Pilih Barang</h3>
                <?php while ($barang = $barangResult->fetch_assoc()) : ?>
                    <label>
                        <input type="checkbox" name="barang[<?= $barang['id'] ?>][check]" value="1"> <?= $barang['nama_barang'] ?>
                    </label>
                    <input type="number" name="barang[<?= $barang['id'] ?>][qty]" min="1" max="<?= $barang['stok'] ?>" placeholder="Jumlah">
                    <br>
                <?php endwhile; ?>

                <button type="submit">Proses Peminjaman</button>
            </form>

        </div>
    </div>
</div>