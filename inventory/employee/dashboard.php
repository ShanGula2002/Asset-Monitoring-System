<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../admin/check_overdue.php';
createOverdueBorrowNotifications($conn);

$user_id = $_SESSION['user_id'];

// Quick query to get active borrows for the dashboard badges
$active_borrows = $conn->query("SELECT COUNT(*) as count FROM borrowing_logs WHERE user_id = $user_id AND borrow_status IN ('active', 'overdue') AND actual_return_date IS NULL")->fetch_assoc()['count'];
$overdue_borrows = $conn->query("SELECT COUNT(*) as count FROM borrowing_logs WHERE user_id = $user_id AND borrow_status IN ('active', 'overdue') AND expected_return_date <= CURRENT_TIMESTAMP AND actual_return_date IS NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | Asset System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
        .action-card {
            border-radius: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            border: none;
        }
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            color: inherit;
        }
        .icon-box {
            width: 60px; height: 60px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }

        @media (max-width: 767.98px) {
            .flex-grow-1.p-4.w-100 { padding: 22px 16px 28px !important; }
            .action-card { min-height: 142px; padding: 14px 10px !important; }
            .icon-box { width: 52px; height: 52px; font-size: 1.45rem; margin-bottom: 10px !important; }
            .alert { align-items: flex-start !important; padding: 14px; }
            .alert i { font-size: 1.5rem !important; }
        }

        @media (min-width: 768px) and (max-width: 1100px) {
            .flex-grow-1.p-4.w-100 { padding: 24px !important; }
            .action-card { min-height: 160px; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4 w-100" style="max-width: 1200px; margin: 0 auto;">
        
        <div class="mb-4">
            <h3 class="fw-bold">Welcome back, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>! 👋</h3>
            <p class="text-muted">What would you like to do today?</p>
        </div>

        <?php if ($overdue_borrows > 0): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-octagon-fill fs-3 me-3"></i>
            <div>
                <h6 class="fw-bold mb-0">Overdue Items!</h6>
                <small>You have <?php echo $overdue_borrows; ?> item(s) past their return date. Please check "My Borrows".</small>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-6 col-md-6 col-lg-3">
                <a href="borrow_asset.php" class="card action-card bg-white shadow-sm p-3 text-center h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                        <i class="bi bi-cart-plus"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Borrow</h6>
                    <small class="text-muted d-none d-md-block">Check out an item</small>
                </a>
            </div>

            <div class="col-6 col-md-6 col-lg-3">
                <a href="my_borrows.php" class="card action-card bg-white shadow-sm p-3 text-center h-100 position-relative">
                    <?php if ($active_borrows > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark mt-2 ms-n3">
                            <?php echo $active_borrows; ?>
                        </span>
                    <?php endif; ?>
                    <div class="icon-box bg-success bg-opacity-10 text-success mx-auto mb-3">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <h6 class="fw-bold mb-1">My Borrows</h6>
                    <small class="text-muted d-none d-md-block">View & return items</small>
                </a>
            </div>

            <div class="col-6 col-md-6 col-lg-3">
                <a href="locate_asset.php" class="card action-card bg-white shadow-sm p-3 text-center h-100">
                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary mx-auto mb-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Locate</h6>
                    <small class="text-muted d-none d-md-block">Find an item's room</small>
                </a>
            </div>

            <div class="col-6 col-md-6 col-lg-3">
                <a href="report_issue.php" class="card action-card bg-white shadow-sm p-3 text-center h-100 border border-danger border-opacity-25">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Report Issue</h6>
                    <small class="text-muted d-none d-md-block">Flag a damaged asset</small>
                </a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>