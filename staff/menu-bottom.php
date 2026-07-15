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
<?php
// Calculate directory prefix dynamically to share the same menu-bottom.php between root and sales subdirectory
$isSalesDir = (basename(getcwd()) === 'sales');
$rootPrefix = $isSalesDir ? '../' : '';
$salesPrefix = $isSalesDir ? '' : 'sales/';

if ($isSalesDir) {
    include_once "../menu-access-helper.php";
} else {
    include_once "menu-access-helper.php";
}

// Check menu access permissions dynamically
$showDashboard = hasMenuAccess($conn, $idSesi, 'dashboard', ($role == 'Super Admin' || $role == 'Admin'));
$showTambahKegiatan = hasMenuAccess($conn, $idSesi, 'tambah_kegiatan', ($role == 'Super Admin' || $role == 'Admin'));
$showWaitingList = hasMenuAccess($conn, $idSesi, 'waiting_list', ($role == 'Super Admin' || $role == 'Admin'));

$showKegiatanTeknisi = hasMenuAccess($conn, $idSesi, 'kegiatan_teknisi', ($role == 'Super Admin' || $role == 'Admin'));
$showLaporanKegiatan = hasMenuAccess($conn, $idSesi, 'laporan_kegiatan', ($role == 'Super Admin' || $role == 'Admin'));
$showTargetTercapai = hasMenuAccess($conn, $idSesi, 'target_tercapai', ($role == 'Super Admin' || $role == 'Admin'));
$showProgressKegiatan = hasMenuAccess($conn, $idSesi, 'progress_kegiatan', ($role == 'Super Admin' || $role == 'Admin'));

$showStokBarang = hasMenuAccess($conn, $idSesi, 'stok_barang', ($role == 'Super Admin' || $role == 'Admin'));
$showPeminjaman = hasMenuAccess($conn, $idSesi, 'peminjaman', ($role == 'Super Admin' || $role == 'Admin'));
$showTutorial = hasMenuAccess($conn, $idSesi, 'tutorial', ($role == 'Super Admin' || $role == 'Admin'));

$showTeknisi = hasMenuAccess($conn, $idSesi, 'teknisi', ($role == 'Super Admin' || $role == 'Admin'));
$showCustomer = hasMenuAccess($conn, $idSesi, 'customer', ($role == 'Super Admin' || $role == 'Admin'));

