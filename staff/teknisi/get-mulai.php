<?php
// Periksa apakah kunci-kunci yang diperlukan telah didefinisikan
if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];

    // Tambahkan JavaScript untuk menampilkan koordinat lokasi dan tanggal saat ini
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
                    xhr.open("POST", "save-mulai.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState == 4 && xhr.status == 200) {
                            console.log("Data berhasil dikirim.");
                            // Redirect ke halaman save-mulai.php setelah data terkirim
                            window.location.href = "save-mulai.php?latitude=" + latitude + "&longitude=" + longitude + "&id_kegiatan=<?php echo $id_kegiatan; ?>";
                        }
                    };
                    xhr.send("latitude=" + latitude + "&longitude=" + longitude + "&id_kegiatan=<?php echo $id_kegiatan; ?>");
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
    // Kunci yang diperlukan tidak didefinisikan, tangani kesalahan di sini
    echo "Error: Kunci yang diperlukan tidak didefinisikan.";
}
?>
