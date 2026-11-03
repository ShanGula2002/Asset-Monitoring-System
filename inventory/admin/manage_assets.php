<?php
session_start();
require '../db_connect.php';

// ENABLE ERROR REPORTING FOR DEBUGGING
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- Handle Adding Asset ---
if (isset($_POST['add_asset'])) {
    $tag = $_POST['asset_tag'];
    $name = $_POST['asset_name'];
    $brand = $_POST['brand'];
    $serial = $_POST['serial_number'];
    $date_acq = $_POST['date_acquired'];
    $cat = $_POST['category'];
    $room = $_POST['room_id'];

    try {
        $room_stmt = $conn->prepare("SELECT latitude, longitude FROM rooms WHERE room_id = ?");
        $room_stmt->bind_param("i", $room);
        $room_stmt->execute();
        $coords = $room_stmt->get_result()->fetch_assoc();
        
        $lat = $coords['latitude'] ?? 0;
        $lng = $coords['longitude'] ?? 0;

        $stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, brand, serial_number, date_acquired, category, room_id, is_available, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
        $stmt->bind_param("ssssssidd", $tag, $name, $brand, $serial, $date_acq, $cat, $room, $lat, $lng);
        $stmt->execute();
        
        header("Location: manage_assets.php?success=1");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            header("Location: manage_assets.php?error=duplicate");
            exit();
        } else {
            die("Insert Error: " . $e->getMessage());
        }
    }
}

// --- Handle Quick Status Update ---
if (isset($_POST['update_status_only'])) {
    $aid = $_POST['asset_id'];
    $status = $_POST['status'];
    $available = (empty($status)) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE assets SET status = ?, is_available = ? WHERE asset_id = ?");
    $stmt->bind_param("sii", $status, $available, $aid); 
    
    if($stmt->execute()) {
        header("Location: manage_assets.php?status=updated");
        exit();
    }
}

// --- Handle Asset Transfer ---
if (isset($_POST['transfer_asset'])) {
    $aid = $_POST['asset_id'];
    $room = $_POST['transfer_room_id'];

    $room_stmt = $conn->prepare("SELECT latitude, longitude FROM rooms WHERE room_id = ?");
    $room_stmt->bind_param("i", $room);
    $room_stmt->execute();
    $coords = $room_stmt->get_result()->fetch_assoc();

    $lat = $coords['latitude'] ?? 0;
    $lng = $coords['longitude'] ?? 0;

    $stmt = $conn->prepare("UPDATE assets SET room_id=?, latitude=?, longitude=? WHERE asset_id=?");
    $stmt->bind_param("iidi", $room, $lat, $lng, $aid);
    $stmt->execute();

    header("Location: manage_assets.php?status=transferred");
    exit();
}

// --- Handle Full Editing Asset ---
if (isset($_POST['edit_asset'])) {
    $aid = $_POST['asset_id'];
    $tag = $_POST['asset_tag'];
    $name = $_POST['asset_name'];
    $brand = $_POST['brand'];
    $serial = $_POST['serial_number'];
    $date_acq = $_POST['date_acquired'];
    $cat = $_POST['category'];
    $status = $_POST['status'];
    $available = isset($_POST['is_available']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("UPDATE assets SET asset_tag=?, asset_name=?, brand=?, serial_number=?, date_acquired=?, category=?, status=?, is_available=? WHERE asset_id=?");
        $stmt->bind_param("ssssssii", $tag, $name, $brand, $serial, $date_acq, $cat, $status, $available, $aid);
        $stmt->execute();
        
        header("Location: manage_assets.php?status=updated");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            header("Location: manage_assets.php?error=duplicate");
            exit();
        } else {
            die("Update Error: " . $e->getMessage());
        }
    }
}

$searchTerm = trim($_GET['search'] ?? '');
$searchWhere = '';
$bindParams = [];
$bindTypes = '';

if ($searchTerm !== '') {
    $searchLike = '%' . $searchTerm . '%';
    $searchWhere = "
        WHERE (
            a.asset_tag LIKE ? OR
            a.asset_name LIKE ? OR
            a.brand LIKE ? OR
            a.serial_number LIKE ? OR
            a.category LIKE ? OR
            r.room_name LIKE ?
        )";
    $bindParams = [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike];
    $bindTypes = 'ssssss';
}

