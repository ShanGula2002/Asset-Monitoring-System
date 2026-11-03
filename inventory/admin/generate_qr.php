<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$tag = $_GET['tag'] ?? 'No-Tag';
$name = $_GET['name'] ?? 'Unknown Item';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print QR Code | <?php echo $tag; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
        @media print { .no-print { display: none; } }
        .qr-card { border: 2px dashed #ccc; padding: 20px; width: 300px; margin: auto; text-align: center; }
        @media (max-width: 576px) {
            body { padding: 76px 16px 24px !important; }
            .qr-card { width: 100%; max-width: 340px; padding: 18px; }
        }
    </style>
</head>
<body class="bg-light p-5">

    <div class="qr-card bg-white shadow">
        <h5 class="fw-bold mb-1"><?php echo $name; ?></h5>
        <p class="text-muted small mb-3">Asset Tag: <?php echo $tag; ?></p>
        
        <canvas id="qr"></canvas>
        
        <div class="mt-4 no-print">
            <button class="btn btn-primary w-100 mb-2" onclick="window.print()">Print Label</button>
            <a href="manage_assets.php" class="btn btn-outline-secondary w-100">Back to List</a>
        </div>
    </div>

    <script>
        (function() {
            var qr = new QRious({
                element: document.getElementById('qr'),
                value: '<?php echo $tag; ?>', // This is the data in the QR
                size: 200,
                level: 'H' // High error correction (better for stickers)
            });
        })();
    </script>
</body>
</html>