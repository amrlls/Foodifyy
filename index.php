<?php
session_start();
require_once __DIR__ . '/config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;

// --- TAMBAHAN BARU DI SINI ---
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
// ----------------------------

// 1. Ambil Resipi (Subquery untuk semak status saved)
$sql_recipes = "SELECT r.*, 
                (SELECT COUNT(*) FROM saved_recipes s WHERE s.recipe_id = r.recipe_id AND s.user_id = $userId) as is_saved 
                FROM recipes r 
                WHERE r.is_public = 1
                ORDER BY r.created_at DESC LIMIT 4";
$res_recipes = $conn->query($sql_recipes);
$recipes = $res_recipes->fetch_all(MYSQLI_ASSOC);

// 2. Ambil Item Groceries
$sql_items = "SELECT * FROM items LIMIT 6";
$res_items = $conn->query($sql_items);
$items = $res_items->fetch_all(MYSQLI_ASSOC);

$gradients = [
    'Melayu'  => 'linear-gradient(135deg, #2E7D32, #9FA825)',
    'Western' => 'linear-gradient(135deg, #c0392b, #e74c3c)',
    'Asian'   => 'linear-gradient(135deg, #e67e22, #f39c12)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodify – Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2E7D32; --green-light: #E8F5E9;
            --orange: #FF8F00; --dark: #1A1A1A;
            --muted: #777; --border: #EEEEEE;
            --sidebar-w: 270px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: #F7F9F7; color: var(--dark); }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: white; box-shadow: 2px 0 16px rgba(0,0,0,0.06);
            padding: 1.8rem 1rem; overflow-y: auto; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .sidebar-logo {
            display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px;
            margin-bottom: 2rem; padding: 0 8px;
        }
        .sidebar-logo img { width: 75px; height: 75px; object-fit: contain; border-radius: 14px; }
        .sidebar-logo .logo-text h2 { font-weight: 900; font-size: 1.6rem; color: var(--green); line-height: 1; margin-bottom: 4px; }
        .sidebar-logo .logo-text span { font-size: 0.7rem; color: var(--muted); font-weight: 600; }

        .sidebar-nav { list-style: none; padding: 0; flex: 1; }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px;
            color: var(--dark); text-decoration: none; border-radius: 12px;
            font-weight: 600; font-size: 0.88rem; transition: all 0.18s;
        }
        .sidebar-nav a i { font-size: 1.15rem; width: 22px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--orange); color: white; }

        .sidebar-bottom { border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem; }
        .user-row {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            background: var(--green-light); border-radius: 12px; margin-bottom: 10px;
            transition: all 0.2s ease-out; cursor: pointer;
        }
        .user-row:hover { background-color: #DDEEE6 !important; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .user-row i { font-size: 1.6rem; color: var(--green); }
        .user-avatar-img { 
            width: 35px; 
            height: 35px; 
            border-radius: 50%; 
            object-fit: cover; 
        }
        .user-row .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-row .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; }
        .btn-side-login { background: var(--orange); color: white; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; transition: 0.2s; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 2.5rem; }
        .hero-banner {
            background: linear-gradient(135deg, var(--green) 0%, #3E9B4E 100%);
            border-radius: 24px; padding: 3.5rem; color: white; margin-bottom: 3rem;
        }

        /* ── RECIPE CARD ── */
        .recipe-card {
            background: white; border-radius: 16px; overflow: hidden; display: flex;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); text-decoration: none; color: var(--dark);
            transition: 0.3s; margin-bottom: 1rem; height: 140px; position: relative;
        }
        .recipe-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); border: 1.2px solid var(--orange); }
        .recipe-img-box { width: 160px; flex-shrink: 0; overflow: hidden; position: relative; }
        .recipe-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .recipe-info { padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        
        .btn-save-recipe {
            position: absolute; top: 10px; left: 10px; z-index: 10;
            background: white; border: none; width: 32px; height: 32px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15); color: #ccc; transition: 0.2s; cursor: pointer;
        }
        .btn-save-recipe:hover { transform: scale(1.1); color: #ff4757; }
        .btn-save-recipe.active { color: #ff4757; }
        .btn-save-recipe.active i::before { content: "\f415"; } /* bi-heart-fill */

        /* ── GROCERY ITEM CARD ── */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-header h4 { font-weight: 800; font-size: 1.3rem; margin: 0; }
        .view-all { color: var(--orange); text-decoration: none; font-weight: 700; font-size: 0.85rem; }

        .grocery-item-card {
            background: white; border-radius: 20px; padding: 1rem; text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); display: flex; flex-direction: column;
            align-items: center; height: 100%; border: 1px solid transparent; transition: 0.2s;
        }
        .grocery-item-card:hover { border-color: var(--green); }
        .item-icon-circle {
            width: 55px; height: 55px; background: #f0f9f1; border-radius: 16px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 0.8rem;
        }
        .item-icon-circle i { font-size: 1.5rem; color: #2E7D32; }
        .grocery-item-card h6 { font-weight: 800; font-size: 0.9rem; margin-bottom: 5px; color: var(--dark); }
        .item-price { color: var(--orange); font-weight: 800; font-size: 1rem; margin-bottom: 0.8rem; }
        .btn-add-cart {
            background: #2E7D32; color: white; border: none; width: 100%;
            padding: 8px; border-radius: 12px; font-weight: 700; font-size: 0.75rem;
            transition: 0.2s; margin-top: auto;
        }

        .cart-float {
            position: fixed; bottom: 30px; right: 30px;
            background: var(--orange); width: 65px; height: 65px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px rgba(255,143,0,0.4); text-decoration: none; z-index: 2000;
        }
        .cart-float i { color: white; font-size: 1.8rem; }
        .cart-count {
            position: absolute; top: 0; right: 0; background: #1a1a1a;
            color: white; width: 24px; height: 24px; border-radius: 50%;
            font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-weight: 800;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Foodify">
        <div class="logo-text">
            <h2>Foodify</h2>
            <span>Recipes + Groceries</span>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li><a href="index.php" class="active"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="modules/recipe/recipes.php"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="modules/shop/index.php"><i class="bi bi-bag-fill"></i> Shop</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="modules/recipe/cookbook.php"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
            <li><a href="modules/order/index.php"><i class="bi bi-truck"></i> My Orders</a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-bottom">
    <?php if ($isLoggedIn): ?>
        <a href="modules/profile/profile.php" class="text-decoration-none" style="color: inherit;">
            <div class="user-row">
                <?php if (!empty($nav_profile_img) && file_exists("assets/images/profiles/" . $nav_profile_img)): ?>
                    <img src="assets/images/profiles/<?= htmlspecialchars($nav_profile_img) ?>" class="user-avatar-img">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
                
                <div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= htmlspecialchars($nav_role) ?></div>
                </div>
            </div>
        </a>
        <a href="modules/auth/logout.php" class="btn-side btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
    <?php else: ?>
        <a href="modules/auth/login.php" class="btn-side btn-side-login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
    <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <div class="hero-banner">
        <h1 style="font-family:'Playfair Display', serif; font-weight:800;">Cook Smart,<br>Eat Fresh.</h1>
        <p>Explore community recipes and get fresh ingredients delivered.</p>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="section-header">
                <h4><i class="bi bi-search me-2"></i>Suggested for You</h4>
                <a href="modules/recipe/recipes.php" class="view-all">View all →</a>
            </div>
            <?php foreach ($recipes as $r): 
                $grad = $gradients[$r['cuisine']] ?? 'var(--green)';
                $activeClass = ($r['is_saved'] > 0) ? 'active' : '';
            ?>
            
            <div style="position: relative;">
                <button class="btn-save-recipe <?= $activeClass ?>" onclick="toggleSave(event, <?= $r['recipe_id'] ?>)">
                    <i class="bi bi-heart"></i>
                </button>

                <!-- LINK DIUBAH KE recipedetail.php DENGAN PARAMETER ?id= -->
                <a href="modules/recipe/recipedetail.php?id=<?= $r['recipe_id'] ?>" class="recipe-card">
                    <div class="recipe-img-box" style="background: <?= $grad ?>;">
                        <?php if($r['image']): ?>
                            <img src="assets/images/recipes/<?= $r['image'] ?>" alt="">
                        <?php else: ?>
                            <div class="h-100 d-flex align-items-center justify-content-center text-white">
                                <i class="bi bi-image" style="font-size: 2rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="recipe-info">
                        <span class="badge mb-1" style="background:var(--green-light); color:var(--green); width:fit-content; font-size: 0.7rem;"><?= $r['cuisine'] ?></span>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($r['title']) ?></h5>
                        <p class="text-muted small mb-0"><?= substr(htmlspecialchars($r['description']), 0, 80) ?>...</p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-5">
            <div class="section-header">
                <h4><i class="bi bi-cart3 me-2"></i>Groceries</h4>
                <a href="modules/shop/index.php" class="view-all">View all →</a>
            </div>

            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                <div class="col-6">
                    <div class="grocery-item-card">
                        <div class="item-icon-circle">
                            <?php 
                                $iconClass = "bi-basket";
                                if(stripos($item['name'], 'chicken') !== false) $iconClass = "bi-tencent-qq";
                                if(stripos($item['name'], 'egg') !== false) $iconClass = "bi-egg-fried";
                                if(stripos($item['name'], 'milk') !== false) $iconClass = "bi-cup-straw";
                                if(stripos($item['name'], 'veg') !== false) $iconClass = "bi-apple";
                            ?>
                            <i class="bi <?= $iconClass ?>"></i>
                        </div>
                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                        <div class="item-price">RM <?= number_format($item['price'], 2) ?></div>
                        <button onclick="requireLogin(event, 'shop')" class="btn-add-cart">+ Add to Cart</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<a href="modules/shop/cart.php" class="cart-float">
    <i class="bi bi-cart-fill"></i>
    <div class="cart-count">0</div>
</a>

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    
    async function toggleSave(event, recipeId) {
        event.preventDefault(); 
        event.stopPropagation();

        if (!isLoggedIn) {
            alert("Sila log masuk untuk menyimpan resipi.");
            window.location.href = 'modules/auth/login.php';
            return;
        }

        const btn = event.currentTarget;

        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);

            const response = await fetch('modules/recipe/toggle_save.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'saved') {
                btn.classList.add('active');
            } else if (data.status === 'removed') {
                btn.classList.remove('active');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function requireLogin(e, type) {
        if (!isLoggedIn) {
            e.preventDefault();
            alert("Sila log masuk untuk menggunakan fungsi ini.");
            window.location.href = 'modules/auth/login.php';
        } else {
            if(type === 'shop') alert("Ditambah ke troli!");
        }
    }
</script>

</body>
</html>