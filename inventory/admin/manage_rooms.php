<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- 1. HANDLE ADDING ROOM ---
if (isset($_POST['add_room'])) {
    $name = $_POST['room_name'];
    $desc = $_POST['description'];
    $lat  = $_POST['latitude'];
    $lon  = $_POST['longitude'];
    $rad  = $_POST['radius']; 

    $image_name = 'default_room.jpg'; 
    if (!empty($_FILES['room_image']['name'])) {
        $image_name = time() . '_' . $_FILES['room_image']['name'];
        move_uploaded_file($_FILES['room_image']['tmp_name'], "../uploads/" . $image_name);
    }

    try {
        $stmt = $conn->prepare("INSERT INTO rooms (room_name, description, latitude, longitude, allowed_radius, room_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddis", $name, $desc, $lat, $lon, $rad, $image_name);
        $stmt->execute();
        header("Location: manage_rooms.php?status=added");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // 1062 is the MySQL error code for Duplicate Entry
            header("Location: manage_rooms.php?status=duplicate");
            exit();
        } else {
            header("Location: manage_rooms.php?status=error");
            exit();
        }
    }
}

// --- 2. HANDLE EDITING ROOM ---
if (isset($_POST['edit_room'])) {
    $rid  = $_POST['room_id'];
    $name = $_POST['room_name'];
    $desc = $_POST['description'];
    $lat  = $_POST['latitude'];
    $lon  = $_POST['longitude'];
    $rad  = $_POST['radius'];

    try {
        if (!empty($_FILES['room_image']['name'])) {
            $old_img_query = $conn->prepare("SELECT room_image FROM rooms WHERE room_id = ?");
            $old_img_query->bind_param("i", $rid);
            $old_img_query->execute();
            $old_img = $old_img_query->get_result()->fetch_assoc()['room_image'];

            if ($old_img && $old_img !== 'default_room.jpg' && file_exists("../uploads/" . $old_img)) {
                unlink("../uploads/" . $old_img);
            }

            $image_name = time() . '_' . $_FILES['room_image']['name'];
            move_uploaded_file($_FILES['room_image']['tmp_name'], "../uploads/" . $image_name);

            $stmt = $conn->prepare("UPDATE rooms SET room_name=?, description=?, latitude=?, longitude=?, allowed_radius=?, room_image=? WHERE room_id=?");
            $stmt->bind_param("ssddisi", $name, $desc, $lat, $lon, $rad, $image_name, $rid);
        } else {
            $stmt = $conn->prepare("UPDATE rooms SET room_name=?, description=?, latitude=?, longitude=?, allowed_radius=? WHERE room_id=?");
            $stmt->bind_param("ssddii", $name, $desc, $lat, $lon, $rad, $rid);
        }

        $stmt->execute();
        header("Location: manage_rooms.php?status=updated");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            header("Location: manage_rooms.php?status=duplicate");
            exit();
        } else {
            header("Location: manage_rooms.php?status=error");
            exit();
        }
    }
}

// --- 3. FETCH ROOMS ---
$rooms_query = "SELECT r.*, COUNT(a.asset_id) as asset_count 
                FROM rooms r 
                LEFT JOIN assets a ON r.room_id = a.room_id 
                GROUP BY r.room_id";
$rooms = $conn->query($rooms_query);


