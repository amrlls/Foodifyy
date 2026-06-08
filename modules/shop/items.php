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
        $username        = $user_nav['username'];
        $nav_profile_img = $user_nav['profile_image'];
        $nav_role        = $user_nav['role'];
    }
}

$cartCount = 0;
if ($isLoggedIn) {
    $stmt_cart = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt_cart->bind_param("i", $userId);
    $stmt_cart->execute();
    $cart_row  = $stmt_cart->get_result()->fetch_assoc();
    $cartCount = $cart_row['total'] ?? 0;
}

$category = $_GET['category'] ?? 'all';
$search   = trim($_GET['search'] ?? '');

$where  = ["stock > 0"];
$params = [];
$types  = '';

if ($category !== 'all') {
    $where[]  = "category = ?";
    $params[] = $category;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "name LIKE ?";
    $params[] = "%$search%";
    $types   .= 's';
}

$whereSQL = implode(' AND ', $where);
$sql      = "SELECT * FROM items WHERE $whereSQL ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$items  = $result->fetch_all(MYSQLI_ASSOC);
$count  = count($items);

$catColors = [
    'Vegetables' => ['grad' => 'linear-gradient(135deg,#1e5128,#4e944f)', 'icon' => 'bi-flower1'],
    'Fruits'     => ['grad' => 'linear-gradient(135deg,#c72b2b,#ff6b6b)',  'icon' => 'bi-apple'],
    'Meat'       => ['grad' => 'linear-gradient(135deg,#7b2d00,#d45700)',  'icon' => 'bi-egg'],
    'Seafood'    => ['grad' => 'linear-gradient(135deg,#0a3d62,#1e90ff)',  'icon' => 'bi-water'],
    'Dairy'      => ['grad' => 'linear-gradient(135deg,#4a4a8a,#a29bfe)',  'icon' => 'bi-cup-straw'],
    'Grains'     => ['grad' => 'linear-gradient(135deg,#7d6608,#f9ca24)',  'icon' => 'bi-grid'],
    'Spices'     => ['grad' => 'linear-gradient(135deg,#6d1a36,#e84393)',  'icon' => 'bi-lightning'],
    'Beverages'  => ['grad' => 'linear-gradient(135deg,#1a4a6d,#00b894)',  'icon' => 'bi-cup'],
];

