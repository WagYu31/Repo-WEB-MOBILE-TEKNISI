<?php
include "../conn.php";
include "../session.php";
include "../get-user-data.php";
$pageNow = "Task";
$currentPage = "Task";
$jenis = $_GET['jenis'] ?? null;

// Helper Functions (tidak diubah)
if (!function_exists('shortenTechnicianName')) {
    function shortenTechnicianName($fullName) {
        $muhammadVariants = ['Muhammad', 'Mohammed', 'Mohammad', 'Muhammed', 'Mohamed', 'Mohamad', 'Muhamad', 'Muhamed', 'Mohamud', 'Mohummad', 'Mohummed'];
        $words = explode(" ", $fullName);
        if (in_array($words[0], $muhammadVariants)) $words[0] = "M.";
        $shortenedName = implode(" ", $words);
        if (strlen($shortenedName) > 20) {
            $lastWordIndex = count($words) - 1;
            if ($lastWordIndex > 0) {
                $words[$lastWordIndex] = strtoupper($words[$lastWordIndex][0]) . '.';
                $shortenedName = implode(" ", $words);
            }
        }
        return $shortenedName;
    }
}
if (!function_exists('getInitials')) {
    function getInitials($fullName) {
        $words = explode(" ", $fullName);
        $initials = "";
        foreach ($words as $word) $initials .= strtoupper($word[0] ?? '');
        return $initials;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Daftar Kegiatan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    
    <style>
        body { background-color: #f8f9fa; }
        .card-header h6 { font-weight: 700; }
        .lunas-background {
            position: relative;
            background-color: #e6f9f0; /* Warna hijau lembut untuk lunas */
        }
        .lunas-background::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background-image: url('assets/img/lunas.png'); /* Pastikan path gambar ini benar */
            background-size: 60%;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
        }

        /* ✅ STYLE BARU: Untuk search bar yang sticky */
        .search-bar-container {
            background-color: #f8f9fa; /* Samakan dengan background body */
            z-index: 1030; /* Pastikan di atas elemen lain */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="bg-light">
    <main class="main-content">
        <?php // include "nav-top.php"; ?>

        <div class="sticky-top search-bar-container">
            <div class="container">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari kegiatan apa saja...">
                </div>
            </div>
        </div>


        <div class="container py-4">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="btn-group w-100" role="group">
                        <a href="?jenis=survey" class="btn <?= $jenis == 'survey' ? 'btn-primary' : 'btn-outline-primary'; ?>">Survey</a>
                        <a href="?jenis=service" class="btn <?= $jenis == 'service' ? 'btn-primary' : 'btn-outline-primary'; ?>">Service</a>
                        <a href="?jenis=pasang%20baru" class="btn <?= $jenis == 'pasang baru' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pasang Baru</a>
                    </div>
                </div>
            </div>

            <div id="kegiatanList">
                <?php
                // --- Logika Fetch Data (Sama, tidak diubah) ---
                $sql_kegiatan = "SELECT 
                                    k.*, c.nama AS nama_customer, c.telp AS cust_nomor, c.alamat,
                                    inv.no_invoice, inv.nominal_invoice
                                FROM kegiatan k
                                LEFT JOIN customer c ON k.customer_id = c.id
                                LEFT JOIN (
                                    SELECT kode, no_invoice, nominal_invoice 
                                    FROM pendapatan_kegiatan 
                                    WHERE deleted_at IS NULL GROUP BY kode
                                ) inv ON k.kode = inv.kode
                                WHERE k.status != 'waiting' AND k.deleted_at IS NULL";
                if ($jenis !== null) {
                    $sql_kegiatan .= " AND k.kegiatan = '" . mysqli_real_escape_string($conn, $jenis) . "'";
                }
                $sql_kegiatan .= " ORDER BY k.jadwal DESC";

                $result_kegiatan = mysqli_query($conn, $sql_kegiatan);
                $groupedData = [];
                if (mysqli_num_rows($result_kegiatan) > 0) {
                    while ($row = mysqli_fetch_assoc($result_kegiatan)) {
                        $groupedData[$row['kode']][] = $row;
                    }
                } else {
                    echo "<div class='alert alert-warning text-center'>Tidak ada kegiatan yang ditemukan.</div>";
                }

                // --- Loop untuk menampilkan data dalam bentuk KARTU yang diperbarui ---
                foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
                    $latest_kegiatan = $kegiatan_group[0];
                    $lunas_class = (!empty($latest_kegiatan['lunas']) && $latest_kegiatan['lunas'] != '0000-00-00') ? 'lunas-background' : '';
                ?>
                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 text-primary"><?= htmlspecialchars($latest_kegiatan['nama_customer']); ?></h6>
                                <p class="text-sm text-muted mb-0"><?= ucwords($latest_kegiatan['kegiatan']); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill"><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])); ?></span>
                                <p class="text-sm mb-0"><?= date("H:i", strtotime($latest_kegiatan['jadwal'])); ?> WIB</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 <?= $lunas_class ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Invoice</h6>
                                    <small class="text-muted">Request oleh: <?= getInitials($latest_kegiatan['request']) ?></small>
                                </div>
                                <?php if (!empty($latest_kegiatan['no_invoice'])) : ?>
                                    <p class="mb-1"><strong><?= htmlspecialchars($latest_kegiatan['no_invoice']); ?></strong> - <strong class="text-success">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.'); ?></strong></p>
                                <?php else: ?>
                                    <p class="mb-1 text-danger">Belum Ada Invoice</p>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item px-0">
                                <h6 class="mb-2">Teknisi Terlibat</h6>
                                <?php
                                $sqlTeknisi = "SELECT tk.nama_teknisi, pk.status as status_pelaksanaan FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id LEFT JOIN pelaksanaan_kegiatan pk ON tk.kegiatan_id = pk.kegiatan_id AND tk.teknisi_id = pk.teknisi_id WHERE k.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id ORDER BY k.id DESC";
                                $stmt = $conn->prepare($sqlTeknisi);
                                $stmt->bind_param("s", $kodeTransaksi);
                                $stmt->execute();
                                $resultTeknisi = $stmt->get_result();
                                if($resultTeknisi->num_rows > 0) {
                                    while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
                                        $statusPelaksanaan = $rowTeknisi['status_pelaksanaan'];
                                        $statusClass = 'secondary'; $statusText = 'Dijadwalkan';
                                        if ($statusPelaksanaan == 'selesai') { $statusClass = 'success'; $statusText = 'Selesai'; } 
                                        elseif ($statusPelaksanaan == 'berjalan') { $statusClass = 'info'; $statusText = 'Dikerjakan'; } 
                                        elseif ($statusPelaksanaan == 'Lanjut Nanti') { $statusClass = 'warning'; $statusText = 'Lanjut Nanti'; }
                                ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <p class="mb-0 small"><?= shortenTechnicianName(htmlspecialchars($rowTeknisi['nama_teknisi'])) ?></p>
                                    <span class="badge text-bg-<?= $statusClass ?> rounded-pill"><?= $statusText ?></span>
                                </div>
                                <?php }
                                } else { echo "<p class='text-xs text-secondary mb-0'>Teknisi belum ditugaskan.</p>"; }
                                $stmt->close(); ?>
                            </li>
                            <li class="list-group-item px-0">
                                <h6 class="mb-1">Alamat</h6>
                                <p class="mb-0 text-muted small text-wrap"><?= htmlspecialchars($latest_kegiatan['alamat']); ?></p>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a class="btn btn-outline-dark w-100" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>">
                            <i class="bi bi-eye-fill me-2"></i> Lihat Detail Lengkap
                        </a>
                    </div>
                </div>
                <?php } ?>
                 <div id="noResultsMessage" class="alert alert-warning text-center" style="display: none;">
                    Pencarian tidak menemukan hasil.
                </div>
            </div>
        </div>
        
        <?php include "bottom-navbar.php"; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            let visibleCards = 0;

            // Loop melalui setiap kartu kegiatan
            $('#kegiatanList .card').each(function() {
                let cardText = $(this).text().toLowerCase();

                // Cek apakah teks di dalam kartu mengandung kata kunci pencarian
                if (cardText.includes(searchTerm)) {
                    $(this).slideDown('fast'); // Tampilkan jika cocok
                    visibleCards++;
                } else {
                    $(this).slideUp('fast'); // Sembunyikan jika tidak cocok
                }
            });

            // Tampilkan pesan jika tidak ada kartu yang cocok
            if (visibleCards === 0) {
                $('#noResultsMessage').show();
            } else {
                $('#noResultsMessage').hide();
            }
        });
    });
    </script>
</body>
</html>