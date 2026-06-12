<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Peminjaman Barang";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .list-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 1rem;
            border-radius: .375rem;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        ?>
        <div class="container-fluid py-4">
            <div class="row mb-4 mt-n4">
                <div class="col-12">
                    <div class="card p-3">
                        <div class="card-header pb-0 p-3">
                            <h6 class="mb-0 mx-1 ms-n3 lead font-weight-bold text-uppercase">Peminjaman Barang</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php
                            // Ambil data teknisi
                            $teknisiQuery = "SELECT id, nama FROM teknisi WHERE deleted_at IS NULL ORDER BY nama ASC";
                            $teknisiResult = $conn->query($teknisiQuery);

                            // Ambil data barang dengan stok > 0
                            $barangQuery = "SELECT id, nama_barang, stok FROM barang WHERE stok > 0 AND deleted_at IS NULL ORDER BY nama_barang ASC";
                            $barangResult = $conn->query($barangQuery);
                            ?>

                            <form method="POST" action="proses_peminjaman.php">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h5>Pilih Teknisi</h5>
                                        <div class="list-container" id="teknisiList">
                                            <?php while ($teknisi = $teknisiResult->fetch_assoc()) : ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="teknisi[]" value="<?= $teknisi['id'] ?>" id="teknisi-<?= $teknisi['id'] ?>">
                                                    <label class="form-check-label" for="teknisi-<?= $teknisi['id'] ?>">
                                                        <?= htmlspecialchars($teknisi['nama']) ?>
                                                    </label>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <h5>Pilih Barang</h5>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="material-icons">search</i></span>
                                            <input type="text" class="form-control border p-2" id="filterBarang" placeholder="Cari nama barang...">
                                        </div>
                                        <div class="table-responsive list-container">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-2">Pilih</th>
                                                        <th>Nama Barang (Stok)</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="barangList">
                                                <?php while ($barang = $barangResult->fetch_assoc()) : ?>
                                                    <tr>
                                                        <td class="align-middle">
                                                            <div class="form-check">
                                                                <input class="form-check-input barang-checkbox" type="checkbox" name="barang[<?= $barang['id'] ?>][check]" value="1" id="barang-check-<?= $barang['id'] ?>">
                                                            </div>
                                                        </td>
                                                        <td class="align-middle">
                                                            <label class="form-check-label" for="barang-check-<?= $barang['id'] ?>">
                                                                <?= htmlspecialchars($barang['nama_barang']) ?> <b>(<?= $barang['stok'] ?>)</b>
                                                            </label>
                                                        </td>
                                                        <td class="align-middle">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm border p-2 barang-qty" 
                                                                   name="barang[<?= $barang['id'] ?>][qty]" 
                                                                   min="1" 
                                                                   max="<?= $barang['stok'] ?>" 
                                                                   placeholder="Qty"
                                                                   data-checkbox-id="barang-check-<?= $barang['id'] ?>">
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100 mt-3">Proses Peminjaman</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </main>
    <?php include "js-include.php"; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- [DIPERBAIKI] Logika filter sekarang untuk Barang ---
        const filterBarangInput = document.getElementById('filterBarang');
        const barangListBody = document.getElementById('barangList');
        const barangItems = barangListBody.getElementsByTagName('tr');

        filterBarangInput.addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase();
            for (let i = 0; i < barangItems.length; i++) {
                // Cari teks di dalam sel kedua (Nama Barang)
                const label = barangItems[i].getElementsByTagName('td')[1].getElementsByTagName('label')[0];
                if (label.textContent.toLowerCase().indexOf(filterValue) > -1) {
                    barangItems[i].style.display = ""; // Tampilkan baris
                } else {
                    barangItems[i].style.display = "none"; // Sembunyikan baris
                }
            }
        });

        // --- Logika untuk interaksi list barang (tidak ada perubahan) ---
        const barangQtyInputs = document.querySelectorAll('.barang-qty');
        barangQtyInputs.forEach(input => {
            input.addEventListener('input', function() {
                const checkboxId = this.getAttribute('data-checkbox-id');
                const checkbox = document.getElementById(checkboxId);
                if (this.value > 0) {
                    checkbox.checked = true;
                }
            });
        });

        const barangCheckboxes = document.querySelectorAll('.barang-checkbox');
        barangCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const qtyInput = document.querySelector(`input[data-checkbox-id="${this.id}"]`);
                if (!this.checked) {
                    qtyInput.value = '';
                }
            });
        });

    });
    </script>
</body>
</html>