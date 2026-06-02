<?php
$conn = new mysqli("localhost", "sql_center_id_giti_com", "4577131c1cfbc", "sql_center_id_giti_com");
$show_result = false;
$last_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_asesmen'])) {
    $nama = !empty($_POST['nama']) ? $conn->real_escape_string($_POST['nama']) : "Responden";
    $essay1 = $conn->real_escape_string($_POST['essay1']);
    $essay2 = $conn->real_escape_string($_POST['essay2']);
    $qs = [];
    for($i=1; $i<=20; $i++) { $qs[$i] = (int)$_POST['q'.$i]; }

    $s_hunter = $qs[2]+$qs[4]+$qs[6]+$qs[8]+$qs[12]+$qs[14]+$qs[15]+$qs[18]+$qs[20];
    $s_safe = $qs[1]+$qs[3]+$qs[7]+$qs[9]+$qs[10]+$qs[13];
    $s_strat = $qs[5]+$qs[11]+$qs[16]+$qs[17]+$qs[19];

    if (($s_hunter/9) >= ($s_safe/6) && ($s_hunter/9) >= ($s_strat/5)) {
        $kategori = "Tipe Petarung (Independent)";
        $analisa = "Anda adalah orang yang sangat mandiri dan punya semangat tinggi kalau melihat hasil kerja nyata. Anda tipe yang nggak suka dibatasi dan bakal kasih performa 200% kalau ngerasa 'hasil keringat' itu sebanding sama yang didapet. Anda punya rasa tanggung jawab besar karena pengen ngebuktiin kalau skill Anda bisa diandalin tanpa harus disuruh-suruh.";
    } elseif (($s_strat/5) >= ($s_hunter/9) && ($s_strat/5) >= ($s_safe/6)) {
        $kategori = "Tipe Perencana (Strategist)";
        $analisa = "Anda tipe orang yang mikirin masa depan. Anda suka keseimbangan antara kerja keras dan hasil yang stabil. Anda tanggung jawab banget dalam ngejaga kualitas kerja biar nama baik Anda tetep bagus di mata kantor dan konsumen. Anda lebih suka sistem yang adil, di mana pas lagi ramai Anda dapet lebih, tapi pas lagi sepi dapur tetep aman.";
    } else {
        $kategori = "Tipe Setia (Reliable)";
        $analisa = "Anda adalah pilar tim yang sangat bisa diandalin buat jaga standar kualitas. Anda kerja paling bagus kalau ngerasa tenang dan dapet dukungan penuh dari kantor. Rasa tanggung jawab Anda muncul dari rasa pengen jaga kepercayaan perusahaan. Anda lebih milih fokus ke teknis pemasangan daripada pusing mikirin target yang berubah-ubah.";
    }

    $sql = "INSERT INTO hasil_asesmen (nama_teknisi, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, q11, q12, q13, q14, q15, q16, q17, q18, q19, q20, skor_hunter, skor_safe, skor_strategist, kategori_akhir, evaluasi_sistem, harapan_sistem) 
            VALUES ('$nama', {$qs[1]}, {$qs[2]}, {$qs[3]}, {$qs[4]}, {$qs[5]}, {$qs[6]}, {$qs[7]}, {$qs[8]}, {$qs[9]}, {$qs[10]}, {$qs[11]}, {$qs[12]}, {$qs[13]}, {$qs[14]}, {$qs[15]}, {$qs[16]}, {$qs[17]}, {$qs[18]}, {$qs[19]}, {$qs[20]}, $s_hunter, $s_safe, $s_strat, '$kategori', '$essay1', '$essay2')";
    
    if ($conn->query($sql)) { $last_id = $conn->insert_id; $show_result = true; }
}

