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

$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? '';

if ($type === 'user') {
    $sql = "SELECT *, 0 as is_saved FROM created_recipes WHERE cr_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recipe_id, $userId);
    $stmt->execute();
    $recipe = $stmt->get_result()->fetch_assoc();
} else {
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
    'Melayu'  => 'linear-gradient(135deg, #1e5128, #4e944f)',
    'Western' => 'linear-gradient(135deg, #800000, #d90429)',
    'Asian'   => 'linear-gradient(135deg, #ff4d00, #ffb703)',
];
$grad = $gradients[$recipe['cuisine']] ?? 'linear-gradient(135deg, #2D3436, #000000)';

$videoUrl = $recipe['video_url'] ?? '';
$embedUrl = (!empty($videoUrl) && str_contains($videoUrl, 'cloudinary.com')) ? $videoUrl : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe['title']) ?> – Foodify</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--soft-bg); color: #1A1C1E; }

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
        .main-content { margin-left: var(--sidebar-w); padding: 2rem; min-height: 100vh; }

        .btn-back {
            display: inline-flex; align-items: center; gap: 8px; color: #1A1C1E;
            text-decoration: none; font-weight: 700; margin-bottom: 1.5rem;
            transition: 0.2s; padding: 8px 16px; background: white; border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .btn-back:hover { color: var(--accent); transform: translateX(-5px); }

        .recipe-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .recipe-card-main { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; }

        .btn-save-recipe {
            position: absolute; top: 15px; right: 15px; z-index: 10;
            background: white; border: none; width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.12);
            color: #ccc; cursor: pointer; transition: 0.2s;
        }
        .btn-save-recipe.active { color: #ff4757; }

        .recipe-img-box { height: 280px; display: flex; align-items: center; justify-content: center; }
        .recipe-img-box img { width: 100%; height: 100%; object-fit: cover; }

        /* ── VIDEO SECTION (SIZE MAINTAINED) ── */
        .video-container { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .video-player { background: #1a1a1a; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; }
        .video-player i { font-size: 4rem; color: white; opacity: 0.8; }
        .video-no-content {
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            aspect-ratio: 16/9; display: flex; flex-direction: column;
            align-items: center; justify-content: center; color: #aaa;
        }

        .info-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .section-title { font-weight: 800; border-bottom: 3px solid var(--accent); display: inline-block; margin-bottom: 1.2rem; padding-bottom: 4px; }
        .step-num { width: 28px; height: 28px; background: var(--primary-grad); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; margin-right: 10px; flex-shrink: 0; }
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
            <li><a href="../order/index.php"><i class="bi bi-receipt"></i> Orders</a></li>
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
    <a href="javascript:history.back()" class="btn-back"><i class="bi bi-arrow-left"></i> Go Back</a>

    <div class="recipe-layout">
        <div class="recipe-card-main">
            <?php if ($type !== 'user'): ?>
                <button class="btn-save-recipe <?= ($recipe['is_saved'] > 0) ? 'active' : '' ?>" onclick="toggleSave(event, <?= $recipe['recipe_id'] ?>)">
                    <i class="bi bi-heart-fill"></i>
                </button>
            <?php endif; ?>
            <div class="recipe-img-box" style="background: <?= $grad ?>;">
                <?php $imgSrc = getImageSrc($recipe['image'], '../../assets/images/recipes/'); ?>
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
                    <span class="badge bg-dark rounded-pill"><?= htmlspecialchars($recipe['cuisine']) ?></span>
                    <span class="ms-2 text-muted small">
                        <i class="bi bi-clock"></i>
                        <?= htmlspecialchars($recipe['cooking_time']) ?> mins
                    </span>
                </div>
            </div>
        </div>

        <!-- VIDEO SECTION (MAINTAINED) -->
        <div class="video-container">
            <?php if ($type === 'user'): ?>
                <div class="video-player" style="background: var(--primary-grad);">
                    <div class="text-center text-white p-4">
                        <i class="bi bi-person-video3" style="font-size: 4rem; opacity: 0.8;"></i>
                        <h5 class="fw-bold mt-3">Your Recipe</h5>
                        <p class="small opacity-75 mb-0">This is your personal creation</p>
                    </div>
                </div>
            <?php elseif ($embedUrl): ?>
                <video controls style="width:100%; aspect-ratio:16/9; background:#000; display:block;">
                    <source src="<?= htmlspecialchars($embedUrl) ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <div class="video-no-content">
                    <i class="bi bi-camera-video-off" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p class="fw-bold mb-0">No video available</p>
                    <p class="small">Video tutorial coming soon</p>
                </div>
            <?php endif; ?>
            <div class="p-3 text-center">
                <h4 class="fw-bold"><?= ($type === 'user') ? 'My Creation' : 'Watch Tutorial' ?></h4>
                <p class="text-muted small mb-0"><?= ($type === 'user') ? 'Created by you' : 'Follow the step-by-step guide' ?></p>
            </div>
        </div>
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
        if (!isLoggedIn) { window.location.href = '../auth/login.php'; return; }
        const btn = event.currentTarget;
        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);
            const response = await fetch('toggle_save.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'saved') btn.classList.add('active');
            else if (data.status === 'removed') btn.classList.remove('active');
        } catch (error) { console.error('Error:', error); }
    }
</script>
</body>
</html>