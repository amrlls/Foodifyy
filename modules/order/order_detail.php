<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId  = $_SESSION['user_id'];
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { header('Location: my_orders.php'); exit; }

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

// Cart count
$cartCount = 0;
$stmt_cart = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt_cart->bind_param("i", $userId);
$stmt_cart->execute();
$cartCount = (int)($stmt_cart->get_result()->fetch_assoc()['total'] ?? 0);

// Fetch order
$stmt = $conn->prepare("
    SELECT o.*, p.method, p.status as pay_status, p.transaction_ref, p.paid_at
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header('Location: my_orders.php'); exit; }

// Fetch order items
$stmtItems = $conn->prepare("
    SELECT oi.quantity, oi.unit_price,
           i.name, i.image, i.category, i.unit
    FROM order_items oi
    JOIN items i ON i.item_id = oi.item_id
    WHERE oi.order_id = ?
");
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$orderItems = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

$catColors = [
    'Vegetables'     => ['grad' => 'linear-gradient(135deg,#1e5128,#4e944f)', 'icon' => 'bi-flower1'],
    'Fruits'         => ['grad' => 'linear-gradient(135deg,#c72b2b,#ff6b6b)', 'icon' => 'bi-apple'],
    'Meat'           => ['grad' => 'linear-gradient(135deg,#7b2d00,#d45700)', 'icon' => 'bi-egg'],
    'Meat & Poultry' => ['grad' => 'linear-gradient(135deg,#7b2d00,#d45700)', 'icon' => 'bi-egg'],
    'Seafood'        => ['grad' => 'linear-gradient(135deg,#0a3d62,#1e90ff)', 'icon' => 'bi-water'],
    'Dairy'          => ['grad' => 'linear-gradient(135deg,#4a4a8a,#a29bfe)', 'icon' => 'bi-cup-straw'],
    'Grains'         => ['grad' => 'linear-gradient(135deg,#7d6608,#f9ca24)', 'icon' => 'bi-grid'],
    'Spices'         => ['grad' => 'linear-gradient(135deg,#6d1a36,#e84393)', 'icon' => 'bi-lightning'],
    'Beverages'      => ['grad' => 'linear-gradient(135deg,#1a4a6d,#00b894)', 'icon' => 'bi-cup'],
];

$isPendingOnline = ($order['status'] === 'pending' && $order['method'] === 'online_banking' && $order['transaction_ref']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?> – Foodify</title>
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

        /* Breadcrumb */
        .breadcrumb-bar {
            padding: 1.2rem 4rem; border-bottom: 1px solid #f5f5f5;
            display: flex; align-items: center; gap: 8px;
        }
        .breadcrumb-bar a { color: #bdc3c7; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .breadcrumb-bar a:hover { color: var(--accent); }
        .breadcrumb-bar span { color: #bdc3c7; font-size: 0.85rem; }
        .breadcrumb-bar .current { color: #1A1C1E; font-weight: 700; }

        .content-body { padding: 2.5rem 4rem; }

        /* Info card */
        .info-card {
            background: white; border-radius: 20px; padding: 1.8rem;
            border: 1px solid #f0f0f0; box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        .info-card h6 {
            font-weight: 800; font-size: 0.8rem; text-transform: uppercase;
            letter-spacing: 1px; color: #bdc3c7; margin-bottom: 1.2rem;
        }

        /* Status badge */
        .status-badge {
            padding: 6px 16px; border-radius: 100px;
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .status-badge.pending    { background: rgba(116,185,255,0.15); color: #0984e3; }
        .status-badge.processing { background: rgba(253,203,110,0.2);  color: #e17055; }
        .status-badge.completed  { background: rgba(0,184,148,0.15);   color: #00b894; }

        /* Detail rows */
        .detail-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.65rem 0; border-bottom: 1px solid #f8f9fa; font-size: 0.88rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: #7f8c8d; font-weight: 600; }
        .detail-row .value { font-weight: 700; color: #1A1C1E; text-align: right; }

        /* Order items */
        .order-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.9rem 0; border-bottom: 1px solid #f8f9fa;
        }
        .order-item:last-child { border-bottom: none; }
        .item-img {
            width: 56px; height: 56px; border-radius: 12px; overflow: hidden;
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }
        .item-img i   { font-size: 1.4rem; color: white; }
        .item-name  { font-weight: 800; font-size: 0.88rem; color: #1A1C1E; }
        .item-meta  { font-size: 0.75rem; color: #bdc3c7; margin-top: 3px; }
        .item-price { font-weight: 800; font-size: 0.9rem; white-space: nowrap;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* Summary totals */
        .total-row {
            display: flex; justify-content: space-between;
            font-size: 0.88rem; margin-bottom: 0.5rem;
        }
        .total-row span:first-child { color: #7f8c8d; }
        .total-row span:last-child  { font-weight: 700; }
        .total-row.grand { font-size: 1.1rem; font-weight: 800; padding-top: 0.8rem; margin-top: 0.3rem; border-top: 1px solid #f5f5f5; }
        .total-row.grand span:last-child {
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* Pay Now button */
        .btn-pay-now {
            width: 100%; padding: 14px; border: none; border-radius: 16px;
            background: var(--primary-grad); color: white;
            font-weight: 800; font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: 0.3s; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 1rem; box-shadow: 0 8px 20px rgba(255,107,107,0.25);
        }
        .btn-pay-now:hover { opacity: 0.88; transform: translateY(-2px); color: white; }

        /* Floating bag */
        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem;
            border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 999; bottom: 30px; right: 30px; transition: 0.3s;
        }
        .floating-cart:hover { background: #2d2f31; transform: translateY(-3px); }

        @media (max-width: 992px) {
            .breadcrumb-bar, .content-body { padding: 1.5rem 2rem; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Order details.</p></div>
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
    <div class="breadcrumb-bar">
        <a href="my_orders.php">My Orders</a>
        <span>/</span>
        <span class="current">Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></span>
    </div>

    <div class="content-body">
        <div class="row g-4">

            <!-- Left column -->
            <div class="col-lg-7">

                <!-- Order status -->
                <div class="info-card">
                    <h6>Order Status</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-weight:800;font-size:1.1rem;margin-bottom:4px;">
                                Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div style="font-size:0.8rem;color:#bdc3c7;">
                                <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <span class="status-badge <?= $order['status'] ?>">
                            <i class="bi bi-circle-fill" style="font-size:0.5rem;"></i>
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>

                    <?php if ($isPendingOnline): ?>
                    <a href="https://toyyibpay.com/<?= htmlspecialchars($order['transaction_ref']) ?>" class="btn-pay-now">
                        <i class="bi bi-lock-fill"></i> Complete Payment
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <div class="info-card">
                    <h6>Items Ordered</h6>
                    <?php foreach ($orderItems as $item):
                        $cs     = $catColors[$item['category']] ?? ['grad' => 'linear-gradient(135deg,#2D3436,#000)', 'icon' => 'bi-bag'];
                        $imgSrc = getImageSrc($item['image'] ?? '', '../../assets/images/items/');
                    ?>
                    <div class="order-item">
                        <div class="item-img" style="background:<?= $cs['grad'] ?>;">
                            <?php if ($imgSrc): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <i class="bi <?= $cs['icon'] ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-meta">
                                x<?= $item['quantity'] ?><?= $item['unit'] ? ' · per ' . htmlspecialchars($item['unit']) : '' ?>
                                · RM <?= number_format($item['unit_price'], 2) ?> each
                            </div>
                        </div>
                        <div class="item-price">RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Totals -->
                    <div class="mt-3">
                        <?php $subtotal = array_sum(array_map(fn($r) => $r['unit_price'] * $r['quantity'], $orderItems)); ?>
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="total-row">
                            <span>Delivery</span>
                            <span><?= $order['delivery_fee'] > 0 ? 'RM ' . number_format($order['delivery_fee'], 2) : 'Free' ?></span>
                        </div>
                        <div class="total-row grand">
                            <span>Total</span>
                            <span>RM <?= number_format($order['total_price'], 2) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right column -->
            <div class="col-lg-5">

                <!-- Shipping info -->
                <div class="info-card">
                    <h6>Shipping Details</h6>
                    <div class="detail-row">
                        <span class="label">Name</span>
                        <span class="value"><?= htmlspecialchars($order['shipping_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Phone</span>
                        <span class="value"><?= htmlspecialchars($order['shipping_phone']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Delivery</span>
                        <span class="value"><?= $order['shipping_address'] === 'Self Pickup' ? 'Self Pickup' : 'Home Delivery' ?></span>
                    </div>
                    <?php if ($order['shipping_address'] !== 'Self Pickup'): ?>
                    <div class="detail-row">
                        <span class="label">Address</span>
                        <span class="value" style="max-width:60%;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Payment info -->
                <div class="info-card">
                    <h6>Payment Details</h6>
                    <div class="detail-row">
                        <span class="label">Method</span>
                        <span class="value"><?= $order['method'] === 'cod' ? 'Cash on Delivery' : 'Online Banking' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status</span>
                        <span class="value">
                            <?php if ($order['pay_status'] === 'success'): ?>
                                <span style="color:#00b894;font-weight:800;">Paid</span>
                            <?php elseif ($order['pay_status'] === 'failed'): ?>
                                <span style="color:#e17055;font-weight:800;">Failed</span>
                            <?php else: ?>
                                <span style="color:#0984e3;font-weight:800;">Pending</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($order['paid_at']): ?>
                    <div class="detail-row">
                        <span class="label">Paid At</span>
                        <span class="value"><?= date('d M Y, h:i A', strtotime($order['paid_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['transaction_ref'] && $order['pay_status'] === 'success'): ?>
                    <div class="detail-row">
                        <span class="label">Reference</span>
                        <span class="value" style="font-size:0.78rem;"><?= htmlspecialchars($order['transaction_ref']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <a href="my_orders.php" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;border:1.5px solid #eee;border-radius:16px;text-decoration:none;color:#1A1C1E;font-weight:700;font-size:0.88rem;transition:all 0.2s;background:white;" onmouseover="this.style.background='#1A1C1E';this.style.color='white';this.style.borderColor='#1A1C1E';" onmouseout="this.style.background='white';this.style.color='#1A1C1E';this.style.borderColor='#eee';">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>

            </div>
        </div>
    </div>
</div>

<!-- Floating Bag -->
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