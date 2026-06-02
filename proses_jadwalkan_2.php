
<?php
session_start();
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kegiatanId = $_POST["kegiatanId"];
    $tanggal = $_POST["tanggal"];
    $jam = $_POST["jam"];
    $selectedTechnicians = $_POST["teknisi"];

    // Gabungkan array teknisi menjadi string dengan koma
    $idTeknisi = implode(",", $selectedTechnicians);

    $tgl_request = $tanggal . ' ' . $jam;
    $stat = "Pending";

    // Update data kegiatan dengan teknisi terpilih, tgl_request, dan status (Pending)
    $updateQuery = "UPDATE kegiatan SET id_teknisi = ?, tgl_request = ?, status = 'Pending' WHERE id_kegiatan = ?";
    if ($stmt = mysqli_prepare($conn, $updateQuery)) {
        mysqli_stmt_bind_param($stmt, "ssi", $idTeknisi, $tgl_request, $kegiatanId);
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil mengupdate tabel kegiatan, tambahkan data teknisi ke tabel team
            $teamInsertQuery = "INSERT INTO team (id_kegiatan, id_teknisi, tgl_request, status) VALUES (?, ?)";
            if ($teamStmt = mysqli_prepare($conn, $teamInsertQuery)) {
                foreach ($selectedTechnicians as $id_teknisi) {
                    mysqli_stmt_bind_param($teamStmt, "iiss", $kegiatanId, $id_teknisi, $tgl_request, $status);
                    mysqli_stmt_execute($teamStmt);
                }
            }
            mysqli_stmt_close($teamStmt);

            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "error";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "error";
}


mysqli_close($conn);
?>
