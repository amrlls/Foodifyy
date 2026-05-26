<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'staff') {
    header('Location: ../index.php');
    exit();
}

$staff_username = $user['username'];
$profile_img = $user['profile_image'];

$order_id = $_GET['id'] ?? 0;
if (!$order_id) header('Location: orders.php');

$stmt = $conn->prepare("SELECT o.*, u.username as customer_name, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) header('Location: orders.php');

$stmt = $conn->prepare("SELECT oi.*, i.name, i.unit, i.image FROM order_items oi JOIN items i ON oi.item_id = i.item_id WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$greeting = "Order details and status update.";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Details</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; }
            .status-badge { padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
            .status-accepted { background: #E3F2FD; color: #2196F3; }
            .status-preparing { background: #E8EAF6; color: #3F51B5; }
            .status-completed { background: #E8F5E9; color: #4CAF50; }
            .info-card { background: white; border-radius: 24px; padding: 1.5rem; height: 100%; }
            .order-item { border-bottom: 1px solid #f0f0f0; padding: 1rem 0; }
            .order-item:last-child { border-bottom: none; }
            .btn-update { background: #1A1C1E; color: white; border: none; padding: 12px 28px; border-radius: 16px; font-weight: 600; width: 100%; }
            .btn-update:hover { background: #FF8E53; }
            .status-select { padding: 12px 20px; border-radius: 16px; border: 1px solid #e0e0e0; font-size: 1rem; width: 100%; }
        </style>
    </head>
    <body>
        <?php include 'includes/staff_nav.php'; ?>

        <div class="main-content">
            <div class="mb-3"><a href="orders.php" class="text-muted"><i class="bi bi-arrow-left"></i> Back to Orders</a></div>
            <div class="d-flex justify-content-between align-items-start mb-4"><div><h2 class="fw-bold mb-1">Order #<?= $order['order_id'] ?></h2><p class="text-muted">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p></div><?php $statusClass = match($order['status']) { 'pending' => 'status-pending', 'processing' => 'status-processing', 'completed' => 'status-completed', default => 'status-pending' }; ?><div><span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span></div></div>
            <div class="row g-4">
                <div class="col-md-5"><div class="info-card"><h6 class="fw-bold mb-3"><i class="bi bi-person-circle me-2"></i>Customer</h6><div><strong>Name:</strong> <?= htmlspecialchars($order['shipping_name'] ?? $order['customer_name']) ?></div><div><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></div><div><strong>Phone:</strong> <?= htmlspecialchars($order['shipping_phone'] ?? $order['phone'] ?? 'N/A') ?></div><div class="mt-2"><strong>Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')) ?></div></div></div>
                <div class="col-md-7"><div class="info-card"><h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Summary</h6><div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span>RM <?= number_format($order['total_price'], 2) ?></span></div><div class="d-flex justify-content-between mb-2"><span>Delivery:</span><span>RM <?= number_format($order['delivery_fee'] ?? 3.50, 2) ?></span></div><hr><div class="d-flex justify-content-between"><strong>Total:</strong><strong class="fs-4 text-primary">RM <?= number_format(($order['total_price'] + ($order['delivery_fee'] ?? 3.50)), 2) ?></strong></div></div></div>
                <div class="col-12"><div class="info-card"><h6 class="fw-bold mb-3"><i class="bi bi-cart-check me-2"></i>Items (<?= count($order_items) ?>)</h6><?php foreach ($order_items as $item): ?><div class="order-item d-flex justify-content-between align-items-center"><div class="d-flex gap-3"><div style="width:50px; height:50px; border-radius:12px; overflow:hidden; background:#f0f0f0;"><img src="<?= getImageSrc($item['image'], '../assets/images/items/') ?: 'https://placehold.co/400x400' ?>" class="w-100 h-100 object-fit-cover"></div><div><div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div><div class="small text-muted">Qty: <?= $item['quantity'] ?> × RM <?= number_format($item['unit_price'], 2) ?></div></div></div><div class="fw-bold">RM <?= number_format($item['quantity'] * $item['unit_price'], 2) ?></div></div><?php endforeach; ?></div></div>
                <div class="col-12"><div class="info-card"><h6 class="fw-bold mb-3"><i class="bi bi-arrow-repeat me-2"></i>Update Status</h6><div class="row g-3"><div class="col-md-8">
                    <select id="statusSelect" class="status-select">
                        <option value="accepted" <?= $order['status'] == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select></div><div class="col-md-4"><button class="btn-update" onclick="updateStatus()">Update Status</button></div></div></div></div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        async function updateStatus() {
            const newStatus = document.getElementById('statusSelect').value;
            const currentStatus = '<?= $order['status'] ?>';
            if (newStatus === currentStatus) { alert('Status is already ' + newStatus.toUpperCase()); return; }
            if (!confirm(`Change status from ${currentStatus.toUpperCase()} to ${newStatus.toUpperCase()}?`)) return;
            const formData = new FormData();
            formData.append('order_id', <?= $order_id ?>);
            formData.append('status', newStatus);
            const response = await fetch('update_order_status.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) location.reload();
            else alert('Failed to update');
        }
        </script>
    </body>
</html>