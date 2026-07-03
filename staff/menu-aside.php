<?php
function renderNavHeader($title) {
    echo '<li class="nav-header" style="padding:16px 16px 8px;font-size:0.65rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;list-style:none;">' . htmlspecialchars($title) . '</li>';
}

function renderNavItem($pageNow, $targetPage, $url, $icon, $text) {
    $isActive = ($pageNow == $targetPage) ? 'active' : '';
    echo '
    <li class="nav-item">
        <a class="nav-link ' . $isActive . '" href="' . $url . '">
            <i class="nav-icon fa-fw ' . $icon . '"></i>
            <p>' . htmlspecialchars($text) . '</p>
        </a>
    </li>';
}
?>

<!-- Font Awesome sudah dimuat di head.php -->

<style>
    /* ==========================================================
       SIDEBAR — inline-level specificity via #sidenav-main ID
       Targets ID to guarantee win over .navbar-vertical classes
       ========================================================== */

    /* Container */
    #sidenav-main {
        background: #111827 !important;
        background-color: #111827 !important;
        background-image: none !important;
        width: 250px !important;
        max-width: 250px !important;
        min-width: 250px !important;
        height: calc(100vh - 2rem) !important;
        top: 1rem !important;
        border: none !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding: 0 !important;
    }
    /* Kill bg-white that JS adds */
    #sidenav-main.bg-white,
    #sidenav-main.bg-transparent {
        background: #111827 !important;
        background-color: #111827 !important;
    }

    /* Header */
    #sidenav-main .sidenav-header {
        padding: 1.25rem 1rem !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        min-height: 60px !important;
        border-bottom: 1px solid #374151 !important;
        background: transparent !important;
        flex-shrink: 0 !important;
    }
    #sidenav-main .navbar-brand-img {
        max-height: 2.5rem !important;
    }

    /* Scrollable nav collapse */
    #sidenav-main .navbar-collapse {
        display: block !important;
        flex: 1 1 0% !important;
        height: 0 !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }
    #sidenav-main .navbar-collapse::-webkit-scrollbar { width: 5px; }
    #sidenav-main .navbar-collapse::-webkit-scrollbar-track { background: transparent; }
    #sidenav-main .navbar-collapse::-webkit-scrollbar-thumb { background: #4B5563; border-radius: 5px; }

    /* Nav list */
    #sidenav-main .navbar-nav {
        padding: 0.5rem 0.75rem !important;
    }

    /* Nav items */
    #sidenav-main .nav-item {
        margin: 0 !important;
        margin-bottom: 2px !important;
        width: 100% !important;
    }

    /* Nav links — the most critical override */
    #sidenav-main .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 0.6rem 0.75rem !important;
        margin: 0 !important;
        border-radius: 0.375rem !important;
        color: #9CA3AF !important;
        background: transparent !important;
        box-shadow: none !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        transition: background 0.15s ease, color 0.15s ease !important;
        position: relative !important;
        font-weight: 400 !important;
    }
    #sidenav-main .nav-link:hover {
        background: #1F2937 !important;
        color: #FFFFFF !important;
    }
    #sidenav-main .nav-link.active {
        background: rgba(59, 130, 246, 0.12) !important;
        color: #FFFFFF !important;
        font-weight: 500 !important;
    }
    #sidenav-main .nav-link.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 15%;
        height: 70%;
        width: 3px;
        background: #3B82F6;
        border-radius: 0 3px 3px 0;
    }
    /* Kill framework gradient backgrounds on active */
    #sidenav-main .nav-link.active[class*="bg-gradient"] {
        background: rgba(59, 130, 246, 0.12) !important;
    }

    /* Icon */
    #sidenav-main .nav-link .nav-icon,
    #sidenav-main .nav-link > i {
        width: 1.25rem !important;
        min-width: 1.25rem !important;
        text-align: center !important;
        margin-right: 0.625rem !important;
        font-size: 0.85rem !important;
        color: inherit !important;
        line-height: 1.5 !important;
    }

    /* Text */
    #sidenav-main .nav-link p,
    #sidenav-main .nav-link span {
        margin: 0 !important;
        font-size: 0.8125rem !important;
        color: inherit !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        opacity: 1 !important;
    }

    /* Section headers */
    #sidenav-main .nav-header {
        padding: 1rem 0.75rem 0.375rem !important;
        font-size: 0.65rem !important;
        font-weight: 600 !important;
        color: #6B7280 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: transparent !important;
        list-style: none !important;
    }

    /* Footer */
    #sidenav-main .sidenav-footer {
        padding: 0.75rem !important;
        border-top: 1px solid #374151 !important;
        margin-top: auto !important;
        background: transparent !important;
        flex-shrink: 0 !important;
    }
    #sidenav-main .sidenav-footer .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 0.5rem 0.75rem !important;
        margin: 0 !important;
        margin-bottom: 2px !important;
        border-radius: 0.375rem !important;
        color: #9CA3AF !important;
        background: transparent !important;
        box-shadow: none !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: background 0.15s ease, color 0.15s ease !important;
    }
    #sidenav-main .sidenav-footer .nav-link:hover {
        background: #1F2937 !important;
        color: #FFFFFF !important;
    }
    #sidenav-main .sidenav-footer .nav-link .nav-icon,
    #sidenav-main .sidenav-footer .nav-link > i {
        width: 1.25rem !important;
        min-width: 1.25rem !important;
        text-align: center !important;
        margin-right: 0.625rem !important;
        font-size: 0.85rem !important;
        color: inherit !important;
    }
    #sidenav-main .sidenav-footer .nav-link p,
    #sidenav-main .sidenav-footer .nav-link span {
        margin: 0 !important;
        font-size: 0.8125rem !important;
        color: inherit !important;
        display: inline !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* Kill framework text-dark/text-white overrides from JS darkMode() */
    #sidenav-main .text-dark,
    #sidenav-main .text-white {
        color: inherit !important;
    }
