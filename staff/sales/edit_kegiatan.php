<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Kegiatan Baru";
$currentPage = "Today";

if (!isset($_GET['id'])) {
    echo "ID kegiatan tidak ditemukan.";
    exit();
}

$kegiatan_id = $_GET['id'];

// Ambil data kegiatan
$sql = "SELECT ks.*, sc.nama AS nama_customer FROM kegiatan_sales ks
        LEFT JOIN sales_customer sc ON ks.id_customer = sc.id
        WHERE ks.id = ? AND ks.deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kegiatan_id);
$stmt->execute();
$result = $stmt->get_result();
$kegiatan = $result->fetch_assoc();

if (!$kegiatan) {
    echo "Kegiatan tidak ditemukan.";
    exit();
}

// Ambil tim yang sudah dipilih
$existing_team = [];
$res = mysqli_query($conn, "SELECT id_sales FROM team_kegiatan_sales WHERE id_kegiatan_sales = '$kegiatan_id' AND deleted_at IS NULL");
while ($r = mysqli_fetch_assoc($res)) {
    $existing_team[] = $r['id_sales'];
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jadwal = $_POST['jadwal'];
    $keterangan = $_POST['keterangan'];
    $team_sales = $_POST['sales'] ?? [];

    // Update kegiatan_sales
    $stmt = $conn->prepare("UPDATE kegiatan_sales SET jadwal = ?, keterangan = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $jadwal, $keterangan, $kegiatan_id);
    $stmt->execute();

    // Hitung perubahan tim sales
    $team_sales_ids = array_map('intval', $team_sales);
    $sales_to_soft_delete = array_diff($existing_team, $team_sales_ids);
    $sales_to_insert = array_diff($team_sales_ids, $existing_team);

    // Soft delete yang tidak dipilih lagi
    foreach ($sales_to_soft_delete as $id_sales) {
        $conn->query("UPDATE team_kegiatan_sales SET deleted_at = NOW(), updated_at = NOW() WHERE id_kegiatan_sales = '$kegiatan_id' AND id_sales = '$id_sales'");
    }

    // Insert yang baru dipilih
    foreach ($sales_to_insert as $id_sales) {
        $stmt = $conn->prepare("INSERT INTO team_kegiatan_sales (id_kegiatan_sales, id_sales, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->bind_param("ii", $kegiatan_id, $id_sales);
        $stmt->execute();
    }

    header("Location: index-sa.php");
    exit();
}
// Ambil semua sales
$sales = mysqli_query($conn, "SELECT * FROM sales WHERE deleted_at IS NULL");

// Ambil tim yang sudah dipilih
$existing_team = [];
$res = mysqli_query($conn, "SELECT id_sales FROM team_kegiatan_sales WHERE id_kegiatan_sales = '$kegiatan_id' AND deleted_at IS NULL");
while ($r = mysqli_fetch_assoc($res)) {
    $existing_team[] = $r['id_sales'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php
  include "head.php";
  ?>
  <style>
    ul#data-tek li:nth-child(odd) {
      background-color: white;
    }

    ul#data-tek li:nth-child(even) {
      background-color: #efefef;
      border-radius: 0;
    }

    #toggleLoadMore,
    #toggleLoadMore1,
    #toggleLoadMore2 {
      border-bottom-left-radius: 0;
      border-bottom-right-radius: 0;
    }

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
    }
        <?php include "css/floating-menu2.css";?>
  </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <?php
  include "cek-menu.php";
  ?>

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

    <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $todayDate = strftime('%d %B %Y');
    ?>

<div class="container-fluid py-4">

  <div class="card p-4 mb-4 shadow-sm">
    <h3>Edit Kegiatan Sales</h3>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Customer</label>
            <input type="text" class="form-control border p-2" value="<?php echo htmlspecialchars($kegiatan['nama_customer']); ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Jadwal</label>
            <input type="datetime-local" name="jadwal" class="form-control border p-2" value="<?php echo date('Y-m-d\TH:i', strtotime($kegiatan['jadwal'])); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control border p-2" rows="3"><?php echo htmlspecialchars($kegiatan['keterangan']); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Pilih Sales</label>
            <div class="row">
                <?php while ($s = mysqli_fetch_assoc($sales)) : ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sales[]" value="<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $existing_team) ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo $s['nama']; ?></label>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="kegiatan.php" class="btn btn-secondary">Kembali</a>
    </form>
    
  </div>



  <?php
  include "floating-menu.php";
  include "footer.php";
  ?>
</div>

    <?php
    // }
    ?>


  </main>
  <?php
  include "js-include.php";
  ?>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>




</body>

</html>