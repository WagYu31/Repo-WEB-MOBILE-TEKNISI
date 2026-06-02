<?php
$current_date = isset($_GET['cariBulanTahun']) && !empty($_GET['cariBulanTahun']) ? $_GET['cariBulanTahun'] : date("Y-m");
$search = $_GET['cari'] ?? '';
?>

<div class="col-lg-12">
    <div class="card h-100 py-3" style="border-top-left-radius: 0;">
        <div class="card-header pb-0 p-3 no-print">
            <div class="row">
                <div class="col-12 d-md-flex d-block align-items-center">
                    <div class="col-md-6 col-12">
                        <h6 class="mb-0 mx-1 ms-2 lead font-weight-bold text-uppercase">Laporan Kegiatan</h6>
                    </div>
                    <div class="col-md-6 col-12 no-print">
                        <form method="GET" action="" class="col-12 col-md-12 d-flex align-items-center justify-content-center flex-row">
                            <input type="month" class="form-control border p-2 bg-outline-info w-70 no-print" name="cariBulanTahun" value="<?= $current_date ?>">
                            <button class="btn bg-gradient-info w-30 mt-3 ms-2 no-print">Cari</button>
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
            <div class="d-flex justify-content-end m-3">
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
                                        <td><span class="mb-0 text-sm"><?= date("d-m-Y | H:i", strtotime($rowLT['waktu_mulai_pertama'])) ?></span></td>
                                        <td><span class="mb-0 text-sm"><?= date("d-m-Y | H:i", strtotime($rowLT['waktu_selesai_terakhir'])) ?></span></td>
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