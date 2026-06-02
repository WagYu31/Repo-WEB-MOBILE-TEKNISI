<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today"; // Anda bisa sesuaikan ini
$role = $jabatan;

// Notifikasi (jika ada)
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $message = 'Terjadi kesalahan tidak diketahui.';
    if ($error_code == 1) $message = 'Gagal memproses data. Silakan coba lagi.';
    elseif ($error_code == 2) $message = 'Gagal. Data yang diperlukan tidak lengkap.';
    elseif ($error_code == 3) $message = 'Permintaan tidak valid. Silakan coba lagi.';
    echo "<script>alert('$message');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php include "head.php"; ?>
    <style>
        .table th,
        .table td {
            vertical-align: middle !important;
        }
        .table .customer-info h6 {
            font-size: 1rem;
            color: #344767;
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
                    <?php include 'nav-laporan.php'; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <h5 class="mb-3 mb-md-0 text-uppercase font-weight-bold">Laporan Kegiatan Tanpa Invoice</h5>
                                <form method="GET" action="" class="w-100 w-md-50">
                                    <div class="input-group">
                                        <input type="text" name="cari" class="form-control" placeholder="Cari nama customer..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>">
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
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 40%;">Customer</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 45%;">Teknisi Ditugaskan</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4" style="width: 15%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query Utama untuk mengambil kegiatan TANPA pelaksanaan atau yang di-set N/A
                                        $search = $_GET['cari'] ?? '';
                                        $sql_main = "SELECT 
                                            k.id, k.kode AS kode_transaksi, k.keterangan, k.created_at, k.kegiatan, k.jadwal,
                                            CASE 
                                                WHEN k.jadwal < '2025-08-01' THEN 0 
                                                ELSE k.paid 
                                            END AS paid,
                                            c.id AS id_cust, c.nama AS nama_cust,
                                            GROUP_CONCAT(DISTINCT t.nama_teknisi SEPARATOR ', ') as teknisi_list
                                        FROM kegiatan k
                                        LEFT JOIN customer c ON k.customer_id = c.id
                                        LEFT JOIN pelaksanaan_kegiatan p ON k.kode = p.kode
                                        LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
                                        WHERE k.status != 'waiting' 
                                          AND k.deleted_at IS NULL 
                                          AND k.invoice = 'n/a'";
                                        
                                        if (!empty($search)) {
                                            $sql_main .= " AND c.nama LIKE ?";
                                        }
                                        $sql_main .= " GROUP BY k.kode ORDER BY k.created_at DESC";
                                        
                                        $stmt_main = $conn->prepare($sql_main);
                                        if (!empty($search)) {
                                            $search_param = "%$search%";
                                            $stmt_main->bind_param("s", $search_param);
                                        }
                                        $stmt_main->execute();
                                        $result_main = $stmt_main->get_result();

                                        if ($result_main->num_rows > 0) {
                                            while ($row_main = $result_main->fetch_assoc()) {
                                                $kodeTransaksi = $row_main['kode_transaksi'];
                                                $idC = $row_main['id_cust'];
                                        ?>
                                                <tr style="border-bottom:1px solid #dee2e6;">
                                                    <td class="ps-4 customer-info text-wrap">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <span class="badge <?= (strtolower($row_main['kegiatan']) == 'survey') ? 'badge-warning' : 'badge-secondary'; ?> text-uppercase mt-1" style="font-size:10px !important;">
                                                                <?= htmlspecialchars($row_main['kegiatan']);?>
                                                            </span>
                                                            <a href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>" target="_blank">
                                                                <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($row_main['nama_cust']); ?></h6>
                                                            </a>
                                                        </div>
                                                        <p class="text-sm text-secondary mb-1">"<?= !empty($row_main['keterangan']) ? htmlspecialchars($row_main['keterangan']) : 'Tidak ada keterangan'; ?>"</p>
                                                        <p class="text-xs text-dark mb-0">Request dibuat: <?= date("d M Y, H:i", strtotime($row_main['created_at'])); ?></p>
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
                                                            <div class="me-3 w-45">
                                                                <p class="text-sm font-weight-bold mb-0"><?= htmlspecialchars($row_teknisi['nama_teknisi']); ?></p>
                                                            </div>
                                                            <div class="text-end me-4 w-40">
                                                                <p class="text-xs text-dark mb-0">Mulai: <?= $row_teknisi['waktu_mulai_pertama'] ? date("d/m H:i", strtotime($row_teknisi['waktu_mulai_pertama'])) : '-'; ?></p>
                                                                <p class="text-xs text-dark mb-0">Selesai: <?= $row_teknisi['waktu_selesai_terakhir'] ? date("d/m H:i", strtotime($row_teknisi['waktu_selesai_terakhir'])) : '-'; ?></p>
                                                            </div>
                                                            <div class="d-flex justify-content-between w-15">
                                                                <div>Rp </div>
                                                                <div><?= htmlspecialchars($row_main['paid']); ?></div>
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
                                                    
                                                    <td class="text-center pe-4">
                                                        <a class="btn btn-outline-warning btn-sm mb-0 p-1 px-2 me-1 btn-sm mb-0" href="proses_no_fee.php?kode_transaksi=<?= $kodeTransaksi; ?>" style="font-size:11px !important;" onclick="return confirm('Anda yakin ingin menandai membatalkan fee ini?')">
                                                            Fee Batal
                                                        </a>
                                                        <a href="proses_set_tidak_valid.php?kode=<?= $kodeTransaksi; ?>" class="btn btn-outline-danger btn-sm mb-0 p-1 px-2 me-1" style="font-size:11px !important;" onclick="return confirm('Anda yakin ingin menandai kegiatan ini sebagai Tidak Valid?')">
                                                            Tidak Valid
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center py-5'>Tidak ada data kegiatan yang belum dikerjakan.</td></tr>";
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
    
    <?php include "js-include.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>