<?php
require '../db_connect.php';

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) exit(json_encode([]));

$searchTerm = "%$q%";

// Query joins assets with rooms and also tries to find the current active borrower
$sql = "SELECT a.*, r.room_name, r.latitude AS room_latitude, r.longitude AS room_longitude,
           r.allowed_radius, u.full_name as borrower_name
        FROM assets a
    LEFT JOIN rooms r ON a.room_id = r.room_id
        LEFT JOIN borrowing_logs b ON a.asset_id = b.asset_id AND b.borrow_status = 'active'
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE a.asset_name LIKE ? 
        OR a.asset_tag LIKE ? 
        OR a.brand LIKE ? 
        OR a.serial_number LIKE ?
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode($assets);