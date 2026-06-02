<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Peminjaman";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .table-responsive {
            padding: 1rem;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .search-bar {
            width: 250px;
        }

        .table thead th {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .table thead th:hover {
            background-color: #f0f2f5;
        }
        
        .table thead th .sort-icon {
            margin-left: 5px;
            color: #ced4da;
            transition: color 0.2s ease;
        }
        
        .table thead th:hover .sort-icon {
            color: #344767;
        }

        .avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
        }

        .table td, .table th {
            vertical-align: middle;
        }
        
        @media print {
            body * { visibility: hidden; }
            #printable-content, #printable-content * { visibility: visible; }
            #printable-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php
        include "nav-top.php";
        setlocale(LC_TIME, 'id_ID.utf8');
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12" id="printable-content">
                    <div class="card">
                        <div class="card-header card-header-flex">
                            <div>
                                <h5 class="mb-0">Dashboard Peminjaman Barang</h5>
                                <p class="text-sm mb-0">Kelola dan pantau semua item yang dipinjam.</p>
                            </div>
                            <div class="no-print">
                                <a href="pinjam.php" class="btn bg-gradient-dark mb-0">+ Peminjaman</a>
                                <button class="btn bg-gradient-info mb-0 btn-print">Print</button>
                            </div>
                        </div>
                        <div class="card-body px-0 pt-0 pb-2">
                            <div class="filter-controls p-3 no-print">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary active" data-status="semua">Semua</button>
                                    <button type="button" class="btn btn-outline-primary" data-status="dipinjam">Masih Dipinjam</button>
                                    <button type="button" class="btn btn-outline-primary" data-status="pengembalian">Pengembalian</button>
                                    <button type="button" class="btn btn-outline-primary" data-status="selesai">Selesai</button>
                                </div>
                                <div class="ms-auto">
                                    <input type="text" id="searchInput" class="form-control form-control search-bar p-2" style="border:1px solid #aaaaaa; background-color: #fbfbfb" placeholder="Cari nama barang/teknisi...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0" id="peminjamanTable">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Teknisi</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Pinjam</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Kembali</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "
                                            SELECT 
                                                pb.id AS id_peminjaman, pb.barang_id, pb.teknisi_id, pb.status, t.nama AS nama_teknisi,
                                                b.nama_barang, pb.qty, pb.qty_akhir, pb.tgl_pinjam, pb.tgl_kembali, pb.keterangan
                                            FROM peminjaman_barang pb
                                            JOIN barang b ON pb.barang_id = b.id
                                            JOIN teknisi t ON pb.teknisi_id = t.id
                                            WHERE pb.deleted_at IS NULL
                                            ORDER BY t.nama ASC";
                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $status = $row['status'];

                                                $status_map = [
                                                    'dipinjam'    => ['text' => 'Dipinjam',    'badge' => 'bg-gradient-warning'],
                                                    'pengembalian'=> ['text' => 'Pengembalian', 'badge' => 'bg-gradient-primary'],
                                                    'selesai'     => ['text' => 'Selesai',     'badge' => 'bg-gradient-success'],
                                                    'dialihkan'   => ['text' => 'Pengalihan',  'badge' => 'bg-gradient-secondary'],
                                                    'persetujuan' => ['text' => 'Menunggu',    'badge' => 'bg-gradient-danger']
                                                ];
                                                
                                                // Ambil data status, jika tidak ditemukan, gunakan 'dipinjam' sebagai default
                                                $status_info = $status_map[$status] ?? $status_map['dipinjam'];
                                                
                                                $status_text = $status_info['text'];
                                                $status_badge = $status_info['badge'];
                                                
                                                $initials = strtoupper(substr($row['nama_teknisi'], 0, 1));
                                                $colors = ['#17A2B8', '#6F42C1', '#344feb', '#a234eb', '#ffe438', '#E83E8C', '#FD7E14', '#20C997'];
                                                $bgColor = $colors[crc32($row['nama_teknisi']) % count($colors)];
                                        ?>
                                                <tr data-status="<?= $status ?>" data-searchable="<?= strtolower(htmlspecialchars($row['nama_teknisi']) . ' ' . htmlspecialchars($row['nama_barang'])) ?>">
                                                    <td>
                                                        <div class="d-flex px-2 py-1">
                                                            <div>
                                                                <div class="avatar-initials me-3" style="background-color: <?= $bgColor ?>;"><?= $initials ?></div>
                                                            </div>
                                                            <div class="d-flex flex-column justify-content-center">
                                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['nama_teknisi']) ?></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <p class="text-sm font-weight-bold mb-0 text-capitalize"><?= htmlspecialchars($row['nama_barang']) ?></p>
                                                        <p class="text-xs text-secondary mb-0">Qty: <?= htmlspecialchars($row['qty']) ?></p>
                                                    </td>
                                                    <td class="align-middle text-center text-sm">
                                                        <span class="badge badge-sm <?= $status_badge ?>"><?= $status_text ?></span>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <span class="text-secondary text-xs font-weight-bold"><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></span>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <span class="text-secondary text-xs font-weight-bold"><?= $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-' ?></span>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <?php if ($status == 'selesai') : ?>
                                                            <a href="proses_delete_peminjaman.php?id=<?= $row['id_peminjaman'] ?>" class="btn btn-outline-danger btn-sm mb-0" onclick="return confirm('Anda yakin ingin menghapus riwayat peminjaman ini?');">
                                                                Hapus
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#pengembalianModal" data-peminjaman-id="<?= $row['id_peminjaman'] ?>" data-barang-id="<?= $row['barang_id'] ?>" data-nama-barang="<?= htmlspecialchars($row['nama_barang']) ?>" data-qty-pinjam="<?= $row['qty'] ?>">
                                                                <?php if ($status == 'pengembalian') : ?>
                                                                Terima
                                                                <?php else: ?>
                                                                Kembalikan
                                                                <?php endif; ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center py-5"><h6 class="text-secondary">Belum ada data peminjaman.</h6></td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // include "floating-menu.php";
            include "footer.php";
            ?>
        </div>
    </main>

    <div class="modal fade" id="pengembalianModal" tabindex="-1" aria-labelledby="pengembalianModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pengembalianModalLabel">Form Pengembalian Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_pengembalian.php" method="POST">
                    <div class="modal-body">
                        <p class="mb-3">Anda akan mengembalikan: <strong id="modalNamaBarang"></strong></p>
                        <input type="hidden" name="peminjaman_id" id="modal_peminjaman_id">
                        <input type="hidden" name="barang_id" id="modal_barang_id">
                        <div class="mb-3">
                            <label for="modal_qty_akhir" class="form-label">Jumlah Kembali</label>
                            <input type="number" class="form-control border p-2" id="modal_qty_akhir" name="qty_akhir" min="0" required>
                            <div class="form-text">Jumlah dipinjam: <span id="modalQtyPinjam"></span></div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_keterangan" class="form-label">Keterangan Pengembalian</label>
                            <textarea class="form-control border p-2" id="modal_keterangan" name="keterangan" rows="2" placeholder="Contoh: Kondisi baik"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Proses Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterButtons = document.querySelectorAll('.filter-controls .btn');
            const tableRows = document.querySelectorAll('#peminjamanTable tbody tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeStatus = document.querySelector('.filter-controls .btn.active').getAttribute('data-status');

                tableRows.forEach(row => {
                    const isStatusMatch = activeStatus === 'semua' || row.dataset.status === activeStatus;
                    const isSearchMatch = row.dataset.searchable.toLowerCase().includes(searchTerm);

                    if (isStatusMatch && isSearchMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('keyup', filterTable);

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    filterTable();
                });
            });

            const pengembalianModal = document.getElementById('pengembalianModal');
            if (pengembalianModal) {
                pengembalianModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    pengembalianModal.querySelector('#modal_peminjaman_id').value = button.dataset.peminjamanId;
                    pengembalianModal.querySelector('#modal_barang_id').value = button.dataset.barangId;
                    pengembalianModal.querySelector('#modalNamaBarang').textContent = button.dataset.namaBarang;
                    pengembalianModal.querySelector('#modalQtyPinjam').textContent = button.dataset.qtyPinjam;
                    const qtyAkhirInput = pengembalianModal.querySelector('#modal_qty_akhir');
                    qtyAkhirInput.value = button.dataset.qtyPinjam;
                    qtyAkhirInput.max = button.dataset.qtyPinjam;
                });
            }
            
            document.querySelector(".btn-print").addEventListener("click", () => window.print());
        });
    </script>
</body>

</html>