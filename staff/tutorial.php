<?php
include "conn.php";
include "session.php";
include "get-user-data.php";
$pageNow = "Tutorial";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? null;

    $uploadPaths = [
        '../../repositories/staging-teknisi-api/storage/app/public/tutorial/',
        '../../repositories/teknisi-api/storage/app/public/tutorial/'
    ];

    foreach ($uploadPaths as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    $media1 = null;
    $media2 = null;

    if (!empty($_FILES['media_1']['name'])) {
        $ext1 = pathinfo($_FILES['media_1']['name'], PATHINFO_EXTENSION);
        $media1 = uniqid() . "_media_1." . $ext1;
        foreach ($uploadPaths as $path) {
            copy($_FILES['media_1']['tmp_name'], $path . $media1);
        }
    }

    if (!empty($_FILES['media_2']['name'])) {
        $ext2 = pathinfo($_FILES['media_2']['name'], PATHINFO_EXTENSION);
        $media2 = uniqid() . "_media_2." . $ext2;
        foreach ($uploadPaths as $path) {
            copy($_FILES['media_2']['tmp_name'], $path . $media2);
        }
    }

    $stmt = $conn->prepare("INSERT INTO data (title, media_1, media_2, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param('ssss', $title, $media1, $media2, $description);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    $stmt->close();
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$total_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM data WHERE deleted_at IS NULL");
$total_data = mysqli_fetch_assoc($total_res)['total'];
$pages = ceil($total_data / $limit);

$sql = "SELECT * FROM data WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php include "head.php"; ?>
    <style>
        .custom-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); background: #fff; }
        .form-control { border-radius: 6px; border: 1px solid #dee2e6; padding: 10px; }
        .search-box { max-width: 300px; }
        .btn-icon-only { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; }
        .file-badge { background: #f8f9fa; border-radius: 6px; padding: 6px 10px; font-size: 11px; color: #344767; display: flex; align-items: center; text-decoration: none; margin-bottom: 5px; border: 1px solid #e9ecef; font-weight: 600; }
        .file-badge:hover { background: #e9ecef; color: #17ad37; }
        .pagination .page-item.active .page-link { background-color: #1a73e8; border: none; color: #fff; }
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="card custom-card mb-4 p-3">
                        <div class="card-header pb-0 bg-transparent d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-info shadow-info text-center border-radius-md me-3">
                                <i class="material-icons opacity-10">add</i>
                            </div>
                            <h6 class="mb-0 font-weight-bolder">TAMBAH TUTORIAL BARU</h6>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label text-xs font-weight-bold">Judul Tutorial</label>
                                        <input type="text" name="title" class="form-control" placeholder="Masukkan judul tutorial..." required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-xs font-weight-bold">Media Utama</label>
                                        <input type="file" name="media_1" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-xs font-weight-bold">Media Pendukung</label>
                                        <input type="file" name="media_2" class="form-control">
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label text-xs font-weight-bold">Deskripsi</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan keterangan tutorial di sini..."></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn bg-gradient-info mb-0"><i class="material-icons text-sm">save</i>&nbsp;&nbsp;SIMPAN DATA</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card custom-card p-3">
                        <div class="card-header pb-0 bg-transparent d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 font-weight-bolder text-uppercase">Daftar Tutorial</h6>
                            <div class="d-flex align-items-center">
                                <input type="text" id="searchInput" class="form-control search-box me-3" placeholder="Cari data...">
                                <span class="badge bg-secondary"><?php echo date('d M Y'); ?></span>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2 mt-3">
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center text-xxs font-weight-bolder opacity-7" width="5%">NO</th>
                                            <th class="text-xxs font-weight-bolder opacity-7 ps-2" width="25%">TUTORIAL</th>
                                            <th class="text-xxs font-weight-bolder opacity-7 ps-2" width="25%">FILES</th>
                                            <th class="text-xxs font-weight-bolder opacity-7 ps-2" width="30%">DESKRIPSI</th>
                                            <th class="text-center text-xxs font-weight-bolder opacity-7" width="15%">OPSI</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tutorialTable">
                                        <?php 
                                        $no = $start + 1;
                                        while ($row = mysqli_fetch_assoc($result)): 
                                        ?>
                                        <tr>
                                            <td class="text-center text-xs font-weight-bold"><?php echo $no++; ?></td>
                                            <td>
                                                <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($row['title']); ?></h6>
                                                <p class="text-xxs text-secondary mb-0">ID: #<?php echo $row['id']; ?></p>
                                            </td>
                                            <td>
                                                <?php if($row['media_1']): ?>
                                                    <a href="view-media.php?file=<?php echo $row['media_1']; ?>" target="_blank" class="file-badge">
                                                        <i class="material-icons text-xs me-2">visibility</i> <?php echo $row['media_1']; ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($row['media_2']): ?>
                                                    <a href="view-media.php?file=<?php echo $row['media_2']; ?>" target="_blank" class="file-badge">
                                                        <i class="material-icons text-xs me-2">visibility</i> <?php echo $row['media_2']; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-xs text-secondary">
                                                <?php echo (strlen($row['description']) > 80) ? substr(htmlspecialchars($row['description']), 0, 80) . '...' : htmlspecialchars($row['description']); ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-icon-only bg-gradient-primary me-1"><i class="material-icons text-xs">edit</i></button>
                                                <a href="delete-tutor.php?id=<?php echo $row['id']; ?>" class="btn btn-icon-only bg-gradient-danger" onclick="return confirm('Hapus data?')">
                                                    <i class="material-icons text-xs">delete</i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>"><i class="fa fa-angle-left"></i></a>
                                    </li>
                                    <?php for($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php if($page >= $pages) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="fa fa-angle-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#tutorialTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
</body>
</html>