</style<?php
// Calculate directory prefix dynamically to share the same menu-aside.php between root and sales subdirectory
$isSalesDir = (basename(getcwd()) === 'sales');
$rootPrefix = $isSalesDir ? '../' : '';
$salesPrefix = $isSalesDir ? '' : 'sales/';
?>
<aside class="sidenav navbar navbar-vertical navbar-expand-xs fixed-start ms-3 my-3" id="sidenav-main">
    <div class="sidenav-header">
        <a class="navbar-brand m-0" href="#">
            <img src="<?php echo $rootPrefix; ?>assets/img/logo/lwx-logo.png" class="navbar-brand-img mt-3 mb-2" style="width:auto; max-height:2.5em;" alt="main_logo">
        </a>
    </div>

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <?php if ($role == 'Super Admin') : ?>
            <?php endif; ?>

            <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
                <?php renderNavHeader("Operasional"); ?>
                <?php renderNavItem($pageNow, "Dashboard", $rootPrefix . "index-sa.php", "fa-solid fa-chart-pie", "Dashboard"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Baru", $rootPrefix . "kegiatan-baru.php", "fa-solid fa-file-circle-plus", "Tambah Kegiatan"); ?>
                <?php renderNavItem($pageNow, "Waiting List", $rootPrefix . "waiting-list.php", "fa-solid fa-hourglass-half", "Waiting List"); ?>

                <?php renderNavHeader("Laporan"); ?>
                <?php renderNavItem($pageNow, "Task", $rootPrefix . "task.php", "fa-solid fa-person-digging", "Kegiatan Teknisi"); ?>
                <?php renderNavItem($pageNow, "Laporan", $rootPrefix . "lap-kegiatan.php", "fa-solid fa-file-invoice", "Laporan Kegiatan"); ?>
                <?php renderNavItem($pageNow, "Target Tercapai", $rootPrefix . "laporan.php", "fa-solid fa-hand-holding-dollar", "Target Tercapai Teknisi"); ?>
                <?php renderNavItem($pageNow, "Progress Kegiatan", $rootPrefix . "lap-progress.php", "fa-solid fa-bars-progress", "Progress Kegiatan"); ?>

                <?php renderNavHeader("Manajemen Aset"); ?>
                <?php renderNavItem($pageNow, "Inventory", $rootPrefix . "inventory.php", "fa-solid fa-boxes-stacked", "Stok Barang"); ?>
                <?php renderNavItem($pageNow, "Peminjaman", $rootPrefix . "peminjaman.php", "fa-solid fa-right-left", "Peminjaman"); ?>
                <?php renderNavItem($pageNow, "Tutorial", $rootPrefix . "tutorial.php", "fa-solid fa-book", "Tutorial"); ?>

                <?php renderNavHeader("Data Master"); ?>
                <?php renderNavItem($pageNow, "Data Teknisi", $rootPrefix . "data-teknisi.php", "fa-solid fa-users-gear", "Teknisi"); ?>
                <?php renderNavItem($pageNow, "Data Customer", $rootPrefix . "customer.php", "fa-solid fa-users", "Customer"); ?>

                <?php renderNavHeader("Aplikasi Sales"); ?>
                <?php renderNavItem($pageNow, "Dashboard Sales", $salesPrefix . "index-sa.php", "fa-solid fa-chart-line", "Dashboard Sales"); ?>
                <?php renderNavItem($pageNow, "Data Sales", $rootPrefix . "sales.php", "fa-solid fa-user-group", "Data Sales"); ?>
                <?php renderNavItem($pageNow, "Kunjungan Sales", $salesPrefix . "kegiatan-baru.php", "fa-solid fa-map-location-dot", "Jadwal Kunjungan"); ?>
                <?php renderNavItem($pageNow, "Laporan Visit", $salesPrefix . "laporan-cust.php", "fa-solid fa-file-contract", "Laporan Visit"); ?>
                <?php renderNavItem($pageNow, "Customer Sales", $salesPrefix . "customer.php", "fa-solid fa-address-book", "Customer Sales"); ?>
            <?php endif; ?>


            <?php if ($role == 'Sales Manager' || $role == 'Sales') : ?>
                <?php renderNavHeader("Sales"); ?>
                <?php renderNavItem($pageNow, ($role == 'Sales Manager' ? "Dashboard" : "Dashboard Sales"), $salesPrefix . "index-sa.php", "fa-solid fa-chart-line", "Dashboard"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Saya", $salesPrefix . "sales/index.php", "fa-solid fa-user-check", "Kegiatan Saya"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Sales", $salesPrefix . "kegiatan-baru.php", "fa-solid fa-map-location-dot", "Visit Customer"); ?>
                <?php renderNavItem($pageNow, "Data Customer", $salesPrefix . "customer.php", "fa-solid fa-address-book", "Customer"); ?>
                
                <?php if ($role == 'Sales Manager') : ?>
                    <?php renderNavItem($pageNow, "Laporan", $salesPrefix . "laporan-cust.php", "fa-solid fa-file-contract", "Laporan Visit"); ?>
                    <?php renderNavItem($pageNow, "Data Sales", $salesPrefix . "sales.php", "fa-solid fa-user-group", "Tim Sales"); ?>
                <?php endif; ?>
                
                <?php renderNavHeader("Teknisi"); ?>
                <?php renderNavItem($pageNow, "Dashboard Teknisi", $rootPrefix . "index-sales.php", "fa-solid fa-chart-pie", "Dashboard Teknisi"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Baru", $rootPrefix . "kegiatan-baru.php", "fa-solid fa-bell-concierge", "Buat Request"); ?>
            <?php endif; ?>
            
            <?php if ($role == 'Teknisi') : ?>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="sidenav-footer">
        <a class="nav-link" href="<?php echo $rootPrefix; ?>data-admin.php">
             <i class="nav-icon fa-fw fa-solid fa-user"></i>
             <p>Data Admin</p>
        </a>
        <a class="nav-link" href="<?php echo $rootPrefix; ?>change_password.php">
             <i class="nav-icon fa-fw fa-solid fa-key"></i>
             <p>Ganti Password</p>
        </a>
        <a class="nav-link" href="<?php echo $rootPrefix; ?>../logout.php">
             <i class="nav-icon fa-fw fa-solid fa-right-from-bracket"></i>
             <p>Sign Out</p>
        </a>
    </div>
</aside>