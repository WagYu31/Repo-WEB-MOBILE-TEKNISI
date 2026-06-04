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
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        /* ═══ PREMIUM TUTORIAL ═══ */
        .tut-layout { max-width: 1000px; margin: 0 auto; }

        /* Add Form */
        .tut-form-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            padding: 24px; margin-bottom: 20px;
        }
        .tut-form-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .tut-form-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(6,182,212,0.2);
        }
        .tut-form-icon i { color: #fff; font-size: 15px; }
        .tut-form-header h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .tut-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block; }
        .tut-input {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 10px 14px; font-size: 13px; color: #1e293b; background: #f8fafc;
            transition: all 0.2s; font-weight: 500;
        }
        .tut-input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6,182,212,0.08); outline: none; background: #fff; }
        .tut-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .tut-field + .tut-field { margin-top: 14px; }
        .tut-btn-save {
            padding: 11px 28px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(6,182,212,0.25); transition: all 0.2s;
            float: right; margin-top: 16px;
        }
        .tut-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(6,182,212,0.35); }

        /* Upload area */
        .tut-upload-zone {
            border: 2px dashed #d1d5db; border-radius: 10px; padding: 20px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            position: relative; overflow: hidden; background: #fafbfc;
        }
        .tut-upload-zone:hover { border-color: #06b6d4; background: #f0fdfa; }
        .tut-upload-zone.has-file { border-color: #22c55e; background: #f0fdf4; }
        .tut-upload-zone input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .tut-upload-icon { font-size: 24px; color: #94a3b8; margin-bottom: 4px; }
        .tut-upload-text { font-size: 12px; color: #94a3b8; font-weight: 600; }
        .tut-upload-name { font-size: 11px; color: #16a34a; font-weight: 700; margin-top: 4px; display: none; }

        /* List Card */
        .tut-list-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .tut-list-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px; flex-wrap: wrap; gap: 12px;
        }
        .tut-list-left { display: flex; align-items: center; gap: 12px; }
        .tut-list-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(139,92,246,0.2);
        }
        .tut-list-icon i { color: #fff; font-size: 15px; }
        .tut-list-left h6 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
        .tut-count {
            font-size: 11px; font-weight: 700; color: #8b5cf6; background: #f5f3ff;
            padding: 3px 10px; border-radius: 20px; margin-left: 8px;
        }
        .tut-list-right { display: flex; align-items: center; gap: 10px; }
        .tut-search {
            border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 8px 14px 8px 34px;
            font-size: 13px; color: #1e293b; background: #f8fafc; font-weight: 500;
            transition: all 0.2s; width: 220px;
        }
        .tut-search:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,0.08); outline: none; background: #fff; }
        .tut-search-wrap { position: relative; }
        .tut-search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #94a3b8; }
        .tut-date-badge {
            font-size: 11px; font-weight: 700; color: #475569; background: #f1f5f9;
            padding: 6px 12px; border-radius: 8px;
        }

        /* Table */
        .tut-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .tut-table thead th {
            background: #f8fafc; border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px; font-size: 10px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap;
        }
        .tut-table tbody tr { transition: background 0.15s; border-bottom: 1px solid #f1f5f9; }
        .tut-table tbody tr:hover { background: #fafbfc; }
        .tut-table tbody td { padding: 14px 16px; font-size: 13px; color: #334155; vertical-align: middle; }

        .tut-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; }
        .tut-id { font-size: 10px; color: #94a3b8; margin-top: 2px; }

        .tut-file-pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600; color: #475569;
            background: #f1f5f9; padding: 5px 10px; border-radius: 8px;
            border: 1px solid #e5e7eb; text-decoration: none; transition: all 0.15s;
            margin-bottom: 4px;
        }
        .tut-file-pill:hover { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
        .tut-file-pill i { font-size: 11px; }

        .tut-desc { font-size: 12px; color: #64748b; line-height: 1.5; max-width: 280px; }

        .tut-act-btn {
            width: 30px; height: 30px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s; font-size: 12px; text-decoration: none;
        }
        .tut-act-edit { background: #eef2ff; color: #6366f1; }
        .tut-act-edit:hover { background: #6366f1; color: #fff; }
        .tut-act-del { background: #fef2f2; color: #ef4444; }
        .tut-act-del:hover { background: #ef4444; color: #fff; }

        /* Pagination */
        .tut-footer { padding: 14px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; }
        .pg-list { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; }
        .pg-item a {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; font-size: 12px; font-weight: 600;
            text-decoration: none; color: #64748b; background: #f8fafc; border: 1px solid #e5e7eb;
            transition: all 0.15s;
        }
        .pg-item a:hover { background: #f1f5f9; color: #1e293b; }
        .pg-item.active a { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border-color: transparent; }
        .pg-item.disabled a { opacity: 0.4; pointer-events: none; }

        @media (max-width: 768px) {
            .tut-row { grid-template-columns: 1fr; }
            .tut-search { width: 100%; }
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">
    <?php include "cek-menu.php"; ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <?php include "nav-top.php"; ?>

        <div class="container-fluid py-4">
            <div class="tut-layout">
                <!-- Add Form -->
                <div class="tut-form-card">
                    <div class="tut-form-header">
                        <div class="tut-form-icon"><i class="fa-solid fa-plus"></i></div>
                        <h6>Tambah Tutorial Baru</h6>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="tut-field">
                            <label class="tut-label">Judul Tutorial</label>
                            <input type="text" name="title" class="tut-input" placeholder="Masukkan judul tutorial..." required>
                        </div>
                        <div class="tut-field" style="margin-top:14px;">
                            <label class="tut-label">Media</label>
                            <div class="tut-row">
                                <div class="tut-upload-zone" id="zone1">
                                    <input type="file" name="media_1" onchange="showFileName(this, 'zone1')">
                                    <div class="tut-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <div class="tut-upload-text">Media Utama</div>
                                    <div class="tut-upload-name" id="zone1-name"></div>
                                </div>
                                <div class="tut-upload-zone" id="zone2">
                                    <input type="file" name="media_2" onchange="showFileName(this, 'zone2')">
                                    <div class="tut-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <div class="tut-upload-text">Media Pendukung</div>
                                    <div class="tut-upload-name" id="zone2-name"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tut-field">
                            <label class="tut-label">Deskripsi</label>
                            <textarea name="description" class="tut-input" rows="3" placeholder="Tuliskan keterangan tutorial..." style="resize:none;"></textarea>
                        </div>
                        <button type="submit" class="tut-btn-save">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Tutorial
                        </button>
                        <div style="clear:both;"></div>
                    </form>
                </div>

                <!-- List -->
                <div class="tut-list-card">
                    <div class="tut-list-header">
                        <div class="tut-list-left">
                            <div class="tut-list-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <h6>Daftar Tutorial</h6>
                            <span class="tut-count"><?= $total_data ?> tutorial</span>
                        </div>
                        <div class="tut-list-right">
                            <div class="tut-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="searchInput" class="tut-search" placeholder="Cari tutorial...">
                            </div>
                            <span class="tut-date-badge"><?= date('d M Y') ?></span>
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="tut-table">
                            <thead>
                                <tr>
                                    <th style="width:45px; text-align:center; padding-left:20px;">No</th>
                                    <th style="width:25%;">Tutorial</th>
                                    <th style="width:25%;">Files</th>
                                    <th>Deskripsi</th>
                                    <th style="width:90px; text-align:center;">Opsi</th>
                                </tr>
                            </thead>
                            <tbody id="tutorialTable">
                                <?php
                                $no = $start + 1;
                                while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td style="text-align:center; padding-left:20px;">
                                        <span style="font-size:12px; font-weight:700; color:#94a3b8;"><?= $no++ ?></span>
                                    </td>
                                    <td>
                                        <div class="tut-title"><?= htmlspecialchars($row['title']) ?></div>
                                        <div class="tut-id">ID: #<?= $row['id'] ?></div>
                                    </td>
                                    <td>
                                        <?php if($row['media_1']): ?>
                                            <a href="view-media.php?file=<?= $row['media_1'] ?>" target="_blank" class="tut-file-pill">
                                                <i class="fa-solid fa-file"></i>
                                                <?= strlen($row['media_1']) > 20 ? substr($row['media_1'], 0, 20) . '...' : $row['media_1'] ?>
                                            </a><br>
                                        <?php endif; ?>
                                        <?php if($row['media_2']): ?>
                                            <a href="view-media.php?file=<?= $row['media_2'] ?>" target="_blank" class="tut-file-pill">
                                                <i class="fa-solid fa-file"></i>
                                                <?= strlen($row['media_2']) > 20 ? substr($row['media_2'], 0, 20) . '...' : $row['media_2'] ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if(!$row['media_1'] && !$row['media_2']): ?>
                                            <span style="font-size:12px; color:#cbd5e1;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="tut-desc">
                                            <?= !empty($row['description']) ? ((strlen($row['description']) > 80) ? htmlspecialchars(substr($row['description'], 0, 80)) . '...' : htmlspecialchars($row['description'])) : '<span style="color:#cbd5e1;">—</span>' ?>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display:flex; gap:4px; justify-content:center;">
                                            <button class="tut-act-btn tut-act-edit"><i class="fa-solid fa-pen"></i></button>
                                            <a href="delete-tutor.php?id=<?= $row['id'] ?>" class="tut-act-btn tut-act-del" onclick="return confirm('Hapus data tutorial ini?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($pages > 1): ?>
                    <div class="tut-footer">
                        <ul class="pg-list">
                            <li class="pg-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a href="?page=<?= $page - 1 ?>"><i class="fa-solid fa-angle-left"></i></a>
                            </li>
                            <?php for($i = 1; $i <= $pages; $i++): ?>
                                <li class="pg-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="pg-item <?= ($page >= $pages) ? 'disabled' : '' ?>">
                                <a href="?page=<?= $page + 1 ?>"><i class="fa-solid fa-angle-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </main>

    <?php include "js-include.php"; ?>
    <script>
        function showFileName(input, zoneId) {
            const zone = document.getElementById(zoneId);
            const nameEl = document.getElementById(zoneId + '-name');
            if (input.files && input.files[0]) {
                nameEl.textContent = '✓ ' + input.files[0].name;
                nameEl.style.display = 'block';
                zone.classList.add('has-file');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll('#tutorialTable tr');
            searchInput.addEventListener('keyup', function() {
                const val = this.value.toLowerCase();
                rows.forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>