// --- 4. HANDLE DELETING ROOM ---
if (isset($_GET['delete'])) {
    $rid = $_GET['delete'];

    $check_assets = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE room_id = ?");
    $check_assets->bind_param("i", $rid);
    $check_assets->execute();
    $has_assets = $check_assets->get_result()->fetch_assoc()['total'];

    $check_logs = $conn->prepare("SELECT COUNT(*) as total FROM tracking_logs WHERE room_id = ?");
    $check_logs->bind_param("i", $rid);
    $check_logs->execute();
    $has_logs = $check_logs->get_result()->fetch_assoc()['total'];

    if ($has_assets > 0) {
        header("Location: manage_rooms.php?status=error_has_assets");
    } elseif ($has_logs > 0) {
        header("Location: manage_rooms.php?status=error_has_logs");
    } else {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
        $stmt->bind_param("i", $rid);
        $stmt->execute();
        header("Location: manage_rooms.php?status=deleted");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | GPS Geofencing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); transition: all 0.3s; }

        .room-card { border: none; border-radius: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #fff; position: relative; overflow: hidden; }
        .room-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important; }
        .room-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #4e73df, #224abe); }

        .gps-box { background: #f8f9fc; border-radius: 12px; padding: 12px; border: 1px dashed #d1d3e2; }
        .gps-coord { font-family: 'Monaco', 'Consolas', monospace; font-size: 0.75rem; color: #6e707e; }

        .modal-content { border-radius: 24px; border: none; }
        .modal-header { padding: 25px 30px; border-bottom: 1px solid #edf2f9; }
        .modal-body { padding: 30px; }
        .form-control-gps { background-color: #f8f9fc; font-weight: 600; color: #4e73df; text-align: center; }
        
        /* Map Styles */
        .map-container { height: 250px; border-radius: 12px; border: 2px solid #e3e6f0; margin-bottom: 15px; }

        /* Print QR Styling */
        @media print {
            body * { visibility: hidden; }
            #qrPrintArea, #qrPrintArea * { visibility: visible; }
            #qrPrintArea { position: absolute; left: 0; top: 0; width: 100%; text-align: center; }
        }

        @media (max-width: 768px) {
            .content > .d-flex:first-child { align-items: flex-start !important; flex-direction: column; gap: 16px; margin-bottom: 1.5rem !important; }
            .content > .d-flex:first-child .btn { width: 100%; }
            .room-card .card-body { padding: 1.1rem !important; }
            .room-card .d-flex.gap-2 { flex-wrap: wrap; }
            .room-card .d-flex.gap-2 .btn:first-child { min-width: 0; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">Classrooms & Geofencing</h2>
            <p class="text-muted mb-0">Define GPS boundaries to prevent unauthorized asset movement.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="bi bi-geo-alt-fill me-2"></i> Add New Geofence
        </button>
    </div>

    <div class="row g-4">
        <?php while($row = $rooms->fetch_assoc()): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card room-card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                            <i class="bi bi-door-open-fill text-primary fs-4"></i>
                        </div>
                        <div class="d-flex flex-column align-items-end">
                            <span class="badge rounded-pill bg-light text-primary border border-primary-subtle px-3 py-2 mb-2">
                                <i class="bi bi-box-seam me-1"></i> <?php echo $row['asset_count']; ?> Assets
                            </span>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=ROOM_<?php echo $row['room_id']; ?>" 
                                 style="width: 45px; cursor: pointer;" 
                                 class="border rounded p-1 bg-white shadow-sm"
                                 title="Click to Print Room QR"
                                 onclick="viewRoomQR(<?php echo $row['room_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['room_name'])); ?>')">
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?php echo $row['room_name']; ?></h5>
                    <p class="text-muted small mb-4" style="min-height: 40px;"><?php echo $row['description'] ?: 'No location description provided.'; ?></p>
                    
                    <div class="gps-box mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.65rem;">GPS Boundary</span>
                            <span class="badge bg-success bg-opacity-10 text-success small">Active</span>
                        </div>
                        <div class="gps-coord">
                            <i class="bi bi-crosshair me-1"></i> <?php echo number_format($row['latitude'], 6); ?>, <?php echo number_format($row['longitude'], 6); ?>
                        </div>
                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 d-flex justify-content-between">
                            <span class="small text-muted">Radius Allowed:</span>
                            <span class="small fw-bold text-dark"><?php echo $row['allowed_radius']; ?>m</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm w-100 py-2 border rounded-3" 
                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            <i class="bi bi-pencil me-1"></i> Edit Map
                        </button>
                        
                        <a href="?delete=<?php echo $row['room_id']; ?>" 
                           class="btn btn-outline-danger btn-sm px-3 rounded-3" 
                           onclick="return confirm('Are you sure? This will remove the geofence for this room.')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="roomQRModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="modal-header border-0 pb-0 justify-content-center position-relative">
                <h6 class="modal-title fw-bold">Room Entry QR</h6>
                <button type="button" class="btn-close position-absolute end-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-0">
                <div id="qrPrintArea">
                    <img id="largeRoomQR" src="" class="img-fluid border p-2 rounded mb-3" style="width: 200px;" alt="QR Code">
                    <h5 id="qrRoomName" class="fw-bold mb-1"></h5>
                    <p class="text-muted small mb-0">ID: <span id="qrRoomID"></span></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-4">
                <button class="btn btn-dark w-100" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Print Room Label
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
       <form action="" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg">
    <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-geo-fill text-success me-2"></i>Create New Geofence</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Room Name</label>
                    <input type="text" name="room_name" class="form-control" placeholder="e.g. Science Lab B" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Radius (Meters)</label>
                    <div class="input-group">
                        <input type="number" name="radius" class="form-control" value="20" min="5">
                        <span class="input-group-text">m</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. 2nd Floor">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Room Photo</label>
                    <input type="file" name="room_image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="col-md-7">
                <label class="form-label fw-bold small text-uppercase text-muted">Pinpoint Location</label>
                <div id="addMap" class="map-container"></div>
                
                <div class="input-group mb-2">
                    <span class="input-group-text bg-light">Lat</span>
                    <input type="text" name="latitude" id="lat_input" class="form-control form-control-gps" readonly required>
                    <span class="input-group-text bg-light">Lon</span>
                    <input type="text" name="longitude" id="lon_input" class="form-control form-control-gps" readonly required>
                </div>
                
                <button class="btn btn-outline-primary btn-sm w-100" type="button" onclick="captureGPSForMap('add')">
                    <i class="bi bi-crosshair"></i> Auto-Center to My Device Location
                </button>
            </div>
        </div>
    </div>
    <div class="modal-footer border-0 pt-0 pb-4 px-4">
        <button type="submit" name="add_room" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow">
            <i class="bi bi-plus-circle me-2"></i> Save Geofence Boundary
        </button>
    </div>
</form>
    </div>
</div>

<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg">
    <input type="hidden" name="room_id" id="edit_room_id">
    <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>Edit Geofence Map</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
         <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Room Name</label>
                    <input type="text" name="room_name" id="edit_room_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Radius (m)</label>
                    <input type="number" name="radius" id="edit_radius" class="form-control">
                </div>
                 <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                    <input type="text" name="description" id="edit_desc" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Update Photo</label>
                    <input type="file" name="room_image" class="form-control form-control-sm" accept="image/*">
                    <div class="form-text mt-1" style="font-size: 0.7rem;">Leave blank to keep current photo.</div>
                </div>
            </div>

            <div class="col-md-7">
                <label class="form-label fw-bold small text-uppercase text-muted">Drag Pin to Update</label>
                <div id="editMap" class="map-container"></div>
                
                <div class="input-group">
                    <span class="input-group-text bg-light">Lat</span>
                    <input type="text" name="latitude" id="edit_lat" class="form-control form-control-gps" readonly required>
                    <span class="input-group-text bg-light">Lon</span>
                    <input type="text" name="longitude" id="edit_lon" class="form-control form-control-gps" readonly required>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer border-0">
        <button type="submit" name="edit_room" class="btn btn-primary w-100 py-3 fw-bold shadow">
            <i class="bi bi-cloud-arrow-up-fill me-2"></i> Update Geofence
        </button>
    </div>
</form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// --- SWEETALERT NOTIFICATION LOGIC ---
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    // Remove the ?status parameter from the URL cleanly so it doesn't trigger on refresh
    const cleanUrl = () => {
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path:newUrl}, '', newUrl);
    };

    if (status) {
        let swalConfig = {};

        if (status === 'added') {
            swalConfig = { title: 'Room Added!', text: 'The new geofence has been registered.', icon: 'success' };
        } else if (status === 'updated') {
            swalConfig = { title: 'Room Updated!', text: 'Geofence boundaries updated successfully.', icon: 'success' };
        } else if (status === 'deleted') {
            swalConfig = { title: 'Room Deleted!', text: 'The geofence has been removed.', icon: 'success' };
        } else if (status === 'duplicate') {
            swalConfig = { title: 'Duplicate Name!', text: 'A room with this name already exists. Please choose a unique name.', icon: 'warning' };
        } else if (status === 'error') {
            swalConfig = { title: 'System Error', text: 'Something went wrong while saving.', icon: 'error' };
        } else if (status === 'error_has_assets') {
            swalConfig = { title: 'Cannot Delete', text: 'This room holds active assets. Please reassign them first.', icon: 'error' };
        } else if (status === 'error_has_logs') {
            swalConfig = { title: 'Cannot Delete', text: 'This room has tracking logs associated with it.', icon: 'error' };
        }

        if (swalConfig.title) {
            swalConfig.confirmButtonColor = '#4e73df';
            Swal.fire(swalConfig).then(cleanUrl);
        }
    }
});


// --- ROOM QR LOGIC ---
function viewRoomQR(id, name) {
    const qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=ROOM_" + id;
    document.getElementById('largeRoomQR').src = qrUrl;
    document.getElementById('qrRoomName').innerText = name;
    document.getElementById('qrRoomID').innerText = id;
    new bootstrap.Modal(document.getElementById('roomQRModal')).show();
}

// --- MAP VARIABLES & LOGIC ---
let addMap, addMarker;
let editMap, editMarker;
const defaultLat = 10.5233;
const defaultLon = 122.7964;

document.getElementById('addRoomModal').addEventListener('shown.bs.modal', function () {
    if (!addMap) {
        addMap = L.map('addMap').setView([defaultLat, defaultLon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(addMap);
        addMarker = L.marker([defaultLat, defaultLon], {draggable: true}).addTo(addMap);
        addMarker.on('dragend', function (e) {
            let pos = addMarker.getLatLng();
            document.getElementById('lat_input').value = pos.lat.toFixed(6);
            document.getElementById('lon_input').value = pos.lng.toFixed(6);
        });
        document.getElementById('lat_input').value = defaultLat.toFixed(6);
        document.getElementById('lon_input').value = defaultLon.toFixed(6);
    }
    addMap.invalidateSize();
});

function openEditModal(room) {
    document.getElementById('edit_room_id').value = room.room_id;
    document.getElementById('edit_room_name').value = room.room_name;
    document.getElementById('edit_lat').value = room.latitude;
    document.getElementById('edit_lon').value = room.longitude;
    document.getElementById('edit_radius').value = room.allowed_radius;
    document.getElementById('edit_desc').value = room.description;
    
    let lat = parseFloat(room.latitude) || defaultLat;
    let lon = parseFloat(room.longitude) || defaultLon;

    var editModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
    editModal.show();

    document.getElementById('editRoomModal').addEventListener('shown.bs.modal', function () {
        if (!editMap) {
            editMap = L.map('editMap').setView([lat, lon], 18);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(editMap);
            editMarker = L.marker([lat, lon], {draggable: true}).addTo(editMap);
            editMarker.on('dragend', function (e) {
                let pos = editMarker.getLatLng();
                document.getElementById('edit_lat').value = pos.lat.toFixed(6);
                document.getElementById('edit_lon').value = pos.lng.toFixed(6);
            });
        } else {
            editMap.setView([lat, lon], 18);
            editMarker.setLatLng([lat, lon]);
        }
        editMap.invalidateSize();
    }, { once: true });
}

function captureGPSForMap(type) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((pos) => {
            let lat = pos.coords.latitude;
            let lon = pos.coords.longitude;
            if(type === 'add' && addMap) {
                addMap.setView([lat, lon], 18);
                addMarker.setLatLng([lat, lon]);
                document.getElementById('lat_input').value = lat.toFixed(6);
                document.getElementById('lon_input').value = lon.toFixed(6);
            }
        }, null, { enableHighAccuracy: true });
    }
}
</script>
</body>
</html>