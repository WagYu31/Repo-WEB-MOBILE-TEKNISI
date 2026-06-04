<!-- jQuery (deferred = non-blocking) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="js/script.js" defer></script>
    <!-- Bootstrap 5.3 Bundle (includes Popper) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
          var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
          dropdownElementList.forEach(function(el) { new bootstrap.Dropdown(el); });
        });
    </script>
  <!-- Material Dashboard (loaded deferred so it doesn't block) -->
  <script src="assets/js/material-dashboard.min.js?v=3.1.0" defer></script>
  <!-- PWA Install Banner -->
  <?php include 'pwa-install.php'; ?>
  <!-- PWA Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/staff/sw.js', { scope: '/staff/' })
          .then(function(reg) { console.log('[PWA] SW registered, scope:', reg.scope); })
          .catch(function(err) { console.log('[PWA] SW registration failed:', err); });
      });
    }
  </script>
