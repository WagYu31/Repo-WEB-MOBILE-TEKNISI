<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Laporan";
$currentPage = "Today";
$role = $jabatan;
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
      <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
      <title>
        LOEWIX | <?php echo $pageNow;?>
      </title>
      <!-- Fonts and icons -->
      <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
      <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
      <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
      <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <style>
          @media print {
                .no-print {
                    display: none !important;
                }
            }
      </style>
</head>

<body>
    <main class="main-content position-relative max-height-vh-100 h-100">
        <!-- Navbar -->
        <?php
        setlocale(LC_TIME, 'id_ID'); // Set locale ke Indonesia
        $todayDate = strftime('%d %B %Y');
        ?>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row mb-4 mt-n3">
                <?php
                $current_date = isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun']) ? $_GET['cariBulanTahun'] : date("Y-m");
                $search = $_GET['cari'] ?? '';
                ?>
                
                <div class="col-lg-12">
                    <div class="card h-100 py-3" style="border-top-left-radius: 0;">
                        <div class="card-header pb-0 p-3">
                            <div class="row">
                                <div class="col-12 d-md-flex d-block align-items-center">
                                    <div class="col-md-6 col-12 mb-2">
                                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan : <?php echo $current_date;?></h6>
                                    </div>
                                    <div class="col-md-6 col-12 no-print">
                                        <form method="GET" action="" class="col-12 col-md-12 mb-2 d-flex align-items-center justify-content-center flex-row">
                                            <input type="month" class="form-control border p-2 bg-outline-info w-70 no-print" name="cariBulanTahun" value="<?= $current_date ?>">
                                            <button class="btn btn-primary w-30 mt-0 ms-2 no-print">Cari</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                
                        <?php
                        $sql = "SELECT k.*, k.kode AS kode_transaksi, t.nama_teknisi, c.id AS id_cust, c.nama AS nama_cust, i.no_invoice AS invoice, i.nominal_invoice
                                FROM kegiatan k
                                LEFT JOIN team_kegiatan t ON k.id = t.kegiatan_id
                                LEFT JOIN customer c ON k.customer_id = c.id
                                LEFT JOIN pendapatan_kegiatan i ON k.id = i.kegiatan_id
                                INNER JOIN pelaksanaan_kegiatan pk ON k.kode = pk.kode
                                WHERE k.status != 'waiting' AND (k.paid IS NULL OR k.paid = '') AND k.deleted_at IS NULL
                                AND DATE_FORMAT(pk.waktu_selesai, '%Y-%m') = ?
                                GROUP BY k.kode ORDER BY k.jadwal DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $current_date);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        ?>
                
                        <div class="card-body pb-0 p-0">
                            <div class="d-flex justify-content-end m-3 no-print">
                                <button class="btn btn-primary no-print me-2" onclick="window.print();">Print</button>
                                <a href="export-laporan.php?cariBulanTahun=<?= $current_date ?>" class="btn btn-success no-print">Export ke Excel</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-dark text-xxs font-weight-bolder opacity-7" style="border-bottom:1px solid #333333; border-top:1px solid #333333;">Customer</th>
                                            <th class="text-uppercase text-dark text-xxs font-weight-bolder opacity-7" style="border-bottom:1px solid #333333; border-top:1px solid #333333;">Status</th>
                                            <th class="text-uppercase text-dark text-xxs font-weight-bolder opacity-7" style="border-bottom:1px solid #333333; border-top:1px solid #333333;">Teknisi</th>
                                            <th class="text-uppercase text-dark text-xxs font-weight-bolder opacity-7" style="border-bottom:1px solid #333333; border-top:1px solid #333333;">Mulai</th>
                                            <th class="text-uppercase text-dark text-xxs font-weight-bolder opacity-7" style="border-bottom:1px solid #333333; border-top:1px solid #333333;">Selesai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <?php
                                            $sqlLapTek = "SELECT p.*, t.*, p.kode AS kode_pelaksanaan, c.nama AS nama_customer,
                                                          MIN(p.waktu_mulai) AS waktu_mulai_pertama, MAX(p.waktu_selesai) AS waktu_selesai_terakhir
                                                          FROM pelaksanaan_kegiatan p
                                                          JOIN team_kegiatan t ON t.teknisi_id = p.teknisi_id
                                                          JOIN kegiatan k ON t.kegiatan_id = k.id
                                                          JOIN customer c ON k.customer_id = c.id
                                                          WHERE p.kode = ? AND k.customer_id = ?
                                                          GROUP BY p.teknisi_id";
                                            $stmtLapTek = $conn->prepare($sqlLapTek);
                                            $stmtLapTek->bind_param("si", $row['kode_transaksi'], $row['id_cust']);
                                            $stmtLapTek->execute();
                                            $resLapTek = $stmtLapTek->get_result();
                                            $rows = $resLapTek->fetch_all(MYSQLI_ASSOC);
                                            ?>
                                            <?php if (!empty($rows)): ?>
                                                <?php foreach ($rows as $index => $rowLT): ?>
                                                    <tr style="border-bottom:1px solid #333333;">
                                                        <?php if ($index === 0): ?>
                                                            <td rowspan="<?= count($rows) ?>"><h6 class="mb-0 text-sm ms-4"><?= $rowLT['nama_customer'] ?></h6></td>
                                                        <?php endif; ?>
                                                        <td><span class="mb-0 text-sm"><?= $rowLT['status'] ?></span></td>
                                                        <td><span class="mb-0 text-sm"><?= $rowLT['nama_teknisi'] ?></span></td>
                                                        <td>
                                                            <span class="mb-0 text-sm">
                                                                <?= ($rowLT['waktu_mulai_pertama'] && $rowLT['waktu_mulai_pertama'] != '0000-00-00 00:00:00') ? date("d-m-Y | H:i", strtotime($rowLT['waktu_mulai_pertama'])) : '-' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="mb-0 text-sm">
                                                                <?= ($rowLT['waktu_selesai_terakhir'] && $rowLT['waktu_selesai_terakhir'] != '0000-00-00 00:00:00') ? date("d-m-Y | H:i", strtotime($rowLT['waktu_selesai_terakhir'])) : '-' ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5">Tidak ada kegiatan.</td></tr>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </main>
    <?php
    include "js-include.php";
    ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>