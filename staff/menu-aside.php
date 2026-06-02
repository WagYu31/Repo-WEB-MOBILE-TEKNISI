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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    :root {
        --sidebar-bg: #111827;
        --sidebar-text: #9CA3AF;
        --sidebar-text-hover: #FFFFFF;
        --sidebar-bg-hover: #1F2937;
        --sidebar-active-text: #FFFFFF;
        --sidebar-active-bg: rgba(59, 130, 246, 0.1);
        --sidebar-active-indicator: #3B82F6;
        --sidebar-header-text: #6B7280;
        --sidebar-border: #374151;
    }

    .sidenav-gemini-dark {
        background-color: var(--sidebar-bg);
        border-right: none;
        width: 250px;
        height: calc(100vh - 2rem);
        top: 1rem !important;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }

    .sidenav-gemini-dark .navbar-collapse {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar { width: 6px; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-track { background: transparent; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-thumb { background: #4B5563; border-radius: 6px; }
    .sidenav-gemini-dark .navbar-collapse::-webkit-scrollbar-thumb:hover { background: #6B7280; }

    .sidenav-gemini-dark .sidenav-header {
        padding: 1.25rem 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 60px;
        border-bottom: 1px solid var(--sidebar-border);
    }
    .sidenav-gemini-dark .navbar-brand-img { max-height: 2.5rem; }

    .sidenav-gemini-dark .navbar-nav {
        padding: 0.75rem;
    }
    .sidenav-gemini-dark .nav-item { margin-bottom: 0.2rem; }
    .sidenav-gemini-dark .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        color: var(--sidebar-text);
        transition: background-color 0.2s ease, color 0.2s ease;
        position: relative;
    }
    .sidenav-gemini-dark .nav-link:hover {
        background-color: var(--sidebar-bg-hover);
        color: var(--sidebar-text-hover);
    }
    .sidenav-gemini-dark .nav-link.active {
        background-color: var(--sidebar-active-bg);
        color: var(--sidebar-active-text);
        font-weight: 500;
    }
    .sidenav-gemini-dark .nav-link.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 15%;
        height: 70%;
        width: 3px;
        background-color: var(--sidebar-active-indicator);
        border-top-right-radius: 3px;
        border-bottom-right-radius: 3px;
    }
    .sidenav-gemini-dark .nav-icon {
        width: 1.5rem;
        text-align: center;
        margin-right: 0.75rem;
        font-size: 0.9rem;
    }
    .sidenav-gemini-dark .nav-link p { margin: 0; font-size: 0.875rem; }

    .sidenav-gemini-dark .nav-header {
        padding: 1rem 1rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--sidebar-header-text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sidenav-gemini-dark .sidenav-footer {
        padding: 1rem;
        border-top: 1px solid var(--sidebar-border);
        margin-top: auto;
    }
    
    .sidenav-gemini-dark .btn-group-switcher a {
        background-color: #374151;
        color: #9CA3AF;
        border: none;
        padding: 0.6rem;
    }
    .sidenav-gemini-dark .btn-group-switcher a.active {
        background-color: var(--sidebar-active-indicator);
        color: #FFFFFF;
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