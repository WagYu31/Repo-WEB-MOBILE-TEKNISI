<!-- Bottom Navbar -->
<nav class="navbar navbar-dark bg-primary navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons">dashboard</i>
                </div>
                <span class="text-xs d-block">Dashboard</span>
            </a>
        </li>
        <li class="nav-item dropup">
            <a href="#" class="nav-link text-center text-white" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">receipt_long</i>
                </div>
                <span class="small d-block">Tagihan</span>
            </a>
            <!-- Dropup menu for profile -->
            <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">
                <a class="dropdown-item" href="verifikasi_pembayaran.php">Verifikasi Pembayaran</a>
                <a class="dropdown-item" href="data_pembayaran.php">Data Pembayaran</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="tagihan.php">Tagihan</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">person</i>
                </div>
                <span class="small d-block">Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="../logout.php" class="nav-link text-center text-white">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons opacity-10">login</i>
                </div>
                <span class="small d-block">Sign Out</span>
            </a>
        </li>
    </ul>
</nav>