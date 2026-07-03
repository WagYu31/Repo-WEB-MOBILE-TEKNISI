<?php
include "conn.php";
include "session.php";
include "get-user-data.php";

$jabatanUser = isset($_SESSION['jabatan']) ? $_SESSION['jabatan'] : (isset($userData['jabatan']) ? $userData['jabatan'] : '');

if ($jabatanUser !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak. Halaman ini khusus untuk Super Admin.'); window.location.href='index.php';</script>";
    exit;
}

$pageNow = "Kelola Akses Menu";

// Auto create table if not exists
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_menu_access'");
if ($checkTable && mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE user_menu_access (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        menu_key VARCHAR(50) NOT NULL,
        is_allowed TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_menu (user_id, menu_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $createTable);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kelola Akses Menu</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
        }
        .premium-card {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: none;
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-200">
    
    <?php include "cek-menu.php"; ?>
    
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>
        
        <div class="container-fluid py-4">
            <div class="d-sm-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-2 mb-sm-0 text-uppercase font-weight-bold">Kelola Akses Menu</h4>
                <p class="text-secondary text-sm mb-0">Atur hak akses menu navigasi untuk masing-masing user secara dinamis</p>
            </div>
            
            <div class="card premium-card mt-4">
                <div class="card-header border-bottom bg-transparent py-3">
                    <h5 class="mb-0 font-weight-bold text-dark">Daftar Pengguna &amp; Hak Akses</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Nama User</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email / Username</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jabatan / Role</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Ambil semua pengguna kecuali ID 2 (Super Admin default) atau yang jabatannya Super Admin (Super Admin selalu punya akses penuh)
                            $query = "SELECT * FROM users WHERE jabatan != 'Super Admin' ORDER BY jabatan DESC, name ASC";
                            $result = $conn->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $badgeColor = 'bg-gradient-info';
                                    if ($row['jabatan'] === 'Admin') {
                                        $badgeColor = 'bg-gradient-dark';
                                    } elseif (str_contains($row['jabatan'], 'Sales')) {
                                        $badgeColor = 'bg-gradient-primary';
                                    } elseif ($row['jabatan'] === 'Teknisi') {
                                        $badgeColor = 'bg-gradient-success';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td><div class='d-flex px-4 py-1'><div class='d-flex flex-column justify-content-center'><h6 class='mb-0 text-sm font-weight-bold text-dark'>{$row['name']}</h6></div></div></td>";
                                    echo "<td><span class='text-secondary text-sm font-weight-bold'>{$row['email']}</span></td>";
                                    echo "<td><span class='badge badge-sm {$badgeColor}'>{$row['jabatan']}</span></td>";
                                    echo "<td class='align-middle text-center'>";
                                    echo "<button class='btn btn-sm btn-outline-primary mb-0 px-3 py-1.5 font-weight-bold' onclick='loadPermissions({$row['id']})'><i class='fa-solid fa-user-shield me-1.5'></i>Kelola Akses</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-4 text-secondary text-sm'>Tidak ada pengguna sistem lain yang terdaftar.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <!-- Modal Kelola Akses Menu -->
    <div class="modal fade" id="aksesModal" tabindex="-1" aria-labelledby="aksesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark" id="aksesModalLabel">Konfigurasi Akses Menu</h5>
                        <p class="text-secondary text-xs mb-0" id="aksesModalSubTitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="aksesForm">
                    <input type="hidden" name="user_id" id="perm_user_id">
                    <div class="modal-body py-4" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row">
                            <!-- Category: Operasional -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-gears text-primary me-2"></i>Operasional (Teknisi)</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_dashboard" name="permissions[dashboard]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_dashboard">Dashboard (Teknisi)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_tambah_kegiatan" name="permissions[tambah_kegiatan]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_tambah_kegiatan">Tambah Kegiatan</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_waiting_list" name="permissions[waiting_list]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_waiting_list">Waiting List</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Category: Laporan -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Laporan</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_kegiatan_teknisi" name="permissions[kegiatan_teknisi]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_kegiatan_teknisi">Kegiatan Teknisi</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_laporan_kegiatan" name="permissions[laporan_kegiatan]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_laporan_kegiatan">Laporan Kegiatan</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_target_tercapai" name="permissions[target_tercapai]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_target_tercapai">Target Tercapai Teknisi</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_progress_kegiatan" name="permissions[progress_kegiatan]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_progress_kegiatan">Progress Kegiatan</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Category: Manajemen Aset -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Manajemen Aset</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_stok_barang" name="permissions[stok_barang]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_stok_barang">Stok Barang (Inventory)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_peminjaman" name="permissions[peminjaman]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_peminjaman">Peminjaman</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_tutorial" name="permissions[tutorial]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_tutorial">Tutorial</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Category: Data Master -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-database text-primary me-2"></i>Data Master</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_teknisi" name="permissions[teknisi]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_teknisi">Teknisi</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_customer" name="permissions[customer]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_customer">Customer (Teknisi)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Category: Aplikasi Sales (Admin/Manager) -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-chart-line text-primary me-2"></i>Aplikasi Sales (Admin/Manager)</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_dashboard_sales" name="permissions[dashboard_sales]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_dashboard_sales">Dashboard Sales</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_data_sales" name="permissions[data_sales]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_data_sales">Data Sales (Tim Sales)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_jadwal_kunjungan" name="permissions[jadwal_kunjungan]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_jadwal_kunjungan">Jadwal Kunjungan (Sales)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_laporan_visit" name="permissions[laporan_visit]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_laporan_visit">Laporan Visit (Sales)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_customer_sales" name="permissions[customer_sales]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_customer_sales">Customer Sales</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Category: Aplikasi Sales (Sales Agent) -->
                            <div class="col-md-6 mb-4">
                                <div class="card p-3 border" style="border-radius: 12px; box-shadow: none;">
                                    <h6 class="font-weight-bold text-dark text-sm border-bottom pb-2 mb-3"><i class="fa-solid fa-user-gear text-primary me-2"></i>Aplikasi Sales (Sales Agent)</h6>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_kegiatan_saya" name="permissions[kegiatan_saya]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_kegiatan_saya">Kegiatan Saya</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_dashboard_teknisi" name="permissions[dashboard_teknisi]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_dashboard_teknisi">Dashboard Teknisi (Sales View)</label>
                                    </div>
                                    <div class="form-check form-switch mb-2.5">
                                        <input class="form-check-input" type="checkbox" id="perm_buat_request" name="permissions[buat_request]" value="1">
                                        <label class="form-check-label text-xs text-dark font-weight-bold" for="perm_buat_request">Buat Request (Sales View)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3">
                        <button type="button" class="btn btn-secondary mb-0 px-4 py-2 font-weight-bold text-xs" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary mb-0 px-4 py-2 font-weight-bold text-xs" id="btnSavePermissions">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        var isSubmitting = false;

        // Fetch and load permissions of the selected user
        function loadPermissions(userId) {
            $('#aksesForm')[0].reset();
            $('#perm_user_id').val(userId);
            
            $.ajax({
                url: 'get_user_permissions.php',
                type: 'GET',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#aksesModalSubTitle').text(response.user.name + ' - ' + response.user.jabatan + ' (' + response.user.email + ')');
                        
                        // Set check status for checkboxes
                        $.each(response.permissions, function(key, val) {
                            var chk = $('#perm_' + key);
                            if (chk.length) {
                                chk.prop('checked', parseInt(val) === 1);
                            }
                        });
                        
                        $('#aksesModal').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat memuat data hak akses user.');
                }
            });
        }

        // Handle permission changes save click
        $(document).ready(function() {
            $('#btnSavePermissions').click(function() {
                if (isSubmitting) return;
                isSubmitting = true;
                
                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...');

                $.ajax({
                    url: 'proses_update_akses.php',
                    type: 'POST',
                    data: $('#aksesForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        isSubmitting = false;
                        btn.prop('disabled', false).text('Simpan Perubahan');
                        
                        if (response.status === 'success') {
                            $('#aksesModal').modal('hide');
                            alert(response.message);
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        isSubmitting = false;
                        btn.prop('disabled', false).text('Simpan Perubahan');
                        alert('Terjadi kesalahan saat menghubungi server.');
                    }
                });
            });
        });
    </script>
</body>
</html>
