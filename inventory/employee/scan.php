<?php
session_start();
require '../db_connect.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

// Fetch all rooms for the dropdown
$rooms = $conn->query("SELECT room_id, room_name FROM rooms ORDER BY room_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Scanner | School Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        body { 
            background-color: #f8f9fc; 
            font-family: 'Inter', sans-serif; 
            color: #333;
        }
        .scan-wrapper {
            max-width: 600px;
            margin: 20px auto;
            padding: 15px;
        }
        .header-section {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-radius: 20px;
            padding: 30px 20px;
            color: white;
            margin-bottom: -40px;
            box-shadow: 0 10px 20px rgba(78, 115, 223, 0.2);
        }
        .main-card {
            background: white;
            border-radius: 25px;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: none;
        }
        #reader { 
            width: 100%; 
            border-radius: 20px; 
            overflow: hidden; 
            border: none !important;
            background: #000;
        }
        #reader__dashboard_section_csr button {
            background-color: #4e73df !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 10px 20px !important;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .step-badge {
            width: 30px;
            height: 30px;
            background: #4e73df;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        #loading-overlay { display: none; }
        
        /* Mobile Specific Tweaks */
        @media (max-width: 576px) {
            .header-section { border-radius: 0 0 30px 30px; margin: -20px -15px 20px -15px; }
            .main-card { border-radius: 20px; }
        }

        @media (max-width: 767.98px) {
            .scan-wrapper { max-width: none; margin: 0 auto; padding: 0; }
            .header-section { padding: 24px 18px; }
            .main-card { padding: 18px; }
            #reader { min-height: 250px; }
            #reader__dashboard_section_csr button { width: 100%; max-width: 280px; }
        }
    </style>
</head>
<body>

<div class="scan-wrapper">
    <div class="header-section text-center">
        <h2 class="fw-bold mb-1">Asset Scanner</h2>
        <p class="opacity-75 mb-0">Hello, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>! Ready to move assets?</p>
    </div>

    <div class="main-card">
        <div class="mb-4">
            <label class="form-label d-flex align-items-center fw-600 mb-3">
                <span class="step-badge">1</span> Destination Room
            </label>
            <select id="location_id" class="form-select form-select-lg shadow-sm border-0 bg-light" style="border-radius: 12px;">
                <option value="">-- Tap to Select Room --</option>
                <?php while($row = $rooms->fetch_assoc()): ?>
                    <option value="<?php echo $row['room_id']; ?>"><?php echo $row['room_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-2">
            <label class="form-label d-flex align-items-center fw-600 mb-3">
                <span class="step-badge">2</span> Scan QR Code
            </label>
            <div id="reader"></div>
        </div>

        <div id="loading-overlay" class="text-center py-4">
            <div class="spinner-grow text-primary" role="status"></div>
            <div class="mt-3 fw-bold text-dark">Updating Asset Records...</div>
            <p class="text-muted small">Please wait a moment.</p>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="../employee/dashboard.php" class="btn btn-link text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        <span class="mx-2 text-muted">|</span>
        <a href="logout.php" class="text-danger text-decoration-none fw-bold small">Logout</a>
    </div>
</div>

<script>
function onScanSuccess(decodedText, decodedResult) {
    const roomId = document.getElementById('location_id').value;
    const loader = document.getElementById('loading-overlay');
    const readerDiv = document.getElementById('reader');

    if (!roomId) {
        Swal.fire({
            icon: 'warning',
            title: 'Wait!',
            text: 'You must select a destination room before scanning.',
            confirmButtonColor: '#4e73df'
        });
        return;
    }

    // Hide scanner UI and show processing state
    html5QrcodeScanner.clear();
    readerDiv.style.display = 'none';
    loader.style.display = 'block';

    // POST data to server immediately (NO GPS WAIT)
    const formData = new URLSearchParams();
    formData.append('asset_tag', decodedText);
    formData.append('room_id', roomId);

    fetch('process_scan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(response => response.json()) 
    .then(data => {
        loader.style.display = 'none';
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Transfer Successful',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => { location.reload(); });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Scan Error',
                text: data.message,
                confirmButtonColor: '#4e73df'
            }).then(() => { location.reload(); });
        }
    })
    .catch(error => {
        loader.style.display = 'none';
        Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Server unreachable.' });
    });
}

// Initializing the Scanner
var html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
    fps: 15, 
    qrbox: {width: 250, height: 250},
    aspectRatio: 1.0,
    rememberLastUsedCamera: true
});
html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>