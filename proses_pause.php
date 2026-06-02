<?php
include "conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_kegiatan = $_POST["id_kegiatan"];
    $reDate = $_POST["reDate"];
    $reTime = $_POST["reTime"];
    $keterangan = $_POST["keterangan"];
    $status = "Pause"; // Status "Pause"
    $lokasi_selesai = $_POST["lokasi_selesai"];
    $tgl_selesai = $_POST["tgl_selesai"];
    
    // Ubah format tanggal dan waktu menjadi "Y-m-d H:i:s"
    $newDatetime = date("Y-m-d H:i:s", strtotime("$reDate $reTime"));

    // Update status kegiatan menjadi "Pause" di tabel kegiatan
    $query = "UPDATE kegiatan SET tgl_reschedule = '$newDatetime', status = '$status' WHERE id_kegiatan = $id_kegiatan";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $sch = "SELECT * FROM kegiatan WHERE id_kegiatan = $id_kegiatan";
        $res_sch = mysqli_query($conn, $sch);
        
        if ($res_sch) {
            $data = mysqli_fetch_assoc($res_sch);
            $lokasi_mulai = $data["lokasi_mulai"];
            $tgl_mulai = $data["tgl_mulai"];
            
            // Periksa apakah data sudah ada di tabel reschedule untuk id_kegiatan yang sama
            $checkQuery = "SELECT COUNT(*) AS count FROM reschedule WHERE id_kegiatan = $id_kegiatan";
            $checkResult = mysqli_query($conn, $checkQuery);
            
            if ($checkResult) {
                $row = mysqli_fetch_assoc($checkResult);
                $rowCount = $row["count"];
                
                if ($rowCount > 0) {
                    $sel = "SELECT * FROM reschedule WHERE id_kegiatan = $id_kegiatan ORDER BY tanggal DESC LIMIT 1";
                    $resSel = mysqli_query($conn, $sel);
                    if($resSel){
                        $data = mysqli_fetch_assoc($resSel);
                        $id_resc = $data["id_resc"];
                        $ins = "UPDATE reschedule SET tgl_selesai = '$tgl_selesai', lokasi_selesai = '$lokasi_selesai', keterangan = '$keterangan', status = '$status' WHERE id_resc = '$id_resc'";
                        $resIns = mysqli_query($conn, $ins);
                        if($resIns){
                           // Simpan data ke dalam tabel reschedule
                            $queryReschedule = "INSERT INTO reschedule (id_kegiatan, tanggal, keterangan, status) 
                                                VALUES ($id_kegiatan, '$newDatetime', '$keterangan', '$status')";
                            
                            $resultReschedule = mysqli_query($conn, $queryReschedule);
                    
                            if ($resultReschedule) {
                                echo "success";
                            } else {
                                echo "error";
                            } 
                        }
                    }
                }
                else{
                    // Simpan data ke dalam tabel reschedule
                    $queryReschedule = "INSERT INTO reschedule (id_kegiatan, tanggal, tgl_mulai, lokasi_mulai, tgl_selesai, lokasi_selesai, keterangan, status) 
                                        VALUES 
                                        ($id_kegiatan, '', '$tgl_mulai', '$lokasi_mulai', '$tgl_selesai', '$lokasi_selesai', '$keterangan', '$status'),
                                        ($id_kegiatan, '$newDatetime', '', '', '', '', '$keterangan', '$status')";
                    
                    $resultReschedule = mysqli_query($conn, $queryReschedule);
            
                    if ($resultReschedule) {
                        echo "success";
                    } else {
                        echo "error";
                    }
                }
            }
        }
    } else {
        echo "error";
    }
} else {
    echo "error";
}

mysqli_close($conn);
?>