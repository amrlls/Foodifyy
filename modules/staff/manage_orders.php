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
    header('Location: ../../dashboard.php');
    exit();
}

$staff_username = $user['username'];
$profile_img    = $user['profile_image'];

// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $orderId   = intval($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['pending', 'processing', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();

        if ($newStatus === 'completed') {
            $stmtPay = $conn->prepare("UPDATE payments SET status = 'success', paid_at = NOW() WHERE order_id = ? AND status != 'success'");
            $stmtPay->bind_param("i", $orderId);
            $stmtPay->execute();
        }
        if ($newStatus === 'cancelled') {
            $stmtPay = $conn->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status != 'success'");
            $stmtPay->bind_param("i", $orderId);
            $stmtPay->execute();
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filters
$search        = trim($_GET['search'] ?? '');
$statusFilter  = $_GET['status'] ?? 'all';
$dateFilter    = $_GET['date_filter'] ?? 'all';

$where  = ["1=1"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(u.username LIKE ? OR o.shipping_name LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'sss';
}
if ($statusFilter !== 'all') {
    $where[]  = "o.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}
if ($dateFilter !== 'all') {
    match($dateFilter) {
        'today' => $where[] = "DATE(o.created_at) = CURDATE()",
        'week'  => $where[] = "YEARWEEK(o.created_at, 1) = YEARWEEK(NOW(), 1)",
        'month' => $where[] = "MONTH(o.created_at) = MONTH(NOW()) AND YEAR(o.created_at) = YEAR(NOW())",
        'year'  => $where[] = "YEAR(o.created_at) = YEAR(NOW())",
        default => null
    };
}

$whereSQL = implode(' AND ', $where);
$sql = "SELECT o.*, u.username as customer_name, p.method as payment_method, p.status as payment_status
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN payments p ON p.order_id = o.order_id
        WHERE $whereSQL
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count  = count($orders);

// Stats
$total_orders      = $conn->query("SELECT COUNT(*) as t FROM orders")->fetch_assoc()['t'] ?? 0;
$pending_count     = $conn->query("SELECT COUNT(*) as t FROM orders WHERE status='pending'")->fetch_assoc()['t'] ?? 0;
$processing_count  = $conn->query("SELECT COUNT(*) as t FROM orders WHERE status='processing'")->fetch_assoc()['t'] ?? 0;
$completed_count   = $conn->query("SELECT COUNT(*) as t FROM orders WHERE status='completed'")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders – Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); --sidebar-dark: #1A1C1E; --accent: #FF8E53; --sidebar-w: 280px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; color: #1A1C1E; }

        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh; background: var(--sidebar-dark); color: white; padding: 2.5rem 1.5rem; z-index: 1000; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); overflow-y: auto; }
        .sidebar::-webkit-scrollbar { display: none; }
        .sidebar-logo h2 { font-family: 'Playfair Display', serif; font-weight: 900; letter-spacing: -1px; background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; padding-left: 1rem; }
        .sidebar-logo .staff-badge { margin-left: 1rem; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: var(--primary-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; opacity: 0.8; }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; margin-top: 0; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400; }
        .nav-section-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #555; padding: 0 18px; margin: 1.2rem 0 0.3rem; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 15px; padding: 14px 18px; color: #949494; text-decoration: none; border-radius: 16px; font-weight: 500; transition: all 0.3s ease; }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-nav a i { font-size: 1rem; width: 20px; text-align: center; }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px; border-radius: 20px; transition: all 0.2s ease; cursor: pointer; }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        .main-content { margin-left: var(--sidebar-w); padding: 3rem 4rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-weight: 900; }
        .page-header p { color: #7f8c8d; margin-top: 4px; font-size: 0.9rem; }

        .stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 18px; padding: 1.2rem 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; }
        .stat-card .label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; margin-bottom: 4px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 900; color: #1A1C1E; }

        .filter-bar { background: white; border-radius: 20px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.04); flex-wrap: wrap; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap input { width: 100%; padding: 10px 16px 10px 42px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 500; transition: 0.2s; }
        .search-wrap input:focus { border-color: var(--accent); outline: none; background: white; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #bdc3c7; }
        .filter-select { padding: 10px 16px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 500; cursor: pointer; }
        .filter-select:focus { border-color: var(--accent); outline: none; }
        .count-badge { background: #f8f9fa; border-radius: 100px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; color: #7f8c8d; }

        .table-card { background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; }
        .order-row { display: grid; grid-template-columns: 110px 1fr 100px 110px 130px 160px; gap: 1rem; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f8f9fa; transition: 0.2s; cursor: pointer; }
        .order-row:last-child { border-bottom: none; }
        .order-row:hover { background: #fafafa; }
        .order-row.header { background: #f8f9fa; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; padding: 0.8rem 1.5rem; cursor: default; }
        .order-row > div { text-align: center; }
        .order-row > div:nth-child(1), .order-row > div:nth-child(2),
        .order-row.header > div:nth-child(1), .order-row.header > div:nth-child(2) { text-align: left; }

        .order-id { font-weight: 800; font-size: 0.88rem; color: #1A1C1E; }
        .order-amount { font-weight: 800; font-size: 0.9rem; background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .status-badge { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 4px 14px; border-radius: 100px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; min-width: 100px; }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
        .status-pending    { background: rgba(116,185,255,0.15); color: #0984e3; }
        .status-processing { background: rgba(253,203,110,0.2);  color: #e17055; }
        .status-completed  { background: rgba(46,204,113,0.12);  color: #27ae60; }
        .status-cancelled  { background: rgba(214,48,49,0.12);   color: #d63031; }

        .pay-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.7rem; font-weight: 700; }
        .pay-cod     { background: rgba(108,92,231,0.12); color: #6c5ce7; }
        .pay-online  { background: rgba(9,132,227,0.12);  color: #0984e3; }
        .pay-success { background: rgba(46,204,113,0.12); color: #27ae60; }
        .pay-failed  { background: rgba(225,112,85,0.12); color: #e17055; }

        .btn-view-order { padding: 6px 14px; border-radius: 10px; border: 1.5px solid #eee; background: white; color: #1A1C1E; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-view-order:hover { background: #1A1C1E; color: white; border-color: #1A1C1E; }

        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state i { font-size: 3rem; color: #eee; display: block; margin-bottom: 1rem; }
        .empty-state p { color: #bdc3c7; font-size: 0.9rem; }

        .detail-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); z-index: 9998; display: none; }
        .detail-overlay.active { display: block; }
        .detail-panel { position: fixed; right: -520px; top: 0; width: 500px; height: 100vh; background: white; z-index: 9999; overflow-y: auto; box-shadow: -20px 0 60px rgba(0,0,0,0.15); transition: right 0.35s cubic-bezier(0.34,1.06,0.64,1); padding: 2rem; }
        .detail-panel.open { right: 0; }
        .detail-panel::-webkit-scrollbar { width: 4px; }
        .detail-panel::-webkit-scrollbar-thumb { background: #eee; border-radius: 4px; }

        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .panel-header h4 { font-weight: 800; font-size: 1.1rem; }
        .btn-close-panel { background: #f8f9fa; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-close-panel:hover { background: #1A1C1E; color: white; }

        .info-section { background: #f8f9fa; border-radius: 16px; padding: 1.2rem; margin-bottom: 1rem; }
        .info-section h6 { font-weight: 800; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; margin-bottom: 0.8rem; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0; font-size: 0.88rem; }
        .info-row .lbl { color: #7f8c8d; font-weight: 600; }
        .info-row .val { font-weight: 700; color: #1A1C1E; }
        .info-row .val.accent { background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .pay-status-success { font-weight: 700; color: #27ae60; background: rgba(46,204,113,0.12); padding: 3px 10px; border-radius: 100px; font-size: 0.8rem; }
        .pay-status-pending  { font-weight: 700; color: #0984e3; background: rgba(116,185,255,0.15); padding: 3px 10px; border-radius: 100px; font-size: 0.8rem; }
        .pay-status-failed   { font-weight: 700; color: #d63031; background: rgba(214,48,49,0.12); padding: 3px 10px; border-radius: 100px; font-size: 0.8rem; }

        .item-line { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        .item-line:last-child { border-bottom: none; }

        .status-select { padding: 10px 16px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 600; cursor: pointer; width: 100%; transition: 0.2s; }
        .status-select:focus { border-color: var(--accent); outline: none; }
        .btn-update-status { width: 100%; padding: 12px; border: none; border-radius: 14px; background: var(--primary-grad); color: white; font-weight: 800; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: 0.3s; margin-top: 0.8rem; box-shadow: 0 6px 16px rgba(255,107,107,0.25); }
        .btn-update-status:hover { opacity: 0.88; transform: translateY(-1px); }

        .alert-toast { padding: 14px 20px; border-radius: 16px; font-weight: 700; font-size: 0.88rem; margin-bottom: 1.5rem; display: flex; align-items: center; animation: slideDown 0.3s ease; }
        .alert-toast.success { background: rgba(0,184,148,0.12); color: #00b894; border: 1px solid rgba(0,184,148,0.2); }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
        <div class="staff-badge">Staff Panel</div>
    </div>
    <div class="sidebar-greet-box"><p>Operations control center</p></div>
    <ul class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <div class="nav-section-label">Manage</div>
        <li><a href="manage_items.php"><i class="bi bi-bag-heart-fill"></i> Manage Items</a></li>
        <li><a href="manage_orders.php" class="active"><i class="bi bi-receipt"></i> Manage Orders</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="../staff/profile.php" class="text-decoration-none d-block">
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
                    <div class="text-white fw-bold small text-truncate" style="max-width:130px;"><?= htmlspecialchars($staff_username) ?></div>
                    <div style="font-size:0.65rem;color:var(--accent);font-weight:600;text-transform:uppercase;">Staff</div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Order Detail Panel -->
<div class="detail-overlay" id="detailOverlay" onclick="closePanel()"></div>
<div class="detail-panel" id="detailPanel">
    <div class="panel-header">
        <h4 id="panelOrderId">Order Details</h4>
        <button class="btn-close-panel" onclick="closePanel()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="info-section">
        <h6>Customer Info</h6>
        <div class="info-row"><span class="lbl">Name</span><span class="val" id="dShippingName">-</span></div>
        <div class="info-row"><span class="lbl">Phone</span><span class="val" id="dShippingPhone">-</span></div>
        <div class="info-row"><span class="lbl">Address</span><span class="val" id="dShippingAddress" style="text-align:right;max-width:60%;">-</span></div>
    </div>
    <div class="info-section">
        <h6>Order Info</h6>
        <div class="info-row"><span class="lbl">Order Date</span><span class="val" id="dCreatedAt">-</span></div>
        <div class="info-row"><span class="lbl">Payment</span><span class="val" id="dPaymentMethod">-</span></div>
        <div class="info-row"><span class="lbl">Payment Status</span><span class="val" id="dPaymentStatus">-</span></div>
        <div class="info-row"><span class="lbl">Delivery Fee</span><span class="val" id="dDeliveryFee">-</span></div>
        <div class="info-row"><span class="lbl">Total</span><span class="val accent" id="dTotal">-</span></div>
    </div>
    <div class="info-section">
        <h6>Order Items</h6>
        <div id="dItems"><span style="color:#bdc3c7;font-size:0.85rem;">Loading...</span></div>
    </div>
    <div class="info-section">
        <h6>Update Status</h6>
        <select id="dStatusSelect" class="status-select">
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button class="btn-update-status" onclick="updateStatus()">
            <i class="bi bi-check-lg me-2"></i>Update Status
        </button>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Manage Orders</h1>
            <p><?= $count ?> order<?= $count !== 1 ? 's' : '' ?> found</p>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert-toast success"><i class="bi bi-check-circle-fill me-2"></i> Order status updated!</div>
    <?php endif; ?>

    <div class="stat-row">
        <div class="stat-card">
            <div><div class="label">Total Orders</div><div class="value"><?= $total_orders ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(255,107,107,0.12);color:#FF6B6B;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-receipt"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="label">Pending</div><div class="value" style="color:#0984e3;"><?= $pending_count ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(116,185,255,0.15);color:#0984e3;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-hourglass-split"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="label">Processing</div><div class="value" style="color:#e17055;"><?= $processing_count ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(253,203,110,0.2);color:#e17055;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-clock-history"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="label">Completed</div><div class="value" style="color:#27ae60;"><?= $completed_count ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(46,204,113,0.12);color:#27ae60;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-check-circle-fill"></i></div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" id="filterForm" style="display:contents;">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search by order ID, customer..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
            </div>
            <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"        <?= $statusFilter === 'all'        ? 'selected' : '' ?>>All Status</option>
                <option value="pending"    <?= $statusFilter === 'pending'    ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="completed"  <?= $statusFilter === 'completed'  ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled"  <?= $statusFilter === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <select name="date_filter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"   <?= $dateFilter === 'all'   ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week"  <?= $dateFilter === 'week'  ? 'selected' : '' ?>>This Week</option>
                <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>This Month</option>
                <option value="year"  <?= $dateFilter === 'year'  ? 'selected' : '' ?>>This Year</option>
            </select>
        </form>
        <span class="count-badge"><?= $count ?> results</span>
    </div>

    <div class="table-card">
        <div class="order-row header">
            <div>Order ID</div><div>Customer</div><div>Total</div><div>Status</div><div>Payment</div><div>Date / Action</div>
        </div>
        <?php if (empty($orders)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>No orders found.</p></div>
        <?php else: ?>
        <?php foreach ($orders as $order):
            $statusClass = match($order['status']) {
                'pending'    => 'status-pending',
                'processing' => 'status-processing',
                'completed'  => 'status-completed',
                'cancelled'  => 'status-cancelled',
                default      => 'status-pending'
            };
            $payClass = 'pay-online';
            if ($order['payment_method'] === 'cod') $payClass = 'pay-cod';
            elseif ($order['payment_status'] === 'success') $payClass = 'pay-success';
            elseif ($order['payment_status'] === 'failed')  $payClass = 'pay-failed';
            $payLabel = $order['payment_method'] === 'cod' ? 'COD' : 'Online Banking';
        ?>
        <div class="order-row" onclick="openPanel(<?= htmlspecialchars(json_encode($order)) ?>)">
            <div><div class="order-id">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></div></div>
            <div><div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($order['shipping_name'] ?? $order['customer_name']) ?></div></div>
            <div><span class="order-amount">RM <?= number_format($order['total_price'], 2) ?></span></div>
            <div><span class="status-badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span></div>
            <div><span class="pay-badge <?= $payClass ?>"><?= $payLabel ?></span></div>
            <div>
                <div style="font-size:0.78rem;color:#bdc3c7;margin-bottom:6px;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                <button class="btn-view-order" onclick="event.stopPropagation(); openPanel(<?= htmlspecialchars(json_encode($order)) ?>)">
                    <i class="bi bi-eye"></i> View
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentOrderId = null;

document.querySelectorAll('.alert-toast').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 3000);
});

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(window._st);
        window._st = setTimeout(() => document.getElementById('filterForm').submit(), 600);
    });
}

function openPanel(order) {
    currentOrderId = order.order_id;
    document.getElementById('panelOrderId').textContent     = 'Order #' + String(order.order_id).padStart(6, '0');
    document.getElementById('dShippingName').textContent    = order.shipping_name || '-';
    document.getElementById('dShippingPhone').textContent   = order.shipping_phone || '-';
    document.getElementById('dShippingAddress').textContent = order.shipping_address || '-';
    document.getElementById('dCreatedAt').textContent       = new Date(order.created_at).toLocaleString('en-MY');
    document.getElementById('dPaymentMethod').textContent   = order.payment_method === 'cod' ? 'Cash on Delivery' : 'Online Banking';

    const payStatus = order.payment_status ? order.payment_status.toLowerCase() : 'pending';
    const payStatusEl = document.getElementById('dPaymentStatus');
    payStatusEl.textContent = payStatus.charAt(0).toUpperCase() + payStatus.slice(1);
    payStatusEl.className = 'pay-status-' + payStatus;

    document.getElementById('dDeliveryFee').textContent = 'RM ' + parseFloat(order.delivery_fee || 0).toFixed(2);
    document.getElementById('dTotal').textContent       = 'RM ' + parseFloat(order.total_price).toFixed(2);
    document.getElementById('dStatusSelect').value      = order.status;

    loadOrderItems(order.order_id);
    document.getElementById('detailOverlay').classList.add('active');
    document.getElementById('detailPanel').classList.add('open');
}

function closePanel() {
    document.getElementById('detailOverlay').classList.remove('active');
    document.getElementById('detailPanel').classList.remove('open');
    currentOrderId = null;
}

async function loadOrderItems(orderId) {
    const container = document.getElementById('dItems');
    container.innerHTML = '<span style="color:#bdc3c7;font-size:0.85rem;">Loading...</span>';
    try {
        const res  = await fetch('get_order_items.php?order_id=' + orderId);
        const data = await res.json();
        if (data.items && data.items.length > 0) {
            container.innerHTML = data.items.map(item =>
                `<div class="item-line">
                    <span style="font-weight:600;">${item.name} <span style="color:#bdc3c7;">x${item.quantity}</span></span>
                    <span style="font-weight:700;">RM ${parseFloat(item.unit_price * item.quantity).toFixed(2)}</span>
                </div>`
            ).join('');
        } else {
            container.innerHTML = '<span style="color:#bdc3c7;font-size:0.85rem;">No items found.</span>';
        }
    } catch (e) {
        container.innerHTML = '<span style="color:#bdc3c7;font-size:0.85rem;">Could not load items.</span>';
    }
}

async function updateStatus() {
    if (!currentOrderId) return;
    const newStatus = document.getElementById('dStatusSelect').value;
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('order_id', currentOrderId);
    fd.append('status', newStatus);

    const res  = await fetch('manage_orders.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        if (newStatus === 'completed') {
            const payEl = document.getElementById('dPaymentStatus');
            payEl.textContent = 'Success';
            payEl.className = 'pay-status-success';
        }
        if (newStatus === 'cancelled') {
            const payEl = document.getElementById('dPaymentStatus');
            payEl.textContent = 'Failed';
            payEl.className = 'pay-status-failed';
        }
        document.querySelectorAll('.order-row').forEach(row => {
            const onclick = row.getAttribute('onclick') || '';
            if (onclick.includes('"order_id":' + currentOrderId) || onclick.includes('"order_id":"' + currentOrderId + '"')) {
                const badge = row.querySelector('.status-badge');
                if (badge) {
                    badge.className = 'status-badge status-' + newStatus;
                    badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                }
            }
        });
        closePanel();
        const toast = document.createElement('div');
        toast.className = 'alert-toast success';
        toast.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Order status updated!';
        document.querySelector('.main-content').prepend(toast);
        setTimeout(() => { toast.style.transition = 'opacity 0.5s'; toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); }, 3000);
        setTimeout(() => location.reload(), 1000);
    }
}
</script>
</body>
</html>