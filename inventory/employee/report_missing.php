<?php
session_start();
require '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['asset_id']) && isset($_POST['room_id'])) {
    $asset_id = (int)$_POST['asset_id'];
    $room_id = (int)$_POST['room_id'];
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['full_name'] ?? "An employee";

    // 1. Get asset name for the message
    $stmt = $conn->prepare("SELECT asset_name, asset_tag FROM assets WHERE asset_id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if ($asset) {
        $msg = "MISSING ALERT: " . $user_name . " reported that '" . $asset['asset_name'] . "' (" . $asset['asset_tag'] . ") is missing from its assigned room.";
        
        // 2. Insert into notifications (using the Option 2 format we created)
        $notif = $conn->prepare("INSERT INTO notifications (user_id, room_id, message, type, created_at) VALUES (?, ?, ?, 'security_alert', NOW())");
        $notif->bind_param("iis", $user_id, $room_id, $msg);
        
        if ($notif->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to log notification']);
        }
    }
}