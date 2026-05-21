<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

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

// Get filters from URL
$meal_type = $_GET['meal_type'] ?? 'all';
$cuisine   = $_GET['cuisine']   ?? 'all';
$search    = trim($_GET['search'] ?? '');

$where  = ["is_public = 1"];
$params = [];
$types  = '';

if ($meal_type !== 'all') {
    $where[]  = "meal_type = ?";
    $params[] = $meal_type;
    $types   .= 's';
}

if ($cuisine !== 'all') {
    $where[]  = "cuisine = ?";
    $params[] = $cuisine;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "title LIKE ?";
    $params[] = "%$search%";
    $types   .= 's';
}

$whereSQL = implode(' AND ', $where);

$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM saved_recipes s WHERE s.recipe_id = r.recipe_id AND s.user_id = ?) as is_saved 
        FROM recipes r WHERE $whereSQL ORDER BY created_at DESC";

$allParams = array_merge([$userId], $params);
$allTypes  = 'i' . $types;

$stmt = $conn->prepare($sql);
if (!empty($allParams)) {
    $stmt->bind_param($allTypes, ...$allParams);
}
$stmt->execute();
$result  = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);
$count   = count($recipes);

$gradients = [
    'Melayu'  => 'linear-gradient(135deg, #1e5128, #4e944f)',
    'Western' => 'linear-gradient(135deg, #800000, #d90429)',
    'Asian'   => 'linear-gradient(135deg, #ff4d00, #ffb703)',
];

