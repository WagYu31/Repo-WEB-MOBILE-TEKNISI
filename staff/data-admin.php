<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

$jabatanUser = isset($_SESSION['jabatan']) ? $_SESSION['jabatan'] : (isset($userData['jabatan']) ? $userData['jabatan'] : '');

if ($jabatanUser !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak. Halaman ini khusus untuk Super Admin.'); window.location.href='index.php';</script>";
    exit;
}

$pageNow = "Data Administrator";

if (isset($_POST['tambah_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $jabatan = 'Admin';
    $date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, jabatan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $password, $jabatan, $date, $date);
    $stmt->execute();
    header("Location: data-admin.php");
    exit;
}

if (isset($_POST['edit_password'])) {
    $id = $_POST['admin_id'];
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("ssi", $password, $date, $id);
    $stmt->execute();
    header("Location: data-admin.php");
    exit;
}

if (isset($_POST['hapus_admin'])) {
    $id = $_POST['admin_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND jabatan = 'Admin'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: data-admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Data Administrator</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    
    <?php include "cek-menu.php"; ?>
    
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        
        <div class="container-fluid py-4">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-2 mb-sm-0 text-uppercase">Data Administrator</h4>
                <div class="d-flex">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahAdminModal"><i class="fa-solid fa-plus me-2"></i>Tambah Admin</button>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Daftar Pengguna Sistem</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Nama</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Username / Email</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jabatan</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Password</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM users WHERE jabatan != 'Super Admin' AND id != 2 ORDER BY jabatan DESC, name ASC";
                            $result = $conn->query($query);
                            
                            while($row = $result->fetch_assoc()) {
                                $badgeColor = ($row['jabatan'] == 'Super Admin') ? 'bg-gradient-success' : 'bg-gradient-info';
                                
                                echo "<tr>";
                                echo "<td><div class='d-flex px-3 py-1'><div class='d-flex flex-column justify-content-center'><h6 class='mb-0 text-sm'>{$row['name']}</h6></div></div></td>";
                                echo "<td class='align-middle text-center'><span class='text-secondary text-sm font-weight-bold'>{$row['email']}</span></td>";
                                echo "<td class='align-middle text-center'><span class='badge badge-sm {$badgeColor}'>{$row['jabatan']}</span></td>";
                                echo "<td class='align-middle text-center'><span class='text-secondary text-sm font-weight-bold'>****</span></td>";
                                echo "<td class='align-middle text-center'>";
                                
                                if($row['jabatan'] === 'Admin') {
                                    echo "<button class='btn btn-link text-secondary mb-0 p-2' onclick='openEditModal({$row['id']})'><i class='fa fa-key text-dark me-2'></i>Ubah Password</button>";
                                    echo "<button class='btn btn-link text-danger mb-0 p-2' onclick='konfirmasiHapus({$row['id']}, \"" . addslashes($row['name']) . "\")'><i class='fa fa-trash text-danger me-2'></i>Hapus</button>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <div class="modal fade" id="tambahAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Administrator Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control p-2 border" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username / Email</label>
                            <input type="text" class="form-control p-2 border" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control p-2 border" name="password" required>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah_admin" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Password Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="admin_id" id="editAdminId">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control p-2 border" name="new_password" required>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="edit_password" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form id="hapusAdminForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="admin_id" id="hapusAdminId">
        <input type="hidden" name="hapus_admin" value="1">
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
    
    <script>
        function openEditModal(id) {
            document.getElementById('editAdminId').value = id;
            var modal = new bootstrap.Modal(document.getElementById('editPasswordModal'));
            modal.show();
        }

        function konfirmasiHapus(id, nama) {
            if (confirm("Apakah Anda yakin ingin menghapus admin " + nama + "?")) {
                document.getElementById('hapusAdminId').value = id;
                document.getElementById('hapusAdminForm').submit();
            }
        }
    </script>
</body>
</html>