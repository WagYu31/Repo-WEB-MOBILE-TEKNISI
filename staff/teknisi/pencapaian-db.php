<?php
include "../conn.php";

$dataTek = "SELECT * FROM user WHERE id_user = $id_user";
$resultDataTek = mysqli_query($conn, $dataTek);
$rowDataTek = mysqli_fetch_assoc($resultDataTek);
$idTek = $rowDataTek["id_teknisi"];

$stat = "Clear";
$pasang = "Pasang Baru";
$surv = "Survey";
$serv = "Service";
// Mengatur nilai default untuk $currentMonth dan $currentYear
if (isset($_GET['bulan'])) {
    // Jika formulir disubmit, gunakan nilai dari formulir
    $selectedDate = $_GET['bulan'];
    $currentMonth = date('m', strtotime($selectedDate));
    $currentYear = date('Y', strtotime($selectedDate));
} else {
    // Jika formulir tidak disubmit, gunakan nilai default (tanggal saat ini)
    $currentMonth = date('m');
    $currentYear = date('Y');
}

$sql = "SELECT COUNT(*) AS survey_count FROM kegiatan WHERE id_teknisi = $idTek > 0 AND status = '$stat' AND jenis = '$surv' AND DATE_FORMAT(tgl_selesai, '%Y-%m') = '$currentYear-$currentMonth'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$surveyCount = $row['survey_count'];

$sqlPs = "SELECT COUNT(*) AS pasang_count FROM kegiatan WHERE id_teknisi = $idTek AND status = '$stat' AND jenis = '$pasang' AND DATE_FORMAT(tgl_selesai, '%Y-%m') = '$currentYear-$currentMonth'";
$resultPs = mysqli_query($conn, $sqlPs);
$rowPs = mysqli_fetch_assoc($resultPs);
$pasangCount = $rowPs['pasang_count'];

$sqlSe = "SELECT COUNT(*) AS service_count FROM kegiatan WHERE id_teknisi = $idTek > 0 AND status = '$stat' AND jenis = '$serv' AND DATE_FORMAT(tgl_selesai, '%Y-%m') = '$currentYear-$currentMonth'";
$resultSe = mysqli_query($conn, $sqlSe);
$rowSe = mysqli_fetch_assoc($resultSe);
$serviceCount = $rowSe['service_count'];


$sqlInv = "SELECT SUM(bonus) AS total_bonus 
           FROM kegiatan 
           WHERE id_teknisi = $idTek 
           AND MONTH(tgl_inv) = $currentMonth 
           AND YEAR(tgl_inv) = $currentYear";
$resultInv = mysqli_query($conn, $sqlInv);
$row = mysqli_fetch_assoc($resultInv);
$totalBonus = $row['total_bonus'];
$totalBonusFormatted = "Rp " . number_format($totalBonus, 0, ',', '.'); // Ubah $totalBonus menjadi format mata uang rupiah

$sqlInv = "SELECT SUM(denda) AS total_denda 
           FROM kegiatan 
           WHERE id_teknisi = $idTek 
           AND MONTH(tgl_inv) = $currentMonth 
           AND YEAR(tgl_inv) = $currentYear";
$resultInv = mysqli_query($conn, $sqlInv);
$row = mysqli_fetch_assoc($resultInv);
$totalDenda = $row['total_denda'];
$totalDendaFormatted = "Rp " . number_format($totalDenda, 0, ',', '.'); // Ubah $totalBonus menjadi format mata uang rupiah

?>

