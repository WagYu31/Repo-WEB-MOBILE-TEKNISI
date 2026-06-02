  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/logo/lwx.png">
  <link rel="icon" type="image/png" href="assets/img/logo/lwx.png">
  <title>
    LOEWIX | <?php echo $pageNow;?>
  </title>
  <!-- DNS Preconnect (speed up CDN connections) -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <!-- Fonts -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Roboto+Slab:wght@400;700&display=swap" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
    <!-- Bootstrap 5.3 CSS (satu-satunya) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery (satu kali saja) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Nucleo Icons -->
  <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons (Kit khusus akun) -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round&display=swap" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="https://jadwal.id-giti.com" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
  <script>
    // Get the initial width
    let initialWidth = window.innerWidth;

    // Function to check for width changes and perform actions
    function checkWidthAndRefresh() {
      // Check if the width has changed
      if (initialWidth !== window.innerWidth) {
        // Execute your actions here, for example, reload or other logic
        location.reload(); // Example: reload the page
      }
    }

    // Attach the function to the window resize event
    window.addEventListener('resize', checkWidthAndRefresh);
  </script>
  <style>
    .nav-link i.material-icons {
      font-size: 2em;
      /* Adjust the size as needed */
    }
    .btm-nav {
        position: fixed;
        bottom: 15px; /* Adjust the distance from the bottom as needed */
        left: 0;
        right: 0;
        margin: 0 auto; /* Center the navbar horizontally */
        border-radius: 15px; /* Add border-radius */
        /* overflow: hidden; */
        background-color: rgba(0, 0, 0, 0.7); /* Add a background color with transparency */
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