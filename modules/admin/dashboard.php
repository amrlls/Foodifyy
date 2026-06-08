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

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$admin_username = $user['username'];
$profile_img    = $user['profile_image'];

// Stats
$total_users   = $conn->query("SELECT COUNT(*) as t FROM users WHERE role = 'customer'")->fetch_assoc()['t'] ?? 0;
$total_staff   = $conn->query("SELECT COUNT(*) as t FROM users WHERE role = 'staff'")->fetch_assoc()['t'] ?? 0;
$total_recipes = $conn->query("SELECT COUNT(*) as t FROM recipes")->fetch_assoc()['t'] ?? 0;
$total_orders  = $conn->query("SELECT COUNT(*) as t FROM orders")->fetch_assoc()['t'] ?? 0;
$total_items   = $conn->query("SELECT COUNT(*) as t FROM items")->fetch_assoc()['t'] ?? 0;
$total_sales   = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE status = 'completed'")->fetch_assoc()['t'] ?? 0;

$today_orders  = $conn->query("SELECT COUNT(*) as t FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['t'] ?? 0;
$today_sales   = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'")->fetch_assoc()['t'] ?? 0;
$weekly_sales  = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1) AND status = 'completed'")->fetch_assoc()['t'] ?? 0;
$monthly_sales = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status = 'completed'")->fetch_assoc()['t'] ?? 0;

$pending_orders = $conn->query("SELECT COUNT(*) as t FROM orders WHERE status = 'pending'")->fetch_assoc()['t'] ?? 0;
$processing_orders = $conn->query("SELECT COUNT(*) as t FROM orders WHERE status = 'processing'")->fetch_assoc()['t'] ?? 0;

$low_stock = $conn->query("SELECT * FROM items WHERE stock <= 10 ORDER BY stock ASC LIMIT 5");
$low_stock_items = $low_stock ? $low_stock->fetch_all(MYSQLI_ASSOC) : [];

