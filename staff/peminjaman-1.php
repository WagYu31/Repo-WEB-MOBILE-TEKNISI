<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Peminjaman";
$currentPage = "Today";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }

    .btn-circle {
      border-radius: 50%;
      width: 30px;
      height: 30px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    #toggleLoadMore {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }

    @media print {
      .no-print {
        display: none;
      }
    }

    .row-teknisi {
      cursor: pointer;
      border-bottom: 1px solid #dee2e6;
    }

    .row-teknisi:hover {
      background-color: #f8f9fa;
    }

    .icon-toggle {
      transition: transform 0.3s ease-in-out;
    }

    .row-teknisi[aria-expanded="true"] .icon-toggle {
      transform: rotate(90deg);
    }

    .collapsing-row>td {
      padding: 0 !important;
      border: none;
    }

    .nested-table-container {
      padding: 0.5rem 1rem;
      background-color: #f8f9fa;
    }

    .nested-table th,
    .nested-table td {
      border: none;
      background-color: transparent !important;
    }
    
    /* Memberi gaya pada baris teknisi yang bisa diklik */
    .row-teknisi-header {
        cursor: pointer;
        border-top: 1px solid #dee2e6;
        transition: background-color 0.2s ease-in-out;
    }

    .row-teknisi-header:hover {
        background-color: #f8f9fa; /* Warna latar saat mouse di atasnya */
    }

    /* Menghilangkan padding dan border dari baris yang berisi konten collapse */
    .collapsing-row > td {
        padding: 0 !important;
        border-bottom: none;
    }

    /* Kontainer untuk tabel detail dengan efek "card" */
    .detail-card {
        margin: 0.5rem 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden; /* Memastikan border-radius terlihat */
    }

    /* Ikon panah dengan transisi */
    .icon-toggle {
        transition: transform 0.3s ease;
    }

    /* Memutar ikon panah saat dropdown terbuka */
    .row-teknisi-header[aria-expanded="true"] .icon-toggle {
        transform: rotate(90deg);
    }

    <?php include "css/floating-menu2.css"; ?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";

  if (isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun'])) {
    $current_date = $_GET['cariBulanTahun'];
  } else {
    $current_date = date("Y-m"); // Today's date
  }
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
    $todayDate = strftime('%d %B %Y');
    ?>
    <!-- End Navbar -->
    <div class="container-fluid py-4">

      <div class="row mb-4 mt-0">
        <div class="col-md-6 col-12 d-flex justify-content-start align-items-center">
          <a href="pinjam.php" class="btn bg-gradient-dark w-md-35 w-40 me-2">+ Peminjaman</a>
          <!-- <a href="detail-lap.php" class="btn bg-gradient-dark w-35 me-2">Detail Invoice</a> -->
          <button class="btn bg-gradient-info w-md-30 w-25 btn-print">Print</button>
        </div>

        <div class="col-6">

        </div>

        <div class="col-lg-12" id="printable-content">
    <div class="card h-100 py-3">
        <div class="card-header pb-0 p-3">
            <div class="row">
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Data Peminjaman Barang</h6>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center justify-content-center">
                    <form method="GET" action="" class="col-12 col-md-12 d-flex align-items-center justify-content-center flex-row">
                        <input type="month" class="form-control border p-2 bg-outline-info w-70 no-print" name="cariBulanTahun" value="<?php echo htmlspecialchars($current_date); ?>">
                        <button class="btn bg-gradient-info w-30 mt-3 ms-2 no-print">Cari</button>
                    </form>
                </div>
            </div>
        </div>
        <?php

        $sql = "
            SELECT 
                pb.code, 
                pb.barang_id,
                pb.id AS id_peminjaman,
                pb.teknisi_id, 
                b.nama_barang,
                t.nama AS nama_teknisi,
                pb.qty,
                pb.qty_akhir,
                pb.denda,
                pb.status,
                pb.tgl_pinjam,
                pb.tgl_kembali,
                pb.keterangan
            FROM peminjaman_barang pb
            JOIN barang b ON pb.barang_id = b.id
            JOIN teknisi t ON pb.teknisi_id = t.id
            WHERE DATE_FORMAT(pb.tgl_pinjam, '%Y-%m') = ? AND pb.deleted_at IS NULL
            ORDER BY pb.teknisi_id, pb.tgl_pinjam, pb.code
        ";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $current_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        ?>
        <div class="card-body pb-0 p-0">
            <div class="col-12 px-4 pt-3">
                <p class="text-dark">
                    Bulan
                    <?php
                    setlocale(LC_TIME, 'id_ID');
                    echo strftime('%B %Y', strtotime($current_date));
                    ?>
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php
                            $grouped_data = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $grouped_data[$row['teknisi_id']]['nama_teknisi'] = $row['nama_teknisi'];
                                $grouped_data[$row['teknisi_id']]['items'][] = $row;
                            }
                            ?>
                            <?php foreach ($grouped_data as $teknisi_id => $data): ?>
                                <?php
                                $teknisi_name = htmlspecialchars($data['nama_teknisi']);
                                $collapse_id = 'collapse-teknisi-' . $teknisi_id;
                                $item_count = count($data['items']);
                                ?>
                                <tr class="row-teknisi-header" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="false">
                                    <td class="p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-chevron-right icon-toggle me-3"></i>
                                            <h6 class="mb-0 text-sm"><?= $teknisi_name ?></h6>
                                        </div>
                                    </td>
                                    <td colspan="6"></td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-secondary"><?= $item_count ?> Item</span>
                                    </td>
                                </tr>
                                <tr class="collapsing-row">
                                    <td colspan="8">
                                        <div class="collapse" id="<?= $collapse_id ?>">
                                            <div class="detail-card">
                                                <table class="table table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Pinjam</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Qty</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Kembali</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Qty Kembali</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan</th>
                                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($data['items'] as $row): ?>
                                                            <?php
                                                            $id_peminjaman = $row['id_peminjaman'];
                                                            $barang_id = $row['barang_id'];
                                                            $keterangan = htmlspecialchars($row['keterangan']);
                                                            $nama_barang = htmlspecialchars($row['nama_barang']);
                                                            $tgl_pinjam = date('d M Y', strtotime($row['tgl_pinjam']));
                                                            $qty_pinjam = htmlspecialchars($row['qty']);
                                                            $tgl_kembali = $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-';
                                                            $qty_kembali = $row['qty_akhir'] ? htmlspecialchars($row['qty_akhir']) : '-';
                                                            ?>
                                                            <tr>
                                                                <td class="w-25 ps-3"><?= $nama_barang ?></td>
                                                                <td class="align-middle text-center text-sm"><?= $tgl_pinjam ?></td>
                                                                <td class="align-middle text-center text-sm"><?= $qty_pinjam ?></td>
                                                                <td class="align-middle text-center text-sm"><?= $tgl_kembali ?></td>
                                                                <td class="align-middle text-center text-sm"><?= $qty_kembali ?></td>
                                                                <td class="align-middle text-center text-sm"><?= $keterangan ?></td>
                                                                <td class="align-middle text-center">
                                                                    <?php if (is_null($row['tgl_kembali'])): ?>
                                                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                                                            <button type="button" class="btn btn-outline-warning btn-xs p-1 px-2 mb-0 px-3" data-bs-toggle="modal" data-bs-target="#pengembalianModal" data-peminjaman-id="<?= $id_peminjaman ?>" data-barang-id="<?= $barang_id ?>" data-nama-barang="<?= $nama_barang ?>" data-qty-pinjam="<?= $qty_pinjam ?>" title="Form Pengembalian">
                                                                                Kembalikan
                                                                            </button>

                                                                            <a href="proses_delete_peminjaman.php?id=<?= $id_peminjaman ?>"
                                                                                class="btn btn-outline-danger btn-xs p-1 px-2 mb-0"
                                                                                onclick="return confirm('Apakah Anda yakin ingin menghapus data peminjaman ini? Aksi ini tidak bisa diurungkan.');"
                                                                                title="Hapus Peminjaman">
                                                                                Delete
                                                                            </a>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-sm bg-gradient-success">Selesai</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <h6 class="text-secondary">Tidak ada data peminjaman pada bulan ini.</h6>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pengembalianModal" tabindex="-1" aria-labelledby="pengembalianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pengembalianModalLabel">Form Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_pengembalian.php" method="POST">
                <div class="modal-body">
                    <p>Anda akan mengembalikan barang: <strong id="modalNamaBarang"></strong></p>

                    <input type="hidden" name="peminjaman_id" id="modal_peminjaman_id">
                    <input type="hidden" name="barang_id" id="modal_barang_id">

                    <div class="mb-3">
                        <label for="modal_qty_akhir" class="form-label">Jumlah Kembali</label>
                        <input type="number" class="form-control border p-2" id="modal_qty_akhir" name="qty_akhir" min="1" required>
                        <div class="form-text">Jumlah yang dipinjam: <span id="modalQtyPinjam"></span></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_keterangan" class="form-label">Keterangan Pengembalian</label>
                        <textarea class="form-control border p-2" id="modal_keterangan" name="keterangan" rows="3" placeholder="Contoh: Kondisi baik, ada sedikit lecet, dll."></textarea>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const pengembalianModal = document.getElementById('pengembalianModal');
        if (pengembalianModal) {
            pengembalianModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const peminjamanId = button.getAttribute('data-peminjaman-id');
                const barangId = button.getAttribute('data-barang-id');
                const namaBarang = button.getAttribute('data-nama-barang');
                const qtyPinjam = button.getAttribute('data-qty-pinjam');

                const modalPeminjamanIdInput = pengembalianModal.querySelector('#modal_peminjaman_id');
                const modalBarangIdInput = pengembalianModal.querySelector('#modal_barang_id');
                const modalNamaBarangText = pengembalianModal.querySelector('#modalNamaBarang');
                const modalQtyAkhirInput = pengembalianModal.querySelector('#modal_qty_akhir');
                const modalQtyPinjamText = pengembalianModal.querySelector('#modalQtyPinjam');

                modalPeminjamanIdInput.value = peminjamanId;
                modalBarangIdInput.value = barangId;
                modalNamaBarangText.textContent = namaBarang;
                modalQtyPinjamText.textContent = qtyPinjam;
                modalQtyAkhirInput.value = qtyPinjam;
                modalQtyAkhirInput.max = qtyPinjam;
            });
        }

        // --- BAGIAN BARU UNTUK MEMAKSA FUNGSI BUKA-TUTUP (TOGGLE) ---

        // 1. Cari semua baris teknisi yang bisa diklik
        const anTRIGGERS = document.querySelectorAll('.row-teknisi');

        anTRIGGERS.forEach(trigger => {
            // 2. Tambahkan event listener 'click' pada setiap baris
            trigger.addEventListener('click', function(event) {
                event.preventDefault(); // Mencegah perilaku default browser

                // 3. Dapatkan ID target dari atribut data-bs-target
                const targetId = this.getAttribute('data-bs-target');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    // 4. Buat atau dapatkan instance Collapse Bootstrap untuk target
                    const collapseInstance = bootstrap.Collapse.getOrCreateInstance(targetElement);

                    // 5. Perintahkan secara manual untuk melakukan toggle (buka/tutup)
                    collapseInstance.toggle();
                }
            });

            // Bagian untuk animasi ikon panah (tetap diperlukan)
            const targetId = trigger.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                targetElement.addEventListener('show.bs.collapse', function() {
                    trigger.setAttribute('aria-expanded', 'true');
                    const icon = trigger.querySelector('.icon-toggle');
                    if (icon) {
                        icon.style.transform = 'rotate(90deg)';
                    }
                });

                targetElement.addEventListener('hide.bs.collapse', function() {
                    trigger.setAttribute('aria-expanded', 'false');
                    const icon = trigger.querySelector('.icon-toggle');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });

    });
</script>

      </div>
      <?php
      include "floating-menu.php";
      include "footer.php";
      ?>
    </div>


  </main>
  <?php
  include "js-include.php";
  ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>

  <script>
    // Fungsi untuk mencetak konten
    function printContent() {
      var content = document.getElementById("printable-content").innerHTML;
      var originalBody = document.body.innerHTML;
      document.body.innerHTML = content;
      window.print();
      document.body.innerHTML = originalBody;
    }

    // Menambahkan event listener untuk tombol "Print"
    document.querySelector(".btn-print").addEventListener("click", printContent);
  </script>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>