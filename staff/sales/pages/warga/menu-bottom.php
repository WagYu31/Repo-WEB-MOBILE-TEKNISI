<!-- Bottom Navbar -->
<nav class="navbar navbar-dark bg-primary navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100">
        <li class="nav-item">
            <a href="tagihan-warga.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons">dashboard</i>
                </div>
                <span class="text-xs d-block">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="sedekah.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">volunteer_activism</i>
                </div>
                <span class="small d-block">Sedekah</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="status_pembayaran.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">format_list_bulleted</i>
                </div>
                <span class="small d-block">Pembayaran</span>
            </a>
        </li>
        <li class="nav-item dropup">
            <a href="#" class="nav-link text-center text-white" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">settings</i>
                </div>
                <span class="small d-block">Setting</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">
                <a class="dropdown-item" href="profile.php">Profile</a>
                <a class="dropdown-item" href="../logout.php">Sign Out</a>
                <div class="dropdown-divider"></div>
                <?php
                if ($roleSesi == "rt") {
                ?>
                    <div class="dropdown-divider"></div>
                    <a class="btn bg-gradient-primary w-100" href="../rt/dashboard.php" type="button">MODE RT</a>
                <?php
                } else if ($roleSesi == "admin") {
                ?>
                    <div class="dropdown-divider"></div>
                    <a class="btn bg-gradient-primary w-100" href="../admin/dashboard.php" type="button">MODE RT</a>
                <?php
                } else if ($roleSesi == "sekretaris") {
                ?>
                    <div class="dropdown-divider"></div>
                    <a class="btn bg-gradient-primary w-100" href="../sekretaris/dashboard.php" type="button">MODE RT</a>
                <?php
                } else {
                }
                ?>
            </div>
        </li>
    </ul>
</nav>