<style>
    /* Style untuk Tombol Aksi Utama di Tengah */
    .fab-button {
        width: 50px !important;
        height: 50px;
        background-color: #ff4081; /* Warna pink yang menonjol */
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
        /*padding:5px 2px !important;*/
    }
    .fab-button:hover {
        transform: scale(1.1);
        color: white;
    }
    /* Menyesuaikan ukuran ikon '+' agar pas di dalam tombol */
    .fab-button i {
        font-size: 16px;
    }
    .nav-item .nav-link.active {
        color: #ff4081 !important; /* Warna pink untuk menu aktif */
    }
</style>

<nav class="navbar navbar-light bg-white navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 shadow-lg">
    <ul class="navbar-nav nav-justified w-100 d-flex justify-content-between">

        <li class="nav-item">
            <a href="index.php" class="nav-link text-center">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="small d-block">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="waiting-list.php" class="nav-link text-center">
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
            <a href="task.php" class="nav-link text-center">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span class="small d-block">Riwayat</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link text-center" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span class="small d-block">Keluar</span> </a>
        </li>

    </ul>
</nav>

<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel" style="height: auto;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasMenuLabel">Menu Lainnya</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group">
            <a href="laporan.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-money-bill-wave fa-fw me-2"></i>Pendapatan Teknisi
            </a>
            <a href="teknisi-db.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-user-gear fa-fw me-2"></i>Data Teknisi
            </a>
            <a href="customer.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-users fa-fw me-2"></i>Data Customer
            </a>
            <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i>Sign Out
            </a>
        </div>
    </div>
</div>