$showDashboardSales = hasMenuAccess($conn, $idSesi, 'dashboard_sales', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager' || $role == 'Sales'));
$showDataSales = hasMenuAccess($conn, $idSesi, 'data_sales', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager'));
$showJadwalKunjungan = hasMenuAccess($conn, $idSesi, 'jadwal_kunjungan', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager' || $role == 'Sales'));
$showLaporanVisit = hasMenuAccess($conn, $idSesi, 'laporan_visit', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager'));
$showCustomerSales = hasMenuAccess($conn, $idSesi, 'customer_sales', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager' || $role == 'Sales'));

$showKegiatanSaya = hasMenuAccess($conn, $idSesi, 'kegiatan_saya', ($role == 'Sales Manager' || $role == 'Sales'));
$showDashboardTeknisi = hasMenuAccess($conn, $idSesi, 'dashboard_teknisi', ($role == 'Sales Manager' || $role == 'Sales'));
$showBuatRequest = hasMenuAccess($conn, $idSesi, 'buat_request', ($role == 'Sales Manager' || $role == 'Sales'));
?>

<nav class="navbar navbar-expand fixed-bottom d-xxl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100 flex-row">
        <?php if ($role == 'Super Admin' || $role == 'Admin') { ?>
            <li class="nav-item">
                <a href="<?php echo $rootPrefix; ?>index-sa.php" class="nav-link text-center <?php echo ($pageNow == 'Dashboard' && !$isSalesDir) ? 'active' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">dashboard</i>
                    </div>
                    <span class="text-xs d-block">Dashboard</span>
                </a>
            </li>

            <?php if ($showTambahKegiatan || $showWaitingList || $showKegiatanTeknisi || $showLaporanKegiatan || $showTargetTercapai) { ?>
            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupOperasional" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">build_circle</i>
                    </div>
                    <span class="text-xs d-block">Operasi</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupOperasional">
                    <?php if ($showTambahKegiatan) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>kegiatan-baru.php"><i class="material-icons">add_task</i> Tambah Kegiatan</a><?php } ?>
                    <?php if ($showWaitingList) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>waiting-list.php"><i class="material-icons">hourglass_empty</i> Waiting List</a><?php } ?>
                    <div class="menu-divider"></div>
                    <?php if ($showKegiatanTeknisi) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>task.php"><i class="material-icons">engineering</i> Kegiatan Teknisi</a><?php } ?>
                    <?php if ($showLaporanKegiatan) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>lap-kegiatan.php"><i class="material-icons">receipt_long</i> Laporan Kegiatan</a><?php } ?>
                    <?php if ($showTargetTercapai && $role !== 'Admin') { ?>
                        <a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>laporan.php"><i class="material-icons">payments</i> Target Tercapai Teknisi</a>
                    <?php } ?>
                </div>
            </li>
            <?php } ?>

            <?php if ($showDashboardSales || $showJadwalKunjungan || $showLaporanVisit || $showCustomerSales || $showDataSales) { ?>
            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center <?php echo ($isSalesDir) ? 'active' : ''; ?>" role="button" id="dropupAdminSales" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">support_agent</i>
                    </div>
                    <span class="text-xs d-block">Sales</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupAdminSales">
                    <?php if ($showDashboardSales) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>index-sa.php"><i class="material-icons">dashboard</i> Dashboard Sales</a><?php } ?>
                    <?php if ($showJadwalKunjungan) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>kegiatan-baru.php"><i class="material-icons">pin_drop</i> Jadwal Kunjungan</a><?php } ?>
                    <?php if ($showLaporanVisit) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>laporan-cust.php"><i class="material-icons">summarize</i> Laporan Visit</a><?php } ?>
                    <?php if ($showCustomerSales) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>customer.php"><i class="material-icons">contacts</i> Customer Sales</a><?php } ?>
                    <?php if ($showDataSales) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>sales.php"><i class="material-icons">groups</i> Data Sales</a><?php } ?>
                </div>
            </li>
            <?php } ?>

            <?php if ($showTeknisi || $showCustomer || $showStokBarang || $showPeminjaman) { ?>
            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center" role="button" id="dropupMaster" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">folder_copy</i>
                    </div>
                    <span class="text-xs d-block">Data</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupMaster">
                    <?php if ($showTeknisi) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>data-teknisi.php"><i class="material-icons">groups</i> Data Teknisi</a><?php } ?>
                    <?php if ($showCustomer) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>customer.php"><i class="material-icons">contact_page</i> Data Customer</a><?php } ?>
                    <div class="menu-divider"></div>
                    <?php if ($showStokBarang) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>inventory.php"><i class="material-icons">inventory_2</i> Stok Barang</a><?php } ?>
                    <?php if ($showPeminjaman) { ?><a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>peminjaman.php"><i class="material-icons">swap_horiz</i> Peminjaman</a><?php } ?>
                </div>
            </li>
            <?php } ?>
        <?php } ?>

        <?php if ($role == 'Sales Manager' || $role == 'Sales') { ?>
            <li class="nav-item">
                <a href="<?php echo ($role == 'Sales Manager') ? $salesPrefix . 'index-sa.php' : $salesPrefix . 'index.php'; ?>" class="nav-link text-center <?php echo ($pageNow == 'Dashboard' || $pageNow == 'Dashboard Sales') ? 'active' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">dashboard</i>
                    </div>
                    <span class="text-xs d-block">Home</span>
                </a>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center <?php echo ($isSalesDir && $pageNow != 'Dashboard Sales') ? 'active' : ''; ?>" role="button" id="dropupSales" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">support_agent</i>
                    </div>
                    <span class="text-xs d-block">Sales</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupSales">
                    <a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>sales/index.php"><i class="material-icons">assignment_ind</i> Kegiatan Saya</a>
                    <a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>kegiatan-baru.php"><i class="material-icons">pin_drop</i> Visit Customer</a>
                    <a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>customer.php"><i class="material-icons">contacts</i> Data Customer</a>
                    <?php if ($role == 'Sales Manager') { ?>
                        <div class="menu-divider"></div>
                        <a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>laporan-cust.php"><i class="material-icons">summarize</i> Laporan Visit</a>
                        <a class="dropdown-item dropup-item-mobile" href="<?php echo $salesPrefix; ?>sales.php"><i class="material-icons">groups</i> Tim Sales</a>
                    <?php } ?>
                </div>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center <?php echo (!$isSalesDir) ? 'active' : ''; ?>" role="button" id="dropupTeknisi" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="material-icons">engineering</i>
                    </div>
                    <span class="text-xs d-block">Teknisi</span>
                </a>
                <div class="dropdown-menu dropup-menu-mobile w-100" aria-labelledby="dropupTeknisi">
                    <a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>index-sales.php"><i class="material-icons">pie_chart</i> Dashboard Teknisi</a>
                    <a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>kegiatan-baru.php"><i class="material-icons">add_alert</i> Buat Request</a>
                </div>
            </li>
        <?php } ?>

        <li class="nav-item dropup">
             <a href="#" class="nav-link text-center <?php echo ($pageNow == 'Profile' || $pageNow == 'Ganti Password') ? 'active' : ''; ?>" role="button" id="dropupAkun" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="material-icons">account_circle</i>
                </div>
                <span class="text-xs d-block">Akun</span>
            </a>
            <div class="dropdown-menu dropup-menu-mobile dropdown-menu-end" aria-labelledby="dropupAkun">
                <a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>change_password.php"><i class="material-icons">vpn_key</i> Ganti Password</a>
                <a class="dropdown-item dropup-item-mobile text-danger" href="<?php echo $rootPrefix; ?>../logout.php"><i class="material-icons">logout</i> Sign Out</a>
                <div class="menu-divider"></div>
                <a class="dropdown-item dropup-item-mobile" href="<?php echo $rootPrefix; ?>tampil_log.php" target="_blank"><i class="material-icons">flag</i> Data Log</a>
            </div>
        </li>
    </ul>
</nav>