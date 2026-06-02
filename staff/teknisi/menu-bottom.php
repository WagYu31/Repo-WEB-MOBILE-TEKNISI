<!-- Bottom Navbar -->
<nav class="navbar navbar-dark bg-gradient-info navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none p-0 btm-nav">
    <ul class="navbar-nav nav-justified w-100">
        <li class="nav-item">
            <a href="index.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "Dashboard"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">dashboard</i>
                </div>
                <span class="text-xs d-block">Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="all-kegiatan.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "All"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">work_history</i>
                </div>
                <span class="text-xs d-block">Riwayat</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="kegiatan.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "Kegiatan"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">task</i>
                </div>
                <span class="text-xs d-block">Kegiatan</span>
            </a>
        </li> -->
        <li class="nav-item">
            <a href="pencapaian.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "Pencapaian"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">star</i>
                </div>
                <span class="text-xs d-block">Pencapaian</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="change_password.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "Ganti Password"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">key</i>
                </div>
                <span class="text-xs d-block">Password</span>
            </a>
        </li> -->
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link text-center text-white
                    <?php
                        if($pageNow == "Logout"){
                            echo "opacity-10";
                        }
                        else{
                            echo "opacity-7";
                        }
                    ?>">
                <div class="text-white text-center mb-1 d-flex align-items-center justify-content-center">
                    <i class="material-icons" style="font-size: 25px;">logout</i>
                </div>
                <span class="text-xs d-block">Log Out</span>
            </a>
        </li>
    </ul>
</nav>