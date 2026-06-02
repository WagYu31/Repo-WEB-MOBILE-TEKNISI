<?php
include 'conn.php';

$role = $_POST['role'];

if ($role === 'sales') {
  $query = "
    SELECT s.id, s.nama 
    FROM sales s 
    LEFT JOIN user_sales us ON s.id = us.sales_id 
    WHERE us.sales_id IS NULL
  ";
} elseif ($role === 'teknisi') {
  $query = "
    SELECT t.id, t.nama 
    FROM teknisi t 
    LEFT JOIN user_teknisi ut ON t.id = ut.teknisi_id 
    WHERE ut.teknisi_id IS NULL
  ";
} else {
  echo '<option value="">-- Pilih Nama --</option>';
  exit;
}

$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
  echo '<option value="">-- Pilih Nama --</option>';
  while ($row = mysqli_fetch_assoc($result)) {
    echo "<option value='{$role}_{$row['id']}'>{$row['nama']}</option>";
  }
} else {
  echo '<option value="">Tidak ada yang tersedia</option>';
}
?>
