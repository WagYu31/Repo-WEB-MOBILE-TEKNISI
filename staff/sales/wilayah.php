<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Wilayah";
$currentPage = "Today";

date_default_timezone_set('Asia/Jakarta');

// Soft Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    // Jangan hapus Jabodetabek (ID 1) demi keamanan default mapping
    if ($id !== 1) {
        $conn->query("UPDATE wilayah SET deleted_at = NOW() WHERE id = '$id'");
        // Ubah semua sales & customer yang dikaitkan ke wilayah ini kembali ke Jabodetabek (ID 1)
        $conn->query("UPDATE sales SET id_wilayah = 1 WHERE id_wilayah = '$id'");
        $conn->query("UPDATE sales_customer SET id_wilayah = 1 WHERE id_wilayah = '$id'");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Update Wilayah
$successMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $nama = trim($_POST['edit_nama']);

    if (!empty($nama)) {
        $stmt = $conn->prepare("UPDATE wilayah SET nama = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $nama, $id);
        $stmt->execute();
        $stmt->close();
        $successMsg = "Nama wilayah berhasil diperbarui!";
    }
}

// Insert Wilayah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_id'])) {
    $nama = trim($_POST['nama']);

    if (!empty($nama)) {
        $stmt = $conn->prepare("INSERT INTO wilayah (nama, created_at, updated_at) VALUES (?, NOW(), NOW())");
        $stmt->bind_param("s", $nama);
        $stmt->execute();
        $stmt->close();
        $successMsg = "Wilayah baru berhasil ditambahkan!";
    }
}

