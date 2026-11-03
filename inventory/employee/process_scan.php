<?php
// Start output buffering to catch stray characters
ob_start();
session_start();
require '../db_connect.php';

// Force JSON header
header('Content-Type: application/json');

// Clear any accidental output (spaces/warnings) from db_connect.php
ob_clean();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $asset_tag = $_POST['asset_tag'] ?? '';
    $room_id   = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0; 
    $user_id   = $_SESSION['user_id'];

    // 1. FETCH ROOM (Just to make sure it's a valid room)
    $room_stmt = $conn->prepare("SELECT room_name FROM rooms WHERE room_id = ?");
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();
    $room_data = $room_stmt->get_result()->fetch_assoc();

    if (!$room_data) {
        echo json_encode(['status' => 'error', 'message' => 'Room not found.']);
        exit();
    }

    // ==========================================
    // GEOFENCE CHECK HAS BEEN COMPLETELY REMOVED
    // ==========================================

    // 2. UPDATE ASSET & LOG
    $stmt = $conn->prepare("SELECT asset_id FROM assets WHERE asset_tag = ?");
    $stmt->bind_param("s", $asset_tag);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if ($asset) {
        $asset_id = $asset['asset_id'];

        // Update main table
        $update = $conn->prepare("UPDATE assets SET room_id = ?, last_updated = NOW() WHERE asset_id = ?");
        $update->bind_param("ii", $room_id, $asset_id);
        
        if ($update->execute()) {
            // Log history (Changed to 'Manual Transfer' since GPS isn't verified)
            $log = $conn->prepare("INSERT INTO tracking_logs (asset_id, room_id, user_id, status) VALUES (?, ?, ?, 'Manual Transfer')");
            $log->bind_param("iii", $asset_id, $room_id, $user_id);
            $log->execute();

            echo json_encode(['status' => 'success', 'message' => 'Asset moved successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB Update Failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'QR Code not found.']);
    }
}
// End buffering and flush
ob_end_flush();
?>