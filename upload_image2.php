<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["image"]["name"]) && !empty($_FILES["image"]["name"])) {
        $targetDirectory = "uploads/"; // Ganti dengan direktori tempat Anda ingin menyimpan gambar
        $imageFilename = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $targetPath = $targetDirectory . $imageFilename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            echo $imageFilename;
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>
