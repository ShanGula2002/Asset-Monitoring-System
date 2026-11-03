<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../admin/check_overdue.php';
createOverdueBorrowNotifications($conn);

$user_id = $_SESSION['user_id'];

// SQL Query: Pulls logs and dynamically accurately reports room tracking assignments
$query = "SELECT b.*, a.asset_name, a.asset_tag, r.room_name,
                                 CASE
                                         WHEN b.borrow_status IN ('active', 'overdue')
                                             AND b.expected_return_date <= CURRENT_TIMESTAMP THEN 'overdue'
                                         ELSE b.borrow_status
                                 END AS display_status
          FROM borrowing_logs b
          JOIN assets a ON b.asset_id = a.asset_id
          JOIN rooms r ON a.room_id = r.room_id
          WHERE b.user_id = ? 
          ORDER BY (b.borrow_status = 'active' OR b.borrow_status = 'overdue') DESC, b.expected_return_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrows | AssetApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .borrow-card { border: none; border-radius: 15px; border-left: 5px solid transparent; background: #fff; }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; font-weight: 600; }
        .overdue { border-left-color: #dc3545; }
        .active-borrow { border-left-color: #4e73df; }
        .returned { opacity: 0.7; border-left-color: #198754; background-color: #f8f9fc; }
        @media (max-width: 767.98px) {
            .flex-grow-1.p-3.p-md-4 { padding: 22px 16px 28px !important; }
            .flex-grow-1.p-3.p-md-4 > .container { max-width: none !important; padding: 0; }
            .flex-grow-1.p-3.p-md-4 > .container > .d-flex { align-items: flex-start !important; gap: 10px; }
            .borrow-card { border-radius: 13px; }
            .borrow-card .d-flex.justify-content-between { gap: 12px; }
            .borrow-card .border-top { flex-wrap: wrap; gap: 12px; }
            .borrow-card .border-top .btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-3 p-md-4">
        <div class="container" style="max-width: 800px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">My Borrowed Assets</h4>
                <span class="badge bg-white text-dark shadow-sm border p-2">Total: <?php echo $result->num_rows; ?></span>
            </div>

            <?php if ($result->num_rows == 0): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-box2 fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">No records found.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php while($row = $result->fetch_assoc()): 
                        $status = $row['display_status']; 
                        $is_active = ($status == 'active' || $status == 'overdue');
                        $card_class = ($status == 'returned') ? 'returned' : (($status == 'overdue') ? 'overdue' : 'active-borrow');
                    ?>
                    <div class="col-12">
                        <div class="card borrow-card shadow-sm <?php echo $card_class; ?> p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['asset_name']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-tag me-1"></i> <?php echo htmlspecialchars($row['asset_tag']); ?> | 
                                        <i class="bi bi-geo-alt me-1"></i> Location: <?php echo htmlspecialchars($row['room_name']); ?>
                                    </p>
                                </div>
                                
                                <span class="status-badge <?php 
                                    if($status == 'active') echo 'bg-primary bg-opacity-10 text-primary';
                                    elseif($status == 'overdue') echo 'bg-danger text-white';
                                    else echo 'bg-success bg-opacity-10 text-success';
                                ?>">
                                    <?php echo strtoupper($status); ?>
                                </span>
                            </div>

                            <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <span class="text-muted"><?php echo ($status == 'returned') ? 'Returned on:' : 'Due:'; ?></span> 
                                    <span class="fw-bold <?php echo ($status == 'overdue') ? 'text-danger' : ''; ?>">
                                        <?php 
                                            $date = ($status == 'returned') ? $row['actual_return_date'] : $row['expected_return_date'];
                                            echo date('M d, h:i A', strtotime($date)); 
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if($is_active): ?>
                                    <button onclick="initiateReturn(<?php echo $row['borrow_id']; ?>, '<?php echo htmlspecialchars($row['asset_tag'], ENT_QUOTES); ?>')" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                        Return Item
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function initiateReturn(borrowId, assetTag) {
    Swal.fire({
        title: 'Confirm Return',
        text: "Are you sure you want to return Asset: " + assetTag + "?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Return it!',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const params = new URLSearchParams();
            params.append('borrow_id', borrowId);

            return fetch('process_return.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response error or file not found.');
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (result.value.status === 'success') {
                Swal.fire('Returned!', result.value.message, 'success')
                .then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', result.value.message, 'error');
            }
        }
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>