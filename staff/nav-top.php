<style>
/* Force main-content to flex layout so nav stays fixed */
.main-content {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}
.main-content > .container-fluid {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}
</style>
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" style="flex-shrink:0 !important;z-index:100;background:rgba(245,247,250,0.97);padding-top:16px;padding-bottom:8px;margin-top:16px !important;">
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