$recent = $conn->query("
    SELECT o.*, u.username as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC LIMIT 8
");
$recent_orders = $recent ? $recent->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --sidebar-dark: #1A1C1E;
            --accent: #FF8E53;
            --sidebar-w: 280px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; color: #1A1C1E; }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05); overflow-y: auto;
        }
        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif; font-weight: 900; letter-spacing: -1px;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem; padding-left: 1rem;
        }
        .sidebar-logo .admin-badge {
            margin-left: 1rem; font-size: 0.65rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1px;
            background: var(--primary-grad); -webkit-background-clip: text;
            -webkit-text-fill-color: transparent; background-clip: text;
            opacity: 0.8;
        }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; margin-top: 0; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400; }

        .nav-section-label {
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1.5px; color: #555; padding: 0 18px;
            margin: 1.2rem 0 0.3rem;
        }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 15px; padding: 14px 18px;
            color: #949494; text-decoration: none; border-radius: 16px;
            font-weight: 500; transition: all 0.3s ease;
        }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-nav a i { font-size: 1rem; width: 20px; text-align: center; }

        .sidebar::-webkit-scrollbar { display: none; }

        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s ease; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        /* Main */
        .main-content { margin-left: var(--sidebar-w); padding: 3rem 4rem; min-height: 100vh; }

        /* Hero banner */
        .hero-banner {
            background: linear-gradient(135deg, #1A1C1E 0%, #2D3436 100%);
            border-radius: 28px; padding: 2.2rem 2.5rem; margin-bottom: 2rem;
            color: white; position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: ''; position: absolute; top: -40px; right: -40px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,107,107,0.08);
        }
        .hero-banner::after {
            content: ''; position: absolute; bottom: -60px; right: 80px;
            width: 150px; height: 150px; border-radius: 50%;
            background: rgba(255,142,83,0.06);
        }

        /* Stat cards */
        .stat-card {
            background: white; border-radius: 22px; padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: 0.3s; height: 100%;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.08); }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .stat-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; margin-bottom: 6px; }
        .stat-value { font-size: 2rem; font-weight: 900; color: #1A1C1E; line-height: 1; }
        .stat-value.gradient {
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* Sales overview */
        .section-card {
            background: white; border-radius: 22px; padding: 1.8rem;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 1.5rem;
        }
        .section-card h5 { font-weight: 800; font-size: 1rem; margin-bottom: 1.5rem; }
        .sales-box {
            background: #f8f9fa; border-radius: 16px; padding: 1.2rem;
            text-align: center;
        }
        .sales-box .label { font-size: 0.75rem; color: #bdc3c7; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; }
        .sales-box .amount { font-size: 1.5rem; font-weight: 900; }

        /* Alert card */
        .alert-card {
            background: white; border-radius: 22px; padding: 1.8rem;
            border-left: 4px solid #FF9800;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 1.5rem;
        }
        .stock-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.6rem 0.8rem; background: #f8f9fa; border-radius: 12px;
        }

        /* Order cards */
        .order-card {
            background: white; border-radius: 16px; padding: 1rem 1.2rem;
            border: 1px solid #f0f0f0; margin-bottom: 0.6rem; transition: 0.2s;
        }
        .order-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.06); transform: translateY(-1px); }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-transform: uppercase;
            width: 110px;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        .status-pending    { background: rgba(116,185,255,0.15); color: #0984e3; }
        .status-processing { background: rgba(253,203,110,0.2);  color: #e17055; }
        .status-completed  { background: rgba(46, 204, 113, 0.12);color: #27ae60;}

        .btn-view {
            background: #1A1C1E; color: white; padding: 6px 16px;
            border-radius: 10px; font-size: 0.75rem; font-weight: 700;
            text-decoration: none; display: inline-block; transition: 0.2s;
        }
        .btn-view:hover { background: var(--accent); color: white; }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .section-header h5 { font-weight: 800; margin: 0; }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
        <div class="admin-badge">Admin Panel</div>
    </div>
    <div class="sidebar-greet-box">
        <p>System control center</p>
    </div>

    <ul class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <li><a href="dashboard.php" class="active"><i class="bi bi-grid-fill"></i> Dashboard</a></li>

        <div class="nav-section-label">Manage</div>
        <li><a href="manage_items.php"><i class="bi bi-bag-heart-fill"></i> Manage Items</a></li>
        <li><a href="manage_orders.php"><i class="bi bi-receipt"></i> Manage Orders</a></li>
        <li><a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a></li>
        <li><a href="manage_recipes.php"><i class="bi bi-book-fill"></i> Manage Recipes</a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="../admin/profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3">
                <?php $profileSrc = getImageSrc($profile_img, '../../assets/images/profiles/'); ?>
                <?php if ($profileSrc): ?>
                    <img src="<?= htmlspecialchars($profileSrc) ?>" style="width:42px;height:42px;border-radius:12px;object-fit:cover;">
                <?php else: ?>
                    <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px;height:42px;background:var(--primary-grad);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="text-white fw-bold small text-truncate" style="max-width:130px;"><?= htmlspecialchars($admin_username) ?></div>
                    <div style="font-size:0.65rem;color:var(--accent);font-weight:600;text-transform:uppercase;">Administrator</div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Hero -->
    <div class="hero-banner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-1" style="font-size:1.8rem;">Admin Dashboard</h1>
                <p class="opacity-75 mb-0">Welcome back, <?= htmlspecialchars($admin_username) ?></p>
            </div>
            <div class="text-end">
                <div class="h4 fw-bold mb-0"><?= date('F j, Y') ?></div>
                <small class="opacity-75"><?= date('l') ?></small>
            </div>
        </div>
    </div>

    <!-- Stats Row 1 -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= $total_users ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(116,185,255,0.15);color:#0984e3;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Staff</div>
                        <div class="stat-value"><?= $total_staff ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(162,155,254,0.15);color:#6c5ce7;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Recipes</div>
                        <div class="stat-value"><?= $total_recipes ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(253,203,110,0.2);color:#e17055;">
                        <i class="bi bi-book-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?= $total_orders ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(255,107,107,0.12);color:#FF6B6B;">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 2 -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Items</div>
                        <div class="stat-value"><?= $total_items ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(0,184,148,0.12);color:#00b894;">
                        <i class="bi bi-bag-heart-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Sales</div>
                        <div class="stat-value gradient" style="font-size:1.4rem;">RM <?= number_format($total_sales, 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(255,142,83,0.12);color:#FF8E53;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Pending Orders</div>
                        <div class="stat-value"><?= $pending_orders ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(116,185,255,0.12);color:#0984e3;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Today's Orders</div>
                        <div class="stat-value"><?= $today_orders ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(253,203,110,0.15);color:#fdcb6e;">
                        <i class="bi bi-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Overview -->
    <div class="section-card">
        <h5><i class="bi bi-graph-up me-2"></i>Sales Overview</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="sales-box">
                    <div class="label">Today</div>
                    <div class="amount text-success">RM <?= number_format($today_sales, 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sales-box">
                    <div class="label">This Week</div>
                    <div class="amount text-primary">RM <?= number_format($weekly_sales, 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sales-box">
                    <div class="label">This Month</div>
                    <div class="amount text-info">RM <?= number_format($monthly_sales, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock_items)): ?>
    <div class="alert-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock Alert</h5>
            <a href="manage_items.php" class="btn-view">Manage Items →</a>
        </div>
        <div class="row g-2">
            <?php foreach ($low_stock_items as $item): ?>
            <div class="col-md-4">
                <div class="stock-item">
                    <span class="fw-600"><?= htmlspecialchars($item['name']) ?></span>
                    <span class="fw-bold text-warning">Stock: <?= $item['stock'] ?> <?= $item['unit'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Orders -->
    <div class="section-header">
        <h5>Recent Orders</h5>
        <a href="manage_orders.php" class="btn-view">Manage Orders →</a>
    </div>

    <?php foreach ($recent_orders as $order):
        $statusClass = match($order['status']) {
            'pending'    => 'status-pending',
            'processing' => 'status-processing',
            'completed'  => 'status-completed',
            default      => 'status-pending'
        };
    ?>
    <div class="order-card">
        <div class="row align-items-center">
            <div class="col-md-3">
                <code class="fw-bold">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></code>
                <div class="small text-muted"><?= htmlspecialchars($order['customer_name']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="fw-bold">RM <?= number_format($order['total_price'], 2) ?></span>
                <div class="small text-muted"><?= date('M d, H:i', strtotime($order['created_at'])) ?></div>
            </div>
            <div class="col-md-3">
                <span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>