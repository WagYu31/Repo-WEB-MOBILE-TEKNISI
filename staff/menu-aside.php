<?php
function renderNavHeader($title) {
    echo '<li class="nav-header" style="padding:16px 16px 8px;font-size:0.65rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;list-style:none;">' . htmlspecialchars($title) . '</li>';
}

function renderNavItem($isActive, $url, $icon, $text) {
    $activeClass = $isActive ? 'active' : '';
    echo '
    <li class="nav-item">
        <a class="nav-link ' . $activeClass . '" href="' . $url . '">
            <i class="nav-icon fa-fw ' . $icon . '"></i>
            <p>' . htmlspecialchars($text) . '</p>
        </a>
    </li>';
}

function renderSubNavItem($isActive, $url, $icon, $text) {
    $activeClass = $isActive ? 'active' : '';
    echo '
    <li class="nav-item" style="list-style: none;">
        <a class="nav-link ' . $activeClass . '" href="' . $url . '">
            <i class="nav-icon fa-fw ' . $icon . '"></i>
            <span>' . htmlspecialchars($text) . '</span>
        </a>
    </li>';
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" />

<style>
    /* ==========================================================
       SIDEBAR — Modern Redesign inspired by Image 2
       ========================================================== */

    #sidenav-main {
        background: #111c2a !important;
        background-color: #111c2a !important;
        background-image: none !important;
        width: 250px !important;
        max-width: 250px !important;
        min-width: 250px !important;
        height: calc(100vh - 2rem) !important;
        top: 1rem !important;
        border: none !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.35) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding: 0 !important;
        z-index: 1040 !important;
    }

    #sidenav-main.bg-white,
    #sidenav-main.bg-transparent {
        background: #111c2a !important;
        background-color: #111c2a !important;
    }

    /* Top Profile Card Banner (Deep Teal) */
    #sidenav-main .sidebar-top-card {
        background: linear-gradient(180deg, #093744 0%, #0d4452 60%, #102e38 100%) !important;
        padding: 16px 14px 18px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        flex-shrink: 0 !important;
        text-align: center !important;
        position: relative !important;
    }

    #sidenav-main .sidebar-brand-area {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        margin-bottom: 8px !important;
    }

    #sidenav-main .sidebar-brand-img {
        max-height: 2.2rem !important;
        width: auto !important;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));
    }

    #sidenav-main .sidebar-profile-box {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
    }

    #sidenav-main .sidebar-avatar-ring {
        width: 64px !important;
        height: 64px !important;
        border-radius: 50% !important;
        padding: 3px !important;
        background: conic-gradient(#ffffff 0deg, #38bdf8 260deg, transparent 260deg 360deg) !important;
        box-shadow: 0 4px 15px rgba(56, 189, 248, 0.28) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-bottom: 6px !important;
        position: relative !important;
    }

    #sidenav-main .sidebar-avatar-circle {
        width: 100% !important;
        height: 100% !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #1b3846 0%, #0e242d 100%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #ffffff !important;
        border: 2px solid #0d3b46 !important;
        overflow: hidden !important;
    }

    #sidenav-main .sidebar-avatar-img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 50% !important;
    }

    #sidenav-main .avatar-initials {
        font-weight: 700 !important;
        font-size: 1.15rem !important;
        color: #ffffff !important;
        letter-spacing: 0.5px !important;
    }

    #sidenav-main .sidebar-user-name {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.92rem !important;
        letter-spacing: 0.3px !important;
        line-height: 1.3 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 210px !important;
    }

    #sidenav-main .sidebar-user-email {
        color: #a5f3fc !important;
        font-size: 0.72rem !important;
        opacity: 0.85 !important;
        margin-top: 1px !important;
        letter-spacing: 0.2px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 210px !important;
    }

    /* Scrollable Nav Collapse */
    #sidenav-main .navbar-collapse {
        display: block !important;
        flex: 1 1 0% !important;
        height: 0 !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        padding-top: 8px !important;
    }
    #sidenav-main .navbar-collapse::-webkit-scrollbar { width: 4px; }
    #sidenav-main .navbar-collapse::-webkit-scrollbar-track { background: transparent; }
    #sidenav-main .navbar-collapse::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

    /* Nav List */
    #sidenav-main .navbar-nav {
        padding: 0.4rem 0 0.4rem 0.6rem !important;
    }

    #sidenav-main .nav-item {
        margin: 0 !important;
        margin-bottom: 3px !important;
        width: 100% !important;
    }

    /* Inactive Nav Links */
    #sidenav-main .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 0.58rem 0.85rem !important;
        margin: 0 !important;
        margin-right: 0.6rem !important;
        border-radius: 12px !important;
        color: #e2e8f0 !important;
        background: transparent !important;
        box-shadow: none !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        transition: all 0.2s ease !important;
        position: relative !important;
        font-weight: 500 !important;
        font-size: 0.86rem !important;
    }

    #sidenav-main .nav-link:hover {
        background: rgba(255, 255, 255, 0.07) !important;
        color: #ffffff !important;
        transform: translateX(2px) !important;
    }

    /* Golden/Amber icons for inactive items (Image 2 style) */
    #sidenav-main .nav-link .nav-icon,
    #sidenav-main .nav-link > i {
        width: 1.35rem !important;
        min-width: 1.35rem !important;
        text-align: center !important;
        margin-right: 0.65rem !important;
        font-size: 0.92rem !important;
        color: #f59e0b !important; /* Golden/amber */
        line-height: 1.5 !important;
        transition: color 0.2s ease !important;
    }

    #sidenav-main .nav-link:hover .nav-icon,
    #sidenav-main .nav-link:hover > i {
        color: #fde047 !important; /* Bright gold on hover */
    }

    /* Active Tab with Inverted Curve Scoop (Image 2 signature effect) */
    #sidenav-main .nav-link.active {
        background: #f0f2f5 !important; /* Matches body background */
        color: #0b3846 !important;
        font-weight: 700 !important;
        border-radius: 26px 0 0 26px !important;
        margin-right: 0 !important;
        position: relative !important;
        box-shadow: -4px 0 12px rgba(0, 0, 0, 0.08) !important;
    }

    #sidenav-main .nav-link.active .nav-icon,
    #sidenav-main .nav-link.active > i {
        color: #0d9488 !important; /* Deep vibrant teal */
    }

    #sidenav-main .nav-link.active p,
    #sidenav-main .nav-link.active span {
        color: #0b3846 !important;
        font-weight: 700 !important;
    }

    /* Concave curve scoops on top-right and bottom-right */
    #sidenav-main .nav-link.active::before {
        content: "" !important;
        position: absolute !important;
        top: -16px !important;
        right: 0 !important;
        width: 16px !important;
        height: 16px !important;
        background: transparent !important;
        border-bottom-right-radius: 16px !important;
        box-shadow: 5px 5px 0 5px #f0f2f5 !important;
        pointer-events: none !important;
    }

    #sidenav-main .nav-link.active::after {
        content: "" !important;
        position: absolute !important;
        bottom: -16px !important;
        right: 0 !important;
        width: 16px !important;
        height: 16px !important;
        background: transparent !important;
        border-top-right-radius: 16px !important;
        box-shadow: 5px -5px 0 5px #f0f2f5 !important;
        pointer-events: none !important;
    }

    /* Text */
    #sidenav-main .nav-link p,
    #sidenav-main .nav-link span {
        margin: 0 !important;
        font-size: 0.86rem !important;
        color: inherit !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Section headers */
    #sidenav-main .nav-header {
        padding: 1.1rem 0.75rem 0.45rem !important;
        font-size: 0.725rem !important;
        font-weight: 700 !important;
        color: #94a3b8 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        background: transparent !important;
        list-style: none !important;
    }

    /* Chevron for collapsible parent */
    #sidenav-main .nav-link .collapse-chevron {
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        color: #94a3b8 !important;
        font-size: 0.72rem !important;
    }
    #sidenav-main .nav-link[aria-expanded="true"] .collapse-chevron {
        transform: rotate(90deg) !important;
        color: #38bdf8 !important;
    }
    #sidenav-main .nav-link[aria-expanded="true"] {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.05) !important;
        border-radius: 12px !important;
    }
    #sidenav-main .nav-link[data-bs-toggle="collapse"]::after {
        display: none !important;
        content: none !important;
    }

    /* Sub-menu styling */
    #sidenav-main .nav-item .collapse ul {
        border-left: 2px solid rgba(255, 255, 255, 0.08) !important;
        margin-left: 18px !important;
        padding-left: 6px !important;
        margin-top: 2px !important;
        margin-bottom: 4px !important;
    }
    #sidenav-main .nav-item .collapse .nav-link {
        padding: 0.48rem 0.75rem !important;
        font-size: 0.82rem !important;
        color: #cbd5e1 !important;
    }
    #sidenav-main .nav-item .collapse .nav-link .nav-icon {
        font-size: 0.78rem !important;
        color: #94a3b8 !important;
    }
    #sidenav-main .nav-item .collapse .nav-link:hover .nav-icon {
        color: #f59e0b !important;
    }
    #sidenav-main .nav-item .collapse .nav-link.active {
        background: #f0f2f5 !important;
        color: #0b3846 !important;
        border-radius: 20px 0 0 20px !important;
        margin-right: 0 !important;
    }
    #sidenav-main .nav-item .collapse .nav-link.active .nav-icon {
        color: #0d9488 !important;
    }

    /* Footer Section */
    #sidenav-main .sidenav-footer {
        padding: 0.65rem 0.6rem !important;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        margin-top: auto !important;
        background: rgba(0, 0, 0, 0.15) !important;
        flex-shrink: 0 !important;
    }
    #sidenav-main .sidenav-footer .nav-link {
        display: flex !important;
        align-items: center !important;
        padding: 0.48rem 0.75rem !important;
        margin-bottom: 2px !important;
        border-radius: 10px !important;
        color: #cbd5e1 !important;
        font-size: 0.84rem !important;
    }
    #sidenav-main .sidenav-footer .nav-link:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
    }
    #sidenav-main .sidenav-footer .nav-link .nav-icon {
        color: #f59e0b !important;
    }
    #sidenav-main .sidenav-footer .nav-link.link-signout:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #fca5a5 !important;
    }
    #sidenav-main .sidenav-footer .nav-link.link-signout:hover .nav-icon {
        color: #ef4444 !important;
    }
