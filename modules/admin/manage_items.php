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

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    $itemId = intval($_POST['item_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    exit;
}

// Handle SAVE (add or edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    $itemId      = intval($_POST['item_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $category    = $_POST['category'] ?? '';
    $unit        = trim($_POST['unit'] ?? '');

    $image = $_POST['existing_image'] ?? '';
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $new_image = uploadToCloudinary($_FILES['item_image']['tmp_name'], 'foodify/items');
        $conn->ping();
        if (!$new_image) {
            $ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $new_image = 'item_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['item_image']['tmp_name'], '../../assets/images/items/' . $new_image);
        }
        $image = $new_image;
    }

    if ($itemId > 0) {
        $stmt = $conn->prepare("UPDATE items SET name=?, description=?, price=?, stock=?, category=?, image=?, unit=? WHERE item_id=?");
        $stmt->bind_param("ssdisssi", $name, $description, $price, $stock, $category, $image, $unit, $itemId);
        if ($stmt->execute()) { header("Location: manage_items.php?status=updated"); exit; }
    } else {
        $stmt = $conn->prepare("INSERT INTO items (name, description, price, stock, category, image, unit) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssdisss", $name, $description, $price, $stock, $category, $image, $unit);
        if ($stmt->execute()) { header("Location: manage_items.php?status=created"); exit; }
    }
}

$search       = trim($_GET['search'] ?? '');
$category     = $_GET['category'] ?? 'all';
$stock_filter = $_GET['stock'] ?? 'all';

$where  = ["1=1"];
$params = [];
$types  = '';

