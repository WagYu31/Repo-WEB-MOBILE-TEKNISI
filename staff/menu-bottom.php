<style>
    .btm-nav {
        background-color: #ffffff;
        box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        z-index: 1050;
        padding-bottom: env(safe-area-inset-bottom);
    }
    .btm-nav .nav-link {
        color: #9CA3AF;
        padding: 0.5rem 0;
        transition: all 0.3s ease;
    }
    .btm-nav .nav-link.active, .btm-nav .nav-link:hover {
        color: #3B82F6;
    }
    .btm-nav .material-icons {
        font-size: 24px;
        margin-bottom: 2px;
    }
    .btm-nav .text-xs {
        font-size: 0.75rem;
        font-weight: 500;
    }
    .dropup-menu-mobile {
        border-radius: 15px;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.15);
        border: none;
        margin-bottom: 15px;
        padding: 10px 0;
    }
    .dropup-item-mobile {
        padding: 10px 20px;
        font-size: 0.85rem;
        color: #374151;
        display: flex;
        align-items: center;
    }
    .dropup-item-mobile:hover {
        background-color: #F3F4F6;
        color: #3B82F6;
    }
    .dropup-item-mobile i {
        margin-right: 10px;
        font-size: 18px;
    }
    .menu-divider {
        height: 1px;
        background-color: #E5E7EB;
        margin: 5px 15px;
    }
</style>

<nav class="navbar navbar-expand fixed-bottom d-xl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100 flex-row">
        <?php if ($role == 'Super Admin' || $role == 'Admin') { ?>
            <li class="nav-item">
                <a href="index-sa.php" class="nav-link text-center <?php echo ($pageNow == 'Dashboard') ? 'active' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">dashboard</i>
                    </div>
                    <span class="text-xs d-block">Dashboard</span>
                </a>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupOperasional" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">build_circle</i>
                    </div>
                    <span class="text-xs d-block">Operasi</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupOperasional">
                    <a class="dropdown-item dropup-item-mobile" href="kegiatan-baru.php"><i class="material-icons">add_task</i> Tambah Kegiatan</a>
                    <a class="dropdown-item dropup-item-mobile" href="waiting-list.php"><i class="material-icons">hourglass_empty</i> Waiting List</a>
                    <div class="menu-divider"></div>
                    <a class="dropdown-item dropup-item-mobile" href="task.php"><i class="material-icons">engineering</i> Kegiatan Teknisi</a>
                    <a class="dropdown-item dropup-item-mobile" href="lap-kegiatan.php"><i class="material-icons">receipt_long</i> Laporan Kegiatan</a>
                    <?php if ($role !== 'Admin') { ?>
                        <a class="dropdown-item dropup-item-mobile" href="laporan.php"><i class="material-icons">payments</i> Pendapatan Teknisi</a>
                    <?php } ?>
                </div>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupMaster" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">folder_copy</i>
                    </div>
                    <span class="text-xs d-block">Data</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupMaster">
                    <a class="dropdown-item dropup-item-mobile" href="data-teknisi.php"><i class="material-icons">groups</i> Data Teknisi</a>
                    <a class="dropdown-item dropup-item-mobile" href="customer.php"><i class="material-icons">contact_page</i> Data Customer</a>
                    <div class="menu-divider"></div>
                    <a class="dropdown-item dropup-item-mobile" href="inventory.php"><i class="material-icons">inventory_2</i> Stok Barang</a>
                    <a class="dropdown-item dropup-item-mobile" href="peminjaman.php"><i class="material-icons">swap_horiz</i> Peminjaman</a>
                </div>
            </li>
        <?php } ?>

        <?php if ($role == 'Sales Manager' || $role == 'Sales') { ?>
            <li class="nav-item">
                <a href="<?php echo ($role == 'Sales Manager') ? 'sales/index-sa.php' : 'sales/index.php'; ?>" class="nav-link text-center <?php echo ($pageNow == 'Dashboard' || $pageNow == 'Dashboard Sales') ? 'active' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">dashboard</i>
                    </div>
                    <span class="text-xs d-block">Home</span>
                </a>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupSales" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">support_agent</i>
                    </div>
                    <span class="text-xs d-block">Sales</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupSales">
                    <a class="dropdown-item dropup-item-mobile" href="sales/sales/index.php"><i class="material-icons">assignment_ind</i> Kegiatan Saya</a>
                    <a class="dropdown-item dropup-item-mobile" href="sales/kegiatan-baru.php"><i class="material-icons">pin_drop</i> Visit Customer</a>
                    <a class="dropdown-item dropup-item-mobile" href="sales/customer.php"><i class="material-icons">contacts</i> Data Customer</a>
                    <?php if ($role == 'Sales Manager') { ?>
                        <div class="menu-divider"></div>
                        <a class="dropdown-item dropup-item-mobile" href="sales/laporan-cust.php"><i class="material-icons">summarize</i> Laporan Visit</a>
                        <a class="dropdown-item dropup-item-mobile" href="sales/sales.php"><i class="material-icons">groups</i> Tim Sales</a>
                    <?php } ?>
                </div>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupTeknisi" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">engineering</i>
                    </div>
                    <span class="text-xs d-block">Teknisi</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupTeknisi">
                    <a class="dropdown-item dropup-item-mobile" href="index-sales.php"><i class="material-icons">pie_chart</i> Dashboard Teknisi</a>
                    <a class="dropdown-item dropup-item-mobile" href="kegiatan-baru.php"><i class="material-icons">add_alert</i> Buat Request</a>
                </div>
            </li>
        <?php } ?>

        <li class="nav-item dropup">
             <a href="#" class="nav-link text-center" role="button" id="dropupAkun" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="material-icons">account_circle</i>
                </div>
                <span class="text-xs d-block">Akun</span>
            </a>
            <div class="dropdown-menu dropup-menu-mobile dropdown-menu-end" aria-labelledby="dropupAkun">
                <a class="dropdown-item dropup-item-mobile" href="change_password.php"><i class="material-icons">vpn_key</i> Ganti Password</a>
                <a class="dropdown-item dropup-item-mobile text-danger" href="../logout.php"><i class="material-icons">logout</i> Sign Out</a>
                <div class="menu-divider"></div>
                <a class="dropdown-item dropup-item-mobile" href="tampil_log.php" target="_blank"><i class="material-icons">flag</i> Data Log</a>
            </div>
        </li>
    </ul>
</nav>