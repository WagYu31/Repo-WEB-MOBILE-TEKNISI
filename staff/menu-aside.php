<?php
function renderNavHeader($title) {
    echo '<li class="nav-header">' . htmlspecialchars($title) . '</li>';
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
    /* ====== SIDEBAR - forced !important to beat material-dashboard.css ====== */
    
    /* Override framework .navbar-vertical.navbar-expand-xs max-width */
    .navbar-vertical.navbar-expand-xs.sidenav-gemini-dark {
        max-width: 250px !important;
        overflow-y: visible !important;
        overflow-x: visible !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .navbar-vertical.navbar-expand-xs.sidenav-gemini-dark .navbar-collapse {
        height: auto !important;
        overflow: visible !important;
    }
    /* Override framework nav-link margin that steals text space */
    .navbar-vertical.navbar-expand-xs.sidenav-gemini-dark .navbar-nav .nav-link {
        margin: 0 !important;
        margin-bottom: 0 !important;
        white-space: normal !important;
    }
    /* Override framework nav-link color */
    .navbar-vertical.sidenav-gemini-dark .navbar-nav .nav-link {
        color: #9CA3AF !important;
    }

    .sidenav-gemini-dark,
    .sidenav.sidenav-gemini-dark,
    aside.sidenav-gemini-dark {
        background-color: #111827 !important;
        background-image: none !important;
        border-right: none !important;
        width: 250px !important;
        max-width: 250px !important;
        height: calc(100vh - 2rem) !important;
        top: 1rem !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }

    .sidenav-gemini-dark .navbar-collapse {
        flex-grow: 1 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        height: auto !important;
    }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar { width: 6px; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-track { background: transparent; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-thumb { background: #4B5563; border-radius: 6px; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-thumb:hover { background: #6B7280; }

    .sidenav-gemini-dark .sidenav-header {
        padding: 1.25rem 1rem !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        height: 60px !important;
        border-bottom: 1px solid #374151 !important;
        background: transparent !important;
    }
    .sidenav-gemini-dark .navbar-brand-img { max-height: 2.5rem !important; }

    .sidenav-gemini-dark .navbar-nav { padding: 0.75rem !important; }
    .sidenav-gemini-dark .nav-item { margin-bottom: 0.2rem !important; }
    
    .sidenav-gemini-dark .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 0.75rem 1rem !important;
        border-radius: 0.375rem !important;
        color: #9CA3AF !important;
        background-color: transparent !important;
        transition: background-color 0.2s ease, color 0.2s ease !important;
        position: relative !important;
        box-shadow: none !important;
        margin: 0 !important;
    }
    .sidenav-gemini-dark .nav-link:hover {
        background-color: #1F2937 !important;
        color: #FFFFFF !important;
    }
    .sidenav-gemini-dark .nav-link.active {
        background-color: rgba(59, 130, 246, 0.1) !important;
        color: #FFFFFF !important;
        font-weight: 500 !important;
    }
    .sidenav-gemini-dark .nav-link.active::before {
        content: "" !important;
        position: absolute !important;
        left: 0 !important;
        top: 15% !important;
        height: 70% !important;
        width: 3px !important;
        background-color: #3B82F6 !important;
        border-top-right-radius: 3px !important;
        border-bottom-right-radius: 3px !important;
    }
    .sidenav-gemini-dark .nav-icon {
        width: 1.5rem !important;
        text-align: center !important;
        margin-right: 0.75rem !important;
        font-size: 0.9rem !important;
        color: inherit !important;
    }
    .sidenav-gemini-dark .nav-link p {
        margin: 0 !important;
        font-size: 0.875rem !important;
        color: inherit !important;
    }

    .sidenav-gemini-dark .nav-header {
        padding: 1rem 1rem 0.5rem !important;
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        color: #6B7280 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: transparent !important;
    }

    .sidenav-gemini-dark .sidenav-footer {
        padding: 1rem !important;
        border-top: 1px solid #374151 !important;
        margin-top: auto !important;
        background: transparent !important;
    }
    .sidenav-gemini-dark .sidenav-footer .nav-link {
        padding: 0.5rem 1rem !important;
    }
    
    .sidenav-gemini-dark .btn-group-switcher a {
        background-color: #374151 !important;
        color: #9CA3AF !important;
        border: none !important;
        padding: 0.6rem !important;
    }
    .sidenav-gemini-dark .btn-group-switcher a.active {
        background-color: #3B82F6 !important;
        color: #FFFFFF !important;
    }
</style>

<aside class="sidenav navbar navbar-vertical navbar-expand-xs sidenav-gemini-dark my-3 fixed-start ms-3" id="sidenav-main">
    <div class="sidenav-header">
        <a class="navbar-brand m-0" href="#">
            <img src="assets/img/logo/lwx-logo.png" class="navbar-brand-img mt-3 mb-2" style="width:auto; max-height:2.5em;" alt="main_logo">
        </a>
    </div>

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <?php if ($role == 'Super Admin') : ?>
            <?php endif; ?>

            <?php if ($role == 'Super Admin' || $role == 'Admin') : ?>
                <?php renderNavHeader("Operasional"); ?>
                <?php renderNavItem($pageNow, "Dashboard", "index-sa.php", "fa-solid fa-chart-pie", "Dashboard"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Baru", "kegiatan-baru.php", "fa-solid fa-file-circle-plus", "Tambah Kegiatan"); ?>
                <?php renderNavItem($pageNow, "Waiting List", "waiting-list.php", "fa-solid fa-hourglass-half", "Waiting List"); ?>

                <?php renderNavHeader("Laporan"); ?>
                <?php renderNavItem($pageNow, "Task", "task.php", "fa-solid fa-person-digging", "Kegiatan Teknisi"); ?>
                <?php renderNavItem($pageNow, "Laporan", "lap-kegiatan.php", "fa-solid fa-file-invoice", "Laporan Kegiatan"); ?>
                <?php renderNavItem($pageNow, "Pendapatan", "laporan.php", "fa-solid fa-hand-holding-dollar", "Pendapatan Teknisi"); ?>
                <?php renderNavItem($pageNow, "Progress Kegiatan", "lap-progress.php", "fa-solid fa-bars-progress", "Progress Kegiatan"); ?>

                <?php renderNavHeader("Manajemen Aset"); ?>
                <?php renderNavItem($pageNow, "Inventory", "inventory.php", "fa-solid fa-boxes-stacked", "Stok Barang"); ?>
                <?php renderNavItem($pageNow, "Peminjaman", "peminjaman.php", "fa-solid fa-right-left", "Peminjaman"); ?>
                <?php renderNavItem($pageNow, "Tutorial", "tutorial.php", "fa-solid fa-book", "Tutorial"); ?>

                <?php renderNavHeader("Data Master"); ?>
                <?php renderNavItem($pageNow, "Data Teknisi", "data-teknisi.php", "fa-solid fa-users-gear", "Teknisi"); ?>
                <?php renderNavItem($pageNow, "Data Customer", "customer.php", "fa-solid fa-users", "Customer"); ?>
            <?php endif; ?>

            <?php if ($role == 'Sales Manager' || $role == 'Sales') : ?>
                <?php renderNavHeader("Sales"); ?>
                <?php renderNavItem($pageNow, ($role == 'Sales Manager' ? "Dashboard" : "Dashboard Sales"), "sales/index-sa.php", "fa-solid fa-chart-line", "Dashboard"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Saya", "sales/sales/index.php", "fa-solid fa-user-check", "Kegiatan Saya"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Sales", "sales/kegiatan-baru.php", "fa-solid fa-map-location-dot", "Visit Customer"); ?>
                <?php renderNavItem($pageNow, "Data Customer", "sales/customer.php", "fa-solid fa-address-book", "Customer"); ?>
                
                <?php if ($role == 'Sales Manager') : ?>
                    <?php renderNavItem($pageNow, "Laporan", "sales/laporan-cust.php", "fa-solid fa-file-contract", "Laporan Visit"); ?>
                    <?php renderNavItem($pageNow, "Data Sales", "sales/sales.php", "fa-solid fa-user-group", "Tim Sales"); ?>
                <?php endif; ?>
                
                <?php renderNavHeader("Teknisi"); ?>
                <?php renderNavItem($pageNow, "Dashboard Teknisi", "index-sales.php", "fa-solid fa-chart-pie", "Dashboard Teknisi"); ?>
                <?php renderNavItem($pageNow, "Kegiatan Baru", "kegiatan-baru.php", "fa-solid fa-bell-concierge", "Buat Request"); ?>
            <?php endif; ?>
            
            <?php if ($role == 'Teknisi') : ?>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="sidenav-footer">
        <a class="nav-link" href="data-admin.php">
             <i class="nav-icon fa-fw fa-solid fa-user"></i>
             <p>Data Admin</p>
        </a>
        <a class="nav-link" href="change_password.php">
             <i class="nav-icon fa-fw fa-solid fa-key"></i>
             <p>Ganti Password</p>
        </a>
        <a class="nav-link" href="../logout.php">
             <i class="nav-icon fa-fw fa-solid fa-right-from-bracket"></i>
             <p>Sign Out</p>
        </a>
    </div>
</aside>