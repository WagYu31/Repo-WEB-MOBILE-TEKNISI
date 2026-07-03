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
    
    // Get current photos and delete files
    $getFoto = $conn->prepare("SELECT foto FROM sales_customer WHERE id = ?");
    $getFoto->bind_param("i", $id);
    $getFoto->execute();
    $resFoto = $getFoto->get_result()->fetch_assoc();
    $foto_json = $resFoto['foto'] ?? '';
    $getFoto->close();
    
    if (!empty($foto_json)) {
        $photos = json_decode($foto_json, true);
        if (is_array($photos)) {
            foreach ($photos as $p) {
                @unlink('../uploads/customer/' . $p);
            }
        }
    }

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
    $id_wilayah = intval($_POST['edit_id_wilayah'] ?? 0);
    $telp = preg_replace('/\D/', '', $_POST['edit_telp']);
    
    // Lokasi GPS
    $lat = !empty($_POST['edit_lat']) ? $_POST['edit_lat'] : NULL;
    $lon = !empty($_POST['edit_lon']) ? $_POST['edit_lon'] : NULL;
    $rad = !empty($_POST['edit_radius']) ? $_POST['edit_radius'] : NULL;
    $location_address = !empty($_POST['edit_location_address']) ? $_POST['edit_location_address'] : NULL;

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    // Get current photos
    $getFoto = $conn->prepare("SELECT foto FROM sales_customer WHERE id = ?");
    $getFoto->bind_param("i", $id);
    $getFoto->execute();
    $resFoto = $getFoto->get_result()->fetch_assoc();
    $foto_json = $resFoto['foto'] ?? '';
    $getFoto->close();

    $existing_photos = [];
    if (!empty($foto_json)) {
        $existing_photos = json_decode($foto_json, true);
        if (!is_array($existing_photos)) {
            $existing_photos = [];
        }
    }

    // Process deleted existing photos
    $deleted_photos_str = $_POST['deleted_existing_photos'] ?? '';
    if (!empty($deleted_photos_str)) {
        $deleted_photos = explode(',', $deleted_photos_str);
        foreach ($deleted_photos as $dp) {
            $dp = trim($dp);
            if (in_array($dp, $existing_photos)) {
                @unlink('../uploads/customer/' . $dp);
                $existing_photos = array_diff($existing_photos, [$dp]);
            }
        }
        $existing_photos = array_values($existing_photos);
    }

    // Handle new file uploads
    $new_filenames = [];
    if (isset($_FILES['edit_foto'])) {
        $files = $_FILES['edit_foto'];
        $uploadFileDir = '../uploads/customer/';
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileTmpPath = $files['tmp_name'][$i];
                $fileName = $files['name'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'cust_' . time() . '_' . rand(1000, 9999) . '_edit_' . $i . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $new_filenames[] = $newFileName;
                }
            }
        }
    }

    // Merge existing and new photos
    $merged_photos = array_merge($existing_photos, $new_filenames);
    $merged_photos = array_slice($merged_photos, 0, 10);
    $foto_json_updated = !empty($merged_photos) ? json_encode($merged_photos) : NULL;

    $stmt = $conn->prepare("UPDATE sales_customer SET nama = ?, kategori = ?, telp_pribadi = ?, email = ?, alamat = ?, kota = ?, id_wilayah = ?, foto = ?, lat = ?, lon = ?, rad = ?, alamat_lokasi = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssisssssi", $nama, $kategori, $telp, $email, $alamat, $kota, $id_wilayah, $foto_json_updated, $lat, $lon, $rad, $location_address, $id);
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
    $id_wilayah = intval($_POST['id_wilayah'] ?? 0);
    $telp = preg_replace('/\D/', '', $_POST['telp']);
    
    // Lokasi GPS
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : NULL;
    $lon = !empty($_POST['lon']) ? $_POST['lon'] : NULL;
    $rad = !empty($_POST['radius']) ? $_POST['radius'] : NULL;
    $location_address = !empty($_POST['location_address']) ? $_POST['location_address'] : NULL;

    if (substr($telp, 0, 1) === '0') {
        $telp = '62' . substr($telp, 1);
    } elseif (substr($telp, 0, 1) === '8') {
        $telp = '62' . $telp;
    } elseif (!str_starts_with($telp, '62')) {
        $telp = '62' . $telp;
    }

    // Handle multiple files upload
    $filenames = [];
    if (isset($_FILES['foto'])) {
        $files = $_FILES['foto'];
        $uploadFileDir = '../uploads/customer/';
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileTmpPath = $files['tmp_name'][$i];
                $fileName = $files['name'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'cust_' . time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $filenames[] = $newFileName;
                }
            }
        }
    }
    
    $foto_json = !empty($filenames) ? json_encode($filenames) : NULL;

    $stmt = $conn->prepare("INSERT INTO sales_customer (kategori, nama, telp_pribadi, email, alamat, kota, id_wilayah, foto, lat, lon, rad, alamat_lokasi, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssssssisssss", $kategori, $nama, $telp, $email, $alamat, $kota, $id_wilayah, $foto_json, $lat, $lon, $rad, $location_address);
    $stmt->execute();
    $stmt->close();

    $successMsg = "Customer baru berhasil ditambahkan!";
}

