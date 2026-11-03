<?php
// 1. Fetch unread notification count
$count_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE is_read = 0";
$count_res = $conn->query($count_query);
$unread_data = $count_res->fetch_assoc();
$unread_count = $unread_data['unread_count'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>

<button class="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-backdrop" aria-hidden="true"></div>
<div class="sidebar d-flex flex-column p-3 text-white" id="adminSidebar">
    <button class="sidebar-close" type="button" aria-label="Close navigation">
        <i class="bi bi-x-lg"></i>
    </button>
    <div class="brand-section text-center py-4">
        <div class="icon-box mx-auto shadow">
            <i class="bi bi-shield-check fs-2"></i>
        </div>
        <h5 class="mt-3 fw-bold mb-0">School Asset</h5>
        <p class="small opacity-50 text-uppercase tracking-wider">Management System</p>
    </div>

    <hr class="sidebar-divider">

    <ul class="nav nav-pills flex-column mb-auto mt-2">
        
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill me-3"></i> <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="notifications.php" class="nav-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <i class="bi bi-bell-fill me-3"></i> 
                <span>Notifications</span>
                <?php if($unread_count > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto shadow-sm">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a href="manage_assets.php" class="nav-link <?php echo ($current_page == 'manage_assets.php') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam-fill me-3"></i> <span>Assets List</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_rooms.php" class="nav-link <?php echo ($current_page == 'manage_rooms.php') ? 'active' : ''; ?>">
                <i class="bi bi-door-open-fill me-3"></i> <span>Rooms/Labs</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_user.php" class="nav-link <?php echo ($current_page == 'manage_user.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill me-3"></i> <span>Manage Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="asset_audit.php" class="nav-link <?php echo ($current_page == 'asset_audit.php') ? 'active' : ''; ?>">
                <i class="bi bi-clock-history me-3"></i> <span>Asset Audit</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph-fill me-3"></i> <span>Reports</span>
            </a>
        </li>
    </ul>

    <div class="mt-auto pt-3 border-top border-secondary border-opacity-25">
        <a href="logout.php" class="btn btn-logout w-100 py-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>
    </div>
</div>

<style>
    /* 1. Sidebar Container */
    .sidebar {
        width: 260px;
        height: 100vh;
        background: #1e1e2d;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .mobile-menu-toggle,
    .sidebar-close,
    .sidebar-backdrop {
        display: none;
    }

    /* 2. Brand Section */
    .brand-section .icon-box {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .tracking-wider { letter-spacing: 1px; font-size: 0.65rem; }

    /* 3. Navigation Styling */
    .sidebar-divider {
        background: rgba(255, 255, 255, 0.1);
        margin: 0.5rem 0 1.5rem 0;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.6) !important;
        font-weight: 500;
        padding: 12px 20px;
        margin: 4px 8px;
        border-radius: 10px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        position: relative;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff !important;
    }

    .sidebar .nav-link.active {
        background: #4e73df !important;
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
    }

    .sidebar .nav-link.active::before {
        content: "";
        position: absolute;
        left: -8px;
        height: 60%;
        width: 4px;
        background: #fff;
        border-radius: 0 4px 4px 0;
    }

    /* Badge adjustment to align correctly in the row */
    .sidebar .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.6em;
    }

    /* 6. Logout Button */
    .btn-logout {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ff5e5e;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .btn-logout:hover {
        background: #e74a3b;
        color: white;
        border-color: #e74a3b;
    }

    .main-content {
        margin-left: 260px;
        padding: 30px;
        width: calc(100% - 260px);
        background: #f8f9fc;
    }

    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: 18px 0 40px rgba(15, 23, 42, 0.2);
        }

        .sidebar.is-open { transform: translateX(0); }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(3px);
        }

        .sidebar-backdrop.is-visible,
        .mobile-menu-toggle,
        .sidebar-close { display: flex; }

        .mobile-menu-toggle {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 998;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            background: #20243a;
            color: #fff;
            font-size: 1.35rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }

        .sidebar-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .content,
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 76px 18px 24px !important;
        }
    }

    @media (min-width: 993px) {
        .sidebar { transform: translateX(0); }
    }
</style>

<script>
    (function() {
        const sidebar = document.getElementById('adminSidebar');
        const toggle = document.querySelector('.mobile-menu-toggle');
        const close = document.querySelector('.sidebar-close');
        const backdrop = document.querySelector('.sidebar-backdrop');

        function setMenu(open) {
            sidebar.classList.toggle('is-open', open);
            backdrop.classList.toggle('is-visible', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('menu-open', open);
        }

        toggle.addEventListener('click', function() { setMenu(true); });
        close.addEventListener('click', function() { setMenu(false); });
        backdrop.addEventListener('click', function() { setMenu(false); });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) setMenu(false);
        });
    })();
</script>