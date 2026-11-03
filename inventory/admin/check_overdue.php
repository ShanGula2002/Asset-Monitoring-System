<?php
function createOverdueBorrowNotifications(mysqli $conn): int
{
    $conn->begin_transaction();

    try {
        $overdue_stmt = $conn->prepare("SELECT b.borrow_id, b.user_id, b.expected_return_date, a.asset_name, u.full_name
                                        FROM borrowing_logs b
                                        JOIN assets a ON b.asset_id = a.asset_id
                                        JOIN users u ON b.user_id = u.user_id
                                        WHERE b.expected_return_date <= CURRENT_TIMESTAMP
                                          AND b.actual_return_date IS NULL
                                          AND b.borrow_status = 'active'
                                        FOR UPDATE");
        $overdue_stmt->execute();
        $overdue_rows = $overdue_stmt->get_result();

        $update_stmt = $conn->prepare("UPDATE borrowing_logs
                                       SET borrow_status = 'overdue'
                                                                             WHERE borrow_id = ?
                                                                                 AND borrow_status = 'active'
                                                                                 AND actual_return_date IS NULL
                                                                                 AND expected_return_date <= CURRENT_TIMESTAMP");
        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at)
                                             VALUES (?, ?, 'overdue_alert', 0, NOW())");

        $notification_count = 0;
        while ($borrow = $overdue_rows->fetch_assoc()) {
            $borrow_id = (int) $borrow['borrow_id'];
            $update_stmt->bind_param('i', $borrow_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows !== 1) {
                continue;
            }

            $user_id = (int) $borrow['user_id'];
            $due_date = date('M d, Y g:i A', strtotime($borrow['expected_return_date']));
            $message = "OVERDUE ALERT: {$borrow['full_name']} has not returned {$borrow['asset_name']}. It was due on {$due_date}.";
            $notification_stmt->bind_param('is', $user_id, $message);
            $notification_stmt->execute();
            $notification_count++;
        }

        $conn->commit();
        return $notification_count;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
?>