// Ambil data customer beserta wilayah
$salesData = mysqli_query($conn, "
    SELECT c.*, w.nama AS nama_wilayah 
    FROM sales_customer c 
    LEFT JOIN wilayah w ON c.id_wilayah = w.id 
    WHERE c.deleted_at IS NULL 
    ORDER BY c.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include "head.php"; ?>
  <!-- Leaflet Map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    /* ── Premium Styling ── */
    .card-premium {
      background: #fff;
      border: none;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
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
      padding: 36px 40px;
    }

    /* ── Form inputs ── */
    .form-group-premium {
      margin-bottom: 20px;
    }
    
    .form-label-premium {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    
    .input-premium {
      width: 100%;
      height: 48px !important;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px 16px !important;
      font-size: 14px;
      color: #1e293b;
      background-color: #fff;
      transition: all 0.2s ease-in-out;
      box-sizing: border-box;
    }
    
    .input-premium:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
      outline: none;
      background-color: #fff;
    }

    /* ── Maps containers ── */
    #map_create, #map_edit {
      height: 290px;
      width: 100%;
      border-radius: 14px;
      border: 1.5px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
      margin-top: 10px;
    }

    /* ── Drag & Drop Zone ── */
    .dropzone-area {
      border: 2px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      padding: 24px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      user-select: none;
    }
    
    .dropzone-area:hover, .dropzone-area.dragover {
      border-color: #3b82f6;
      background: #f0f7ff;
    }
    
    .dropzone-icon {
      font-size: 32px;
      color: #64748b;
      margin-bottom: 6px;
    }
    
    .dropzone-text {
      font-size: 12.5px;
      font-weight: 600;
      color: #475569;
      margin: 0;
    }

    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
      gap: 12px;
      margin-top: 14px;
    }
    
    .preview-item {
      position: relative;
      width: 80px;
      height: 80px;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px solid #e2e8f0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    
    .preview-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .preview-remove {
      position: absolute;
      top: 3px;
      right: 3px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: rgba(220, 38, 38, 0.95);
      color: #fff;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: none;
      font-weight: 700;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    /* Existing Photos List in Edit */
    .edit-photo-thumb {
      position: relative;
      width: 72px;
      height: 72px;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px solid #e2e8f0;
    }
    .edit-photo-thumb img {
      width: 100%; height: 100%; object-fit: cover;
    }

    /* ── Custom Styled Select arrow ── */
    select.input-premium {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 16px;
      padding-right: 40px !important;
      cursor: pointer;
    }

    /* ── Category Pill Group (Radio Switcher) ── */
    .category-pill-group {
      display: flex;
      gap: 10px;
    }
    
    .category-pill-label {
      cursor: pointer;
      margin: 0;
      flex: 1;
    }
    
    .category-pill-input {
      display: none;
    }
    
    .category-pill-span {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 48px;
      font-size: 12px;
      font-weight: 700;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      color: #64748b;
      background: #fff;
      transition: all 0.2s ease-in-out;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      box-sizing: border-box;
    }
    
    /* Dealer checked style */
    #kategori_dealer:checked + .span-dealer {
      border-color: #3b82f6;
      background: #eff6ff;
      color: #1d4ed8;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.05);
    }
    /* Installer checked style */
    #kategori_installer:checked + .span-installer {
      border-color: #8b5cf6;
      background: #f5f3ff;
      color: #6d28d9;
      box-shadow: 0 4px 12px rgba(139, 92, 246, 0.05);
    }
    /* User checked style */
    #kategori_user:checked + .span-user {
      border-color: #10b981;
      background: #ecfdf5;
      color: #047857;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.05);
    }

    /* ── Avatars in Table ── */
    .avatar-initials-table {
      width: 42px; height: 42px;
      border-radius: 50%;
      color: #fff;
      font-size: 13px; font-weight: 700;
      display: inline-flex; align-items: center; justify-content: center;
      margin-right: 14px;
      vertical-align: middle;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
      cursor: pointer;
      transition: all 0.2s;
    }

    .avatar-initials-table:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
    }
    
    .customer-identity-cell {
      display: flex;
      align-items: center;
      text-align: left;
    }

    /* ── Category Badges ── */
    .category-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 6px 12px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      display: inline-block;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
      border: 1px solid rgba(0,0,0,0.03);
    }
    .badge-dealer { background: #eff6ff; color: #1e40af; border-color: #dbeafe; }
    .badge-installer { background: #faf5ff; color: #6b21a8; border-color: #f3e8ff; }
    .badge-user { background: #ecfdf5; color: #065f46; border-color: #d1fae5; }
    .badge-default { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
 
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
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 18px 20px;
      text-align: left;
    }
    
    .premium-table td {
      padding: 16px 20px;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
      font-size: 13.5px;
      vertical-align: middle;
    }
    
    .premium-table tbody tr {
      transition: all 0.2s ease-in-out;
    }

    .premium-table tbody tr:hover td {
      background-color: #f8fafc;
    }
    
    /* WhatsApp Pill style */
    .wa-pill {
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      color: #065f46;
      font-size: 12.5px; 
      font-weight: 700;
      padding: 6px 12px;
      border-radius: 30px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .wa-pill:hover {
      background: #10b981;
      color: #fff;
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
    }
    .wa-pill i {
      font-size: 15px;
    }

    /* Map Pin Button */
    .btn-map-pin {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      color: #2563eb;
      width: 34px; height: 34px;
      border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.2s;
      cursor: pointer;
    }
    .btn-map-pin:hover {
      background: #2563eb;
      color: #fff;
      transform: scale(1.1);
      box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }
    .btn-map-pin .material-symbols-outlined {
      font-size: 18px;
    }

    /* ── Action Buttons ── */
    .btn-act {
      width: 36px; height: 36px; padding: 0; display: inline-flex;
      align-items: center; justify-content: center; border-radius: 50%;
      border: 1px solid transparent; transition: all 0.2s; cursor: pointer; text-decoration: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn-act:hover { transform: scale(1.1); }
    .btn-act .material-symbols-outlined { font-size: 18px; }
    .btn-act-edit { background: #fffbeb; color: #d97706; border-color: #fef3c7; }
    .btn-act-edit:hover { background: #d97706; color: #fff; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.2); }
    .btn-act-delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; margin-left: 8px; }
    .btn-act-delete:hover { background: #dc2626; color: #fff; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2); }
 
    .btn-submit-premium {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #2563eb 100%);
      color: #fff !important;
      border: none;
      border-radius: 12px;
      padding: 12px 28px;
      font-size: 14px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 6px;
      box-shadow: 0 4px 20px rgba(37, 99, 235, 0.25);
      transition: all 0.22s ease;
      cursor: pointer;
    }
    
    .btn-submit-premium:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(37, 99, 235, 0.4);
    }
    
    .btn-submit-premium:active {
      transform: translateY(0);
    }
    
    .btn-submit-premium .material-symbols-outlined {
      font-size: 18px;
    }

    /* ── Modal Premium Styling ── */
    .modal-content-premium {
      border-radius: 16px;
      border: none;
      overflow: hidden;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    }
    
    .modal-header-premium {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
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
      <!-- Premium Gradient Header -->
      <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2563eb 100%);padding:28px 36px;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-40px;right:-20px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.04);"></div>
          <div style="position:absolute;bottom:-50px;right:100px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.03);"></div>
          <div style="position:absolute;top:10px;right:30px;width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.2);"></div>
          <div style="display:flex;align-items:center;gap:14px;position:relative;z-index:1;">
              <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.12);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.1);">
                  <span class="material-symbols-outlined" style="color:#fff;font-size:22px;">person_add</span>
              </div>
              <div>
                  <h5 style="color:#fff;margin:0;font-size:18px;font-weight:700;letter-spacing:-0.3px;">Tambah Sales Customer</h5>
                  <p style="color:rgba(255,255,255,0.6);margin:0;font-size:12px;margin-top:2px;">Daftarkan toko, mitra, atau installer baru beserta wilayah kerjanya</p>
              </div>
          </div>
      </div>

      <div class="card-body-premium">
        <form method="POST" enctype="multipart/form-data" id="createCustomerForm">
          <div class="row">
            
            <!-- LEFT COLUMN: Form Fields -->
            <div class="col-lg-7" style="border-right: 1px solid #f1f5f9; padding-right: 32px;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;">
                  <div style="width:3px;height:16px;background:#3b82f6;border-radius:2px;"></div>
                  <span style="font-size:12px;font-weight:800;color:#1e293b;text-transform: uppercase; letter-spacing: 0.05em;">Informasi Customer</span>
              </div>

              <div class="row">
                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">store</span> Nama Toko / Mitra / Personal
                  </label>
                  <input type="text" name="nama" class="input-premium" placeholder="Masukkan nama customer..." required>
                </div>
                
                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">category</span> Kategori Customer
                  </label>
                  <div class="category-pill-group">
                    <label class="category-pill-label" for="kategori_dealer">
                      <input class="category-pill-input" type="radio" name="kategori" id="kategori_dealer" value="Dealer" required checked>
                      <span class="category-pill-span span-dealer">Dealer</span>
                    </label>
                    <label class="category-pill-label" for="kategori_installer">
                      <input class="category-pill-input" type="radio" name="kategori" id="kategori_installer" value="Installer">
                      <span class="category-pill-span span-installer">Installer</span>
                    </label>
                    <label class="category-pill-label" for="kategori_user">
                      <input class="category-pill-input" type="radio" name="kategori" id="kategori_user" value="User">
                      <span class="category-pill-span span-user">User</span>
                    </label>
                  </div>
                </div>

                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">map</span> Wilayah Customer
                  </label>
                  <select name="id_wilayah" class="input-premium" required>
                    <option value="">-- Pilih Wilayah --</option>
                    <?php 
                    $wQuery = mysqli_query($conn, "SELECT * FROM wilayah WHERE deleted_at IS NULL ORDER BY nama ASC");
                    while ($w = mysqli_fetch_assoc($wQuery)) {
                        echo "<option value='{$w['id']}'>" . htmlspecialchars($w['nama']) . "</option>";
                    }
                    ?>
                  </select>
                </div>
                
                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">call</span> No. Telepon (WhatsApp)
                  </label>
                  <input type="text" name="telp" class="input-premium" placeholder="Contoh: 0812345678" required>
                </div>
                
                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">mail</span> Email Customer
                  </label>
                  <input type="email" name="email" class="input-premium" placeholder="Contoh: customer@loewix.com">
                </div>
                
                <div class="col-md-6 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">location_city</span> Kota
                  </label>
                  <input type="text" name="kota" class="input-premium" placeholder="Masukkan kota asal customer...">
                </div>

                <!-- Drag & Drop Multiple Photos -->
                <div class="col-md-12 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">image</span> Foto Dokumentasi Toko / Gudang / Pabrik (Maksimal 5 Foto)
                  </label>
                  <div class="dropzone-area" id="dropzone_create">
                    <span class="material-symbols-outlined dropzone-icon">cloud_upload</span>
                    <p class="dropzone-text">Drag &amp; drop file foto di sini, atau klik untuk memilih</p>
                    <input type="file" id="foto_input_create" name="foto[]" multiple accept="image/*" class="d-none">
                  </div>
                  <div class="preview-grid" id="preview_grid_create"></div>
                </div>
                
                <div class="col-md-12 form-group-premium">
                  <label class="form-label-premium">
                    <span class="material-symbols-outlined" style="font-size:16px; color:#3b82f6;">home_pin</span> Alamat Lengkap
                  </label>
                  <input type="text" name="alamat" class="input-premium" placeholder="Masukkan alamat lengkap toko/mitra...">
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: Location Map -->
            <div class="col-lg-5" style="padding-left: 32px;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;">
                  <div style="width:3px;height:16px;background:#10b981;border-radius:2px;"></div>
                  <span style="font-size:12px;font-weight:800;color:#1e293b;text-transform: uppercase; letter-spacing: 0.05em;">Lokasi Koordinat Toko (Geofence)</span>
              </div>

              <div class="form-group-premium">
                <label class="form-label-premium">
                  <span class="material-symbols-outlined" style="font-size:16px; color:#10b981;">search</span> Cari Alamat / Koordinat
                </label>
                <div class="d-flex gap-2">
                  <input type="text" id="gmap_search" class="input-premium" placeholder="Contoh: Jawa Timur atau -6.175, 106.827...">
                  <button type="button" id="gmap_search_btn" class="btn bg-gradient-info text-white font-weight-bold" style="border-radius:10px; padding: 12px 18px; font-size:11px; display:inline-flex; align-items:center; gap:4px; margin-bottom:0;">
                    <span class="material-symbols-outlined" style="font-size:16px;">search</span>CARI
                  </button>
                </div>
              </div>

              <!-- Leaflet Map create -->
              <div id="map_create"></div>

              <div class="row g-2 mt-3">
                <div class="col-5">
                  <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Latitude</label>
                  <input type="text" id="lat_display" class="input-premium" placeholder="-6.xxxxx" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                </div>
                <div class="col-5">
                  <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Longitude</label>
                  <input type="text" id="lon_display" class="input-premium" placeholder="106.xxxxx" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                </div>
                <div class="col-2">
                  <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Radius (m)</label>
                  <input type="number" id="radius_input" class="input-premium" value="100" style="font-size:12px; padding: 8px 6px !important; text-align:center;">
                </div>
              </div>

              <!-- Hidden parameters to submit -->
              <input type="hidden" id="lat" name="lat">
              <input type="hidden" id="lon" name="lon">
              <input type="hidden" id="radius" name="radius" value="100">
              <input type="hidden" id="location_address" name="location_address">
            </div>

          </div>
          <div class="mt-3">
            <button type="submit" class="btn-submit-premium" style="width: 100%; justify-content: center;">
              <span class="material-symbols-outlined">save</span>
              Simpan Customer &amp; Koordinat Lokasi
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Card Daftar Sales Customer -->
    <div class="card-premium">
      <!-- Premium Gradient Header -->
      <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2563eb 100%);padding:20px 28px;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-40px;right:-20px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.04);"></div>
          <div style="display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;">
              <div style="display:flex;align-items:center;gap:12px;">
                  <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.12);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.1);">
                      <span class="material-symbols-outlined" style="color:#fff;font-size:18px;">groups</span>
                  </div>
                  <div>
                      <h5 style="color:#fff;margin:0;font-size:15px;font-weight:700;letter-spacing:-0.2px;">Daftar Sales Customer</h5>
                  </div>
              </div>
              <span class="badge bg-light text-dark font-weight-bold" style="font-size: 11px; padding: 6px 14px; border-radius: 10px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.08);"><?= mysqli_num_rows($salesData); ?> Customer Terdaftar</span>
          </div>
      </div>
      
      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th style="width: 60px; text-align: center;">No</th>
              <th style="width: 130px;">Kategori</th>
              <th>Nama Toko / Personal</th>
              <th style="width: 160px;">Wilayah</th>
              <th style="width: 170px;">No. Telepon</th>
              <th>Email</th>
              <th>Alamat</th>
              <th style="width: 140px;">Kota</th>
              <th style="width: 50px; text-align: center;">Peta</th>
              <th style="width: 130px; text-align: center;">Aksi</th>
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
              
              // Parse multiple photos JSON
              $photos = [];
              $foto_json = $row['foto'] ?? '';
              if (!empty($foto_json)) {
                  $photos = json_decode($foto_json, true);
                  if (!is_array($photos)) {
                      $photos = [];
                  }
              }
              $firstPhoto = !empty($photos) ? $photos[0] : '';
              
              // Wilayah Colorful Badges
              $regionName = $row['nama_wilayah'] ?? 'Tanpa Wilayah';
              if (stripos($regionName, 'Jabodetabek') !== false) {
                  $wBadge = 'background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: #fff;';
              } elseif (stripos($regionName, 'Jawa Timur') !== false) {
                  $wBadge = 'background: linear-gradient(135deg, #7c2d12, #ea580c); color: #fff;';
              } elseif (stripos($regionName, 'Jawa Tengah') !== false) {
                  $wBadge = 'background: linear-gradient(135deg, #065f46, #10b981); color: #fff;';
              } elseif (stripos($regionName, 'Jawa Barat') !== false) {
                  $wBadge = 'background: linear-gradient(135deg, #5b21b6, #8b5cf6); color: #fff;';
              } else {
                  $wBadge = 'background: linear-gradient(135deg, #374151, #4b5563); color: #fff;';
              }
            ?>
            <tr>
              <td style="text-align: center; font-weight: 700; color: #64748b; font-size:12px;"><?= $no++; ?></td>
              <td><span class="category-badge <?= $badgeClass; ?>"><?= $kat; ?></span></td>
              <td>
                <div class="customer-identity-cell">
                  <!-- Gallery Trigger Avatar -->
                  <?php if (!empty($firstPhoto) && file_exists("../uploads/customer/" . $firstPhoto)): ?>
                    <div class="avatar-initials-table openGalleryBtn" 
                         style="background: <?= $avatarBg; ?>; overflow: hidden; padding: 0;"
                         data-photos='<?= htmlspecialchars(json_encode($photos)); ?>'
                         data-name="<?= htmlspecialchars($row['nama'] ?? ''); ?>">
                      <img src="../uploads/customer/<?= htmlspecialchars($firstPhoto); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                  <?php else: ?>
                    <div class="avatar-initials-table" style="background: <?= $avatarBg; ?>; cursor: default;">
                      <?php 
                        $words = explode(' ', $row['nama'] ?? '');
                        echo strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                      ?>
                    </div>
                  <?php endif; ?>
                  <span style="font-weight: 700; color: #0f172a; font-size:13.5px;"><?= htmlspecialchars($row['nama'] ?? ''); ?></span>
                </div>
              </td>
              <td>
                <span class="badge text-capitalize" style="font-size: 9px; padding: 4px 10px; font-weight: 700; letter-spacing: 0.05em; border-radius:30px; text-transform: uppercase; <?= $wBadge; ?>">
                  <?= htmlspecialchars($regionName); ?>
                </span>
              </td>
              <td>
                <?php if (!empty($row['telp_pribadi'])): ?>
                <a href="https://wa.me/<?= htmlspecialchars($row['telp_pribadi'] ?? ''); ?>" target="_blank" class="wa-pill">
                  <i class="fab fa-whatsapp"></i> 
                  <?= htmlspecialchars(preg_replace('/^62/', '0', $row['telp_pribadi'] ?? '')); ?>
                </a>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td style="color: #64748b; font-size:12.5px; font-family: monospace;"><?= htmlspecialchars($row['email'] ?? '-'); ?></td>
              <td><span style="font-size: 12px; color: #64748b; font-weight:500; line-height: 1.4; display:block; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['alamat'] ?? ''); ?>"><?= htmlspecialchars($row['alamat'] ?? '-'); ?></span></td>
              <td><span style="font-weight: 700; color: #475569; font-size:12.5px;"><?= htmlspecialchars($row['kota'] ?? '-'); ?></span></td>
              <td style="text-align: center;">
                <?php if (!empty($row['lat']) && !empty($row['lon'])): ?>
                  <button type="button" class="btn-map-pin openMapModalBtn" 
                          data-lat="<?= htmlspecialchars($row['lat']); ?>"
                          data-lon="<?= htmlspecialchars($row['lon']); ?>"
                          data-rad="<?= htmlspecialchars($row['rad'] ?? '100'); ?>"
                          data-name="<?= htmlspecialchars($row['nama'] ?? ''); ?>"
                          data-alamat="<?= htmlspecialchars($row['alamat_lokasi'] ?? ''); ?>"
                          title="Lihat Peta Lokasi Toko">
                    <span class="material-symbols-outlined">location_on</span>
                  </button>
                <?php else: ?>
                  <span class="material-symbols-outlined text-muted" style="font-size: 18px;" title="Lokasi Belum Diset">location_off</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <button type="button" class="btn-act btn-act-edit editBtn"
                  data-id="<?= $row['id']; ?>"
                  data-nama="<?= htmlspecialchars($row['nama'] ?? ''); ?>"
                  data-kategori="<?= htmlspecialchars($row['kategori'] ?? ''); ?>"
                  data-telp="<?= htmlspecialchars($row['telp_pribadi'] ?? ''); ?>"
                  data-email="<?= htmlspecialchars($row['email'] ?? ''); ?>"
                  data-alamat="<?= htmlspecialchars($row['alamat'] ?? ''); ?>"
                  data-kota="<?= htmlspecialchars($row['kota'] ?? ''); ?>"
                  data-foto='<?= htmlspecialchars(json_encode($photos)); ?>'
                  data-id-wilayah="<?= $row['id_wilayah']; ?>"
                  data-lat="<?= htmlspecialchars($row['lat'] ?? ''); ?>"
                  data-lon="<?= htmlspecialchars($row['lon'] ?? ''); ?>"
                  data-rad="<?= htmlspecialchars($row['rad'] ?? ''); ?>"
                  data-alamat-lokasi="<?= htmlspecialchars($row['alamat_lokasi'] ?? ''); ?>"
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
                <td colspan="10" class="text-center text-muted" style="padding: 40px;">Belum ada customer terdaftar.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <form method="POST" class="modal-content modal-content-premium" enctype="multipart/form-data" id="editCustomerForm">
          <div class="modal-header modal-header-premium">
            <h5 class="modal-title modal-title-premium">
              <span class="material-symbols-outlined">manage_accounts</span>
              Ubah Data Sales Customer
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body modal-body-premium">
            <div class="row">
              
              <!-- EDIT LEFT SIDE: Details -->
              <div class="col-lg-7" style="border-right: 1px solid #f1f5f9; padding-right: 28px;">
                <input type="hidden" name="update_id" id="edit_id">
                
                <div class="row">
                  <div class="col-md-6 form-group-premium">
                    <label class="form-label-premium">Nama Toko / Personal</label>
                    <input type="text" name="edit_nama" id="edit_nama" class="input-premium" required>
                  </div>
                  
                  <div class="col-md-6 form-group-premium">
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

                  <div class="col-md-6 form-group-premium">
                    <label class="form-label-premium">Wilayah Customer</label>
                    <select name="edit_id_wilayah" id="edit_id_wilayah" class="input-premium" required>
                      <option value="">-- Pilih Wilayah --</option>
                      <?php 
                      $wQuery2 = mysqli_query($conn, "SELECT * FROM wilayah WHERE deleted_at IS NULL ORDER BY nama ASC");
                      while ($w2 = mysqli_fetch_assoc($wQuery2)) {
                          echo "<option value='{$w2['id']}'>" . htmlspecialchars($w2['nama']) . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                  
                  <div class="col-md-6 form-group-premium">
                    <label class="form-label-premium">No. Telepon</label>
                    <input type="text" name="edit_telp" id="edit_telp" class="input-premium" required>
                  </div>
                  
                  <div class="col-md-6 form-group-premium">
                    <label class="form-label-premium">Email</label>
                    <input type="text" name="edit_email" id="edit_email" class="input-premium">
                  </div>
                  
                  <div class="col-md-6 form-group-premium">
                    <label class="form-label-premium">Kota</label>
                    <input type="text" name="edit_kota" id="edit_kota" class="input-premium">
                  </div>

                  <div class="col-md-12 form-group-premium">
                    <label class="form-label-premium">Alamat Lengkap</label>
                    <input type="text" name="edit_alamat" id="edit_alamat" class="input-premium">
                  </div>

                  <!-- Existing Photos -->
                  <div class="col-md-12 form-group-premium mt-3">
                    <label class="form-label-premium">Foto Dokumentasi Saat Ini (Klik ❌ untuk Menghapus)</label>
                    <div class="d-flex flex-wrap gap-3 mb-3" id="edit_existing_photos_container">
                      <!-- preview -->
                    </div>
                    <input type="hidden" name="deleted_existing_photos" id="deleted_existing_photos">
                    
                    <label class="form-label-premium">Tambah Foto Dokumentasi Baru</label>
                    <div class="dropzone-area" id="dropzone_edit">
                      <span class="material-symbols-outlined dropzone-icon">cloud_upload</span>
                      <p class="dropzone-text">Drag &amp; drop file baru di sini, atau klik untuk memilih</p>
                      <input type="file" id="foto_input_edit" name="edit_foto[]" multiple accept="image/*" class="d-none">
                    </div>
                    <div class="preview-grid" id="preview_grid_edit"></div>
                  </div>
                </div>
              </div>

              <!-- EDIT RIGHT SIDE: Location map -->
              <div class="col-lg-5" style="padding-left: 28px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;">
                    <div style="width:3px;height:16px;background:#10b981;border-radius:2px;"></div>
                    <span style="font-size:12px;font-weight:800;color:#1e293b;text-transform: uppercase; letter-spacing: 0.05em;">Edit Koordinat Toko (Geofence)</span>
                </div>

                <div class="form-group-premium">
                  <label class="form-label-premium">Cari Alamat / Koordinat Baru</label>
                  <div class="d-flex gap-2">
                    <input type="text" id="gmap_search_edit" class="input-premium" placeholder="Contoh: Surabaya atau -7.250, 112.750...">
                    <button type="button" id="gmap_search_btn_edit" class="btn bg-gradient-info text-white font-weight-bold" style="border-radius:10px; padding: 12px 18px; font-size:11px; display:inline-flex; align-items:center; gap:4px; margin-bottom:0;">
                      <span class="material-symbols-outlined" style="font-size:16px;">search</span>CARI
                    </button>
                  </div>
                </div>

                <div id="map_edit"></div>

                <div class="row g-2 mt-3">
                  <div class="col-5">
                    <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Latitude</label>
                    <input type="text" id="edit_lat_display" class="input-premium" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                  </div>
                  <div class="col-5">
                    <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Longitude</label>
                    <input type="text" id="edit_lon_display" class="input-premium" style="font-family: monospace; font-size:12px; padding: 8px 12px !important; background:#f8fafc;" readonly>
                  </div>
                  <div class="col-2">
                    <label class="form-label-premium" style="font-size: 9px; color:#64748b;">Radius (m)</label>
                    <input type="number" id="edit_radius_input" class="input-premium" style="font-size:12px; padding: 8px 6px !important; text-align:center;">
                  </div>
                </div>

                <input type="hidden" id="edit_lat" name="edit_lat">
                <input type="hidden" id="edit_lon" name="edit_lon">
                <input type="hidden" id="edit_radius" name="edit_radius">
                <input type="hidden" id="edit_location_address" name="edit_location_address">
              </div>

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

    <!-- Gallery slideshow modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; background:#0f172a;">
          <div class="modal-header border-0 text-white" style="padding: 16px 24px; background: rgba(255,255,255,0.03);">
            <h6 class="modal-title text-white font-weight-bold" id="galleryTitle">Dokumentasi Toko</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(1) brightness(2);"></button>
          </div>
          <div class="modal-body text-center" style="padding: 30px;">
            <!-- Carousel -->
            <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner" id="galleryCarouselInner" style="max-height: 480px; border-radius:12px; overflow:hidden; border: 2px solid rgba(255,255,255,0.1);">
                <!-- slides inject -->
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- View map coordinates modal -->
    <div class="modal fade" id="viewMapModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px; overflow:hidden; border:none; background:#fff;">
          <div class="modal-header border-0 bg-gradient-dark text-white" style="padding: 18px 24px;">
            <h6 class="modal-title text-white font-weight-bold" id="viewMapTitle">Lokasi Geofence Toko</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 24px;">
            <div id="map_view_container" style="height: 380px; border-radius: 12px; border: 1.5px solid #e2e8f0; width: 100%;"></div>
            <div class="mt-3 p-3 bg-light rounded-3" style="font-size: 13px; color: #475569;">
              <div style="font-weight: 700; color: #1e293b; margin-bottom:4px;">Alamat Peta Geocoder:</div>
              <span id="viewMapAddress">Sedang memuat alamat...</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include "floating-menu.php"; ?>
    <?php include "footer.php"; ?>
  </div>
</main>

<?php include "js-include.php"; ?>

<!-- Leaflet Map JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  // ── Drag & Drop Uploader Script ──
  function setupDragAndDrop(dropzoneId, inputId, previewGridId, maxFiles = 5) {
    const dropzone = document.getElementById(dropzoneId);
    const input = document.getElementById(inputId);
    const previewGrid = document.getElementById(previewGridId);
    let selectedFiles = [];

    // Trigger click on dropzone
    dropzone.addEventListener('click', () => input.click());

    // Drag events
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
      }, false);
    });

    // Handle dropped files
    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      handleFiles(files);
    });

    // Handle file selection
    input.addEventListener('change', () => {
      handleFiles(input.files);
    });

    function handleFiles(files) {
      const filesArr = Array.from(files).filter(file => file.type.startsWith('image/'));
      
      if (selectedFiles.length + filesArr.length > maxFiles) {
        alert(`Maksimal hanya dapat mengunggah ${maxFiles} foto dokumentasi.`);
        return;
      }

      filesArr.forEach(file => {
        selectedFiles.push(file);
        
        const reader = new FileReader();
        reader.onload = (e) => {
          const previewItem = document.createElement('div');
          previewItem.className = 'preview-item';
          
          const img = document.createElement('img');
          img.src = e.target.result;
          
          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'preview-remove';
          removeBtn.innerHTML = '❌';
          removeBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            const idx = selectedFiles.indexOf(file);
            if (idx > -1) {
              selectedFiles.splice(idx, 1);
            }
            previewItem.remove();
            updateFileInput();
          });
          
          previewItem.appendChild(img);
          previewItem.appendChild(removeBtn);
          previewGrid.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
      });

      updateFileInput();
    }

    function updateFileInput() {
      const dt = new DataTransfer();
      selectedFiles.forEach(file => dt.items.add(file));
      input.files = dt.files;
    }

    return {
      reset: () => {
        selectedFiles = [];
        previewGrid.innerHTML = '';
        input.value = '';
      }
    };
  }

  // Setup uploader zones
  const uploaderCreate = setupDragAndDrop('dropzone_create', 'foto_input_create', 'preview_grid_create', 5);
  const uploaderEdit = setupDragAndDrop('dropzone_edit', 'foto_input_edit', 'preview_grid_edit', 5);

  // ── Gallery Slideshow Logic ──
  document.querySelectorAll('.openGalleryBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const photos = JSON.parse(btn.dataset.photos || '[]');
      const name = btn.dataset.name;
      
      document.getElementById('galleryTitle').innerText = 'Dokumentasi Foto: ' + name;
      const carouselInner = document.getElementById('galleryCarouselInner');
      carouselInner.innerHTML = '';
      
      photos.forEach((photo, idx) => {
        const item = document.createElement('div');
        item.className = 'carousel-item' + (idx === 0 ? ' active' : '');
        
        const img = document.createElement('img');
        img.src = '../uploads/customer/' + photo;
        img.className = 'd-block w-100';
        img.style.height = '420px';
        img.style.objectFit = 'contain';
        img.style.background = '#020617';
        
        item.appendChild(img);
        carouselInner.appendChild(item);
      });
      
      const galleryModal = new bootstrap.Modal(document.getElementById('galleryModal'));
      galleryModal.show();
    });
  });

  // ── View Map Modal Logic ──
  let mapViewInstance = null;
  let markerView = null;
  let circleView = null;

  document.querySelectorAll('.openMapModalBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      const lat = parseFloat(btn.dataset.lat);
      const lon = parseFloat(btn.dataset.lon);
      const rad = parseInt(btn.dataset.rad) || 100;
      const name = btn.dataset.name;
      const address = btn.dataset.alamat || "Alamat lengkap tidak tertera.";

      document.getElementById('viewMapTitle').innerText = 'Lokasi Geofence: ' + name;
      document.getElementById('viewMapAddress').innerText = address;

      const latlng = L.latLng(lat, lon);

      const viewMapModal = new bootstrap.Modal(document.getElementById('viewMapModal'));
      viewMapModal.show();

      // Initialize map on modal shown
      setTimeout(() => {
        if (!mapViewInstance) {
          mapViewInstance = L.map('map_view_container').setView(latlng, 16);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
          }).addTo(mapViewInstance);

          markerView = L.marker(latlng).addTo(mapViewInstance);
          circleView = L.circle(latlng, { radius: rad, color: '#2563eb', fillColor: '#2563eb', fillOpacity: 0.15 }).addTo(mapViewInstance);
        } else {
          mapViewInstance.setView(latlng, 16);
          markerView.setLatLng(latlng);
          circleView.setLatLng(latlng).setRadius(rad);
          mapViewInstance.invalidateSize();
        }
      }, 350);
    });
  });

  // ── Map Create Logic ──
  const defaultLat = -6.13037113;
  const defaultLon = 106.75144230;
  const defaultRad = 100;

  const mapCreate = L.map('map_create').setView([defaultLat, defaultLon], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(mapCreate);

  let markerCreate = L.marker([defaultLat, defaultLon], { draggable: true }).addTo(mapCreate);
  let circleCreate = L.circle([defaultLat, defaultLon], { radius: defaultRad, color: '#10b981', fillColor: '#10b981', fillOpacity: 0.15 }).addTo(mapCreate);

  function updateCreateMapData(latlng, rad) {
    const r = parseInt(rad) || defaultRad;
    markerCreate.setLatLng(latlng);
    circleCreate.setLatLng(latlng).setRadius(r);
    mapCreate.setView(latlng, 16);

    document.getElementById('lat').value = latlng.lat;
    document.getElementById('lon').value = latlng.lng;
    document.getElementById('lat_display').value = latlng.lat.toFixed(6);
    document.getElementById('lon_display').value = latlng.lng.toFixed(6);
    document.getElementById('radius').value = r;
    document.getElementById('radius_input').value = r;

    // Nominatim geocode reverse
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=id`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('location_address').value = data?.display_name || '';
      })
      .catch(() => {
        document.getElementById('location_address').value = '';
      });
  }

  mapCreate.on('click', function(e) {
    updateCreateMapData(e.latlng, document.getElementById('radius_input').value);
  });

  markerCreate.on('dragend', function() {
    updateCreateMapData(markerCreate.getLatLng(), document.getElementById('radius_input').value);
  });

  document.getElementById('radius_input').addEventListener('input', function() {
    updateCreateMapData(markerCreate.getLatLng(), this.value);
  });

  document.getElementById('gmap_search_btn').addEventListener('click', function() {
    const query = document.getElementById('gmap_search').value.trim();
    if (query === "") return;

    const coordsRegex = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/;
    if (coordsRegex.test(query)) {
      const parts = query.split(',');
      updateCreateMapData(L.latLng(parseFloat(parts[0]), parseFloat(parts[1])), document.getElementById('radius_input').value);
    } else {
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id&accept-language=id`)
        .then(res => res.json())
        .then(data => {
          if (data && data.length > 0) {
            updateCreateMapData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('radius_input').value);
          } else {
            alert("Lokasi tidak ditemukan.");
          }
        });
    }
  });

  // Init create map
  updateCreateMapData(L.latLng(defaultLat, defaultLon), defaultRad);


  // ── Map Edit Modal Logic ──
  let mapEditInstance = null;
  let markerEdit = null;
  let circleEdit = null;

  const editModalEl = document.getElementById('editModal');
  editModalEl.addEventListener('shown.bs.modal', function () {
      const latVal = parseFloat(document.getElementById('edit_lat').value) || defaultLat;
      const lonVal = parseFloat(document.getElementById('edit_lon').value) || defaultLon;
      const radVal = parseInt(document.getElementById('edit_radius').value) || defaultRad;
      
      const latlng = L.latLng(latVal, lonVal);
      
      if (!mapEditInstance) {
          mapEditInstance = L.map('map_edit').setView(latlng, 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
          }).addTo(mapEditInstance);
          
          markerEdit = L.marker(latlng, { draggable: true }).addTo(mapEditInstance);
          circleEdit = L.circle(latlng, { radius: radVal, color: '#10b981', fillColor: '#10b981', fillOpacity: 0.15 }).addTo(mapEditInstance);
          
          mapEditInstance.on('click', function(e) {
            updateEditMapData(e.latlng, document.getElementById('edit_radius_input').value);
          });
          
          markerEdit.on('dragend', function() {
            updateEditMapData(markerEdit.getLatLng(), document.getElementById('edit_radius_input').value);
          });
      } else {
          mapEditInstance.setView(latlng, 15);
          markerEdit.setLatLng(latlng);
          circleEdit.setLatLng(latlng).setRadius(radVal);
          mapEditInstance.invalidateSize();
      }
  });

  function updateEditMapData(latlng, rad) {
      const r = parseInt(rad) || defaultRad;
      markerEdit.setLatLng(latlng);
      circleEdit.setLatLng(latlng).setRadius(r);
      
      document.getElementById('edit_lat').value = latlng.lat;
      document.getElementById('edit_lon').value = latlng.lng;
      document.getElementById('edit_lat_display').value = latlng.lat.toFixed(6);
      document.getElementById('edit_lon_display').value = latlng.lng.toFixed(6);
      document.getElementById('edit_radius').value = r;
      document.getElementById('edit_radius_input').value = r;
      
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=id`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('edit_location_address').value = data?.display_name || '';
        })
        .catch(() => {
          document.getElementById('edit_location_address').value = '';
        });
  }

  document.getElementById('edit_radius_input').addEventListener('input', function() {
    if (markerEdit) {
      updateEditMapData(markerEdit.getLatLng(), this.value);
    }
  });

  document.getElementById('gmap_search_btn_edit').addEventListener('click', function() {
    const query = document.getElementById('gmap_search_edit').value.trim();
    if (query === "" || !mapEditInstance) return;

    const coordsRegex = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/;
    if (coordsRegex.test(query)) {
      const parts = query.split(',');
      updateEditMapData(L.latLng(parseFloat(parts[0]), parseFloat(parts[1])), document.getElementById('edit_radius_input').value);
    } else {
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id&accept-language=id`)
        .then(res => res.json())
        .then(data => {
          if (data && data.length > 0) {
            updateEditMapData(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), document.getElementById('edit_radius_input').value);
          } else {
            alert("Lokasi tidak ditemukan.");
          }
        });
    }
  });

  // ── Edit Modal Population ──
  let deletedExistingPhotos = [];
  document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
      deletedExistingPhotos = [];
      document.getElementById('deleted_existing_photos').value = '';
      uploaderEdit.reset();

      document.getElementById('edit_id').value = btn.dataset.id;
      document.getElementById('edit_nama').value = btn.dataset.nama;
      document.getElementById('edit_telp').value = btn.dataset.telp;
      document.getElementById('edit_email').value = btn.dataset.email;
      document.getElementById('edit_alamat').value = btn.dataset.alamat;
      document.getElementById('edit_kota').value = btn.dataset.kota;
      document.getElementById('edit_id_wilayah').value = btn.dataset.idWilayah || "";

      // GPS Data Populate
      document.getElementById('edit_lat').value = btn.dataset.lat || "";
      document.getElementById('edit_lon').value = btn.dataset.lon || "";
      document.getElementById('edit_radius').value = btn.dataset.rad || "100";
      document.getElementById('edit_radius_input').value = btn.dataset.rad || "100";
      document.getElementById('edit_location_address').value = btn.dataset.alamatLokasi || "";
      
      const latVal = parseFloat(btn.dataset.lat);
      const lonVal = parseFloat(btn.dataset.lon);
      document.getElementById('edit_lat_display').value = isNaN(latVal) ? "" : latVal.toFixed(6);
      document.getElementById('edit_lon_display').value = isNaN(lonVal) ? "" : lonVal.toFixed(6);

      // Existing photos preview with delete
      const existingContainer = document.getElementById('edit_existing_photos_container');
      existingContainer.innerHTML = '';
      
      const photos = JSON.parse(btn.dataset.foto || '[]');
      if (photos.length > 0) {
        photos.forEach(photo => {
          const wrapper = document.createElement('div');
          wrapper.className = 'edit-photo-thumb';
          
          const img = document.createElement('img');
          img.src = '../uploads/customer/' + photo;
          
          const delBtn = document.createElement('button');
          delBtn.type = 'button';
          delBtn.className = 'preview-remove';
          delBtn.innerHTML = '❌';
          delBtn.addEventListener('click', () => {
            deletedExistingPhotos.push(photo);
            document.getElementById('deleted_existing_photos').value = deletedExistingPhotos.join(',');
            wrapper.remove();
          });
          
          wrapper.appendChild(img);
          wrapper.appendChild(delBtn);
          existingContainer.appendChild(wrapper);
        });
      } else {
        existingContainer.innerHTML = '<span class="text-muted" style="font-size: 12px;">Belum ada dokumentasi foto.</span>';
      }

      const kategori = btn.dataset.kategori;
      document.querySelectorAll('input[name="edit_kategori"]').forEach(radio => {
        radio.checked = (radio.value === kategori);
      });
    });
  });
</script>
</body>
</html>
