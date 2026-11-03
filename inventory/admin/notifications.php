<?php
session_start();
require '../db_connect.php';

// Simple check for admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/check_overdue.php';
createOverdueBorrowNotifications($conn);

// Handle "Mark as Read" action
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

// Handle "Mark All as Read"
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1");
    header("Location: notifications.php");
    exit();
}

// Paginate notifications to keep the page manageable as the log grows.
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$total_notifications = (int)$conn->query("SELECT COUNT(*) AS total FROM notifications")->fetch_assoc()['total'];
$unread_notifications = (int)$conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0")->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_notifications / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// OPTION 2: JOIN rooms to get room_name dynamically
$query = "SELECT n.*, r.room_name 
          FROM notifications n 
          LEFT JOIN rooms r ON n.room_id = r.room_id 
          ORDER BY n.is_read ASC, n.created_at DESC
          LIMIT $per_page OFFSET $offset";
$notifs = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Notifications | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { color: #263238; }
        .admin-notifications-page { margin-left: 260px; padding: 30px 36px; max-width: 1160px; }
        .notifications-heading { border-bottom: 1px solid #dce2e7; padding-bottom: 14px; }
        .notifications-heading h1 { font-size: 1.45rem; letter-spacing: 0; }
        .notification-summary { color: #66727d; font-size: 0.82rem; }
        .notification-list { overflow: hidden; border: 1px solid #dce2e7; border-radius: 8px; background: #fff; }
        .notification-item { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 16px; border-bottom: 1px solid #e8ecef; }
        .notification-item:last-child { border-bottom: 0; }
        .notification-item.is-unread { background: #f8fafc; box-shadow: inset 3px 0 #315f86; }
        .notification-content { min-width: 0; }
        .notification-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 5px; color: #66727d; font-size: 0.78rem; }
        .notification-message { margin: 0; color: #263238; font-size: 0.9rem; line-height: 1.45; }
        .notification-location { display: inline-block; margin-top: 4px; color: #52606d; font-size: 0.8rem; }
        .notification-type { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.025em; }
        .notification-status { color: #66727d; font-size: 0.73rem; }
        .notification-action { flex: 0 0 auto; }
        .notifications-empty { padding: 42px 20px; border: 1px solid #dce2e7; border-radius: 8px; background: #fff; color: #66727d; text-align: center; }
        @media (max-width: 992px) {
            .admin-notifications-page { margin-left: 0; max-width: none; padding: 76px 18px 24px; }
        }
        @media (max-width: 576px) {
            .notifications-heading { align-items: flex-start !important; flex-direction: column; gap: 12px; }
            .notifications-heading form,
            .notifications-heading form button { width: 100%; }
            .notification-item { align-items: flex-start; padding: 11px 12px; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'sidebar.php'; ?>

<div class="admin-notifications-page">
    <div class="notifications-heading d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="fw-bold text-dark mb-1">Notifications</h1>
            <p class="text-muted small mb-0">System alerts and activity messages</p>
        </div>
        <form method="POST">
            <button name="mark_all_read" class="btn btn-outline-secondary btn-sm" <?php echo $unread_notifications === 0 ? 'disabled' : ''; ?>>
                <i class="bi bi-check2-all me-1"></i>Mark all as read
            </button>
        </form>
    </div>

    <div class="notification-summary mb-2">
        <?php echo $total_notifications; ?> total
        <span class="mx-1">|</span><?php echo $unread_notifications; ?> unread
    </div>

    <?php if ($notifs->num_rows > 0): ?>
        <div class="notification-list">
        <?php while($n = $notifs->fetch_assoc()): ?>
            <div class="notification-item <?php echo $n['is_read'] ? '' : 'is-unread'; ?>">
                <div class="notification-content">
                    <div class="notification-meta">
                        <span class="badge notification-type <?php echo $n['type'] === 'security_alert' ? 'text-bg-danger' : 'text-bg-secondary'; ?>">
                            <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $n['type']))); ?>
                        </span>
                        <time datetime="<?php echo htmlspecialchars(date('c', strtotime($n['created_at']))); ?>">
                            <?php echo date('M d, Y · h:i A', strtotime($n['created_at'])); ?>
                        </time>
                        <span class="notification-status"><?php echo $n['is_read'] ? 'Read' : 'Unread'; ?></span>
                    </div>
                    <p class="notification-message"><?php echo htmlspecialchars($n['message']); ?></p>
                    <?php if(!empty($n['room_name'])): ?>
                        <span class="notification-location"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($n['room_name']); ?></span>
                    <?php endif; ?>
                </div>
                    <?php if (!$n['is_read']): ?>
                        <a href="?mark_read=<?php echo (int)$n['id']; ?>" class="notification-action btn btn-sm btn-outline-secondary" aria-label="Mark notification as read" title="Mark as read">
                            <i class="bi bi-check-lg"></i>
                        </a>
                    <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="notifications-empty">
            <i class="bi bi-inbox fs-3"></i>
            <p class="mb-0 mt-2">No notifications are available.</p>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Notifications pages" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($page_number = 1; $page_number <= $total_pages; $page_number++): ?>
                    <li class="page-item <?php echo $page_number === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page_number; ?>"><?php echo $page_number; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
    
</div>

</body>
</html>