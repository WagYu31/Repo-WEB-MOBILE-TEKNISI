<!-- Bottom Navbar -->
<nav class="navbar navbar-dark bg-gradient-info navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100">
        <?php
        if ($role == 'Sales Manager' || $role == 'Sales') {
            if ($role == 'Sales Manager') {
        ?>
                <li class="nav-item">
                    <a href="../index-sa.php" class="nav-link text-center text-white">
                        <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                            <i class="material-icons">dashboard</i>
                        </div>
                        <span class="text-xs d-block">Dashboard</span>
                    </a>
                </li>
            <?php
            }
            ?>
            <li class="nav-item">
                <a href="index.php" class="nav-link text-center text-white">
                    <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">assignment</i>
                    </div>
                    <span class="small d-block">Kegiatan</span>
                </a>
            </li>
            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center text-white" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">support_agent</i>
                    </div>
                    <span class="small d-block">Sales</span>
                </a>
                <!-- Dropup menu for profile -->
                <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">

                    <?php
                    if ($role == 'Sales') {
                    ?>
                        <a class="dropdown-item" href="../kegiatan-baru.php">Visit Customer</a>
                    <?php
                    }
                    ?>
                    <?php
                    if ($role == 'Sales Manager') {
                    ?>
                        <a class="dropdown-item" href="kegiatan-baru.php">Visit Customer</a>
                        <a class="dropdown-item" href="laporan-cust.php">Laporan Visit</a>
                        <a class="dropdown-item" href="sales.php">Data Sales</a>
                    <?php
                    }
                    ?>
                    <a class="dropdown-item" href="../customer.php">Data Customer</a>
                </div>
            </li>

            <li class="nav-item dropup">
                <a href="#" class="nav-link text-center text-white" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">engineering</i>
                    </div>
                    <span class="small d-block">Teknisi</span>
                </a>
                <!-- Dropup menu for profile -->
                <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">
                    <a class="dropdown-item" href="../index-sales.php">Dashboard</a>
                    <a class="dropdown-item" href="../kegiatan-baru.php">Request</a>
                </div>
            </li>
        <?php
        }

        ?>
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