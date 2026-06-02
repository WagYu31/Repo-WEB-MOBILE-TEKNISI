<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract data from the form
    $permasalahan = $_POST['permasalahan'];
    $solusi = $_POST['solusi'];
    $keterangan_tambahan = $_POST['ket_finish'];
    $idKegiatan = $_POST['idKegiatan'];
    $tglSekarang = $_POST['tglSekarang'];
    $location = $_POST['location'];
    $status = "Clear";
    $uploadDirectory = "uploads/"; // Specify your upload directory
    // Process each uploaded file
    for ($i = 1; $i <= 5; $i++) {
        $inputFileName = 'dokumentasi' . $i;
        $newFileName = ''; // Initialize the newFileName variable

        // Check if a file is uploaded
        if (!empty($_FILES[$inputFileName]['name'])) {
            $originalFileName = $_FILES[$inputFileName]['name'];
            $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

            // Generate random prefix for file name
            $randomPrefix = rand(10000, 99999);
            $randomLetters = chr(rand(65, 90)) . chr(rand(65, 90)) . chr(rand(65, 90));
            $newFileName = $idKegiatan . '_' . $randomPrefix . '_' . $randomLetters . '.' . $extension;

            // Move the uploaded file to the desired directory with the new name
            move_uploaded_file($_FILES[$inputFileName]['tmp_name'], $uploadDirectory . $newFileName);

            // Update the database with file information
            // Assuming you have a database connection established

            // Your database update query
            $queryFinishKegiatan = "UPDATE kegiatan SET tgl_selesai='$tglSekarang', ket_finish='$permasalahan', ket_finish_2='$solusi', ket_finish_3='$keterangan_tambahan', lokasi_selesai='$location', gambar_finish_$i='$newFileName', status='$status' WHERE id_kegiatan='$idKegiatan'";
        } else {
            // If no file is uploaded, assign value '-' to the corresponding database column
            $queryFinishKegiatan = "UPDATE kegiatan SET tgl_selesai='$tglSekarang', ket_finish='$permasalahan', ket_finish_2='$solusi', ket_finish_3='$keterangan_tambahan', lokasi_selesai='$location', gambar_finish_$i='-', status='$status' WHERE id_kegiatan='$idKegiatan'";
        }

        // Execute the query
        $resultFinishKegiatan = mysqli_query($conn, $queryFinishKegiatan);
        $resultFinishKegiatan = true; // For the sake of this example, set $result to true

        // Check for success or handle errors accordingly
        if ($resultFinishKegiatan) {
            
        } else {
            echo 'Error updating data: ' . mysqli_error($your_database_connection);
        }
    }
    header("Location: index-teknisi.php");
    exit();
}
?>
