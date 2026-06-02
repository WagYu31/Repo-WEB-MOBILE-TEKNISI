<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark" id="sidenav-main">
  <div class="sidenav-header d-flex align-items-center justify-content-center">
    <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="navbar-brand m-0" href="#">
      <img src="../assets/img/logo/lwx-logo.png" class="img-fluid mt-2" style="max-height:3em;" alt="main_logo">
      <!-- <span class="ms-1 font-weight-bold text-white">RUTE12</span> -->
    </a>
  </div>
  <hr class="horizontal light mt-0 mb-2">
  <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link text-white 
        <?php
        if($pageNow == "Dashboard"){
          echo "bg-gradient-info";
        }
        ?>" href="index.php">
          <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="material-icons opacity-10">dashboard</i>
          </div>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white 
        <?php
        if($pageNow == "All"){
          echo "bg-gradient-info";
        }
        ?>" href="all-kegiatan.php">
          <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="material-icons opacity-10">work_history</i>
          </div>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <!-- <li class="nav-item">
        <a class="nav-link text-white 
        <?php
        if($pageNow == "Kegiatan Saya"){
          echo "bg-gradient-info";
        }
        ?>" href="kegiatan-saya.php">
          <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="material-icons opacity-10">task</i>
          </div>
          <span class="nav-link-text ms-1">Kegiatan Saya</span>
        </a>
      </li> -->
      <li class="nav-item">
        <a class="nav-link text-white 
        <?php
        if($pageNow == "Pencapaian"){
          echo "bg-gradient-info";
        }
        ?>" href="pencapaian.php">
          <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="material-icons opacity-10">star</i>
          </div>
          <span class="nav-link-text ms-1">Pencapaian</span>
        </a>
      </li>
      
      <li class="nav-item mt-3">
        <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Halaman Akun</h6>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="../../logout.php">
          <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="material-icons opacity-10">login</i>
          </div>
          <span class="nav-link-text ms-1">Sign Out</span>
        </a>
      </li>

    </ul>
  </div>
  <div class="sidenav-footer position-absolute w-100 bottom-0 ">
    <div class="mx-3">
      <!-- <a class="btn bg-gradient-info mt-4 w-100" href="change_password.php" type="button">Ganti Password</a> -->
      <!-- <a class="btn bg-gradient-info w-100" href="../warga/tagihan-warga.php" type="button">MODE WARGA</a> -->
    </div>
  </div>
</aside>