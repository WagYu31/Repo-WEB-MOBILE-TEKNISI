  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
  <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
  <!-- PWA -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#1a1a2e">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Loewix">
  <meta name="mobile-web-app-capable" content="yes">
  <title>
    LOEWIX | <?php echo $pageNow;?>
  </title>
  <!-- DNS Preconnect (speed up CDN connections) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <!-- Fonts (only weights we actually use) -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" />
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Material Icons (critical - used in sidebar/nav) -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round&display=swap" rel="stylesheet">
  <!-- CSS Files (critical) -->
  <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <!-- Non-critical CSS: load async via media swap trick -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" media="print" onload="this.media='all'" crossorigin="anonymous" />
  <link href="assets/css/nucleo-icons.css" rel="stylesheet" media="print" onload="this.media='all'" />
  <link href="assets/css/nucleo-svg.css" rel="stylesheet" media="print" onload="this.media='all'" />
  <style>
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
  
  <?php
  
// require_once 'logger.php';
?>