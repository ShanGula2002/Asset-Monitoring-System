<?php

$isPaid = false;
$dueDate = '2026-08-10 23:59:59';

$currentTimestamp = time();
$dueTimestamp = strtotime($dueDate);

$isExpiredOrUnpaid = (!$isPaid || $currentTimestamp >= $dueTimestamp);

if ($isExpiredOrUnpaid):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Access Suspended | Payment Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lockout-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
        }
        .countdown-box {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e3e6f0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card lockout-card p-4 text-center">
                <div class="mb-3 text-danger">
                    <div style="width: 70px; height: 70px; background: rgba(220, 53, 69, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-2">System Access Suspended</h3>
                <p class="text-muted small mb-4">
                    Access to this Inventory Management System has been temporarily disabled due to pending account settlement or expiration of the service agreement.
                </p>

                <!-- Live Countdown Timer Component -->
                <div class="countdown-box mb-4">
                    <small class="text-uppercase fw-bold text-muted d-block mb-2">Settlement Deadline Countdown</small>
                    <div id="countdown" class="fs-5 fw-bold text-danger font-monospace">Loading timer...</div>
                </div>

                <div class="alert alert-warning small text-start mb-4" role="alert">
                    <strong>Instructions for Administrator:</strong> Please contact your system developer or service provider to complete payment verification and restore full access to the database.
                </div>

                <div class="text-muted small">
                    &copy; 2026 Asset Monitoring & Tracking System
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Set the target date for the countdown (August 10, 2026 23:59:59)
    const targetDate = new Date("Aug 10, 2026 23:59:59").getTime();

    const timer = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const countdownElement = document.getElementById("countdown");

        if (distance < 0) {
            clearInterval(timer);
            countdownElement.innerHTML = "DEADLINE REACHED — LOCKED";
        } else {
            countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
    }, 1000);
</script>
</body>
</html>
<?php 
    exit(); // Stops login.php from rendering anything else underneath!
endif; 
?>