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
    echo "<script>alert('Berhasil melakukan perubahan invoice.');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .table th, .table td {
            vertical-align: middle !important;
        }
        .table .customer-info h6 {
            font-size: 1rem;
            color: #344767;
        }
        .table .technician-list .technician-item {
            border-bottom: 1px solid #f0f2f5;
        }
        .table .technician-list .technician-item:last-child {
            border-bottom: none;
        }
        .modal-xl {
            max-width: 80%;
        }
        @media (max-width: 767px) {
            .modal-xl {
                max-width: 95%;
            }
        }
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
                    <div class="btn-group w-100" role="group" aria-label="Laporan Navigation">
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
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Kegiatan</h5>
                                <form method="GET" action="" class="w-100 w-md-50">
                                    <div class="input-group">
                                        <input type="text" name="cari" class="form-control p-4" style="border-bottom:1px solid #adb5bd" placeholder="Cari berdasarkan nama customer..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                        <button class="btn btn-primary mb-0" type="submit"><i class="material-icons text-sm">search</i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 30%;">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 35%;">Teknisi & Absensi</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4" style="width: 15%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $search = $_GET['cari'] ?? '';
                                        $sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.keterangan, k.kegiatan, k.created_at, k.status AS status_kegiatan, c.id AS id_cust, c.nama AS nama_cust
                                                     FROM kegiatan k
                                                     LEFT JOIN customer c ON k.customer_id = c.id
                                                     LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
                                                     WHERE k.status != 'waiting' AND (k.paid IS NULL OR k.paid = '')
                                                     AND k.deleted_at IS NULL AND p.kode IS NOT NULL";

                                        if (!empty($search)) {
                                            $sql_main .= " AND (c.nama LIKE ? OR k.kode LIKE ? OR k.kegiatan LIKE ? OR k.keterangan LIKE ?)";
                                        }

                                        $sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";

                                        $stmt_main = $conn->prepare($sql_main);

                                        if (!empty($search)) {
                                            $search_param = "%$search%";
                                            // Mengikat parameter pencarian ke empat placeholder (?)
                                            $stmt_main->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
                                        }

                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();
                                        

                                        if ($result_main->num_rows > 0) {
                                            while ($row_main = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $idC = $row_main['id_cust'];
                                        ?>
                                                <tr style="border-bottom:1px solid #adb5bd">
                                                    <td class="ps-4 customer-info text-wrap w-50">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <span class="badge <?= (strtolower($row_main['kegiatan']) == 'survey') ? 'badge-warning' : 'badge-secondary'; ?> text-uppercase mt-1" style="font-size:10px !important;">
                                                                <?= htmlspecialchars($row_main['kegiatan']);?>
                                                            </span>
                                                            <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                                <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                            </a>
                                                        </div>
                                                        <p class="text-sm text-secondary mb-1">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="text-xs text-muted mb-0">Request dibuat: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
                                                    </td>

                                                    <td class="technician-list pe-5">
                                                        <?php
                                                        $sql_teknisi = "SELECT p.status, t.nama_teknisi,
                                                                        (SELECT MIN(waktu_mulai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_mulai_pertama,
                                                                        (SELECT MAX(waktu_selesai) FROM pelaksanaan_kegiatan WHERE teknisi_id = p.teknisi_id AND kode = p.kode) AS waktu_selesai_terakhir
                                                                    FROM pelaksanaan_kegiatan p
                                                                    JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                                                                    JOIN kegiatan k ON t.kegiatan_id = k.id
                                                                    WHERE p.kode = ? AND k.customer_id = ? AND p.deleted_at IS NULL
                                                                    GROUP BY p.teknisi_id";
                                                        
                                                        $stmt_teknisi = $conn->prepare($sql_teknisi);
                                                        $stmt_teknisi->bind_param("si", $kodeTransaksi, $idC);
                                                        $stmt_teknisi->execute();
                                                        $result_teknisi = $stmt_teknisi->get_result();

                                                        if($result_teknisi->num_rows > 0) {
                                                            while($row_teknisi = $result_teknisi->fetch_assoc()) {
                                                        ?>
                                                        <div class="d-flex justify-content-between align-items-center py-2 technician-item">
                                                            <div class="me-3">
                                                                <p class="text-sm font-weight-bold mb-0"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></p>
                                                            </div>
                                                            <div class="text-end">
                                                                <p class="text-xs text-dark mb-0">Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?></p>
                                                                <p class="text-xs text-dark mb-0">Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?></p>
                                                            </div>
                                                        </div>
                                                        <?php
                                                            }
                                                        } else {
                                                            echo "<p class='text-xs text-danger mb-0'>Data teknisi tidak ditemukan.</p>";
                                                        }
                                                        $stmt_teknisi->close();
                                                        ?>
                                                    </td>


                                                    <td class="text-center pe-4" style="font-size:11px !important;">
                                                        <button class="btn btn-outline-success btn-sm mb-0 detailBtn p-1 px-2 me-1" style="font-size:11px !important;" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?= $kodeTransaksi; ?>">
                                                            Input Invoice
                                                        </button>
                                                        <a href="proses_set_no_invoice.php?kode=<?= $kodeTransaksi; ?>" class="btn btn-outline-warning btn-sm mb-0 p-1 px-2 me-1" style="font-size:11px !important;" onclick="return confirm('Anda yakin ingin menandai kegiatan ini Tidak memiliki Invoice?')">
                                                                No Invoice
                                                            </a>
                                                        <a href="proses_set_tidak_valid.php?kode=<?= $kodeTransaksi; ?>" class="btn btn-outline-danger btn-sm mb-0 p-1 px-2 me-1" style="font-size:11px !important;" onclick="return confirm('Anda yakin ingin menandai kegiatan ini sebagai Tidak Valid?')">
                                                                Tidak Valid
                                                            </a>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
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
        
        <?php
// --- Blok Logika untuk Modal Pengumuman ---
$show_modal = false;
// Set zona waktu ke Jakarta
$timezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTime('now', $timezone);
// Modal akan berhenti muncul pada tanggal 5 Agustus 2025
$expiry_date = new DateTime('2025-08-05', $timezone);

// Hanya aktifkan modal jika hari ini sebelum tanggal kedaluwarsa
if ($today < $expiry_date) {
    $show_modal = true;
}

// Jika flag $show_modal aktif, maka render HTML dan JavaScript untuk modal
if ($show_modal) :
?>

<div class="modal fade" id="infoUpdateModal" tabindex="-1" aria-labelledby="infoUpdateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title text-white" id="infoUpdateModalLabel"><i class="material-icons opacity-10 me-2">campaign</i>Psstt... Ada yang Baru, lho!</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fs-6">
                <p class="text-center mb-3">✨✨✨</p>
                <p>Ehem! Tampilan halaman ini sekarang lebih <em>user friendly</em>, tapi tenang... cara kerjanya <strong>99% masih sama kok.</strong></p>
                <p>Yuk, budayakan <strong>kepo dan klik-klik mandiri</strong> dulu. Siapa tahu nemu harta karun! 😉</p>
                <hr>
                <p class="text-muted small">Kalau sudah keluar asap dari kepala, baru deh lambaikan tangan ke developer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Oke, Saya Mengerti!</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fungsi untuk mendapatkan tanggal hari ini dalam format YYYY-MM-DD
    const getTodayDate = () => {
        const d = new Date();
        // Menyesuaikan dengan zona waktu lokal untuk akurasi
        const offset = d.getTimezoneOffset();
        const adjustedDate = new Date(d.getTime() - (offset*60*1000));
        return adjustedDate.toISOString().split('T')[0];
    }
    
    const today = getTodayDate();
    const lastShown = localStorage.getItem('updateModalLastShown');

    // Jika modal belum pernah ditampilkan atau terakhir ditampilkan bukan hari ini
    if (lastShown !== today) {
        var myModal = new bootstrap.Modal(document.getElementById('infoUpdateModal'));
        myModal.show();

        // Saat modal ditutup, simpan tanggal hari ini ke localStorage
        document.getElementById('infoUpdateModal').addEventListener('hidden.bs.modal', function () {
            localStorage.setItem('updateModalLastShown', today);
        });
    }
});
</script>

<?php
endif; // Akhir dari blok if ($show_modal)
// --- Akhir Blok Logika Modal ---
?>
    </main>

    <div class="modal fade" style="z-index:99999;" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Rincian & Input Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dataDetailTek">
                    <div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php include "js-include.php"; ?>
    <script>
    $(document).ready(function() {
        $('.detailBtn').click(function() {
            var kode_transaksi = $(this).data('kode');
            var modalBody = $('#dataDetailTek');
            
            modalBody.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            $.ajax({
                url: 'get-detail-pekerjaan-new.php',
                type: 'POST',
                data: { kode_transaksi: kode_transaksi },
                success: function(response) {
                    modalBody.html(response);
                },
                error: function(xhr, status, error) {
                    modalBody.html('<div class="alert alert-danger text-white">Gagal memuat detail pekerjaan. Silakan coba lagi.</div>');
                    console.error("AJAX Error: " + status + " - " + error);
                }
            });
        });
    });
    </script>
</body>
</html>