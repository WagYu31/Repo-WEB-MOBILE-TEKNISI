<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">
  <div class="sidenav-header d-flex align-items-center justify-content-center">
    <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="navbar-brand m-0" href="#">
      <img src="assets/img/logo/lwx-logo.png" class="img-fluid mt-2" style="max-height:3em;" alt="main_logo">
      <!-- <span class="ms-1 font-weight-bold text-white">RUTE12</span> -->
    </a>
  </div>
  <hr class="horizontal light mt-0 mb-2">
  <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
    <ul class="navbar-nav">

      <?php
      if ($role == 'Super Admin' || $role == 'Admin') {
      ?>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Dashboard") {
          echo "bg-gradient-info";
        }
        ?>" href="index-sa.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Kegiatan Baru") {
          echo "bg-gradient-info";
        }
        ?>" href="kegiatan-baru.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">Tambah Kegiatan</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Waiting List") {
          echo "bg-gradient-info";
        }
        ?>" href="waiting-list.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">pending_actions</i>
            </div>
            <span class="nav-link-text ms-1">Waiting List</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Laporan") {
          echo "bg-gradient-info";
        }
        ?>" href="under_construction.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">report</i>
            </div>
            <span class="nav-link-text ms-1">Laporan Kegiatan</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Pendapatan") {
          echo "bg-gradient-info";
        }
        ?>" href="under_construction.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">paid</i>
            </div>
            <span class="nav-link-text ms-1">Pendapatan Teknisi</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Teknisi") {
          echo "bg-gradient-info";
        }
        ?>" href="teknisi-db.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">engineering</i>
            </div>
            <span class="nav-link-text ms-1">Teknisi</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Customer") {
          echo "bg-gradient-info";
        }
        ?>" href="customer.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">groups</i>
            </div>
            <span class="nav-link-text ms-1">Customer</span>
          </a>
        </li>

      <?php
      }

      if ($role == 'Super Admin') {
      ?>
        <li class="nav-item mt-3 bg-white pt-1 opacity-8">
        </li>
        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Sales</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Dashboard-Sales") {
          echo "bg-gradient-info";
        }
        ?>" href="dashboard-sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">widgets</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Daftar Kegiatan Sales") {
          echo "bg-gradient-info";
        }
        ?>" href="cust-sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">toc</i>
            </div>
            <span class="nav-link-text ms-1">Visit Customer</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Sales") {
          echo "bg-gradient-info";
        }
        ?>" href="sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">support_agent</i>
            </div>
            <span class="nav-link-text ms-1">Sales</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Customer Sales") {
          echo "bg-gradient-info";
        }
        ?>" href="data-cust-sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">diversity_1</i>
            </div>
            <span class="nav-link-text ms-1">Customer</span>
          </a>
        </li>
      <?php
      }

      if ($role == 'Sales Manager') {
      ?>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Dashboard") {
          echo "bg-gradient-info";
        }
        ?>" href="sales/index-sa.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">widgets</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Kegiatan Saya") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/sales/index.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">Kegiatan Saya</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Kegiatan Sales") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/kegiatan-baru.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">toc</i>
            </div>
            <span class="nav-link-text ms-1">Visit Customer</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Laporan") {
          echo "bg-gradient-info";
        }
        ?>" href="sales/laporan-cust.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">report</i>
            </div>
            <span class="nav-link-text ms-1">Laporan Visit</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Customer") {
          echo "bg-gradient-info";
        }
        ?>" href="sales/customer.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">diversity_1</i>
            </div>
            <span class="nav-link-text ms-1">Customer</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link text-white 
        <?php
        if ($pageNow == "Data Sales") {
          echo "bg-gradient-info";
        }
        ?>" href="sales/sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">support_agent</i>
            </div>
            <span class="nav-link-text ms-1">Sales</span>
          </a>
        </li>
      <?php
      }


      if ($role == 'Sales') {
          ?>
          <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Dashboard Sales") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/index-sa.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">widgets</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Kegiatan Saya") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/sales/index.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">Kegiatan Saya</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Kegiatan Sales") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/kegiatan-baru.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">toc</i>
            </div>
            <span class="nav-link-text ms-1">Visit Customer</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
          <?php
          if ($pageNow == "Data Customer") {
            echo "bg-gradient-info";
          }
          ?>" href="sales/customer.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">diversity_1</i>
            </div>
            <span class="nav-link-text ms-1">Customer</span>
          </a>
        </li>

          <?php
      }
      if ($role == 'Sales' || $role == 'Sales Manager') {
      ?>
        <li class="nav-item mt-3 bg-white pt-1 opacity-8">
        </li>
        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Request Teknisi</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
            <?php
            if ($pageNow == "Dashboard Teknisi") {
              echo "bg-gradient-info";
            }
            ?>" href="index-sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white 
            <?php
            if ($pageNow == "Kegiatan Baru") {
              echo "bg-gradient-info";
            }
            ?>" href="kegiatan-baru.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">Request</span>
          </a>
        </li>
      <?php
      }

      if ($role == 'Teknisi') {
      }


      ?>

      <li class="nav-item mt-3 bg-white pt-1 opacity-8">
      </li>
      <li class="nav-item mt-3">
        <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Halaman Akun</h6>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="../logout.php">
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
      <a class="btn bg-gradient-info mt-4 w-100" href="change_password.php" type="button">Ganti Password</a>
      <!-- <a class="btn bg-gradient-info w-100" href="../warga/tagihan-warga.php" type="button">MODE WARGA</a> -->
    </div>
  </div>
</aside>