$cat_result = $conn->query("SELECT DISTINCT category FROM items WHERE stock > 0 ORDER BY category");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items – Foodify</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--soft-bg); color: #1A1C1E; overflow-x: hidden; }

        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow-y: auto; transition: transform 0.3s ease;
        }
        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif; font-weight: 900; letter-spacing: -1px;
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
        .cart-badge {
            background: var(--primary-grad); color: white; font-size: 0.65rem; font-weight: 800;
            width: 20px; height: 20px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; margin-left: auto;
        }

        .main-content { margin-left: var(--sidebar-w); padding: 0; min-height: 100vh; background: white; }
        .header-section { padding: 3rem 4rem 1.5rem 4rem; background: #fff; }
        .top-bar-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.0rem; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-weight: 900; color: #1A1C1E; line-height: 1; margin: 0; }
        .top-bar p { color: #7f8c8d; font-size: 1.1rem; margin-top: 0.8rem; }

        .search-container { position: relative; width: 350px; }
        .search-container input {
            width: 100%; padding: 14px 20px 14px 50px;
            border-radius: 100px; border: 1px solid #f0f0f0;
            background: #f8f9fa; font-weight: 500; transition: 0.3s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .search-container input:focus { background: white; border-color: var(--accent); box-shadow: 0 10px 25px rgba(255,142,83,0.1); outline: none; }
        .search-container i { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #95a5a6; font-size: 1.2rem; }

        .filters-wrapper { padding: 0 4rem 2.5rem 4rem; border-bottom: 1px solid #f5f5f5; }
        .filter-label { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #bdc3c7; margin-bottom: 12px; letter-spacing: 1.5px; display: block; }
        .filter-pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-pill {
            background: transparent; border: 1px solid #eee; padding: 8px 22px; border-radius: 100px;
            font-size: 0.85rem; font-weight: 600; text-decoration: none; color: #444; transition: 0.3s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .filter-pill:hover { border-color: var(--accent); color: var(--accent); background: #fffaf9; }
        .filter-pill.active { background: var(--primary-grad); border-color: transparent; color: white; box-shadow: 0 8px 20px rgba(255,107,107,0.2); }

        .content-body { padding: 2rem 4rem; background: #fdfdfd; }
        .results-info { font-weight: 700; color: #bdc3c7; margin-bottom: 2rem; display: block; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.8rem; }
        .product-card {
            background: white; border-radius: 24px; overflow: hidden; border: 1px solid #f0f0f0;
            box-shadow: var(--card-shadow); transition: 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex; flex-direction: column;
        }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 24px 48px rgba(0,0,0,0.1); }
        .product-img-box { height: 160px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .product-img-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.6s ease; }
        .product-card:hover .product-img-box img { transform: scale(1.08); }
        .product-img-box i { font-size: 3rem; color: white; opacity: 0.85; }
        .cat-badge {
            position: absolute; bottom: 12px; right: 12px;
            background: rgba(255,255,255,0.2); backdrop-filter: blur(8px);
            color: white; border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
            padding: 5px 12px; border-radius: 100px; letter-spacing: 0.5px;
        }
        .product-body { padding: 1rem 1.1rem 1.1rem; display: flex; flex-direction: column; flex-grow: 1; }
        .product-name { font-weight: 800; font-size: 1rem; color: #1A1C1E; margin-bottom: 0.35rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35; }
        .product-price { font-size: 1.2rem; font-weight: 800; background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.75rem; }
        .btn-cart {
            margin-top: auto; width: 100%; padding: 11px; background: var(--primary-grad);
            border: none; border-radius: 14px; color: white; font-weight: 700; font-size: 0.85rem;
            font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-cart:hover { opacity: 0.88; box-shadow: 0 10px 24px rgba(255,107,107,0.3); transform: translateY(-1px); }
        .btn-cart:active { transform: scale(0.97); }
        .btn-cart.adding { background: linear-gradient(135deg, #00b894, #00cec9); pointer-events: none; }

        .floating-cart {
            background: var(--sidebar-dark); padding: 1rem 1.8rem; border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); z-index: 997; bottom: 30px; right: 30px; transition: 0.3s;
        }
        .floating-cart:hover { background: #2d2f31; transform: translateY(-3px); box-shadow: 0 16px 40px rgba(0,0,0,0.25); }
        .floating-cart .cart-count { background: var(--primary-grad); color: white; font-size: 0.65rem; font-weight: 800; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-left: 4px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 992px) {
            .header-section, .filters-wrapper, .content-body { padding: 2rem; }
            .top-bar-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .search-container { width: 100%; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; padding-top: 5rem; }
            .header-section, .filters-wrapper, .content-body { padding: 1.2rem; }
            .top-bar h1 { font-size: 2rem; }
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .search-container { width: 100%; }
            .floating-cart { bottom: 20px; right: 16px; padding: 0.8rem 1.2rem; }
        }

        @media (max-width: 400px) {
            .product-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR (mobile) ── -->
<div id="topbar" style="display:none;position:fixed;top:0;left:0;right:0;z-index:999;background:#1A1C1E;padding:1rem 1.5rem;align-items:center;justify-content:space-between;">
    <span style="font-family:'Playfair Display',serif;font-weight:900;font-size:1.5rem;background:linear-gradient(135deg,#FF6B6B,#FF8E53);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">foodify.</span>
    <button onclick="toggleSidebar()" style="background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;"><i class="bi bi-list" id="hamburgerIcon"></i></button>
</div>

<!-- ── OVERLAY ── -->
<div id="sidebarOverlay" onclick="toggleSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:998;"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Fresh picks, just for you.</p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="items.php" class="active"><i class="bi bi-bag-heart"></i> Market</a></li>
        <?php if ($isLoggedIn): ?>
            <li><a href="../recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
            <li><a href="../order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
        <?php if ($isLoggedIn): ?>
            <a href="../profile/profile.php" class="text-decoration-none d-block">
                <div class="user-card d-flex align-items-center gap-3 mb-3">
                    <?php $navProfileSrc = getImageSrc($nav_profile_img, '../../assets/images/profiles/'); ?>
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
        <div class="top-bar-flex">
            <div class="top-bar">
                <h1>Fresh Market</h1>
                <p>Quality groceries, delivered to your door.</p>
            </div>
        </div>
        <form method="GET" action="" id="filterForm">
            <div class="search-container">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" name="search" placeholder="Search for groceries..." value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            </div>
        </form>
    </div>

    <div class="filters-wrapper">
        <span class="filter-label">Category</span>
        <div class="filter-pills">
            <?php
            $allActive = ($category === 'all') ? 'active' : '';
            echo "<a href='?category=all&search=" . urlencode($search) . "' class='filter-pill $allActive'>All</a>";
            foreach ($categories as $cat):
                $activeClass = ($category === $cat['category']) ? 'active' : '';
                $url = "?category=" . urlencode($cat['category']) . "&search=" . urlencode($search);
            ?>
                <a href="<?= $url ?>" class="filter-pill <?= $activeClass ?>"><?= htmlspecialchars($cat['category']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="content-body">
        <span class="results-info"><?= $count ?> item<?= $count !== 1 ? 's' : '' ?> available</span>
        <div class="product-grid">
            <?php if ($count > 0): ?>
                <?php foreach ($items as $item):
                    $catStyle = $catColors[$item['category']] ?? ['grad' => 'linear-gradient(135deg,#2D3436,#000)', 'icon' => 'bi-bag'];
                    $imgSrc   = getImageSrc($item['image'] ?? '', '../../assets/images/items/');
                ?>
                    <div class="product-card" onclick="openModal(<?= $item['item_id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>', '<?= addslashes(htmlspecialchars($item['category'])) ?>', <?= $item['price'] ?>, '<?= addslashes($imgSrc ?: '') ?>', '<?= addslashes($catStyle['grad']) ?>', '<?= addslashes($catStyle['icon']) ?>', <?= $item['stock'] ?>, '<?= addslashes(htmlspecialchars($item['unit'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($item['description'] ?? '')) ?>')" style="cursor:pointer;">
                        <div class="product-img-box" style="background:<?= $catStyle['grad'] ?>;">
                            <?php if ($imgSrc): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <i class="bi <?= $catStyle['icon'] ?>"></i>
                            <?php endif; ?>
                            <span class="cat-badge"><?= htmlspecialchars($item['category']) ?></span>
                        </div>
                        <div class="product-body">
                            <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="product-price">RM <?= number_format($item['price'], 2) ?></div>
                            <button class="btn-cart" onclick="event.stopPropagation(); addToCart(event, <?= $item['item_id'] ?>, this)" <?= !$isLoggedIn ? "data-guest='1'" : '' ?>>
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 w-100" style="grid-column:1/-1;">
                    <i class="bi bi-bag-x" style="font-size:3rem;color:#eee;"></i>
                    <p class="mt-3 text-muted">No items found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'item_modal.php'; ?>

<a href="cart.php" class="floating-cart position-fixed text-white text-decoration-none d-flex align-items-center gap-3">
    <i class="bi bi-bag-fill fs-5"></i>
    <span class="fw-bold small">Bag
        <?php if ($cartCount > 0): ?>
            <span class="cart-count ms-1"><?= $cartCount ?></span>
        <?php endif; ?>
    </span>
</a>

<script>
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

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

async function addToCart(e, itemId, btn) {
    e.preventDefault();
    if (!isLoggedIn) { window.location.href = '../auth/login.php'; return; }

    btn.classList.add('adding');
    btn.innerHTML = '<i class="bi bi-check-lg"></i> Added!';

    try {
        const fd = new FormData();
        fd.append('item_id', itemId);
        fd.append('quantity', 1);

        const res  = await fetch('addtocart.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            const countEl = document.querySelector('.floating-cart .cart-count');
            if (countEl) {
                countEl.textContent = parseInt(countEl.textContent || 0) + 1;
            } else {
                const span = document.querySelector('.floating-cart span.fw-bold');
                if (span) {
                    const b = document.createElement('span');
                    b.className = 'cart-count ms-1'; b.textContent = '1';
                    span.appendChild(b);
                }
            }
        } else {
            btn.style.background = 'linear-gradient(135deg,#e17055,#d63031)';
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Failed';
        }
    } catch (err) {
        btn.style.background = 'linear-gradient(135deg,#e17055,#d63031)';
        btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error';
    }

    setTimeout(() => {
        btn.classList.remove('adding');
        btn.style.background = '';
        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
    }, 1800);
}

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(window._st);
        window._st = setTimeout(() => document.getElementById('filterForm').submit(), 700);
    });
}
</script>
</body>
</html>