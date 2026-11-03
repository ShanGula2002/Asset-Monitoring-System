<?php
session_start();
require '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $asset_tag = $_POST['asset_tag'];
    $scanned_room = $_POST['scanned_room'];
    $expected_room = $_POST['expected_room'];

    $msg = "LOCATION MISMATCH: User ID $user_id scanned Room ID $scanned_room for Asset $asset_tag, but it should be in Room ID $expected_room.";

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'security_alert', NOW())");
    $stmt->bind_param("is", $user_id, $msg);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>