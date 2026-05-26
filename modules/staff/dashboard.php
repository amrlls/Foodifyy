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
    header('Location: ../../index.php');
    exit();
}

$staff_username = $user['username'];
$profile_img = $user['profile_image'];

$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'] ?? 0;
$total_sales = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$today_orders = $conn->query("SELECT COUNT(*) as today FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['today'] ?? 0;

$status_counts = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

$low_stock = $conn->query("SELECT * FROM items WHERE stock <= 10 ORDER BY stock ASC LIMIT 5");
$low_stock_items = $low_stock ? $low_stock->fetch_all(MYSQLI_ASSOC) : [];

$recent = $conn->query("
    SELECT o.*, u.username as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.created_at DESC LIMIT 10
");
$recent_orders = $recent ? $recent->fetch_all(MYSQLI_ASSOC) : [];

// Sales calculations
$today_sales = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
$weekly_sales = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(NOW()) AND status = 'completed'")->fetch_assoc()['total'] ?? 0;
$monthly_sales = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status = 'completed'")->fetch_assoc()['total'] ?? 0;

$greeting = "Welcome back, " . htmlspecialchars($staff_username);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Staff Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; }
            .hero-banner { background: linear-gradient(135deg, #1A1C1E 0%, #2D3436 100%); border-radius: 28px; padding: 2rem; margin-bottom: 2rem; }
            .stat-card { background: white; border-radius: 24px; padding: 1.5rem; transition: 0.3s; border: 1px solid rgba(0,0,0,0.04); }
            .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
            .stat-icon { width: 50px; height: 50px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
            .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
            .status-accepted { background: #E3F2FD; color: #2196F3; }
            .status-preparing { background: #E8EAF6; color: #3F51B5; }
            .status-completed { background: #E8F5E9; color: #4CAF50; }
            .btn-view { background: #1A1C1E; color: white; padding: 6px 16px; border-radius: 12px; font-size: 0.75rem; text-decoration: none; display: inline-block; }
            .btn-view:hover { background: #FF8E53; color: white; }
            .order-card { background: white; border-radius: 20px; padding: 1rem; margin-bottom: 0.75rem; border: 1px solid rgba(0,0,0,0.04); }
            .alert-card { background: white; border-radius: 24px; padding: 1.5rem; border-left: 4px solid #FF9800; }
        </style>
    </head>
    <body>
        <?php include 'includes/staff_nav.php'; ?>

        <div class="main-content">
            <div class="hero-banner">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h1 class="mb-1" style="font-size: 1.8rem;">Staff Dashboard</h1><p class="opacity-75 mb-0">Manage inventory and orders</p></div>
                    <div class="text-end"><div class="h3 mb-0 fw-bold text-white"><?= date('F j, Y') ?></div><small><?= date('l') ?></small></div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted mb-1 small">TOTAL ORDERS</p><h2 class="fw-bold mb-0"><?= $total_orders ?></h2></div>
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-receipt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted mb-1 small">TOTAL SALES</p><h2 class="fw-bold mb-0">RM <?= number_format($total_sales, 2) ?></h2></div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-currency-dollar"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted mb-1 small">TODAY'S ORDERS</p><h2 class="fw-bold mb-0"><?= $today_orders ?></h2></div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-day"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div><p class="text-muted mb-1 small">TO ACCEPT</p><h2 class="fw-bold mb-0"><?= $status_counts['accepted'] ?? 0 ?></h2></div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-graph-up me-2"></i>Sales Overview</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded-4">
                                    <div class="small text-muted">Today's Sales</div>
                                    <div class="h3 fw-bold text-success">RM <?= number_format($today_sales, 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded-4">
                                    <div class="small text-muted">This Week</div>
                                    <div class="h3 fw-bold text-primary">RM <?= number_format($weekly_sales, 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded-4">
                                    <div class="small text-muted">This Month</div>
                                    <div class="h3 fw-bold text-info">RM <?= number_format($monthly_sales, 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($low_stock_items)): ?>
            <div class="alert-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock Alert</h5>
                    <a href="inventory.php" class="btn-view">Manage Stock →</a>
                </div>
                <div class="row g-3">
                    <?php foreach ($low_stock_items as $item): ?>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between p-2 bg-light rounded-3">
                            <span><?= htmlspecialchars($item['name']) ?></span>
                            <span class="fw-bold text-warning">Stock: <?= $item['stock'] ?> <?= $item['unit'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn-view">View All →</a>
            </div>
            
            <?php foreach ($recent_orders as $order): 
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
                        <code>#<?= $order['order_id'] ?></code>
                        <div class="small text-muted"><?= htmlspecialchars($order['customer_name']) ?></div>
                    </div>
                    <div class="col-md-2">
                        <span class="fw-bold">RM <?= number_format($order['total_price'], 2) ?></span>
                        <div class="small text-muted"><?= date('M d, H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Order #<?= $order['order_id'] ?></small>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn-view">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>