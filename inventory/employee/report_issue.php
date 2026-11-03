<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Issue | AssetApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background-color: #fff5f5; font-family: 'Inter', sans-serif; }
        .issue-card { border-radius: 20px; border: none; overflow: hidden; }
        #reader { background: #000; border: none !important; }
        .report-form { display: none; border-radius: 15px; background: #fff; padding: 25px; box-shadow: 0 10px 30px rgba(220, 53, 69, 0.1); border: 1px solid #fecaca; }
        .btn-danger-soft { background-color: #fee2e2; color: #dc3545; border: none; font-weight: 600; }
        .btn-danger-soft:hover { background-color: #dc3545; color: white; }
        @media (max-width: 767.98px) {
            .flex-grow-1.p-3.p-md-4 { padding: 22px 16px 28px !important; }
            .flex-grow-1.p-3.p-md-4 > div { max-width: none !important; }
            #reader { min-height: 250px; }
            .report-form { padding: 18px; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-3 p-md-4">
        <div style="max-width: 550px; margin: 0 auto;">
            
            <div class="mb-4 text-center">
                <div class="bg-danger bg-opacity-10 d-inline-block p-3 rounded-circle mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                </div>
                <h4 class="fw-bold">Report an Issue</h4>
                <p class="text-muted small">Scan the asset QR code to report damage or malfunctions.</p>
            </div>

            <div id="scanner-section" class="card issue-card shadow-sm mb-4">
                <div id="reader"></div>
            </div>

            <div id="form-section" class="report-form shadow-lg">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="me-3">
                        <i class="bi bi-box-seam fs-2 text-danger"></i>
                    </div>
                    <div>
                        <h6 id="display-name" class="fw-bold mb-0">---</h6>
                        <small id="display-tag" class="text-muted"></small>
                    </div>
                </div>

                <form id="issueForm">
                    <input type="hidden" name="asset_tag" id="hidden_tag_issue">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">ISSUE TYPE</label>
                        <select name="status" class="form-select border-2" required>
                            <option value="need repair">🔧 Needs Repair (Still Functional)</option>
                            <option value="damage">❌ Damaged (Not Working)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">DESCRIBE THE PROBLEM</label>
                        <textarea name="note" class="form-control border-2" rows="3" placeholder="e.g. Screen is flickering, missing power cable..." required></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill fw-bold">
                            Submit Report
                        </button>
                        <button type="button" onclick="location.reload()" class="btn btn-link text-muted">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
function onScanSuccessIssue(decodedText) {
    html5QrcodeScannerIssue.clear();
    
    // Check if asset exists
    fetch(`get_asset_info.php?tag=${encodeURIComponent(decodedText)}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('display-name').innerText = data.asset_name;
            document.getElementById('display-tag').innerText = "Asset Tag: " + data.asset_tag;
            document.getElementById('hidden_tag_issue').value = data.asset_tag;
            
            document.getElementById('scanner-section').style.display = 'none';
            document.getElementById('form-section').style.display = 'block';
        } else {
            Swal.fire('Error', 'QR code not found.', 'error').then(() => location.reload());
        }
    });
}

document.getElementById('issueForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('process_issue.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Reported',
                text: 'Maintenance has been notified.',
                confirmButtonColor: '#dc3545'
            }).then(() => { window.location.href = 'dashboard.php'; });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});

let html5QrcodeScannerIssue = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
html5QrcodeScannerIssue.render(onScanSuccessIssue);
</script>

</body>
</html>