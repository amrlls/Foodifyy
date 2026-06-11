<?php

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/upload_helper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;

$nav_profile_img = "";
$nav_role = "Customer";
$username = 'Guest';

if ($isLoggedIn) {
    $stmt_nav = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
    $stmt_nav->bind_param("i", $userId);
    $stmt_nav->execute();
    $user_nav = $stmt_nav->get_result()->fetch_assoc();
    if ($user_nav) {
        $username = $user_nav['username'];
        $nav_profile_img = $user_nav['profile_image'];
        $nav_role = $user_nav['role'];
    }
}

$greeting = "Welcome to Foodify!";
if ($isLoggedIn) {
    $role_clean = strtolower($nav_role);
    if ($role_clean == 'admin') {
        $greeting = "Ready to manage, Admin?";
    } elseif ($role_clean == 'seller') {
        $greeting = "Your shop is open! Check your stock.";
    } else {
        $greeting = "Happy cooking, " . htmlspecialchars($username) . "!";
    }
} else {
    $greeting = "Sign in to start your kitchen journey.";
}

$sql_recipes = "SELECT r.*, 
                (SELECT COUNT(*) FROM saved_recipes s WHERE s.recipe_id = r.recipe_id AND s.user_id = $userId) as is_saved 
                FROM recipes r 
                WHERE r.is_public = 1
                ORDER BY RAND() LIMIT 5";
$res_recipes = $conn->query($sql_recipes);
$recipes = $res_recipes->fetch_all(MYSQLI_ASSOC);

$sql_items = "SELECT * FROM items ORDER BY RAND() LIMIT 8";
$res_items = $conn->query($sql_items);
$items = $res_items->fetch_all(MYSQLI_ASSOC);

