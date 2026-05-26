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
        $greeting = "System active. Ready to manage, Admin?";
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
                ORDER BY r.created_at DESC LIMIT 5";
$res_recipes = $conn->query($sql_recipes);
$recipes = $res_recipes->fetch_all(MYSQLI_ASSOC);

$sql_items = "SELECT * FROM items WHERE stock > 0 ORDER BY created_at DESC LIMIT 6";
$res_items = $conn->query($sql_items);
$items = $res_items->fetch_all(MYSQLI_ASSOC);

// Cart count untuk floating badge
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

        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
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
        .recipe-item {
            background: #ffffff; border-radius: 28px; padding: 1rem;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0,0,0,0.04); margin-bottom: 1.8rem;
            cursor: pointer; position: relative;
        }
        .recipe-item:hover { transform: translateY(-8px) scale(1.01); box-shadow: 0 25px 50px rgba(0,0,0,0.08); }
        .recipe-img-wrapper { 
            width: 220px; height: 160px; border-radius: 22px;
            overflow: hidden; flex-shrink: 0; position: relative;
        }
        .recipe-item:hover .recipe-img-wrapper::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.2), transparent);
        }
        .recipe-item h5 {
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
            letter-spacing: -0.5px; color: #1A1C1E; transition: color 0.3s ease;
        }
        .recipe-item:hover h5 { color: #FF6B6B; }
        .cuisine-tag {
            font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px;
            font-weight: 700; padding: 5px 12px; background: #F8F9FA;
            color: #6c757d; border-radius: 10px; display: inline-block;
        }
        .btn-view-details {
            background: #1A1C1E; color: #fff; border: none;
            padding: 8px 20px; border-radius: 10px; font-size: 0.8rem;
            font-weight: 600; transition: all 0.2s ease;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-view-details:hover { background: #444; transform: translateY(-2px); color: #fff; }
        .save-btn-circle {
            width: 40px; height: 40px; border-radius: 50%; background: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; border: none;
        }
        .save-btn-circle:hover { transform: scale(1.1); background: #fff0f0; }
        .save-btn-circle.active i { color: #FF6B6B; }

        .grocery-card {
            background: white; border-radius: 20px; border: none;
            overflow: hidden; transition: 0.3s; height: 100%;
            display: flex; flex-direction: column;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            cursor: pointer;
        }
        .grocery-card:hover { box-shadow: 0 16px 36px rgba(0,0,0,0.1); transform: translateY(-4px); }
        .grocery-card:hover .product-img-wrapper img { transform: scale(1.06); }
        .grocery-card-img {
            position: relative; height: 130px; overflow: hidden;
        }
        .grocery-card-img img {
            width: 100%; height: 100%; object-fit: cover; transition: 0.5s ease;
        }
        .grocery-card-body {
            padding: 0.9rem 1rem 1rem;
            display: flex; flex-direction: column; flex-grow: 1;
        }
        .grocery-price {
            font-size: 1rem; font-weight: 800;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .btn-cart-minimal {
            background: #f8f9fa; color: #2D3436; border: none;
            padding: 9px; border-radius: 10px; font-weight: 700; font-size: 0.82rem;
            width: 100%; transition: 0.25s; font-family: 'Plus Jakarta Sans', sans-serif;
            margin-top: auto;
        }
        .btn-cart-minimal:hover { background: var(--sidebar-dark); color: white; }
        .btn-cart-minimal:disabled { opacity: 0.7; cursor: not-allowed; }

        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem;
            border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 999; bottom: 30px; right: 30px; transition: 0.3s;
        }
        .floating-cart:hover { background: #2d2f31; transform: translateY(-3px); }
        .cart-badge-pill {
            background: var(--primary-grad); color: white;
            font-size: 0.65rem; font-weight: 800;
            width: 20px; height: 20px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            margin-left: 4px;
        }
    </style>
</head>
<body>

<div class="sidebar">
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
        <!-- RECIPES -->
        <div class="col-lg-7">
            <div class="section-header-box">
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase small" style="font-size:0.7rem;margin-bottom:4px;">Handpicked</h6>
                        <h3 class="fw-bold m-0">New For You</h3>
                    </div>
                    <a href="modules/recipe/recipes.php" class="text-muted fw-bold text-decoration-none small">Browse All <i class="bi bi-arrow-right ms-1"></i></a>
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
                </div>
                <div class="flex-grow-1 pe-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="cuisine-tag mb-2"><?= $r['cuisine'] ?></span>
                        <button class="save-btn-circle <?= $activeClass ?>" onclick="toggleSave(event, <?= $r['recipe_id'] ?>)">
                            <i class="bi bi-heart<?= $isSaved ? '-fill' : '' ?> fs-6 text-danger"></i>
                        </button>
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

        <!-- MARKETPLACE -->
        <div class="col-lg-5">
            <div class="section-header-box">
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase small" style="font-size:0.7rem;margin-bottom:4px;">Fresh Picks</h6>
                        <h3 class="fw-bold m-0">Looking for Groceries?</h3>
                    </div>
                    <a href="modules/shop/items.php" class="text-muted fw-bold text-decoration-none small">See All <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                <div class="col-sm-6">
                    <div class="grocery-card shadow-sm">
                        <!-- Image -->
                        <div class="grocery-card-img">
                            <?php $productImg = getImageSrc($item['image'], 'assets/images/items/'); ?>
                            <img src="<?= $productImg ? htmlspecialchars($productImg) : 'https://placehold.co/400x300?text=No+Image' ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <!-- Body -->
                        <div class="grocery-card-body">
                            <h6 class="fw-bold mb-1 text-truncate" style="font-size:0.88rem;" title="<?= htmlspecialchars($item['name']) ?>">
                                <?= htmlspecialchars($item['name']) ?>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="grocery-price">RM <?= number_format($item['price'], 2) ?></span>
                                <?php if (!empty($item['unit'])): ?>
                                    <span class="text-muted" style="font-size:0.7rem;font-weight:600;">per <?= htmlspecialchars($item['unit']) ?></span>
                                <?php endif; ?>
                            </div>
                            <button onclick="addToCart(event, <?= $item['item_id'] ?>, this)" class="btn-cart-minimal">
                                <i class="bi bi-plus-lg me-1"></i> Add
                            </button>
                        </div>
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

async function addToCart(e, itemId, btn) {
    e.stopPropagation();
    if (!isLoggedIn) { window.location.href = 'modules/auth/login.php'; return; }

    const original = btn.innerHTML;
    btn.innerHTML  = '<i class="bi bi-check-lg me-1"></i> Added!';
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
            btn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Failed';
            btn.style.background = '#e17055';
        }
    } catch (err) {
        btn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Failed';
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

    const icon = event.currentTarget.querySelector('i');
    try {
        const formData = new FormData();
        formData.append('recipe_id', recipeId);
        const response = await fetch('modules/recipe/toggle_save.php', { method: 'POST', body: formData });
        const data     = await response.json();
        if (data.status === 'saved') { 
            icon.classList.replace('bi-heart', 'bi-heart-fill'); 
            icon.style.transform = "scale(1.3)";
            setTimeout(() => icon.style.transform = "scale(1)", 200);
        } else { 
            icon.classList.replace('bi-heart-fill', 'bi-heart'); 
        }
    } catch (error) { console.error('Error:', error); }
}
</script>
</body>
</html>