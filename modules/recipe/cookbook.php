<?php
session_start();

// Database connection
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;

if (!$isLoggedIn) {
    header("Location: ../auth/login.php");
    exit();
}

// --- LOGIC SIMPAN RECIPE (POST) ---
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

// --- LOGIC UPDATE RECIPE ---
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
        // Ambil gambar lama dari DB dulu
        $stmt_old = $conn->prepare("SELECT image FROM created_recipes WHERE cr_id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $cr_id, $userId);
        $stmt_old->execute();
        $old_data  = $stmt_old->get_result()->fetch_assoc();
        $old_image = $old_data['image'] ?? '';

        // Upload gambar baru ke Cloudinary
        $new_image = uploadToCloudinary($_FILES['recipe_image']['tmp_name'], 'foodify/user_recipes');
        $conn->ping();

        // Delete gambar lama dari Cloudinary kalau upload baru berjaya
        if ($new_image && !empty($old_image)) {
            deleteFromCloudinary($old_image);
        }

        // Fallback local kalau Cloudinary gagal
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

// --- LOGIC DELETE RECIPE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_recipe'])) {
    $cr_id = (int)$_POST['cr_id'];

    // Ambil gambar lama sebelum delete
    $stmt_old = $conn->prepare("SELECT image FROM created_recipes WHERE cr_id = ? AND user_id = ?");
    $stmt_old->bind_param("ii", $cr_id, $userId);
    $stmt_old->execute();
    $old_data  = $stmt_old->get_result()->fetch_assoc();
    $old_image = $old_data['image'] ?? '';

    // Delete dari DB
    $stmt = $conn->prepare("DELETE FROM created_recipes WHERE cr_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cr_id, $userId);
    if ($stmt->execute()) {
        // Delete gambar dari Cloudinary selepas DB berjaya delete
        deleteFromCloudinary($old_image);
        header("Location: cookbook.php?deleted=1");
        exit();
    }
}

// Data Navbar
$stmt_nav = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
$stmt_nav->bind_param("i", $userId);
$stmt_nav->execute();
$user_nav = $stmt_nav->get_result()->fetch_assoc();
$username = $user_nav['username'] ?? 'Guest';
$nav_profile_img = $user_nav['profile_image'] ?? '';
$nav_role = $user_nav['role'] ?? 'Customer';

// Tarik data My Creations
$stmt_my = $conn->prepare("SELECT * FROM created_recipes WHERE user_id = ? ORDER BY created_at DESC");
$stmt_my->bind_param("i", $userId);
$stmt_my->execute();
$my_recipes = $stmt_my->get_result()->fetch_all(MYSQLI_ASSOC);

// Tarik data Saved Recipes
$stmt_saved = $conn->prepare("
    SELECT r.*, 1 as is_saved FROM recipes r 
    JOIN saved_recipes s ON r.recipe_id = s.recipe_id 
    WHERE s.user_id = ?
");
$stmt_saved->bind_param("i", $userId);
$stmt_saved->execute();
$saved_recipes = $stmt_saved->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>My Cookbooks – Foodify</title>
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
            cursor: pointer; transition: all 0.2s ease-out;
        }
        .user-row:hover { background-color: #DDEEE6 !important; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .user-avatar-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; transition: 0.2s; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 2.5rem; min-height: 100vh; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 800; }

        /* ── RECIPE GRID & CARD ── */
        .section-header { border-left: 5px solid var(--orange); padding-left: 15px; margin: 40px 0 25px; font-weight: 800; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
        
        .recipe-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .recipe-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.22s; border: 1.5px solid transparent; position: relative; height: 100%; }
        .recipe-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0,0,0,0.1); border-color: var(--orange); }
        
        .btn-save-recipe {
            position: absolute; top: 12px; left: 12px; z-index: 10;
            background: white; border: none; width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            color: #ccc; cursor: pointer; transition: 0.2s;
        }
        .btn-save-recipe.active { color: #ff4757; }
        .btn-save-recipe.active i::before { content: "\f415"; }

        .btn-edit-recipe {
            position: absolute; top: 12px; left: 12px; z-index: 10;
            background: white; border: none; width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            color: var(--green); cursor: pointer; transition: 0.2s;
        }
        .btn-edit-recipe:hover { background: var(--green); color: white; }

        .btn-delete-recipe {
            position: absolute; top: 12px; left: 52px; z-index: 10;
            background: white; border: none; width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
            color: #DC2626; cursor: pointer; transition: 0.2s;
        }
        .btn-delete-recipe:hover { background: #DC2626; color: white; }

        /* Custom Delete Confirm Popup */
        .confirm-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: 0.2s;
        }
        .confirm-overlay.show { opacity: 1; pointer-events: all; }
        .confirm-box {
            background: white; border-radius: 24px; padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); text-align: center;
            width: 100%; max-width: 380px; transform: scale(0.9); transition: 0.2s;
        }
        .confirm-overlay.show .confirm-box { transform: scale(1); }
        .confirm-icon {
            width: 65px; height: 65px; background: #FEE2E2; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .confirm-icon i { font-size: 1.8rem; color: #DC2626; }
        .confirm-title { font-weight: 800; font-size: 1.2rem; margin-bottom: 0.4rem; }
        .confirm-msg { font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem; }
        .confirm-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-confirm-cancel {
            padding: 10px 24px; border-radius: 50px; border: 1.5px solid #eee;
            background: white; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s;
        }
        .btn-confirm-cancel:hover { background: #f5f5f5; }
        .btn-confirm-delete {
            padding: 10px 24px; border-radius: 50px; border: none;
            background: #DC2626; color: white; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: 0.2s;
        }
        .btn-confirm-delete:hover { background: #b91c1c; }

        .recipe-img { height: 170px; display: flex; align-items: center; justify-content: center; position: relative; }
        .recipe-img img { width: 100%; height: 100%; object-fit: cover; }
        .recipe-img i { font-size: 3.5rem; color: white; }
        .badge-cuisine { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.4); color: white; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 50px; }

        .recipe-info { padding: 1.2rem; }
        .recipe-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 0.4rem; color: var(--dark); }
        .recipe-desc {
            font-size: 0.8rem; color: var(--muted); line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 0.8rem;
        }
        .recipe-footer { display: flex; align-items: center; justify-content: space-between; }
        .recipe-tag { background: var(--green-light); color: var(--green); font-size: 0.7rem; font-weight: 800; padding: 3px 12px; border-radius: 50px; }
        .recipe-time { font-size: 0.75rem; color: #aaa; font-weight: 600; }

        .btn-create { background: var(--green); color: white; border-radius: 50px; padding: 12px 25px; border: none; font-weight: 700; transition: 0.3s; }
        .btn-create:hover { background: #1B5E20; color: white; box-shadow: 0 4px 12px rgba(46,125,50,0.3); }

        #createRecipeModal .modal-header,
        #editRecipeModal .modal-header { padding: 1.2rem 1.5rem 0.5rem; }
        #createRecipeModal .modal-body,
        #editRecipeModal .modal-body { padding: 0.5rem 1.5rem 1.5rem; }
        #createRecipeModal .form-control,
        #createRecipeModal .form-select,
        #editRecipeModal .form-control,
        #editRecipeModal .form-select { padding: 8px 12px; font-size: 0.88rem; }

        .modal-content { border-radius: 25px; border: none; }
        .modal-header { border-bottom: none; padding: 2rem 2rem 1rem; }
        .modal-body { padding: 1rem 2rem 2rem; }
        .form-control, .form-select { border-radius: 12px; padding: 12px; border: 1.5px solid #eee; }
        .form-control:focus { border-color: var(--green); box-shadow: none; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .main-content { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../../assets/images/logo.png" alt="Foodify">
        <div class="logo-text"><h2>Foodify</h2><span>Recipes + Groceries</span></div>
    </div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="recipes.php"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="../shop/index.php"><i class="bi bi-bag-fill"></i> Shop</a></li>
        <li><a href="cookbook.php" class="active"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
        <li><a href="../order/index.php"><i class="bi bi-truck"></i> My Orders</a></li>
    </ul>
    <div class="sidebar-bottom">
        <a href="../profile/profile.php" class="text-decoration-none" style="color: inherit;">
            <div class="user-row">
                <?php 
                $imgPath = "../../assets/images/profiles/" . $nav_profile_img;
                if (!empty($nav_profile_img) && file_exists($imgPath)): ?>
                    <img src="<?= $imgPath ?>" class="user-avatar-img">
                <?php else: ?>
                    <i class="bi bi-person-circle" style="font-size:1.6rem; color:var(--green);"></i>
                <?php endif; ?>
                <div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= htmlspecialchars($nav_role) ?></div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn-side btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </div>
</div>

<div class="main-content">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success rounded-3 mb-3">
            <i class="bi bi-check-circle-fill me-2"></i> Recipe created successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success rounded-3 mb-3">
            <i class="bi bi-check-circle-fill me-2"></i> Recipe updated successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger rounded-3 mb-3">
            <i class="bi bi-trash-fill me-2"></i> Recipe deleted successfully!
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>My Cookbooks</h1>
            <p class="text-muted">Manage your own culinary creations and saved favorites</p>
        </div>
        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#createRecipeModal">
            <i class="bi bi-plus-lg"></i> New Recipe
        </button>
    </div>

    <!-- MY CREATIONS SECTION -->
    <div class="section-header">My Creations</div>
    <div class="recipe-grid">
        <?php if (empty($my_recipes)): ?>
            <p class="text-muted ps-3">You haven't created any recipes yet.</p>
        <?php endif; ?>

        <?php foreach ($my_recipes as $recipe): 
            $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2E7D32, #9FA825)';
            $icon = $icons[$recipe['meal_type']] ?? 'bi-journal-text';
        ?>
            <div class="recipe-card">
                <button class="btn-edit-recipe" onclick="openEditModal(<?= htmlspecialchars(json_encode($recipe)) ?>)">
                    <i class="bi bi-pencil-fill"></i>
                </button>

                <button class="btn-delete-recipe" onclick="confirmDelete(<?= $recipe['cr_id'] ?>)">
                    <i class="bi bi-trash-fill"></i>
                </button>

                <a href="recipedetail.php?id=<?= $recipe['cr_id'] ?>&type=user" class="text-decoration-none" style="color:inherit; display:block;">
                    <div class="recipe-img" style="background: <?= $grad ?>;">
                        <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
                        <?php else: ?>
                            <i class="bi <?= $icon ?>"></i>
                        <?php endif; ?>
                        <span class="badge-cuisine"><?= htmlspecialchars($recipe['cuisine']) ?></span>
                    </div>
                    <div class="recipe-info">
                        <div class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></div>
                        <div class="recipe-desc"><?= htmlspecialchars($recipe['description'] ?? 'No description provided.') ?></div>
                        <div class="recipe-footer">
                            <span class="recipe-tag"><?= htmlspecialchars($recipe['meal_type']) ?></span>
                            <span class="recipe-time"><i class="bi bi-clock"></i> <?= htmlspecialchars($recipe['cooking_time']) ?>m</span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- SAVED FAVORITES SECTION -->
    <div class="section-header mt-5">Saved Favorites</div>
    <div class="recipe-grid">
        <?php if (empty($saved_recipes)): ?>
            <p class="text-muted ps-3">No saved recipes yet. Explore and save some!</p>
        <?php endif; ?>

        <?php foreach ($saved_recipes as $recipe): 
            $grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2E7D32, #9FA825)';
            $icon = $icons[$recipe['meal_type']] ?? 'bi-egg-fried';
            $savedActive = 'active'; 
        ?>
            <div class="recipe-card">
                <button class="btn-save-recipe <?= $savedActive ?>" onclick="toggleSave(event, <?= $recipe['recipe_id'] ?>)">
                    <i class="bi bi-heart"></i>
                </button>

                <a href="recipedetail.php?id=<?= $recipe['recipe_id'] ?>" class="text-decoration-none" style="color:inherit;">
                    <div class="recipe-img" style="background: <?= $grad ?>;">
                        <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
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

<!-- CREATE RECIPE MODAL -->
<div class="modal fade" id="createRecipeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="fw-bold" style="font-family:'Playfair Display', serif;">Share Your Recipe</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="cookbook.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Recipe Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Nasi Lemak Sambal Sotong" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Short Description</label>
                            <textarea name="description" class="form-control" rows="1" placeholder="Briefly describe your dish..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cuisine</label>
                            <select name="cuisine" class="form-select" required>
                                <option value="Melayu">Melayu</option>
                                <option value="Asian">Asian</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Meal Type</label>
                            <select name="meal_type" class="form-select" required>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Snack">Snack</option>
                                <option value="Drinks">Drinks</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cooking Time (Mins)</label>
                            <input type="number" name="cooking_time" class="form-control" placeholder="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Recipe Image</label>
                            <input type="file" name="recipe_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Ingredients</label>
                            <textarea name="ingredients" class="form-control" rows="2" placeholder="List items separated by commas or lines..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="3" placeholder="Step 1: Prep... Step 2: Cook... separated by lines" required></textarea>
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="submit_recipe" class="btn btn-success rounded-pill px-5 fw-bold">Create Now</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EDIT RECIPE MODAL -->
<div class="modal fade" id="editRecipeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="fw-bold" style="font-family:'Playfair Display', serif;">Edit Recipe</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="cookbook.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="cr_id" id="edit_cr_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <img id="edit_img_preview" src="" style="width:100%;height:180px;object-fit:cover;border-radius:12px;display:none;" class="mb-2">
                            <label class="form-label fw-bold">Change Image <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="recipe_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Recipe Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Short Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="1"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cuisine</label>
                            <select name="cuisine" id="edit_cuisine" class="form-select" required>
                                <option value="Melayu">Melayu</option>
                                <option value="Asian">Asian</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Meal Type</label>
                            <select name="meal_type" id="edit_meal_type" class="form-select" required>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Snack">Snack</option>
                                <option value="Drinks">Drinks</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cooking Time (Mins)</label>
                            <input type="number" name="cooking_time" id="edit_cooking_time" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Ingredients</label>
                            <textarea name="ingredients" id="edit_ingredients" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Instructions</label>
                            <textarea name="instructions" id="edit_instructions" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_recipe" class="btn btn-success rounded-pill px-5 fw-bold">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Confirm Popup -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon">
            <i class="bi bi-trash-fill"></i>
        </div>
        <div class="confirm-title">Delete Recipe?</div>
        <div class="confirm-msg">This action cannot be undone. Your recipe will be permanently removed.</div>
        <div class="confirm-actions">
            <button class="btn-confirm-cancel" onclick="closeConfirm()">Cancel</button>
            <button class="btn-confirm-delete" onclick="submitDelete()">Yes, Delete</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    async function toggleSave(event, recipeId) {
        event.preventDefault(); 
        event.stopPropagation();
        const btn = event.currentTarget;
        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);
            const response = await fetch('toggle_save.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'removed') {
            btn.closest('.recipe-card').remove();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function openEditModal(recipe) {
        document.getElementById('edit_cr_id').value        = recipe.cr_id;
        document.getElementById('edit_title').value        = recipe.title;
        document.getElementById('edit_description').value  = recipe.description ?? '';
        document.getElementById('edit_cuisine').value      = recipe.cuisine;
        document.getElementById('edit_meal_type').value    = recipe.meal_type;
        document.getElementById('edit_cooking_time').value = recipe.cooking_time;
        document.getElementById('edit_ingredients').value  = recipe.ingredients;
        document.getElementById('edit_instructions').value = recipe.instructions;

        const preview = document.getElementById('edit_img_preview');
        if (recipe.image) {
            preview.src = recipe.image.startsWith('http')
                ? recipe.image
                : '../../assets/images/recipes/' + recipe.image;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
        new bootstrap.Modal(document.getElementById('editRecipeModal')).show();
    }

    let pendingDeleteId = null;

    function confirmDelete(crId) {
        pendingDeleteId = crId;
        document.getElementById('confirmOverlay').classList.add('show');
    }

    function closeConfirm() {
        document.getElementById('confirmOverlay').classList.remove('show');
        pendingDeleteId = null;
    }

    function submitDelete() {
        if (!pendingDeleteId) return;

        const formData = new FormData();
        formData.append('cr_id', pendingDeleteId);
        formData.append('delete_recipe', '1');

        fetch('cookbook.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (res.ok) {
                window.location.href = 'cookbook.php?deleted=1';
            }
        })
        .catch(err => console.error(err));
    }

    document.getElementById('confirmOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeConfirm();
    });

    // Auto hide alerts after 3 seconds
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