<?php
include "conn.php";

// Inisialisasi variabel agar tidak error jika tidak ada POST
$kode_transaksi = '';
$result = null;

if (isset($_POST['kode_transaksi'])) {
    $kode_transaksi = $_POST['kode_transaksi'];

    // [PENTING] Menggunakan Prepared Statements untuk mencegah SQL Injection
$query = "
    WITH RankedKegiatan AS (
        SELECT 
            pk.id, 
            t.nama AS nama_teknisi, 
            pk.waktu_mulai, 
            pk.waktu_selesai, 
            k.kegiatan, 
            k.jadwal, 
            k.invoice, 
            k.garansi, 
            k.keterangan_garansi, -- [PERBAIKAN] Tanda '=' yang salah telah dihapus dari sini
            ROW_NUMBER() OVER(PARTITION BY pk.teknisi_id, DATE(pk.waktu_mulai) ORDER BY pk.waktu_mulai ASC) as rn
        FROM 
            pelaksanaan_kegiatan pk
        JOIN 
            teknisi t ON pk.teknisi_id = t.id
        JOIN 
            kegiatan k ON pk.kegiatan_id = k.id
        WHERE 
            pk.kode = ?
    )
    SELECT 
        id, 
        nama_teknisi, 
        waktu_mulai, 
        waktu_selesai, 
        kegiatan, 
        jadwal, 
        invoice, 
        garansi, 
        keterangan_garansi
    FROM 
        RankedKegiatan
    WHERE 
        rn = 1
    ORDER BY 
        nama_teknisi ASC, waktu_mulai ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $kode_transaksi);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Query Error: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function formatRupiah(input) {
            var nominal = input.value.replace(/\D/g, "");
            nominal = nominal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            input.value = "Rp " + nominal;
        }
    </script>
</head>

<body>
    <?php
    // ========================================================================
    // [MODIFIKASI 1] Ambil nilai invoice sebelum form ditampilkan
    // ========================================================================
    $default_invoice = ''; // Siapkan variabel dengan nilai kosong
    if ($result && mysqli_num_rows($result) > 0) {
        // Ambil baris pertama untuk mendapatkan nilai default
        $first_row = mysqli_fetch_assoc($result);
        $default_invoice = $first_row['invoice'];
        $default_garansi = $first_row['garansi'];
        $defaut_ket_garansi = $first_row['keterangan_garansi'];

        // Kembalikan pointer data ke awal agar loop while di bawah tetap utuh
        mysqli_data_seek($result, 0);
    }
    ?>

    <div class="container mt-2">
        <form action="submit_invoice.php" method="post">
            <span>Garansi</span> : <b><?php echo htmlspecialchars($default_garansi ?? ''); ?></b><br>
            <span>Keterangan Garansi</span> : <b><?php echo htmlspecialchars($default_ket_garansi ?? ''); ?></b>
            <input type="hidden" name="kode_transaksi" value="<?php echo htmlspecialchars($kode_transaksi); ?>">

            <div class="mb-3 mt-3">
                <input type="text" class="form-control" placeholder="Kode Invoice" id="kodeInvoice" name="kode_invoice" value="<?php echo htmlspecialchars((strtolower($default_invoice) == 'no') ? '' : $default_invoice); ?>" required>
            </div>

            <div class="mb-3">
                <input type="text" class="form-control" id="nominalInvoice" placeholder="Nominal Invoice" name="nominal_invoice" oninput="formatRupiah(this)" required>
            </div>

            <div class="mb-3">
                <label>Tanggal Invoice</label>
                <input type="date" class="form-control" id="tanggalInvoice" name="tanggal_invoice" required>
            </div>
            
            
            <div class="mb-3">
                <label>Tanggal Lunas</label>
                <input type="date" class="form-control" id="tanggalLunas" name="tanggal_lunas" required>
            </div>
            

            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Pilih</th>
                                <th>Nama Teknisi</th>
                                <th>Kegiatan</th>
                                <th>Request</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $checkboxDisabled = false;
                                $waktu_mulai_valid = ($row['waktu_mulai'] != NULL && $row['waktu_mulai'] != '0000-00-00 00:00:00');
                                $jadwal_valid = ($row['jadwal'] != NULL && $row['jadwal'] != '0000-00-00 00:00:00');
                                $merah = "";

                                if ($waktu_mulai_valid && $jadwal_valid) {
                                    try {
                                        $waktu_mulai_dt = new DateTime($row['waktu_mulai']);
                                        $jadwal_dt = new DateTime($row['jadwal']);

                                        if ($waktu_mulai_dt > $jadwal_dt) {
                                            $diff_minutes = ($waktu_mulai_dt->getTimestamp() - $jadwal_dt->getTimestamp()) / 60;
                                            if ($diff_minutes > 60) {
                                                $checkboxDisabled = true;
                                                $merah = "color:red;";
                                            }
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error parsing date: " . $e->getMessage() . " for row id " . $row['id']);
                                    }
                                }
                                ?>
                                <tr>
                                    <td class="text-center" style="<?php echo $merah; ?>">
                                        <input type="checkbox" name="selected_kegiatan[]" value="<?php echo $row['id'].'|'.$row['kegiatan']; ?>">
                                    </td>
                                    <td style="<?php echo $merah; ?>"><?php echo htmlspecialchars($row['nama_teknisi']); ?></td>
                                    <td style="text-transform:capitalize;<?php echo $merah; ?>"><?php echo htmlspecialchars($row['kegiatan']); ?></td>
                                    <td style="<?php echo $merah; ?>">
                                        <?php
                                        echo ($row['jadwal'] == NULL || $row['jadwal'] == '0000-00-00 00:00:00')
                                            ? '<span style="color:red;">Tidak Absen</span>'
                                            : date("d-m-Y H:i", strtotime($row['jadwal']));
                                        ?>
                                    </td>
                                    <td style="<?php echo $merah; ?>">
                                        <?php
                                        echo ($row['waktu_mulai'] == NULL || $row['waktu_mulai'] == '0000-00-00 00:00:00')
                                            ? '<span style="color:red;">Tidak Absen</span>'
                                            : date("d-m-Y H:i", strtotime($row['waktu_mulai']));
                                        ?>
                                    </td>
                                    <td style="<?php echo $merah; ?>">
                                        <?php
                                        echo ($row['waktu_selesai'] == NULL || $row['waktu_selesai'] == '0000-00-00 00:00:00')
                                            ? '<span style="color:red;">Tidak Absen</span>'
                                            : date("d-m-Y H:i", strtotime($row['waktu_selesai']));
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (isset($_POST['kode_transaksi'])): ?>
                <p class="text-danger">Tidak ada pelaksanaan kegiatan yang ditemukan untuk kode transaksi ini.</p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>