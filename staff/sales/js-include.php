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
  });
</script>

<script async defer src="https://buttons.github.io/buttons.js"></script>
<script src="assets/js/material-dashboard.min.js?v=3.1.0"></script>
