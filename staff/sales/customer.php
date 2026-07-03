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
$successMsg = "";
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

    $successMsg = "Data Customer berhasil diperbarui!";
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

    $successMsg = "Customer baru berhasil ditambahkan!";
}

$salesData = mysqli_query($conn, "SELECT * FROM sales_customer WHERE deleted_at IS NULL ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
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

    /* ── Avatars in Table ── */
    .avatar-initials-table {
      width: 36px; height: 36px;
      border-radius: 50%;
      color: #fff;
      font-size: 12px; font-weight: 700;
      display: inline-flex; align-items: center; justify-content: center;
      margin-right: 12px;
      vertical-align: middle;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .customer-identity-cell {
      display: flex;
      align-items: center;
      text-align: left;
    }

    /* ── Category Badges ── */
    .category-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: inline-block;
    }
    .badge-dealer { background: #dbeafe; color: #1e40af; }
    .badge-installer { background: #f3e8ff; color: #6b21a8; }
    .badge-user { background: #d1fae5; color: #065f46; }
    .badge-default { background: #f1f5f9; color: #475569; }

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
    
    .wa-link {
      font-size: 13px; color: #10b981;
      text-decoration: none; display: inline-flex;
      align-items: center; gap: 4px; font-weight: 600;
    }
    .wa-link:hover { text-decoration: underline; }

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

    input[type="checkbox"] {
      -webkit-appearance: checkbox;
      -moz-appearance: checkbox;
      appearance: checkbox;
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

    <!-- Card Tambah Sales Customer -->
    <div class="card-premium">
      <div class="section-header-premium">
        <h6>
          <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">person_add</span>
          Tambah Sales Customer
        </h6>
      </div>
      <div class="card-body-premium">
        <form method="POST">
          <div class="row">
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">Nama Toko / Mitra / Personal</label>
              <input type="text" name="nama" class="input-premium" placeholder="Masukkan nama customer..." required>
            </div>
            
            <div class="col-md-5 form-group-premium">
              <label class="form-label-premium">Kategori Customer</label>
              <div class="d-flex align-items-center gap-4 mt-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="kategori" id="kategori_dealer" value="Dealer" required>
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="kategori_dealer">Dealer</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="kategori" id="kategori_installer" value="Installer">
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="kategori_installer">Installer</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="kategori" id="kategori_user" value="User">
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="kategori_user">User</label>
                </div>
              </div>
            </div>
            
            <div class="col-md-3 form-group-premium">
              <label class="form-label-premium">No. Telepon (WhatsApp)</label>
              <input type="text" name="telp" class="input-premium" placeholder="Contoh: 0812345678" required>
            </div>
            
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">Email Customer</label>
              <input type="email" name="email" class="input-premium" placeholder="Contoh: customer@loewix.com">
            </div>
            
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">Kota</label>
              <input type="text" name="kota" class="input-premium" placeholder="Masukkan kota asal customer...">
            </div>
            
            <div class="col-md-12 form-group-premium">
              <label class="form-label-premium">Alamat Lengkap</label>
              <input type="text" name="alamat" class="input-premium" placeholder="Masukkan alamat lengkap toko/mitra...">
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn-submit-premium">
              <span class="material-symbols-outlined">save</span>
              Simpan Customer
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Card Daftar Sales Customer -->
    <div class="card-premium">
      <div class="section-header-premium">
        <h6>
          <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">groups</span>
          Daftar Sales Customer
        </h6>
        <span class="badge bg-light text-dark font-weight-bold" style="font-size: 11px;"><?= mysqli_num_rows($salesData); ?> Customer Terdaftar</span>
      </div>
      
      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th style="width: 60px; text-align: center;">No</th>
              <th style="width: 120px;">Kategori</th>
              <th>Nama Toko / Personal</th>
              <th style="width: 180px;">No. Telepon</th>
              <th>Email</th>
              <th>Alamat</th>
              <th style="width: 150px;">Kota</th>
              <th style="width: 120px; text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($salesData)): 
              $kat = htmlspecialchars($row['kategori'] ?? '');
              $badgeClass = match($kat) {
                'Dealer' => 'badge-dealer',
                'Installer' => 'badge-installer',
                'User' => 'badge-user',
                default => 'badge-default'
              };
              $avatarBg = match($kat) {
                'Dealer' => '#3b82f6',
                'Installer' => '#8b5cf6',
                'User' => '#10b981',
                default => '#64748b'
              };
            ?>
            <tr>
              <td style="text-align: center; font-weight: 600; color: #64748b;"><?= $no++; ?></td>
              <td><span class="category-badge <?= $badgeClass; ?>"><?= $kat; ?></span></td>
              <td>
                <div class="customer-identity-cell">
                  <div class="avatar-initials-table" style="background: <?= $avatarBg; ?>;">
                    <?php 
                      $words = explode(' ', $row['nama'] ?? '');
                      echo strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    ?>
                  </div>
                  <span style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['nama'] ?? ''); ?></span>
                </div>
              </td>
              <td>
                <?php if (!empty($row['telp_pribadi'])): ?>
                <a href="https://wa.me/<?= htmlspecialchars($row['telp_pribadi'] ?? ''); ?>" target="_blank" class="wa-link">
                  <i class="fab fa-whatsapp" style="font-size: 16px;"></i> 
                  <?= htmlspecialchars(preg_replace('/^62/', '0', $row['telp_pribadi'] ?? '')); ?>
                </a>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['email'] ?? '-'); ?></td>
              <td><span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($row['alamat'] ?? '-'); ?></span></td>
              <td><span style="font-weight: 600; color: #475569;"><?= htmlspecialchars($row['kota'] ?? '-'); ?></span></td>
              <td style="text-align: center;">
                <button type="button" class="btn-act btn-act-edit editBtn"
                  data-id="<?= $row['id']; ?>"
                  data-nama="<?= htmlspecialchars($row['nama'] ?? ''); ?>"
                  data-kategori="<?= htmlspecialchars($row['kategori'] ?? ''); ?>"
                  data-telp="<?= htmlspecialchars($row['telp_pribadi'] ?? ''); ?>"
                  data-email="<?= htmlspecialchars($row['email'] ?? ''); ?>"
                  data-alamat="<?= htmlspecialchars($row['alamat'] ?? ''); ?>"
                  data-kota="<?= htmlspecialchars($row['kota'] ?? ''); ?>"
                  data-bs-toggle="modal" data-bs-target="#editModal" title="Ubah Data Customer">
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <a href="?delete_id=<?= $row['id']; ?>" class="btn-act btn-act-delete" onclick="return confirm('Yakin ingin menghapus customer ini?')" title="Hapus Customer">
                  <span class="material-symbols-outlined">delete</span>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($salesData) == 0): ?>
              <tr>
                <td colspan="8" class="text-center text-muted" style="padding: 40px;">Belum ada customer terdaftar.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content modal-content-premium">
          <div class="modal-header modal-header-premium">
            <h5 class="modal-title modal-title-premium">
              <span class="material-symbols-outlined">manage_accounts</span>
              Ubah Data Sales Customer
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body modal-body-premium row">
            <input type="hidden" name="update_id" id="edit_id">
            
            <div class="col-md-5 form-group-premium">
              <label class="form-label-premium">Nama Toko / Personal</label>
              <input type="text" name="edit_nama" id="edit_nama" class="input-premium" required>
            </div>
            
            <div class="col-md-7 form-group-premium">
              <label class="form-label-premium">Kategori Customer</label>
              <div class="d-flex align-items-center gap-4 mt-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="edit_kategori" id="edit_kategori_dealer" value="Dealer" required>
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="edit_kategori_dealer">Dealer</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="edit_kategori" id="edit_kategori_installer" value="Installer">
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="edit_kategori_installer">Installer</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="edit_kategori" id="edit_kategori_user" value="User">
                  <label class="form-check-label font-weight-bold text-sm text-dark" for="edit_kategori_user">User</label>
                </div>
              </div>
            </div>
            
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">No. Telepon</label>
              <input type="text" name="edit_telp" id="edit_telp" class="input-premium" required>
            </div>
            
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">Email</label>
              <input type="text" name="edit_email" id="edit_email" class="input-premium">
            </div>
            
            <div class="col-md-4 form-group-premium">
              <label class="form-label-premium">Kota</label>
              <input type="text" name="edit_kota" id="edit_kota" class="input-premium">
            </div>
            
            <div class="col-md-12 form-group-premium">
              <label class="form-label-premium">Alamat Lengkap</label>
              <input type="text" name="edit_alamat" id="edit_alamat" class="input-premium">
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
