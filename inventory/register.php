<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Asset Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .register-card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card register-card p-4">
                <h4 class="text-center fw-bold mb-4">Create Account</h4>
                
                <form action="register_process.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Assign Role</label>
                        <select name="role" class="form-select">
                            <option value="employee">Employee (Scanner)</option>
                            <option value="admin">Administrator (Manager)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Register User</button>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none small text-muted">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>