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
    <title>Locate Asset | AssetApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .search-container { max-width: 800px; margin: 0 auto; }
        .search-input-group {
            background: white;
            border-radius: 15px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .search-input-group input { border: none; box-shadow: none !important; }
        .asset-card {
            border: none;
            border-radius: 15px;
            transition: all 0.2s;
        }
        .asset-result { color: inherit; cursor: pointer; text-align: left; }
        .asset-result:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.09) !important; }
        .asset-result:focus-visible { outline: 3px solid #0d6efd; outline-offset: 3px; }
        .map-modal-content { border: 0; border-radius: 14px; overflow: hidden; }
        #assetLocationMap { height: min(65vh, 620px); min-height: 340px; background: #e9efed; }
        .room-label { background: white; border: 2px solid #4e73df; color: #224abe; font-weight: 700; border-radius: 8px; padding: 4px 10px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .status-dot {
            height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px;
        }
        .bg-available { background-color: #198754; }
        .bg-borrowed { background-color: #ffc107; }
        .bg-damaged { background-color: #dc3545; }
        @media (max-width: 767.98px) {
            .flex-grow-1.p-3.p-md-4 { padding: 22px 16px 28px !important; }
            .search-container { max-width: none; }
            .search-input-group { padding: 7px; }
            .search-input-group input { font-size: 1rem; }
            #resultsArea .card { padding: 14px !important; }
            #resultsArea .row { --bs-gutter-y: .5rem; }
            #assetLocationMap { min-height: 300px; height: 60vh; }
        }
    </style>
</head>
<body>

<div class="d-flex flex-column flex-md-row min-vh-100">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-3 p-md-4">
        <div class="search-container">
            
            <div class="mb-4 text-center text-md-start">
                <h4 class="fw-bold"><i class="bi bi-geo-alt-fill text-primary"></i> Locate Asset</h4>
                <p class="text-muted small">Search by Name, Brand, or Asset Tag</p>
            </div>

            <div class="search-input-group d-flex align-items-center mb-4">
                <i class="bi bi-search ms-3 text-muted fs-5"></i>
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="e.g. Projector, Dell, SN-992..." autocomplete="off">
            </div>

            <div id="resultsArea" class="row g-3">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-keyboard fs-1 opacity-25"></i>
                    <p class="mt-2">Start typing to search the inventory...</p>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="assetMapModal" tabindex="-1" aria-labelledby="mapAssetTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content map-modal-content">
            <div class="modal-header">
                <div>
                    <div class="small text-muted">Designated asset location</div>
                    <h5 class="modal-title fw-bold" id="mapAssetTitle">Asset location</h5>
                    <div class="small text-muted" id="mapAssetSubtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close map"></button>
            </div>
            <div id="assetLocationMap" role="region" aria-label="Map showing the asset's designated room"></div>
            <div id="mapUnavailable" class="d-none text-center text-muted py-5 px-3">
                <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                This asset does not have a designated room with map coordinates.
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-muted" id="mapRoomLabel"></span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="recenterAssetMap"><i class="bi bi-crosshair me-1"></i>Recenter map</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
const resultsArea = document.getElementById('resultsArea');
const mapModalElement = document.getElementById('assetMapModal');
const mapModal = bootstrap.Modal.getOrCreateInstance(mapModalElement);
let assetMap = null;
let assetMapLayers = null;
let selectedAsset = null;
let latestSearch = 0;
let displayedAssets = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[character]);
}

function showAssetLocation(asset) {
    selectedAsset = asset;
    document.getElementById('mapAssetTitle').textContent = asset.asset_name || 'Asset location';
    document.getElementById('mapAssetSubtitle').textContent = `${asset.asset_tag || 'No asset tag'} · ${asset.room_name || 'No designated room'}`;
    document.getElementById('mapRoomLabel').textContent = asset.room_name || 'No designated room';
    mapModal.show();
}

function renderAssetMap() {
    if (!selectedAsset) return;

    const mapElement = document.getElementById('assetLocationMap');
    const unavailableElement = document.getElementById('mapUnavailable');
    const latitude = Number(selectedAsset.room_latitude);
    const longitude = Number(selectedAsset.room_longitude);
    const hasCoordinates = selectedAsset.room_latitude !== null && selectedAsset.room_longitude !== null &&
        Number.isFinite(latitude) && Number.isFinite(longitude) && Math.abs(latitude) <= 90 && Math.abs(longitude) <= 180 &&
        (latitude !== 0 || longitude !== 0);

    if (!hasCoordinates) {
        mapElement.classList.add('d-none');
        unavailableElement.classList.remove('d-none');
        return;
    }

    mapElement.classList.remove('d-none');
    unavailableElement.classList.add('d-none');

    if (!assetMap) {
        assetMap = L.map('assetLocationMap').setView([latitude, longitude], 18);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(assetMap);
        assetMapLayers = L.layerGroup().addTo(assetMap);
    }

    assetMap.invalidateSize();
    assetMap.setView([latitude, longitude], 18);
    assetMapLayers.clearLayers();

    const roomName = selectedAsset.room_name || 'Designated room';
    const radius = Math.max(1, Number(selectedAsset.allowed_radius) || 15);
    L.circle([latitude, longitude], {
        color: '#4e73df',
        fillColor: '#4e73df',
        fillOpacity: 0.15,
        radius: radius
    }).bindTooltip(escapeHtml(roomName), {
        permanent: true,
        direction: 'center',
        className: 'room-label'
    }).addTo(assetMapLayers);

    L.marker([latitude, longitude]).bindPopup(
        `<strong>${escapeHtml(selectedAsset.asset_name)}</strong><br>${escapeHtml(selectedAsset.asset_tag)}<br>${escapeHtml(roomName)}`
    ).addTo(assetMapLayers).openPopup();
}

mapModalElement.addEventListener('shown.bs.modal', renderAssetMap);
document.getElementById('recenterAssetMap').addEventListener('click', renderAssetMap);
resultsArea.addEventListener('click', event => {
    const assetResult = event.target.closest('.asset-result');
    if (!assetResult) return;

    event.preventDefault();
    const asset = displayedAssets[Number(assetResult.dataset.assetIndex)];
    if (asset) showAssetLocation(asset);
});
resultsArea.addEventListener('keydown', event => {
    const assetResult = event.target.closest('.asset-result');
    if (!assetResult || event.key !== ' ') return;

    event.preventDefault();
    const asset = displayedAssets[Number(assetResult.dataset.assetIndex)];
    if (asset) showAssetLocation(asset);
});

searchInput.addEventListener('input', async function() {
    const query = this.value.trim();
    const requestId = ++latestSearch;

    if (query.length < 2) {
        resultsArea.innerHTML = `<div class="text-center py-5 text-muted"><i class="bi bi-keyboard fs-1 opacity-25"></i><p class="mt-2">Start typing...</p></div>`;
        return;
    }

    try {
        const response = await fetch(`search_assets.php?q=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Asset search failed');
        const data = await response.json();
        if (requestId !== latestSearch) return;
        resultsArea.innerHTML = '';
        
        if (data.length === 0) {
            displayedAssets = [];
            resultsArea.innerHTML = `<div class="text-center py-5 text-muted"><i class="bi bi-search fs-1 opacity-25"></i><p class="mt-2">No assets found matching "${escapeHtml(query)}"</p></div>`;
            return;
        }

        displayedAssets = data;
        data.forEach((asset, index) => {
            let statusText = 'Available';
            let dotClass = 'bg-available';
            let extraInfo = '';

            if (asset.status === 'damage' || asset.status === 'need repair') {
                statusText = asset.status.toUpperCase();
                dotClass = 'bg-damaged';
            } else if (asset.is_available == 0) {
                statusText = 'Currently Borrowed';
                dotClass = 'bg-borrowed';
                extraInfo = `<div class="mt-2 small text-warning"><i class="bi bi-person"></i> Held by: ${escapeHtml(asset.borrower_name || 'Unknown')}</div>`;
            }

            resultsArea.innerHTML += `
                <div class="col-12">
                    <a href="#" role="button" data-asset-index="${index}" class="card asset-card asset-result shadow-sm p-3 w-100 text-decoration-none" aria-label="Show map for ${escapeHtml(asset.asset_name)}">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h6 class="fw-bold mb-1">${escapeHtml(asset.asset_name)}</h6>
                                <p class="text-muted small mb-0">
                                    <span class="badge bg-light text-dark border me-1">${escapeHtml(asset.brand || 'No Brand')}</span>
                                    Tag: <strong>${escapeHtml(asset.asset_tag)}</strong>
                                </p>
                            </div>
                            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                <div class="mb-1">
                                    <i class="bi bi-door-open text-primary me-1"></i> 
                                    <strong>${escapeHtml(asset.room_name || 'No designated room')}</strong>
                                </div>
                                <div class="small text-muted">
                                    <span class="status-dot ${dotClass}"></span>${escapeHtml(statusText)}
                                </div>
                                ${extraInfo}
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
    } catch (error) {
        if (requestId !== latestSearch) return;
        resultsArea.innerHTML = `<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-circle fs-1"></i><p class="mt-2">Unable to search assets. Please try again.</p></div>`;
    }
});
</script>

</body>
</html>