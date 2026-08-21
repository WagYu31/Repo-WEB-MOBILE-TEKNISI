  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
  <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
  <title>
    LOEWIX | <?php echo $pageNow;?>
  </title>
  <!-- Fonts and icons -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <!-- Bootstrap 5.3.0 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Nucleo Icons -->
  <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
  
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  
  <!-- Theme CSS -->
  <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />

  <style>
    /* ── GLOBAL MOBILE TOUCH & RESPONSIVE OPTIMIZATIONS ── */
    html, body {
      touch-action: pan-y pinch-zoom;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-y: auto;
    }

    /* Remove 300ms tap delay, eliminate dead clicks on mobile */
    button, a, input, select, textarea, .btn, .nav-link, .tab-item, .action-btn, [role="button"], [onclick], .page-link, .dropdown-item, .filter-tab-pill {
      touch-action: manipulation !important;
      -webkit-tap-highlight-color: rgba(59, 130, 246, 0.15) !important;
      cursor: pointer;
    }

    /* Prevent text selection during fast taps on interactive elements */
    button, .btn, .nav-link, .badge, .action-btn, [role="button"], .tab-pill, .filter-tab-pill {
      user-select: none;
      -webkit-user-select: none;
    }

    /* Touch Target & Mobile Sizing Rules */
    @media (max-width: 991.98px) {
      /* Form elements: font-size 15px-16px to prevent iOS auto-zoom glitch */
      input, select, textarea, .form-control, .form-select, .input-premium {
        font-size: 15px !important;
      }
      
      /* Ensure action buttons in tables/cards have adequate tap hitbox (min 38px) */
      .action-btn, .btn-action, .table .btn-sm, .table a.btn, .table button.btn, .table a[href], .table button {
        min-width: 36px !important;
        min-height: 36px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 6px 10px !important;
        margin: 2px !important;
      }

      /* Horizontal scrollable containers on mobile */
      .table-responsive, .scrollable-tabs-mobile {
        -webkit-overflow-scrolling: touch !important;
        overscroll-behavior-x: contain !important;
      }

      /* Main content padding on mobile */
      .container-fluid {
        padding-left: 14px !important;
        padding-right: 14px !important;
      }

      .dashboard-header {
        padding: 18px 16px !important;
        border-radius: 16px !important;
      }

      .card-body-premium {
        padding: 20px 16px !important;
      }
    }

    .nav-link i.material-icons {
      font-size: 2em;
    }
    .btm-nav {
        position: fixed;
        bottom: 15px;
        left: 0;
        right: 0;
        margin: 0 auto;
        border-radius: 15px;
        background-color: rgba(0, 0, 0, 0.7);
        width:94%;
        margin-left:3%;
    }
    .navbar-brand-img{
        width:4em;
        height: 5em;
    }
  </style>
