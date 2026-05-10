//adam <!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Cookbooks</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            :root {
                --primary-green: #2E7D32;
                --primary-orange: #FF8F00;
                --primary-lime: #9FA825;
                --dark-text: #212121;
                --sidebar-width: 280px;
            }
            body {
                background: #f5f5f5;
                font-family: system-ui, sans-serif;
                margin: 0;
                padding: 0;
            }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: var(--sidebar-width);
                height: 100vh;
                background: white;
                box-shadow: 2px 0 12px rgba(0,0,0,0.05);
                padding: 2rem 1rem;
                overflow-y: auto;
                z-index: 1000;
            }
            .sidebar-logo {
                text-align: center;
                margin-bottom: 2rem;
            }
            .sidebar-logo img {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 20px;
            }
            .sidebar-logo h2 {
                font-weight: 800;
                color: var(--primary-green);
                margin-top: 0.5rem;
                font-size: 1.5rem;
            }
            .sidebar-nav {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .sidebar-nav li {
                margin-bottom: 0.5rem;
            }
            .sidebar-nav a {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                color: var(--dark-text);
                text-decoration: none;
                border-radius: 12px;
                transition: all 0.2s;
                font-weight: 500;
            }
            .sidebar-nav a:hover, .sidebar-nav a.active {
                background: var(--primary-orange);
                color: white;
            }
            .sidebar-nav a i {
                font-size: 1.25rem;
                width: 24px;
            }
            .user-info {
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid #e0e0e0;
            }
            .user-info .user-avatar {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 1rem;
            }
            .user-info .user-avatar i {
                font-size: 2rem;
                color: var(--primary-green);
            }
            .login-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .btn-login-side {
                background: var(--primary-orange);
                color: white;
                border: none;
                padding: 8px;
                border-radius: 50px;
                font-weight: 600;
                text-align: center;
                text-decoration: none;
            }
            .btn-register-side {
                background: transparent;
                border: 1.5px solid var(--primary-green);
                color: var(--primary-green);
                padding: 8px;
                border-radius: 50px;
                font-weight: 600;
                text-align: center;
                text-decoration: none;
            }
            .main-content {
                margin-left: var(--sidebar-width);
                padding: 2rem;
                min-height: 100vh;
            }
            .top-bar {
                margin-bottom: 2rem;
            }
            .top-bar h1 {
                font-size: 1.8rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }
            .section-title {
                font-size: 1.3rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .section-title i {
                color: var(--primary-orange);
            }
            .recipe-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-bottom: 3rem;
            }
            .recipe-card {
                background: white;
                border-radius: 20px;
                overflow: hidden;
                transition: all 0.2s;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                position: relative;
            }
            .recipe-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            }
            .delete-icon {
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(255,255,255,0.9);
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                color: #dc3545;
                z-index: 2;
            }
            .recipe-img {
                height: 160px;
                background: linear-gradient(135deg, var(--primary-green), var(--primary-lime));
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .recipe-img i {
                font-size: 3.5rem;
                color: white;
            }
            .recipe-info {
                padding: 1rem;
            }
            .recipe-title {
                font-weight: 700;
                margin-bottom: 0.25rem;
            }
            .recipe-meta {
                font-size: 0.75rem;
                color: #999;
                display: flex;
                justify-content: space-between;
            }
            .empty-state {
                text-align: center;
                padding: 2rem;
                color: #aaa;
                grid-column: 1 / -1;
            }
            .empty-state i {
                font-size: 3rem;
                margin-bottom: 1rem;
                color: #ccc;
            }
            .footer {
                background: #1a1a1a;
                color: #aaa;
                padding: 2rem 0;
                margin-top: 2rem;
                text-align: center;
            }
            .floating-cart {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 60px;
                height: 60px;
                background: var(--primary-orange);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                cursor: pointer;
                transition: all 0.3s ease;
                z-index: 999;
                text-decoration: none;
            }
            .floating-cart:hover {
                transform: scale(1.1);
                background: #e07f00;
            }
            .floating-cart i {
                font-size: 1.8rem;
                color: white;
            }
            .cart-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #dc3545;
                color: white;
                border-radius: 50%;
                width: 22px;
                height: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                font-weight: 700;
            }
            @media (max-width: 768px) {
                .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
                .sidebar.open { transform: translateX(0); }
                .main-content { margin-left: 0; padding: 1rem; }
                .menu-toggle {
                    display: block;
                    position: fixed;
                    top: 1rem;
                    left: 1rem;
                    z-index: 1001;
                    background: var(--primary-orange);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    padding: 8px 12px;
                }
                .floating-cart { bottom: 20px; right: 20px; width: 50px; height: 50px; }
                .floating-cart i { font-size: 1.5rem; }
            }
            @media (min-width: 769px) { .menu-toggle { display: none; } }

            .add-recipe-card {
                background: white;
                border-radius: 20px;
                border: 2px dashed #ccc;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                min-height: 220px;
                cursor: pointer;
                transition: all 0.2s;
                color: #aaa;
                font-weight: 600;
                font-size: 0.95rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .add-recipe-card:hover {
                border-color: var(--primary-green);
                color: var(--primary-green);
                transform: translateY(-5px);
                box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            }
            .add-recipe-card i { font-size: 2.5rem; }
            .cat-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 1.25rem;
            }
            .cat-item {
                padding: 10px 14px;
                border: 1.5px solid #e0e0e0;
                border-radius: 12px;
                background: #fff;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
                font-family: inherit;
                font-weight: 500;
                transition: all 0.15s;
                color: #333;
            }
            .cat-item:hover { border-color: var(--primary-green); background: #f1f8f1; }
            .cat-item.selected {
                border-color: var(--primary-green);
                background: #e8f5e9;
                color: var(--primary-green);
                font-weight: 700;
            }
            .cat-item i { font-size: 1.2rem; }
            .step { display: none; }
            .step.active { display: block; }
        </style>
    </head>
    <body>
        <button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="Foodify Icon.png" alt="Foodify Logo">
                <h2>FOODIFY</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.html"><i class="bi bi-house"></i> HOME</a></li>
                <li><a href="recipes.html"><i class="bi bi-journal-bookmark-fill"></i> RECIPES</a></li>
                <li><a href="shop.html"><i class="bi bi-bag"></i> SHOP</a></li>
                <li><a href="myCookbooks.html" class="active"><i class="bi bi-bookmark-heart-fill"></i> MY COOKBOOKS</a></li>
                <li><a href="myOrders.html"><i class="bi bi-truck"></i> MY ORDERS</a></li>
            </ul>
            <div class="user-info">
                <div class="user-avatar"><i class="bi bi-person-circle"></i><span>Guest User</span></div>
                <div class="login-buttons">
                    <a href="loginPage.html" class="btn-login-side">Login</a>
                    <a href="registerPage.html" class="btn-register-side">Register</a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>My Cookbooks</h1>
                <p class="text-secondary">Manage your personal recipes and saved favorites</p>
            </div>

            <div class="section-title">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>My Recipes</span>
            </div>
            <!-- Grid kosong — JavaScript akan isi -->
            <div class="recipe-grid" id="myRecipesGrid">
                <div class="add-recipe-card" id="addRecipeBtn" onclick="openAddModal()">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add New Recipe</span>
                </div>
            </div>

            <div class="section-title">
                <i class="bi bi-bookmark-heart-fill"></i>
                <span>Saved Recipes</span>
            </div>
            <div class="recipe-grid" id="savedRecipesGrid"></div>
        </div>

        <footer class="footer">
            <div class="container">
                <p class="mb-0">© 2026 Foodify. All rights reserved.</p>
            </div>
        </footer>

        <a href="cart.html" class="floating-cart">
            <i class="bi bi-cart"></i>
            <span class="cart-badge" id="cartBadge">0</span>
        </a>

        <!-- Add Recipe Modal -->
        <div class="modal fade" id="addRecipeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:20px;border:none;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Add New Recipe</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <div class="step active" id="step1">
                            <p class="text-secondary mb-3" style="font-size:14px;">Choose a category for your recipe</p>
                            <div class="cat-grid">
                                <button class="cat-item" onclick="selectCat(this,'Main Dish','bi-bowl-hot')"><i class="bi bi-bowl-hot"></i> Main Dish</button>
                                <button class="cat-item" onclick="selectCat(this,'Drinks','bi-cup-straw')"><i class="bi bi-cup-straw"></i> Drinks</button>
                                <button class="cat-item" onclick="selectCat(this,'Dessert','bi-cake2')"><i class="bi bi-cake2"></i> Dessert</button>
                                <button class="cat-item" onclick="selectCat(this,'Snacks','bi-bag-heart')"><i class="bi bi-bag-heart"></i> Snacks</button>
                                <button class="cat-item" onclick="selectCat(this,'Soup','bi-droplet-half')"><i class="bi bi-droplet-half"></i> Soup</button>
                                <button class="cat-item" onclick="selectCat(this,'Other','bi-star')"><i class="bi bi-star"></i> Other</button>
                            </div>
                            <button class="btn w-100 text-white fw-bold" style="background:var(--primary-green);border-radius:12px;padding:10px;" onclick="goStep2()">Next <i class="bi bi-arrow-right"></i></button>
                        </div>
                        <div class="step" id="step2">
                            <div class="mb-3">
                                <label class="form-label text-secondary" style="font-size:13px;">Recipe Name</label>
                                <input type="text" id="recipeName" class="form-control" placeholder="e.g. Mee Goreng Mamak" style="border-radius:12px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary" style="font-size:13px;">Short Description (optional)</label>
                                <textarea id="recipeDesc" class="form-control" rows="3" placeholder="What makes this recipe special?" style="border-radius:12px;resize:none;"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary fw-semibold flex-fill" style="border-radius:12px;" onclick="goBack()"><i class="bi bi-arrow-left"></i> Back</button>
                                <button class="btn text-white fw-bold flex-fill" style="background:var(--primary-green);border-radius:12px;" onclick="saveRecipe()">Save Recipe</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // ── Sidebar ───────────────────────────────────────────────
            const menuToggle = document.getElementById('menuToggle');
            const sidebar    = document.getElementById('sidebar');
            if (menuToggle) menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

            // ── Helper ────────────────────────────────────────────────
            function escapeHtml(text) {
                const d = document.createElement('div');
                d.textContent = text;
                return d.innerHTML;
            }

            // ── MY RECIPES — disimpan dalam localStorage ──────────────
            const DEFAULT_MY_RECIPES = [
                { name: 'My Special Fried Rice', icon: 'bi-egg-fried',  date: 'Jan 15, 2025' },
                { name: 'Family Secret Rendang', icon: 'bi-cup-straw',  date: 'Feb 3, 2025'  }
            ];

            function getMyRecipes() {
                const stored = localStorage.getItem('myRecipes');
                if (!stored) {
                    // Kali pertama — isi dengan data default
                    localStorage.setItem('myRecipes', JSON.stringify(DEFAULT_MY_RECIPES));
                    return DEFAULT_MY_RECIPES;
                }
                return JSON.parse(stored);
            }

            function saveMyRecipes(recipes) {
                localStorage.setItem('myRecipes', JSON.stringify(recipes));
            }

            function renderMyRecipes() {
                const recipes   = getMyRecipes();
                const grid      = document.getElementById('myRecipesGrid');
                const addBtn    = document.getElementById('addRecipeBtn');

                // Kosongkan grid tapi simpan butang Add
                grid.innerHTML = '';

                recipes.forEach((recipe, index) => {
                    const card = document.createElement('div');
                    card.className = 'recipe-card';
                    card.setAttribute('onclick',
                        `window.location.href='recipeDetails.html?name=${encodeURIComponent(recipe.name)}&icon=${encodeURIComponent(recipe.icon)}'`
                    );
                    card.innerHTML = `
                        <div class="delete-icon" onclick="event.stopPropagation(); deleteMyRecipe(${index})">
                            <i class="bi bi-trash3"></i>
                        </div>
                        <div class="recipe-img"><i class="bi ${escapeHtml(recipe.icon)}"></i></div>
                        <div class="recipe-info">
                            <div class="recipe-title">${escapeHtml(recipe.name)}</div>
                            <div class="recipe-meta"><span>Created: ${escapeHtml(recipe.date)}</span></div>
                        </div>`;
                    grid.appendChild(card);
                });

                // Letak balik butang Add di hujung
                grid.appendChild(addBtn);
            }

            function deleteMyRecipe(index) {
                if (!confirm('Delete this recipe?')) return;
                const recipes = getMyRecipes();
                recipes.splice(index, 1);
                saveMyRecipes(recipes);
                renderMyRecipes();
            }

            // ── SAVED RECIPES ─────────────────────────────────────────
            function loadSavedRecipes() {
                const savedRecipes = JSON.parse(localStorage.getItem('savedRecipes') || '[]');
                const savedGrid    = document.getElementById('savedRecipesGrid');
                if (!savedGrid) return;

                if (savedRecipes.length === 0) {
                    savedGrid.innerHTML = '<div class="empty-state"><i class="bi bi-bookmark"></i><p>No saved recipes yet. Save recipes from recipe details page!</p></div>';
                    return;
                }

                savedGrid.innerHTML = savedRecipes.map((recipe, index) => `
                    <div class="recipe-card" onclick="window.location.href='recipeDetails.html?name=${encodeURIComponent(recipe)}&icon=bi-journal-bookmark-fill'">
                        <div class="delete-icon" onclick="event.stopPropagation(); removeSavedRecipe(${index})">
                            <i class="bi bi-bookmark-x"></i>
                        </div>
                        <div class="recipe-img"><i class="bi bi-journal-bookmark-fill"></i></div>
                        <div class="recipe-info">
                            <div class="recipe-title">${escapeHtml(recipe)}</div>
                            <div class="recipe-meta"><span>Saved recipe</span></div>
                        </div>
                    </div>`).join('');
            }

            function removeSavedRecipe(index) {
                if (!confirm('Remove this saved recipe?')) return;
                let saved = JSON.parse(localStorage.getItem('savedRecipes') || '[]');
                saved.splice(index, 1);
                localStorage.setItem('savedRecipes', JSON.stringify(saved));
                loadSavedRecipes();
            }

            // ── CART BADGE ────────────────────────────────────────────
            function updateCartBadge() {
                const cart  = JSON.parse(localStorage.getItem('cart') || '[]');
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent    = cart.length;
                    badge.style.display  = cart.length > 0 ? 'flex' : 'none';
                }
            }

            // ── MODAL: Add Recipe ──────────────────────────────────────
            let selectedCat  = '';
            let selectedIcon = 'bi-journal-bookmark-fill';
            let addModal;

            function openAddModal() {
                selectedCat  = '';
                selectedIcon = 'bi-journal-bookmark-fill';
                document.querySelectorAll('.cat-item').forEach(e => e.classList.remove('selected'));
                document.getElementById('recipeName').value = '';
                document.getElementById('recipeDesc').value = '';
                document.getElementById('step1').classList.add('active');
                document.getElementById('step2').classList.remove('active');
                addModal = new bootstrap.Modal(document.getElementById('addRecipeModal'));
                addModal.show();
            }

            function selectCat(el, cat, icon) {
                document.querySelectorAll('.cat-item').forEach(e => e.classList.remove('selected'));
                el.classList.add('selected');
                selectedCat  = cat;
                selectedIcon = icon;
            }

            function goStep2() {
                if (!selectedCat) { alert('Please choose a category first.'); return; }
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
            }

            function goBack() {
                document.getElementById('step2').classList.remove('active');
                document.getElementById('step1').classList.add('active');
            }

            function saveRecipe() {
                const name = document.getElementById('recipeName').value.trim();
                if (!name) { alert('Please enter a recipe name.'); return; }

                const today  = new Date();
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const dateStr = `${months[today.getMonth()]} ${today.getDate()}, ${today.getFullYear()}`;

                // Simpan dalam localStorage — kekal walaupun refresh
                const recipes = getMyRecipes();
                recipes.push({ name, icon: selectedIcon, date: dateStr });
                saveMyRecipes(recipes);

                renderMyRecipes();
                addModal.hide();
            }

            // ── INIT ──────────────────────────────────────────────────
            renderMyRecipes();
            loadSavedRecipes();
            updateCartBadge();

            window.addEventListener('storage', e => {
                if (e.key === 'cart')         updateCartBadge();
                if (e.key === 'savedRecipes') loadSavedRecipes();
                if (e.key === 'myRecipes')    renderMyRecipes();
            });
        </script>
    </body>
</html>