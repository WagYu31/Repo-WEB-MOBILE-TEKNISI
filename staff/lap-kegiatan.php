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
    <style>
        .table th, .table td { vertical-align: middle !important; }
        .table .customer-info h6 { font-size: 0.9rem; color: #1e293b; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; border-radius: 10px 10px 0 0 !important; }
        .card-header h5 { font-size: 0.95rem; color: #1e293b; letter-spacing: 0.02em; }
        thead th { background: #f8fafc !important; color: #64748b !important; font-size: 10.5px !important; letter-spacing: 0.06em; padding: 10px 16px !important; border-bottom: 2px solid #e2e8f0 !important; }
        tbody tr { transition: background 0.15s; border-bottom: 1px solid #f1f5f9 !important; }
        tbody tr:hover { background: #f8fafc !important; }
        .technician-item { border-bottom: 1px solid #f1f5f9; padding: 8px 0; }
        .technician-item:last-child { border-bottom: none; }
        .search-box { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 14px; font-size: 13px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
        .search-box:focus { border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.1); outline: none; }
        .btn-search { background: #1e293b; border: none; border-radius: 8px; padding: 10px 16px; }
        .btn-search:hover { background: #334155; }
        .btn-aksi { font-size: 11px; padding: 5px 12px; border-radius: 6px; font-weight: 600; border: 1px solid transparent; transition: all 0.2s; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-input-inv { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .btn-input-inv:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
        .btn-no-inv { background: #fef3c7; color: #d97706; border-color: #fde68a; }
        .btn-no-inv:hover { background: #d97706; color: #fff; border-color: #d97706; }
        .btn-tidak-valid { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .btn-tidak-valid:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        .modal-xl { max-width: 80%; }
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
                    <?php include 'nav-laporan.php'; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3 px-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Kegiatan</h5>
                                <form method="GET" action="" class="d-flex gap-2" style="max-width:380px;width:100%;">
                                    <input type="text" name="cari" class="search-box flex-grow-1" placeholder="Cari customer / kode..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
                                    <button class="btn btn-search mb-0 text-white" type="submit"><i class="material-icons" style="font-size:16px;vertical-align:middle;">search</i></button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="table-layout:fixed;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 30%;">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 40%;">Teknisi & Absensi</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4" style="width: 30%;">Aksi</th>
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
                                                     AND k.deleted_at IS NULL AND p.kode IS NOT NULL
                                                     AND EXISTS (
                                                         SELECT 1 FROM pelaksanaan_kegiatan px
                                                         WHERE px.kode = k.kode AND px.deleted_at IS NULL
                                                         AND px.status NOT IN ('Lanjut Nanti', 'Lanjutan', 'berjalan', 'dijadwalkan')
                                                     )";

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
                                                <tr>
                                                    <td class="ps-4 customer-info text-wrap">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span style="font-size:9px;padding:3px 8px;border-radius:4px;font-weight:700;letter-spacing:0.04em;<?= (strtolower($row_main['kegiatan']) == 'survey') ? 'background:#fef3c7;color:#92400e;' : 'background:#e0e7ff;color:#3730a3;'; ?>">
                                                                <?= strtoupper(htmlspecialchars($row_main['kegiatan']));?>
                                                            </span>
                                                        </div>
                                                        <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank" style="text-decoration:none;color:#1e293b;">
                                                            <h6 class="font-weight-bold mb-1" style="font-size:0.9rem;"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                        </a>
                                                        <p class="mb-1" style="font-size:12px;color:#64748b;font-style:italic;">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="mb-0" style="font-size:11px;color:#94a3b8;">Request dibuat: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
                                                    </td>

                                                    <td class="technician-list">
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
                                                            <div>
                                                                <p class="mb-0" style="font-size:13px;font-weight:600;color:#1e293b;"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></p>
                                                            </div>
                                                            <div class="text-end" style="font-size:11px;color:#64748b;">
                                                                <p class="mb-0">Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?></p>
                                                                <p class="mb-0">Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?></p>
                                                            </div>
                                                        </div>
                                                        <?php
                                                            }
                                                        } else {
                                                            echo "<p style='font-size:11px;color:#94a3b8;margin:0;'>Data teknisi tidak ditemukan.</p>";
                                                        }
                                                        $stmt_teknisi->close();
                                                        ?>
                                                    </td>

                                                    <td class="text-center pe-4">
                                                        <button class="btn-aksi btn-input-inv me-1 detailBtn" data-bs-toggle="modal" data-bs-target="#detailModal" data-kode="<?= $kodeTransaksi; ?>">
                                                            Input Invoice
                                                        </button>
                                                        <a href="proses_set_no_invoice.php?kode=<?= $kodeTransaksi; ?>" class="btn-aksi btn-no-inv me-1" onclick="return confirm('Tandai kegiatan ini Tidak memiliki Invoice?')">
                                                            No Invoice
                                                        </a>
                                                        <a href="proses_set_tidak_valid.php?kode=<?= $kodeTransaksi; ?>" class="btn-aksi btn-tidak-valid" onclick="return confirm('Tandai kegiatan ini sebagai Tidak Valid?')">
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