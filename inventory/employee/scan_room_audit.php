<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if we are in "List Mode" or "Scanner Mode"
$room_id = isset($_GET['room_id']) ? mysqli_real_escape_string($conn, $_GET['room_id']) : null;
$assets = null;

// Only run queries if we actually have a room_id
if ($room_id) {
    // Fetch Room Name
    $room_name = "Unknown Room";
    $rm_stmt = $conn->prepare("SELECT room_name FROM rooms WHERE room_id = ?");
    $rm_stmt->bind_param("i", $room_id);
    $rm_stmt->execute();
    $room_res = $rm_stmt->get_result()->fetch_assoc();
    if ($room_res) { $room_name = $room_res['room_name']; }

    // Fetch Assets
    $query = "SELECT a.asset_id, a.asset_tag, a.asset_name, a.is_available, a.status FROM assets a WHERE a.room_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $assets = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Audit | AssetApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .scanner-card { border-radius: 12px; border: 1px solid #d9dfe2; overflow: hidden; background: #151a1d; }
        #reader { width: 100%; aspect-ratio: 4 / 3; background: #151a1d; }
        #reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
        .camera-controls { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; background: #fff; }
        .camera-status { color: #66727d; font-size: 0.82rem; }
        .camera-switch { min-height: 44px; flex: 0 0 auto; }
        .asset-card { border-radius: 15px; border: none; }
        @media (max-width: 767.98px) {
            .container { max-width: none !important; padding: 0; }
            #reader { aspect-ratio: 1 / 1; }
            .camera-controls { align-items: flex-start; flex-direction: column; }
            .camera-switch { width: 100%; }
            .asset-card { border-radius: 13px; }
            .asset-card .d-flex { gap: 12px; }
            .asset-card .btn { flex: 0 0 auto; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-3 p-md-4">
    <div class="container py-4" style="max-width: 600px;">
    <?php if (!$room_id): ?>
        <div class="text-center mb-4">
            <h4 class="fw-bold">Scan Room QR</h4>
            <p class="text-muted small">Scan the code in the room to start the audit.</p>
        </div>
        <div class="card scanner-card shadow-sm mb-4">
            <div id="reader"></div>
            <div class="camera-controls">
                <div>
                    <div class="fw-semibold">Room camera</div>
                    <div id="camera-status" class="camera-status" aria-live="polite">Camera is off</div>
                </div>
                <button id="camera-switch" type="button" class="btn btn-dark camera-switch">
                    <i class="bi bi-camera-video me-2" aria-hidden="true"></i><span>Start back camera</span>
                </button>
            </div>
        </div>
        <script>
            const roomScanner = new Html5Qrcode("reader", {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                verbose: false
            });
            const cameraButton = document.getElementById('camera-switch');
            const cameraButtonLabel = cameraButton.querySelector('span');
            const cameraStatus = document.getElementById('camera-status');
            let usingBackCamera = true;
            let cameraRunning = false;
            let scanHandled = false;

            async function startRoomCamera() {
                cameraStatus.textContent = usingBackCamera ? 'Starting back camera…' : 'Starting front camera…';
                try {
                    await roomScanner.start(
                        { facingMode: usingBackCamera ? 'environment' : 'user' },
                        { fps: 10 },
                        onScanSuccess
                    );
                    cameraRunning = true;
                    cameraButtonLabel.textContent = usingBackCamera ? 'Switch to front camera' : 'Switch to back camera';
                    cameraStatus.textContent = usingBackCamera ? 'Back camera active' : 'Front camera active';
                    cameraButton.disabled = false;
                    return true;
                } catch (error) {
                    cameraRunning = false;
                    cameraButtonLabel.textContent = 'Start back camera';
                    cameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
                    cameraButton.disabled = false;
                    return false;
                }
            }

            async function toggleRoomCamera() {
                cameraButton.disabled = true;

                if (!cameraRunning) {
                    usingBackCamera = true;
                    await startRoomCamera();
                    return;
                }

                const previousCamera = usingBackCamera;
                try {
                    await roomScanner.stop();
                    cameraRunning = false;
                    usingBackCamera = !previousCamera;

                    if (!await startRoomCamera()) {
                        usingBackCamera = previousCamera;
                        if (await startRoomCamera()) {
                            cameraStatus.textContent = 'Could not switch cameras. Previous camera restored.';
                        }
                    }
                } catch (error) {
                    cameraRunning = false;
                    cameraButtonLabel.textContent = 'Start back camera';
                    cameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
                    cameraButton.disabled = false;
                }
            }

            cameraButton.addEventListener('click', toggleRoomCamera);

            async function onScanSuccess(decodedText) {
                if (scanHandled) return;
                scanHandled = true;
                cameraStatus.textContent = 'Room QR code found';
                if (cameraRunning) {
                    await roomScanner.stop();
                    cameraRunning = false;
                }
                const roomId = decodedText.startsWith('ROOM_') ? decodedText.split('_')[1] : decodedText;
                window.location.href = 'scan_room_audit.php?room_id=' + encodeURIComponent(roomId);
            }
        </script>

    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
            <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($room_name); ?></h3>
            <p class="text-muted small mb-0">Asset Inventory Checklist</p>
        </div>

        <?php if ($assets && $assets->num_rows > 0): ?>
            <div class="d-grid gap-3">
                <?php while($row = $assets->fetch_assoc()): ?>
                    <div class="card asset-card shadow-sm p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold d-block"><?php echo htmlspecialchars($row['asset_name']); ?></span>
                                <small class="text-muted"><?php echo htmlspecialchars($row['asset_tag']); ?></small>
                            </div>
                            <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="reportMissing(<?php echo $row['asset_id']; ?>, '<?php echo addslashes($row['asset_name']); ?>', <?php echo $room_id; ?>)">Missing</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No assets in this room.</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="scan_room_audit.php" class="btn btn-primary rounded-pill px-4">Scan Another Room</a>
        </div>
    <?php endif; ?>

</div>
</div>

<script>
function reportMissing(assetId, assetName, roomId) {
    Swal.fire({
        title: 'Confirm Missing',
        text: `Report ${assetName} as missing?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('report_missing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `asset_id=${assetId}&room_id=${roomId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') Swal.fire('Reported', '', 'success').then(() => location.reload());
            });
        }
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>