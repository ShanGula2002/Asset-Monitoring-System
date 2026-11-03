<?php
session_start();
require '../db_connect.php';

header('Content-Type: application/json');

// 1. Session and Validation check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
    exit();
}

// UPDATED: Expecting origin_room_id and destination_room_id from the new multi-step form
if (empty($_POST['asset_tag']) || empty($_POST['origin_room_id']) || empty($_POST['destination_room_id']) || empty($_POST['return_date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required information.']);
    exit();
}

$tag = $_POST['asset_tag'];
$origin_room_id = (int)$_POST['origin_room_id']; 
$destination_room_id = (int)$_POST['destination_room_id']; 
$return_date = $_POST['return_date'];
$user_id = $_SESSION['user_id'];

// 2. Get Asset Details (Joining with rooms to get the human-readable name)
$stmt = $conn->prepare("
    SELECT a.asset_id, a.asset_name, a.room_id as expected_room_id, a.is_available, r.room_name 
    FROM assets a 
    JOIN rooms r ON a.room_id = r.room_id 
    WHERE a.asset_tag = ?
");
$stmt->bind_param("s", $tag);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    echo json_encode(['status' => 'error', 'message' => 'Asset not found.']);
    exit();
}

// 3. Security Check: Location Verification against the physically scanned ORIGIN room
if ($asset['expected_room_id'] != $origin_room_id) {
    $asset_name = $asset['asset_name'];
    $expected_room_name = $asset['room_name'];
    
    // Log the security alert with the Room Name for the Admin to understand easily
    $alert_msg = "SECURITY ALERT: User attempted to borrow '$asset_name' ($tag) in Room ID $origin_room_id, but it belongs in '$expected_room_name'.";
    
    // Insert notification linked to the origin room where the mismatch occurred
    $notif = $conn->prepare("INSERT INTO notifications (user_id, room_id, message, type, created_at) VALUES (?, ?, ?, 'security_alert', NOW())");
    $notif->bind_param("iis", $user_id, $origin_room_id, $alert_msg);
    $notif->execute();

    echo json_encode([
        'status' => 'error', 
        'message' => "Location mismatch! This item belongs in $expected_room_name. Incident reported."
    ]);
    exit();
}

// 4. Availability Check
if ($asset['is_available'] == 0) {
    echo json_encode(['status' => 'error', 'message' => 'This item is already borrowed or unavailable.']);
    exit();
}

$asset_id = $asset['asset_id'];
$conn->begin_transaction();

try {
    // 5. Save the room where the asset is being borrowed to
    $log = $conn->prepare("INSERT INTO borrowing_logs (asset_id, user_id, borrowed_room_id, borrow_date, expected_return_date, actual_return_date, borrow_status) 
                           VALUES (?, ?, ?, NOW(), ?, NULL, 'active')");
    $log->bind_param("iiis", $asset_id, $user_id, $destination_room_id, $return_date);
    $log->execute();

    // 6. Update Asset Status & Destination Room (Moves the live asset tracking to its new home)
    $update = $conn->prepare("UPDATE assets SET is_available = 0, room_id = ? WHERE asset_id = ?");
    $update->bind_param("ii", $destination_room_id, $asset_id);
    $update->execute();

    // 7. Tracking Log (Records the physical scan event at the origin room before it leaves)
    $track = $conn->prepare("INSERT INTO tracking_logs (asset_id, user_id, room_id, scan_time) VALUES (?, ?, ?, NOW())");
    $track->bind_param("iii", $asset_id, $user_id, $origin_room_id);
    $track->execute();

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error during transaction: ' . $e->getMessage()]);
}
?>