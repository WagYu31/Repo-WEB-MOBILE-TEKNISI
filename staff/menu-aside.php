<?php
function renderNavHeader($title) {
    echo '<li style="padding:16px 16px 8px;font-size:0.65rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;list-style:none;">' . htmlspecialchars($title) . '</li>';
}

function renderNavItem($pageNow, $targetPage, $url, $icon, $text) {
    $isActive = ($pageNow == $targetPage);
    $bgColor = $isActive ? 'rgba(59,130,246,0.12)' : 'transparent';
    $textColor = $isActive ? '#FFFFFF' : '#9CA3AF';
    $fontWeight = $isActive ? '500' : '400';
    $indicator = $isActive ? '<span style="position:absolute;left:0;top:15%;height:70%;width:3px;background:#3B82F6;border-radius:0 3px 3px 0;"></span>' : '';
    
    echo '
    <li style="list-style:none;margin-bottom:2px;">
        <a href="' . $url . '" style="display:flex;align-items:center;padding:10px 12px;border-radius:6px;color:' . $textColor . ';background:' . $bgColor . ';text-decoration:none;position:relative;font-weight:' . $fontWeight . ';" 
           onmouseover="this.style.background=\'' . ($isActive ? 'rgba(59,130,246,0.12)' : '#1F2937') . '\';this.style.color=\'#fff\';" 
           onmouseout="this.style.background=\'' . $bgColor . '\';this.style.color=\'' . $textColor . '\';">
            ' . $indicator . '
            <i class="' . $icon . '" style="width:20px;text-align:center;margin-right:10px;font-size:14px;flex-shrink:0;"></i>
            <span style="font-size:13px;white-space:nowrap;">' . htmlspecialchars($text) . '</span>
        </a>
    </li>';
}
?>

<aside id="sidenav-main" style="
    position:fixed;
    left:0;
    top:0;
    bottom:0;
    width:250px;
    max-width:250px;
    margin:1rem 0 1rem 1rem;
    height:calc(100vh - 2rem);
    background:#111827;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    z-index:999;
    box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);
">
    <!-- Logo -->
    <div style="padding:20px 16px;display:flex;justify-content:center;align-items:center;border-bottom:1px solid #374151;flex-shrink:0;">
        <a href="#">
            <img src="assets/img/logo/lwx-logo.png" style="max-height:2.5em;width:auto;" alt="main_logo">
        </a>
    </div>

    <!-- Menu Scrollable Area -->
    <div style="flex:1;overflow-y:auto;overflow-x:hidden;padding:8px 12px;">
        <ul style="list-style:none;padding:0;margin:0;">
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
    
    <!-- Footer -->
    <div style="padding:12px;border-top:1px solid #374151;flex-shrink:0;">
        <a href="data-admin.php" style="display:flex;align-items:center;padding:8px 12px;color:#9CA3AF;text-decoration:none;font-size:13px;border-radius:6px;" onmouseover="this.style.background='#1F2937';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='#9CA3AF';">
            <i class="fa-solid fa-user" style="width:20px;text-align:center;margin-right:10px;font-size:14px;"></i>
            <span>Data Admin</span>
        </a>
        <a href="change_password.php" style="display:flex;align-items:center;padding:8px 12px;color:#9CA3AF;text-decoration:none;font-size:13px;border-radius:6px;" onmouseover="this.style.background='#1F2937';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='#9CA3AF';">
            <i class="fa-solid fa-key" style="width:20px;text-align:center;margin-right:10px;font-size:14px;"></i>
            <span>Ganti Password</span>
        </a>
        <a href="../logout.php" style="display:flex;align-items:center;padding:8px 12px;color:#9CA3AF;text-decoration:none;font-size:13px;border-radius:6px;" onmouseover="this.style.background='#1F2937';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='#9CA3AF';">
            <i class="fa-solid fa-right-from-bracket" style="width:20px;text-align:center;margin-right:10px;font-size:14px;"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>