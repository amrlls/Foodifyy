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

// Logik Pesanan Aluan (Greeting Message) berdasarkan Role
$greeting = "Welcome to Foodify!";
if($isLoggedIn) {
    $role_clean = strtolower($nav_role);
    if($role_clean == 'admin') {
        $greeting = "System active. Ready to manage, Admin?";
    } elseif($role_clean == 'seller') {
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
                ORDER BY r.created_at DESC LIMIT 4";
$res_recipes = $conn->query($sql_recipes);
$recipes = $res_recipes->fetch_all(MYSQLI_ASSOC);

$sql_items = "SELECT * FROM items LIMIT 6";
$res_items = $conn->query($sql_items);
$items = $res_items->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodify – Modern Kitchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        }

        .sidebar-logo h2 { 
            font-family: 'Playfair Display', serif; font-weight: 900; 
            letter-spacing: -1px;
            background: var(--primary-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem; padding-left: 1rem;
        }

        .sidebar-greet-box {
            padding-left: 1rem; margin-bottom: 3rem;
        }
        .sidebar-greet-box p {
            color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400;
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

        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        /* -- PROFILE CARD ANIMATION -- */
        .user-card {
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; 
            border-radius: 20px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); /* Animasi smooth */
            cursor: pointer;
            user-select: none;
        }

        /* Kesan bila mouse lalu (hover) */
        .user-card:hover {
            background: rgba(255,255,255,0.07);
            border-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        /* Kesan bila diklik (active) */
        .user-card:active {
            transform: scale(0.95); /* Mengecil sedikit (push effect) */
            background: rgba(255,255,255,0.1);
        }

        /* Tambah sedikit glow pada gambar profil */
        .user-card img {
            transition: transform 0.3s ease;
        }

        .user-card:hover img {
            transform: rotate(5deg);
        }

        /* ── MAIN CONTENT ── */
        /* -- MAIN CONTENT -- */
        .main-content { 
            margin-left: var(--sidebar-w); 
            padding: 2rem 3rem; /* Dikurangkan sedikit padding atas */
        }

        .hero-banner {
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=80');
            background-size: cover; 
            background-position: center;
            border-radius: 35px; 
            padding: 4rem 4rem; /* Dikurangkan sedikit padding dalam banner */
            color: white; 
            margin-bottom: 1.5rem; /* DIKECILKAN: Jarak antara banner dan content di bawahnya */
        }

        /* Penyelarasan header supaya tidak terlalu jauh dari banner */
        .section-header-box {
            height: 60px; /* Dikurangkan sedikit dari 80px */
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            margin-bottom: 1.2rem;
        }
        .recipe-item {
            background: white; border-radius: 24px; padding: 1.2rem;
            transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0,0,0,0.05); margin-bottom: 1.5rem;
            cursor: pointer;
        }
        .recipe-item:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .recipe-img-wrapper { width: 140px; height: 140px; border-radius: 20px; overflow: hidden; flex-shrink: 0; }

        .grocery-card {
            background: white; border-radius: 24px; border: none;
            padding: 1.5rem; transition: 0.3s; height: 100%;
            display: flex; flex-direction: column; justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .grocery-card:hover { box-shadow: 0 15px 30px rgba(0,0,0,0.08); }

        .btn-cart-minimal {
            background: #F8F9FA; color: #2D3436; border: none;
            padding: 10px; border-radius: 12px; font-weight: 600; width: 100%; transition: 0.3s;
        }
        .btn-cart-minimal:hover { background: var(--sidebar-dark); color: white; }

        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem;
            border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 999; bottom: 30px; right: 30px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
    </div>
    <!-- Greeting Message DI SINI -->
    <div class="sidebar-greet-box">
        <p><?= $greeting ?></p>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="index.php" class="active"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="modules/recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="modules/shop/index.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="modules/recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Library</a></li>
            <li><a href="modules/order/index.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="sidebar-footer">
    <?php if ($isLoggedIn): ?>
        <!-- Selubungi kad dengan link supaya keseluruhan kad boleh diklik -->
        <a href="modules/profile/profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3">
                <?php $navProfileSrc = getImageSrc($nav_profile_img, 'assets/images/profiles/'); ?>
                
                <?php if ($navProfileSrc): ?>
                    <img src="<?= htmlspecialchars($navProfileSrc) ?>" style="width:42px; height:42px; border-radius:12px; object-fit:cover;">
                <?php else: ?>
                    <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px; height:42px; background: var(--primary-grad);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>

                <div class="overflow-hidden">
                    <div class="text-white fw-bold small text-truncate" style="max-width: 130px;">
                        <?= htmlspecialchars($username) ?>
                    </div>
                    <div style="font-size: 0.65rem; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                        <?= htmlspecialchars($nav_role) ?>
                    </div>
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
        <!-- RECIPES SECTION -->
        <div class="col-lg-7">
            <div class="section-header-box">
                <div class="d-flex justify-content-between align-items-end w-100">
                    <div>
                        <h6 class="text-danger fw-bold text-uppercase small ls-wide" style="font-size: 0.7rem; margin-bottom: 4px;">Handpicked</h6>
                        <h3 class="fw-bold m-0">Trending Kitchen</h3>
                    </div>
                    <a href="modules/recipe/recipes.php" class="text-dark fw-bold text-decoration-none small">Browse All <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>

            <?php foreach ($recipes as $r): 
                $activeClass = ($r['is_saved'] > 0) ? 'active' : '';
            ?>
            <!-- Boleh klik satu kad untuk ke details -->
            <div class="recipe-item d-flex align-items-center gap-4" onclick="location.href='modules/recipe/recipedetail.php?id=<?= $r['recipe_id'] ?>'">
                <div class="recipe-img-wrapper shadow-sm">
                    <?php $imgSrc = getImageSrc($r['image'], 'assets/images/recipes/'); ?>
                    <img src="<?= $imgSrc ? htmlspecialchars($imgSrc) : 'https://placehold.co/400x400' ?>" class="w-100 h-100 object-fit-cover">
                </div>
                <div class="flex-grow-1">
                    <span class="badge bg-light text-dark mb-2 rounded-pill border"><?= $r['cuisine'] ?></span>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($r['title']) ?></h5>
                    <p class="text-muted small mb-3"><?= substr(htmlspecialchars($r['description']), 0, 70) ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="btn btn-dark btn-sm rounded-pill px-4" style="font-size: 0.75rem;">View Recipe</span>
                        <button class="btn btn-link text-danger p-0 <?= $activeClass ?>" onclick="toggleSave(event, <?= $r['recipe_id'] ?>)">
                            <i class="bi bi-heart<?= $r['is_saved'] > 0 ? '-fill' : '' ?> fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- MARKETPLACE -->
        <div class="col-lg-5">
            <div class="section-header-box">
                <h3 class="fw-bold m-0">Pantry Essentials</h3>
            </div>
            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                <div class="col-sm-6">
                    <div class="grocery-card shadow-sm">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="p-2 rounded-3 bg-light text-danger">
                                    <?php 
                                        $icon = "bi-basket";
                                        if(stripos($item['name'], 'chicken') !== false) $icon = "bi-tencent-qq";
                                        if(stripos($item['name'], 'egg') !== false) $icon = "bi-egg-fried";
                                        if(stripos($item['name'], 'veg') !== false) $icon = "bi-leaf";
                                    ?>
                                    <i class="bi <?= $icon ?> fs-4"></i>
                                </div>
                                <span class="fw-bold text-dark small">RM <?= number_format($item['price'], 2) ?></span>
                            </div>
                            <h6 class="fw-bold text-truncate"><?= htmlspecialchars($item['name']) ?></h6>
                        </div>
                        <button onclick="requireLogin(event, 'shop')" class="btn-cart-minimal mt-2">
                            <i class="bi bi-plus-lg me-1"></i> Add
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<a href="modules/shop/cart.php" class="floating-cart position-fixed text-white text-decoration-none d-flex align-items-center gap-3">
    <i class="bi bi-bag-fill fs-5"></i>
    <span class="fw-bold small">Bag (0)</span>
</a>

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    
    async function toggleSave(event, recipeId) {
        event.stopPropagation(); // Elak kad klik ke recipedetails
        event.preventDefault(); 
        if (!isLoggedIn) { window.location.href = 'modules/auth/login.php'; return; }

        const icon = event.currentTarget.querySelector('i');
        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);
            const response = await fetch('modules/recipe/toggle_save.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'saved') { 
                icon.classList.replace('bi-heart', 'bi-heart-fill'); 
                icon.style.transform = "scale(1.3)";
                setTimeout(() => icon.style.transform = "scale(1)", 200);
            }
            else { icon.classList.replace('bi-heart-fill', 'bi-heart'); }
        } catch (error) { console.error('Error:', error); }
    }

    function requireLogin(e, type) {
        e.stopPropagation(); // Elak kad klik jika ada wrapper
        if (!isLoggedIn) { window.location.href = 'modules/auth/login.php'; }
        else { alert("Added to your modern kitchen bag!"); }
    }
</script>

</body>
</html>