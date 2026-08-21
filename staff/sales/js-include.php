<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/script.js"></script>

<!-- Bootstrap 5.3 Bundle (includes Popper) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- Theme Plugins & Abstractions -->
<script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="assets/js/plugins/smooth-scrollbar.min.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    dropdownElementList.forEach(function (dropdownToggleEl) {
        new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Mobile Sidenav Backdrop click handler
    var sidenav = document.getElementById("sidenav-main");
    var body = document.querySelector("body");
    if (sidenav && body) {
      document.addEventListener("click", function(e) {
        if (window.innerWidth < 1200 && (body.classList.contains("g-sidenav-pinned") || body.classList.contains("g-sidenav-show"))) {
          var icon = document.getElementById("iconNavbarSidenav");
          if (!sidenav.contains(e.target) && (!icon || !icon.contains(e.target))) {
            body.classList.remove("g-sidenav-pinned");
          }
        }
      });
    }
  });
</script>

<script async defer src="https://buttons.github.io/buttons.js"></script>
<script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
