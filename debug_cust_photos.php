<?php
include "staff/sales/conn.php";
$sql = "
    SELECT
        ks.id              AS kegiatan_id,
        ks.jadwal,
        ks.keterangan,
        ks.status          AS status_kegiatan,
        c.id               AS customer_id,
        c.nama             AS nama_customer,
        c.alamat           AS alamat_customer,
        c.foto             AS foto_customer
    FROM team_kegiatan_sales tks
    JOIN kegiatan_sales ks ON ks.id = tks.id_kegiatan_sales AND ks.deleted_at IS NULL
    JOIN sales_customer c  ON c.id  = ks.id_customer        AND c.deleted_at IS NULL
    ORDER BY ks.id DESC LIMIT 5
";
$res = mysqli_query($conn, $sql);
echo "<pre>";
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
echo "</pre>";
