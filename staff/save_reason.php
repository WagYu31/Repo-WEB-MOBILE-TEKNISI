<?php
include "conn.php";
include "session.php";

date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kegiatan_id = isset($_POST['kegiatan_id']) ? intval($_POST['kegiatan_id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if ($kegiatan_id == 0 || empty($reason)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Kegiatan atau Alasan tidak boleh kosong.']);
        exit;
    }

    $mediaPath = null;

    // Proses Upload Media (Jika ada)
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/reasons/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['media']['tmp_name'];
        $fileName = $_FILES['media']['name'];
        $fileSize = $_FILES['media']['size'];
        $fileType = $_FILES['media']['type'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($fileExtension, $allowedExtensions)) {
            // Generate nama file unik
            $newFileName = 'reason_' . $kegiatan_id . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $mediaPath = $newFileName;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload file.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Format file tidak diizinkan (hanya jpg, png, pdf).']);
            exit;
        }
    }

    // Simpan ke Database
    $stmt = $conn->prepare("INSERT INTO kegiatan_reasons (kegiatan_id, reason, media) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $kegiatan_id, $reason, $mediaPath);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $conn->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>