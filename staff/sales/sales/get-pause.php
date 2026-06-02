<?php
include "../conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data yang dikirim dari form
    $pauseDate = $_POST["pauseDate"];
    $pauseTime = $_POST["pauseTime"];
    $kegiatanId = $_POST["kegiatanId"];
    $keterangan = $_POST["keterangan"];
    $status = "Lanjut Nanti";

    // Ambil data-file yang dikirim
    $image1 = isset($_FILES["image1"]) ? $_FILES["image1"] : null;
    $image2 = isset($_FILES["image2"]) ? $_FILES["image2"] : null;
    $image3 = isset($_FILES["image3"]) ? $_FILES["image3"] : null;

    $sql = "SELECT * FROM kegiatan WHERE id_kegiatan = $kegiatanId";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $kodeTransaksi = $row["kode_transaksi"];

    // Buat nama baru untuk file
    function generateNewFilename($kodeTransaksi, $kegiatanId, $extension) {
        return $kodeTransaksi . "_" . uniqid() . "_" . $kegiatanId . "." . $extension;
    }

    // Tentukan folder upload
    $uploadDir = "../assets/img/uploads/";

    // Ubah nama file jika file diunggah
    function moveFile($file, $newFilename, $uploadDir) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            move_uploaded_file($file["tmp_name"], $uploadDir . $newFilename);
            return $newFilename;
        }
        return "NO";
    }

    // Ubah nama file dan pindahkan file ke folder upload
    $newFilename1 = moveFile($image1, generateNewFilename($kodeTransaksi, $kegiatanId, pathinfo($image1["name"], PATHINFO_EXTENSION)), $uploadDir);
    $newFilename2 = moveFile($image2, generateNewFilename($kodeTransaksi, $kegiatanId, pathinfo($image2["name"], PATHINFO_EXTENSION)), $uploadDir);
    $newFilename3 = moveFile($image3, generateNewFilename($kodeTransaksi, $kegiatanId, pathinfo($image3["name"], PATHINFO_EXTENSION)), $uploadDir);

?>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <!-- <div id="locationCoordinates"></div> -->
    <div id="currentDateTime"></div>
    <script>
    // Dapatkan koordinat lokasi saat ini
    function getDeviceLocation() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;

                    // Kirim data menggunakan AJAX
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "save-pause.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState == 4 && xhr.status == 200) {
                            console.log("Data berhasil dikirim.");
                            // Redirect ke halaman save-mulai.php setelah data terkirim
                            window.location.href = "save-pause.php?latitude=" + latitude + "&longitude=" + longitude + "&id_kegiatan=<?php echo $kegiatanId; ?>&file1=<?php echo $newFilename1;?>&file2=<?php echo $newFilename2;?>&file3=<?php echo $newFilename3;?>&pauseDate=<?php echo $pauseDate;?>&pauseTime=<?php echo $pauseTime;?>&keterangan=<?php echo $keterangan;?>";
                        }
                    };
                    xhr.send("latitude=" + latitude + "&longitude=" + longitude + "&id_kegiatan=<?php echo $kegiatanId; ?>&file1=<?php echo $newFilename1;?>&file2=<?php echo $newFilename2;?>&file3=<?php echo $newFilename3;?>&pauseDate=<?php echo $pauseDate;?>&pauseTime=<?php echo $pauseTime;?>&keterangan=<?php echo $keterangan;?>");
                },
                function(error) {
                    console.error("Error getting geolocation:", error);
                }
            );
        } else {
            console.error("Geolocation is not supported by this browser.");
        }
    }

    // Panggil fungsi untuk mendapatkan lokasi perangkat saat ini
    getDeviceLocation();
    </script>
    <?php

} else {
    // Jika bukan metode POST, tangani sesuai kebutuhan aplikasi Anda
    echo "Metode yang diterima bukan POST.";
}
?>