// Ambil data wilayah
$wilayahData = mysqli_query($conn, "SELECT * FROM wilayah WHERE deleted_at IS NULL ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <?php include "head.php"; ?>
  <style>
    /* ── Premium Styling ── */
    .card-premium {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.02);
      margin-bottom: 24px;
    }
    
    .section-header-premium {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      background: #1e293b;
      color: #fff;
    }
    
    .section-header-premium h6 {
      margin: 0;
      font-size: 13px;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
    }
    
    .card-body-premium {
      padding: 28px 24px;
    }

    /* ── Form inputs ── */
    .form-group-premium {
      margin-bottom: 20px;
    }
    
    .form-label-premium {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .input-premium {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 10px 16px !important;
      font-size: 14px;
      color: #1e293b;
      background-color: #fff;
      transition: all 0.2s ease;
    }
    
    .input-premium:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
      outline: none;
    }

    /* ── Table custom styling ── */
    .premium-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .premium-table th {
      background: #f8fafc;
      border-bottom: 2px solid #e2e8f0;
      color: #64748b;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 14px 16px;
      text-align: left;
    }
    
    .premium-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
      font-size: 13px;
      vertical-align: middle;
    }
    
    .premium-table tr:hover td {
      background-color: #f8fafc;
    }

    /* ── Action Buttons ── */
    .btn-act {
      width: 32px; height: 32px; padding: 0; display: inline-flex;
      align-items: center; justify-content: center; border-radius: 8px;
      border: 1px solid transparent; transition: all 0.2s; cursor: pointer; text-decoration: none;
    }
    .btn-act:hover { transform: scale(1.08); }
    .btn-act .material-symbols-outlined { font-size: 16px; }
    .btn-act-edit { background: #fffbeb; color: #d97706; border-color: #fef3c7; }
    .btn-act-edit:hover { background: #d97706; color: #fff; }
    .btn-act-delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; margin-left: 6px; }
    .btn-act-delete:hover { background: #dc2626; color: #fff; }

    .btn-submit-premium {
      background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
      color: #fff !important;
      border: none;
      border-radius: 10px;
      padding: 10px 24px;
      font-size: 13px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 6px;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
      transition: all 0.22s ease;
      cursor: pointer;
    }
    
    .btn-submit-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    
    .btn-submit-premium:active {
      transform: translateY(0);
    }
    
    .btn-submit-premium .material-symbols-outlined {
      font-size: 16px;
    }

    /* ── Modal Premium Styling ── */
    .modal-content-premium {
      border-radius: 16px;
      border: none;
      overflow: hidden;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    }
    
    .modal-header-premium {
      background: #1e293b;
      color: #fff;
      padding: 20px 24px;
      border-bottom: none;
    }
    
    .modal-title-premium {
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .modal-body-premium {
      padding: 28px 24px;
      background: #fff;
    }
    
    .modal-footer-premium {
      background: #f8fafc;
      padding: 16px 24px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    
    <?php include "css/floating-menu2.css"; ?>
  </style>
</head>
<body class="g-sidenav-show bg-gray-200">
<?php include "cek-menu.php"; ?>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
  <?php include "nav-top.php"; ?>
  <div class="container-fluid py-4">
    
    <!-- Success Alert -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success text-white font-weight-bold mb-4" style="background: #10b981; border: none; border-radius: 10px; padding: 14px 20px;">
        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">check_circle</span>
        <?php echo $successMsg; ?>
      </div>
    <?php endif; ?>

    <!-- Card Tambah Wilayah -->
    <div class="card-premium">
      <div class="section-header-premium">
        <h6>
          <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add_location_alt</span>
          Tambah Wilayah Baru
        </h6>
      </div>
      <div class="card-body-premium">
        <form method="POST">
          <div class="row">
            <div class="col-md-6 form-group-premium">
              <label class="form-label-premium">Nama Wilayah / Region</label>
              <input type="text" name="nama" class="input-premium" placeholder="Masukkan nama wilayah baru (contoh: Jawa Timur)..." required>
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn-submit-premium">
              <span class="material-symbols-outlined">save</span>
              Simpan Wilayah
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Card Daftar Wilayah -->
    <div class="card-premium">
      <div class="section-header-premium">
        <h6>
          <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">map</span>
          Daftar Wilayah Operasional
        </h6>
        <span class="badge bg-light text-dark font-weight-bold" style="font-size: 11px;"><?= mysqli_num_rows($wilayahData); ?> Wilayah Terdaftar</span>
      </div>
      
      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th style="width: 70px; text-align: center;">No</th>
              <th>Nama Wilayah / Region</th>
              <th style="width: 250px;">Tanggal Dibuat</th>
              <th style="width: 150px; text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($wilayahData)): ?>
            <tr>
              <td style="text-align: center; font-weight: 600; color: #64748b;"><?= $no++; ?></td>
              <td><span style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['nama']); ?></span></td>
              <td><span class="text-muted" style="font-size:12.5px;"><?= date('d M Y, H:i', strtotime($row['created_at'])); ?> WIB</span></td>
              <td style="text-align: center;">
                <button type="button" class="btn-act btn-act-edit editBtn" 
                  data-id="<?= $row['id']; ?>" 
                  data-nama="<?= htmlspecialchars($row['nama']); ?>"
                  data-bs-toggle="modal" data-bs-target="#editModal" title="Ubah Nama Wilayah">
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <?php if ($row['id'] !== 1): ?>
                  <a href="?delete_id=<?= $row['id']; ?>" class="btn-act btn-act-delete" onclick="return confirm('Yakin ingin menghapus wilayah ini? Semua data sales/customer di wilayah ini akan dikembalikan ke default Jabodetabek.')" title="Hapus Wilayah">
                    <span class="material-symbols-outlined">delete</span>
                  </a>
                <?php else: ?>
                  <button type="button" class="btn-act text-muted" style="background:#f1f5f9; border-color:#e2e8f0; cursor:not-allowed;" title="Wilayah default tidak dapat dihapus" disabled>
                    <span class="material-symbols-outlined">lock</span>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($wilayahData) == 0): ?>
              <tr>
                <td colspan="4" class="text-center text-muted" style="padding: 40px;">Belum ada data wilayah operasional.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog">
        <form method="POST" class="modal-content modal-content-premium">
          <div class="modal-header modal-header-premium">
            <h5 class="modal-title modal-title-premium">
              <span class="material-symbols-outlined">edit_location_alt</span>
              Ubah Nama Wilayah
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body modal-body-premium">
            <input type="hidden" name="update_id" id="edit_id">
            
            <div class="form-group-premium">
              <label class="form-label-premium">Nama Wilayah / Region</label>
              <input type="text" name="edit_nama" id="edit_nama" class="input-premium" required>
            </div>
          </div>
          <div class="modal-footer modal-footer-premium">
            <button type="submit" class="btn-submit-premium">
              <span class="material-symbols-outlined">save</span>
              Simpan Perubahan
            </button>
            <button type="button" class="btn bg-gradient-secondary font-weight-bold" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 20px; font-size: 13px;">Batal</button>
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
    });
  });
</script>
</body>
</html>