// Handling feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $fid = (int)$_POST['last_id'];
    $fback = $conn->real_escape_string($_POST['feedback_hasil']);
    $conn->query("UPDATE hasil_asesmen SET feedback_hasil = '$fback' WHERE id = $fid");
    echo "<script>alert('Terima kasih masukkannya!'); window.location='index.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Kepuasan Teknisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f9; font-family: sans-serif; }
        .hero { background: #2c3e50; color: white; padding: 40px 15px; text-align: center; border-radius: 0 0 20px 20px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .q-card { padding: 20px; border-bottom: 1px solid #eee; }
        .form-check-input { width: 1.5em; height: 1.5em; margin-top: 0; }
        .btn-primary { background: #2c3e50; border: none; padding: 12px; font-weight: bold; }
        /* Gaya untuk Pilihan Jawaban yang Lebih Rapi */
        .likert-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 15px;
        }
        
        .likert-item {
            flex: 1;
            text-align: center;
            padding: 5px;
        }
        
        .likert-item label {
            display: block;
            cursor: pointer;
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 8px;
            line-height: 1.2;
        }
        
        .form-check-input {
            width: 1.8em !important;
            height: 1.8em !important;
            float: none !important;
            margin: 0 auto !important;
            cursor: pointer;
        }
        
        /* Biar gak terlalu dempet di mobile */
        @media (max-width: 576px) {
            .likert-item label {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>

<?php if (!$show_result): ?>
<div class="hero">
    <h3 class="fw-bold">Ngobrol Bareng Manajemen</h3>
    <p class="small">Kami pengen denger pendapat temen-temen biar ke depannya kerja makin enak.</p>
</div>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="alert alert-info small text-center">
                <strong>Catatan:</strong> Nggak usah tegang, ini bukan ujian. Isi aja sejujur-jujurnya sesuai apa yang dirasain pas kerja di lapangan. Nama boleh diisi, boleh nggak (kosongin aja).
            </div>

            <form method="POST">
                <div class="card p-3">
                    <label class="form-label fw-bold small">Nama / Panggilan (Bebas, boleh kosong)</label>
                    <input type="text" name="nama" class="form-control" placeholder="Tulis di sini kalau mau...">
                </div>

                <div class="card overflow-hidden">
                    <div class="bg-light p-3 border-bottom text-center">
                        <small class="fw-bold text-secondary">Pilih jawaban yang paling pas menurut kamu:</small>
                    </div>
                    <?php
                    $questions = [
                        "Saya lebih tenang kalau tiap bulan sudah pasti dapet gaji tetap, biarpun kerjaan lagi sepi.",
                        "Saya lebih semangat kerja kalau bayarannya dihitung per titik atau per unit yang saya pasang.",
                        "Saya mending dapet gaji kecil tapi pasti tiap bulan, daripada dapet gede tapi bulan depan nggak jelas dapet berapa.",
                        "Nggak apa-apa bulan ini dapet duit dikit, asalkan bulan depan ada kesempatan dapet uang banyak.",
                        "Saya bisa kerja bener dan disiplin biarpun nggak diawasin terus sama bos/atasan.",
                        "Saya bakal kerja lebih rapi dan tanggung jawab kalau uang jasa pasangnya langsung masuk ke kantong saya.",
                        "Saya lebih nyaman kalau alat kerja dan motor/mobil disediain kantor, jadi saya nggak pusing servis sendiri.",
                        "Kalau kerjaan kantor lagi sepi, saya biasanya aktif cari-cari servis sampingan di luar.",
                        "Sistem bonus yang sekarang kayaknya perlu diperbaiki biar lebih pas sama capeknya di lapangan.",
                        "Saya jadi males kerja kalau target dari kantor terlalu tinggi dan mustahil buat dikejar.",
                        "Saya suka kalau gaji bulanan dihitung rata dari hasil kerja 3 bulan terakhir, biar stabil tapi tetep gede.",
                        "Saya ngerasa lebih dihargai kalau urusan harga servis/pasang bisa saya atur sendiri ke pelanggan.",
                        "Kalau lagi nggak punya pegangan uang, saya jadi nggak fokus pas lagi kerja di rumah orang.",
                        "Saya mau aja disuruh lembur capek-capek asalkan uang lemburnya langsung cair jadi duit tunai.",
                        "Kerja dengan gaji yang segitu-gitu aja bikin saya kurang semangat buat kasih yang maksimal.",
                        "Sistem gaji rata-rata per 3 bulan ngebantu dapur saya tetep 'ngebul' biarpun lagi musim sepi orderan.",
                        "Saya bakal setia kerja di sini kalau urusan hitungan uang itu jujur dan nggak ada yang ditutup-tutupin.",
                        "Saya ngerasa punya skill yang cukup buat cari uang sendiri kalau sewaktu-waktu nggak kerja di kantor lagi.",
                        "Buat saya, naik jabatan atau karir yang jelas itu lebih penting daripada dapet uang kaget sekali-kali.",
                        "Saya ngerasa potongan kantor untuk biaya admin selama ini kegedean buat kami yang di lapangan."
                    ];
                
                    $labels = [
                        1 => "Sangat Gak Setuju",
                        2 => "Gak Setuju",
                        3 => "Ragu / Netral",
                        4 => "Setuju",
                        5 => "Setuju Banget"
                    ];
                
                    foreach ($questions as $i => $q) {
                        $n = $i + 1;
                        echo "<div class='q-card'>
                                <p class='mb-2 fw-bold' style='color: #1e293b;'>$n. $q</p>
                                <div class='likert-container'>";
                                
                                foreach ($labels as $val => $text) {
                                    echo "<div class='likert-item'>
                                            <input class='form-check-input' type='radio' name='q$n' id='q{$n}_{$val}' value='$val' required>
                                            <label for='q{$n}_{$val}'><strong>$val</strong><br>$text</label>
                                          </div>";
                                }
                
                        echo "  </div>
                              </div>";
                    }
                    ?>
                </div>

                <div class="card p-4">
                    <h6 class="fw-bold mb-3">Tulis Pendapat Kamu:</h6>
                    <div class="mb-4">
                        <label class="form-label small">1. Menurut kamu, apa kekurangan sistem gaji dan bonus yang ada sekarang?</label>
                        <textarea name="essay1" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">2. Pengennya ke depan sistem kerja kita kayak gimana biar kamu makin semangat?</label>
                        <textarea name="essay2" class="form-control" rows="3" required></textarea>
                    </div>
                </div>

                <button type="submit" name="submit_asesmen" class="btn btn-primary btn-lg w-100 shadow mb-5">Kirim Pendapat Saya</button>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<div class="container my-5 text-center">
    <div class="card p-5 shadow">
        <h4 class="fw-bold text-success mb-3">Terima Kasih Banyak!</h4>
        <p class="text-muted">Pendapat kamu udah kami simpan. Ini adalah analisa karakter kerja kamu berdasarkan jawaban tadi:</p>
        <div class="bg-light p-4 rounded border-start border-primary border-5 text-start my-4">
            <h5 class="fw-bold"><?php echo $kategori; ?></h5>
            <p class="mb-0" style="line-height: 1.6;"><?php echo $analisa; ?></p>
        </div>
        
        <div class="text-start mt-4 p-3 border rounded">
            <p class="small fw-bold">Ngerasa nggak sesuai? Atau ada yang mau ditambahin?</p>
            <form method="POST">
                <input type="hidden" name="last_id" value="<?php echo $last_id; ?>">
                <textarea name="feedback_hasil" class="form-control mb-3" rows="2" placeholder="Tulis di sini..."></textarea>
                <button type="submit" name="submit_feedback" class="btn btn-sm btn-dark">Kirim Masukan Tambahan</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>