$cartCount = 0;
if ($isLoggedIn) {
    $stmt_cart = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt_cart->bind_param("i", $userId);
    $stmt_cart->execute();
    $cart_row  = $stmt_cart->get_result()->fetch_assoc();
    $cartCount = (int)($cart_row['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodify – Modern Kitchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Syne:wght@800&family=Fraunces:wght@900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --sidebar-dark: #1A1C1E;
            --accent: #FF8E53;
            --soft-bg: #F8F9FA;
            --sidebar-w: 280px;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--soft-bg);
            color: #2D3436;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s ease;
        }
        .sidebar-logo h2 { 
            font-family: 'Playfair Display', serif; font-weight: 900; 
            letter-spacing: -1px;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem; padding-left: 1rem;
        }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 15px; padding: 14px 18px;
            color: #949494; text-decoration: none; border-radius: 16px;
            font-weight: 500; transition: all 0.3s ease;
        }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s ease; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }
        .user-card:active { transform: scale(0.95); background: rgba(255,255,255,0.1); }
        .user-card img { transition: transform 0.3s ease; }
        .user-card:hover img { transform: rotate(5deg); }

        /* ── TOPBAR (mobile only) ── */
        .topbar {
            display: none;
            position: fixed; top: 0; left: 0; right: 0; z-index: 999;
            background: var(--sidebar-dark); padding: 1rem 1.5rem;
            align-items: center; justify-content: space-between;
        }
        .topbar-logo {
            font-family: 'Playfair Display', serif; font-weight: 900;
            font-size: 1.5rem; letter-spacing: -1px;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hamburger { background: none; border: none; color: white; font-size: 1.4rem; cursor: pointer; padding: 4px; }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 998; }
        .sidebar-overlay.active { display: block; }

        .main-content { margin-left: var(--sidebar-w); padding: 2rem 3rem; }

        .hero-banner {
            font-family: 'Fraunces', serif;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=80');
            background-size: cover; background-position: center;
            border-radius: 20px; padding: 4rem 4rem; color: white; margin-bottom: 1.5rem; 
        }
        .section-header-box {
            height: 60px; display: flex; flex-direction: column;
            justify-content: flex-end; margin-bottom: 1.2rem;
        }
        .btn-see-all {
            display: inline-flex; align-items: center; gap: 6px;
            color: #1A1C1E; font-weight: 700; font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.25s ease;
        }
        .btn-see-all i { transition: transform 0.25s ease; }
        .btn-see-all:hover { color: #FF8E53; }
        .btn-see-all:hover i { transform: translateX(4px); }

        /* ── RECIPE CARD ── */
        .recipe-item {
            background: #ffffff; border-radius: 28px; padding: 1rem;
            border: 1px solid rgba(0,0,0,0.04); margin-bottom: 2.60rem;
            cursor: pointer; position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .recipe-item:nth-child(1) { animation-delay: 0s; }
        .recipe-item:nth-child(2) { animation-delay: 0.6s; }
        .recipe-item:nth-child(3) { animation-delay: 1.2s; }
        .recipe-item:nth-child(4) { animation-delay: 1.8s; }
        .recipe-item:nth-child(5) { animation-delay: 2.4s; }
        .recipe-img-wrapper { 
            width: 220px; height: 160px; border-radius: 22px;
            flex-shrink: 0; position: relative;
        }
        .recipe-img-wrapper img { width: 100%; height: 100%; object-fit: cover; border-radius: 22px; transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .recipe-img-wrapper::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.25) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.4s ease;
        }
        .recipe-item h5 {
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
            letter-spacing: -0.5px; color: #1A1C1E;
        }
        @media (hover: hover) {
            .recipe-item:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(0,0,0,0.1); }
            .recipe-item:hover .recipe-img-wrapper img { transform: scale(1.08); }
            .recipe-item:hover .recipe-img-wrapper::after { opacity: 1; }
            .recipe-item:hover h5 { color: #FF6B6B; letter-spacing: -0.8px; }
        }
        .recipe-item p {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; min-height: 48px;
        }
        .cuisine-tag {
            font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;
            font-weight: 700; padding: 5px 12px; background: #F8F9FA;
            color: #6c757d; border-radius: 10px; display: inline-block;
        }
        .btn-view-details {
            background: #1A1C1E; color: #fff; border: none;
            padding: 8px 20px; border-radius: 10px; font-size: 0.8rem;
            font-weight: 600; transition: all 0.25s ease;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-view-details:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,28,30,0.2); color: #fff; background: #444; }
        .btn-view-details i { position: relative; z-index: 1; }

        /* ── SAVE BUTTON — sama sebijik macam .btn-heart dalam recipes.php ── */
        .save-btn-circle {
            position: absolute; top: 12px; left: 12px; z-index: 10;
            background: rgba(255,255,255,0.9); border: none;
            width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: #d1d1d1; cursor: pointer; transition: 0.3s;
            backdrop-filter: blur(5px);
        }
        .save-btn-circle:hover { transform: scale(1.1); color: #ff4757; }
        .save-btn-circle.active { color: #ff4757; background: #fff; }

        .grocery-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .grocery-card {
            background: white; border-radius: 22px; border: none; overflow: hidden;
            display: flex; flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            cursor: pointer; position: relative;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .grocery-card:nth-child(1) { animation-delay: 0s; }
        .grocery-card:nth-child(2) { animation-delay: 0.4s; }
        .grocery-card:nth-child(3) { animation-delay: 0.8s; }
        .grocery-card:nth-child(4) { animation-delay: 1.2s; }
        .grocery-card:nth-child(5) { animation-delay: 1.6s; }
        .grocery-card:nth-child(6) { animation-delay: 2.0s; }
        .grocery-card:nth-child(7) { animation-delay: 2.4s; }
        .grocery-card:nth-child(8) { animation-delay: 2.8s; }
        
        .grocery-card-img { position: relative; height: 120px; overflow: hidden; background: #f1f3f5; }
        .grocery-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.55s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .grocery-card-img::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(26,28,30,0.2) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.35s ease; pointer-events: none;
        }
        .grocery-category-badge {
            position: absolute; top: 8px; left: 8px; z-index: 3;
            background: rgba(255,255,255,0.9); backdrop-filter: blur(6px);
            font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; color: #636E72; padding: 3px 8px; border-radius: 20px;
        }
        .grocery-card-body { padding: 0.85rem 0.95rem 1rem; display: flex; flex-direction: column; flex-grow: 1; gap: 6px; }
        .grocery-card-name {
            font-size: 0.85rem; font-weight: 800; color: #1A1C1E; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; min-height: 2.2em; letter-spacing: -0.2px;
        }
        @media (hover: hover) {
            .grocery-card:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(0,0,0,0.1); }
            .grocery-card:hover .grocery-card-img img { transform: scale(1.1); }
            .grocery-card:hover .grocery-card-img::after { opacity: 1; }
            .grocery-card:hover .grocery-card-name { color: #FF6B6B; }
        }
        .grocery-price-row { display: flex; align-items: baseline; justify-content: space-between; margin-top: 2px; }
        .grocery-price {
            font-size: 1.05rem; font-weight: 800;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1;
        }
        .grocery-unit { font-size: 0.65rem; font-weight: 600; color: #adb5bd; letter-spacing: 0.3px; }
        .grocery-stock { display: flex; align-items: center; gap: 4px; margin-top: 2px; }
        .stock-dot { width: 6px; height: 6px; border-radius: 50%; background: #00b894; flex-shrink: 0; }
        .stock-dot.low { background: #fdcb6e; }
        .stock-dot.out { background: #d63031; }
        .stock-label { font-size: 0.65rem; font-weight: 600; color: #b2bec3; letter-spacing: 0.2px; }
        .btn-cart-minimal {
            background: #F8F9FA; color: #1A1C1E; border: none; padding: 9px 12px;
            border-radius: 12px; font-weight: 700; font-size: 0.78rem; width: 100%;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: 'Plus Jakarta Sans', sans-serif; margin-top: auto;
            display: flex; align-items: center; justify-content: center;
            gap: 6px; letter-spacing: 0.2px;
        }
        .btn-cart-minimal:hover {
            background: var(--sidebar-dark); color: white;
            transform: translateY(-2px) scale(1.02); box-shadow: 0 8px 20px rgba(26,28,30,0.18);
        }
        .btn-cart-minimal:active { transform: scale(0.96); }
        .btn-cart-minimal:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem;
            border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 997; bottom: 30px; right: 30px; transition: 0.3s;
        }
        .floating-cart:hover { background: #2d2f31; transform: translateY(-3px); }
        .cart-badge-pill {
            background: var(--primary-grad); color: white;
            font-size: 0.65rem; font-weight: 800;
            width: 20px; height: 20px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            margin-left: 4px;
        }

        /* ── RESPONSIVE MOBILE ── */
        @media (max-width: 768px) {
            .topbar { display: flex; }
            .sidebar { transform: translateX(-100%); padding-top: 1.5rem; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.2rem 1rem; padding-top: 5rem; }
            .hero-banner { padding: 2rem 1.5rem; border-radius: 16px; }
            .hero-banner h1 { font-size: 1.8rem; }
            .hero-banner p { font-size: 1rem !important; }
            .recipe-item { flex-direction: column !important; gap: 1rem !important; margin-bottom: 1.2rem; }
            .recipe-img-wrapper { width: 100% !important; height: 180px !important; border-radius: 16px; }
            .recipe-item .pe-2 { padding-right: 0 !important; }
            .row.g-5 { --bs-gutter-x: 1rem; --bs-gutter-y: 1rem; }
            .col-lg-7, .col-lg-5 { padding: 0; }
            .section-header-box { height: auto; margin-bottom: 1rem; }
            .floating-cart { padding: 0.8rem 1.2rem; bottom: 20px; right: 16px; }
            .grocery-grid { gap: 10px; }
        }

        @media (max-width: 480px) {
            .grocery-card-img { height: 100px; }
            .grocery-price { font-size: 0.95rem; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR (mobile) ── -->
<div class="topbar">
    <span class="topbar-logo">foodify.</span>
    <button class="hamburger" onclick="toggleSidebar()">
        <i class="bi bi-list" id="hamburgerIcon"></i>
    </button>
</div>

<!-- ── SIDEBAR OVERLAY ── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ── SIDEBAR ── -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p><?= $greeting ?></p></div>
    
    <ul class="sidebar-nav">
        <li><a href="index.php" class="active"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="modules/recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="modules/shop/items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="modules/recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
            <li><a href="modules/order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
        <?php if ($isLoggedIn): ?>
            <a href="modules/profile/profile.php" class="text-decoration-none d-block">
                <div class="user-card d-flex align-items-center gap-3 mb-3">
                    <?php $navProfileSrc = getImageSrc($nav_profile_img, 'assets/images/profiles/'); ?>
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
            <a href="modules/auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        <?php else: ?>
            <a href="modules/auth/login.php" class="btn btn-light w-100 rounded-3 py-3 fw-bold shadow-sm">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <div class="hero-banner">
        <h1 class="mb-2">Cook Smart,<br>Eat Fresh.</h1>
        <p class="opacity-75 fs-5">Premium ingredients for your modern kitchen.</p>
    </div>

    <div class="row g-5">
        <!-- Recipes -->
        <div class="col-lg-7">
            <div class="section-header-box">
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase small" style="font-size:0.7rem;margin-bottom:4px;">Handpicked</h6>
                        <h3 class="fw-bold m-0">Suggested For You</h3>
                    </div>
                    <a href="modules/recipe/recipes.php" class="btn-see-all">Browse All <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <?php foreach ($recipes as $r): 
                $isSaved    = ($r['is_saved'] > 0);
                $activeClass = $isSaved ? 'active' : '';
            ?>
            <div class="recipe-item d-flex align-items-center gap-4" onclick="location.href='modules/recipe/recipedetail.php?id=<?= $r['recipe_id'] ?>'">
                <div class="recipe-img-wrapper shadow-sm">
                    <?php $imgSrc = getImageSrc($r['image'], 'assets/images/recipes/'); ?>
                    <img src="<?= $imgSrc ? htmlspecialchars($imgSrc) : 'https://placehold.co/400x400' ?>" class="w-100 h-100 object-fit-cover">
                    <button class="save-btn-circle <?= $activeClass ?>" onclick="toggleSave(event, <?= $r['recipe_id'] ?>)">
                        <i class="bi bi-heart-fill fs-6"></i>
                    </button>
                </div>
                <div class="flex-grow-1 pe-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="cuisine-tag mb-2"><?= $r['cuisine'] ?></span>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($r['title']) ?></h5>
                    <p class="text-muted small mb-3" style="line-height:1.5;">
                        <?= substr(htmlspecialchars($r['description']), 0, 85) ?>...
                    </p>
                    <div class="d-flex align-items-center gap-3">
                        <span class="btn-view-details">View Recipe <i class="bi bi-chevron-right" style="font-size:0.7rem;"></i></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Market place — right column -->
        <div class="col-lg-5">
            <div class="section-header-box">
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase small" style="font-size:0.7rem;margin-bottom:4px;">Fresh Picks</h6>
                        <h3 class="fw-bold m-0">Looking for Groceries?</h3>
                    </div>
                    <a href="modules/shop/items.php" class="btn-see-all">See All <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="grocery-grid">
                <?php foreach ($items as $item):
                    $productImg = getImageSrc($item['image'], 'assets/images/items/');
                    $stock = (int)($item['stock'] ?? 99);
                    $stockClass = $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : '');
                    $stockText  = $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? 'Low stock' : 'In stock');
                ?>
                <div class="grocery-card" onclick="openModal(
                    <?= $item['item_id'] ?>,
                    '<?= addslashes(htmlspecialchars($item['name'])) ?>',
                    '<?= addslashes(htmlspecialchars($item['category'] ?? 'Grocery')) ?>',
                    '<?= $item['price'] ?>',
                    '<?= $productImg ? addslashes(htmlspecialchars($productImg)) : '' ?>',
                    'linear-gradient(135deg,#FF6B6B,#FF8E53)',
                    'bi-basket2-fill',
                    <?= $stock ?>,
                    '<?= addslashes(htmlspecialchars($item['unit'] ?? '')) ?>',
                    '<?= addslashes(htmlspecialchars($item['description'] ?? '')) ?>'
                )">
                    <div class="grocery-card-img">
                        <?php if (!empty($item['category'])): ?>
                            <span class="grocery-category-badge"><?= htmlspecialchars($item['category']) ?></span>
                        <?php endif; ?>
                        <img src="<?= $productImg ? htmlspecialchars($productImg) : 'https://placehold.co/400x300?text=No+Image' ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="grocery-card-body">
                        <div class="grocery-card-name" title="<?= htmlspecialchars($item['name']) ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </div>
                        <div class="grocery-price-row">
                            <span class="grocery-price">RM <?= number_format($item['price'], 2) ?></span>
                            <?php if (!empty($item['unit'])): ?>
                                <span class="grocery-unit">/ <?= htmlspecialchars($item['unit']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="grocery-stock">
                            <span class="stock-dot <?= $stockClass ?>"></span>
                            <span class="stock-label"><?= $stockText ?></span>
                        </div>
                        <button onclick="event.stopPropagation(); addToCart(event, <?= $item['item_id'] ?>, this)"
                                class="btn-cart-minimal"
                                <?= $stock <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-bag-plus"></i>
                            <?= $stock <= 0 ? 'Unavailable' : 'Add to Bag' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Floating Cart -->
<a href="modules/shop/cart.php" class="floating-cart position-fixed text-white text-decoration-none d-flex align-items-center gap-3">
    <i class="bi bi-bag-fill fs-5"></i>
    <span class="fw-bold small">Bag
        <span id="cartBadge" class="cart-badge-pill" <?= $cartCount === 0 ? 'style="display:none;"' : '' ?>>
            <?= $cartCount ?>
        </span>
    </span>
</a>

<script>
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
const cartPath   = 'modules/shop/addtocart.php';
</script>

<?php $cartUrl = 'modules/shop/cart.php'; ?>
<?php include __DIR__ . '/modules/shop/item_modal.php'; ?>

<script>
function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const icon     = document.getElementById('hamburgerIcon');
    const isOpen   = sidebar.classList.toggle('open');
    overlay.classList.toggle('active', isOpen);
    icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
}

document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('open')) toggleSidebar();
    });
});

async function addToCart(e, itemId, btn) {
    e.stopPropagation();
    if (!isLoggedIn) { window.location.href = 'modules/auth/login.php'; return; }

    const original = btn.innerHTML;
    btn.innerHTML  = '<i class="bi bi-check-lg"></i> Added!';
    btn.style.background = '#00b894';
    btn.style.color  = 'white';
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('item_id', itemId);
        fd.append('quantity', 1);

        const res  = await fetch('modules/shop/addtocart.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.style.display = 'inline-flex';
                badge.textContent   = parseInt(badge.textContent || 0) + 1;
            }
        } else {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Failed';
            btn.style.background = '#e17055';
        }
    } catch (err) {
        btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Failed';
        btn.style.background = '#e17055';
    }

    setTimeout(() => {
        btn.innerHTML = original;
        btn.style.background = '';
        btn.style.color  = '';
        btn.disabled = false;
    }, 1800);
}

async function toggleSave(event, recipeId) {
    event.stopPropagation();
    event.preventDefault(); 
    if (!isLoggedIn) { window.location.href = 'modules/auth/login.php'; return; }

    const btn = event.currentTarget;
    try {
        const formData = new FormData();
        formData.append('recipe_id', recipeId);
        const response = await fetch('modules/recipe/toggle_save.php', { method: 'POST', body: formData });
        const data     = await response.json();
        if (data.status === 'saved') { 
            btn.classList.add('active');
        } else { 
            btn.classList.remove('active');
        }
    } catch (error) { console.error('Error:', error); }
}
</script>
</body>
</html>