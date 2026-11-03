<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$selected_asset_id = max(0, (int) ($_GET['asset_id'] ?? 0));
$assets_sql = "SELECT a.asset_id, a.asset_tag, a.asset_name, a.category, a.status, r.room_name
               FROM assets a
               LEFT JOIN rooms r ON a.room_id = r.room_id";

if ($search !== '') {
    $search_like = '%' . $search . '%';
    $assets_stmt = $conn->prepare($assets_sql . " WHERE a.asset_tag LIKE ? OR a.asset_name LIKE ? OR a.category LIKE ? ORDER BY a.asset_name, a.asset_tag");
    $assets_stmt->bind_param('sss', $search_like, $search_like, $search_like);
} else {
    $assets_stmt = $conn->prepare($assets_sql . ' ORDER BY a.asset_name, a.asset_tag');
}
$assets_stmt->execute();
$assets_result = $assets_stmt->get_result();
$assets = [];
while ($asset = $assets_result->fetch_assoc()) {
    $assets[] = $asset;
}

$selected_asset = null;
$movements = [];
if ($selected_asset_id > 0) {
    $asset_stmt = $conn->prepare("SELECT a.asset_id, a.asset_tag, a.asset_name, a.category, a.status, r.room_name
                                  FROM assets a
                                  LEFT JOIN rooms r ON a.room_id = r.room_id
                                  WHERE a.asset_id = ?");
    $asset_stmt->bind_param('i', $selected_asset_id);
    $asset_stmt->execute();
    $selected_asset = $asset_stmt->get_result()->fetch_assoc();

    if ($selected_asset) {
        $movement_sql = "SELECT t.scan_time AS occurred_at, 'Tracking' AS movement_type,
                                COALESCE(NULLIF(t.status, ''), 'Asset scanned') AS activity,
                                r.room_name, u.full_name AS person, t.note AS details
                         FROM tracking_logs t
                         LEFT JOIN rooms r ON t.room_id = r.room_id
                         LEFT JOIN users u ON t.user_id = u.user_id
                         WHERE t.asset_id = ?
                         UNION ALL
                         SELECT b.borrow_date AS occurred_at, 'Borrowing' AS movement_type,
                                CONCAT('Borrowed (', COALESCE(b.borrow_status, 'active'), ')') AS activity,
                                br.room_name, u.full_name AS person,
                                CONCAT('Expected return: ', DATE_FORMAT(b.expected_return_date, '%b %e, %Y %l:%i %p')) AS details
                         FROM borrowing_logs b
                         LEFT JOIN rooms br ON b.borrowed_room_id = br.room_id
                         LEFT JOIN users u ON b.user_id = u.user_id
                         WHERE b.asset_id = ?
                         UNION ALL
                         SELECT b.actual_return_date AS occurred_at, 'Return' AS movement_type,
                                'Asset returned' AS activity, br.room_name, u.full_name AS person,
                                CONCAT('Borrow record #', b.borrow_id) AS details
                         FROM borrowing_logs b
                         LEFT JOIN rooms br ON b.borrowed_room_id = br.room_id
                         LEFT JOIN users u ON b.user_id = u.user_id
                         WHERE b.asset_id = ? AND b.actual_return_date IS NOT NULL
                         ORDER BY occurred_at DESC";
        $movement_stmt = $conn->prepare($movement_sql);
        $movement_stmt->bind_param('iii', $selected_asset_id, $selected_asset_id, $selected_asset_id);
        $movement_stmt->execute();
        $movement_result = $movement_stmt->get_result();
        while ($movement = $movement_result->fetch_assoc()) {
            $movements[] = $movement;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Audit | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --ink: #24323d; --muted: #71808b; --line: #e2e8eb; --teal: #087e79; --paper: #fff; }
        body { background: #f3f6f5; color: var(--ink); font-family: 'Plus Jakarta Sans', sans-serif; }
        .content { margin-left: 260px; padding: 30px; width: calc(100% - 260px); }
        .page-heading { border-bottom: 1px solid #dce4e3; padding-bottom: 18px; margin-bottom: 22px; }
        .page-heading h1 { font-size: 1.65rem; font-weight: 700; margin: 0; }
        .audit-layout { display: grid; grid-template-columns: minmax(270px, 340px) minmax(0, 1fr); gap: 20px; align-items: start; }
        .panel { background: var(--paper); border: 1px solid var(--line); border-radius: 8px; }
        .asset-panel { overflow: hidden; }
        .panel-heading { padding: 16px 18px; border-bottom: 1px solid var(--line); }
        .asset-search { padding: 12px 14px; border-bottom: 1px solid var(--line); }
        .asset-list { max-height: calc(100vh - 260px); min-height: 280px; overflow-y: auto; }
        .asset-link { display: block; padding: 13px 16px; color: inherit; text-decoration: none; border-bottom: 1px solid #edf0f1; }
        .asset-link:last-child { border-bottom: 0; }
        .asset-link:hover { background: #f5faf9; }
        .asset-link.active { background: #e8f5f3; box-shadow: inset 3px 0 var(--teal); }
        .asset-title { font-size: .9rem; font-weight: 700; }
        .asset-meta { color: var(--muted); font-size: .75rem; margin-top: 3px; }
        .tag { display: inline-block; color: #53626b; background: #f1f4f5; border: 1px solid #e2e8eb; border-radius: 3px; padding: 2px 6px; font-family: monospace; font-size: .72rem; }
        .audit-summary { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--line); }
        .audit-summary h2 { font-size: 1.1rem; font-weight: 700; margin: 0 0 5px; }
        .timeline { list-style: none; margin: 0; padding: 4px 20px 18px; }
        .movement { display: grid; grid-template-columns: 22px minmax(0, 1fr); gap: 12px; padding: 15px 0; border-bottom: 1px solid #edf0f1; }
        .movement:last-child { border-bottom: 0; }
        .movement-marker { width: 10px; height: 10px; margin: 5px 0 0 4px; border-radius: 50%; background: var(--teal); box-shadow: 0 0 0 4px #e2f2f0; }
        .movement-type { color: var(--teal); font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        .movement-activity { font-size: .9rem; font-weight: 700; margin: 2px 0 4px; }
        .movement-details { color: var(--muted); font-size: .79rem; }
        .empty-state { padding: 54px 22px; text-align: center; color: var(--muted); }
        .empty-state i { display: block; font-size: 1.8rem; margin-bottom: 10px; color: #9aa9ac; }
        .print-button { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cbd6d6; border-radius: 5px; padding: 8px 12px; background: #fff; color: var(--ink); font: inherit; font-size: .82rem; font-weight: 600; }
        .print-button:hover { border-color: var(--teal); color: var(--teal); }
        @media print {
            @page { margin: 14mm; }
            body { background: #fff; color: #111; font-family: Arial, sans-serif; }
            .sidebar, .mobile-menu-toggle, .sidebar-backdrop, .asset-panel, .print-button { display: none !important; }
            .content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .page-heading { margin-bottom: 14px; padding-bottom: 10px; border-color: #999; }
            .page-heading h1 { font-size: 18pt; }
            .audit-layout { display: block; }
            .panel { border: 0; border-radius: 0; }
            .audit-summary { padding: 10px 0; border-color: #bbb; }
            .timeline { padding: 0; }
            .movement { grid-template-columns: 14px minmax(0, 1fr); gap: 8px; padding: 10px 0; border-color: #ddd; break-inside: avoid; }
            .movement-marker { width: 7px; height: 7px; margin-left: 2px; background: #111; box-shadow: none; }
            .movement-type, .movement-details, .text-muted { color: #444 !important; }
            .movement-activity { color: #111; }
            .tag { color: #111; border-color: #aaa; background: transparent; }
        }
        @media (max-width: 992px) { .audit-layout { grid-template-columns: minmax(0, 1fr); } .asset-list { max-height: 320px; min-height: 0; } }
        @media (max-width: 768px) { .content { margin-left: 0; width: 100%; padding: 76px 16px 24px; } .page-heading h1 { font-size: 1.4rem; } .audit-summary { padding: 15px; } .timeline { padding-left: 15px; padding-right: 15px; } }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="content">
    <header class="page-heading">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 text-uppercase small fw-bold mb-2" style="color: var(--teal);"><i class="bi bi-clock-history"></i> Inventory history</div>
                <h1>Asset Audit</h1>
            </div>
            <?php if ($selected_asset): ?>
                <button class="print-button" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print report</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="audit-layout">
        <section class="panel asset-panel" aria-label="Asset list">
            <div class="panel-heading d-flex justify-content-between align-items-center">
                <div><strong>Assets</strong><div class="text-muted small"><?php echo count($assets); ?> shown</div></div>
                <i class="bi bi-box-seam text-secondary"></i>
            </div>
            <form class="asset-search" method="GET">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Find by name, tag, category" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($selected_asset_id > 0): ?><input type="hidden" name="asset_id" value="<?php echo $selected_asset_id; ?>"><?php endif; ?>
                </div>
            </form>
            <div class="asset-list">
                <?php if (!$assets): ?>
                    <div class="empty-state">No matching assets found.</div>
                <?php else: ?>
                    <?php foreach ($assets as $asset): ?>
                        <a class="asset-link <?php echo (int) $asset['asset_id'] === $selected_asset_id ? 'active' : ''; ?>" href="?asset_id=<?php echo (int) $asset['asset_id']; ?><?php echo $search !== '' ? '&search=' . rawurlencode($search) : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="asset-title"><?php echo htmlspecialchars($asset['asset_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <i class="bi bi-chevron-right small text-secondary"></i>
                            </div>
                            <div class="asset-meta"><span class="tag"><?php echo htmlspecialchars($asset['asset_tag'], ENT_QUOTES, 'UTF-8'); ?></span> <span><?php echo htmlspecialchars($asset['room_name'] ?: 'No assigned room', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel" aria-label="Asset movement history">
            <?php if ($selected_asset_id === 0): ?>
                <div class="empty-state"><i class="bi bi-arrow-left-circle"></i>Select an asset to view its movement history.</div>
            <?php elseif (!$selected_asset): ?>
                <div class="empty-state"><i class="bi bi-exclamation-circle"></i>This asset could not be found.</div>
            <?php else: ?>
                <div class="audit-summary">
                    <div>
                        <h2><?php echo htmlspecialchars($selected_asset['asset_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span class="tag"><?php echo htmlspecialchars($selected_asset['asset_tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="text-muted small ms-1">Current room: <?php echo htmlspecialchars($selected_asset['room_name'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <span class="badge rounded-pill text-bg-light border"><?php echo count($movements); ?> events</span>
                </div>
                <?php if (!$movements): ?>
                    <div class="empty-state"><i class="bi bi-journal-x"></i>No tracking or borrowing history is recorded for this asset.</div>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($movements as $movement): ?>
                            <li class="movement">
                                <span class="movement-marker" aria-hidden="true"></span>
                                <div>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                                        <span class="movement-type"><?php echo htmlspecialchars($movement['movement_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="text-muted small" datetime="<?php echo htmlspecialchars($movement['occurred_at'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(date('M j, Y · g:i A', strtotime($movement['occurred_at'])), ENT_QUOTES, 'UTF-8'); ?></time>
                                    </div>
                                    <div class="movement-activity"><?php echo htmlspecialchars($movement['activity'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="movement-details">
                                        <?php echo htmlspecialchars($movement['person'] ?: 'Unknown user', ENT_QUOTES, 'UTF-8'); ?>
                                        <span class="mx-1">·</span><?php echo htmlspecialchars($movement['room_name'] ?: 'No room recorded', ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($movement['details'])): ?><span class="mx-1">·</span><?php echo htmlspecialchars($movement['details'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>