$sql = "
    SELECT a.*, r.room_name,
            (SELECT u.full_name
            FROM borrowing_logs bl
            JOIN users u ON bl.user_id = u.user_id
            WHERE bl.asset_id = a.asset_id
              AND bl.actual_return_date IS NULL
            ORDER BY bl.borrow_date DESC LIMIT 1) as borrower_name
    FROM assets a
    LEFT JOIN rooms r ON a.room_id = r.room_id
    $searchWhere
";

$stmt = $conn->prepare($sql);
if ($searchTerm !== '') {
    $stmt->bind_param($bindTypes, ...$bindParams);
}
$stmt->execute();
$assets = $stmt->get_result();
$rooms = $conn->query("SELECT * FROM rooms");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assets | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); transition: all 0.3s; }
        .inventory-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .table thead th { background-color: #f8f9fc; text-transform: uppercase; font-size: 0.7rem; color: #4e73df; padding: 20px; border: none; }
        .tag-badge { background: #f1f4f8; color: #2d3748; font-family: monospace; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; border: 1px solid #e2e8f0; }
        .status-pill { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; font-weight: 600; }
        .modal-content { border-radius: 24px; border: none; }
        
        @media print {
            body * { visibility: hidden; }
            #qrPrintArea, #qrPrintArea * { visibility: visible; }
            #qrPrintArea { position: absolute; left: 0; top: 0; width: 100%; text-align: center; }
        }

        @media (max-width: 768px) {
            .content > .d-flex:first-child { align-items: flex-start !important; flex-direction: column; gap: 16px; margin-bottom: 1.5rem !important; }
            .content > .d-flex:first-child .btn { width: 100%; }
            .inventory-card .table { min-width: 980px; }
            .inventory-card .table thead th, .inventory-card .table tbody td { padding: 12px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">Inventory Management</h2>
            <p class="text-muted mb-0">Track asset health and availability.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2" id="assetSearchForm">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="search"
                           id="assetSearchInput"
                           class="form-control border-start-0"
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           placeholder="Search asset, tag, room..."
                           style="min-width: 260px;">
                </div>
                <?php if ($searchTerm !== ''): ?>
                    <a href="manage_assets.php" class="btn btn-light">Clear</a>
                <?php endif; ?>
            </div>

            <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                <i class="bi bi-plus-lg me-2"></i> Add New Asset
            </button>
        </div>
    </div>

    <div class="card inventory-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="assetTable">
                    <thead>
                        <tr>
                            <th>QR Code</th> 
                            <th>Asset Info</th>
                            <th>Hardware Details</th>
                            <th>Category & Date</th>
                            <th>Location</th>
                            <th>Availability</th>
                            <th>Status (Condition)</th>
                            <th>Last Updated</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $assets->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                    $qr_data = $row['asset_tag'];
                                    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_data);
                                ?>
                                <img src="<?php echo $qr_url; ?>" 
                                     alt="QR" 
                                     style="width: 50px; cursor: pointer;" 
                                     class="border rounded p-1 bg-white shadow-sm"
                                     onclick="viewQR('<?php echo htmlspecialchars($qr_data); ?>', '<?php echo htmlspecialchars(addslashes($row['asset_name'])); ?>')">
                            </td>

                            <td>
                                <span class="tag-badge mb-1 d-inline-block"><?php echo htmlspecialchars($row['asset_tag']); ?></span>
                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($row['asset_name']); ?></span>
                            </td>
                            
                            <td>
                                <div class="text-dark small"><span class="text-muted">Brand:</span> <?php echo htmlspecialchars($row['brand'] ?: 'N/A'); ?></div>
                                <div class="text-dark small"><span class="text-muted">S/N:</span> <?php echo htmlspecialchars($row['serial_number'] ?: 'N/A'); ?></div>
                            </td>

                            <td>
                                <span class="badge bg-light text-primary border mb-1"><?php echo htmlspecialchars($row['category']); ?></span>
                                <div class="small text-muted">Acq: <?php echo $row['date_acquired'] ? date('M d, Y', strtotime($row['date_acquired'])) : 'N/A'; ?></div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                    <span><?php echo htmlspecialchars($row['room_name'] ?? 'Unassigned'); ?></span>
                                </div>
                            </td>

                            <td>
                                <?php if($row['is_available'] == 1): ?>
                                    <span class="status-pill bg-success text-white">Available</span>
                                <?php else: ?>
                                    <div class="d-flex flex-column">
                                        <?php if(!empty($row['borrower_name'])): ?>
                                            <span class="status-pill bg-warning text-dark mb-1">
                                                <i class="bi bi-person-walking me-1"></i>Borrowed
                                            </span>
                                            <small class="text-muted fw-bold" style="font-size: 0.7rem; line-height: 1;">
                                                By: <?php echo htmlspecialchars($row['borrower_name']); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="status-pill bg-secondary text-white">Unavailable</span>
                                            <small class="text-muted" style="font-size: 0.7rem;">Maintenance</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php 
                                    if($row['status'] == 'damage') {
                                        echo '<span class="status-pill bg-danger text-white">Damaged</span>';
                                    } elseif($row['status'] == 'need repair') {
                                        echo '<span class="status-pill bg-info text-dark">Need Repair</span>';
                                    } else {
                                        echo '<span class="text-muted small">Functional</span>';
                                    }
                                ?>
                            </td>

                            <td class="small text-muted">
                                <?php echo $row['last_updated'] ? date('M d, H:i', strtotime($row['last_updated'])) : 'Never'; ?>
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-outline-primary btn-sm rounded-2" onclick='openEditAssetModal(<?php echo json_encode($row); ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <?php if(!empty($row['borrower_name'])): ?>
                                        <button class="btn btn-light btn-sm rounded-2 text-muted" style="cursor: not-allowed; opacity:0.6;" title="Cannot update status while borrowed" disabled>
                                            <i class="bi bi-tools"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm rounded-2"
                                                onclick="openStatusModal(<?php echo $row['asset_id']; ?>, '<?php echo $row['status']; ?>')">
                                            <i class="bi bi-tools"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="modal-header border-0 pb-0 justify-content-center position-relative">
                <h6 class="modal-title fw-bold">Asset QR Tag</h6>
                <button type="button" class="btn-close position-absolute end-0" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-0">
                <div id="qrPrintArea">
                    <img id="largeQR" src="" class="img-fluid border p-2 rounded mb-3" style="width: 200px;">
                    <h5 id="qrTagText" class="fw-bold mb-1 font-monospace"></h5>
                    <p id="qrAssetName" class="text-muted small mb-0"></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-4">
                <button class="btn btn-dark w-100" onclick="window.print()"><i class="bi bi-printer me-2"></i> Print Tag</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="asset_id" id="status_asset_id">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Update Condition</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select name="status" id="quick_status_select" class="form-select border-2">
                    <option value="">✅ Functional</option>
                    <option value="need repair">🔧 Need Repair</option>
                    <option value="damage">❌ Damaged</option>
                </select>
                <p class="text-muted small mt-2 mb-0">Setting to Repair/Damage will mark item as unavailable.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" name="update_status_only" class="btn btn-primary w-100">Update</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">New School Asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">QR Tag ID</label><input type="text" name="asset_tag" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Item Name</label><input type="text" name="asset_name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Date Acquired</label><input type="date" name="date_acquired" class="form-control"></div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="Electronics">Electronics</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Lab Equipment">Lab Equipment</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Permanent Room</label>
                        <select name="room_id" class="form-select">
                            <?php $rooms->data_seek(0); while($r = $rooms->fetch_assoc()): ?>
                                <option value="<?php echo $r['room_id']; ?>"><?php echo $r['room_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" name="add_asset" class="btn btn-primary px-4">Save Asset</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="asset_id" id="edit_asset_id">
            <div class="modal-header"><h5 class="modal-title text-primary">Edit Asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Tag</label><input type="text" name="asset_tag" id="edit_asset_tag" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="asset_name" id="edit_asset_name" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Brand</label><input type="text" name="brand" id="edit_brand" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Serial</label><input type="text" name="serial_number" id="edit_serial" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Acquired</label><input type="date" name="date_acquired" id="edit_date" class="form-control"></div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_category" class="form-select">
                            <option value="Electronics">Electronics</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Lab Equipment">Lab Equipment</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="">Functional</option>
                            <option value="need repair">Need Repair</option>
                            <option value="damage">Damaged</option>
                        </select>
                    </div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" id="edit_available" value="1"><label class="form-check-label">Available for Borrow</label></div></div>
                    <div class="col-12">
                        <input type="hidden" id="edit_current_room_id">
                        <button type="button" class="btn btn-outline-primary w-100" onclick="openTransferModal()">
                            <i class="bi bi-arrow-left-right me-2"></i> Transfer
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="submit" name="edit_asset" class="btn btn-primary px-4">Update</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="transferAssetModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="asset_id" id="transfer_asset_id">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Transfer Asset</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Available Rooms</label>
                <select name="transfer_room_id" id="transfer_room_id" class="form-select" required>
                    <?php $rooms->data_seek(0); while($r = $rooms->fetch_assoc()): ?>
                        <option value="<?php echo $r['room_id']; ?>"><?php echo htmlspecialchars($r['room_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" name="transfer_asset" class="btn btn-primary w-100">Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Modal Handlers
function openEditAssetModal(asset) {
    document.getElementById('edit_asset_id').value = asset.asset_id;
    document.getElementById('edit_asset_tag').value = asset.asset_tag;
    document.getElementById('edit_asset_name').value = asset.asset_name;
    document.getElementById('edit_brand').value = asset.brand;
    document.getElementById('edit_serial').value = asset.serial_number;
    document.getElementById('edit_date').value = asset.date_acquired;
    document.getElementById('edit_category').value = asset.category;
    document.getElementById('edit_current_room_id').value = asset.room_id;
    document.getElementById('edit_status').value = asset.status;
    document.getElementById('edit_available').checked = (asset.is_available == 1);
    new bootstrap.Modal(document.getElementById('editAssetModal')).show();
}

function openTransferModal() {
    const assetId = document.getElementById('edit_asset_id').value;
    const currentRoomId = document.getElementById('edit_current_room_id').value;
    const select = document.getElementById('transfer_room_id');

    document.getElementById('transfer_asset_id').value = assetId;

    Array.from(select.options).forEach(option => {
        if (Number(option.value) === Number(currentRoomId)) {
            option.remove();
        }
    });

    if (!select.options.length) {
        select.innerHTML = '<option value="">No available rooms</option>';
    }

    const editModal = bootstrap.Modal.getInstance(document.getElementById('editAssetModal'));
    if (editModal) editModal.hide();

    new bootstrap.Modal(document.getElementById('transferAssetModal')).show();
}

function openStatusModal(id, currentStatus) {
    document.getElementById('status_asset_id').value = id;
    document.getElementById('quick_status_select').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function viewQR(tag, name) {
    const qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" + encodeURIComponent(tag);
    document.getElementById('largeQR').src = qrUrl;
    document.getElementById('qrTagText').innerText = tag;
    document.getElementById('qrAssetName').innerText = name;
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}

// --- SweetAlert Trigger Logic ---
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchInput = document.getElementById('assetSearchInput');
    const assetRows = document.querySelectorAll('#assetTable tbody tr');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            assetRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const matches = !query || rowText.includes(query);
                row.style.display = matches ? '' : 'none';
            });
        });
    }
    
    // Duplicate Error
    if (urlParams.has('error') && urlParams.get('error') === 'duplicate') {
        Swal.fire({
            icon: 'error',
            title: 'Duplicate Entry!',
            text: 'This Asset Tag or Serial Number is already registered.',
            confirmButtonColor: '#4e73df'
        });
    }

    // Success Adding
    if (urlParams.has('success')) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'New asset has been added to inventory.',
            timer: 3000,
            showConfirmButton: false
        });
    }

    // Success Update
    if (urlParams.get('status') === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Asset records have been modified.',
            timer: 2500,
            showConfirmButton: false
        });
    }

    if (urlParams.get('status') === 'transferred') {
        Swal.fire({
            icon: 'success',
            title: 'Asset Transferred!',
            text: 'The asset has been moved to a new room.',
            timer: 2500,
            showConfirmButton: false
        });
    }

    // Clean URL to prevent re-triggering on refresh
    window.history.replaceState(null, null, window.location.pathname);
});
</script>
</body>
</html>