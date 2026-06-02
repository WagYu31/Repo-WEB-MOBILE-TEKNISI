<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today";
$role = $jabatan;

// --- Notifikasi Alert ---
if (isset($_GET['error'])) {
    $error_messages = [
        1 => 'Gagal memproses data. Silakan coba lagi.',
        2 => 'Gagal. Data yang diperlukan tidak lengkap.',
        3 => 'Permintaan tidak valid. Silakan coba lagi.'
    ];
    $error_code = $_GET['error'];
    $message = $error_messages[$error_code] ?? 'Terjadi kesalahan tidak diketahui.';
    echo "<script>alert('$message');</script>";
} elseif (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        echo "<script>alert('Berhasil menambahkan invoice.');</script>";
    } elseif ($_GET['success'] == 2) {
        echo "<script>alert('Status pembayaran berhasil diperbarui.');</script>";
    }
}

// --- Logika untuk Tab Aktif ---
$active_tab = $_GET['tab'] ?? 'belum_lunas'; // Default ke 'belum_lunas'
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .table th, .table td { vertical-align: middle !important; }
        .table .customer-info h6 { font-size: 1rem; color: #344767; }
        .technician-list .technician-item { border-bottom: 1px solid #f0f2f5; }
        .technician-list .technician-item:last-child { border-bottom: none; }
        .invoice-summary { background: rgba(255,255,255,0.85); border-radius: .5rem; height: 100%; }
        .lunas-background { background-image: url('assets/img/lunas.png'); background-size: 35%; background-position: center; background-repeat: no-repeat; }
        .modal-xl { max-width: 80%; }
        .nav-tabs .nav-link.active {
            background-color: #596CFF !important;
            color: #fff !important;
            border-bottom: 2px solid #596CFF;
        }
        @media (max-width: 767px) { .modal-xl { max-width: 95%; } }
        <?php include "css/floating-menu2.css"; ?>
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
                <div class="col-12">
                    <div class="btn-group w-100" role="group">
                        <a href="lap-kegiatan.php" class="btn btn-dark m-0">Belum Input Invoice</a>
                        <a href="lap-kegiatan-selesai.php" class="btn btn-dark m-0">Selesai</a>
                        <a href="lap-noinv.php" class="btn bg-dark m-0 text-light font-weight-bold">No Invoice</a>
                        <a href="lap-loss.php" class="btn bg-gradient-danger m-0">Tidak Selesai</a>
                        <a href="laporan-bulanan.php" class="btn btn-secondary m-0" target="_blank"><i class="material-icons text-sm">print</i></a>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Kegiatan Selesai</h5>
                                <form method="GET" action="" class="w-100 w-md-50">
                                    <div class="input-group">
                                        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab); ?>">
                                        <input type="text" name="cari" class="form-control p-4" style="border-bottom:1px solid #adb5bd" placeholder="Cari nama customer atau no. invoice..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                        <button class="btn btn-primary mb-0" type="submit"><i class="material-icons text-sm">search</i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-tabs px-3" id="laporanTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $active_tab == 'belum_lunas' ? 'active' : '' ?>" href="?tab=belum_lunas">Belum Lunas</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $active_tab == 'lunas' ? 'active' : '' ?>" href="?tab=lunas">Lunas</a>
                                </li>
                            </ul>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 w-35">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 w-25">Invoice</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 w-30">Teknisi & Absensi</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query Utama dengan filter tab
                                        $search = $_GET['cari'] ?? '';
                                        $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.lunas, c.id AS id_cust, c.nama AS nama_cust
                                                    FROM kegiatan k
                                                    LEFT JOIN customer c ON k.customer_id = c.id
                                                    LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
                                                    LEFT JOIN pendapatan_kegiatan inv ON k.kode = inv.kode AND inv.deleted_at IS NULL
                                                    WHERE k.status != 'waiting' AND k.paid = 'yes' AND k.deleted_at IS NULL AND p.kode IS NOT NULL";

                                        if ($active_tab == 'belum_lunas') {
                                            $sql_main .= " AND (k.lunas IS NULL OR k.lunas = '0000-00-00')";
                                        } else { // tab 'lunas'
                                            $sql_main .= " AND (k.lunas IS NOT NULL AND k.lunas != '0000-00-00')";
                                        }

                                        if (!empty($search)) {
                                            $sql_main .= " AND (c.nama LIKE ? OR inv.no_invoice LIKE ?)";
                                        }
                                        $sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";

                                        $stmt_main = $conn->prepare($sql_main);
                                        if (!empty($search)) {
                                            $search_param = "%$search%";
                                            $stmt_main->bind_param("ss", $search_param, $search_param);
                                        }
                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();

                                        if ($result_main->num_rows > 0) {
                                            while ($row_main = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $idC = $row_main['id_cust'];
                                                $lunas_class = (!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                                        ?>
                                                <tr style="border-bottom:1px solid #adb5bd">
                                                    <td class="ps-4 customer-info text-wrap">
                                                        <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                            <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                        </a>
                                                        <p class="text-sm text-secondary mb-1">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="text-xs text-muted mb-0">Request: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
                                                    </td>

                                                    <td class="text-wrap <?= $lunas_class ?>">
                                                        <?php
                                                        $sql_invoice = "SELECT no_invoice, tanggal, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? AND deleted_at IS NULL LIMIT 1";
                                                        $stmt_invoice = $conn->prepare($sql_invoice);
                                                        $stmt_invoice->bind_param("s", $kodeTransaksi);
                                                        $stmt_invoice->execute();
                                                        $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
                                                        $stmt_invoice->close();
                                                        ?>
                                                        <div class="p-2 invoice-summary">
                                                            <?php if ($invoice_data) : ?>
                                                                <div class="d-flex justify-content-between">
                                                                    <div>
                                                                        <span class="text-xs text-uppercase font-weight-bold">No. Invoice:</span>
                                                                        <p class="text-sm font-weight-bolder mb-0"><?= htmlspecialchars($invoice_data['no_invoice']); ?></p>
                                                                        <p class="text-xxs text-muted mb-0"><?= date("d M Y", strtotime($invoice_data['tanggal'])); ?></p>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <span class="text-xs text-uppercase font-weight-bold">Nominal:</span>
                                                                        <p class="text-sm font-weight-bolder text-success mb-0">Rp <?= number_format($invoice_data['nominal_invoice'], 0, ',', '.'); ?></p>
                                                                        <p class="text-xxs mb-0 <?= $lunas_class ? 'text-primary' : 'text-danger font-weight-bold'; ?>">
                                                                            <?= $lunas_class ? date("d M Y", strtotime($row_main['lunas'])) : 'Belum Lunas'; ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            <?php else : ?>
                                                                <p class="text-xs text-center text-danger font-weight-bold mb-0">Tidak ada Invoice</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>

                                                    <td class="technician-list pe-4">
                                                        <?php
                                                        $sql_teknisi = "SELECT t.nama_teknisi,
                                                                        (SELECT MIN(waktu_mulai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_mulai_pertama,
                                                                        (SELECT MAX(waktu_selesai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_selesai_terakhir
                                                                    FROM pelaksanaan_kegiatan p JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id JOIN kegiatan k ON t.kegiatan_id = k.id
                                                                    WHERE p.kode = ? AND k.customer_id = ? AND p.deleted_at IS NULL GROUP BY p.teknisi_id";
                                                        $stmt_teknisi = $conn->prepare($sql_teknisi);
                                                        $stmt_teknisi->bind_param("si", $kodeTransaksi, $idC);
                                                        $stmt_teknisi->execute();
                                                        $result_teknisi = $stmt_teknisi->get_result();
                                                        if($result_teknisi->num_rows > 0) {
                                                            while($row_teknisi = $result_teknisi->fetch_assoc()) {
                                                        ?>
                                                        <div class="d-flex justify-content-between align-items-center py-2 technician-item">
                                                            <p class="text-sm font-weight-bold mb-0"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></p>
                                                            <div class="text-end">
                                                                <p class="text-xs text-dark mb-0">Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?></p>
                                                                <p class="text-xs text-dark mb-0">Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?></p>
                                                            </div>
                                                        </div>
                                                        <?php }
                                                        } else { echo "<p class='text-xs text-danger text-center mb-0'>Data teknisi tidak ditemukan.</p>"; }
                                                        $stmt_teknisi->close();
                                                        ?>
                                                    </td>

                                                    <td class="text-center pe-4">
                                                        <?php 
                                                        // if ($active_tab == 'belum_lunas') :
                                                        ?>
                                                            <button class="btn btn-outline-success btn-sm mb-0 lunasBtn" data-bs-toggle="modal" data-bs-target="#lunasModal" data-kode="<?= $kodeTransaksi; ?>">
                                                                💸 Bayar
                                                            </button>
                                                            <a class="btn bg-gradient-danger btn-sm text-white mb-0" href="reset_invoice.php?kode=<?= $kodeTransaksi; ?>">
                                                                ⭮
                                                            </a>
                                                            <button class="btn bg-gradient-danger btn-sm text-white mb-0 btn-hapus-laporan" 
                                                                data-kode="<?= $kodeTransaksi; ?>" 
                                                                data-customer="<?= htmlspecialchars($row_main['nama_cust']); ?>">
                                                                <i class="material-icons" style="font-size:14px;vertical-align:middle;">delete</i>
                                                            </button>
                                                        <?php 
                                                        // else: 
                                                        ?>
                                                            <!--<button class="btn btn-outline-success btn-sm mb-0 lunasBtn" data-bs-toggle="modal" data-bs-target="#lunasModal" data-kode="<?= $kodeTransaksi; ?>">-->
                                                            <!--    💸 Bayar-->
                                                            <!--</button>-->
                                                            <!-- <a class="btn bg-gradient-danger btn-sm text-white mb-0" href="reset_invoice.php?kode=<?= $kodeTransaksi; ?>">-->
                                                            <!--    ⭮ -->
                                                            <!--</a>-->
                                                        <?php 
                                                        // endif; 
                                                        ?>
                                                    </td>

                                                </tr>
                                        <?php }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center py-5'>Tidak ada data laporan yang ditemukan.</td></tr>";
                                        }
                                        $stmt_main->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <div class="modal fade" style="z-index:99999;" id="lunasModal" tabindex="-1" aria-labelledby="lunasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="lunasForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="lunasModalLabel">Tandai Pelunasan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan menandai transaksi ini sebagai lunas. Silakan masukkan tanggal pembayaran.</p>
                        <input type="hidden" id="kode_transaksi_lunas" name="kode_transaksi">
                        <div class="form-group">
                            <label for="tanggal_lunas">Tanggal Pelunasan</label>
                            <input type="date" class="form-control p-2" style="border:1px solid #adb5bd" id="tanggal_lunas" name="tanggal_lunas" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn bg-gradient-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
    $(document).ready(function() {
        // --- Handler untuk Modal Pelunasan ---
        $('.lunasBtn').click(function() {
            var kode_transaksi = $(this).data('kode');
            $('#kode_transaksi_lunas').val(kode_transaksi);
            // Set tanggal default ke hari ini
            document.getElementById('tanggal_lunas').valueAsDate = new Date();
        });

        $('#lunasForm').submit(function(e) {
            e.preventDefault(); // Mencegah form submit biasa
            
            var formData = $(this).serialize(); // Mengambil data dari form
            
            $.ajax({
                url: 'proses_update_lunas.php', // File PHP untuk memproses update
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.trim() === 'success') {
                        // Redirect ke tab 'lunas' dengan notifikasi sukses
                        window.location.href = 'lap-kegiatan-selesai.php?tab=lunas&success=2';
                    } else {
                        alert('Gagal memperbarui status: ' + response);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
                }
            });
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.querySelectorAll('.btn-hapus-laporan').forEach(btn => {
        btn.addEventListener('click', function() {
            const kode = this.dataset.kode;
            const customer = this.dataset.customer;
            Swal.fire({
                title: 'Hapus Kegiatan?',
                html: `<p style="margin:0;color:#666;">Kode: <strong>${kode}</strong></p>
                       <p style="margin:0;color:#666;">Customer: <strong>${customer}</strong></p>
                       <p style="margin-top:10px;color:#e74c3c;font-size:13px;">Data yang dihapus tidak dapat dikembalikan!</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;margin-right:4px;">delete</i> Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete-kegiatan.php?kode=' + kode;
                }
            });
        });
    });
    </script>
</body>
</html>