if ($search !== '') { $where[] = "name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($category !== 'all') { $where[] = "category = ?"; $params[] = $category; $types .= 's'; }
if ($stock_filter === 'low') { $where[] = "stock <= 10"; }
if ($stock_filter === 'out') { $where[] = "stock = 0"; }

$whereSQL = implode(' AND ', $where);
$sql = "SELECT * FROM items WHERE $whereSQL ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count = count($items);

$categories = $conn->query("SELECT DISTINCT category FROM items ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items – Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); --sidebar-dark: #1A1C1E; --accent: #FF8E53; --sidebar-w: 280px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; color: #1A1C1E; }

        /* Sidebar — sama sebijik macam dashboard */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05); overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar { display: none; }
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
            -webkit-text-fill-color: transparent; background-clip: text; opacity: 0.8;
        }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; margin-top: 0; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400; }
        .nav-section-label {
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1.5px; color: #555; padding: 0 18px; margin: 1.2rem 0 0.3rem;
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
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s ease; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        /* Main */
        .main-content { margin-left: var(--sidebar-w); padding: 3rem 4rem; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-weight: 900; }
        .page-header p { color: #7f8c8d; margin-top: 4px; font-size: 0.9rem; }

        .btn-add { background: var(--primary-grad); color: white; border: none; padding: 12px 24px; border-radius: 16px; font-weight: 700; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 8px 20px rgba(255,107,107,0.25); }
        .btn-add:hover { opacity: 0.88; transform: translateY(-2px); color: white; }

        .filter-bar { background: white; border-radius: 20px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.04); flex-wrap: wrap; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap input { width: 100%; padding: 10px 16px 10px 42px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 500; transition: 0.2s; }
        .search-wrap input:focus { border-color: var(--accent); outline: none; background: white; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #bdc3c7; }
        .filter-select { padding: 10px 16px; border-radius: 12px; border: 1.5px solid #f0f0f0; background: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.88rem; font-weight: 500; cursor: pointer; }
        .filter-select:focus { border-color: var(--accent); outline: none; }
        .count-badge { background: #f8f9fa; border-radius: 100px; padding: 6px 14px; font-size: 0.8rem; font-weight: 700; color: #7f8c8d; }

        .table-card { background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; }
        .item-row { display: grid; grid-template-columns: 60px 1fr 100px 80px 100px 120px 130px; gap: 1rem; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f8f9fa; transition: 0.2s; }
        .item-row:last-child { border-bottom: none; }
        .item-row:hover { background: #fafafa; }
        .item-row.header { background: #f8f9fa; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; padding: 0.8rem 1.5rem; }
        .item-row > div:not(:nth-child(2)) { display: flex; justify-content: center; align-items: center; text-align: center; }

        .item-img { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #f0f0f0; }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }
        .item-img i { font-size: 1.2rem; color: #bdc3c7; }
        .item-name { font-weight: 700; font-size: 0.9rem; color: #1A1C1E; }
        .item-desc { font-size: 0.75rem; color: #bdc3c7; margin-top: 2px; }

        .category-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .cat-vegetables { background: rgba(46,204,113,0.12); color: #27ae60; }
        .cat-fruits { background: rgba(232,67,147,0.12); color: #e84393; }
        .cat-meat { background: rgba(214,48,49,0.12); color: #d63031; }
        .cat-seafood { background: rgba(9,132,227,0.12); color: #0984e3; }
        .cat-dairy { background: rgba(108,92,231,0.12); color: #6c5ce7; }
        .cat-drygoods { background: rgba(241,196,15,0.15); color: #d4a017; }

        .price-val { font-weight: 800; font-size: 0.9rem; background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .stock-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .stock-ok  { background: rgba(46,204,113,0.12); color: #27ae60; }
        .stock-low { background: rgba(253,203,110,0.2); color: #e17055; }
        .stock-out { background: rgba(225,112,85,0.12); color: #d63031; }

        .action-btns { display: flex; gap: 6px; justify-content: center; }
        .btn-edit { padding: 6px 14px; border-radius: 10px; border: 1.5px solid #eee; background: white; color: #1A1C1E; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit:hover { background: #1A1C1E; color: white; border-color: #1A1C1E; }
        .btn-delete { padding: 6px 14px; border-radius: 10px; border: 1.5px solid #fff0f0; background: #fff0f0; color: #e17055; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-delete:hover { background: #e17055; color: white; border-color: #e17055; }

        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state i { font-size: 3rem; color: #eee; display: block; margin-bottom: 1rem; }
        .empty-state p { color: #bdc3c7; font-size: 0.9rem; }

        .del-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); z-index: 9999; display: none; align-items: center; justify-content: center; }
        .del-overlay.active { display: flex; }
        .del-box { background: white; border-radius: 28px; padding: 2.5rem; width: 90%; max-width: 420px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.15); animation: popIn 0.3s cubic-bezier(0.34,1.56,0.64,1); }
        @keyframes popIn { from { transform:scale(0.9); opacity:0; } to { transform:scale(1); opacity:1; } }
        .btn-del-confirm { width: 100%; padding: 13px; border: none; border-radius: 14px; background: linear-gradient(135deg,#e17055,#d63031); color: white; font-weight: 800; font-size: 0.9rem; cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; margin-bottom: 0.6rem; }
        .btn-del-cancel { width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 14px; background: white; color: #1A1C1E; font-weight: 700; font-size: 0.88rem; cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; }

        .btn-create { background: var(--primary-grad); color: white; border: none; padding: 14px 28px; border-radius: 100px; font-weight: 700; transition: 0.3s; box-shadow: 0 10px 20px rgba(255,107,107,0.2); font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; }
        .btn-create:hover { transform: translateY(-3px); color: white; }

        .alert-toast { padding: 14px 20px; border-radius: 16px; font-weight: 700; font-size: 0.88rem; margin-bottom: 1.5rem; display: flex; align-items: center; animation: slideDown 0.3s ease; }
        .alert-toast.success { background: rgba(0,184,148,0.12); color: #00b894; border: 1px solid rgba(0,184,148,0.2); }
        .alert-toast.danger  { background: rgba(225,112,85,0.12); color: #e17055; border: 1px solid rgba(225,112,85,0.2); }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        .modal-content { border-radius: 35px; border: none; padding: 1rem; }
        .form-hint { font-size: 0.75rem; color: #aaa; margin-top: 5px; display: block; }
        .form-hint i { color: var(--accent); }
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
        <li><a href="manage_items.php" class="active"><i class="bi bi-bag-heart-fill"></i> Manage Items</a></li>
        <li><a href="manage_orders.php"><i class="bi bi-receipt"></i> Manage Orders</a></li>
        <li><a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a></li>
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

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h3 class="fw-bold" id="itemModalTitle">Add New Item</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="itemForm" action="manage_items.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_item" value="1">
                    <input type="hidden" name="item_id" id="itemId" value="0">
                    <input type="hidden" name="existing_image" id="existingImage" value="">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Item Name</label>
                            <input type="text" name="name" id="itemName" class="form-control" placeholder="e.g. Tomato (500g)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" id="itemCategory" class="form-select">
                                <option value="Vegetables">Vegetables</option>
                                <option value="Fruits">Fruits</option>
                                <option value="Meat & Poultry">Meat & Poultry</option>
                                <option value="Seafood">Seafood</option>
                                <option value="Dairy">Dairy</option>
                                <option value="Dry Goods">Dry Goods</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="itemDesc" class="form-control" rows="2" placeholder="Brief description of the item"></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Keep it short and clear</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Price (RM)</label>
                            <input type="number" name="price" id="itemPrice" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock</label>
                            <input type="number" name="stock" id="itemStock" class="form-control" placeholder="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Unit</label>
                            <input type="text" name="unit" id="itemUnit" class="form-control" placeholder="e.g. kg, pcs, g">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Unit per item</span>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Image</label>
                            <input type="file" name="item_image" id="itemImage" class="form-control" accept="image/*" onchange="previewItemImg(this)">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Leave empty to keep current</span>
                        </div>
                        <div id="imgPreviewWrap" style="display:none;" class="col-12">
                            <img id="imgPreview" src="" style="width:100%;height:160px;object-fit:cover;border-radius:16px;">
                        </div>
                    </div>
                    <div class="text-end mt-5">
                        <button type="submit" id="saveItemBtn" class="btn-create px-5">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Manage Items</h1>
            <p><?= $count ?> item<?= $count !== 1 ? 's' : '' ?> found</p>
        </div>
        <button class="btn-add" onclick="openItemModal()">
            <i class="bi bi-plus-lg"></i> Add New Item
        </button>
    </div>

    <?php $status = $_GET['status'] ?? ''; ?>
    <?php if ($status === 'created'): ?>
    <div class="alert-toast success"><i class="bi bi-check-circle-fill me-2"></i> Item created successfully!</div>
    <?php elseif ($status === 'updated'): ?>
    <div class="alert-toast success"><i class="bi bi-check-circle-fill me-2"></i> Item updated successfully!</div>
    <?php elseif ($status === 'deleted'): ?>
    <div class="alert-toast danger"><i class="bi bi-trash3-fill me-2"></i> Item deleted successfully!</div>
    <?php endif; ?>

    <div class="filter-bar">
        <form method="GET" id="filterForm" style="display:contents;">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search item name..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
            </div>
            <select name="category" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All Categories</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stock" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all" <?= $stock_filter === 'all' ? 'selected' : '' ?>>All Stock</option>
                <option value="low" <?= $stock_filter === 'low' ? 'selected' : '' ?>>Low Stock (≤10)</option>
                <option value="out" <?= $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
        </form>
        <span class="count-badge"><?= $count ?> results</span>
    </div>

    <div class="table-card">
        <div class="item-row header">
            <div></div><div>Item</div><div>Category</div><div>Price</div><div>Stock</div><div>Unit</div><div>Actions</div>
        </div>
        <?php if (empty($items)): ?>
        <div class="empty-state"><i class="bi bi-bag-x"></i><p>No items found.</p></div>
        <?php else: ?>
        <?php foreach ($items as $item):
            $imgSrc = getImageSrc($item['image'] ?? '', '../../assets/images/items/');
            if ($item['stock'] == 0) $stockClass = 'stock-out';
            elseif ($item['stock'] <= 10) $stockClass = 'stock-low';
            else $stockClass = 'stock-ok';
            $catClass = match($item['category']) {
                'Vegetables'     => 'cat-vegetables',
                'Fruits'         => 'cat-fruits',
                'Meat & Poultry' => 'cat-meat',
                'Seafood'        => 'cat-seafood',
                'Dairy'          => 'cat-dairy',
                'Dry Goods'      => 'cat-drygoods',
                default          => ''
            };
        ?>
        <div class="item-row" id="row-<?= $item['item_id'] ?>">
            <div class="item-img">
                <?php if ($imgSrc): ?><img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
                <?php else: ?><i class="bi bi-bag"></i><?php endif; ?>
            </div>
            <div>
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <?php if (!empty($item['description'])): ?>
                <div class="item-desc"><?= htmlspecialchars(substr($item['description'], 0, 40)) ?>...</div>
                <?php endif; ?>
            </div>
            <div><span class="category-tag <?= $catClass ?>"><?= htmlspecialchars($item['category']) ?></span></div>
            <div><span class="price-val">RM <?= number_format($item['price'], 2) ?></span></div>
            <div>
                <span class="stock-badge <?= $stockClass ?>">
                    <?= $item['stock'] == 0 ? 'Out of Stock' : ($item['stock'] <= 10 ? 'Low: ' . $item['stock'] : $item['stock']) ?>
                </span>
            </div>
            <div style="font-size:0.82rem;color:#7f8c8d;font-weight:600;"><?= htmlspecialchars($item['unit'] ?? '-') ?></div>
            <div class="action-btns">
                <button class="btn-edit" onclick="openItemModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn-delete" onclick="confirmDelete(<?= $item['item_id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="del-overlay" id="deleteModal">
    <div class="del-box">
        <div class="mb-3"><i class="bi bi-trash3-fill" style="font-size:3rem;color:#e17055;"></i></div>
        <h4 class="fw-bold mb-2">Delete Item?</h4>
        <p class="text-muted mb-4" id="delModalText">This action cannot be undone.</p>
        <button class="btn-del-confirm" onclick="doDelete()"><i class="bi bi-trash3 me-2"></i>Yes, Delete</button>
        <button class="btn-del-cancel" onclick="closeDeleteModal()">Cancel</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let deleteId = null;
function confirmDelete(id, name) {
    deleteId = id;
    document.getElementById('delModalText').textContent = `"${name}" will be permanently deleted.`;
    document.getElementById('deleteModal').classList.add('active');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteId = null;
}
async function doDelete() {
    if (!deleteId) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('item_id', deleteId);
    const res  = await fetch('manage_items.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        closeDeleteModal();
        window.location.href = 'manage_items.php?status=deleted';
    }
}
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(window._st);
        window._st = setTimeout(() => document.getElementById('filterForm').submit(), 600);
    });
}
document.querySelectorAll('.alert-toast').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 3000);
});
setTimeout(() => {
    const url = new URL(window.location.href);
    url.searchParams.delete('status');
    window.history.replaceState({}, '', url);
}, 100);
function openItemModal(item = null) {
    document.getElementById('itemForm').reset();
    document.getElementById('imgPreviewWrap').style.display = 'none';
    if (item) {
        document.getElementById('itemModalTitle').textContent = 'Update Item';
        document.getElementById('saveItemBtn').textContent    = 'Save Changes';
        document.getElementById('itemId').value               = item.item_id;
        document.getElementById('existingImage').value        = item.image ?? '';
        document.getElementById('itemName').value             = item.name ?? '';
        document.getElementById('itemDesc').value             = item.description ?? '';
        document.getElementById('itemCategory').value         = item.category ?? 'Vegetables';
        document.getElementById('itemPrice').value            = item.price ?? '';
        document.getElementById('itemStock').value            = item.stock ?? '';
        document.getElementById('itemUnit').value             = item.unit ?? '';
        if (item.image) {
            const src = item.image.startsWith('http') ? item.image : '../../assets/images/items/' + item.image;
            document.getElementById('imgPreview').src = src;
            document.getElementById('imgPreviewWrap').style.display = 'block';
        }
    } else {
        document.getElementById('itemModalTitle').textContent = 'Add New Item';
        document.getElementById('saveItemBtn').textContent    = 'Create';
        document.getElementById('itemId').value               = '0';
        document.getElementById('existingImage').value        = '';
    }
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}
function previewItemImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>