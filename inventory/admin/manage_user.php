<?php
session_start();
require '../db_connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$status = '';

if (isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';

    if ($full_name === '' || $username === '' || $password === '' || !in_array($role, ['admin', 'employee'], true)) {
        $status = 'invalid';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $username, $hashed_password, $role, $full_name);
            $stmt->execute();
            header('Location: manage_user.php?status=added');
            exit();
        } catch (mysqli_sql_exception $e) {
            $status = $e->getCode() === 1062 ? 'duplicate' : 'error';
        }
    }
}

if (isset($_POST['edit_user'])) {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';

    if ($user_id < 1 || $full_name === '' || $username === '' || !in_array($role, ['admin', 'employee'], true)) {
        $status = 'invalid';
    } elseif ($user_id === (int) $_SESSION['user_id'] && $role !== 'admin') {
        $status = 'self_role';
    } else {
        try {
            if ($password !== '') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, role = ?, password = ? WHERE user_id = ?');
                $stmt->bind_param('ssssi', $username, $full_name, $role, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, role = ? WHERE user_id = ?');
                $stmt->bind_param('sssi', $username, $full_name, $role, $user_id);
            }
            $stmt->execute();
            header('Location: manage_user.php?status=updated');
            exit();
        } catch (mysqli_sql_exception $e) {
            $status = $e->getCode() === 1062 ? 'duplicate' : 'error';
        }
    }
}

if (isset($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];

    if ($user_id === (int) $_SESSION['user_id']) {
        $status = 'self_delete';
    } else {
        $stmt = $conn->prepare('SELECT (SELECT COUNT(*) FROM borrowing_logs WHERE user_id = ?) + (SELECT COUNT(*) FROM tracking_logs WHERE user_id = ?) AS activity_count');
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $activity_count = (int) $stmt->get_result()->fetch_assoc()['activity_count'];

        if ($activity_count > 0) {
            $status = 'has_activity';
        } else {
            $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            header('Location: manage_user.php?status=deleted');
            exit();
        }
    }
}

$users = $conn->query('SELECT user_id, username, role, full_name FROM users ORDER BY full_name, username');
$status = $_GET['status'] ?? $status;
$status_messages = [
    'added' => ['success', 'User created successfully.'],
    'updated' => ['success', 'User updated successfully.'],
    'deleted' => ['success', 'User deleted successfully.'],
    'duplicate' => ['error', 'That username is already in use.'],
    'has_activity' => ['error', 'This user cannot be deleted because they have borrowing or tracking history.'],
    'self_delete' => ['error', 'You cannot delete your own administrator account.'],
    'self_role' => ['error', 'You cannot remove administrator access from your own account.'],
    'invalid' => ['error', 'Please complete all required fields with valid values.'],
    'error' => ['error', 'The user could not be saved. Please try again.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .users-card { border: 0; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,.05); overflow: hidden; }
        .table thead th { background: #f8f9fc; color: #4e73df; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; padding: 18px; border: 0; }
        .table tbody td { padding: 17px 18px; border-color: #f1f3f7; }
        .avatar { width: 38px; height: 38px; border-radius: 12px; background: #e0e9ff; color: #4e73df; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
        .modal-content { border: 0; border-radius: 20px; }
        .form-control, .form-select { padding: 11px 13px; border-radius: 9px; }
        @media (max-width: 992px) { .content { margin-left: 0; width: 100%; padding: 76px 18px 24px; } }
        @media (max-width: 768px) { .content > .d-flex:first-child { align-items: flex-start !important; flex-direction: column; gap: 16px; } .content > .d-flex:first-child .btn { width: 100%; } .users-card .table { min-width: 680px; } }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">User Management</h2>
            <p class="text-muted mb-0">Create and manage system access for employees and administrators.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAddModal()">
            <i class="bi bi-person-plus-fill me-2"></i> Add New User
        </button>
    </div>

    <div class="card users-card">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="fw-bold mb-1">System Users</h5>
            <p class="text-muted small mb-0">Review account roles and update access details.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>User</th><th>Username</th><th>Role</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><span class="avatar me-2"><?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?></span><strong><?php echo htmlspecialchars($user['full_name'] ?: 'Unnamed user'); ?></strong></td>
                        <td class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></td>
                        <td><span class="badge <?php echo $user['role'] === 'admin' ? 'text-bg-primary' : 'text-bg-light text-dark border'; ?>"><?php echo $user['role'] === 'admin' ? 'Administrator' : 'Employee'; ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Edit user" data-bs-toggle="modal" data-bs-target="#userModal" onclick='openEditModal(<?php echo json_encode($user); ?>)'><i class="bi bi-pencil"></i></button>
                            <?php if ((int) $user['user_id'] !== (int) $_SESSION['user_id']): ?>
                                <a href="manage_user.php?delete=<?php echo (int) $user['user_id']; ?>" class="btn btn-outline-danger btn-sm rounded-2" title="Delete user" onclick="return confirm('Delete this user? Users with activity cannot be deleted.')"><i class="bi bi-trash"></i></a>
                            <?php else: ?>
                                <button class="btn btn-light btn-sm rounded-2 text-muted" title="Your account cannot be deleted" disabled><i class="bi bi-shield-lock"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <input type="hidden" name="user_id" id="user_id">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold" id="modalTitle">Add New User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-0">
                <div class="mb-3"><label class="form-label fw-semibold">Full Name</label><input type="text" name="full_name" id="full_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-semibold">Username</label><input type="text" name="username" id="username" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-semibold">Password <span class="text-muted small" id="passwordHint"></span></label><input type="password" name="password" id="password" class="form-control"><div class="form-text" id="passwordHelp">Required for new users.</div></div>
                <div class="mb-2"><label class="form-label fw-semibold">Role</label><select name="role" id="role" class="form-select"><option value="employee">Employee (Scanner)</option><option value="admin">Administrator (Manager)</option></select></div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_user" id="submitButton" class="btn btn-primary">Create User</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('submitButton').textContent = 'Create User';
        document.getElementById('submitButton').name = 'add_user';
        document.getElementById('user_id').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passwordHelp').textContent = 'Required for new users.';
        document.getElementById('role').value = 'employee';
    }
    function openEditModal(user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('submitButton').textContent = 'Save Changes';
        document.getElementById('submitButton').name = 'edit_user';
        document.getElementById('user_id').value = user.user_id;
        document.getElementById('full_name').value = user.full_name || '';
        document.getElementById('username').value = user.username;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('passwordHelp').textContent = 'Leave blank to keep the current password.';
        document.getElementById('role').value = user.role;
    }
</script>
<?php if (isset($status_messages[$status])): [$type, $message] = $status_messages[$status]; ?>
<script>Swal.fire({icon: '<?php echo $type === 'success' ? 'success' : 'error'; ?>', text: <?php echo json_encode($message); ?>, confirmButtonColor: '#4e73df'});</script>
<?php endif; ?>
</body>
</html>
