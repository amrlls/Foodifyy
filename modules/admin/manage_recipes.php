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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    $recipeId = intval($_POST['recipe_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM recipes WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipeId);
    echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recipe'])) {
    $recipeId     = intval($_POST['recipe_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $cuisine      = $_POST['cuisine'] ?? '';
    $meal_type    = $_POST['meal_type'] ?? '';
    $cooking_time = trim($_POST['cooking_time'] ?? '');
    $ingredients  = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $is_public    = isset($_POST['is_public']) ? 1 : 0;

    $image = $_POST['existing_image'] ?? '';
    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] == 0) {
        $new_image = uploadToCloudinary($_FILES['recipe_image']['tmp_name'], 'foodify/admin_recipes');
        $conn->ping();
        if (!$new_image) {
            $ext = pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION);
            $new_image = 'recipe_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['recipe_image']['tmp_name'], '../../assets/images/recipes/' . $new_image);
        }
        $image = $new_image;
    }

    $video_url = $_POST['existing_video'] ?? '';
    if (isset($_FILES['recipe_video']) && $_FILES['recipe_video']['error'] == 0) {
        $new_video = uploadVideoToCloudinary($_FILES['recipe_video']['tmp_name'], 'foodify/recipe_video');
        $conn->ping();
        if (!$new_video) {
            $ext = pathinfo($_FILES['recipe_video']['name'], PATHINFO_EXTENSION);
            $new_video = 'recipe_video_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['recipe_video']['tmp_name'], '../../assets/images/recipes/' . $new_video);
        }
        $video_url = $new_video;
    }

    if ($recipeId > 0) {
        $stmt = $conn->prepare("UPDATE recipes SET title=?, description=?, cuisine=?, meal_type=?, cooking_time=?, ingredients=?, instructions=?, image=?, video_url=?, is_public=?, updated_at=NOW() WHERE recipe_id=?");
        $stmt->bind_param("sssssssssii", $title, $description, $cuisine, $meal_type, $cooking_time, $ingredients, $instructions, $image, $video_url, $is_public, $recipeId);
        if ($stmt->execute()) { header("Location: manage_recipes.php?status=updated"); exit; }
    } else {
        $stmt = $conn->prepare("INSERT INTO recipes (title, description, cuisine, meal_type, cooking_time, ingredients, instructions, image, video_url, is_public) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssi", $title, $description, $cuisine, $meal_type, $cooking_time, $ingredients, $instructions, $image, $video_url, $is_public);
        if ($stmt->execute()) { header("Location: manage_recipes.php?status=created"); exit; }
    }
}

$search     = trim($_GET['search'] ?? '');
$cuisine    = $_GET['cuisine'] ?? 'all';
$visibility = $_GET['visibility'] ?? 'all';

$where  = ["1=1"];
$params = [];
$types  = '';

