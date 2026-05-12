<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';
$nav_profile_img = '';
$nav_role = 'Customer';

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

// Ambil ID resipi dari URL
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? '';

if ($type === 'user') {
    // Query dari created_recipes
    $sql = "SELECT *, 0 as is_saved FROM created_recipes WHERE cr_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recipe_id, $userId);
    $stmt->execute();
    $recipe = $stmt->get_result()->fetch_assoc();
} else {
    // Query dari recipes (asal)
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM saved_recipes s WHERE s.recipe_id = r.recipe_id AND s.user_id = ?) as is_saved 
            FROM recipes r WHERE r.recipe_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $recipe_id);
    $stmt->execute();
    $recipe = $stmt->get_result()->fetch_assoc();
}

if (!$recipe) {
    die("No recipe found.");
}

$ingredients = explode("\n", $recipe['ingredients']);
$steps = explode("\n", $recipe['instructions']);

$gradients = [
    'Melayu'  => 'linear-gradient(135deg, #2E7D32, #9FA825)',
    'Western' => 'linear-gradient(135deg, #c0392b, #e74c3c)',
    'Asian'   => 'linear-gradient(135deg, #e67e22, #f39c12)',
];
$grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2E7D32, #9FA825)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe['title']) ?> – Foodify</title>
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

        /* ── SIDEBAR (KEKAL ASAL) ── */
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
        .user-avatar-img { 
            width: 35px; 
            height: 35px; 
            border-radius: 50%; 
            object-fit: cover; 
        }
        
        .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; transition: 0.2s; }
        .btn-side-login { background: var(--orange); color: white; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 2rem; min-height: 100vh; }
        
        /* Back Button Style */
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px; color: var(--dark);
            text-decoration: none; font-weight: 700; margin-bottom: 1.5rem;
            transition: 0.2s; padding: 8px 16px; background: white; border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .btn-back:hover { color: var(--orange); transform: translateX(-5px); }

        .recipe-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        
        .recipe-card-main { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; }
        
        /* Save Button */
        .btn-save-recipe {
            position: absolute; top: 15px; right: 15px; z-index: 10;
            background: white; border: none; width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.12);
            color: #ccc; cursor: pointer; transition: 0.2s;
        }
        .btn-save-recipe.active { color: #ff4757; }
        .btn-save-recipe.active i::before { content: "\f415"; }

        .recipe-img-box { height: 280px; display: flex; align-items: center; justify-content: center; }
        .recipe-img-box img { width: 100%; height: 100%; object-fit: cover; }
        
        .video-container { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .video-player { background: #1a1a1a; aspect-ratio: 16 / 9; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .video-player i { font-size: 4rem; color: white; opacity: 0.8; }

        .info-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .section-title { font-weight: 800; border-bottom: 3px solid var(--orange); display: inline-block; margin-bottom: 1.2rem; padding-bottom: 4px; }
        
        .step-num { width: 28px; height: 28px; background: var(--orange); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; margin-right: 10px; flex-shrink: 0; }
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
            <li><a href="cookbook.php"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
            <li><a href="../order/index.php"><i class="bi bi-truck"></i> My Orders</a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-bottom">
        <?php if ($isLoggedIn): ?>
            <a href="../profile/profile.php" class="text-decoration-none" style="color: inherit;">
               <div class="user-row">
                    <?php 
                    // Kita kena undur 2 folder ke belakang untuk jumpa folder assets
                    $imgPath = "../../assets/images/profiles/" . $nav_profile_img;
                    
                    if (!empty($nav_profile_img) && file_exists($imgPath)): ?>
                        <img src="<?= $imgPath ?>" class="user-avatar-img">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                    
                    <div>
                        <div class="user-name"><?= htmlspecialchars($username) ?></div>
                        <div class="user-role"><?= htmlspecialchars($nav_role) ?></div>
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
    <!-- BACK BUTTON TU KAT SINI -->
    <a href="javascript:history.back()" class="btn-back">
        <i class="bi bi-arrow-left"></i> Go Back
    </a>

    <div class="recipe-layout">
        <div class="recipe-card-main">
            <?php if ($type !== 'user'): ?>
        <button class="btn-save-recipe <?= ($recipe['is_saved'] > 0) ? 'active' : '' ?>" 
                onclick="toggleSave(event, <?= $recipe['recipe_id'] ?>)">
            <i class="bi bi-heart"></i>
        </button>
<?php endif; ?>
            <div class="recipe-img-box" style="background: <?= $grad ?>;">
                <?php 
            $basePath = ($type === 'user') ? '../../assets/images/recipes/' : '../../assets/images/recipes/';
            $imgSrc = getImageSrc($recipe['image'], $basePath); 
            ?>
            <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>">
            <?php else: ?>
                <i class="bi bi-egg-fried" style="font-size: 5rem; color: white;"></i>
            <?php endif; ?>
            </div>
            <div class="p-4">
                <h1 class="fw-bold"><?= htmlspecialchars($recipe['title']) ?></h1>
                <p class="text-muted"><?= htmlspecialchars($recipe['description']) ?></p>
                <div>
                    <span class="badge bg-success rounded-pill"><?= htmlspecialchars($recipe['cuisine']) ?></span>
                    <span class="ms-2 text-muted small"><i class="bi bi-clock"></i> <?= htmlspecialchars($recipe['cooking_time'] ?? '45m') ?></span>
                </div>
            </div>
        </div>

        <?php if ($type === 'user'): ?>
        <div class="video-container">
            <div class="video-player" style="background: linear-gradient(135deg, #2E7D32, #9FA825);">
                <div class="text-center text-white p-4">
                    <i class="bi bi-person-video3" style="font-size: 4rem; opacity: 0.8;"></i>
                    <h5 class="fw-bold mt-3">Your Recipe</h5>
                    <p class="small opacity-75 mb-0">This is your personal creation</p>
                </div>
            </div>
            <div class="p-3 text-center">
                <h4 class="fw-bold">My Creation</h4>
                <p class="text-muted small mb-0">Created by you on <?= date('d M Y', strtotime($recipe['created_at'])) ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="video-container">
            <div class="video-player" onclick="alert('Video coming soon!')">
                <i class="bi bi-play-circle-fill"></i>
            </div>
            <div class="p-3 text-center">
                <h4 class="fw-bold">Watch Tutorial</h4>
                <p class="text-muted small mb-0">Follow the step-by-step video guide</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="info-card">
                <h4 class="section-title">Ingredients</h4>
                <ul class="list-unstyled">
                    <?php foreach ($ingredients as $ing): if(!trim($ing)) continue; ?>
                        <li class="py-2 border-bottom"><i class="bi bi-check-circle text-success me-2"></i> <?= htmlspecialchars($ing) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-7">
            <div class="info-card">
                <h4 class="section-title">Preparation</h4>
                <ul class="list-unstyled">
                    <?php $i=1; foreach ($steps as $step): if(!trim($step)) continue; ?>
                        <li class="py-3 d-flex align-items-start border-bottom">
                            <span class="step-num"><?= $i++ ?></span>
                            <span><?= htmlspecialchars($step) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    async function toggleSave(event, recipeId) {
        event.preventDefault(); 
        if (!isLoggedIn) {
            alert("Sila log masuk untuk menyimpan resipi.");
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
</script>
</body>
</html>