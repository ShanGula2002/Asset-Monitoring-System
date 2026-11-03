<?php
session_start();
require '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tag = $_POST['asset_tag'];
    $new_status = $_POST['status']; // 'need repair' or 'damage'
    $note = $_POST['note'];
    $user_id = $_SESSION['user_id'];

    // 1. Get Asset Info
    $stmt = $conn->prepare("SELECT asset_id FROM assets WHERE asset_tag = ?");
    $stmt->bind_param("s", $tag);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if ($asset) {
        $asset_id = $asset['asset_id'];

        // 2. Update Asset: Change status and make it unavailable for borrowing
        $update = $conn->prepare("UPDATE assets SET status = ?, is_available = 0 WHERE asset_id = ?");
        $update->bind_param("si", $new_status, $asset_id);

        // 3. Log the issue in tracking_logs (re-using your logs table)
        // Note: You might want to add a 'notes' column to tracking_logs if you haven't yet
        $log_status = "Issue Reported: " . $new_status;
        $log = $conn->prepare("INSERT INTO tracking_logs (asset_id, room_id, user_id, status) 
                               SELECT ?, room_id, ?, ? FROM assets WHERE asset_id = ?");
        $log->bind_param("iisi", $asset_id, $user_id, $log_status, $asset_id);

        if ($update->execute() && $log->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update asset status.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Asset not found.']);
    }
}