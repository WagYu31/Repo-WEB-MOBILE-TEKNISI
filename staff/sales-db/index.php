<?php
require_once 'config.php';
require_once 'functions.php';

if (isset($_GET['code'])) {
    header('Location: aol-oauth-callback.php?' . $_SERVER['QUERY_STRING']);
    exit();
}

requireAuth();

$currentPage = max(1, (int)($_GET['page'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');
$error = null;
$databases = [];
$customers = ['d' => [], 'sp' => ['rowCount' => 0]];

if (empty($_SESSION['databases']) || isset($_GET['refresh'])) {
    $dbResponse = getDatabaseList();
    if ($dbResponse['success']) {
        $_SESSION['databases'] = $dbResponse['data']['d'] ?? [];
    } else {
        $error = $dbResponse['error'] ?? 'Gagal memuat database';
    }
}

if (isset($_GET['db_id'])) {
    $dbId = (int)$_GET['db_id'];
    
    foreach ($_SESSION['databases'] as $db) {
        if ($db['id'] == $dbId) {
            $selectedDb = $db;
            break;
        }
    }

    if (!empty($selectedDb)) {
        if (empty($_SESSION['accurate_session']) || $_SESSION['accurate_db_id'] != $dbId) {
            $openResponse = openDatabase($dbId);
            if (!$openResponse['success']) {
                $error = $openResponse['error'] ?? 'Gagal membuka database';
            }
        }

        if (empty($error)) {
            $customersResponse = getCustomers($currentPage, $searchQuery);
            if ($customersResponse['success']) {
                $customers = [
                    'd' => $customersResponse['data']['d'] ?? [],
                    'sp' => $customersResponse['data']['sp'] ?? ['rowCount' => 0]
                ];
            } else {
                $error = $customersResponse['error'] ?? 'Gagal memuat data customer';
            }
        }
    } else {
        $error = 'Database tidak ditemukan';
    }
}

if (isset($_GET['customer_id']) && !empty($_SESSION['accurate_session'])) {
    $customerDetail = getCustomerDetail($_GET['customer_id']);
    if (!$customerDetail['success']) {
        $error = $customerDetail['error'] ?? 'Gagal memuat detail customer';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Customer | Accurate Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-header { font-weight: 600; background-color: #f8f9fa; }
        .table th { background-color: #f1f4f7; }
        .text-ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>Manajemen Customer
                </h1>
                <div class="mt-2">
                    <a href="add_customer.php" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i> Tambah Customer
                    </a>
                    <span class="ms-2 text-muted">
                        <i class="fas fa-user me-1"></i>
                        <?= displayValue($_SESSION['accurate_user']['name'] ?? '') ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= displayValue($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-database me-2"></i>Database</span>
                        <a href="?refresh=1" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($_SESSION['databases'])): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($_SESSION['databases'] as $db): ?>
                                    <a href="?db_id=<?= $db['id'] ?>"
                                       class="list-group-item list-group-item-action <?= ($_SESSION['accurate_db_id'] ?? null) == $db['id'] ? 'active' : '' ?>">
                                        <div class="d-flex justify-content-between">
                                            <span><?= displayValue($db['alias'] ?? 'Database ' . $db['id']) ?></span>
                                            <span class="badge bg-light text-dark">ID: <?= $db['id'] ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fas fa-database fa-3x mb-3"></i>
                                <p>Tidak ada database tersedia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if (!empty($selectedDb)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-store me-2"></i>
                                <?= displayValue($selectedDb['alias'] ?? 'Database ' . $selectedDb['id']) ?>
                            </span>
                            <span class="badge bg-primary">ID: <?= $selectedDb['id'] ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-muted mb-1">Status Session</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm" 
                                                   value="<?= !empty($_SESSION['accurate_session']) ? 'Aktif' : 'Nonaktif' ?>" readonly>
                                            <?php if (!empty($_SESSION['accurate_session'])): ?>
                                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                                    onclick="navigator.clipboard.writeText('<?= htmlspecialchars($_SESSION['accurate_session']) ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small text-muted mb-1">Host</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               value="<?= displayValue($_SESSION['accurate_host'] ?? '-') ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list me-2"></i>Daftar Customer</span>
                            <span class="badge bg-primary">
                                Total: <?= number_format($customers['sp']['rowCount']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <form method="get" class="mb-4">
                                <input type="hidden" name="db_id" value="<?= $selectedDb['id'] ?>">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Cari customer..." value="<?= displayValue($searchQuery) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-1"></i> Cari
                                    </button>
                                    <?php if ($searchQuery): ?>
                                        <a href="?db_id=<?= $selectedDb['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> Reset
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <?php if (!empty($customers['d'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nama</th>
                                                <th>Nomor</th>
                                                <th>Terakhir Diubah</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers['d'] as $customer): ?>
                                                <tr>
                                                    <td><?= displayValue($customer['id']) ?></td>
                                                    <td class="text-ellipsis" style="max-width: 150px;">
                                                        <?= displayValue($customer['name']) ?>
                                                    </td>
                                                    <td><?= displayValue($customer['customerNo'] ?? '-') ?></td>
                                                    <td><?= formatDate($customer['lastUpdate'] ?? null) ?></td>
                                                    <td>
                                                        <a href="?db_id=<?= $selectedDb['id'] ?>&customer_id=<?= $customer['id'] ?>"
                                                           class="btn btn-sm btn-outline-primary" title="Lihat detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($customers['sp']['rowCount'] > ITEMS_PER_PAGE): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mt-3">
                                            <?php if ($currentPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" 
                                                       href="?db_id=<?= $selectedDb['id'] ?>&page=<?= $currentPage-1 ?><?= $searchQuery ? '&search='.urlencode($searchQuery) : '' ?>">
                                                        Sebelumnya
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    Halaman <?= $currentPage ?> dari <?= ceil($customers['sp']['rowCount']/ITEMS_PER_PAGE) ?>
                                                </span>
                                            </li>
                                            
                                            <?php if ($currentPage * ITEMS_PER_PAGE < $customers['sp']['rowCount']): ?>
                                                <li class="page-item">
                                                    <a class="page-link" 
                                                       href="?db_id=<?= $selectedDb['id'] ?>&page=<?= $currentPage+1 ?><?= $searchQuery ? '&search='.urlencode($searchQuery) : '' ?>">
                                                        Selanjutnya
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                                    <h5>Tidak ada customer ditemukan</h5>
                                    <?php if ($searchQuery): ?>
                                        <p>Coba dengan kata kunci lain</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-5">
                            <i class="fas fa-database fa-4x text-muted mb-4"></i>
                            <h4 class="mb-3">Belum ada database dipilih</h4>
                            <p class="text-muted">Silakan pilih database dari daftar di samping</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($customerDetail['data'])): ?>
        <div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user me-2"></i>
                            <?= displayValue($customerDetail['data']['name']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h5 class="mb-3 pb-2 border-bottom">Informasi Dasar</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">ID Customer</th>
                                        <td><?= displayValue($customerDetail['data']['id']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nomor Customer</th>
                                        <td><?= displayValue($customerDetail['data']['customerNo'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?= displayValue($customerDetail['data']['email'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Telepon</th>
                                        <td><?= formatPhone($customerDetail['data']['phone'] ?? $customerDetail['data']['mobilePhone'] ?? null) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h5 class="mb-3 pb-2 border-bottom">Alamat</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Alamat Penagihan</th>
                                        <td>
                                            <?= displayValue($customerDetail['data']['billStreet'] ?? '-') ?><br>
                                            <?= displayValue($customerDetail['data']['billCity'] ?? '-') ?>,
                                            <?= displayValue($customerDetail['data']['billProvince'] ?? '-') ?><br>
                                            <?= displayValue($customerDetail['data']['billZipCode'] ?? '-') ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Alamat Pengiriman</th>
                                        <td>
                                            <?php if ($customerDetail['data']['shipSameAsBill'] ?? false): ?>
                                                Sama dengan alamat penagihan
                                            <?php else: ?>
                                                <?= displayValue($customerDetail['data']['shipStreet'] ?? '-') ?><br>
                                                <?= displayValue($customerDetail['data']['shipCity'] ?? '-') ?>,
                                                <?= displayValue($customerDetail['data']['shipProvince'] ?? '-') ?><br>
                                                <?= displayValue($customerDetail['data']['shipZipCode'] ?? '-') ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3 pb-2 border-bottom">Catatan</h5>
                                <div class="border p-3 bg-light rounded">
                                    <?= nl2br(displayValue($customerDetail['data']['description'] ?? 'Tidak ada catatan')) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('customerModal'));
                modal.show();
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                bootstrap.Alert.getInstance(alert)?.close();
            }, 5000);
        });
    </script>
</body>
</html>