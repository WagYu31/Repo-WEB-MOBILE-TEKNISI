<?php
include "conn.php";
include "session.php";

if (!isset($_GET['bulan']) || !isset($_GET['tahun']) || !is_numeric($_GET['bulan']) || !is_numeric($_GET['tahun'])) {
    die("Error: Bulan dan Tahun tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
    $daftar_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayDate = date('d') . ' ' . $daftar_bulan[(int)date('m')] . ' ' . date('Y');
$nama_bulan = formatTanggal('MMMM', date('Y-m-d', mktime(0, 0, 0, $bulan, 1)));
$filename = "Laporan Kegiatan - " . $nama_bulan . " " . $tahun . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Kegiatan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<style>
    body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
    table { border-collapse: collapse; }
    th { background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; padding: 6px 10px; border: 0.5pt solid #000000; }
    td { vertical-align: middle; padding: 5px 8px; border: 0.5pt solid #000000; }
    .num { mso-number-format: "\#\,\#\#0"; text-align: right; }
    .text { mso-number-format: "\@"; }
    .date { mso-number-format: "dd\/mm\/yyyy"; text-align: center; }
    .time { mso-number-format: "hh\:mm"; text-align: center; }
    .center { text-align: center; }
</style>';
echo '</head>';
echo '<body>';
echo '<h3>Laporan Kegiatan Lengkap - Periode: ' . $nama_bulan . ' ' . $tahun . '</h3>';
echo '<table border="1">';
echo '<thead>
        <tr>
            <th rowspan="2">Customer</th>
            <th rowspan="2">Request</th>
            <th rowspan="2">No. Invoice</th>
            <th rowspan="2">Nominal Invoice</th>
            <th rowspan="2">Status Bayar</th>
            <th colspan="5">Rincian Teknisi</th>
        </tr>
        <tr>
            <th>Teknisi</th>
            <th>Target Tercapai</th>
            <th>Tanggal Kerja</th>
            <th>Absen Mulai</th>
            <th>Absen Selesai</th>
        </tr>
      </thead>';
echo '<tbody>';

$sql_main = "SELECT k.id, k.kode AS kode_transaksi, k.created_at, k.lunas, k.paid, c.nama AS nama_cust
            FROM kegiatan k LEFT JOIN customer c ON k.customer_id = c.id
            WHERE (
                (EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode AND MONTH(pk.tanggal) = ? AND YEAR(pk.tanggal) = ? AND pk.deleted_at IS NULL))
                OR
                (NOT EXISTS (SELECT 1 FROM pendapatan_kegiatan pk WHERE pk.kode = k.kode AND pk.deleted_at IS NULL) AND MONTH(k.created_at) = ? AND YEAR(k.created_at) = ?)
            )
            AND k.deleted_at IS NULL
            GROUP BY k.kode ORDER BY k.created_at ASC";

$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param("iiii", $bulan, $tahun, $bulan, $tahun);
$stmt_main->execute();
$result_main = $stmt_main->get_result();

if ($result_main->num_rows > 0) {
    while ($row_main = $result_main->fetch_assoc()) {
        $kodeTransaksi = $row_main['kode_transaksi'];
        $is_manual_fee = is_numeric($row_main['paid']);
        
        $sql_invoice = "SELECT no_invoice, nominal_invoice FROM pendapatan_kegiatan WHERE kode = ? LIMIT 1";
        $stmt_invoice = $conn->prepare($sql_invoice);
        $stmt_invoice->bind_param("s", $kodeTransaksi);
        $stmt_invoice->execute();
        $invoice_data = $stmt_invoice->get_result()->fetch_assoc();
        $stmt_invoice->close();

        $sql_count_active = "SELECT COUNT(DISTINCT teknisi_id) as total_aktif 
                            FROM pelaksanaan_kegiatan 
                            WHERE kode = ? AND waktu_mulai IS NOT NULL";
        $stmt_count = $conn->prepare($sql_count_active);
        $stmt_count->bind_param("s", $kodeTransaksi);
        $stmt_count->execute();
        $res_count = $stmt_count->get_result()->fetch_assoc();
        $jumlah_teknisi_aktif = $res_count['total_aktif'] ?? 0;
        $stmt_count->close();

        $sql_team = "SELECT t.id, t.nama AS nama_teknisi 
                     FROM team_kegiatan tk 
                     JOIN teknisi t ON tk.teknisi_id = t.id 
                     JOIN kegiatan k ON tk.kegiatan_id = k.id 
                     WHERE k.kode = ? 
                     GROUP BY t.id";
        $stmt_team = $conn->prepare($sql_team);
        $stmt_team->bind_param("s", $kodeTransaksi);
        $stmt_team->execute();
        $res_team = $stmt_team->get_result();

        $grouped_data = [];
        $total_rows_for_job = 0;

        while ($t = $res_team->fetch_assoc()) {
            $tid = $t['id'];
            
            $sql_pendapatan = "SELECT SUM(pendapatan) as total FROM pendapatan_kegiatan WHERE kode = ? AND teknisi_id = ?";
            $stmt_p = $conn->prepare($sql_pendapatan);
            $stmt_p->bind_param("si", $kodeTransaksi, $tid);
            $stmt_p->execute();
            $pendapatan_db = $stmt_p->get_result()->fetch_assoc()['total'] ?? 0;
            $stmt_p->close();

            $sql_absensi = "SELECT DATE(waktu_mulai) as tgl, MIN(waktu_mulai) as mulai, MAX(waktu_selesai) as selesai 
                            FROM pelaksanaan_kegiatan 
                            WHERE kode = ? AND teknisi_id = ? AND waktu_mulai IS NOT NULL 
                            GROUP BY tgl ORDER BY tgl ASC";
            $stmt_a = $conn->prepare($sql_absensi);
            $stmt_a->bind_param("si", $kodeTransaksi, $tid);
            $stmt_a->execute();
            $res_a = $stmt_a->get_result();
            
            $absensi_list = [];
            while($a = $res_a->fetch_assoc()) {
                $absensi_list[] = $a;
            }
            $stmt_a->close();

            $final_pendapatan = $pendapatan_db;
            if ($pendapatan_db == 0 && $is_manual_fee) {
                if (count($absensi_list) > 0 && $jumlah_teknisi_aktif > 0) {
                    $final_pendapatan = 30000 / $jumlah_teknisi_aktif;
                } else {
                    $final_pendapatan = 0;
                }
            }

            $rowspan_tech = max(1, count($absensi_list));
            $grouped_data[] = [
                'nama' => $t['nama_teknisi'],
                'pendapatan' => $final_pendapatan,
                'absensi' => $absensi_list,
                'rowspan_tech' => $rowspan_tech
            ];
            $total_rows_for_job += $rowspan_tech;
        }
        $stmt_team->close();

        // Nominal Invoice Calculation
        $nom_invoice_td = '';
        if ($invoice_data && isset($invoice_data['nominal_invoice']) && is_numeric($invoice_data['nominal_invoice'])) {
            $nom_invoice_td = '<td class="num">' . round((float)$invoice_data['nominal_invoice']) . '</td>';
        } elseif ($is_manual_fee) {
            $nom_invoice_td = '<td class="num">30000</td>';
        } else {
            $nom_invoice_td = '<td class="text center">-</td>';
        }

        if (empty($grouped_data)) {
            echo '<tr>';
            echo '<td class="text">' . htmlspecialchars($row_main['nama_cust']) . '</td>';
            echo '<td class="date">' . date("d/m/Y", strtotime($row_main['created_at'])) . '</td>';
            echo '<td class="text">' . ($invoice_data['no_invoice'] ?? ($is_manual_fee ? 'Tidak ada Invoice' : '-')) . '</td>';
            echo $nom_invoice_td;
            echo '<td class="text">' . ((!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'Lunas ' . date("d/m/Y", strtotime($row_main['lunas'])) : 'Belum Lunas') . '</td>';
            echo '<td colspan="5" class="text center">Tidak ada data teknisi</td>';
            echo '</tr>';
        } else {
            $first_row_job = true;
            foreach ($grouped_data as $g) {
                $first_row_tech = true;
                $loop_count = count($g['absensi']) > 0 ? count($g['absensi']) : 1;

                for ($i = 0; $i < $loop_count; $i++) {
                    echo '<tr>';
                    if ($first_row_job) {
                        echo '<td class="text" rowspan="' . $total_rows_for_job . '">' . htmlspecialchars($row_main['nama_cust']) . '</td>';
                        echo '<td class="date" rowspan="' . $total_rows_for_job . '">' . date("d/m/Y", strtotime($row_main['created_at'])) . '</td>';
                        echo '<td class="text" rowspan="' . $total_rows_for_job . '">' . ($invoice_data['no_invoice'] ?? ($is_manual_fee ? 'Tidak ada Invoice' : '-')) . '</td>';
                        
                        if ($invoice_data && isset($invoice_data['nominal_invoice']) && is_numeric($invoice_data['nominal_invoice'])) {
                            echo '<td class="num" rowspan="' . $total_rows_for_job . '">' . round((float)$invoice_data['nominal_invoice']) . '</td>';
                        } elseif ($is_manual_fee) {
                            echo '<td class="num" rowspan="' . $total_rows_for_job . '">30000</td>';
                        } else {
                            echo '<td class="text center" rowspan="' . $total_rows_for_job . '">-</td>';
                        }

                        echo '<td class="text" rowspan="' . $total_rows_for_job . '">' . ((!empty($row_main['lunas']) && $row_main['lunas'] != '0000-00-00') ? 'Lunas ' . date("d/m/Y", strtotime($row_main['lunas'])) : 'Belum Lunas') . '</td>';
                        $first_row_job = false;
                    }

                    if ($first_row_tech) {
                        echo '<td class="text" rowspan="' . $g['rowspan_tech'] . '">' . htmlspecialchars($g['nama']) . '</td>';
                        echo '<td class="num" rowspan="' . $g['rowspan_tech'] . '">' . round((float)$g['pendapatan']) . '</td>';
                        $first_row_tech = false;
                    }

                    if (!empty($g['absensi'])) {
                        $abs = $g['absensi'][$i];
                        echo '<td class="date">' . date("d/m/Y", strtotime($abs['tgl'] ?? '')) . '</td>';
                        echo '<td class="time">' . ($abs['mulai'] ? date("H:i", strtotime($abs['mulai'])) : '-') . '</td>';
                        echo '<td class="time">' . ($abs['selesai'] ? date("H:i", strtotime($abs['selesai'])) : '-') . '</td>';
                    } else {
                        echo '<td colspan="3" class="text center" style="color:red;">Tidak ada data pelaksanaan</td>';
                    }
                    echo '</tr>';
                }
            }
        }
    }
}

echo '</tbody></table></body></html>';
$stmt_main->close();
$conn->close();
exit;
?>