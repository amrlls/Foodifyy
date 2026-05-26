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
    header('Location: 17./../index.php');
    exit();
}

$staff_username = $user['username'];
$profile_img = $user['profile_image'];

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT o.*, u.username as customer_name FROM orders o JOIN users u ON o.user_id = u.user_id";
$conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $conditions[] = "o.status = ?";
    $params[] = $status_filter;
}
if (!empty($search)) {
    $conditions[] = "(u.username LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (count($conditions) > 0) $query .= " WHERE " . implode(" AND ", $conditions);
$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $result->fetch_assoc()) $counts[$row['status']] = $row['count'];

$greeting = "Update customer order status.";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer Orders</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; }
            .filter-btn { padding: 8px 20px; border-radius: 30px; font-size: 0.85rem; font-weight: 600; text-decoration: none; background: white; color: #6c757d; border: 1px solid #e0e0e0; display: inline-block; }
            .filter-btn.active, .filter-btn:hover { background: #1A1C1E; color: white; }
            .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
            .status-accepted { background: #E3F2FD; color: #2196F3; }
            .status-preparing { background: #E8EAF6; color: #3F51B5; }
            .status-completed { background: #E8F5E9; color: #4CAF50; }
            .btn-view { background: #1A1C1E; color: white; padding: 6px 16px; border-radius: 12px; font-size: 0.75rem; text-decoration: none; display: inline-block; }
            .btn-view:hover { background: #FF8E53; color: white; }
            .btn-clear { background: #6c757d; color: white; padding: 6px 16px; border-radius: 12px; font-size: 0.75rem; text-decoration: none; display: inline-block; }
            .btn-clear:hover { background: #5a6268; color: white; }
            .order-card { background: white; border-radius: 20px; padding: 1rem; margin-bottom: 1rem; }
            .status-select { padding: 5px 10px; border-radius: 12px; border: 1px solid #e0e0e0; font-size: 0.75rem; }
            .search-input { border-radius: 12px; border: 1px solid #e0e0e0; padding: 6px 12px; font-size: 0.75rem; }
            .search-input:focus { outline: none; border-color: #FF8E53; }
        </style>
    </head>
    <body>
        <?php include 'includes/staff_nav.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2 class="fw-bold mb-1">Customer Orders</h2><p class="text-muted mb-0">Update order status</p></div>
                <div><div class="small text-muted">Total Orders</div><div class="h4 fw-bold"><?= count($orders) ?></div></div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="?status=all&search=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">All (<?= array_sum($counts) ?>)</a>
                <a href="?status=accepted&search=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'accepted' ? 'active' : '' ?>">Accepted (<?= $counts['accepted'] ?? 0 ?>)</a>
                <a href="?status=preparing&search=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'preparing' ? 'active' : '' ?>">Preparing (<?= $counts['preparing'] ?? 0 ?>)</a>
                <a href="?status=completed&search=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'completed' ? 'active' : '' ?>">Completed (<?= $counts['completed'] ?? 0 ?>)</a>
            </div>

            <form method="GET" class="d-flex gap-2 mb-4 align-items-center">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <input type="text" name="search" class="form-control" placeholder="Search by customer name or order ID..." value="<?= htmlspecialchars($search) ?>" style="max-width: 280px; border-radius: 12px;">
                                <button type="submit" class="btn-view">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="?status=<?= $status_filter ?>" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5 bg-white rounded-4">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    <span>No orders found</span>
                    <?php if (!empty($search)): ?>
                        <div class="mt-2">
                            <a href="?status=<?= $status_filter ?>" class="btn-view">Clear Search</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: foreach ($orders as $order): 
                $statusClass = match($order['status']) { 
                    'accepted' => 'status-accepted', 
                    'preparing' => 'status-preparing', 
                    'completed' => 'status-completed', 
                    default => 'status-accepted' 
                };
            ?>
            <div class="order-card">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <code class="fw-bold">#<?= $order['order_id'] ?></code>
                        <div class="small text-muted"><?= htmlspecialchars($order['customer_name']) ?></div>
                    </div>
                    <div class="col-md-2">
                        <span class="fw-bold">RM <?= number_format($order['total_price'], 2) ?></span>
                        <div class="small text-muted"><?= date('M d, H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                    <div class="col-md-4 text-end">
                        <select class="status-select me-2" onchange="updateOrderStatus(<?= $order['order_id'] ?>, this.value)">
                            <option value="accepted" <?= $order['status'] == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                            <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn-view">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        async function updateOrderStatus(orderId, newStatus) {
            if (!confirm(`Change status to ${newStatus.toUpperCase()}?`)) { location.reload(); return; }
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            const response = await fetch('update_order_status.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) location.reload();
            else alert('Failed to update');
        }
        </script>
    </body>
</html>