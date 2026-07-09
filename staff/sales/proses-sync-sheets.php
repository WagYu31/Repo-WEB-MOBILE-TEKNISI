<?php
header('Content-Type: application/json');
include "../conn.php";
include "../session.php";
include_once "../include/format_tanggal.php";
require_once "GoogleSheetsClient.php";

$userRole = strtolower($_SESSION['jabatan'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'super admin' && $userRole !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Admin yang dapat melakukan sinkronisasi.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$spreadsheetId = trim($_POST['spreadsheet_id'] ?? '');
$sheetName     = trim($_POST['sheet_name'] ?? 'Sheet1');
$idSales       = intval($_POST['id_sales'] ?? 0);
$bulan         = trim($_POST['bulan'] ?? '');

if (empty($spreadsheetId)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Spreadsheet / URL harus diisi.']);
    exit;
}

// Extract Spreadsheet ID if a full URL was provided
if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $spreadsheetId, $matches)) {
    $spreadsheetId = $matches[1];
}

if (empty($bulan)) {
    $bulan = date('Y-m');
}

// 1. Fetch Sales name
$salesName = "SEMUA SALES";
if ($idSales > 0) {
    $resSales = mysqli_query($conn, "SELECT nama FROM sales WHERE id = $idSales LIMIT 1");
    if ($resSales && $rSales = mysqli_fetch_assoc($resSales)) {
        $salesName = strtoupper($rSales['nama']);
    }
}

// 2. Fetch visited activities
$whereClauses = ["ks.deleted_at IS NULL", "tks.deleted_at IS NULL"];
if ($idSales > 0) {
    $whereClauses[] = "tks.id_sales = $idSales";
}
if (!empty($bulan)) {
    $whereClauses[] = "DATE_FORMAT(ks.jadwal, '%Y-%m') = '" . mysqli_real_escape_string($conn, $bulan) . "'";
}

$whereSql = implode(" AND ", $whereClauses);

$sql = "SELECT ks.id AS kegiatan_id, ks.kode AS kode_kunjungan, ks.jadwal AS tgl_visits, 
               sc.nama AS nama_cust, sc.kategori AS jenis_usaha, sc.telp_pribadi AS contact_person,
               sc.alamat AS alamat_cust, ks.alamat_lokasi AS alamat_kegiatan, sc.kota AS kota_cust,
               ps.catatan_visit, ps.keterangan AS ps_keterangan,
               ps.image_1, ps.image_2, ps.image_3, ps.image_4, ps.image_5,
               s.nama AS nama_sales
        FROM kegiatan_sales ks
        JOIN team_kegiatan_sales tks ON ks.id = tks.id_kegiatan_sales
        JOIN sales s ON tks.id_sales = s.id
        JOIN sales_customer sc ON ks.id_customer = sc.id
        LEFT JOIN pelaksanaan_sales ps ON (ps.kegiatan_id = ks.id AND ps.sales_id = tks.id_sales)
        WHERE $whereSql
        ORDER BY ks.jadwal ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data dari database: ' . mysqli_error($conn)]);
    exit;
}

$rows = [];
$no = 1;
$lokasiKunjungan = "SEMUA WILAYAH";
$firstCity = '';

while ($row = mysqli_fetch_assoc($result)) {
    // Determine the visit number (kunjungan ke-N)
    $kegiatanId = $row['kegiatan_id'];
    // Extract KJG-XXX from code (e.g. CUST-001/KJG-002 -> Kunjungan Kedua)
    $jenisKunjungan = "Kunjungan Pertama";
    if (preg_match('/KJG-(\d+)/', $row['kode_kunjungan'], $matchesKjg)) {
        $num = intval($matchesKjg[1]);
        if ($num == 1) {
            $jenisKunjungan = "Kunjungan Pertama";
        } elseif ($num == 2) {
            $jenisKunjungan = "Kunjungan Kedua";
        } elseif ($num == 3) {
            $jenisKunjungan = "Kunjungan Ketiga";
        } else {
            $jenisKunjungan = "Kunjungan Ke-" . $num;
        }
    }

    // Set first city visited for location header
    if (empty($firstCity) && !empty($row['kota_cust'])) {
        $firstCity = strtoupper($row['kota_cust']);
        $lokasiKunjungan = $firstCity;
    }

    // Format Hari & Tanggal
    $tglKunjungan = '-';
    if (!empty($row['tgl_visits']) && $row['tgl_visits'] !== '0000-00-00 00:00:00') {
        $tglKunjungan = formatTanggal('EEEE, dd MMMM yyyy', $row['tgl_visits']);
    }

    // Maps link / address (as a clean HYPERLINK formula)
    $rawAddress = !empty($row['alamat_kegiatan']) ? $row['alamat_kegiatan'] : (!empty($row['alamat_cust']) ? $row['alamat_cust'] : '');
    $tokoName = $row['nama_cust'];
    $cleanTokoName = str_replace('"', "'", $tokoName);
    if (!empty($rawAddress)) {
        $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . rawurlencode($rawAddress);
        $mapsLink = '=HYPERLINK("' . $mapsUrl . '", "📍 ' . $cleanTokoName . '")';
    } else {
        $mapsLink = '';
    }

    // Contact Person
    $cp = !empty($row['contact_person']) ? $row['contact_person'] : '';

    // Bukti Foto Sales (using HYPERLINK + IMAGE formula for cell thumbnail preview)
    $fotoLinks = [];
    $apiBase = "https://api-teknisi.id-giti.com/storage/image/";
    for ($i = 1; $i <= 5; $i++) {
        $imgCol = "image_$i";
        if (!empty($row[$imgCol])) {
            $fotoLinks[] = $apiBase . $row[$imgCol];
        }
    }
    $buktiFoto = '';
    if (!empty($fotoLinks)) {
        $firstFoto = $fotoLinks[0];
        $buktiFoto = '=HYPERLINK("' . $firstFoto . '", IMAGE("' . $firstFoto . '"))';
    }

    // Catatan
    $catatan = !empty($row['catatan_visit']) ? $row['catatan_visit'] : (!empty($row['ps_keterangan']) ? $row['ps_keterangan'] : '');

    // Format data row
    $rows[] = [
        $no++,                                          // NO
        $tglKunjungan,                                  // HARI & TANGGAL KUNJUNGAN
        $row['nama_cust'],                              // NAMA TOKO
        $mapsLink,                                      // LINK MAPS
        ucfirst($row['jenis_usaha']),                   // JENIS USAHA
        $cp,                                            // CONTACT PERSON
        $jenisKunjungan,                                // JENIS KUNJUNGAN
        $buktiFoto,                                     // BUKTI FOTO SALES
        $catatan,                                       // CATATAN KUNJUNGAN
        ''                                              // CHECK LAPORAN (diisi oleh checker)
    ];
}