</style>
<?php
// Calculate directory prefix dynamically to share the same menu-aside.php between root and sales subdirectory
$isSalesDir = (basename(getcwd()) === 'sales');
$rootPrefix = $isSalesDir ? '../' : '';
$salesPrefix = $isSalesDir ? '' : 'sales/';

if ($isSalesDir) {
    include_once "../menu-access-helper.php";
} else {
    include_once "menu-access-helper.php";
}

// Retrieve user info for sidebar profile banner (matching Image 2)
$sidebarUserName = !empty($nmUser) ? $nmUser : (!empty($_SESSION['name']) ? $_SESSION['name'] : '');
$sidebarUserRole = !empty($role) ? $role : (!empty($_SESSION['jabatan']) ? $_SESSION['jabatan'] : '');
$sidebarUserEmail = '';

if (empty($sidebarUserName) || empty($sidebarUserEmail)) {
    if (!empty($conn) && !empty($idSesi)) {
        $qSidebarUser = mysqli_query($conn, "SELECT name, email, jabatan FROM users WHERE id = '" . mysqli_real_escape_string($conn, $idSesi) . "' LIMIT 1");
        if ($qSidebarUser && mysqli_num_rows($qSidebarUser) > 0) {
            $rSUser = mysqli_fetch_assoc($qSidebarUser);
            if (empty($sidebarUserName)) $sidebarUserName = $rSUser['name'];
            if (empty($sidebarUserRole)) $sidebarUserRole = $rSUser['jabatan'];
            $sidebarUserEmail = $rSUser['email'] ?? '';
        }
    }
}

