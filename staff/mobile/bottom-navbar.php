<style>
    .fab-button {
        width: 50px !important;
        height: 50px;
        background-color: #ff4081;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        border: 2px solid white;
        margin-top: -25px;
        margin-left:10px;
        z-index: 10;
        transition: transform 0.2s;
    }
    .fab-button:hover {
        transform: scale(1.1);
        color: white;
    }
    .fab-button i {
        font-size: 16px;
    }
    .nav-item .nav-link.active {
        color: #ff4081 !important;
    }
    .offcanvas-menu-item {
        display: flex;
        align-items: center;
        padding: 14px 20px;
        color: #374151;
        text-decoration: none;
        border-radius: 10px;
        margin-bottom: 4px;
        transition: all 0.2s;
    }
    .offcanvas-menu-item:hover { background-color: #F3F4F6; color: #3B82F6; }
    .offcanvas-menu-item i { width: 24px; margin-right: 12px; font-size: 16px; text-align: center; }
    .offcanvas-menu-section { font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 20px 4px; }
    .offcanvas-menu-divider { height: 1px; background: #E5E7EB; margin: 8px 16px; }
</style>

<nav class="navbar navbar-light bg-white navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 shadow-lg">
    <ul class="navbar-nav nav-justified w-100 d-flex justify-content-between">
        <li class="nav-item">
            <a href="index.php" class="nav-link text-center <?= ($pageNow == 'Jadwal') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="small d-block">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="waiting-list.php" class="nav-link text-center <?= ($pageNow == 'Waiting') ? 'active' : ''; ?>">
                <i class="fa-solid fa-hourglass-half"></i>
                <span class="small d-block">Waiting</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="kegiatan-baru.php" class="nav-link fab-button p-2">
                <i class="fa-solid fa-plus"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="task.php" class="nav-link text-center <?= ($pageNow == 'Task') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="small d-block">Riwayat</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link text-center" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
                <i class="fa-solid fa-bars"></i>
                <span class="small d-block">Lainnya</span>
            </a>
        </li>
    </ul>
</nav>

<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel" style="height: auto; max-height: 75vh; border-radius: 20px 20px 0 0;">
    <div class="offcanvas-header pb-0">
        <h6 class="offcanvas-title fw-bold" id="offcanvasMenuLabel">Menu Lainnya</h6>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2" style="padding-bottom: env(safe-area-inset-bottom);">

        <div class="offcanvas-menu-section">Operasional</div>
        <a class="offcanvas-menu-item" href="kegiatan-baru.php"><i class="fa-solid fa-circle-plus"></i> Tambah Kegiatan</a>
        <a class="offcanvas-menu-item" href="waiting-list.php"><i class="fa-solid fa-hourglass-half"></i> Waiting List</a>
        <a class="offcanvas-menu-item" href="task.php"><i class="fa-solid fa-list-check"></i> Kegiatan Teknisi</a>

        <div class="offcanvas-menu-divider"></div>
        <div class="offcanvas-menu-section">Laporan</div>
        <a class="offcanvas-menu-item" href="lap-kegiatan.php"><i class="fa-solid fa-receipt"></i> Laporan Kegiatan</a>
        <a class="offcanvas-menu-item" href="laporan.php"><i class="fa-solid fa-money-bill-wave"></i> Pendapatan Teknisi</a>
        <a class="offcanvas-menu-item" href="lap-progress.php"><i class="fa-solid fa-chart-line"></i> Progress Kegiatan</a>

        <div class="offcanvas-menu-divider"></div>
        <div class="offcanvas-menu-section">Data</div>
        <a class="offcanvas-menu-item" href="teknisi-db.php"><i class="fa-solid fa-user-gear"></i> Data Teknisi</a>
        <a class="offcanvas-menu-item" href="customer.php"><i class="fa-solid fa-users"></i> Data Customer</a>
        <a class="offcanvas-menu-item" href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Stok Barang</a>
        <a class="offcanvas-menu-item" href="peminjaman.php"><i class="fa-solid fa-arrows-rotate"></i> Peminjaman</a>
        <a class="offcanvas-menu-item" href="tutorial.php"><i class="fa-solid fa-video"></i> Tutorial</a>

        <div class="offcanvas-menu-divider"></div>
        <div class="offcanvas-menu-section">Akun</div>
        <a class="offcanvas-menu-item" href="data-admin.php"><i class="fa-solid fa-shield-halved"></i> Data Admin</a>
        <a class="offcanvas-menu-item" href="change_password.php"><i class="fa-solid fa-key"></i> Ganti Password</a>
        <a class="offcanvas-menu-item text-danger" href="../../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>

    </div>
</div>