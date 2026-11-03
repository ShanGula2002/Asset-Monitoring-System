<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-md-none px-3 shadow-sm employee-mobile-nav">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        <i class="bi bi-box-seam"></i> AssetApp
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open employee menu">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<div class="offcanvas-md offcanvas-start bg-dark text-white shadow" tabindex="-1" id="mobileSidebar" style="width: 260px; height: 100vh;">
    <div class="offcanvas-header border-bottom border-secondary d-md-none">
        <h5 class="offcanvas-title fw-bold"><i class="bi bi-box-seam"></i> AssetApp</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#mobileSidebar"></button>
    </div>
    
    <div class="offcanvas-body d-flex flex-column p-0">
        <div class="p-4 border-bottom border-secondary d-none d-md-block text-center">
            <h4 class="fw-bold mb-0"><i class="bi bi-box-seam text-primary"></i> AssetApp</h4>
            <span class="badge bg-primary mt-2">Employee Portal</span>
        </div>

        <ul class="nav nav-pills flex-column mb-auto p-3 gap-1">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="borrow_asset.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'borrow_asset.php' ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-cart-plus me-2"></i> Borrow Asset
                </a>
            </li>
            <li class="nav-item">
                <a href="my_borrows.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'my_borrows.php' ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-list-check me-2"></i> My Borrows
                </a>
            </li>
            <li class="nav-item">
                <a href="locate_asset.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'locate_asset.php' ? 'active bg-primary' : ''; ?>">
                    <i class="bi bi-search me-2"></i> Locate Asset
                </a>
            </li>

<li class="nav-item">
    <a href="scan_room_audit.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'audit_room.php' || basename($_SERVER['PHP_SELF']) == 'scan_room_audit.php') ? 'active bg-primary' : ''; ?>">
        <i class="bi bi-building-check me-2"></i> Room Audit
    </a>
</li>

            <li class="nav-item">
                <a href="report_issue.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'report_issue.php' ? 'active bg-danger' : ''; ?>">
                    <i class="bi bi-exclamation-triangle me-2"></i> Report Issue
                </a>
            </li>
        </ul>

        <div class="p-3 border-top border-secondary">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary rounded-circle text-center me-3" style="width: 40px; height: 40px; line-height: 40px;">
                    <i class="bi bi-person text-white"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold"><?php echo explode(' ', $_SESSION['full_name'])[0]; ?></h6>
                    <small class="text-muted">Employee</small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-light w-100 btn-sm"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
    </div>
</div>

<style>
    .employee-sidebar-shell {
        background: #171b2e;
    }

    #mobileSidebar {
        flex: 0 0 260px;
        min-height: 100vh;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
    }

    #mobileSidebar .nav-link {
        min-height: 46px;
        display: flex;
        align-items: center;
        border-radius: 12px;
        color: rgba(255, 255, 255, 0.72) !important;
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    #mobileSidebar .nav-link:hover,
    #mobileSidebar .nav-link.active {
        color: #fff !important;
        transform: translateX(2px);
    }

    #mobileSidebar .nav-link:not(.active):hover {
        background: rgba(255, 255, 255, 0.08) !important;
    }

    #mobileSidebar .offcanvas-body {
        min-height: 0;
        overflow-y: auto;
    }

    .employee-sidebar-shell + .flex-grow-1,
    #mobileSidebar + .flex-grow-1 {
        min-width: 0;
    }

    @media (max-width: 767.98px) {
        body:has(#mobileSidebar) { background: #f4f7f6; }

        #mobileSidebar {
            width: min(86vw, 310px) !important;
            min-height: 100vh;
        }

        .navbar.d-md-none {
            min-height: 58px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2000;
            background: #1f4fd0 !important;
        }

        .employee-mobile-nav { pointer-events: auto !important; }

        .navbar-toggler {
            width: 44px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .flex-grow-1.p-3.p-md-4,
        .flex-grow-1.p-4 {
            padding: 80px 16px 28px !important;
        }

        .offcanvas-backdrop { z-index: 2040; }
        #mobileSidebar { z-index: 2050; }
    }

    @media (min-width: 768px) and (max-width: 1100px) {
        #mobileSidebar { flex-basis: 230px; width: 230px !important; }
        .flex-grow-1.p-3.p-md-4,
        .flex-grow-1.p-4 { padding: 24px !important; }
    }
</style>