$icons = [
    'Breakfast' => 'bi-egg-fried',
    'Lunch'     => 'bi-cup-straw',
    'Dinner'    => 'bi-egg',
    'Dessert'   => 'bi-cake2',
    'Snack'     => 'bi-apple',
    'Drinks'    => 'bi-cup',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipes – Foodify</title>
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

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-bg);
            color: #1A1C1E;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow-y: auto;
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
        .sidebar-nav a.active {
            background: var(--primary-grad); color: white;
            box-shadow: 0 10px 20px rgba(255,107,107,0.25);
        }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        padding: 15px;
        border-radius: 20px;
        transition: all 0.2s ease;
        cursor: pointer;
        }
        .user-card:hover {
            background: rgba(255,255,255,0.07);
            transform: translateY(-2px);
        }
        .user-card:active {
            transform: scale(0.95);
            background: rgba(255,255,255,0.1);
        }
        .user-card img {
            transition: transform 0.3s ease;
        }
        .user-card:hover img {
            transform: rotate(5deg);
        }

        /* ── MAIN CONTENT ── */
        .main-content { 
            margin-left: var(--sidebar-w); 
            padding: 0; 
            min-height: 100vh;
            background: white;
        }

        .header-section {
            padding: 3rem 4rem 1.5rem 4rem;
            background: #fff;
        }

        .top-bar-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.0rem;
        }

        .top-bar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem; font-weight: 900;
            color: #1A1C1E; line-height: 1; margin: 0;
        }
        .top-bar p { color: #7f8c8d; font-size: 1.1rem; margin-top: 0.8rem; }

        .search-container {
            position: relative;
            width: 350px;
        }
        .search-container input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border-radius: 100px;
            border: 1px solid #f0f0f0;
            background: #f8f9fa;
            font-weight: 500;
            transition: 0.3s;
        }
        .search-container input:focus {
            background: white;
            border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(255,142,83,0.1);
            outline: none;
        }
        .search-container i {
            position: absolute; left: 20px; top: 50%;
            transform: translateY(-50%); color: #95a5a6;
            font-size: 1.2rem;
        }

        .filters-wrapper {
            padding: 0 4rem 2.5rem 4rem;
            border-bottom: 1px solid #f5f5f5;
        }
        .filter-group { margin-bottom: 1.5rem; }
        .filter-label {
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
            color: #bdc3c7; margin-bottom: 12px; letter-spacing: 1.5px;
            display: block;
        }
        .filter-pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-pill {
            background: transparent; border: 1px solid #eee;
            padding: 8px 22px; border-radius: 100px;
            font-size: 0.85rem; font-weight: 600;
            text-decoration: none; color: #444; transition: 0.3s;
        }
        .filter-pill:hover { border-color: var(--accent); color: var(--accent); background: #fffaf9; }
        .filter-pill.active {
            background: var(--primary-grad); border-color: transparent; color: white;
            box-shadow: 0 8px 20px rgba(255,107,107,0.2);
        }
        .filter-pill.cuisine-active {
            background: #1A1C1E; border-color: #1A1C1E; color: white;
        }

        .content-body {  padding: 2rem 4rem; background: #fdfdfd; }
        .results-info { 
            font-weight: 700; color: #bdc3c7; 
            margin-bottom: 2rem; display: block;
        }
        
        .recipe-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 2.5rem; 
        }

        .recipe-card {
            background: white; border-radius: 30px; overflow: hidden;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #f8f9fa; position: relative;
            box-shadow: var(--card-shadow);
        }
        .recipe-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
        }

        .card-img-box { 
            height: 210px; position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .card-img-box img { 
            width: 100%; height: 100%; object-fit: cover; 
            transition: 0.6s ease;
        }
        .recipe-card:hover .card-img-box img { transform: scale(1.1); }
        .card-img-box i { font-size: 4rem; color: white; opacity: 0.8; }

        .btn-heart {
            position: absolute; top: 20px; left: 20px; z-index: 10;
            background: rgba(255,255,255,0.9); border: none; 
            width: 45px; height: 45px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            color: #d1d1d1; cursor: pointer; transition: 0.3s;
            backdrop-filter: blur(5px);
        }
        .btn-heart:hover { transform: scale(1.1); color: #ff6b6b; }
        .btn-heart.active { color: #ff4757; background: #fff; }

        .cuisine-badge {
            position: absolute; bottom: 20px; right: 20px;
            background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
            color: white; border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
            padding: 6px 14px; border-radius: 100px;
        }

        .card-content { padding: 1.2rem; }
        .card-cat {
            color: var(--accent); font-size: 0.75rem; 
            font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 0.5rem; display: block;
        }
        .card-title { 
            font-weight: 800; font-size: 1.3rem; 
            color: #1A1C1E; margin-bottom: 0.8rem;
            display: -webkit-box; -webkit-line-clamp: 1; line-clamp: 3;
 -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-text {
            color: #7f8c8d; font-size: 0.9rem; line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-meta {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 1.2rem; border-top: 1px solid #f8f9fa;
        }
        .meta-item { display: flex; align-items: center; gap: 8px; color: #bdc3c7; font-size: 0.85rem; font-weight: 600; }
        .meta-item i { color: #1A1C1E; }

        @media (max-width: 992px) {
            .header-section, .filters-wrapper, .content-body { padding: 2rem; }
            .top-bar-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .search-container { width: 100%; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
    </div>
    <div class="sidebar-greet-box">
        <p>What are we cooking today?</p>
    </div>

    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="recipes.php" class="active"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="../shop/items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
            <li><a href="../order/order.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <?php if ($isLoggedIn): ?>
            <a href="../profile/profile.php" class="text-decoration-none d-block">
                <div class="user-card d-flex align-items-center gap-3 mb-3">
                <?php $navProfileSrc = getImageSrc($nav_profile_img, '../../assets/images/profiles/'); ?>
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
            <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-light w-100 rounded-3 py-3 fw-bold shadow-sm">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    
    <div class="header-section">

    <!-- TITLE -->
    <div class="top-bar-flex">
        <div class="top-bar">
            <h1>Recipes For You</h1>
            <p>Tasty ideas for everyday cooking.</p>
        </div>
    </div>

    <!-- SEARCH -->
    <form method="GET" action="" id="filterForm">
        <div class="search-container">
            <i class="bi bi-search"></i>

            <input 
                type="text"
                id="searchInput"
                name="search"
                placeholder="Search for inspiration..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <input 
                type="hidden" 
                name="meal_type" 
                value="<?= htmlspecialchars($meal_type) ?>"
            >

            <input 
                type="hidden" 
                name="cuisine" 
                value="<?= htmlspecialchars($cuisine) ?>"
            >
        </div>
</div>

    <div class="filters-wrapper">
        <div class="filter-group">
            <span class="filter-label">Meal Category</span>
            <div class="filter-pills">
                <?php
                $mt_list = ['all', 'Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Snack', 'Drinks'];
                foreach ($mt_list as $mt):
                    $activeClass = ($meal_type === $mt) ? 'active' : '';
                    $url = "?meal_type=$mt&cuisine=$cuisine&search=".urlencode($search);
                ?>
                    <a href="<?= $url ?>" class="filter-pill <?= $activeClass ?>"><?= ucfirst($mt) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filter-group mb-0">
            <span class="filter-label">Origin & Culture</span>
            <div class="filter-pills">
                <?php
                $c_list = ['all', 'Melayu', 'Western', 'Asian'];
                foreach ($c_list as $c):
                    $activeClass = ($cuisine === $c) ? 'cuisine-active active' : '';
                    $url = "?meal_type=$meal_type&cuisine=$c&search=".urlencode($search);
                ?>
                    <a href="<?= $url ?>" class="filter-pill <?= $activeClass ?>"><?= ucfirst($c) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </form>

    <div class="content-body">
        <span class="results-info"><?= $count ?> delicious recipes found</span>

        <div class="recipe-grid">
            <?php if ($count > 0): ?>
                <?php foreach ($recipes as $recipe): 
                    $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2D3436, #000000)';
                    $icon = $icons[$recipe['meal_type']] ?? 'bi-egg-fried';
                    $savedActive = ($recipe['is_saved'] > 0) ? 'active' : '';
                ?>
                    <div class="recipe-card">
                        <button class="btn-heart <?= $savedActive ?>" onclick="toggleSave(event, <?= $recipe['recipe_id'] ?>)">
                            <i class="bi bi-heart-fill"></i>
                        </button>

                        <a href="recipedetail.php?id=<?= $recipe['recipe_id'] ?>" class="text-decoration-none">
                            <div class="card-img-box" style="background: <?= $grad ?>;">
                                <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
                                <?php if ($imgSrc): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
                                <?php else: ?>
                                    <i class="bi <?= $icon ?>"></i>
                                <?php endif; ?>
                                <span class="cuisine-badge"><?= htmlspecialchars($recipe['cuisine']) ?></span>
                            </div>

                            <div class="card-content">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="card-cat" style="margin-bottom:0;"><?= htmlspecialchars($recipe['meal_type']) ?></span>
                                    <div class="meta-item">
                                        <i class="bi bi-stopwatch"></i>
                                        <?= htmlspecialchars($recipe['cooking_time'] ?? '20') ?> mins
                                    </div>
                                </div>
                                <h3 class="card-title"><?= htmlspecialchars($recipe['title']) ?></h3>
                                <p class="card-text"><?= htmlspecialchars($recipe['description']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 w-100" style="grid-column: 1 / -1;">
                    <i class="bi bi-search" style="font-size: 3rem; color: #eee;"></i>
                    <p class="mt-3 text-muted">No recipes found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    async function toggleSave(event, recipeId) {
        event.preventDefault(); 
        event.stopPropagation();
        
        if (!isLoggedIn) {
            window.location.href = '../auth/login.php';
            return;
        }

        const btn = event.currentTarget;
        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);

            const response = await fetch('toggle_save.php', {
                method: 'POST',
                body: formData
            });

            // Sekarang guna FormData dan check status betul:
            const data = await response.json();
            if (data.status === 'saved') {
                btn.classList.add('active');
            } else if (data.status === 'removed') {
                btn.classList.remove('active');
            }
        } catch (e) { 
            console.error(e); 
        }
    }

    // Auto submit search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(window.searchTimer);
            window.searchTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 700);
        });
    }
</script>
</body>
</html>