if ($search !== '') { $where[] = "title LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($cuisine !== 'all') { $where[] = "cuisine = ?"; $params[] = $cuisine; $types .= 's'; }
if ($visibility === 'public')  { $where[] = "is_public = 1"; }
if ($visibility === 'private') { $where[] = "is_public = 0"; }

$whereSQL = implode(' AND ', $where);
$sql = "SELECT * FROM recipes WHERE $whereSQL ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$recipes  = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count    = count($recipes);
$cuisines = $conn->query("SELECT DISTINCT cuisine FROM recipes ORDER BY cuisine")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipes – Admin</title>
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
        .recipe-row { display: grid; grid-template-columns: 60px 1fr 120px 100px 120px 130px; gap: 1rem; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f8f9fa; transition: 0.2s; }
        .recipe-row:last-child { border-bottom: none; }
        .recipe-row:hover { background: #fafafa; }
        .recipe-row.header { background: #f8f9fa; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #bdc3c7; padding: 0.8rem 1.5rem; }
        .recipe-row > div { text-align: center; }
        .recipe-row > div:nth-child(1), .recipe-row > div:nth-child(2),
        .recipe-row.header > div:nth-child(1), .recipe-row.header > div:nth-child(2) { text-align: left; }

        .recipe-img { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #f0f0f0; }
        .recipe-img img { width: 100%; height: 100%; object-fit: cover; }
        .recipe-img i { font-size: 1.2rem; color: #bdc3c7; }
        .recipe-title { font-weight: 700; font-size: 0.9rem; color: #1A1C1E; }

        .cuisine-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .cuisine-melayu  { background: rgba(255,107,107,0.12); color: #e74c3c; }
        .cuisine-asian   { background: rgba(241,196,15,0.15);  color: #d4a017; }
        .cuisine-western { background: rgba(52,152,219,0.12);  color: #2980b9; }

        .visibility-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
        .visibility-public  { background: rgba(46,204,113,0.12); color: #27ae60; }
        .visibility-private { background: rgba(225,112,85,0.12); color: #e15555; }

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
        <li><a href="manage_items.php"><i class="bi bi-bag-heart-fill"></i> Manage Items</a></li>
        <li><a href="manage_orders.php"><i class="bi bi-receipt"></i> Manage Orders</a></li>
        <li><a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a></li>
        <li><a href="manage_recipes.php" class="active"><i class="bi bi-book-fill"></i> Manage Recipes</a></li>
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

<!-- Recipe Modal -->
<div class="modal fade" id="recipeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h3 class="fw-bold" id="recipeModalTitle">Add New Recipe</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="recipeForm" action="manage_recipes.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_recipe" value="1">
                    <input type="hidden" name="recipe_id" id="recipeId" value="0">
                    <input type="hidden" name="existing_image" id="existingImage" value="">
                    <input type="hidden" name="existing_video" id="existingVideo" value="">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Recipe Title</label>
                            <input type="text" name="title" id="recipeTitle" class="form-control" placeholder="Give your recipe a name" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Cuisine</label>
                            <select name="cuisine" id="recipeCuisine" class="form-select">
                                <option value="Melayu">Melayu</option>
                                <option value="Asian">Asian</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="recipeDesc" class="form-control" rows="2" placeholder="Briefly describe your dish"></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Keep it short and appetizing</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Meal Type</label>
                            <select name="meal_type" id="recipeMealType" class="form-select">
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
                            <input type="number" name="cooking_time" id="recipeCookingTime" class="form-control" placeholder="Total cooking time" required>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Enter total time in minutes</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Image</label>
                            <input type="file" name="recipe_image" id="recipeImage" class="form-control" accept="image/*" onchange="previewRecipeImg(this)">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Leave empty to keep current</span>
                        </div>
                        <div id="imgPreviewWrap" style="display:none;" class="col-12">
                            <img id="imgPreview" src="" style="width:100%;height:160px;object-fit:cover;border-radius:16px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ingredients</label>
                            <textarea name="ingredients" id="recipeIngredients" class="form-control" rows="5" placeholder="List your ingredients here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each ingredient on a new line</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Instructions</label>
                            <textarea name="instructions" id="recipeInstructions" class="form-control" rows="5" placeholder="Write your cooking steps here..." required></textarea>
                            <span class="form-hint"><i class="bi bi-info-circle"></i> Write each step on a new line</span>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Video (optional)</label>
                            <input type="file" name="recipe_video" id="recipeVideo" class="form-control" accept="video/*">
                            <span class="form-hint"><i class="bi bi-info-circle"></i> MP4 and MOV recommended</span>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <label class="form-label fw-bold mb-0">Make Public</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_public" id="recipeIsPublic" style="width:40px;height:22px;cursor:pointer;">
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-5">
                        <button type="submit" id="savRecipeBtn" class="btn-create px-5">Save Recipe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Manage Recipes</h1>
            <p><?= $count ?> recipe<?= $count !== 1 ? 's' : '' ?> found</p>
        </div>
        <button class="btn-add" onclick="openRecipeModal()">
            <i class="bi bi-plus-lg"></i> Add New Recipe
        </button>
    </div>

    <?php $status = $_GET['status'] ?? ''; ?>
    <?php if ($status === 'created'): ?>
    <div class="alert-toast success"><i class="bi bi-check-circle-fill me-2"></i> Recipe created successfully!</div>
    <?php elseif ($status === 'updated'): ?>
    <div class="alert-toast success"><i class="bi bi-check-circle-fill me-2"></i> Recipe updated successfully!</div>
    <?php elseif ($status === 'deleted'): ?>
    <div class="alert-toast danger"><i class="bi bi-trash3-fill me-2"></i> Recipe deleted successfully!</div>
    <?php endif; ?>

    <div class="filter-bar">
        <form method="GET" id="filterForm" style="display:contents;">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search recipe title..." value="<?= htmlspecialchars($search) ?>" id="searchInput">
            </div>
            <select name="cuisine" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all" <?= $cuisine === 'all' ? 'selected' : '' ?>>All Cuisines</option>
                <?php foreach ($cuisines as $c): ?>
                <option value="<?= htmlspecialchars($c['cuisine']) ?>" <?= $cuisine === $c['cuisine'] ? 'selected' : '' ?>><?= htmlspecialchars($c['cuisine']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="visibility" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"     <?= $visibility === 'all'     ? 'selected' : '' ?>>All</option>
                <option value="public"  <?= $visibility === 'public'  ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Private</option>
            </select>
        </form>
        <span class="count-badge"><?= $count ?> results</span>
    </div>

    <div class="table-card">
        <div class="recipe-row header">
            <div></div><div>Recipe</div><div>Cuisine</div><div>Visibility</div><div>Date</div><div>Actions</div>
        </div>
        <?php if (empty($recipes)): ?>
        <div class="empty-state"><i class="bi bi-book-x"></i><p>No recipes found.</p></div>
        <?php else: ?>
        <?php foreach ($recipes as $recipe):
            $imgSrc = getImageSrc($recipe['image'] ?? '', '../../assets/images/recipes/');
            $cuisineClass = match($recipe['cuisine']) {
                'Melayu'  => 'cuisine-melayu',
                'Asian'   => 'cuisine-asian',
                'Western' => 'cuisine-western',
                default   => ''
            };
        ?>
        <div class="recipe-row" id="row-<?= $recipe['recipe_id'] ?>">
            <div class="recipe-img">
                <?php if ($imgSrc): ?><img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
                <?php else: ?><i class="bi bi-image"></i><?php endif; ?>
            </div>
            <div><div class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></div></div>
            <div><span class="cuisine-tag <?= $cuisineClass ?>"><?= htmlspecialchars($recipe['cuisine']) ?></span></div>
            <div>
                <span class="visibility-badge <?= $recipe['is_public'] ? 'visibility-public' : 'visibility-private' ?>">
                    <i class="bi <?= $recipe['is_public'] ? 'bi-globe' : 'bi-lock-fill' ?>" style="font-size:0.6rem;"></i>
                    <?= $recipe['is_public'] ? 'Public' : 'Private' ?>
                </span>
            </div>
            <div style="font-size:0.78rem;color:#bdc3c7;"><?= date('d M Y', strtotime($recipe['created_at'])) ?></div>
            <div class="action-btns">
                <button class="btn-edit" onclick="openRecipeModal(<?= htmlspecialchars(json_encode($recipe)) ?>)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn-delete" onclick="confirmDelete(<?= $recipe['recipe_id'] ?>, '<?= addslashes(htmlspecialchars($recipe['title'])) ?>')">
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
        <h4 class="fw-bold mb-2">Delete Recipe?</h4>
        <p class="text-muted mb-4" id="delModalText">This action cannot be undone.</p>
        <button class="btn-del-confirm" onclick="doDelete()"><i class="bi bi-trash3 me-2"></i>Yes, Delete</button>
        <button class="btn-del-cancel" onclick="closeDeleteModal()">Cancel</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let deleteId = null;
function confirmDelete(id, title) {
    deleteId = id;
    document.getElementById('delModalText').textContent = `"${title}" will be permanently deleted.`;
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
    fd.append('recipe_id', deleteId);
    const res  = await fetch('manage_recipes.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        closeDeleteModal();
        window.location.href = 'manage_recipes.php?status=deleted';
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
function openRecipeModal(recipe = null) {
    document.getElementById('recipeForm').reset();
    document.getElementById('imgPreviewWrap').style.display = 'none';
    document.getElementById('recipeIsPublic').checked = true;
    if (recipe) {
        document.getElementById('recipeModalTitle').textContent = 'Update Your Recipe';
        document.getElementById('savRecipeBtn').textContent     = 'Save Changes';
        document.getElementById('recipeId').value               = recipe.recipe_id;
        document.getElementById('existingImage').value          = recipe.image ?? '';
        document.getElementById('existingVideo').value          = recipe.video_url ?? '';
        document.getElementById('recipeTitle').value            = recipe.title ?? '';
        document.getElementById('recipeDesc').value             = recipe.description ?? '';
        document.getElementById('recipeCuisine').value          = recipe.cuisine ?? 'Melayu';
        document.getElementById('recipeMealType').value         = recipe.meal_type ?? 'Breakfast';
        document.getElementById('recipeCookingTime').value      = recipe.cooking_time ?? '';
        document.getElementById('recipeIngredients').value      = recipe.ingredients ?? '';
        document.getElementById('recipeInstructions').value     = recipe.instructions ?? '';
        document.getElementById('recipeIsPublic').checked       = recipe.is_public == 1;
        if (recipe.image) {
            const src = recipe.image.startsWith('http') ? recipe.image : '../../assets/images/recipes/' + recipe.image;
            document.getElementById('imgPreview').src = src;
            document.getElementById('imgPreviewWrap').style.display = 'block';
        }
    } else {
        document.getElementById('recipeModalTitle').textContent = 'Create your recipe';
        document.getElementById('savRecipeBtn').textContent     = 'Create';
        document.getElementById('recipeId').value               = '0';
        document.getElementById('existingImage').value          = '';
        document.getElementById('existingVideo').value          = '';
    }
    new bootstrap.Modal(document.getElementById('recipeModal')).show();
}
function previewRecipeImg(input) {
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