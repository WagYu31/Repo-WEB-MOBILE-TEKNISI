<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Data Customer";
$currentPage = "Today";

date_default_timezone_set('Asia/Jakarta');

// Soft Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("UPDATE sales_customer SET deleted_at = NOW() WHERE id = '$id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $nama = $_POST['edit_nama'];
    $kategori = $_POST['edit_kategori'];
    $email = $_POST['edit_email'];
    $alamat = $_POST['edit_alamat'];
    $kota = $_POST['edit_kota'];
    $telp = preg_replace('/\D/', '', $_POST['edit_telp']);

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    $stmt = $conn->prepare("UPDATE sales_customer SET nama = ?, kategori = ?, telp_pribadi = ?, email = ?, alamat = ?, kota = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssi", $nama, $kategori, $telp, $email, $alamat, $kota, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_id'])) {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $email = $_POST['email'];
    $alamat = $_POST['alamat'];
    $kota = $_POST['kota'];
    $telp = preg_replace('/\D/', '', $_POST['telp']);

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    $stmt = $conn->prepare("INSERT INTO sales_customer (kategori, nama, telp_pribadi, email, alamat, kota, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssssss", $kategori, $nama, $telp, $email, $alamat, $kota);
    $stmt->execute();
    $stmt->close();

    echo "<div class='alert alert-success'>Sales Customer berhasil ditambahkan!</div>";
}

$salesData = mysqli_query($conn, "SELECT * FROM sales_customer WHERE deleted_at IS NULL ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include "head.php"; ?>
</head>
<body class="g-sidenav-show bg-gray-200">
<?php include "cek-menu.php"; ?>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
  <?php include "nav-top.php"; ?>
  <div class="container-fluid py-4">
    <div class="card p-4 mb-4 shadow-sm">
      <h2 class="mb-4">Tambah Sales Customer</h2>
      <form method="POST" class="mb-5">
        <div class="row">
          <div class="mb-3 col-md-4"><label>Nama</label><input type="text" name="nama" class="form-control border p-2" required></div>
          <div class="mb-3 col-md-6">
              <label class="d-block">Kategori</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="kategori" id="kategori_dealer" value="Dealer" required>
                <label class="form-check-label" for="kategori_dealer">Dealer</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="kategori" id="kategori_installer" value="Installer">
                <label class="form-check-label" for="kategori_installer">Installer</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="kategori" id="kategori_user" value="User">
                <label class="form-check-label" for="kategori_user">User</label>
              </div>
            </div>
          <div class="mb-3 col-md-3"><label>No. Telepon</label><input type="text" name="telp" class="form-control border p-2" required></div>
          <div class="mb-3 col-md-3"><label>Email</label><input type="email" name="email" class="form-control border p-2"></div>
          <div class="mb-3 col-md-6"><label>Kota</label><input type="text" name="kota" class="form-control border p-2"></div>
          <div class="mb-3 col-md-12"><label>Alamat</label><input type="text" name="alamat" class="form-control border p-2"></div>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </form>

      <h3 class="mb-3">Daftar Sales Customer</h3>
      <table class="table table-bordered">
        <thead class="table-light">
          <tr class="text-center">
            <th>No</th><th>kategori</th><th>Nama</th><th>Telp</th><th>Email</th><th>Alamat</th><th>Kota</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($row = mysqli_fetch_assoc($salesData)): ?>
          <tr>
            <td class="text-center"><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['kategori']); ?></td>
            <td><?= htmlspecialchars($row['nama']); ?></td>
            <td><a href="https://wa.me/<?= htmlspecialchars($row['telp_pribadi']); ?>" target="_blank"><?= htmlspecialchars(preg_replace('/^62/', '0', $row['telp_pribadi'])); ?></a></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= htmlspecialchars($row['alamat']); ?></td>
            <td><?= htmlspecialchars($row['kota']); ?></td>
            <td>
              <button type="button" class="btn btn-warning btn-sm editBtn"
                data-id="<?= $row['id']; ?>"
                data-nama="<?= htmlspecialchars($row['nama']); ?>"
                data-nik="<?= htmlspecialchars($row['kategori']); ?>"
                data-telp="<?= htmlspecialchars($row['telp_pribadi']); ?>"
                data-email="<?= htmlspecialchars($row['email']); ?>"
                data-alamat="<?= htmlspecialchars($row['alamat']); ?>"
                data-kota="<?= htmlspecialchars($row['kota']); ?>"
                data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
              <a href="?delete_id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
          <tr></tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
          <div class="modal-header"><h5>Edit Data Sales Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body row">
            <input type="hidden" name="update_id" id="edit_id">
            <div class="col-md-4 mb-3"><label>Nama</label><input type="text" name="edit_nama" id="edit_nama" class="form-control border p-2" required></div>
            <div class="col-md-6 mb-3">
              <label class="d-block">Kategori</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="edit_kategori" id="kategori_dealer" value="Dealer" required>
                <label class="form-check-label" for="kategori_dealer">Dealer</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="edit_kategori" id="kategori_installer" value="Installer">
                <label class="form-check-label" for="kategori_installer">Installer</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="edit_kategori" id="kategori_user" value="User">
                <label class="form-check-label" for="kategori_user">User</label>
              </div>
            </div>
            <div class="col-md-3 mb-3"><label>No. Telepon</label><input type="text" name="edit_telp" id="edit_telp" class="form-control border p-2" required></div>
            <div class="col-md-3 mb-3"><label>Email</label><input type="text" name="edit_email" id="edit_email" class="form-control border p-2"></div>
            <div class="col-md-6 mb-3"><label>Kota</label><input type="text" name="edit_kota" id="edit_kota" class="form-control border p-2"></div>
            <div class="col-md-12 mb-3"><label>Alamat</label><input type="text" name="edit_alamat" id="edit_alamat" class="form-control border p-2"></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-success">Update</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
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
      document.getElementById('edit_telp').value = btn.dataset.telp;
      document.getElementById('edit_email').value = btn.dataset.email;
      document.getElementById('edit_alamat').value = btn.dataset.alamat;
      document.getElementById('edit_kota').value = btn.dataset.kota;

      const kategori = btn.dataset.kategori;
      document.querySelectorAll('input[name="edit_kategori"]').forEach(radio => {
        radio.checked = (radio.value === kategori);
      });
    });
  });
</script>

</body>
</html>
