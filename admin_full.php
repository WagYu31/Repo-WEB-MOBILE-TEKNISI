<?php
$conn = new mysqli("localhost", "sql_center_id_giti_com", "4577131c1cfbc", "sql_center_id_giti_com");
$res = $conn->query("SELECT * FROM hasil_asesmen ORDER BY tanggal_isi DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Full Data Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .score-badge { font-size: 0.85rem; padding: 5px 10px; border-radius: 20px; font-weight: 600; }
        .ans-pill { 
            display: inline-block; 
            width: 24px; 
            height: 24px; 
            line-height: 24px; 
            text-align: center; 
            border-radius: 4px; 
            background: #e2e8f0; 
            font-size: 0.75rem; 
            margin: 1px;
            font-weight: bold;
            color: #475569;
        }
        .ans-header { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .text-wrap-custom { max-width: 250px; font-size: 0.85rem; line-height: 1.4; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0">Laporan Detil Asesmen Teknisi</h2>
            <p class="text-muted">Data lengkap termasuk skor kategori dan pilihan jawaban individu.</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">Cetak Laporan</button>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Info Responden</th>
                        <th class="py-3 text-center">Skor Kategori</th>
                        <th class="py-3">Rincian Jawaban (Q1 - Q20)</th>
                        <th class="py-3">Aspirasi & Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['nama_teknisi']); ?></div>
                            <div class="small text-muted"><?php echo date('d M Y, H:i', strtotime($row['tanggal_isi'])); ?></div>
                            <div class="mt-2">
                                <span class="badge bg-dark"><?php echo $row['kategori_akhir']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between small">
                                    <span>Hunter:</span> <span class="fw-bold"><?php echo $row['skor_hunter']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span>Safe:</span> <span class="fw-bold"><?php echo $row['skor_safe']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span>Strategist:</span> <span class="fw-bold"><?php echo $row['skor_strategist']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="width: 280px;">
                                <div class="ans-header mb-1 text-center">Skala Jawaban 1 - 5</div>
                                <?php for($i=1; $i<=20; $i++): 
                                    $val = $row['q'.$i];
                                    $bg = "";
                                    if($val >= 4) $bg = "background: #dcfce7; color: #166534;";
                                    if($val <= 2) $bg = "background: #fee2e2; color: #991b1b;";
                                ?>
                                    <span class="ans-pill" style="<?php echo $bg; ?>" title="Pertanyaan <?php echo $i; ?>">
                                        <?php echo $val; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td>
                            <div class="text-wrap-custom mb-2">
                                <strong>Evaluasi:</strong> <span class="text-secondary"><?php echo htmlspecialchars($row['evaluasi_sistem']); ?></span>
                            </div>
                            <div class="text-wrap-custom mb-2">
                                <strong>Harapan:</strong> <span class="text-secondary"><?php echo htmlspecialchars($row['harapan_sistem']); ?></span>
                            </div>
                            <?php if(!empty($row['feedback_hasil'])): ?>
                            <div class="text-wrap-custom p-2 bg-warning bg-opacity-10 border-start border-warning border-3">
                                <strong>Bantahan/Kesan:</strong> <br>
                                <small class="fst-italic"><?php echo htmlspecialchars($row['feedback_hasil']); ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>