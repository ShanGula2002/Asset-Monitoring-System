<?php
session_start();
require '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

// FIXED: Using your exact column 'room_id' from the rooms table
$rooms_query = $conn->query("SELECT room_id, room_name FROM rooms ORDER BY room_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Asset | AssetApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .scanner-card { border-radius: 12px; border: 1px solid #d9dfe2; overflow: hidden; background: #151a1d; }
        #reader, #room-reader { background: #151a1d; border: 0 !important; width: 100%; aspect-ratio: 4 / 3; }
        #reader video, #room-reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
        .camera-controls { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; background: #fff; }
        .camera-status { color: #66727d; font-size: 0.82rem; }
        .camera-switch { min-height: 44px; flex: 0 0 auto; }
        .step-container { display: none; border-radius: 15px; background: #fff; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e3e6f0; }
        #asset-scanner-wrapper { display: block; } 
        .step-indicator { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 800; color: #4e73df; margin-bottom: 10px; display: block; }
        .review-box { background-color: #f8f9fc; border: 1px dashed #d1d3e2; border-radius: 10px; padding: 15px; }

        @media (max-width: 767.98px) {
            .flex-grow-1.p-3.p-md-4 { padding: 22px 16px 28px !important; }
            .flex-grow-1.p-3.p-md-4 > div { max-width: none !important; }
            .step-container { padding: 18px; }
            #reader, #room-reader { aspect-ratio: 1 / 1; }
            .camera-controls { align-items: flex-start; flex-direction: column; }
            .camera-switch { width: 100%; }
            .modal-dialog { margin: 12px; }
            .review-box { padding: 13px; }
            .form-select-lg, .form-control-lg { font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-3 p-md-4">
        <div style="max-width: 500px; margin: 0 auto;">
            
            <div class="mb-4 text-center">
                <h4 class="fw-bold"><i class="bi bi-cart-plus me-2 text-primary"></i>Borrow Asset</h4>
                <p id="instruction-text" class="text-muted small">Step 1: Scan the Asset QR code.</p>
            </div>

            <div id="asset-scanner-wrapper" class="card scanner-card shadow-sm mb-4">
                <div id="reader"></div>
                <div class="camera-controls">
                    <div>
                        <div class="fw-semibold">Asset camera</div>
                        <div id="asset-camera-status" class="camera-status" aria-live="polite">Camera is off</div>
                    </div>
                    <button id="asset-camera-switch" type="button" class="btn btn-dark camera-switch">
                        <i class="bi bi-camera-video me-2" aria-hidden="true"></i><span>Start back camera</span>
                    </button>
                </div>
            </div>

            <div id="step2-container" class="step-container text-center mb-4">
                <span class="step-indicator">Step 2: Location Verification</span>
                <i class="bi bi-geo-alt-fill fs-1 text-primary mb-3 d-block"></i>
                <h5>Scan Current Room QR</h5>
                <p class="text-muted small">Scan the QR code where the asset is currently located.</p>
                <button class="btn btn-primary w-100 rounded-pill" onclick="startRoomScanner()">Open Room Scanner</button>
            </div>

            <div id="step3-container" class="step-container mb-4">
                <span class="step-indicator">Step 3: Destination & Duration</span>
                
                <label class="form-label fw-bold small">WHERE WILL YOU BRING THIS ASSET?</label>
                <select id="ui_destination_room" class="form-select form-select-lg mb-4">
                    <option value="">-- Select Destination Room --</option>
                    <?php 
                    if ($rooms_query && $rooms_query->num_rows > 0) {
                        while($room = $rooms_query->fetch_assoc()) {
                            echo "<option value='".$room['room_id']."'>".htmlspecialchars($room['room_name'])."</option>";
                        }
                    }
                    ?>
                </select>

                <label class="form-label fw-bold small">WHEN WILL YOU RETURN THIS?</label>
                <input type="datetime-local" id="ui_return_date" class="form-control form-control-lg mb-3">
                <button class="btn btn-primary w-100 btn-lg fw-bold" onclick="goToStep4()">Review Request</button>
            </div>

            <div id="step4-container" class="step-container">
                <span class="step-indicator">Step 4: Final Confirmation</span>
                <div class="review-box mb-4">
                    <small class="text-muted">ITEM:</small> <span id="review-asset" class="fw-bold d-block mb-2"></span>
                    <small class="text-muted">ORIGIN LOCATION:</small> <span id="review-origin" class="fw-bold d-block mb-2 text-success">Verified</span>
                    <small class="text-muted">DESTINATION LOCATION:</small> <span id="review-destination" class="fw-bold d-block mb-2 text-primary"></span>
                    <small class="text-muted">DUE BY:</small> <span id="review-date" class="fw-bold d-block text-primary"></span>
                </div>

                <form id="borrowForm">
                    <input type="hidden" name="asset_tag" id="hidden_tag">
                    <input type="hidden" name="origin_room_id" id="hidden_origin_room_id">
                    <input type="hidden" name="destination_room_id" id="hidden_destination_room_id">
                    <input type="hidden" name="return_date" id="hidden_return_date">

                    <button type="submit" id="btnSubmit" class="btn btn-success btn-lg w-100 fw-bold py-3">CONFIRM BORROW</button>
                    <button type="button" class="btn btn-link btn-sm w-100 mt-2 text-muted" onclick="location.reload()">Cancel</button>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="roomScannerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="room-reader"></div>
            <div class="camera-controls">
                <div>
                    <div class="fw-semibold">Room camera</div>
                    <div id="room-camera-status" class="camera-status" aria-live="polite">Starting back camera…</div>
                </div>
                <button id="room-camera-switch" type="button" class="btn btn-dark camera-switch">
                    <i class="bi bi-camera-video me-2" aria-hidden="true"></i><span>Switch to front camera</span>
                </button>
            </div>
            <div class="p-3"><button class="btn btn-secondary w-100" data-bs-dismiss="modal" onclick="stopRoomScanner()">Cancel</button></div>
        </div>
    </div>
</div>

<script>
const qrFormats = [Html5QrcodeSupportedFormats.QR_CODE];
let assetScanner = new Html5Qrcode("reader", { formatsToSupport: qrFormats, verbose: false });
let roomScanner;
let isLock = false;
let expectedRoomId = null;
let assetCameraRunning = false;
let assetUsingBackCamera = true;
let roomCameraRunning = false;
let roomUsingBackCamera = true;
let assetScanHandled = false;

const assetCameraButton = document.getElementById('asset-camera-switch');
const assetCameraButtonLabel = assetCameraButton.querySelector('span');
const assetCameraStatus = document.getElementById('asset-camera-status');
const roomCameraButton = document.getElementById('room-camera-switch');
const roomCameraButtonLabel = roomCameraButton.querySelector('span');
const roomCameraStatus = document.getElementById('room-camera-status');

async function startAssetCamera() {
    assetCameraStatus.textContent = assetUsingBackCamera ? 'Starting back camera…' : 'Starting front camera…';
    try {
        await assetScanner.start(
            { facingMode: assetUsingBackCamera ? 'environment' : 'user' },
            { fps: 10 },
            onAssetScanSuccess
        );
        assetCameraRunning = true;
        assetCameraButtonLabel.textContent = assetUsingBackCamera ? 'Switch to front camera' : 'Switch to back camera';
        assetCameraStatus.textContent = assetUsingBackCamera ? 'Back camera active' : 'Front camera active';
        assetCameraButton.disabled = false;
        return true;
    } catch (error) {
        assetCameraRunning = false;
        assetCameraButtonLabel.textContent = 'Start back camera';
        assetCameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
        assetCameraButton.disabled = false;
        return false;
    }
}

async function toggleAssetCamera() {
    assetCameraButton.disabled = true;

    if (!assetCameraRunning) {
        assetUsingBackCamera = true;
        await startAssetCamera();
        return;
    }

    const previousCamera = assetUsingBackCamera;
    try {
        await assetScanner.stop();
        assetCameraRunning = false;
        assetUsingBackCamera = !previousCamera;

        if (!await startAssetCamera()) {
            assetUsingBackCamera = previousCamera;
            if (await startAssetCamera()) {
                assetCameraStatus.textContent = 'Could not switch cameras. Previous camera restored.';
            }
        }
    } catch (error) {
        assetCameraRunning = false;
        assetCameraButtonLabel.textContent = 'Start back camera';
        assetCameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
        assetCameraButton.disabled = false;
    }
}

assetCameraButton.addEventListener('click', toggleAssetCamera);

async function onAssetScanSuccess(decodedText) {
    if (isLock || assetScanHandled) return;
    isLock = true;
    assetScanHandled = true;
    await assetScanner.stop();
    assetCameraRunning = false;

    fetch(`get_asset_info.php?tag=${encodeURIComponent(decodedText)}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(data.is_available == 0) {
                Swal.fire('Error', 'The Asset is not Available', 'error').then(() => location.reload());
                return;
            }
            expectedRoomId = data.room_id;
            document.getElementById('hidden_tag').value = data.asset_tag;
            document.getElementById('review-asset').innerText = data.asset_name;
            document.getElementById('asset-scanner-wrapper').style.display = 'none';
            document.getElementById('step2-container').style.display = 'block';
            isLock = false;
        } else {
            Swal.fire('Error', data.message, 'warning').then(() => location.reload());
        }
    });
}

function startRoomScanner() {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('roomScannerModal'));
    modal.show();
    if (!roomScanner) {
        roomScanner = new Html5Qrcode("room-reader", { formatsToSupport: qrFormats, verbose: false });
    }
    roomUsingBackCamera = true;
    startRoomCamera();
}

async function startRoomCamera() {
    roomCameraStatus.textContent = roomUsingBackCamera ? 'Starting back camera…' : 'Starting front camera…';
    try {
        await roomScanner.start(
            { facingMode: roomUsingBackCamera ? 'environment' : 'user' },
            { fps: 10 },
            onRoomScanSuccess
        );
        roomCameraRunning = true;
        roomCameraButtonLabel.textContent = roomUsingBackCamera ? 'Switch to front camera' : 'Switch to back camera';
        roomCameraStatus.textContent = roomUsingBackCamera ? 'Back camera active' : 'Front camera active';
        roomCameraButton.disabled = false;
        return true;
    } catch (error) {
        roomCameraRunning = false;
        roomCameraButtonLabel.textContent = 'Start back camera';
        roomCameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
        roomCameraButton.disabled = false;
        return false;
    }
}

async function toggleRoomCamera() {
    roomCameraButton.disabled = true;

    if (!roomCameraRunning) {
        roomUsingBackCamera = true;
        await startRoomCamera();
        return;
    }

    const previousCamera = roomUsingBackCamera;
    try {
        await roomScanner.stop();
        roomCameraRunning = false;
        roomUsingBackCamera = !previousCamera;

        if (!await startRoomCamera()) {
            roomUsingBackCamera = previousCamera;
            if (await startRoomCamera()) {
                roomCameraStatus.textContent = 'Could not switch cameras. Previous camera restored.';
            }
        }
    } catch (error) {
        roomCameraRunning = false;
        roomCameraButtonLabel.textContent = 'Start back camera';
        roomCameraStatus.textContent = 'Camera unavailable. Check permission and try again.';
        roomCameraButton.disabled = false;
    }
}

roomCameraButton.addEventListener('click', toggleRoomCamera);

async function onRoomScanSuccess(decodedText) {
    if (isLock || !decodedText.startsWith('ROOM_')) return;
    isLock = true;
    const modal = bootstrap.Modal.getInstance(document.getElementById('roomScannerModal'));
    const scannedId = decodedText.split('_')[1];

    if (expectedRoomId && scannedId !== String(expectedRoomId)) {
        await stopRoomScanner();
        modal.hide();
        Swal.fire('Wrong Location', 'This asset does not belong in this room!', 'error').then(() => location.reload());
        return;
    }

    document.getElementById('hidden_origin_room_id').value = scannedId;
    await stopRoomScanner();
    modal.hide();
    document.getElementById('step2-container').style.display = 'none';
    document.getElementById('step3-container').style.display = 'block';
    isLock = false;
}

function stopRoomScanner() {
    if (!roomScanner || !roomCameraRunning) return Promise.resolve();
    return roomScanner.stop().then(() => {
        roomCameraRunning = false;
    });
}

document.getElementById('roomScannerModal').addEventListener('hidden.bs.modal', stopRoomScanner);

function goToStep4() {
    const destSelect = document.getElementById('ui_destination_room');
    const destRoomId = destSelect.value;
    const destRoomName = destSelect.options[destSelect.selectedIndex].text;
    const dateVal = document.getElementById('ui_return_date').value;
    const originRoomId = document.getElementById('hidden_origin_room_id').value;

    if(!destRoomId) return Swal.fire('Wait', 'Please select a destination room.', 'info');
    if(!dateVal) return Swal.fire('Wait', 'Select a return time.', 'info');
    
    // Core Check: Block transfers within the same exact room space
    if (String(destRoomId) === String(originRoomId)) {
        return Swal.fire('Invalid Transfer', 'You cannot transfer an asset to the same room it came from.', 'error');
    }

    if (new Date(dateVal) < new Date()) {
        return Swal.fire('Invalid Date', 'The return date cannot be in the past.', 'warning');
    }
    
    document.getElementById('hidden_destination_room_id').value = destRoomId;
    document.getElementById('hidden_return_date').value = dateVal;
    
    document.getElementById('review-destination').innerText = destRoomName;
    document.getElementById('review-date').innerText = new Date(dateVal).toLocaleString();
    
    document.getElementById('step3-container').style.display = 'none';
    document.getElementById('step4-container').style.display = 'block';
}

document.getElementById('borrowForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerText = "Processing...";

    fetch('process_borrow.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('Success', 'Asset Borrowed & Room Location Updated!', 'success').then(() => window.location.href='my_borrows.php');
        } else {
            Swal.fire('Error', data.message, 'error');
            btn.disabled = false;
            btn.innerText = "CONFIRM BORROW";
        }
    });
});

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>