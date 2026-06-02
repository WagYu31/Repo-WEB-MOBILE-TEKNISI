<?php

    if($role == "SA"){
        ?>
        <!-- Bottom Navbar -->
        <nav class="navbar navbar-dark navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0">
            <ul class="navbar-nav nav-justified w-100">
                <li class="nav-item">
                    <a href="index.php" class="nav-link text-center">
                        <i class='bx bx-grid-alt nav_icon'></i>
                        <span class="small d-block">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <nav class="navv">
                        <input id="menuv" type="checkbox">
                        <label for="menuv">Menu</label>
                        <ul class="menuv">
                            <li>
                                <a href="waiting_list.php">
                                    <span class="tg">Daftar Tunggu</span>
                                    <i class="fas fa-tasks" aria-hidden="true"></i>
                                    <?php if ($waitingCount > 0): ?>
                                        <span class="notif"><?php echo $waitingCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="data-customer.php">
                                    <span>Customer</span>
                                    <i class="fas fa-address-card" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="teknisi.php">
                                    <span>Teknisi</span>
                                    <i class="fas fa-users" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="kegiatan.php">
                                    <span>Kegiatan</span>
                                    <i class="fas fa-business-time" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-center">
                        <i class='bx bx-log-out nav_icon'></i>
                        <span class="small d-block">Sign Out</span>
                    </a>
                </li>
                <!--<li class="nav-item dropup">-->
                <!--    <a href="#" class="nav-link text-center" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >-->
                <!--        <i class='bx bx-log-out nav_icon'></i>-->
                <!--        <span class="small d-block">SignOut</span>-->
                <!--    </a>-->
                    <!-- Dropup menu for profile -->
                <!--    <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">-->
                <!--        <a class="dropdown-item" href="#">Edit Profile</a>-->
                <!--        <a class="dropdown-item" href="#">Notification</a>-->
                <!--        <div class="dropdown-divider"></div>-->
                <!--        <a class="dropdown-item" href="logout.php">Logout</a>-->
                <!--    </div>-->
                <!--</li>-->
            </ul>
        </nav>
        <?php
    }
    
    else if($role == "Admin"){
        ?>
        <!-- Bottom Navbar -->
        <nav class="navbar navbar-dark navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0">
            <ul class="navbar-nav nav-justified w-100">
                <li class="nav-item">
                    <a href="index.php" class="nav-link text-center">
                        <i class='bx bx-grid-alt nav_icon'></i>
                        <span class="small d-block">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <nav class="navv">
                        <input id="menuv" type="checkbox">
                        <label for="menuv">Menu</label>
                        <ul class="menuv">
                            <li>
                                <a href="waiting_list.php">
                                    <span class="tg">Daftar Tunggu</span>
                                    <i class="fas fa-tasks" aria-hidden="true"></i>
                                    <?php if ($waitingCount > 0): ?>
                                        <span class="notif"><?php echo $waitingCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a href="data-customer.php">
                                    <span>Customer</span>
                                    <i class="fas fa-address-card" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="teknisi.php">
                                    <span>Teknisi</span>
                                    <i class="fas fa-users" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="kegiatan.php">
                                    <span>Kegiatan</span>
                                    <i class="fas fa-business-time" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-center">
                        <i class='bx bx-log-out nav_icon'></i>
                        <span class="small d-block">Sign Out</span>
                    </a>
                </li>
                <!--<li class="nav-item dropup">-->
                <!--    <a href="#" class="nav-link text-center" role="button" id="dropdownMenuProfile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >-->
                <!--        <i class='bx bx-log-out nav_icon'></i>-->
                <!--        <span class="small d-block">SignOut</span>-->
                <!--    </a>-->
                    <!-- Dropup menu for profile -->
                <!--    <div class="dropdown-menu" aria-labelledby="dropdownMenuProfile">-->
                <!--        <a class="dropdown-item" href="#">Edit Profile</a>-->
                <!--        <a class="dropdown-item" href="#">Notification</a>-->
                <!--        <div class="dropdown-divider"></div>-->
                <!--        <a class="dropdown-item" href="logout.php">Logout</a>-->
                <!--    </div>-->
                <!--</li>-->
            </ul>
        </nav>
        <?php
    }
    
    else if($role == "Teknisi"){
        ?>
        <!-- Bottom Navbar -->
        <nav class="navbar navbar-dark navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0">
            <ul class="navbar-nav nav-justified w-100">
                <li class="nav-item">
                    <a href="profile-tek.php" class="nav-link text-center">
                        <i class='bx bx-trophy nav_icon'></i>
                        <span class="small d-block">Pencapaian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <nav class="navv">
                        <input id="menuv" type="checkbox">
                        <label for="menuv">Menu</label>
                        <ul class="menuv">
                            <li>
                                <a href="index-teknisi.php">
                                    <span>Kegiatan</span>
                                    <i class="fas fa-tasks" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-center">
                        <i class='bx bx-log-out nav_icon'></i>
                        <span class="small d-block">Sign Out</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
    }
    
    else if($role == "Sales"){
        ?>
        <!-- Bottom Navbar -->
        <nav class="navbar navbar-dark navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0">
            <ul class="navbar-nav nav-justified w-100">
                <li class="nav-item">
                    <a href="profile-tek.php" class="nav-link text-center">
                        <i class='bx bx-trophy nav_icon'></i>
                        <span class="small d-block">Pencapaian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <nav class="navv">
                        <input id="menuv" type="checkbox">
                        <label for="menuv">Menu</label>
                        <ul class="menuv">
                            <li>
                                <a href="index-teknisi.php">
                                    <span>Kegiatan</span>
                                    <i class="fas fa-tasks" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-center">
                        <i class='bx bx-log-out nav_icon'></i>
                        <span class="small d-block">Sign Out</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
    }
    
    else{
        ?>
        <!-- Bottom Navbar -->
        <nav class="navbar navbar-dark navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0">
            <ul class="navbar-nav nav-justified w-100">
                <li class="nav-item">
                    <a href="guest-mode.php" class="nav-link text-center">
                        <i class='bx bx-home nav_icon'></i>
                        <span class="small d-block">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <nav class="navv">
                        <input id="menuv" type="checkbox">
                        <label for="menuv">Menu</label>
                        <ul class="menuv">
                            <li>
                                <a href="login.php">
                                    <span>Main Menu</span>
                                    <i class='bx bx-home nav_icon' aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li class="nav-item">
                    <a href="cek-resi.php" class="nav-link text-center">
                        <i class='bx bxs-truck nav_icon'></i>
                        <span class="small d-block">Tracking</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php
    }
?>