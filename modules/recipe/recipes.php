<?php
session_start();
// Path database disesuaikan untuk keluar dari modules/recipe/ ke root
require_once __DIR__ . '/../../config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username   = $_SESSION['username'] ?? 'Guest';

// Get filters from URL
$meal_type = $_GET['meal_type'] ?? 'all';
$cuisine   = $_GET['cuisine']   ?? 'all';
$search    = trim($_GET['search'] ?? '');

// Build query
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
    $where[]  = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}

$whereSQL = implode(' AND ', $where);
$sql       = "SELECT * FROM recipes WHERE $whereSQL ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result  = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);
$count   = count($recipes);

// Gradient colours per cuisine (Sama macam index.php)
$gradients = [
    'Melayu'  => 'linear-gradient(135deg, #2E7D32, #9FA825)',
    'Western' => 'linear-gradient(135deg, #c0392b, #e74c3c)',
    'Asian'   => 'linear-gradient(135deg, #e67e22, #f39c12)',
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2E7D32; --green-light: #E8F5E9;
            --orange: #FF8F00; --dark: #1A1A1A;
            --muted: #777; --border: #EEEEEE;
            --sidebar-w: 270px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: #F7F9F7; color: var(--dark); }

        /* ── SIDEBAR (SAMA MACAM INDEX) ── */
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
            cursor: pointer; transition: all 0.2s ease-out;
        }
        .user-row:hover { background-color: #DDEEE6 !important; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .user-row i { font-size: 1.6rem; color: var(--green); }
        .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; transition: 0.2s; }
        .btn-side-login { background: var(--orange); color: white; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 2rem; min-height: 100vh; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 800; }

        /* ── SEARCH & PILLS ── */
        .search-wrap { position: relative; max-width: 420px; margin-bottom: 1.5rem; }
        .search-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .search-wrap input { width: 100%; padding: 12px 16px 12px 44px; border: 1.5px solid var(--border); border-radius: 50px; font-weight: 600; outline: none; transition: 0.2s; }
        .search-wrap input:focus { border-color: var(--orange); box-shadow: 0 0 0 4px rgba(255,143,0,0.08); }

        .filter-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #aaa; margin-bottom: 8px; letter-spacing: 0.05em; }
        .filter-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1.5rem; }
        .filter-pill { background: white; border: 1.5px solid var(--border); padding: 7px 18px; border-radius: 50px; font-size: 0.82rem; font-weight: 700; text-decoration: none; color: var(--dark); transition: 0.18s; }
        .filter-pill:hover { border-color: var(--orange); color: var(--orange); }
        .filter-pill.active { background: var(--orange); border-color: var(--orange); color: white; }
        .filter-pill.cuisine-active { background: var(--green); border-color: var(--green); color: white; }

        /* ── RECIPE GRID ── */
        .recipe-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .recipe-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.22s; border: 1.5px solid transparent; position: relative; }
        .recipe-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0,0,0,0.1); border-color: var(--orange); }
        
        .btn-save-recipe {
            position: absolute; top: 12px; left: 12px; z-index: 10;
            background: white; border: none; width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            color: #ccc; cursor: pointer; transition: 0.2s;
        }
        .btn-save-recipe.active { color: #ff4757; }
        .btn-save-recipe.active i::before { content: "\f415"; }

        .recipe-img { height: 170px; display: flex; align-items: center; justify-content: center; position: relative; }
        .recipe-img img { width: 100%; height: 100%; object-fit: cover; }
        .recipe-img i { font-size: 3.5rem; color: white; }
        .badge-cuisine { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.4); color: white; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 50px; }

        .recipe-info { padding: 1.2rem; }
        .recipe-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 0.4rem; color: var(--dark); }
        .recipe-desc {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2; /* <--- TAMBAH BARIS NI SAHAJA */
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0.8rem;
        }
        .recipe-footer { display: flex; align-items: center; justify-content: space-between; }
        .recipe-tag { background: var(--green-light); color: var(--green); font-size: 0.7rem; font-weight: 800; padding: 3px 12px; border-radius: 50px; }
        .recipe-time { font-size: 0.75rem; color: #aaa; font-weight: 600; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../../assets/images/logo.png" alt="Foodify">
        <div class="logo-text">
            <h2>Foodify</h2>
            <span>Recipes + Groceries</span>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="recipes.php" class="active"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="../shop/index.php"><i class="bi bi-bag-fill"></i> Shop</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="collection.php"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
            <li><a href="../order/index.php"><i class="bi bi-truck"></i> My Orders</a></li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-bottom">
        <?php if ($isLoggedIn): ?>
            <a href="../auth/profile.php" class="text-decoration-none" style="color: inherit;">
                <div class="user-row">
                    <i class="bi bi-person-circle"></i>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($username) ?></div>
                        <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Customer') ?></div>
                    </div>
                </div>
            </a>
            <a href="../auth/logout.php" class="btn-side btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn-side btn-side-login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <div class="top-bar mb-4">
        <h1>All Recipes</h1>
        <p class="text-muted">Discover delicious recipes from our community</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="" id="filterForm">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" name="search" id="searchInput" placeholder="Search recipes..." value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="meal_type" value="<?= htmlspecialchars($meal_type) ?>">
            <input type="hidden" name="cuisine" value="<?= htmlspecialchars($cuisine) ?>">
        </div>

        <div class="filter-label">Meal Type</div>
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

        <div class="filter-label">Cuisine</div>
        <div class="filter-pills">
            <?php
            $c_list = ['all', 'Melayu', 'Western', 'Asian'];
            foreach ($c_list as $c):
                $activeClass = ($cuisine === $c) ? 'cuisine-active' : '';
                $url = "?meal_type=$meal_type&cuisine=$c&search=".urlencode($search);
            ?>
                <a href="<?= $url ?>" class="filter-pill <?= $activeClass ?>"><?= ucfirst($c) ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="mb-3 fw-bold small text-muted">Showing <?= $count ?> results</div>

    <div class="recipe-grid">
        <?php foreach ($recipes as $recipe): 
            $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2E7D32, #9FA825)';
            $icon = $icons[$recipe['meal_type']] ?? 'bi-egg-fried';
        ?>
            <div class="recipe-card">
                <button class="btn-save-recipe" onclick="toggleSave(event, <?= $recipe['id'] ?>)">
                    <i class="bi bi-heart"></i>
                </button>

                <a href="detail.php?id=<?= $recipe['id'] ?>" class="text-decoration-none" style="color:inherit;">
                    <div class="recipe-img" style="background: <?= $grad ?>;">
                        <?php if ($recipe['image']): ?>
                            <img src="../../assets/images/recipes/<?= htmlspecialchars($recipe['image']) ?>" alt="">
                        <?php else: ?>
                            <i class="bi <?= $icon ?>"></i>
                        <?php endif; ?>
                        <span class="badge-cuisine"><?= htmlspecialchars($recipe['cuisine']) ?></span>
                    </div>
                    <div class="recipe-info">
                        <div class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></div>
                        <div class="recipe-desc"><?= htmlspecialchars($recipe['description']) ?></div>
                        <div class="recipe-footer">
                            <span class="recipe-tag"><?= htmlspecialchars($recipe['meal_type']) ?></span>
                            <span class="recipe-time"><i class="bi bi-clock"></i> <?= htmlspecialchars($recipe['cooking_time'] ?? '15m') ?></span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // System Heart Save (Sama macam index.php)
    function toggleSave(event, recipeId) {
        event.preventDefault(); 
        event.stopPropagation();
        const btn = event.currentTarget;
        btn.classList.toggle('active');
        
        let saved = JSON.parse(localStorage.getItem('saved_recipes') || '[]');
        if (btn.classList.contains('active')) {
            if(!saved.includes(recipeId)) saved.push(recipeId);
        } else {
            saved = saved.filter(id => id !== recipeId);
        }
        localStorage.setItem('saved_recipes', JSON.stringify(saved));
    }

    document.addEventListener('DOMContentLoaded', () => {
        let saved = JSON.parse(localStorage.getItem('saved_recipes') || '[]');
        document.querySelectorAll('.btn-save-recipe').forEach(btn => {
            const onclickAttr = btn.getAttribute('onclick');
            const idMatch = onclickAttr.match(/\d+/);
            if(idMatch && saved.includes(parseInt(idMatch[0]))) {
                btn.classList.add('active');
            }
        });
    });

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