<?php
// Sambungkan ke database dan periksa apakah pengguna sudah login (sesuaikan dengan kebutuhan Anda)
include "conn.php";

if (isset($_GET['id_kegiatan'])) {
    $id_kegiatan = $_GET['id_kegiatan'];

    // Query untuk mengambil gambar_finish dari database
    $sql = "SELECT gambar_finish FROM kegiatan WHERE id_kegiatan = $id_kegiatan";
    $result = mysqli_query($conn, $sql);

// ...
if (mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);
    $gambarFinishBlob = $row['gambar_finish'];

    // Cetak data blob untuk debugging
    echo '<pre>';
    print_r($gambarFinishBlob);
    echo '</pre>';

    // Mengonversi blob ke format gambar (misalnya, PNG)
    $gambarFinishBase64 = base64_encode($gambarFinishBlob);
    $gambarFinishDataURI = 'data:image/png;base64,' . $gambarFinishBase64;

    // Keluarkan gambar_finish sebagai data URI
    echo '<img id="gambarFinish" src="' . $gambarFinishDataURI . '" alt="Gambar Selesai">';
} else {
    // Gambar tidak ditemukan
    echo '<img id="gambarFinish" src="path/to/your/default/image.png" alt="Gambar Selesai">';
}
// ...

} else {
    // Parameter id_kegiatan tidak valid
    echo '<img id="gambarFinish" src="path/to/your/default/image.png" alt="Gambar Selesai">';
}
?>
