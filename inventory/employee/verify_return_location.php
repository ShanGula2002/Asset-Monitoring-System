<?php
require '../db_connect.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get data from the POST request
$borrow_id = isset($_POST['borrow_id']) ? $_POST['borrow_id'] : null;
$scanned_room_id = isset($_POST['room_id']) ? $_POST['room_id'] : null;

if (!$borrow_id || !$scanned_room_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing borrow ID or Room QR data.']);
    exit();
}

// 1. Fetch the room details currently linked to this asset via the borrow log
// We join borrowing_logs -> assets -> rooms to see where it MUST be returned
$query = "SELECT r.room_id, r.room_name 
          FROM borrowing_logs b
          JOIN assets a ON b.asset_id = a.asset_id
          JOIN rooms r ON a.room_id = r.room_id
          WHERE b.borrow_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $borrow_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_data = $result->fetch_assoc();

if (!$assigned_data) {
    echo json_encode(['status' => 'error', 'message' => 'Could not find the assigned room for this item.']);
    exit();
}

// 2. COMPARE: Does the ID from the Scanned QR match the Database ID?
if ($scanned_room_id == $assigned_data['room_id']) {
    // Success: They are at the right room
    echo json_encode(['status' => 'success']);
} else {
    // Failure: They scanned the wrong room's QR code
    echo json_encode([
        'status' => 'error', 
        'message' => 'Wrong Location! This item must be returned to: ' . $assigned_data['room_name']
    ]);
}
?>