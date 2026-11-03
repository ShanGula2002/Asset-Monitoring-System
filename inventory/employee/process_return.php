<?php
header('Content-Type: application/json');
session_start();
require '../db_connect.php';

// Security check: Only logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session. Please log in again.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_id'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];

    // 1. Verify record exists, belongs to this user, and isn't already returned
    $check_stmt = $conn->prepare("
        SELECT b.asset_id, b.borrow_date 
        FROM borrowing_logs b
        WHERE b.borrow_id = ? AND b.user_id = ? AND b.borrow_status != 'returned'
    ");
    $check_stmt->bind_param("ii", $borrow_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $log = $result->fetch_assoc();

    if ($log) {
        $asset_id = $log['asset_id'];
        $borrow_date = $log['borrow_date'];

        // 2. Find the original home room by looking at the tracking log created at checkout
        $home_stmt = $conn->prepare("
            SELECT room_id FROM tracking_logs 
            WHERE asset_id = ? AND scan_time <= ? 
            ORDER BY scan_time DESC LIMIT 1
        ");
        $home_stmt->bind_param("is", $asset_id, $borrow_date);
        $home_stmt->execute();
        $home_record = $home_stmt->get_result()->fetch_assoc();

        if ($home_record) {
            $original_home_room = $home_record['room_id'];
        } else {
            // Fallback protection if no tracking log exists
            $fallback = $conn->query("SELECT room_id FROM assets WHERE asset_id = $asset_id")->fetch_assoc();
            $original_home_room = $fallback['room_id'];
        }

        // Begin transaction to ensure data integrity
        $conn->begin_transaction();

        try {
            // 3. Update the borrowing log status and set the return timestamp
            $updateLog = $conn->prepare("UPDATE borrowing_logs SET borrow_status = 'returned', actual_return_date = NOW() WHERE borrow_id = ?");
            $updateLog->bind_param("i", $borrow_id);
            if (!$updateLog->execute()) {
                throw new Exception("Failed to update borrowing log.");
            }

            // 4. RESET LOCATION: Set asset back to available AND restore its original home room ID
            $updateAsset = $conn->prepare("UPDATE assets SET is_available = 1, room_id = ? WHERE asset_id = ?");
            $updateAsset->bind_param("ii", $original_home_room, $asset_id);
            if (!$updateAsset->execute()) {
                throw new Exception("Failed to update asset availability and location reset.");
            }

            // 5. Record the return arrival event inside tracking_logs
            $track_stmt = $conn->prepare("INSERT INTO tracking_logs (asset_id, user_id, room_id, scan_time) VALUES (?, ?, ?, NOW())");
            $track_stmt->bind_param("iii", $asset_id, $user_id, $original_home_room);
            if (!$track_stmt->execute()) {
                throw new Exception("Failed to log return tracking history.");
            }

            // If everything is successful, commit changes
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Asset successfully returned and restored to its home room.']);

        } catch (Exception $e) {
            // If any step fails, roll back all changes
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Valid borrowing record not found or already returned.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing parameters.']);
}
?>