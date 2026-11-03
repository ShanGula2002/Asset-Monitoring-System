<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Asset Tracking & Geofencing System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d8f91;
            --primary-hover: #087174;
            --ink: #102a35;
            --muted: #627780;
            --paper: #f5f8f6;
            --line: #dbe7e3;
            --lime: #c9f27c;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, .navbar-brand { font-family: 'Space Grotesk', sans-serif; }

        /* Navbar */
        .navbar {
            background-color: rgba(245, 248, 246, .92);
            border-bottom: 1px solid var(--line);
            padding: 17px 0;
            backdrop-filter: blur(14px);
        }
        .navbar-brand img {
            width: 40px;
            height: auto;
            margin-right: 10px;
        }
        .nav-link {
            font-weight: 500;
            color: var(--muted);
            margin: 0 8px;
            font-size: .92rem;
        }
        .nav-link:hover {
            color: var(--primary-color);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 11px 22px;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 11px 22px;
            border-radius: 4px;
            font-weight: 600;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        /* Hero Section */
        .hero-section {
            background: radial-gradient(circle at 85% 18%, rgba(201, 242, 124, .32), transparent 25%), linear-gradient(135deg, #eef5f0 0%, #f5f8f6 62%, #dcebe5 100%);
            padding: 92px 0 104px;
            position: relative;
        }
        .hero-badge {
            background-color: rgba(13, 143, 145, .1);
            color: var(--primary-color);
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .hero-section h1 { font-size: clamp(2.8rem, 5vw, 5rem); line-height: .98; letter-spacing: -.055em; max-width: 680px; }
        .hero-section .lead { color: var(--muted) !important; max-width: 590px; line-height: 1.7; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .hero-visual { position: relative; padding: 14px; background: rgba(255,255,255,.65); border: 1px solid rgba(16,42,53,.12); box-shadow: 18px 22px 0 rgba(16,42,53,.08); }
        .hero-visual img { aspect-ratio: 4 / 3; object-fit: cover; filter: saturate(.82); }
        .hero-stat { position: absolute; right: -24px; bottom: 28px; padding: 16px 18px; background: var(--ink); color: white; box-shadow: 8px 8px 0 var(--lime); text-align: left; }
        .hero-stat strong { display: block; color: var(--lime); font: 700 1.45rem 'Space Grotesk', sans-serif; }
        .hero-stat span { color: #c4d7d4; font-size: .75rem; }

        /* Feature Cards */
        .feature-card {
            border: none;
            border-radius: 4px;
            border: 1px solid var(--line);
            box-shadow: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 8px 8px 0 rgba(13, 143, 145, .14);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            background-color: rgba(13, 143, 145, .1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 20px;
        }

        /* How It Works Step */
        .step-number {
            width: 45px;
            height: 45px;
            border-radius: 4px;
            background-color: var(--ink);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 15px;
        }

        /* Footer */
        footer {
            background-color: var(--ink);
            color: #a9c0bd;
            padding: 46px 0 22px;
        }
        footer a {
            color: #d7e6e1;
            text-decoration: none;
        }
        footer a:hover {
            color: var(--lime);
        }
        .bg-light { background-color: #e8f1ed !important; }
        @media (max-width: 991.98px) {
            .hero-section { padding: 70px 0 82px; }
            .hero-stat { right: 12px; }
        }
        @media (max-width: 575.98px) {
            .navbar { padding: 12px 0; }
            .navbar-brand img { width: 34px; }
            .navbar-collapse { padding-top: 14px; }
            .nav-link { margin: 4px 0; }
            .hero-section { padding: 52px 0 64px; }
            .hero-section h1 { font-size: 2.75rem; }
            .hero-actions .btn { width: 100%; }
            .hero-visual { margin: 12px 10px 0 0; box-shadow: 10px 12px 0 rgba(16,42,53,.08); }
            .hero-stat { right: -4px; bottom: 16px; }
            .feature-card { padding: 1.35rem !important; }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/40'">
                <span class="fw-bold text-dark fs-5">AssetTracker</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#geofencing">Geofencing</a></li>
                    <li class="nav-item"><a class="nav-link" href="#roles">System Roles</a></li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="login.php" class="btn btn-primary text-white">Login to System</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <span class="hero-badge"><i class="bi bi-geo-alt-fill me-1"></i> Smart Campus Technology</span>
                    <h1 class="display-5 fw-bold text-dark mb-3">Next-Gen Asset Tracking with Geofencing</h1>
                    <p class="lead text-muted mb-4">Efficiently monitor, locate, and manage school inventory in real time. Prevent loss and ensure accountability with location-based boundary.</p>
                    <div class="hero-actions">
                        <a href="login.php" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">Explore Features</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-visual">
                        <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80" alt="Asset Tracking Dashboard Preview" class="img-fluid rounded-3">
                        <div class="hero-stat"><strong>24/7</strong><span>Campus visibility</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Key System Features</h2>
                <p class="text-muted">Designed specifically to streamline campus inventory management and security.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-geo-fill"></i>
                        </div>
                        <h4 class="fw-bold fs-5">Geofence Security</h4>
                        <p class="text-muted mb-0">Define virtual boundaries around school premises and receive instant alerts when assets are tagged missing or borrowed</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="fw-bold fs-5">Rapid Scanner Integration</h4>
                        <p class="text-muted mb-0">Employees can scan QR codes or barcodes on equipment using mobile devices for quick audits and location tagging.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <h4 class="fw-bold fs-5">Real-time Analytics</h4>
                        <p class="text-muted mb-0">Administrators gain comprehensive insights into equipment status, maintenance histories, and current campus locations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How Geofencing Works -->
    <section id="geofencing" class="py-5 bg-light">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold">How Geofencing Protects Assets</h2>
                <p class="text-muted">A simple 3-step automated monitoring process.</p>
            </div>

            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="step-number">1</div>
                    <h5 class="fw-bold">Tag & Register</h5>
                    <p class="text-muted">School assets (laptops, projectors, lab gear) are assigned digital IDs and perimeter parameters.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-number">2</div>
                    <h5 class="fw-bold">Monitor Location</h5>
                    <p class="text-muted">The system continuously monitors and checks location updates against set Map boundaries.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-number">3</div>
                    <h5 class="fw-bold">Automated Alerts</h5>
                    <p class="text-muted">If an asset reported missing from its designated room or campus perimeter, an instant flag is raised on the admin panel.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Based Access -->
    <section id="roles" class="py-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Tailored Experience for Every User</h2>
                    <p class="text-muted">The system provides specialized workflows based on user authorization, guaranteeing security and efficiency across staff members.</p>
                    <a href="login.php" class="btn btn-primary mt-2">Proceed to Login</a>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="card mb-3 border-0 shadow-sm p-3 rounded-3">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon mb-0 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Employee (Scanner)</h5>
                                <p class="text-muted mb-0 small">Scan items on-the-go, perform routine inventory checks, and update asset conditions effortlessly.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm p-3 rounded-3">
                        <div class="d-flex align-items-center">
                            <div class="feature-icon mb-0 me-3">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Administrator (Manager)</h5>
                                <p class="text-muted mb-0 small">Configure geofence zones, manage user accounts, oversee audits, and view real-time missing reports.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <div class="mb-3">
                <a href="index.php" class="text-white fw-bold me-3">Home</a>
                <a href="#features" class="text-white me-3">Features</a>
                <a href="login.php" class="text-white">Login</a>
            </div>
            <small class="text-muted">&copy; 2026 Asset Monitoring & Tracking System with Geofencing. All rights reserved.</small>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>