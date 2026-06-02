<?php
include "../conn.php";
include "../session.php";
include "../get-user-data.php";
$pageNow = "Jadwal";
$jenis = $_GET['jenis'] ?? null; // Untuk filter: survey, service, dll.

// Fungsi pembantu (Sama seperti halaman task)
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
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Jadwal Mendatang</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body { background-color: #f8f9fa; }
        .sticky-top {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            z-index: 1030;
        }
    </style>
</head>
<body class="bg-light">
    <main class="main-content mb-5">
        <div class="sticky-top py-2">
            <div class="container">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari jadwal customer, teknisi, alamat...">
                </div>
            </div>
        </div>

        <div class="container py-4">
            <div class="row mb-3">
                <div class="col-12 d-flex align-items-center justify-content-center mb-3">
                    <a href="lanjut-nanti.php" class="btn btn-warning w-100">Kegiatan Lanjut Nanti</a>
                </div>
                <div class="col-12">
                    <h4 class="mb-1">🗓️ Jadwal Hari Ini & Mendatang</h4>
                    <p class="text-muted">Menampilkan semua kegiatan yang sudah terjadwal.</p>
                    <div class="btn-group w-100" role="group">
                        <a href="?jenis=survey" class="btn <?= $jenis == 'survey' ? 'btn-primary' : 'btn-outline-primary'; ?>">Survey</a>
                        <a href="?jenis=service" class="btn <?= $jenis == 'service' ? 'btn-primary' : 'btn-outline-primary'; ?>">Service</a>
                        <a href="?jenis=pasang%20baru" class="btn <?= $jenis == 'pasang baru' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pasang Baru</a>
                    </div>
                </div>
            </div>

            <div id="jadwalList">
                <?php
                // --- PERBEDAAN UTAMA ADA DI KUERI SQL INI ---
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
                                WHERE 
                                    k.status != 'waiting' 
                                    AND k.deleted_at IS NULL
                                    -- Filter untuk menampilkan kegiatan HARI INI dan SETERUSNYA
                                    AND DATE(k.jadwal) >= CURDATE()";
                
                if ($jenis !== null) {
                    $sql_kegiatan .= " AND k.kegiatan = '" . mysqli_real_escape_string($conn, $jenis) . "'";
                }
                // Urutkan dari yang paling dekat (ASCENDING)
                $sql_kegiatan .= " ORDER BY k.jadwal ASC";

                $result_kegiatan = mysqli_query($conn, $sql_kegiatan);
                $groupedData = [];
                if (mysqli_num_rows($result_kegiatan) > 0) {
                    while ($row = mysqli_fetch_assoc($result_kegiatan)) {
                        $groupedData[$row['kode']][] = $row;
                    }
                } else {
                    echo "<div class='alert alert-success text-center'>Tidak ada jadwal mendatang yang ditemukan.</div>";
                }

                // --- Loop untuk menampilkan data dalam bentuk KARTU ---
                foreach ($groupedData as $kodeTransaksi => $kegiatan_group) {
                    $latest_kegiatan = $kegiatan_group[0];
                    $is_today = (date('Y-m-d', strtotime($latest_kegiatan['jadwal'])) == date('Y-m-d'));
                ?>
                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 text-primary"><?= htmlspecialchars($latest_kegiatan['nama_customer']); ?></h6>
                                <p class="text-sm text-muted mb-0"><?= ucwords($latest_kegiatan['kegiatan']); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $is_today ? 'bg-primary' : 'bg-secondary-subtle text-secondary-emphasis'; ?> rounded-pill">
                                    <i class="fa-regular fa-calendar me-1"></i><?= date("d M Y", strtotime($latest_kegiatan['jadwal'])); ?>
                                </span>
                                <p class="text-sm mb-0 mt-1"><i class="fa-regular fa-clock"></i> <?= date("H:i", strtotime($latest_kegiatan['jadwal'])); ?> WIB</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <h6 class="mb-1 small">Invoice</h6>
                            <small class="text-muted">Req: <?= getInitials($latest_kegiatan['request']) ?></small>
                        </div>
                        <?php if (!empty($latest_kegiatan['no_invoice'])) : ?>
                            <p class="mb-2"><strong><?= htmlspecialchars($latest_kegiatan['no_invoice']); ?></strong> - <strong class="text-success">Rp <?= number_format($latest_kegiatan['nominal_invoice'], 0, ',', '.'); ?></strong></p>
                        <?php else: ?>
                            <p class="mb-2 text-danger small">Belum Ada Invoice</p>
                        <?php endif; ?>
                        
                        <h6 class="mb-2 small">Teknisi Bertugas</h6>
                        <?php
                        $sqlTeknisi = "SELECT tk.nama_teknisi FROM team_kegiatan tk JOIN kegiatan k ON tk.kegiatan_id = k.id WHERE k.kode = ? AND tk.deleted_at IS NULL GROUP BY tk.teknisi_id";
                        $stmt = $conn->prepare($sqlTeknisi);
                        $stmt->bind_param("s", $kodeTransaksi);
                        $stmt->execute();
                        $resultTeknisi = $stmt->get_result();
                        if($resultTeknisi->num_rows > 0) {
                            while ($rowTeknisi = $resultTeknisi->fetch_assoc()) {
                        ?>
                        <span class="badge bg-info-subtle text-info-emphasis mb-1 me-1">
                            <i class="fa-solid fa-user-gear me-1"></i><?= shortenTechnicianName(htmlspecialchars($rowTeknisi['nama_teknisi'])) ?>
                        </span>
                        <?php }
                        } else { echo "<p class='text-xs text-secondary mb-0'>Teknisi belum ditugaskan.</p>"; }
                        $stmt->close(); ?>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a class="btn btn-outline-dark w-100" href="view-kegiatan.php?kode_transaksi=<?= $kodeTransaksi; ?>">
                            <i class="fa-solid fa-eye me-2"></i>Lihat Detail
                        </a>
                    </div>
                </div>
                <?php } ?>
                 <div id="noResultsMessage" class="alert alert-warning text-center" style="display: none;">
                    Pencarian tidak menemukan jadwal.
                </div>
            </div>
        </div>
        
        <?php include "bottom-navbar.php"; ?>
    </main>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            let visibleCards = 0;

            $('#jadwalList .card').each(function() {
                let cardText = $(this).text().toLowerCase();

                if (cardText.includes(searchTerm)) {
                    $(this).slideDown('fast');
                    visibleCards++;
                } else {
                    $(this).slideUp('fast');
                }
            });

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