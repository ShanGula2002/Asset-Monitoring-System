<?php
session_start();
require '../db_connect.php';

// Security: Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/check_overdue.php';
createOverdueBorrowNotifications($conn);

$overdue_count = $conn->query("SELECT COUNT(*) AS count
                               FROM borrowing_logs
                               WHERE expected_return_date < NOW()
                                 AND actual_return_date IS NULL
                                 AND borrow_status IN ('active', 'overdue')")->fetch_assoc()['count'];

// --- DATA FETCHING ---
$total_assets = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];

// Fetch recent activity for the Latest Scans table
$recent_scans = $conn->query("SELECT t.*, a.asset_name, r.room_name, u.full_name 
                              FROM tracking_logs t 
                              JOIN assets a ON t.asset_id = a.asset_id 
                              JOIN rooms r ON t.room_id = r.room_id 
                              JOIN users u ON t.user_id = u.user_id 
                              ORDER BY t.scan_time DESC LIMIT 5");

// Fetch rooms for the map
$rooms_list = $conn->query("SELECT room_id, room_name, latitude, longitude, allowed_radius, room_image FROM rooms WHERE latitude IS NOT NULL");

// Fetch all assets and their assigned room directly from the assets table
$current_inventory = $conn->query("
    SELECT asset_id, asset_tag, asset_name, brand, serial_number, category, status, is_available, room_id
    FROM assets 
    WHERE room_id IS NOT NULL AND room_id != 0
");

// Build an array organizing assets by their room_id for the popup
$assets_by_room = [];
while ($item = $current_inventory->fetch_assoc()) {
    $roomId = $item['room_id'];
    if (!isset($assets_by_room[$roomId])) {
        $assets_by_room[$roomId] = [];
    }
    $assets_by_room[$roomId][] = [
        'asset_id' => (int) $item['asset_id'],
        'asset_tag' => $item['asset_tag'],
        'asset_name' => $item['asset_name'],
        'brand' => $item['brand'],
        'serial_number' => $item['serial_number'],
        'category' => $item['category'],
        'status' => $item['status'],
        'is_available' => (int) $item['is_available']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Asset Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --sidebar-bg: #1e1e2d;
        }

        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        
        .main-content { 
            margin-left: 260px; 
            padding: 35px; 
            transition: all 0.3s;
        }

        /* Stats Cards */
        .stat-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            text-decoration: none; /* For links */
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
        
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        /* Map UI */
        #map { height: 520px; border-radius: 0 0 16px 16px; z-index: 1; }
        .map-card { background: white; border-radius: 16px; border: none; overflow: hidden; }
        .map-header { padding: 1.25rem; background: white; border-bottom: 1px solid #edf2f9; }

        /* Table Styling */
        .content-card { border: none; border-radius: 16px; }
        .table thead th {
            background-color: #f8f9fc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #4e73df;
            padding: 15px;
            border: none;
        }
        .table tbody td { padding: 15px; vertical-align: middle; border-color: #f8f9fc; }

        /* Map Labels */
        .room-label {
            background: white;
            border: 2px solid #4e73df;
            color: #224abe;
            font-weight: 700;
            border-radius: 8px;
            padding: 4px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .badge-soft-info {
            background-color: #e0e9ff;
            color: #4e73df;
            border-radius: 6px;
        }

        .asset-detail-item { background: #f8f9fc; border: 1px solid #edf2f9; border-radius: 10px; }
        .asset-detail-label { color: #858796; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; }
        .asset-detail-value { color: #2d3748; font-size: .8rem; font-weight: 600; }
        .asset-details summary { color: #4e73df; cursor: pointer; font-size: .78rem; font-weight: 700; list-style: none; display: flex; align-items: center; gap: 6px; }
        .asset-details summary::-webkit-details-marker { display: none; }
        .asset-details summary .details-chevron { transition: transform .2s ease; }
        .asset-details[open] summary .details-chevron { transform: rotate(180deg); }
        .asset-details summary:focus-visible { outline: 2px solid #4e73df; outline-offset: 3px; border-radius: 3px; }

        /* Modal Design */
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { border-radius: 20px 20px 0 0; background: var(--primary-gradient); color: white; }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
            .sidebar { display: none; }
        }
 
        @media (max-width: 768px) {
            .main-content { padding: 76px 16px 24px; }
            .main-content > .d-flex:first-child { align-items: flex-start !important; gap: 16px; flex-direction: column; }
            .main-content > .d-flex:first-child > div:last-child { width: 100%; }
            .main-content > .d-flex:first-child .bg-white { justify-content: flex-start; width: 100%; }
            .row.g-4 { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }
            .stat-card { padding: 14px !important; }
            #map { height: min(62vh, 440px); min-height: 320px; }
            .map-header { padding: 1rem; gap: 12px; align-items: flex-start !important; }
            .map-header h5 { font-size: 1rem; line-height: 1.4; }
            .map-header .btn { white-space: nowrap; }
            .content-card .card-header { padding: 1.1rem !important; }
            .content-card .table { min-width: 650px; }
            .modal-dialog { margin: 12px; }
            .modal-body { padding: 1rem !important; }
        }

        @media (max-width: 420px) {
            .map-header { flex-direction: column; }
            .map-header .btn { width: 100%; }
            #map { min-height: 290px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-1">System Overview</h3>
            <p class="text-muted small mb-0">Live tracking & geofencing dashboard</p>
        </div>
        <div class="d-flex align-items-center bg-white p-2 rounded-4 shadow-sm border">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="me-3">
                <p class="small fw-bold mb-0"><?php echo $_SESSION['full_name'] ?? 'Admin User'; ?></p>
                <p class="text-muted small mb-0" style="font-size: 0.7rem;">Administrator</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-0">Total Assets</p>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $total_assets; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-0">Tracked Rooms</p>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $total_rooms; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="notifications.php" class="text-decoration-none h-100 d-block">
                <div class="card stat-card shadow-sm h-100 p-3 <?php echo ($overdue_count > 0) ? 'border-danger border-2' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon <?php echo ($overdue_count > 0) ? 'bg-danger text-white' : 'bg-warning bg-opacity-10 text-warning'; ?> me-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <p class="text-muted small fw-bold text-uppercase mb-0 <?php echo ($overdue_count > 0) ? 'text-danger' : ''; ?>">Overdue Items</p>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $overdue_count; ?></h3>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="map-card shadow-sm mb-5">
        <div class="map-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-map me-2 text-primary"></i> Live Asset Locations</h5>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="autoFocusMap()">
                <i class="bi bi-arrows-fullscreen me-1"></i> Recenter Map
            </button>
        </div>
        <div id="map"></div>
    </div>

    <div class="card content-card shadow-sm">
        <div class="card-header bg-white py-4 px-4 border-0">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Activity Logs</h5>
        </div>
        <div class="card-body px-0 pt-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Asset Information</th>
                            <th>Location</th>
                            <th>Staff Member</th>
                            <th class="pe-4">Scanned On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_scans->num_rows > 0): ?>
                            <?php while($row = $recent_scans->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3"><i class="bi bi-tag-fill text-primary"></i></div>
                                        <span class="fw-bold text-dark"><?php echo $row['asset_name']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info px-3 py-2">
                                        <i class="bi bi-door-closed me-1"></i> <?php echo $row['room_name']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><?php echo $row['full_name']; ?></div>
                                </td>
                                <td class="pe-4">
                                    <span class="text-muted small">
                                        <i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($row['scan_time'])); ?><br>
                                        <i class="bi bi-clock me-1"></i> <?php echo date('g:i A', strtotime($row['scan_time'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No recent activity found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="roomInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
            
            <div id="carouselSection" style="position: relative;">
                <div id="carouselContent">
                    </div>
                <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 50%; background: linear-gradient(transparent, rgba(0,0,0,0.8)); z-index: 1;"></div>
                
                <div style="position: absolute; bottom: 15px; left: 20px; z-index: 2; color: white;">
                    <h4 class="fw-bold mb-0" id="modalRoomName">Room Name</h4>
                    <span class="badge bg-primary rounded-pill small">Active Geofence</span>
                </div>
                
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                        style="position: absolute; top: 15px; right: 15px; z-index: 3; "></button>
            </div>

            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-box-seam text-primary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-0">Assets Stored Here</h6>
                </div>
                
                <div id="modalAssetList" class="list-group list-group-flush border-top-0">
                    </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light w-100 fw-bold py-2 rounded-3" data-bs-dismiss="modal">Close View</button>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Data passed from PHP
    const roomInventory = <?php echo json_encode($assets_by_room); ?>;

    // 2. Initialize Map
    var map = L.map('map').setView([10.5372, 122.8400], 18);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var viewGroup = L.featureGroup();

    // 3. Populate Map with Room Circles
    <?php 
    $rooms_list->data_seek(0); 
    while($room = $rooms_list->fetch_assoc()): 
    ?>
        var lat = <?php echo floatval($room['latitude']); ?>;
        var lon = <?php echo floatval($room['longitude']); ?>;
        var radius = <?php echo floatval($room['allowed_radius']); ?>;
        var roomId = <?php echo $room['room_id']; ?>;
        var roomName = "<?php echo addslashes($room['room_name']); ?>";
        // FETCH IMAGE FILENAME FROM PHP
        var roomImage = "<?php echo !empty($room['room_image']) ? $room['room_image'] : 'default_room.jpg'; ?>";
        
        if (lat !== 0 && lon !== 0) {
            var circle = L.circle([lat, lon], {
                color: '#4e73df',
                fillColor: '#4e73df',
                fillOpacity: 0.15,
                radius: radius > 0 ? radius : 15
            }).addTo(map);

            circle.bindTooltip(roomName, {
                permanent: true, direction: 'center', className: 'room-label'
            });

            // PASS IMAGE TO THE MODAL FUNCTION
            circle.on('click', function() {
                openInventoryModal(this.roomId, this.roomName, this.roomImage);
            }.bind({roomId: roomId, roomName: roomName, roomImage: roomImage})); 
            
            viewGroup.addLayer(circle);
        }
    <?php endwhile; ?>

    // 4. Updated Modal Population Logic
    function openInventoryModal(roomId, roomName, roomImage) {
        document.getElementById('modalRoomName').innerHTML = '<i class="bi bi-door-open me-2"></i>' + roomName;
        
        var listContainer = document.getElementById('modalAssetList');
        var carouselContainer = document.getElementById('carouselContent'); 
        
        listContainer.innerHTML = ''; 
        
        // DISPLAY THE ROOM PHOTO AT THE TOP
        carouselContainer.innerHTML = `
            <div class="carousel-item active">
                <img src="../uploads/${roomImage}" class="d-block w-100" style="height: 250px; object-fit: cover;" onerror="this.src='../assets/img/default_room.jpg'">
                <div class="carousel-caption bg-dark bg-opacity-50 py-1 rounded">
                    <p class="small mb-0">Current View: ${roomName}</p>
                </div>
            </div>`;

        // POPULATE ASSETS
        if (roomInventory[roomId] && roomInventory[roomId].length > 0) {
            roomInventory[roomId].forEach(function(asset) {
                var li = document.createElement('div');
                li.className = 'list-group-item py-3 px-0 border-0 border-bottom';
                var availability = Number(asset.is_available) === 1 ? 'Available' : 'Unavailable';
                var condition = asset.status ? asset.status : 'Functional';
                li.innerHTML = `
                    <div class="d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3"><i class="bi bi-box-seam text-primary"></i></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="fw-bold text-dark">${escapeHtml(asset.asset_name)}</span>
                                <span class="badge ${Number(asset.is_available) === 1 ? 'bg-success' : 'bg-warning text-dark'}">${availability}</span>
                            </div>
                            <details class="asset-details mt-2">
                                <summary><i class="bi bi-chevron-down details-chevron" aria-hidden="true"></i>View asset details</summary>
                                <div class="mt-2 row g-2">
                                    <div class="col-6"><div class="asset-detail-item p-2"><div class="asset-detail-label">Asset tag</div><div class="asset-detail-value text-truncate">${escapeHtml(asset.asset_tag || 'N/A')}</div></div></div>
                                    <div class="col-6"><div class="asset-detail-item p-2"><div class="asset-detail-label">Category</div><div class="asset-detail-value text-truncate">${escapeHtml(asset.category || 'N/A')}</div></div></div>
                                    <div class="col-6"><div class="asset-detail-item p-2"><div class="asset-detail-label">Brand</div><div class="asset-detail-value text-truncate">${escapeHtml(asset.brand || 'N/A')}</div></div></div>
                                    <div class="col-6"><div class="asset-detail-item p-2"><div class="asset-detail-label">Condition</div><div class="asset-detail-value text-truncate">${escapeHtml(condition)}</div></div></div>
                                    <div class="col-12"><div class="asset-detail-item p-2"><div class="asset-detail-label">Serial number</div><div class="asset-detail-value text-truncate">${escapeHtml(asset.serial_number || 'N/A')}</div></div></div>
                                </div>
                            </details>
                        </div>
                    </div>`;
                listContainer.appendChild(li);
            });
        } else {
            listContainer.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-info-circle mb-2 d-block fs-4"></i>No assets currently in this room.</div>';
        }

        var myModal = new bootstrap.Modal(document.getElementById('roomInventoryModal'));
        myModal.show();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, function(character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[character];
        });
    }

    // 5. Map Auto-Focus
    function autoFocusMap() {
        if (viewGroup.getLayers().length > 0) {
            map.fitBounds(viewGroup.getBounds().pad(0.2)); 
        }
    }

    autoFocusMap();
    setTimeout(function() { map.invalidateSize(); }, 400);
</script>

</body>
</html>