<div class="container">
    <div class="profile-card col-10 offset-1">
        <div class="profile-info text-center">
            <?php
                $dtMt = date('M Y');
            ?>
            <span class="nmUs text-dark text-uppercase" style="font-weight:bold; font-size:25px;"><?php echo $nmUser; ?></span><br>
            <form method="GET" class="form-inline d-flex justify-content-start align-items-center col-12">
                <div class="form-group col-8">
                    <!--<label for="bulan" class="mr-2">Pilih Bulan:</label>-->
                    <input type="month" id="bulan" name="bulan" class="form-control border bg-white p-2" min="<?php echo date('Y-m', strtotime('-5 months')); ?>" max="<?php echo date('Y-m'); ?>" value="<?php echo isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m'); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary col-4 mt-3 ms-2">Cari</button>
            </form>
            <p></p>Kamu berhasil menyelesaikan :</p>
        </div>
        <div class="task-counts text-center d-flex flex-column col-12">
            <div class="d-flex flex-row justify-content-between mb-2">
                <div class="task-count task-count-survey bg-gradient-info w-30 p-3 text-white" style="border-radius:10px;">
                    <p><span id="count1" class="display-4"></span></p>
                    <h5 class="text-white" style="font-size: 18px;">Survey</h5>
                </div>
                <div class="task-count task-count-pasang bg-gradient-info w-30 p-3 text-white" style="border-radius:10px;">
                    <p><span id="count2" class="display-4"></span></p>
                    <h5 class="text-white" style="font-size: 18px;">Pasang Baru</h5>
                </div>
                <div class="task-count task-count-service bg-gradient-info w-30 p-3 text-white" style="border-radius:10px;">
                    <p><span id="count3" class="display-4"></span></p>
                    <h5 class="text-white" style="font-size: 18px;">Service</h5>
                </div>
            </div>
            <div class="task-count task-count-service bg-gradient-success col-12 p-3 mt-2 text-white" style="border-radius:10px;">
                <p><span id="count4" class="display-4"></span></p>
                <h5 class="text-white" style="font-size: 18px;">Pendapatan</h5>
            </div>
            <p class="font-italic text-xs text-dark mt-3">* Pendapatan dihitung berdasarkan kegiatan yang telah diselesaikan dan dibuat invoice di bulan yang sama</p>
            <div class="task-count task-count-service bg-gradient-danger col-12 p-3 mt-2 text-white" style="border-radius:10px;">
                <p><span id="count5" class="display-4"></span></p>
                <h5 class="text-white" style="font-size: 18px;">Denda</h5>
            </div>
        </div>

    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-animateNumber/0.0.14/jquery.animateNumber.min.js"></script>

<script>
    function animateRandomNumbers(ids, ends) {
        var timers = [];
        var currentValues = [];

        // Initialize the current values to 0
        for (var i = 0; i < ids.length; i++) {
            currentValues.push(0);
        }

        // Iterate through the elements and their corresponding end values
        for (var i = 0; i < ids.length; i++) {
            var id = ids[i];
            var end = ends[i];
            var $element = $(id);
            $element.text('0');

            var timer = setInterval(function() {
                // Update the current values randomly
                for (var i = 0; i < ids.length; i++) {
                    currentValues[i] = Math.floor(Math.random() * 1000); // Random between 0 and 999
                }

                // Update the text of all elements
                for (var i = 0; i < ids.length; i++) {
                    $(ids[i]).text(currentValues[i]);
                }
            }, 50); // Adjust the interval as needed

            timers.push(timer);
        }

        setTimeout(function() {
            for (var i = 0; i < timers.length; i++) {
                clearInterval(timers[i]);
            }

            // Set the final values for all elements
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                var end = ends[i];
                $(id).text(end);
            }
        }, 2000); // After 3 seconds, stop the animation and set the actual values
    }

    animateRandomNumbers(["#count1", "#count2", "#count3", "#count4", "#count5"], [<?php echo $surveyCount > 0 ? $surveyCount : '0'; ?>, <?php echo $pasangCount > 0 ? $pasangCount : '0'; ?>, <?php echo $serviceCount > 0 ? $serviceCount : '0'; ?>, <?php echo $totalBonus > 0 ? $totalBonus : '0'; ?>, <?php echo $totalDenda > 0 ? $totalDenda : '0'; ?>]);
    
    setTimeout(function() {
        $('#count4').text('<?php echo $totalBonusFormatted; ?>');
    }, 2000); // Setelah 3 detik, ubah nilai count4
    setTimeout(function() {
        $('#count5').text('<?php echo $totalDendaFormatted; ?>');
    }, 2000); // Setelah 3 detik, ubah nilai count4

</script>