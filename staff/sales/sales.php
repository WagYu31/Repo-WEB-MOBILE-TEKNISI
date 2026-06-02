<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Sales";
$currentPage = "Today";

date_default_timezone_set('Asia/Jakarta');

// Soft Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("UPDATE sales SET deleted_at = NOW() WHERE id = '$id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Update Sales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $nama = $_POST['edit_nama'];
    $nik = $_POST['edit_nik'];
    $telp = preg_replace('/\D/', '', $_POST['edit_telp']);

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    $stmt = $conn->prepare("UPDATE sales SET nama = ?, nik = ?, telp = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $nama, $nik, $telp, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_id'])) {
    $nama = $_POST['nama'];
    $nik = $_POST['nik'];
    $rawTelp = $_POST['telp'];

    $telp = preg_replace('/\D/', '', $rawTelp);

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    $stmt = $conn->prepare("INSERT INTO sales (nik, nama, telp, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sss", $nik, $nama, $telp);
    $stmt->execute();
    $stmt->close();

    echo "<div class='alert alert-success'>Sales berhasil ditambahkan!</div>";
}

$salesData = mysqli_query($conn, "SELECT * FROM sales WHERE deleted_at IS NULL ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    <?php include "css/floating-menu2.css"; ?>
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
<?php include "cek-menu.php"; ?>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
  <?php
    include "nav-top.php";
    setlocale(LC_TIME, 'id_ID');
    $todayDate = strftime('%d %B %Y');
  ?>
  <div class="container-fluid py-4">
    <div class="card p-4 mb-4 shadow-sm">
      <h2 class="mb-4">Tambah Sales</h2>
      <form method="POST" class="mb-5">
        <div class="row">
          <div class="mb-3 col-12 col-md-6">
            <label class="form-label">Nama Sales</label>
            <input type="text" name="nama" class="form-control border p-2" required>
          </div>
          <div class="mb-3 col-12 col-md-2">
            <label class="form-label">NIK</label>
            <input type="text" name="nik" class="form-control border p-2" required>
          </div>
          <div class="mb-3 col-12 col-md-4">
            <label class="form-label">No. Telepon</label>
            <input type="text" name="telp" class="form-control border p-2" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </form>

      <h3 class="mb-3">Daftar Sales</h3>
      <table class="table table-bordered">
        <thead class="table-light">
          <tr class="text-center">
            <th>No</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>No. Telepon</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($row = mysqli_fetch_assoc($salesData)): ?>
          <tr>
            <td class="text-center"><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nik']); ?></td>
            <td><?= htmlspecialchars($row['nama']); ?></td>
            <td>
              <a href="https://wa.me/<?= htmlspecialchars($row['telp']); ?>" target="_blank">
                <?= htmlspecialchars(preg_replace('/^62/', '0', $row['telp'])); ?>
              </a>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-warning editBtn" 
                data-id="<?= $row['id']; ?>" 
                data-nama="<?= htmlspecialchars($row['nama']); ?>" 
                data-nik="<?= htmlspecialchars($row['nik']); ?>" 
                data-telp="<?= htmlspecialchars($row['telp']); ?>" 
                data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
              <a href="?delete_id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
          <tr></tr>
        </tbody>
      </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Data Sales</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body row">
            <input type="hidden" name="update_id" id="edit_id">
            <div class="col-md-6 mb-3">
              <label>Nama</label>
              <input type="text" name="edit_nama" id="edit_nama" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label>NIK</label>
              <input type="text" name="edit_nik" id="edit_nik" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label>No. Telepon</label>
              <input type="text" name="edit_telp" id="edit_telp" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </form>
      </div>
    </div>

    <?php include "floating-menu.php"; ?>
    <?php include "footer.php"; ?>
  </div>
</main>

<?php include "js-include.php"; ?>
<script>
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('edit_id').value = btn.dataset.id;
      document.getElementById('edit_nama').value = btn.dataset.nama;
      document.getElementById('edit_nik').value = btn.dataset.nik;
      document.getElementById('edit_telp').value = btn.dataset.telp;
    });
  });
</script>
</body>
</html>
