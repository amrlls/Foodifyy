<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Cart count
$cartCount = 0;
$stmt_cart = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt_cart->bind_param("i", $userId);
$stmt_cart->execute();
$cart_row  = $stmt_cart->get_result()->fetch_assoc();
$cartCount = (int)($cart_row['total'] ?? 0);

// Nav info
$nav_profile_img = "";
$nav_role = "Customer";
$username = 'Guest';
$stmt_nav = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
$stmt_nav->bind_param("i", $userId);
$stmt_nav->execute();
$user_nav = $stmt_nav->get_result()->fetch_assoc();
if ($user_nav) {
    $username        = $user_nav['username'];
    $nav_profile_img = $user_nav['profile_image'];
    $nav_role        = $user_nav['role'];
}

// Filter by status
$statusFilter = $_GET['status'] ?? 'all';

$where  = ["o.user_id = ?"];
$params = [$userId];
$types  = "i";

if ($statusFilter !== 'all') {
    $where[]  = "o.status = ?";
    $params[] = $statusFilter;
    $types   .= "s";
}

$whereSQL = implode(' AND ', $where);

$stmt = $conn->prepare("
    SELECT o.*, p.method, p.status as pay_status, p.transaction_ref,
           COUNT(oi.item_id) as item_count
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.order_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    WHERE $whereSQL
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count  = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --sidebar-dark: #1A1C1E;
            --accent: #FF8E53;
            --soft-bg: #fdfdfd;
            --sidebar-w: 280px;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--soft-bg); color: #1A1C1E; overflow-x: hidden; }

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
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 15px; padding: 14px 18px;
            color: #949494; text-decoration: none; border-radius: 16px;
            font-weight: 500; transition: all 0.3s;
        }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        .main-content { margin-left: var(--sidebar-w); padding: 0; min-height: 100vh; background: white; }
        .header-section { padding: 3rem 4rem 2rem; background: white; border-bottom: 1px solid #f5f5f5; }
        .top-bar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1rem;
        }

        .header-section {
            padding: 3rem 4rem 2rem;
            background: white;
            border-bottom: 1px solid #f5f5f5;
        }
        .header-section p { color: #7f8c8d; font-size: 1rem; margin-top: 0.5rem; }

        .content-body { padding: 2rem 4rem; }

        /* Filter tabs */
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 2rem; flex-wrap: wrap; }
        .filter-tab {
            padding: 9px 22px; border-radius: 100px; border: 1.5px solid #eee;
            font-size: 0.85rem; font-weight: 700; color: #7f8c8d;
            text-decoration: none; transition: 0.2s;
        }
        .filter-tab:hover { border-color: var(--accent); color: var(--accent); }
        .filter-tab.active { background: var(--primary-grad); border-color: transparent; color: white; }

        /* Order card */
        .order-card {
            background: white; border-radius: 20px; padding: 1.5rem;
            border: 1px solid #f0f0f0; box-shadow: var(--card-shadow);
            margin-bottom: 1.2rem; transition: 0.3s; cursor: pointer;
            text-decoration: none; display: block; color: inherit;
        }
        .order-card:hover { box-shadow: 0 16px 40px rgba(0,0,0,0.09); transform: translateY(-2px); color: inherit; }

        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .order-id { font-weight: 800; font-size: 0.95rem; color: #1A1C1E; }
        .order-date { font-size: 0.78rem; color: #bdc3c7; font-weight: 600; margin-top: 3px; }

        /* Status badges */
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

        /* Dot styling */
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor; 
            flex-shrink: 0;  
        }
        .status-badge i { font-size: 0.5rem; }
        .status-badge.pending    { background: rgba(116,185,255,0.15); color: #0984e3; }
        .status-badge.processing { background: rgba(253,203,110,0.2);  color: #e17055; }
        .status-badge.completed  { background: rgba(46, 204, 113, 0.12);color: #27ae60; }
        
        .order-divider { border: none; border-top: 1px solid #f5f5f5; margin: 1rem 0; }

        .order-meta {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            flex: 1;
        }
        .meta-item { font-size: 0.82rem; }
        .meta-item .label { color: #bdc3c7; font-weight: 600; margin-bottom: 4px; font-size: 0.75rem; }
        .meta-item .value { font-weight: 800; color: #1A1C1E; }
        .meta-item .value.price {
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* Payment method badge */
        .pay-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700;
            background: #f8f9fa; color: #7f8c8d;
        }

        /* Empty state */
        .empty-state { text-align: center; padding: 5rem 2rem; }
        .empty-state i { font-size: 4rem; color: #eee; display: block; margin-bottom: 1.5rem; }
        .empty-state h4 { font-weight: 800; color: #1A1C1E; margin-bottom: 0.5rem; }
        .empty-state p { color: #bdc3c7; font-size: 0.9rem; }

        /* Floating cart */
        /* Pay Now button */
        .btn-pay-now {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: 14px;
            background: var(--primary-grad); color: white;
            font-size: 0.85rem; font-weight: 800; border: none;
            cursor: pointer; transition: 0.25s; text-decoration: none;
            white-space: nowrap; flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(255,107,107,0.25);
        }
        .btn-pay-now:hover { opacity: 0.88; color: white; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(255,107,107,0.35); }

        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem;
            border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 999; bottom: 30px; right: 30px; transition: 0.3s;
        }
        .floating-cart:hover { background: #2d2f31; transform: translateY(-3px); }

        @media (max-width: 992px) {
            .header-section, .content-body { padding: 2rem; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Track your orders.</p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="../shop/items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <li><a href="../recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
        <li><a href="my_orders.php" class="active"><i class="bi bi-receipt"></i> Orders</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../profile/profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3">
                <?php $navProfileSrc = getImageSrc($nav_profile_img, '../../assets/images/profiles/'); ?>
                <?php if ($navProfileSrc): ?>
                    <img src="<?= htmlspecialchars($navProfileSrc) ?>" style="width:42px;height:42px;border-radius:12px;object-fit:cover;">
                <?php else: ?>
                    <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px;height:42px;background:var(--primary-grad);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="text-white fw-bold small text-truncate" style="max-width:130px;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:0.65rem;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($nav_role) ?></div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-end header-section">
    <div class="top-bar">
        <h1 class="page-title">My Orders</h1>
        <p class="text-muted mb-0">
            <?= $count ?> order<?= $count !== 1 ? 's' : '' ?> found
        </p>
    </div>
</div>

    <div class="content-body">
        <!-- Filter tabs -->
        <div class="filter-tabs">
            <?php
            $tabs = ['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed'];
            foreach ($tabs as $val => $label):
                $active = ($statusFilter === $val) ? 'active' : '';
            ?>
                <a href="?status=<?= $val ?>" class="filter-tab <?= $active ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <h4>No orders yet</h4>
                <p>Start shopping to see your orders here.</p>
                <a href="../shop/items.php" class="btn mt-3 px-4 py-2 fw-bold text-white" style="background:var(--primary-grad);border-radius:14px;">
                    Browse Market
                </a>
            </div>
        <?php else: ?>
        <?php foreach ($orders as $order):
            $isPendingOnline = ($order['status'] === 'pending' && $order['method'] === 'online_banking' && $order['transaction_ref']);
        ?>
            <?php if ($isPendingOnline): ?>
            <div class="order-card" onclick="window.location.href='order_detail.php?id=<?= $order['order_id'] ?>'">
            <?php else: ?>
            <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="order-card">
            <?php endif; ?>

                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></div>
                            <div class="order-date"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                        </div>
                        <span class="status-badge <?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>

                    <hr class="order-divider">

                    <div class="d-flex align-items-center justify-content-between">
                        <div class="order-meta" style="flex:1;">
                            <div class="meta-item">
                                <div class="label">Items</div>
                                <div class="value"><?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="label">Total</div>
                                <div class="value price">RM <?= number_format($order['total_price'], 2) ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="label">Delivery</div>
                                <div class="value"><?= $order['shipping_address'] === 'Self Pickup' ? 'Self Pickup' : 'Home Delivery' ?></div>
                            </div>
                            <div class="meta-item">
                                <span class="pay-badge">
                                    <i class="bi <?= $order['method'] === 'cod' ? 'bi-cash-stack' : 'bi-bank' ?>"></i>
                                    <?= $order['method'] === 'cod' ? 'Cash on Delivery' : 'Online Banking' ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($isPendingOnline): ?>
                        <a href="https://toyyibpay.com/<?= htmlspecialchars($order['transaction_ref']) ?>"
                           class="btn-pay-now ms-3"
                           onclick="event.stopPropagation();">
                            <i class="bi bi-lock-fill"></i> Pay Now
                        </a>
                        <?php endif; ?>
                    </div>

            <?php if ($isPendingOnline): ?>
            </div>
            <?php else: ?>
            </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Bag Button -->
<a href="../shop/cart.php" class="floating-cart position-fixed text-white text-decoration-none d-flex align-items-center gap-3">
    <i class="bi bi-bag-fill fs-5"></i>
    <span class="fw-bold small">Bag
        <?php if ($cartCount > 0): ?>
            <span style="background:var(--primary-grad);color:white;font-size:0.65rem;font-weight:800;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-left:4px;"><?= $cartCount ?></span>
        <?php endif; ?>
    </span>
</a>

</body>
</html>