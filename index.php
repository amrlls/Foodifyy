<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username   = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodify – Recipes & Groceries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green:         #2E7D32;
            --green-mid:    #3E9B4E;
            --green-light:  #E8F5E9;
            --orange:       #FF8F00;
            --orange-light: #FFF8E1;
            --dark:         #1A1A1A;
            --muted:        #777;
            --border:       #EEEEEE;
            --sidebar-w:    270px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Nunito', sans-serif;
            background: #F7F9F7;
            color: var(--dark);
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: white;
            box-shadow: 2px 0 16px rgba(0,0,0,0.06);
            padding: 1.8rem 1rem;
            overflow-y: auto;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar-logo {
            display: flex;
            flex-direction: column; 
            align-items: center;    
            text-align: center;     
            gap: 12px;
            margin-bottom: 2rem;
            padding: 0 8px;
        }
        .sidebar-logo img {
            width: 75px;
            height: 75px;
            object-fit: contain;
            border-radius: 14px;
        }
        .sidebar-logo .logo-text h2 {
            font-weight: 900;
            font-size: 1.6rem;
            color: var(--green);
            line-height: 1;
            margin-bottom: 4px;
        }
        .sidebar-logo .logo-text span {
            font-size: 0.7rem;
            color: var(--muted);
            font-weight: 600;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
            flex: 1;
        }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.18s;
        }
        .sidebar-nav a i { font-size: 1.15rem; width: 22px; }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--orange);
            color: white;
        }
        .sidebar-section-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #bbb;
            padding: 16px 14px 6px;
            text-transform: uppercase;
        }
        .sidebar-bottom {
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .user-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--green-light);
            border-radius: 12px;
            margin-bottom: 10px;
        }
        .user-row i { font-size: 1.6rem; color: var(--green); }
        .user-row .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-row .user-role { font-size: 0.7rem; color: var(--muted); }
        .btn-side-login {
            display: block;
            background: var(--orange);
            color: white;
            border: none;
            padding: 9px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-align: center;
            text-decoration: none;
            margin-bottom: 6px;
            transition: all 0.2s;
        }
        .btn-side-login:hover { background: #e07f00; color: white; }
        .btn-side-register {
            display: block;
            background: transparent;
            border: 1.5px solid var(--green);
            color: var(--green);
            padding: 9px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-side-register:hover { background: var(--green); color: white; }
        .btn-side-logout {
            display: block;
            background: #FEE2E2;
            color: #DC2626;
            border: none;
            padding: 9px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN ── */
        .main-content {
            margin-left: var(--sidebar-w);
            padding: 2rem 2rem 4rem;
            min-height: 100vh;
        }

        /* ── BANNER ── */
        .hero-banner {
            background: linear-gradient(135deg, var(--green) 0%, #3E9B4E 55%, #9FA825 100%);
            border-radius: 24px;
            padding: 2.5rem 2.5rem;
            color: white;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -50px; right: 120px;
            width: 150px; height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .hero-banner .tagline {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        .hero-banner h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 0.8rem;
        }
        .hero-banner p {
            font-size: 0.92rem;
            opacity: 0.85;
            max-width: 420px;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: var(--green);
            font-weight: 800;
            font-size: 0.88rem;
            padding: 11px 22px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
            z-index: 1;
        }
        .hero-cta:hover {
            background: var(--orange);
            color: white;
            transform: translateY(-2px);
        }
        .hero-emoji {
            position: absolute;
            right: 2.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 6rem;
            opacity: 0.15;
            z-index: 0;
        }

        /* ── SECTION HEADERS ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
        }
        .section-header h2 {
            font-size: 1.3rem;
            font-weight: 800;
        }
        .section-header a {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--orange);
            text-decoration: none;
        }
        .section-header a:hover { text-decoration: underline; }

        /* ── RECIPE CARDS ── */
        .recipe-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.22s;
            cursor: pointer;
            display: flex;
            border: 1.5px solid transparent;
        }
        .recipe-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--orange);
        }
        .recipe-img {
            width: 110px;
            min-height: 110px;
            background: linear-gradient(135deg, var(--green), #9FA825);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .recipe-img i { font-size: 2.8rem; color: white; }
        .recipe-info {
            padding: 1rem 1.1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .recipe-title {
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            color: var(--dark);
        }
        .recipe-desc {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .recipe-tag {
            display: inline-block;
            background: var(--green-light);
            color: var(--green);
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            margin-top: 0.5rem;
        }

        /* ── GROCERY CARDS (SQUARE VERSION) ── */
        .groceries-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s;
            border: 1.5px solid transparent;
            height: 100%;
        }
        .groceries-card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            border-color: var(--orange);
            transform: translateY(-3px);
        }
        .groceries-icon {
            width: 60px; height: 60px;
            background: var(--green-light);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .groceries-icon i { font-size: 1.8rem; color: var(--green); }
        .groceries-name { font-weight: 800; font-size: 0.88rem; margin-bottom: 4px; min-height: 2.5em; display: flex; align-items: center; }
        .groceries-price { font-size: 0.95rem; color: var(--orange); font-weight: 800; margin-bottom: 12px; }
        
        .add-btn {
            background: var(--green);
            border: none;
            border-radius: 50px;
            padding: 8px 0;
            width: 100%;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .add-btn:hover { background: var(--orange); }

        /* ── LOGIN MODAL ── */
        .login-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .login-overlay.show { display: flex; }
        .login-modal {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            animation: popIn 0.3s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.85); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .login-modal .modal-icon {
            width: 70px; height: 70px;
            background: var(--green-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .login-modal .modal-icon i { font-size: 2rem; color: var(--green); }
        .login-modal h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .login-modal p {
            color: var(--muted);
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .modal-btn-login {
            display: block;
            background: var(--orange);
            color: white;
            padding: 12px;
            border-radius: 50px;
            font-weight: 800;
            text-decoration: none;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .modal-btn-login:hover { background: #e07f00; color: white; transform: translateY(-1px); }
        .modal-btn-register {
            display: block;
            border: 1.5px solid var(--green);
            color: var(--green);
            padding: 12px;
            border-radius: 50px;
            font-weight: 800;
            text-decoration: none;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        .modal-btn-register:hover { background: var(--green); color: white; }
        .modal-close {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: underline;
        }

        /* ── FLOATING CART ── */
        .floating-cart {
            position: fixed;
            bottom: 30px; right: 30px;
            width: 58px; height: 58px;
            background: var(--orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255,143,0,0.4);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 999;
            text-decoration: none;
        }
        .floating-cart:hover { transform: scale(1.1); box-shadow: 0 8px 25px rgba(255,143,0,0.5); }
        .floating-cart i { font-size: 1.6rem; color: white; }
        .cart-badge {
            position: absolute;
            top: -4px; right: -4px;
            background: #DC2626;
            color: white;
            border-radius: 50%;
            width: 20px; height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 800;
        }

        /* ── FOOTER ── */
        footer {
            background: #1A1A1A;
            color: #888;
            padding: 1.8rem 0;
            text-align: center;
            font-size: 0.85rem;
        }

        /* ── MOBILE ── */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem; left: 1rem;
            z-index: 1001;
            background: var(--orange);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .menu-toggle { display: block; }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .hero-emoji { display: none; }
            .hero-banner h1 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Foodify">
        <div class="logo-text">
            <h2>Foodify</h2>
            <span>Recipes + Groceries</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-section-label">Menu</li>
        <li><a href="index.php" class="active"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="#" onclick="requireLogin(event)"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="#" onclick="requireLogin(event)"><i class="bi bi-bag-fill"></i> Shop</a></li>

        <?php if ($isLoggedIn): ?>
        <li class="sidebar-section-label">My Account</li>
        <li><a href="#" onclick="requireLogin(event)"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
        <li><a href="#" onclick="requireLogin(event)"><i class="bi bi-truck"></i> My Orders</a></li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-bottom">
        <?php if ($isLoggedIn): ?>
            <div class="user-row">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Customer') ?></div>
                </div>
            </div>
            <a href="modules/auth/logout.php" class="btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
        <?php else: ?>
            <a href="modules/auth/login.php" class="btn-side-login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <a href="modules/auth/register.php" class="btn-side-register"><i class="bi bi-person-plus"></i> Register</a>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">

    <div class="hero-banner">
        <div class="tagline"> Your Kitchen Companion</div>
        <h1>Cook Smart,<br>Eat Fresh.</h1>
        <p>Discover thousands of recipes and get fresh groceries delivered straight to your door.</p>
        <?php if ($isLoggedIn): ?>
            <a href="#" class="hero-cta"><i class="bi bi-search"></i> Explore Recipes</a>
        <?php else: ?>
            <a href="modules/auth/register.php" class="hero-cta"><i class="bi bi-person-plus"></i> Get Started Free</a>
        <?php endif; ?>
        <div class="hero-emoji">🥘</div>
    </div>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="section-header">
                <h2>🍳 Suggested for You</h2>
                <a href="#" onclick="requireLogin(event)">View all →</a>
            </div>

            <div class="recipe-card" onclick="requireLogin(event)">
                <div class="recipe-img"><i class="bi bi-egg-fried"></i></div>
                <div class="recipe-info">
                    <div class="recipe-title">Malaysian Nasi Lemak</div>
                    <div class="recipe-desc">Fragrant coconut rice, spicy sambal, crispy anchovies, peanuts, and boiled egg — the ultimate Malaysian comfort food.</div>
                    <span class="recipe-tag">Malaysian · 45 min</span>
                </div>
            </div>

            <div class="recipe-card" onclick="requireLogin(event)">
                <div class="recipe-img" style="background: linear-gradient(135deg, #c0392b, #e74c3c)"><i class="bi bi-cup-straw"></i></div>
                <div class="recipe-info">
                    <div class="recipe-title">Classic Italian Spaghetti</div>
                    <div class="recipe-desc">Al dente spaghetti tossed in rich tomato sauce with fresh basil and Parmesan cheese.</div>
                    <span class="recipe-tag">Italian · 30 min</span>
                </div>
            </div>

            <div class="recipe-card" onclick="requireLogin(event)">
                <div class="recipe-img" style="background: linear-gradient(135deg, #e67e22, #f39c12)"><i class="bi bi-egg"></i></div>
                <div class="recipe-info">
                    <div class="recipe-title">South Indian Chicken Curry</div>
                    <div class="recipe-desc">Rich and aromatic curry with tender chicken, coconut milk, and fragrant spices.</div>
                    <span class="recipe-tag">Indian · 50 min</span>
                </div>
            </div>

            <div class="recipe-card" onclick="requireLogin(event)">
                <div class="recipe-img" style="background: linear-gradient(135deg, #8e44ad, #9b59b6)"><i class="bi bi-cup"></i></div>
                <div class="recipe-info">
                    <div class="recipe-title">Spaghetti Bolognese</div>
                    <div class="recipe-desc">Hearty meat sauce with ground beef, tomatoes, and herbs served over spaghetti.</div>
                    <span class="recipe-tag">Italian · 40 min</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="section-header">
                <h2>🛒 Groceries</h2>
                <a href="#" onclick="requireLogin(event)">View all →</a>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <div class="groceries-card">
                        <div class="groceries-icon"><i class="bi bi-basket"></i></div>
                        <p class="groceries-name">Fresh Chicken (whole)</p>
                        <p class="groceries-price">RM 12.90</p>
                        <button class="add-btn" onclick="requireLogin(event)">+ Add to Cart</button>
                    </div>
                </div>

                <div class="col-6">
                    <div class="groceries-card">
                        <div class="groceries-icon"><i class="bi bi-egg-fried"></i></div>
                        <p class="groceries-name">Free Range Eggs (10pcs)</p>
                        <p class="groceries-price">RM 6.50</p>
                        <button class="add-btn" onclick="requireLogin(event)">+ Add to Cart</button>
                    </div>
                </div>

                <div class="col-6">
                    <div class="groceries-card">
                        <div class="groceries-icon"><i class="bi bi-cup-straw"></i></div>
                        <p class="groceries-name">Coconut Milk (1L)</p>
                        <p class="groceries-price">RM 4.50</p>
                        <button class="add-btn" onclick="requireLogin(event)">+ Add to Cart</button>
                    </div>
                </div>

                <div class="col-6">
                    <div class="groceries-card">
                        <div class="groceries-icon"><i class="bi bi-apple"></i></div>
                        <p class="groceries-name">Fresh Vegetables Pack</p>
                        <p class="groceries-price">RM 8.90</p>
                        <button class="add-btn" onclick="requireLogin(event)">+ Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<footer>
    <div class="container">
        <p>© 2026 Foodify. Made with for food lovers.</p>
    </div>
</footer>

<a href="#" onclick="requireLogin(event)" class="floating-cart">
    <i class="bi bi-cart-fill"></i>
    <span class="cart-badge">0</span>
</a>

<div class="login-overlay" id="loginOverlay">
    <div class="login-modal">
        <div class="modal-icon"><i class="bi bi-lock-fill"></i></div>
        <h3>Login Required</h3>
        <p>You need to be logged in to access this feature. Join Foodify to explore recipes and shop groceries!</p>
        <a href="modules/auth/login.php" class="modal-btn-login"><i class="bi bi-box-arrow-in-right"></i> Login Now</a>
        <a href="modules/auth/register.php" class="modal-btn-register"><i class="bi bi-person-plus"></i> Create Account</a>
        <button class="modal-close" onclick="closeModal()">Maybe later</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile sidebar
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // Login gate
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    function requireLogin(e) {
        if (!isLoggedIn) {
            e.preventDefault();
            document.getElementById('loginOverlay').classList.add('show');
        }
    }

    function closeModal() {
        document.getElementById('loginOverlay').classList.remove('show');
    }

    // Close modal on overlay click
    document.getElementById('loginOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
</body>
</html>