
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Asset Tracking | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #102a35; --teal: #0d8f91; --lime: #c9f27c; --muted: #627780; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: radial-gradient(circle at 15% 15%, rgba(201,242,124,.3), transparent 25%), linear-gradient(135deg, #e8f2ec, #f8faf8);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        h1, h4, .brand-name { font-family: 'Space Grotesk', sans-serif; }
        .login-layout { min-height: 620px; background: #fff; box-shadow: 18px 20px 0 rgba(16,42,53,.1); }
        .login-aside { background: var(--ink); color: #fff; position: relative; overflow: hidden; }
        .login-aside:after { content: ''; position: absolute; width: 220px; height: 220px; border: 1px solid rgba(201,242,124,.45); border-radius: 50%; right: -90px; bottom: -70px; box-shadow: 0 0 0 24px rgba(201,242,124,.08), 0 0 0 48px rgba(201,242,124,.05); }
        .aside-mark { width: 50px; height: 50px; object-fit: contain; background: #fff; padding: 7px; }
        .aside-kicker { color: var(--lime); font-size: .72rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; }
        .login-form-panel { padding: clamp(28px, 5vw, 64px); }
        .login-card {
            border: none;
            border-radius: 0;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #0d8f91;
            border: none;
            padding: 13px;
            border-radius: 4px;
        }
        .school-logo {
            width: 64px;
            margin-bottom: 20px;
        }
        .form-control, .form-select { border-color: #d7e4df; border-radius: 4px; padding: 12px 13px; }
        .form-control:focus, .form-select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,143,145,.13); }
        .form-label { color: var(--ink); font-size: .86rem; font-weight: 600; }
        @media (max-width: 767.98px) {
            body { align-items: flex-start; padding: 24px 0; }
            .login-layout { min-height: 0; box-shadow: 10px 12px 0 rgba(16,42,53,.08); }
            .login-aside { padding: 28px !important; }
            .login-aside h1 { font-size: 1.8rem; }
            .login-form-panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="row g-0 login-layout">
                <div class="col-md-5 login-aside p-5 d-flex flex-column justify-content-between">
                    <div>
                        <img src="logo.png" alt="Logo" class="aside-mark mb-4">
                        <div class="aside-kicker mb-3">AssetTracker</div>
                        <h1 class="fw-bold mb-3">School Asset Tracking &amp; Geofencing System</h1>
                        <p class="mb-0" style="color: #b7ceca;">Efficiently monitor, locate, and manage school inventory in real time.</p>
                    </div>
                    <small class="mt-5" style="color: #8ea9a4;">Secure access for every campus role.</small>
                </div>
                <div class="col-md-7 login-form-panel">
                    <div class="mb-4">
                        <img src="logo.png" alt="Logo" class="school-logo d-md-none">
                        <h4 class="fw-bold mb-1">Log-in</h4>
                        <p class="text-muted mb-0">Enter your details to access the system.</p>
                    </div>

                <form action="login_process.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Login As</label>
                        <select name="role" class="form-select">
                            <option value="employee">Employee (Scanner)</option>
                            <option value="admin">Administrator (Manager)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold">Login to System</button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">&copy; 2026 Asset Monitoring & Tracking System with Geofencing</small>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>