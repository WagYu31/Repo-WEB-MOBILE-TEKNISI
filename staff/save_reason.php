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
    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadError = $_FILES['media']['error'];
        
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas maksimal upload server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas maksimal form.',
                UPLOAD_ERR_PARTIAL    => 'File hanya ter-upload sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk server.',
                UPLOAD_ERR_EXTENSION  => 'Upload file dihentikan oleh ekstensi PHP.'
            ];
            $msg = isset($errMessages[$uploadError]) ? $errMessages[$uploadError] : 'Gagal mengupload file (Kode: ' . $uploadError . ').';
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit;
        }

        $uploadDir = __DIR__ . '/uploads/reasons/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['media']['tmp_name'];
        $fileName = $_FILES['media']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'reason_' . $kegiatan_id . '_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $mediaPath = $newFileName;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file ke folder uploads/reasons. Pastikan izin folder telah diberikan.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Format file tidak diizinkan! Hanya file JPG, PNG, WEBP, atau PDF.']);
            exit;
        }
    }

    // Simpan ke Database
    $stmt = $conn->prepare("INSERT INTO kegiatan_reasons (kegiatan_id, reason, media) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $kegiatan_id, $reason, $mediaPath);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Catatan dan bukti berhasil disimpan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $conn->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>