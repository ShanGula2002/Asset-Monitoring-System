<?php
    session_start();
    require '../db_connect.php';

    // Security check
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }

    // 1. Get Filters
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'tracking';
    $filter = isset($_GET['time_range']) ? $_GET['time_range'] : '';
    $per_page = 15;
    $page = max(1, (int)($_GET['page'] ?? 1));

    // 2. Determine the correct Date Column based on Report Type
    $date_col = 't.scan_time'; // Default for tracking
    if ($report_type === 'borrowing') $date_col = 'b.borrow_date';
    if ($report_type === 'notifications') $date_col = 'n.created_at';
    if ($report_type === 'condition') $date_col = 'a.last_updated';

    // 3. Build the WHERE clause for Time Range
    $where_clause = "WHERE 1=1"; 
    if ($filter === 'today') {
        $where_clause .= " AND DATE($date_col) = CURDATE()";
    } elseif ($filter === 'week') {
        $where_clause .= " AND $date_col >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter === 'month') {
        $where_clause .= " AND $date_col >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // 4. Dynamic Queries based on Report Type
    if ($report_type === 'condition') {
        $logs_query = "SELECT a.asset_id, a.asset_tag, a.asset_name, a.brand, a.serial_number,
                            a.date_acquired, a.category, a.is_available, a.status, a.last_updated,
                            a.room_id, r.room_name
                    FROM assets a
                    LEFT JOIN rooms r ON a.room_id = r.room_id
                    $where_clause
                    ORDER BY a.status ASC, a.asset_name ASC";
        $count_query = "SELECT COUNT(*) as total FROM assets a $where_clause";

    } elseif ($report_type === 'borrowing') {
        $logs_query = "SELECT b.borrow_id, b.borrow_date, b.expected_return_date, b.actual_return_date, b.borrow_status,
                    b.borrowed_room_id, a.asset_name, a.asset_tag, a.room_id AS current_room_id,
                    current_room.room_name AS current_room_name, borrowed_room.room_name AS borrowed_room_name, u.full_name
                    FROM borrowing_logs b
                    JOIN assets a ON b.asset_id = a.asset_id
                    JOIN users u ON b.user_id = u.user_id
                    LEFT JOIN rooms current_room ON a.room_id = current_room.room_id
                LEFT JOIN rooms borrowed_room ON b.borrowed_room_id = borrowed_room.room_id
                    $where_clause
                    ORDER BY b.borrow_date DESC";
        $count_query = "SELECT COUNT(*) as total FROM borrowing_logs b $where_clause";

    } elseif ($report_type === 'notifications') {
        $logs_query = "SELECT n.id, n.message, n.type, n.is_read, n.created_at, 
                            u.full_name, r.room_name 
                    FROM notifications n
                    LEFT JOIN users u ON n.user_id = u.user_id
                    LEFT JOIN rooms r ON n.room_id = r.room_id
                    $where_clause
                    ORDER BY n.created_at DESC";
        $count_query = "SELECT COUNT(*) as total FROM notifications n $where_clause";

    } else {
        // Updated: Tracking Logs with IDs and Status Description
        $logs_query = "SELECT t.log_id, t.asset_id, t.room_id, t.user_id, t.scan_time, t.status,
                            a.asset_name, a.asset_tag, r.room_name, u.full_name 
                    FROM tracking_logs t
                    JOIN assets a ON t.asset_id = a.asset_id
                    JOIN rooms r ON t.room_id = r.room_id
                    JOIN users u ON t.user_id = u.user_id
                    $where_clause
                    ORDER BY t.scan_time DESC";
        $count_query = "SELECT COUNT(*) as total FROM tracking_logs t $where_clause";
    }

    $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    $total_pages = max(1, (int)ceil($total_records / $per_page));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $per_page;
    $logs = $conn->query($logs_query . " LIMIT $per_page OFFSET $offset");
    $report_titles = [
        'tracking' => 'Location Tracking Logs',
        'borrowing' => 'Asset Borrowing Logs',
        'notifications' => 'System Notifications',
        'condition' => 'Asset Condition Report'
    ];
    $range_labels = [
        '' => 'All dates',
        'today' => 'Today',
        'week' => 'Last 7 days',
        'month' => 'Last 30 days'
    ];
    $report_title = $report_titles[$report_type] ?? 'Location Tracking Logs';
    $period_label = $range_labels[$filter] ?? 'All dates';
    $visible_records = min($per_page, max(0, (int)$total_records - $offset));
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Comprehensive Report | Asset Tracker</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        
        <style>
            :root { --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
            body { background-color: #f4f7fe; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3748; }
            body { background-color: #f5f6f8; color: #263238; }
            .content { margin-left: 260px; padding: 28px 32px; width: calc(100% - 260px); transition: all 0.3s; }
            .report-card { border: 1px solid #e2e6ea; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(24,39,56,0.04); overflow: hidden; }
            .table { margin-bottom: 0; }
            .table thead th { background-color: #f2f4f6; text-transform: uppercase; font-size: 0.73rem; letter-spacing: 0.035em; font-weight: 700; color: #52606d; padding: 9px 11px; border-bottom: 1px solid #dfe4e8; white-space: nowrap; }
            .table tbody td { padding: 8px 11px; font-size: 0.84rem; line-height: 1.35; border-bottom: 1px solid #e9edf0; vertical-align: middle; }
            .table tbody tr:last-child td { border-bottom: 0; }
            .id-badge { background: #f1f3f5; color: #475569; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.72rem; }
            .report-heading { border-bottom: 1px solid #d8dee4; padding: 0 0 12px; margin-bottom: 16px; }
            .report-heading h2 { font-size: 1.45rem; letter-spacing: 0; }
            .report-print-heading { display: none; }
            .report-summary { color: #66727d; font-size: 0.78rem; }
            @page { size: landscape; margin: 12mm; }
            @media print {
                body { background: #fff !important; color: #111 !important; font-family: Arial, sans-serif; }
                .sidebar, .no-print, .search-container, .report-page-heading { display: none !important; }
                .content { margin: 0; padding: 0; width: 100%; }
                .report-print-heading { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
                .report-print-heading h1 { font-size: 16pt; font-weight: 700; margin: 0; }
                .report-print-heading p { font-size: 9pt; margin: 3px 0 0; color: #444; }
                .report-card { border: 0; border-radius: 0; box-shadow: none; overflow: visible; }
                .table-responsive { overflow: visible !important; }
                .table { width: 100% !important; font-size: 9pt; }
                .table thead { display: table-header-group; }
                .table thead th { background: #e9ecef !important; color: #111 !important; padding: 5px 6px; font-size: 8pt; }
                .table tbody td { padding: 5px 6px; font-size: 9pt; }
                .table tr { break-inside: avoid; }
                .badge { border: 1px solid #999; color: #111 !important; background: transparent !important; font-size: 7pt; }
            }
            @media (max-width: 768px) {
                .content > .d-flex:first-child { align-items: flex-start !important; flex-direction: column; gap: 12px; margin-bottom: 1rem !important; }
                .content > .d-flex:first-child .no-print { width: 100%; }
                .content > .d-flex:first-child .no-print .btn { width: 100%; }
                .search-container form { gap: 10px !important; }
                .search-container form > div { width: 100%; }
                .report-card .table { min-width: 850px; }
                .table thead th, .table tbody td { padding: 8px; }
            }
        </style>
    </head>
    <body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="report-heading d-flex justify-content-between align-items-center report-page-heading">
            <div>
                <h2 class="fw-bold text-dark mb-1">System Reports</h2>
                <p class="text-muted mb-0 small">Activity and inventory records</p>
            </div>
            <div class="no-print">
                <button class="btn btn-outline-secondary btn-sm px-3 py-2" onclick="window.print()">
                    <i class="bi bi-printer-fill me-2"></i> Print Report
                </button>
            </div>
        </div>

        <div class="search-container mb-4 no-print">
            <form action="" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5 position-relative">
                    <input type="text" id="logSearch" class="form-control rounded-3" placeholder="Search logs...">
                </div>
                <div class="col-md-4">
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="tracking" <?php echo $report_type == 'tracking' ? 'selected' : ''; ?>>Location Tracking Logs</option>
                        <option value="borrowing" <?php echo $report_type == 'borrowing' ? 'selected' : ''; ?>>Asset Borrowing Logs</option>
                        <option value="notifications" <?php echo $report_type == 'notifications' ? 'selected' : ''; ?>>System Notifications</option>
                        <option value="condition" <?php echo $report_type == 'condition' ? 'selected' : ''; ?>>Asset Condition Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="time_range" class="form-select" onchange="this.form.submit()">
                        <option value="" <?php echo $filter == '' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="report-print-heading">
            <div>
                <h1><?php echo htmlspecialchars($report_title); ?></h1>
                <p>Reporting period: <?php echo htmlspecialchars($period_label); ?></p>
            </div>
            <div class="text-end">
                <p>Generated: <?php echo date('M d, Y'); ?></p>
                <p><?php echo (int)$total_records; ?> total records</p>
            </div>
        </div>
        <div class="report-summary mb-2 no-print">
            <?php echo $visible_records; ?> records on this page of <?php echo (int)$total_records; ?> total
            <span class="mx-1">|</span><?php echo htmlspecialchars($period_label); ?>
        </div>

        <div class="card report-card">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" id="logsTable">
                    <thead>
                        <tr>
                            <?php if ($report_type === 'tracking'): ?>
                                <th>Log ID</th>
                                <th>Scan Time</th>
                                <th>Asset Details (ID)</th>
                                <th>Location (Room ID)</th>
                                <th>User (User ID)</th>
                                <th>Status / Description</th>
                            <?php elseif ($report_type === 'borrowing'): ?>
                                <th>Borrow Date</th>
                                <th>Asset Name</th>
                                <th>Current Location</th>
                                <th>Borrowed Room</th>
                                <th>User</th>
                                <th>Expected Return</th>
                                <th>Status</th>
                            <?php elseif ($report_type === 'notifications'): ?>
                                <th>Date / Time</th>
                                <th>Alert Type</th>
                                <th>Message</th>
                                <th>Related User/Room</th>
                            <?php elseif ($report_type === 'condition'): ?>
                                <th>Asset</th>
                                <th>Category</th>
                                <th>Brand / Serial</th>
                                <th>Condition</th>
                                <th>Availability</th>
                                <th>Location</th>
                                <th>Date Acquired</th>
                                <th>Last Updated</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($logs && $logs->num_rows > 0): ?>
                            <?php while($row = $logs->fetch_assoc()): ?>
                            <tr>
                                <?php if ($report_type === 'tracking'): ?>
                                    <td><span class="id-badge">#<?php echo $row['log_id']; ?></span></td>
                                    <td>
                                        <span class="d-block fw-bold"><?php echo date('h:i A', strtotime($row['scan_time'])); ?></span>
                                        <span class="text-muted small"><?php echo date('M d, Y', strtotime($row['scan_time'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['asset_name']; ?></div>
                                        <span class="text-muted small">Asset ID: <?php echo $row['asset_id']; ?></span>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-geo-alt me-1"></i><?php echo $row['room_name']; ?></div>
                                        <span class="text-muted small">Room ID: <?php echo $row['room_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo $row['full_name']; ?></div>
                                        <span class="text-muted small">User ID: <?php echo $row['user_id']; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-medium"><?php echo htmlspecialchars($row['status'] ?: 'Success'); ?></span>
                                    </td>

                                <?php elseif ($report_type === 'borrowing'): ?>
                                    <td><?php echo date('M d, Y', strtotime($row['borrow_date'])); ?></td>
                                    <td><?php echo $row['asset_name']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['current_room_name'] ?: 'Unknown'); ?><br>
                                        <span class="text-muted small">Room ID: <?php echo $row['current_room_id'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['borrowed_room_name'] ?: 'Unknown'); ?><br>
                                        <span class="text-muted small">Room ID: <?php echo $row['borrowed_room_id'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td><?php echo $row['full_name']; ?></td>
                                    <td><?php echo $row['expected_return_date'] ? date('M d, Y', strtotime($row['expected_return_date'])) : 'N/A'; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $row['borrow_status']; ?></span></td>

                                <?php elseif ($report_type === 'notifications'): ?>
                                    <td><?php echo date('M d, h:i A', strtotime($row['created_at'])); ?></td>
                                    <td class="text-capitalize"><?php echo str_replace('_', ' ', $row['type']); ?></td>
                                    <td><?php echo $row['message']; ?></td>
                                    <td><?php echo $row['full_name'] ?: $row['room_name']; ?></td>
                                <?php elseif ($report_type === 'condition'): ?>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['asset_name']); ?></div>
                                        <span class="text-muted small">Tag: <?php echo htmlspecialchars($row['asset_tag']); ?> · ID: <?php echo (int)$row['asset_id']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category'] ?: 'N/A'); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['brand'] ?: 'N/A'); ?></div>
                                        <span class="text-muted small">S/N: <?php echo htmlspecialchars($row['serial_number'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $condition = trim($row['status'] ?? '');
                                            $condition_label = $condition === '' ? 'Functional' : ucwords(str_replace('_', ' ', $condition));
                                            $condition_class = $condition === 'damage' ? 'bg-danger' : ($condition === 'need repair' ? 'bg-warning text-dark' : ($condition === '' ? 'bg-success' : 'bg-info text-dark'));
                                        ?>
                                        <span class="badge <?php echo $condition_class; ?>"><?php echo htmlspecialchars($condition_label); ?></span>
                                    </td>
                                    <td><span class="badge <?php echo $row['is_available'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $row['is_available'] ? 'Available' : 'Unavailable'; ?></span></td>
                                    <td><?php echo htmlspecialchars($row['room_name'] ?: 'Unassigned'); ?></td>
                                    <td><?php echo $row['date_acquired'] ? date('M d, Y', strtotime($row['date_acquired'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['last_updated'] ? date('M d, Y', strtotime($row['last_updated'])) : 'N/A'; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo $report_type === 'condition' ? 8 : ($report_type === 'borrowing' ? 7 : ($report_type === 'notifications' ? 4 : 6)); ?>" class="text-center py-4">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Report pages" class="mt-4 no-print">
                <ul class="pagination justify-content-center">
                    <?php for ($page_number = 1; $page_number <= $total_pages; $page_number++): ?>
                        <li class="page-item <?php echo $page_number === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?report_type=<?php echo urlencode($report_type); ?>&time_range=<?php echo urlencode($filter); ?>&page=<?php echo $page_number; ?>">
                                <?php echo $page_number; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('logSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
    </body>
    </html>