try {
    $client = new GoogleSheetsClient(__DIR__ . '/../config/google-sheets-key.json');

    // 1. Update Header Info
    // B3: NAMA SALES, C3: [Sales Name]
    // B4: LOKASI KUNJUNGAN, C4: [Location]
    $client->updateValues($spreadsheetId, "{$sheetName}!C3:C4", [
        [$salesName],
        [$lokasiKunjungan]
    ]);

    // 2. Clear previous data rows from A8:J1000
    $client->clearValues($spreadsheetId, "{$sheetName}!A8:J1000");

    // 3. Write data rows starting from A8
    if (!empty($rows)) {
        $endRow = 8 + count($rows) - 1;
        $client->updateValues($spreadsheetId, "{$sheetName}!A8:J" . $endRow, $rows);

        try {
            // 4. Dynamically get Sheet ID for batch formatting
            $sheetId = $client->getSheetIdByName($spreadsheetId, $sheetName);

            // 5. Build batchUpdate requests to apply borders, vertical alignment, text wrapping, and custom horizontal alignments
            $borderStyle = [
                'style' => 'SOLID',
                'width' => 1,
                'color' => ['red' => 0.7, 'green' => 0.7, 'blue' => 0.7]
            ];

            $requests = [
                // Apply borders to table
                [
                    'updateBorders' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7, // Row 8 is index 7
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 10
                        ],
                        'top' => $borderStyle,
                        'bottom' => $borderStyle,
                        'left' => $borderStyle,
                        'right' => $borderStyle,
                        'innerHorizontal' => $borderStyle,
                        'innerVertical' => $borderStyle
                    ]
                ],
                // Apply text wrap and vertical alignment
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 10
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'wrapStrategy' => 'WRAP',
                                'verticalAlignment' => 'MIDDLE'
                            ]
                        ],
                        'fields' => 'userEnteredFormat.wrapStrategy,userEnteredFormat.verticalAlignment'
                    ]
                ],
                // Center align Columns A & B (NO, HARI & TANGGAL)
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 2
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'horizontalAlignment' => 'CENTER'
                            ]
                        ],
                        'fields' => 'userEnteredFormat.horizontalAlignment'
                    ]
                ],
                // Center align Column E (JENIS USAHA)
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 4,
                            'endColumnIndex' => 5
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'horizontalAlignment' => 'CENTER'
                            ]
                        ],
                        'fields' => 'userEnteredFormat.horizontalAlignment'
                    ]
                ],
                // Center align Column G (JENIS KUNJUNGAN)
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 6,
                            'endColumnIndex' => 7
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'horizontalAlignment' => 'CENTER'
                            ]
                        ],
                        'fields' => 'userEnteredFormat.horizontalAlignment'
                    ]
                ],
                // Center align Column H (BUKTI FOTO SALES)
                [
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 7,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => 7,
                            'endColumnIndex' => 8
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'horizontalAlignment' => 'CENTER'
                            ]
                        ],
                        'fields' => 'userEnteredFormat.horizontalAlignment'
                    ]
                ]
            ];

            $client->batchUpdate($spreadsheetId, $requests);
        } catch (Exception $errFormat) {
            // Log formatting error but do not fail the sync if only formatting fails
            error_log("Google Sheets formatting failed: " . $errFormat->getMessage());
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Berhasil mensinkronisasikan ' . count($rows) . ' data kunjungan ke Google Sheets!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal sinkronisasi Google Sheets: ' . $e->getMessage()
    ]);
}