if (empty($sidebarUserName)) $sidebarUserName = 'Administrator';
if (empty($sidebarUserRole)) $sidebarUserRole = 'Staff';
if (empty($sidebarUserEmail)) {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sidebarUserName));
    $sidebarUserEmail = $cleanName . '@loewix.id';
}

$nameParts = explode(' ', trim($sidebarUserName));
$initials = (count($nameParts) >= 2) 
    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
    : strtoupper(substr($sidebarUserName, 0, 2));
?>
<aside class="sidenav navbar navbar-vertical navbar-expand-xs fixed-start ms-3 my-3" id="sidenav-main">
    <!-- Top Profile & Brand Card (Image 2 style) -->
    <div class="sidebar-top-card">
        <div class="sidebar-brand-area">
            <a href="<?php echo $rootPrefix; ?>index-sa.php" class="d-inline-block">
                <img src="<?php echo $rootPrefix; ?>assets/img/logo/lwx-logo.png" class="sidebar-brand-img" alt="Loewix CCTV">
            </a>
        </div>
        <div class="sidebar-profile-box">
            <div class="sidebar-avatar-ring">
                <div class="sidebar-avatar-circle">
                    <?php if (file_exists(__DIR__ . '/assets/img/user.png')): ?>
                        <img src="<?php echo $rootPrefix; ?>assets/img/user.png" alt="Avatar" class="sidebar-avatar-img">
                    <?php else: ?>
                        <span class="avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sidebar-user-name" title="<?php echo htmlspecialchars($sidebarUserName); ?>"><?php echo htmlspecialchars($sidebarUserName); ?></div>
            <div class="sidebar-user-email" title="<?php echo htmlspecialchars($sidebarUserEmail); ?>"><?php echo htmlspecialchars($sidebarUserEmail); ?></div>
        </div>
    </div>

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <?php
            // Check menu access permissions dynamically
            $showDashboard = hasMenuAccess($conn, $idSesi, 'dashboard', ($role == 'Super Admin' || $role == 'Admin'));
            $showLiveTracking = hasMenuAccess($conn, $idSesi, 'live_tracking', ($role == 'Super Admin' || $role == 'Admin'));
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
            $showTiptok = hasMenuAccess($conn, $idSesi, 'tiptok', ($role == 'Super Admin' || $role == 'Admin' || $role == 'Sales Manager' || $role == 'Sales'));

            $showKegiatanSaya = hasMenuAccess($conn, $idSesi, 'kegiatan_saya', ($role == 'Sales Manager' || $role == 'Sales'));
            $showDashboardTeknisi = hasMenuAccess($conn, $idSesi, 'dashboard_teknisi', ($role == 'Sales Manager' || $role == 'Sales'));
            $showBuatRequest = hasMenuAccess($conn, $idSesi, 'buat_request', ($role == 'Sales Manager' || $role == 'Sales'));

            // Render menus according to user permissions
            if ($role == 'Super Admin' || $role == 'Admin') {
                // Category: Operasional
                if ($showDashboard || $showTambahKegiatan || $showWaitingList || $showLiveTracking) {
                    $isOperasionalActive = ($pageNow == "Dashboard" && !$isSalesDir) || ($pageNow == "Kegiatan Baru" && !$isSalesDir) || ($pageNow == "Waiting List" && !$isSalesDir);
                    $operasionalCollapse = $isOperasionalActive ? 'show' : '';
                    $operasionalAria = $isOperasionalActive ? 'true' : 'false';
                    $operasionalLinkClass = $isOperasionalActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $operasionalLinkClass; ?>" data-bs-toggle="collapse" href="#collapseOperasional" role="button" aria-expanded="<?php echo $operasionalAria; ?>" aria-controls="collapseOperasional">
                            <i class="nav-icon fa-fw fa-solid fa-gears"></i>
                            <p class="flex-grow-1">Operasional</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $operasionalCollapse; ?>" id="collapseOperasional">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showDashboard) renderSubNavItem(($pageNow == "Dashboard" && !$isSalesDir), $rootPrefix . "index-sa.php", "fa-solid fa-chart-pie", "Dashboard");
                                if ($showLiveTracking) renderSubNavItem(false, "/admin_live_tracking.php", "fa-solid fa-map-location-dot", "Live Tracking Teknisi");
                                if ($showTambahKegiatan) renderSubNavItem(($pageNow == "Kegiatan Baru" && !$isSalesDir), $rootPrefix . "kegiatan-baru.php", "fa-solid fa-file-circle-plus", "Tambah Kegiatan");
                                if ($showWaitingList) renderSubNavItem(($pageNow == "Waiting List" && !$isSalesDir), $rootPrefix . "waiting-list.php", "fa-solid fa-hourglass-half", "Waiting List");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }

                // Category: Laporan
                if ($showKegiatanTeknisi || $showLaporanKegiatan || $showTargetTercapai || $showProgressKegiatan) {
                    $isLaporanActive = ($pageNow == "Task" && !$isSalesDir) || ($pageNow == "Laporan" && !$isSalesDir) || ($pageNow == "Target Tercapai" && !$isSalesDir) || ($pageNow == "Progress Kegiatan" && !$isSalesDir);
                    $laporanCollapse = $isLaporanActive ? 'show' : '';
                    $laporanAria = $isLaporanActive ? 'true' : 'false';
                    $laporanLinkClass = $isLaporanActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $laporanLinkClass; ?>" data-bs-toggle="collapse" href="#collapseLaporan" role="button" aria-expanded="<?php echo $laporanAria; ?>" aria-controls="collapseLaporan">
                            <i class="nav-icon fa-fw fa-solid fa-file-lines"></i>
                            <p class="flex-grow-1">Laporan</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $laporanCollapse; ?>" id="collapseLaporan">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showKegiatanTeknisi) renderSubNavItem(($pageNow == "Task" && !$isSalesDir), $rootPrefix . "task.php", "fa-solid fa-person-digging", "Kegiatan Teknisi");
                                if ($showLaporanKegiatan) renderSubNavItem(($pageNow == "Laporan" && !$isSalesDir), $rootPrefix . "lap-kegiatan.php", "fa-solid fa-file-invoice", "Laporan Kegiatan");
                                if ($showTargetTercapai) renderSubNavItem(($pageNow == "Target Tercapai" && !$isSalesDir), $rootPrefix . "laporan.php", "fa-solid fa-hand-holding-dollar", "Target Tercapai Teknisi");
                                if ($showProgressKegiatan) renderSubNavItem(($pageNow == "Progress Kegiatan" && !$isSalesDir), $rootPrefix . "lap-progress.php", "fa-solid fa-bars-progress", "Progress Kegiatan");
                                if ($showLaporanKegiatan) renderSubNavItem(($pageNow == "Backup Data Teknisi" && !$isSalesDir), $rootPrefix . "restore_kegiatan.php", "fa-solid fa-database", "Backup Data Teknisi");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }

                // Category: Manajemen Aset
                if ($showStokBarang || $showPeminjaman || $showTutorial) {
                    $isAsetActive = ($pageNow == "Inventory" && !$isSalesDir) || ($pageNow == "Peminjaman" && !$isSalesDir) || ($pageNow == "Tutorial" && !$isSalesDir);
                    $asetCollapse = $isAsetActive ? 'show' : '';
                    $asetAria = $isAsetActive ? 'true' : 'false';
                    $asetLinkClass = $isAsetActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $asetLinkClass; ?>" data-bs-toggle="collapse" href="#collapseAset" role="button" aria-expanded="<?php echo $asetAria; ?>" aria-controls="collapseAset">
                            <i class="nav-icon fa-fw fa-solid fa-boxes-stacked"></i>
                            <p class="flex-grow-1">Manajemen Aset</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $asetCollapse; ?>" id="collapseAset">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showStokBarang) renderSubNavItem(($pageNow == "Inventory" && !$isSalesDir), $rootPrefix . "inventory.php", "fa-solid fa-box", "Stok Barang");
                                if ($showPeminjaman) renderSubNavItem(($pageNow == "Peminjaman" && !$isSalesDir), $rootPrefix . "peminjaman.php", "fa-solid fa-right-left", "Peminjaman");
                                if ($showTutorial) renderSubNavItem(($pageNow == "Tutorial" && !$isSalesDir), $rootPrefix . "tutorial.php", "fa-solid fa-book-open", "Tutorial");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }

                // Category: Data Master
                if ($showTeknisi || $showCustomer) {
                    $isMasterActive = ($pageNow == "Data Teknisi" && !$isSalesDir) || ($pageNow == "Data Customer" && !$isSalesDir);
                    $masterCollapse = $isMasterActive ? 'show' : '';
                    $masterAria = $isMasterActive ? 'true' : 'false';
                    $masterLinkClass = $isMasterActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $masterLinkClass; ?>" data-bs-toggle="collapse" href="#collapseMaster" role="button" aria-expanded="<?php echo $masterAria; ?>" aria-controls="collapseMaster">
                            <i class="nav-icon fa-fw fa-solid fa-database"></i>
                            <p class="flex-grow-1">Data Master</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $masterCollapse; ?>" id="collapseMaster">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showTeknisi) renderSubNavItem(($pageNow == "Data Teknisi" && !$isSalesDir), $rootPrefix . "data-teknisi.php", "fa-solid fa-users-gear", "Teknisi");
                                if ($showCustomer) renderSubNavItem(($pageNow == "Data Customer" && !$isSalesDir), $rootPrefix . "customer.php", "fa-solid fa-users", "Customer");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }

                // Category: Aplikasi Sales
                if ($showDashboardSales || $showDataSales || $showJadwalKunjungan || $showLaporanVisit || $showCustomerSales || $showTiptok) {
                    $isSalesActive = ($pageNow == "Dashboard Sales" || ($pageNow == "Dashboard" && $isSalesDir)) || ($pageNow == "Kegiatan Baru" && $isSalesDir) || ($pageNow == "Laporan" && $isSalesDir) || ($pageNow == "Data Customer" && $isSalesDir) || ($pageNow == "Sales" && $isSalesDir) || ($pageNow == "Scraping GMaps") || ($pageNow == "TIP TOK");
                    $salesCollapse = $isSalesActive ? 'show' : '';
                    $salesAria = $isSalesActive ? 'true' : 'false';
                    $salesLinkClass = $isSalesActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $salesLinkClass; ?>" data-bs-toggle="collapse" href="#collapseSales" role="button" aria-expanded="<?php echo $salesAria; ?>" aria-controls="collapseSales">
                            <i class="nav-icon fa-fw fa-solid fa-chart-line"></i>
                            <p class="flex-grow-1">Aplikasi Sales</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $salesCollapse; ?>" id="collapseSales">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showDashboardSales) renderSubNavItem(($pageNow == "Dashboard Sales" || ($pageNow == "Dashboard" && $isSalesDir)), $salesPrefix . "index-sa.php", "fa-solid fa-gauge-high", "Dashboard Sales");
                                if ($showJadwalKunjungan) renderSubNavItem(($pageNow == "Kegiatan Baru" && $isSalesDir), $salesPrefix . "kegiatan-baru.php", "fa-solid fa-map-location-dot", "Jadwal Kunjungan");
                                if ($showLaporanVisit) renderSubNavItem(($pageNow == "Laporan" && $isSalesDir), $salesPrefix . "laporan-cust.php", "fa-solid fa-file-contract", "Laporan Visit");
                                if ($showCustomerSales) renderSubNavItem(($pageNow == "Data Customer" && $isSalesDir), $salesPrefix . "customer.php", "fa-solid fa-address-book", "Customer Sales");
                                if ($showTiptok) renderSubNavItem(($pageNow == "TIP TOK"), $salesPrefix . "tiptok.php", "fa-solid fa-boxes-packing", "TIP TOK");
                                if ($showDataSales) renderSubNavItem(($pageNow == "Sales" && $isSalesDir), $salesPrefix . "sales.php", "fa-solid fa-user-group", "Data Sales");
                                if ($role == 'Super Admin' || $role == 'Admin') renderSubNavItem(($pageNow == "Scraping GMaps"), $salesPrefix . "scraping-gmaps.php", "fa-solid fa-satellite-dish", "Scraping GMaps");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }
            } elseif ($role == 'Sales Manager' || $role == 'Sales') {
                // Category: Sales Menu for Staff
                if ($showKegiatanSaya || $showDashboardSales || $showJadwalKunjungan || $showCustomerSales || $showTiptok) {
                    $isSalesStaffActive = ($pageNow == "Dashboard Sales" || ($pageNow == "Dashboard" && $isSalesDir)) || ($pageNow == "Kegiatan Saya" && $isSalesDir) || ($pageNow == "Kegiatan Baru" && $isSalesDir) || ($pageNow == "Laporan" && $isSalesDir) || ($pageNow == "Data Customer" && $isSalesDir) || ($pageNow == "Sales" && $isSalesDir) || ($pageNow == "TIP TOK");
                    $salesStaffCollapse = $isSalesStaffActive ? 'show' : '';
                    $salesStaffAria = $isSalesStaffActive ? 'true' : 'false';
                    $salesStaffLinkClass = $isSalesStaffActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $salesStaffLinkClass; ?>" data-bs-toggle="collapse" href="#collapseSalesStaff" role="button" aria-expanded="<?php echo $salesStaffAria; ?>" aria-controls="collapseSalesStaff">
                            <i class="nav-icon fa-fw fa-solid fa-chart-line"></i>
                            <p class="flex-grow-1">Sales</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $salesStaffCollapse; ?>" id="collapseSalesStaff">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showDashboardSales) renderSubNavItem(($pageNow == "Dashboard Sales" || ($pageNow == "Dashboard" && $isSalesDir)), $salesPrefix . "index-sa.php", "fa-solid fa-gauge-high", "Dashboard");
                                if ($showKegiatanSaya) renderSubNavItem(($pageNow == "Kegiatan Saya" && $isSalesDir), $salesPrefix . "sales/index.php", "fa-solid fa-user-check", "Kegiatan Saya");
                                if ($showJadwalKunjungan) renderSubNavItem(($pageNow == "Kegiatan Baru" && $isSalesDir), $salesPrefix . "kegiatan-baru.php", "fa-solid fa-map-location-dot", "Visit Customer");
                                if ($role == 'Sales Manager' && $showLaporanVisit) renderSubNavItem(($pageNow == "Laporan" && $isSalesDir), $salesPrefix . "laporan-cust.php", "fa-solid fa-file-contract", "Laporan Visit");
                                if ($showCustomerSales) renderSubNavItem(($pageNow == "Data Customer" && $isSalesDir), $salesPrefix . "customer.php", "fa-solid fa-address-book", "Customer");
                                if ($showTiptok) renderSubNavItem(($pageNow == "TIP TOK"), $salesPrefix . "tiptok.php", "fa-solid fa-boxes-packing", "TIP TOK");
                                if ($role == 'Sales Manager' && $showDataSales) renderSubNavItem(($pageNow == "Sales" && $isSalesDir), $salesPrefix . "sales.php", "fa-solid fa-user-group", "Tim Sales");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }

                // Category: Teknisi Menu for Staff
                if ($showDashboardTeknisi || $showBuatRequest) {
                    $isTeknisiStaffActive = ($pageNow == "Dashboard Teknisi" && !$isSalesDir) || ($pageNow == "Kegiatan Baru" && !$isSalesDir);
                    $teknisiStaffCollapse = $isTeknisiStaffActive ? 'show' : '';
                    $teknisiStaffAria = $isTeknisiStaffActive ? 'true' : 'false';
                    $teknisiStaffLinkClass = $isTeknisiStaffActive ? '' : 'collapsed';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $teknisiStaffLinkClass; ?>" data-bs-toggle="collapse" href="#collapseTeknisiStaff" role="button" aria-expanded="<?php echo $teknisiStaffAria; ?>" aria-controls="collapseTeknisiStaff">
                            <i class="nav-icon fa-fw fa-solid fa-wrench"></i>
                            <p class="flex-grow-1">Teknisi</p>
                            <i class="collapse-chevron fa-solid fa-chevron-right ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $teknisiStaffCollapse; ?>" id="collapseTeknisiStaff">
                            <ul class="nav flex-column ms-3 ps-2" style="border-left: 1.5px solid #374151; margin-left: 18px; padding-left: 8px;">
                                <?php
                                if ($showDashboardTeknisi) renderSubNavItem(($pageNow == "Dashboard Teknisi" && !$isSalesDir), $rootPrefix . "index-sales.php", "fa-solid fa-chart-pie", "Dashboard Teknisi");
                                if ($showBuatRequest) renderSubNavItem(($pageNow == "Kegiatan Baru" && !$isSalesDir), $rootPrefix . "kegiatan-baru.php", "fa-solid fa-bell-concierge", "Buat Request");
                                ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
    </div>
    
    <div class="sidenav-footer">
        <?php if ($role == 'Super Admin') : ?>
            <a class="nav-link" href="<?php echo $rootPrefix; ?>kelola-akses.php">
                 <i class="nav-icon fa-fw fa-solid fa-user-shield"></i>
                 <p>Kelola Akses Menu</p>
            </a>
        <?php endif; ?>
        <a class="nav-link" href="<?php echo $rootPrefix; ?>data-admin.php">
             <i class="nav-icon fa-fw fa-solid fa-user"></i>
             <p>Data Admin</p>
        </a>
        <a class="nav-link" href="<?php echo $rootPrefix; ?>change_password.php">
             <i class="nav-icon fa-fw fa-solid fa-key"></i>
             <p>Ganti Password</p>
        </a>
        <a class="nav-link link-signout" href="<?php echo $rootPrefix; ?>../logout.php">
             <i class="nav-icon fa-fw fa-solid fa-right-from-bracket"></i>
             <p>Sign Out</p>
        </a>
    </div>
</aside>