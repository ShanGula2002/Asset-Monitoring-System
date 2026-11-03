<?php
require '../db_connect.php';

header('Content-Type: application/json');

$tag = $_GET['tag'] ?? '';

if (empty($tag)) {
    echo json_encode(['status' => 'error', 'message' => 'No tag provided']);
    exit();
}

// 1. Fetch asset details
$stmt = $conn->prepare("SELECT asset_name, asset_tag, is_available, room_id FROM assets WHERE asset_tag = ?");
$stmt->bind_param("s", $tag);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    // 2. Logic Check: Adjust this if your DB uses "Available" (string) instead of 1 (int)
    // If your column 'is_available' is a TINYINT (0 or 1), keep it as is.
    // If it's a VARCHAR, use: $available = ($res['is_available'] == 'Available');
    $available = (int)$res['is_available'] === 1;

    echo json_encode([
        'status' => 'success',
        'asset_name' => $res['asset_name'],
        'asset_tag' => $res['asset_tag'],
        'is_available' => $available, 
        'room_id' => $res['room_id']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Asset not found']);
}