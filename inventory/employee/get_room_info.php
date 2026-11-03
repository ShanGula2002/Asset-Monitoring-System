<?php
// 1. Include the database connection
require '../db_connect.php';

// 2. Set the header so the browser knows this is JSON
header('Content-Type: application/json');

// 3. Check if ID is provided to prevent errors
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 4. Prepare and execute
    $stmt = $conn->prepare("SELECT room_name FROM rooms WHERE room_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success', 
            'room_name' => $row['room_name']
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Room not found'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No ID provided'
    ]);
}
?>