<?php
session_start();

// ── Auth check ──
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo "error";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["image"]["name"]) && !empty($_FILES["image"]["name"])) {
        $targetDirectory = "uploads/";

        // ── Validasi ekstensi file ──
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            echo "error";
            exit;
        }

        // ── Validasi MIME type ──
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            echo "error";
            exit;
        }

        // ── Validasi ukuran (max 10MB) ──
        if ($_FILES["image"]["size"] > 10 * 1024 * 1024) {
            echo "error";
            exit;
        }

        // ── Generate nama file aman (tanpa nama asli user) ──
        $imageFilename = uniqid('img_', true) . "." . $ext;
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
