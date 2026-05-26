<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId  = $_SESSION['user_id'];
$orderId = intval($_GET['order_id'] ?? 0);
$status  = $_GET['status'] ?? ''; // 'cod' atau dari ToyyibPay: status_id

if (!$orderId) { header('Location: items.php'); exit; }

// Verify order belongs to user
$stmt = $conn->prepare("SELECT o.*, p.method, p.status as pay_status FROM orders o JOIN payments p ON p.order_id = o.order_id WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header('Location: items.php'); exit; }

// Determine payment result
$isSuccess = false;

if ($status === 'cod') {
    // COD — terus success
    $isSuccess = true;
    // Update order status
    $conn->query("UPDATE orders SET status = 'processing' WHERE order_id = $orderId");

} elseif (isset($_GET['status_id'])) {
    // ToyyibPay return — status_id: 1 = success, 2 = pending, 3 = fail
    $statusId = intval($_GET['status_id']);
    $isSuccess = ($statusId === 1);

    if ($isSuccess) {
        $ref = $_GET['billcode'] ?? '';
        $stmtUpd = $conn->prepare("UPDATE payments SET status = 'success', transaction_ref = ?, paid_at = NOW() WHERE order_id = ?");
        $stmtUpd->bind_param("si", $ref, $orderId);
        $stmtUpd->execute();
        $conn->query("UPDATE orders SET status = 'processing' WHERE order_id = $orderId");
    } else {
        $conn->query("UPDATE payments SET status = 'failed' WHERE order_id = $orderId");
    }
} else {
    // Fallback — check DB status
    $isSuccess = ($order['pay_status'] === 'success' || $order['method'] === 'cod');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isSuccess ? 'Order Confirmed' : 'Payment Failed' ?> – Foodify</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fdfdfd; color: #1A1C1E; }

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
        .sidebar-nav a.active { background: var(--primary-grad); color: white; }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        /* Main */
        .main-content {
            margin-left: var(--sidebar-w); padding: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }

        .result-wrap { width: 100%; max-width: 520px; padding: 2rem; }

        /* Success card */
        .result-card {
            background: white; border-radius: 28px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        }

        .result-banner {
            padding: 2.5rem 2rem; text-align: center;
        }
        .result-banner.success { background: linear-gradient(135deg,#00b894,#00cec9); }
        .result-banner.failed  { background: linear-gradient(135deg,#e17055,#d63031); }

        .result-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(255,255,255,0.2); margin: 0 auto 1rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: white;
            animation: popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popIn { from { transform:scale(0); opacity:0; } to { transform:scale(1); opacity:1; } }

        .result-banner h3 { color: white; font-weight: 900; font-size: 1.5rem; margin-bottom: 0.3rem; }
        .result-banner p  { color: rgba(255,255,255,0.85); font-size: 0.88rem; }

        .result-body { padding: 2rem; }

        /* Order details */
        .detail-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.7rem 0; border-bottom: 1px solid #f5f5f5;
            font-size: 0.88rem;
        }
        .detail-row:last-of-type { border-bottom: none; }
        .detail-row .label { color: #7f8c8d; font-weight: 600; }
        .detail-row .value { font-weight: 800; color: #1A1C1E; }
        .detail-row .value.accent {
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 100px;
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
        }
        .status-badge.processing { background: rgba(253,203,110,0.15); color: #e17055; }
        .status-badge.pending    { background: rgba(116,185,255,0.15); color: #0984e3; }

        /* Buttons */
        .btn-primary-custom {
            width: 100%; padding: 14px; border: none; border-radius: 16px;
            background: var(--primary-grad); color: white;
            font-weight: 800; font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: 0.3s; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-bottom: 0.8rem;
        }
        .btn-primary-custom:hover { opacity: 0.88; transform: translateY(-2px); color: white; }

        .btn-outline-custom {
            width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 16px;
            background: white; color: #1A1C1E; font-weight: 700; font-size: 0.88rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: 0.3s; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-outline-custom:hover { border-color: #1A1C1E; color: #1A1C1E; }

        /* Failed actions */
        .failed-note {
            background: #fff5f3; border-radius: 14px; padding: 1rem 1.2rem;
            font-size: 0.82rem; color: #e17055; font-weight: 600;
            margin-bottom: 1.2rem; text-align: center;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p><?= $isSuccess ? 'Thank you! 🎉' : 'Something went wrong.' ?></p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <li><a href="../order/my_orders.php" class="active"><i class="bi bi-receipt"></i> Orders</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../profile/profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3">
                <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px;height:42px;background:var(--primary-grad);">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white fw-bold small text-truncate" style="max-width:130px;">My Account</div>
                    <div style="font-size:0.65rem;color:var(--accent);font-weight:600;text-transform:uppercase;">Customer</div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Main -->
<div class="main-content">
    <div class="result-wrap">
        <div class="result-card">

            <!-- Banner -->
            <div class="result-banner <?= $isSuccess ? 'success' : 'failed' ?>">
                <div class="result-icon">
                    <i class="bi <?= $isSuccess ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                </div>
                <h3><?= $isSuccess ? 'Order Confirmed!' : 'Payment Failed' ?></h3>
                <p><?= $isSuccess ? 'Your order has been placed successfully.' : 'Your payment could not be processed.' ?></p>
            </div>

            <div class="result-body">
                <?php if ($isSuccess): ?>
                <!-- Order details -->
                <div class="mb-3">
                    <div class="detail-row">
                        <span class="label">Order ID</span>
                        <span class="value">#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Payment Method</span>
                        <span class="value"><?= $order['method'] === 'cod' ? 'Cash on Delivery' : 'Online Banking' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Delivery</span>
                        <span class="value"><?= $order['shipping_address'] === 'Self Pickup' ? 'Self Pickup' : 'Home Delivery' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Total</span>
                        <span class="value accent">RM <?= number_format($order['total_price'], 2) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status</span>
                        <span class="status-badge processing">
                            <i class="bi bi-circle-fill" style="font-size:0.5rem;"></i>
                            Processing
                        </span>
                    </div>
                </div>

                <a href="../order/my_orders.php" class="btn-primary-custom">
                    <i class="bi bi-receipt"></i> View My Orders
                </a>
                <a href="items.php" class="btn-outline-custom">
                    <i class="bi bi-bag-heart"></i> Continue Shopping
                </a>

                <?php else: ?>
                <!-- Failed -->
                <div class="failed-note">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    Your payment was not completed. Your order has been cancelled.
                </div>
                <a href="cart.php" class="btn-primary-custom">
                    <i class="bi bi-arrow-left"></i> Back to Cart
                </a>
                <a href="items.php" class="btn-outline-custom">
                    <i class="bi bi-bag-heart"></i> Continue Shopping
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>