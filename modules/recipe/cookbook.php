<?php 
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;

if (!$isLoggedIn) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_recipe'])) {
    $title        = $_POST['title'];
    $cuisine      = $_POST['cuisine'];
    $meal_type    = $_POST['meal_type']; 
    $cooking_time = $_POST['cooking_time'];
    $instructions = $_POST['instructions'];
    $ingredients  = $_POST['ingredients'];
    $description  = $_POST['description'] ?? '';
    
    $image_url = "";
    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] == 0) {
        $image_url = uploadToCloudinary($_FILES['recipe_image']['tmp_name'], 'foodify/user_recipes');
        $conn->ping();
        if (!$image_url) {
            $ext = pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION);
            $image_url = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['recipe_image']['tmp_name'], "../../assets/images/recipes/" . $image_url);
        }
    }

    $stmt_insert = $conn->prepare("INSERT INTO created_recipes (user_id, title, cuisine, meal_type, cooking_time, ingredients, instructions, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("issssssss", $userId, $title, $cuisine, $meal_type, $cooking_time, $ingredients, $instructions, $image_url, $description);
    
    if ($stmt_insert->execute()) {
        header("Location: cookbook.php?success=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_recipe'])) {
    $cr_id        = (int)$_POST['cr_id'];
    $title        = $_POST['title'];
    $description  = $_POST['description'] ?? '';
    $cuisine      = $_POST['cuisine'];
    $meal_type    = $_POST['meal_type'];
    $cooking_time = $_POST['cooking_time'];
    $ingredients  = $_POST['ingredients'];
    $instructions = $_POST['instructions'];

    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] == 0) {
        $stmt_old = $conn->prepare("SELECT image FROM created_recipes WHERE cr_id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $cr_id, $userId);
        $stmt_old->execute();
        $old_data  = $stmt_old->get_result()->fetch_assoc();
        $old_image = $old_data['image'] ?? '';

        $new_image = uploadToCloudinary($_FILES['recipe_image']['tmp_name'], 'foodify/user_recipes');
        $conn->ping();

        if (!$new_image) {
            $ext = pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION);
            $new_image = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['recipe_image']['tmp_name'], "../../assets/images/recipes/" . $new_image);
        }

        $stmt = $conn->prepare("UPDATE created_recipes SET title=?, description=?, cuisine=?, meal_type=?, cooking_time=?, ingredients=?, instructions=?, image=? WHERE cr_id=? AND user_id=?");
        $stmt->bind_param("ssssssssii", $title, $description, $cuisine, $meal_type, $cooking_time, $ingredients, $instructions, $new_image, $cr_id, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE created_recipes SET title=?, description=?, cuisine=?, meal_type=?, cooking_time=?, ingredients=?, instructions=? WHERE cr_id=? AND user_id=?");
        $stmt->bind_param("sssssssii", $title, $description, $cuisine, $meal_type, $cooking_time, $ingredients, $instructions, $cr_id, $userId);
    }

    if ($stmt->execute()) {
        header("Location: cookbook.php?updated=1");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_recipe'])) {
    $cr_id = (int)$_POST['cr_id'];
    $stmt_old = $conn->prepare("SELECT image FROM created_recipes WHERE cr_id = ? AND user_id = ?");
    $stmt_old->bind_param("ii", $cr_id, $userId);
    $stmt_old->execute();
    $old_data  = $stmt_old->get_result()->fetch_assoc();
    $old_image = $old_data['image'] ?? '';

    $stmt = $conn->prepare("DELETE FROM created_recipes WHERE cr_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cr_id, $userId);
    if ($stmt->execute()) {
        if (!empty($old_image)) deleteFromCloudinary($old_image);
        header("Location: cookbook.php?deleted=1");
        exit();
    }
}

$stmt_nav = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
$stmt_nav->bind_param("i", $userId);
$stmt_nav->execute();
$user_nav = $stmt_nav->get_result()->fetch_assoc();
$username = $user_nav['username'] ?? 'Guest';
$nav_profile_img = $user_nav['profile_image'] ?? '';
$nav_role = $user_nav['role'] ?? 'Customer';

$stmt_my = $conn->prepare("SELECT * FROM created_recipes WHERE user_id = ? ORDER BY created_at DESC");
$stmt_my->bind_param("i", $userId);
$stmt_my->execute();
$my_recipes = $stmt_my->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_saved = $conn->prepare("
    SELECT r.*, 1 as is_saved FROM recipes r 
    JOIN saved_recipes s ON r.recipe_id = s.recipe_id 
    WHERE s.user_id = ? AND r.is_public = 1
");
$stmt_saved->bind_param("i", $userId);
$stmt_saved->execute();
$saved_recipes = $stmt_saved->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>My Cookbook – Foodify</title>
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

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdfdfd; color: #1A1C1E; overflow-x: hidden; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow-y: auto;
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

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 0; min-height: 100vh; }
        .header-section { padding: 3rem 4rem 2rem; background: white; border-bottom: 1px solid #f5f5f5; }
        .content-body { padding: 2rem 4rem; }

        .header-section { padding: 3rem 4rem 2rem; background: white; border-bottom: 1px solid #f5f5f5; }

        .content-body { padding: 2rem 4rem; }
        .top-bar h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 900;
    color: #1A1C1E;
    line-height: 1;
    margin: 0;
}

.top-bar p {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin-top: 0.8rem;
}
        
        .section-header { display: flex; align-items: center; gap: 15px; margin: 2rem 0 1rem; font-weight: 800; font-size: 1.5rem; color: #1A1C1E; }
        .section-header::after { content: ""; height: 1px; flex: 1; background: #eee; }

        .btn-create { background: var(--primary-grad); color: white; border: none; padding: 14px 28px; border-radius: 100px; font-weight: 700; transition: 0.3s; box-shadow: 0 10px 20px rgba(255,107,107,0.2); }
        .btn-create:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(255,107,107,0.3); color: white; }

        .recipe-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2.5rem; }
        .recipe-card { background: white; border-radius: 30px; overflow: hidden; border: 1px solid #f8f9fa; transition: 0.4s; position: relative; height: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .recipe-card:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(0,0,0,0.08); }
        .card-img-box { height: 210px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .card-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.6s ease; }
        .recipe-card:hover .card-img-box img { transform: scale(1.1); }
        .card-actions { position: absolute; top: 20px; left: 20px; display: flex; gap: 10px; z-index: 10; }
        .circle-btn { width: 40px; height: 40px; border-radius: 14px; border: none; background: rgba(255,255,255,0.9); display: flex; align-items: center; justify-content: center; transition: 0.3s; backdrop-filter: blur(5px); }
        .cuisine-badge { position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; font-size: 0.7rem; font-weight: 800; padding: 6px 14px; border-radius: 100px; text-transform: uppercase; border: 1px solid rgba(255,255,255,0.3); }
        .card-content { padding: 1.5rem; }
        .card-cat { color: var(--accent); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; display: block; }
        .card-title { font-weight: 800; font-size: 1.3rem; margin-bottom: 0.8rem; line-height: 1.3; }
        .card-text { color: #7f8c8d; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .card-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 1.2rem; border-top: 1px solid #f8f9fa; }
        .meta-item { display: flex; align-items: center; gap: 8px; color: #bdc3c7; font-size: 0.85rem; font-weight: 700; }
        .meta-item i { color: #1A1C1E; }

        .modal-content { border-radius: 35px; border: none; padding: 1rem; }
        #confirmOverlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 10000; display: none; align-items: center; justify-content: center; }
        .confirm-box { background: white; padding: 3rem; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }

        .form-hint { font-size: 0.75rem; color: #aaa; margin-top: 5px; display: block; }
        .form-hint i { color: var(--accent); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; padding-top: 5rem; }

            .header-section { padding: 1.2rem; }

            .content-body { padding: 1rem; }
            .top-bar h1 { font-size: 2rem; }
            .d-flex.justify-content-between { flex-direction: column; gap: 1rem; align-items: flex-start !important; }
            .btn-create { width: 100%; text-align: center; justify-content: center; display: flex; }
            .recipe-grid { grid-template-columns: 1fr; gap: 1.2rem; }
            .section-header { font-size: 1.2rem; }
            
        }
        .topbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: var(--sidebar-dark);
            padding: 1rem 1.5rem;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-logo {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 1.5rem;
            letter-spacing: -1px;
            background: var(--primary-grad);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hamburger {
            background: none;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
            padding: 4px;
        }
    </style>
</head>
<body>

<!-- ── TOPBAR (mobile) ── -->
<div class="topbar" id="topbar">
    <span class="topbar-logo">foodify.</span>
    <button class="hamburger" onclick="toggleSidebar()">
        <i class="bi bi-list" id="hamburgerIcon"></i>
    </button>
</div>

<!-- ── OVERLAY ── -->
<div id="sidebarOverlay" onclick="toggleSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:998;"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
    </div>
    <div class="sidebar-greet-box">
        <p>Your personal collection</p>
    </div>

    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="../shop/items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <li><a href="cookbook.php" class="active"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
        <li><a href="../order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
    </ul>

    <div class="sidebar-footer">
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
    </div>
</div>

<div class="main-content">
    <div class="header-section">
    <div class="header-topbar">
    <div class="top-bar">
        <h1>My Cookbook</h1>
        <p>Your personal laboratory of tastes and memories.</p>
    </div>

    <button class="btn-create d-flex align-items-center gap-2"
        data-bs-toggle="modal"
        data-bs-target="#createRecipeModal">
        <i class="bi bi-plus-lg"></i> Create New Recipe
    </button>
</div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 rounded-4 mt-4 shadow-sm">Recipe created successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success border-0 rounded-4 mt-4 shadow-sm">Recipe updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger border-0 rounded-4 mt-4 shadow-sm">Recipe deleted successfully!</div>
    <?php endif; ?>

    <div class="section-header">My Recipe Creations</div>
    <div class="recipe-grid">
        <?php foreach ($my_recipes as $recipe): 
            $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2D3436, #000000)';
            $icon = $icons[$recipe['meal_type']] ?? 'bi-journal-text';
        ?>
            <div class="recipe-card">
                <div class="card-actions">
                    <button class="circle-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($recipe)) ?>)"><i class="bi bi-pencil-square"></i></button>
                    <button class="circle-btn" onclick="confirmDelete(<?= $recipe['cr_id'] ?>)"><i class="bi bi-trash3 text-danger"></i></button>
                </div>
                <a href="recipedetail.php?id=<?= $recipe['cr_id'] ?>&type=user" class="text-decoration-none text-dark">
                    <div class="card-img-box" style="background: <?= $grad ?>;">
                        <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>">
                        <?php else: ?>
                            <i class="bi <?= $icon ?>"></i>
                        <?php endif; ?>
                        <span class="cuisine-badge"><?= $recipe['cuisine'] ?></span>
                    </div>
                    <div class="card-content">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="card-cat" style="margin-bottom:0;"><?= $recipe['meal_type'] ?></span>
                            <div class="meta-item">
                                <i class="bi bi-stopwatch"></i>
                                <?= htmlspecialchars($recipe['cooking_time'] ?? '20') ?> mins
                            </div>
                        </div>
                        <h3 class="card-title"><?= $recipe['title'] ?></h3>
                        <p class="card-text"><?= $recipe['description'] ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-header">Saved Recipes</div>
    <div class="recipe-grid">
        <?php foreach ($saved_recipes as $recipe): 
            $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2D3436, #000000)';
        ?>
            <div class="recipe-card">
                <div class="card-actions">
                    <button class="circle-btn active" onclick="toggleSave(event, <?= $recipe['recipe_id'] ?>)"><i class="bi bi-heart-fill text-danger"></i></button>
                </div>
                <a href="recipedetail.php?id=<?= $recipe['recipe_id'] ?>" class="text-decoration-none text-dark">
                    <div class="card-img-box" style="background: <?= $grad ?>;">
                        <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>">
                        <?php else: ?>
                            <i class="bi bi-egg-fried"></i>
                        <?php endif; ?>
                        <span class="cuisine-badge"><?= $recipe['cuisine'] ?></span>
                    </div>
                    <div class="card-content">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="card-cat" style="margin-bottom:0;"><?= $recipe['meal_type'] ?></span>
                            <div class="meta-item">
                                <i class="bi bi-stopwatch"></i>
                                <?= htmlspecialchars($recipe['cooking_time'] ?? '20') ?> mins
                            </div>
                        </div>
                        <h3 class="card-title"><?= $recipe['title'] ?></h3>
                        <p class="card-text"><?= $recipe['description'] ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- DELETE CONFIRM OVERLAY -->
<div id="confirmOverlay">
    <div class="confirm-box">
        <h3 class="fw-bold mb-3">Delete Recipe?</h3>
        <p class="text-muted">This action is permanent and cannot be undone.</p>
        <div class="d-flex gap-3 mt-4">
            <button class="btn btn-light w-100 rounded-pill py-2 fw-bold" onclick="closeConfirm()">Cancel</button>
            <form id="deleteForm" method="POST" class="w-100">
                <input type="hidden" name="cr_id" id="delete_cr_id">
                <button type="submit" name="delete_recipe" class="btn btn-danger w-100 rounded-pill py-2 fw-bold">Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- CREATE RECIPE MODAL -->
<div class="modal fade" id="createRecipeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">Create your recipe</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="cookbook.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Recipe Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Give your recipe a name" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Cuisine</label>
                            <select name="cuisine" class="form-select">
                                <option value="Melayu">Melayu</option>
                                <option value="Asian">Asian</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Briefly describe your dish"></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Keep it short and appetizing</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Meal Type</label>
                            <select name="meal_type" class="form-select">
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Snack">Snack</option>
                                <option value="Drinks">Drinks</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Time (Min)</label>
                            <input type="number" name="cooking_time" class="form-control" placeholder="Total cooking time" required>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Enter total time in minutes</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Image</label>
                            <input type="file" name="recipe_image" class="form-control" accept="image/*">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> JPG, PNG or WEBP recommended</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ingredients</label>
                            <textarea name="ingredients" class="form-control" rows="5" placeholder="List your ingredients here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each ingredient on a new line, include quantity and unit</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="5" placeholder="Write your cooking steps here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each step on a new line for better readability</span>
                        </div>
                    </div>
                    <div class="text-end mt-5">
                        <button type="submit" name="submit_recipe" class="btn-create px-5">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EDIT RECIPE MODAL -->
<div class="modal fade" id="editRecipeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">Update Your Recipe</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="cookbook.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="cr_id" id="edit_cr_id">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Recipe Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" placeholder="Give your recipe a name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cuisine</label>
                            <select name="cuisine" id="edit_cuisine" class="form-select">
                                <option value="Melayu">Melayu</option>
                                <option value="Asian">Asian</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2" placeholder="Briefly describe your dish"></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Keep it short and appetizing</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Meal Type</label>
                            <select name="meal_type" id="edit_meal_type" class="form-select">
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Snack">Snack</option>
                                <option value="Drinks">Drinks</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Time (Min)</label>
                            <input type="number" name="cooking_time" id="edit_cooking_time" class="form-control" placeholder="Total cooking time" required>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Enter total time in minutes</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Update Image</label>
                            <input type="file" name="recipe_image" class="form-control" accept="image/*">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Leave empty to keep current image</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ingredients</label>
                            <textarea name="ingredients" id="edit_ingredients" class="form-control" rows="5" placeholder="List your ingredients here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each ingredient on a new line, include quantity and unit</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instructions</label>
                            <textarea name="instructions" id="edit_instructions" class="form-control" rows="5" placeholder="Write your cooking steps here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each step on a new line for better readability</span>
                        </div>
                    </div>
                    <div class="text-end mt-5">
                        <button type="submit" name="update_recipe" class="btn-create px-5">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Mobile sidebar ──
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const icon    = document.getElementById('hamburgerIcon');
        const isOpen  = sidebar.classList.toggle('open');
        overlay.style.display = isOpen ? 'block' : 'none';
        icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
    }

    function checkTopbar() {
        document.getElementById('topbar').style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    }
    checkTopbar();
    window.addEventListener('resize', checkTopbar);

    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        link.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('open')) toggleSidebar();
        });
    });

    function openEditModal(recipe) {
        document.getElementById('edit_cr_id').value = recipe.cr_id;
        document.getElementById('edit_title').value = recipe.title;
        document.getElementById('edit_description').value = recipe.description || '';
        document.getElementById('edit_cuisine').value = recipe.cuisine;
        document.getElementById('edit_meal_type').value = recipe.meal_type;
        document.getElementById('edit_cooking_time').value = recipe.cooking_time;
        document.getElementById('edit_ingredients').value = recipe.ingredients;
        document.getElementById('edit_instructions').value = recipe.instructions;
        new bootstrap.Modal(document.getElementById('editRecipeModal')).show();
    }

    function confirmDelete(id) {
        document.getElementById('delete_cr_id').value = id;
        document.getElementById('confirmOverlay').style.display = 'flex';
    }

    function closeConfirm() { 
        document.getElementById('confirmOverlay').style.display = 'none'; 
    }
    
    async function toggleSave(event, id) {
        event.preventDefault(); 
        event.stopPropagation();
        const btn = event.currentTarget;
        const formData = new FormData(); 
        formData.append('recipe_id', id);
        try {
            const res = await fetch('toggle_save.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'removed') {
                const card = btn.closest('.recipe-card');
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 400);
            }
        } catch (e) { console.log(e); }
    }

    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 3000);
    });
</script>
</body>
</html>