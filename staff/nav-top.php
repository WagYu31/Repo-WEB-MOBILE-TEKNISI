<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" style="position:sticky !important;top:0 !important;z-index:100 !important;background:rgba(245,247,250,0.97) !important;backdrop-filter:blur(12px) !important;-webkit-backdrop-filter:blur(12px) !important;margin-top:0 !important;padding-top:12px !important;padding-bottom:8px !important;">
    <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
                <li class="breadcrumb-item text-sm text-dark active" aria-current="page"><?php echo $pageNow; ?></li>
            </ol>
            <h6 class="font-weight-bolder mb-0"><?php echo $pageNow; ?></h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 ms-auto me-md-0 me-sm-4" id="navbar">
            <ul class="navbar-nav justify-content-end d-none d-md-block">
                <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
// Prevent Material Dashboard JS from overriding sticky position
document.addEventListener('DOMContentLoaded', function() {
    var nav = document.getElementById('navbarBlur');
    if (nav) {
        nav.removeAttribute('data-scroll');
        // Re-apply sticky after any framework JS runs
        setTimeout(function() {
            nav.style.setProperty('position', 'sticky', 'important');
            nav.style.setProperty('top', '0', 'important');
        }, 100);
    }
});
</script>