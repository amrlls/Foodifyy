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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Toggle status active/inactive
    if ($_POST['action'] === 'toggle_status') {
        $targetId  = intval($_POST['user_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['active', 'inactive']) && $targetId !== $userId) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newStatus, $targetId);
            echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot change own status']);
        }
        exit;
    }

    // Update role
    if ($_POST['action'] === 'update_role') {
        $targetId = intval($_POST['user_id'] ?? 0);
        $newRole  = $_POST['role'] ?? '';
        if (in_array($newRole, ['customer', 'staff']) && $targetId !== $userId) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newRole, $targetId);
            echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    // Update user info
    if ($_POST['action'] === 'update_user') {
        $targetId = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $role     = $_POST['role'] ?? 'customer';
        $status   = $_POST['status'] ?? 'active';

        if ($targetId && $username && $email) {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, address=?, role=?, status=? WHERE user_id=?");
            $stmt->bind_param("ssssssi", $username, $email, $phone, $address, $role, $status, $targetId);
            echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }
}

// Filters
$search    = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

$where  = ["user_id != ?"];
$params = [$userId];
$types  = 'i';

if ($search !== '') {
    $where[]  = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}
if ($roleFilter !== 'all') {
    $where[]  = "role = ?";
    $params[] = $roleFilter;
    $types   .= 's';
}
if ($statusFilter !== 'all') {
    $where[]  = "status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

$whereSQL = implode(' AND ', $where);
$sql = "SELECT * FROM users WHERE $whereSQL ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count = count($users);

$total_customers = $conn->query("SELECT COUNT(*) as t FROM users WHERE role='customer'")->fetch_assoc()['t'] ?? 0;
$total_staff     = $conn->query("SELECT COUNT(*) as t FROM users WHERE role='staff'")->fetch_assoc()['t'] ?? 0;
$total_inactive  = $conn->query("SELECT COUNT(*) as t FROM users WHERE status='inactive'")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – Admin</title>
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
        .sidebar-logo .admin-badge { margin-left: 1rem; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: var(--primary-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; opacity: 0.8; }
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

        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
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
        .user-row { display: grid; grid-template-columns: 52px 1fr 160px 90px 100px 150px; gap: 1rem; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f8f9fa; transition: 0.2s; }
        .user-row:last-child { border-bottom: none; }
        .user-row:hover { background: #fafafa; }
        .user-row.header { background: #f8f9fa; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; padding: 0.8rem 1.5rem; }
        .user-row > div { text-align: center; }
        .user-row > div:nth-child(1), .user-row > div:nth-child(2),
        .user-row.header > div:nth-child(1), .user-row.header > div:nth-child(2) { text-align: left; }

        .user-avatar { width: 40px; height: 40px; border-radius: 12px; object-fit: cover; }
        .avatar-placeholder { width: 40px; height: 40px; border-radius: 12px; background: var(--primary-grad); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 0.9rem; }
        .user-name { font-weight: 700; font-size: 0.9rem; color: #1A1C1E; }
        .user-email { font-size: 0.75rem; color: #bdc3c7; margin-top: 2px; }

        .role-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .role-customer { background: rgba(116,185,255,0.15); color: #0984e3; }
        .role-staff    { background: rgba(108,92,231,0.12);  color: #6c5ce7; }
        .role-admin    { background: rgba(255,107,107,0.12); color: #e74c3c; }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .status-active   { background: rgba(46,204,113,0.12); color: #27ae60; }
        .status-inactive { background: rgba(225,112,85,0.12); color: #e17055; }

        .action-btns { display: flex; gap: 6px; justify-content: center; }
        .btn-edit { padding: 6px 14px; border-radius: 10px; border: 1.5px solid #eee; background: white; color: #1A1C1E; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit:hover { background: #1A1C1E; color: white; border-color: #1A1C1E; }

        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state i { font-size: 3rem; color: #eee; display: block; margin-bottom: 1rem; }
        .empty-state p { color: #bdc3c7; font-size: 0.9rem; }

        /* Edit Panel */
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

        .form-label-sm { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #7f8c8d; margin-bottom: 6px; display: block; }
        .form-ctrl { width: 100%; padding: 11px 14px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #fafafa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 500; transition: 0.2s; margin-bottom: 1rem; }
        .form-ctrl:focus { border-color: var(--accent); outline: none; background: white; }
        .btn-save { width: 100%; padding: 13px; border: none; border-radius: 14px; background: var(--primary-grad); color: white; font-weight: 800; font-size: 0.9rem; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; box-shadow: 0 6px 16px rgba(255,107,107,0.25); margin-top: 0.5rem; }
        .btn-save:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-deactivate { width: 100%; padding: 11px; border: 1.5px solid #eee; border-radius: 14px; background: white; color: #e17055; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; margin-top: 0.6rem; }
        .btn-deactivate:hover { background: #fff0f0; }
        .btn-activate { width: 100%; padding: 11px; border: 1.5px solid rgba(46,204,113,0.3); border-radius: 14px; background: rgba(46,204,113,0.08); color: #27ae60; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; margin-top: 0.6rem; }
        .btn-activate:hover { background: rgba(46,204,113,0.15); }

        .alert-toast { padding: 14px 20px; border-radius: 16px; font-weight: 700; font-size: 0.88rem; margin-bottom: 1.5rem; display: flex; align-items: center; animation: slideDown 0.3s ease; }
        .alert-toast.success { background: rgba(0,184,148,0.12); color: #00b894; border: 1px solid rgba(0,184,148,0.2); }
        .alert-toast.danger  { background: rgba(225,112,85,0.12); color: #e17055; border: 1px solid rgba(225,112,85,0.2); }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
        <div class="admin-badge">Admin Panel</div>
    </div>
    <div class="sidebar-greet-box"><p>System control center</p></div>
    <ul class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <li><a href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <div class="nav-section-label">Manage</div>
        <li><a href="manage_items.php"><i class="bi bi-bag-heart-fill"></i> Manage Items</a></li>
        <li><a href="manage_orders.php"><i class="bi bi-receipt"></i> Manage Orders</a></li>
        <li><a href="manage_users.php" class="active"><i class="bi bi-people-fill"></i> Manage Users</a></li>
        <li><a href="manage_recipes.php"><i class="bi bi-book-fill"></i> Manage Recipes</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="profile.php" class="text-decoration-none d-block">
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

<!-- Edit Panel -->
<div class="detail-overlay" id="detailOverlay" onclick="closePanel()"></div>
<div class="detail-panel" id="detailPanel">
    <div class="panel-header">
        <h4>Edit User</h4>
        <button class="btn-close-panel" onclick="closePanel()"><i class="bi bi-x-lg"></i></button>
    </div>

    <input type="hidden" id="editUserId">

    <label class="form-label-sm">Username</label>
    <input type="text" id="editUsername" class="form-ctrl" placeholder="Username">

    <label class="form-label-sm">Email</label>
    <input type="email" id="editEmail" class="form-ctrl" placeholder="Email">

    <label class="form-label-sm">Phone</label>
    <input type="text" id="editPhone" class="form-ctrl" placeholder="Phone">

    <label class="form-label-sm">Address</label>
    <textarea id="editAddress" class="form-ctrl" rows="2" placeholder="Address"></textarea>

    <label class="form-label-sm">Role</label>
    <select id="editRole" class="form-ctrl">
        <option value="customer">Customer</option>
        <option value="staff">Staff</option>
    </select>

    <button class="btn-save" onclick="saveUser()"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
    <button class="btn-deactivate" id="toggleStatusBtn" onclick="toggleStatus()">
        <i class="bi bi-slash-circle me-2"></i>Deactivate User
    </button>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Manage Users</h1>
            <p><?= $count ?> user<?= $count !== 1 ? 's' : '' ?> found</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-card">
            <div><div class="label">Customers</div><div class="value"><?= $total_customers ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(116,185,255,0.15);color:#0984e3;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="label">Staff</div><div class="value"><?= $total_staff ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(108,92,231,0.12);color:#6c5ce7;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-person-badge-fill"></i></div>
        </div>
        <div class="stat-card">
            <div><div class="label">Inactive</div><div class="value" style="color:#e17055;"><?= $total_inactive ?></div></div>
            <div style="width:44px;height:44px;border-radius:14px;background:rgba(225,112,85,0.12);color:#e17055;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="bi bi-slash-circle"></i></div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" id="filterForm" style="display:contents;">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
            </div>
            <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"      <?= $roleFilter === 'all'      ? 'selected' : '' ?>>All Roles</option>
                <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customer</option>
                <option value="staff"    <?= $roleFilter === 'staff'    ? 'selected' : '' ?>>Staff</option>
            </select>
            <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"      <?= $statusFilter === 'all'      ? 'selected' : '' ?>>All Status</option>
                <option value="active"   <?= $statusFilter === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </form>
        <span class="count-badge"><?= $count ?> results</span>
    </div>

    <div class="table-card">
        <div class="user-row header">
            <div></div><div>User</div><div>Email</div><div>Role</div><div>Status</div><div>Actions</div>
        </div>
        <?php if (empty($users)): ?>
        <div class="empty-state"><i class="bi bi-people"></i><p>No users found.</p></div>
        <?php else: ?>
        <?php foreach ($users as $u):
            $avatarSrc  = getImageSrc($u['profile_image'] ?? '', '../../assets/images/profiles/');
            $roleClass  = 'role-' . ($u['role'] ?? 'customer');
            $statClass  = 'status-' . ($u['status'] ?? 'active');
            $statLabel  = ucfirst($u['status'] ?? 'active');
            $initial    = strtoupper(substr($u['username'], 0, 1));
        ?>
        <div class="user-row" id="urow-<?= $u['user_id'] ?>">
            <div>
                <?php if ($avatarSrc): ?>
                    <img src="<?= htmlspecialchars($avatarSrc) ?>" class="user-avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?= $initial ?></div>
                <?php endif; ?>
            </div>
            <div>
                <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                <div class="user-email"><?= htmlspecialchars($u['phone'] ?? '-') ?></div>
            </div>
            <div style="font-size:0.82rem;font-weight:600;color:#7f8c8d;text-align:center;"><?= htmlspecialchars($u['email']) ?></div>
            <div><span class="role-badge <?= $roleClass ?>"><?= ucfirst($u['role']) ?></span></div>
            <div><span class="status-badge <?= $statClass ?>"><?= $statLabel ?></span></div>
            <div class="action-btns">
                <button class="btn-edit" onclick="openPanel(<?= htmlspecialchars(json_encode($u)) ?>)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentUser = null;

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(window._st);
        window._st = setTimeout(() => document.getElementById('filterForm').submit(), 600);
    });
}

function openPanel(u) {
    currentUser = u;
    document.getElementById('editUserId').value   = u.user_id;
    document.getElementById('editUsername').value = u.username ?? '';
    document.getElementById('editEmail').value    = u.email ?? '';
    document.getElementById('editPhone').value    = u.phone ?? '';
    document.getElementById('editAddress').value  = u.address ?? '';
    document.getElementById('editRole').value     = u.role ?? 'customer';

    const btn = document.getElementById('toggleStatusBtn');
    if ((u.status ?? 'active') === 'active') {
        btn.className = 'btn-deactivate';
        btn.innerHTML = '<i class="bi bi-slash-circle me-2"></i>Deactivate User';
    } else {
        btn.className = 'btn-activate';
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Activate User';
    }

    document.getElementById('detailOverlay').classList.add('active');
    document.getElementById('detailPanel').classList.add('open');
}

function closePanel() {
    document.getElementById('detailOverlay').classList.remove('active');
    document.getElementById('detailPanel').classList.remove('open');
    currentUser = null;
}

async function saveUser() {
    if (!currentUser) return;
    const fd = new FormData();
    fd.append('action',   'update_user');
    fd.append('user_id',  document.getElementById('editUserId').value);
    fd.append('username', document.getElementById('editUsername').value);
    fd.append('email',    document.getElementById('editEmail').value);
    fd.append('phone',    document.getElementById('editPhone').value);
    fd.append('address',  document.getElementById('editAddress').value);
    fd.append('role',     document.getElementById('editRole').value);
    fd.append('status',   currentUser.status ?? 'active');

    const res  = await fetch('manage_users.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        closePanel();
        showToast('success', 'User updated successfully!');
        setTimeout(() => location.reload(), 1000);
    }
}

async function toggleStatus() {
    if (!currentUser) return;
    const newStatus = (currentUser.status ?? 'active') === 'active' ? 'inactive' : 'active';
    const fd = new FormData();
    fd.append('action',  'toggle_status');
    fd.append('user_id', currentUser.user_id);
    fd.append('status',  newStatus);

    const res  = await fetch('manage_users.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        currentUser.status = newStatus;
        closePanel();
        showToast(newStatus === 'inactive' ? 'danger' : 'success',
            newStatus === 'inactive' ? 'User deactivated.' : 'User activated.');
        setTimeout(() => location.reload(), 1000);
    }
}

function showToast(type, msg) {
    const toast = document.createElement('div');
    toast.className = `alert-toast ${type}`;
    toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'slash-circle'} me-2"></i>${msg}`;
    document.querySelector('.main-content').prepend(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}
</script>
</body>
</html>