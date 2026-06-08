<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt_user = $conn->prepare("SELECT username, phone, address FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $userId);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

$nav_profile_img = "";
$nav_role = "Customer";
$username = 'Guest';
$stmt_nav = $conn->prepare("SELECT username, profile_image, role FROM users WHERE user_id = ?");
$stmt_nav->bind_param("i", $userId);
$stmt_nav->execute();
$user_nav = $stmt_nav->get_result()->fetch_assoc();
if ($user_nav) {
    $username        = $user_nav['username'];
    $nav_profile_img = $user_nav['profile_image'];
    $nav_role        = $user_nav['role'];
}

$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity,
           i.item_id, i.name, i.price, i.image, i.category, i.unit
    FROM cart c
    JOIN items i ON c.item_id = i.item_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    header('Location: items.php');
    exit;
}

$subtotal    = array_sum(array_map(fn($r) => $r['price'] * $r['quantity'], $cartItems));
$deliveryFee = 3.50;
$total       = $subtotal + $deliveryFee;
$cartCount   = array_sum(array_column($cartItems, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); --sidebar-dark: #1A1C1E; --accent: #FF8E53; --soft-bg: #fdfdfd; --sidebar-w: 280px; --card-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--soft-bg); color: #1A1C1E; overflow-x: hidden; }

        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh; background: var(--sidebar-dark); color: white; padding: 2.5rem 1.5rem; z-index: 1000; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); overflow-y: auto; transition: transform 0.3s ease; }
        .sidebar-logo h2 { font-family: 'Playfair Display', serif; font-weight: 900; letter-spacing: -1px; background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; padding-left: 1rem; }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 15px; padding: 14px 18px; color: #949494; text-decoration: none; border-radius: 16px; font-weight: 500; transition: all 0.3s; }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px; border-radius: 20px; transition: all 0.2s; cursor: pointer; }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        .main-content { margin-left: var(--sidebar-w); padding: 0; min-height: 100vh; background: white; }
        .breadcrumb-bar { padding: 1.2rem 4rem; border-bottom: 1px solid #f5f5f5; background: white; display: flex; align-items: center; gap: 8px; }
        .breadcrumb-bar a { color: #bdc3c7; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .breadcrumb-bar a:hover { color: var(--accent); }
        .breadcrumb-bar span { color: #bdc3c7; font-size: 0.85rem; }
        .breadcrumb-bar .current { color: #1A1C1E; font-size: 0.85rem; font-weight: 700; }

        .steps-bar { padding: 1.5rem 4rem; background: white; display: flex; align-items: center; gap: 0; border-bottom: 1px solid #f5f5f5; }
        .step { display: flex; align-items: center; gap: 10px; font-size: 0.82rem; font-weight: 700; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; }
        .step.done .step-num { background: var(--primary-grad); color: white; }
        .step.active .step-num { background: #1A1C1E; color: white; }
        .step.inactive .step-num { background: #f0f0f0; color: #bdc3c7; }
        .step.done .step-label { color: var(--accent); }
        .step.active .step-label { color: #1A1C1E; }
        .step.inactive .step-label { color: #bdc3c7; }
        .step-line { flex: 1; height: 2px; background: #f0f0f0; margin: 0 12px; max-width: 60px; }
        .step-line.done { background: var(--primary-grad); }

        .content-body { padding: 2.5rem 4rem; }
        .form-card { background: white; border-radius: 24px; padding: 2rem; border: 1px solid #f0f0f0; box-shadow: var(--card-shadow); margin-bottom: 1.5rem; }
        .form-card h5 { font-weight: 800; font-size: 1rem; color: #1A1C1E; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .form-card h5 i { width: 32px; height: 32px; border-radius: 10px; background: var(--primary-grad); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
        .form-label { font-size: 0.8rem; font-weight: 700; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .form-control { border: 1.5px solid #f0f0f0; border-radius: 14px; padding: 12px 16px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9rem; font-weight: 500; transition: 0.2s; background: #fafafa; }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(255,142,83,0.1); background: white; outline: none; }
        textarea.form-control { resize: none; }

        .payment-option { border: 2px solid #f0f0f0; border-radius: 16px; padding: 1.2rem 1.5rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 14px; margin-bottom: 0.8rem; }
        .payment-option:hover { border-color: #ddd; }
        .payment-option.selected { border-color: var(--accent); background: #fffaf8; }
        .payment-option input[type="radio"] { display: none; }
        .payment-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #ddd; flex-shrink: 0; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .payment-option.selected .payment-radio { border-color: var(--accent); background: var(--accent); }
        .payment-option.selected .payment-radio::after { content: ''; width: 8px; height: 8px; background: white; border-radius: 50%; }
        .payment-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .payment-label { font-weight: 700; font-size: 0.9rem; color: #1A1C1E; }
        .payment-desc  { font-size: 0.78rem; color: #bdc3c7; margin-top: 2px; }

        .summary-card { background: white; border-radius: 24px; padding: 2rem; border: 1px solid #f0f0f0; box-shadow: var(--card-shadow); position: sticky; top: 2rem; }
        .summary-card h5 { font-weight: 800; font-size: 1rem; margin-bottom: 1.5rem; }
        .summary-item { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; }
        .summary-item-img { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .summary-item-img img { width: 100%; height: 100%; object-fit: cover; }
        .summary-item-img i  { font-size: 1.2rem; color: white; }
        .summary-item-name { font-weight: 700; font-size: 0.85rem; flex-grow: 1; }
        .summary-item-qty  { font-size: 0.78rem; color: #bdc3c7; }
        .summary-item-price { font-weight: 800; font-size: 0.85rem; white-space: nowrap; }
        .summary-divider { border: none; border-top: 1px solid #f5f5f5; margin: 1rem 0; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.6rem; font-size: 0.88rem; }
        .summary-row span:first-child { color: #7f8c8d; }
        .summary-row span:last-child  { font-weight: 700; }
        .summary-total { font-size: 1.1rem; font-weight: 800; }
        .summary-total span:last-child { background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-place-order { width: 100%; padding: 15px; border: none; border-radius: 16px; background: var(--primary-grad); color: white; font-weight: 800; font-size: 0.95rem; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: 0.3s; margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-place-order:hover { opacity: 0.88; box-shadow: 0 12px 30px rgba(255,107,107,0.3); transform: translateY(-2px); }
        .btn-place-order:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── RESPONSIVE ── */
        @media (max-width: 992px) {
            .breadcrumb-bar, .steps-bar, .content-body { padding: 1.5rem 2rem; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; padding-top: 5rem; }
            .breadcrumb-bar, .steps-bar, .content-body { padding: 1rem 1.2rem; }
            .step-label { display: none; }
            .summary-card { position: static; margin-top: 1.5rem; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR (mobile) ── -->
<div id="topbar" style="display:none;position:fixed;top:0;left:0;right:0;z-index:999;background:#1A1C1E;padding:1rem 1.5rem;align-items:center;justify-content:space-between;">
    <span style="font-family:'Playfair Display',serif;font-weight:900;font-size:1.5rem;background:linear-gradient(135deg,#FF6B6B,#FF8E53);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">foodify.</span>
    <button onclick="toggleSidebar()" style="background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;"><i class="bi bi-list" id="hamburgerIcon"></i></button>
</div>

<div id="sidebarOverlay" onclick="toggleSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:998;"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Almost there!</p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="items.php" class="active"><i class="bi bi-bag-heart"></i> Market</a></li>
        <?php if ($isLoggedIn ?? true): ?>
            <li><a href="../recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
            <li><a href="../order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
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
    </div>
</div>

<div class="main-content">
    <div class="breadcrumb-bar">
        <a href="items.php">Market</a>
        <span>/</span>
        <a href="cart.php">Cart</a>
        <span>/</span>
        <span class="current">Checkout</span>
    </div>

    <div class="steps-bar">
        <div class="step done">
            <div class="step-num"><i class="bi bi-check-lg" style="font-size:0.7rem;"></i></div>
            <span class="step-label">Cart</span>
        </div>
        <div class="step-line done"></div>
        <div class="step active">
            <div class="step-num">2</div>
            <span class="step-label">Checkout</span>
        </div>
        <div class="step-line"></div>
        <div class="step inactive">
            <div class="step-num">3</div>
            <span class="step-label">Payment</span>
        </div>
        <div class="step-line"></div>
        <div class="step inactive">
            <div class="step-num">4</div>
            <span class="step-label">Done</span>
        </div>
    </div>

    <div class="content-body">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="form-card">
                    <h5><i class="bi bi-geo-alt-fill"></i> Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="shipping_name" class="form-control" placeholder="Enter your full name" value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" id="shipping_phone" class="form-control" placeholder="01X-XXXXXXXX" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12" id="addressField">
                            <label class="form-label">Delivery Address</label>
                            <textarea id="shipping_address" class="form-control" rows="3" placeholder="Enter your full delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12" id="pickupInfo" style="display:none;">
                            <div style="background:#f8f9fa;border-radius:14px;padding:1rem 1.2rem;">
                                <div style="font-weight:700;font-size:0.88rem;margin-bottom:4px;"><i class="bi bi-geo-alt-fill me-2" style="color:var(--accent);"></i>Pickup Location</div>
                                <div style="font-size:0.85rem;color:#7f8c8d;">Foodifyy Store, Kuala Lumpur</div>
                                <div style="font-size:0.8rem;color:#bdc3c7;margin-top:4px;">Mon–Sat: 9am – 6pm</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h5><i class="bi bi-truck"></i> Delivery Method</h5>
                    <div class="payment-option selected" onclick="selectDelivery('delivery', this)">
                        <input type="radio" name="delivery" value="delivery" checked>
                        <div class="payment-radio"></div>
                        <div class="payment-icon" style="background:#fff0e8;color:#FF8E53;"><i class="bi bi-truck"></i></div>
                        <div><div class="payment-label">Home Delivery</div><div class="payment-desc">Delivered to your address · RM 3.50</div></div>
                    </div>
                    <div class="payment-option" onclick="selectDelivery('pickup', this)">
                        <input type="radio" name="delivery" value="pickup">
                        <div class="payment-radio"></div>
                        <div class="payment-icon" style="background:#e8fdf0;color:#00b894;"><i class="bi bi-shop"></i></div>
                        <div><div class="payment-label">Self Pickup</div><div class="payment-desc">Pick up at our store · Free</div></div>
                    </div>
                </div>

                <div class="form-card">
                    <h5><i class="bi bi-credit-card-fill"></i> Payment Method</h5>
                    <div class="payment-option selected" onclick="selectPayment('online_banking', this)">
                        <input type="radio" name="payment" value="online_banking" checked>
                        <div class="payment-radio"></div>
                        <div class="payment-icon" style="background:#e8f4fd;color:#0984e3;"><i class="bi bi-bank"></i></div>
                        <div><div class="payment-label">Online Banking</div><div class="payment-desc">FPX — Maybank, CIMB, RHB & more</div></div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('cod', this)">
                        <input type="radio" name="payment" value="cod">
                        <div class="payment-radio"></div>
                        <div class="payment-icon" style="background:#e8fdf0;color:#00b894;"><i class="bi bi-cash-stack"></i></div>
                        <div><div class="payment-label">Cash on Delivery</div><div class="payment-desc">Pay when your order arrives</div></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="summary-card">
                    <h5>Order Summary</h5>
                    <?php
                    $catColors = [
                        'Vegetables'     => ['grad' => 'linear-gradient(135deg,#1e5128,#4e944f)', 'icon' => 'bi-flower1'],
                        'Fruits'         => ['grad' => 'linear-gradient(135deg,#c72b2b,#ff6b6b)', 'icon' => 'bi-apple'],
                        'Meat'           => ['grad' => 'linear-gradient(135deg,#7b2d00,#d45700)', 'icon' => 'bi-egg'],
                        'Meat & Poultry' => ['grad' => 'linear-gradient(135deg,#7b2d00,#d45700)', 'icon' => 'bi-egg'],
                        'Seafood'        => ['grad' => 'linear-gradient(135deg,#0a3d62,#1e90ff)', 'icon' => 'bi-water'],
                        'Dairy'          => ['grad' => 'linear-gradient(135deg,#4a4a8a,#a29bfe)', 'icon' => 'bi-cup-straw'],
                        'Grains'         => ['grad' => 'linear-gradient(135deg,#7d6608,#f9ca24)', 'icon' => 'bi-grid'],
                        'Spices'         => ['grad' => 'linear-gradient(135deg,#6d1a36,#e84393)', 'icon' => 'bi-lightning'],
                        'Beverages'      => ['grad' => 'linear-gradient(135deg,#1a4a6d,#00b894)', 'icon' => 'bi-cup'],
                    ];
                    foreach ($cartItems as $row):
                        $cs     = $catColors[$row['category']] ?? ['grad' => 'linear-gradient(135deg,#2D3436,#000)', 'icon' => 'bi-bag'];
                        $imgSrc = getImageSrc($row['image'] ?? '', '../../assets/images/items/');
                    ?>
                    <div class="summary-item">
                        <div class="summary-item-img" style="background:<?= $cs['grad'] ?>;">
                            <?php if ($imgSrc): ?><img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
                            <?php else: ?><i class="bi <?= $cs['icon'] ?>"></i><?php endif; ?>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="summary-item-name"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="summary-item-qty">x<?= $row['quantity'] ?><?= $row['unit'] ? ' · per ' . htmlspecialchars($row['unit']) : '' ?></div>
                        </div>
                        <div class="summary-item-price">RM <?= number_format($row['price'] * $row['quantity'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>

                    <hr class="summary-divider">
                    <div class="summary-row"><span>Subtotal (<?= $cartCount ?> items)</span><span>RM <?= number_format($subtotal, 2) ?></span></div>
                    <div class="summary-row" id="deliveryRow"><span>Delivery</span><span id="deliveryVal">RM 3.50</span></div>
                    <hr class="summary-divider">
                    <div class="summary-row summary-total"><span>Total</span><span id="totalVal">RM <?= number_format($total, 2) ?></span></div>

                    <button class="btn-place-order" id="placeOrderBtn" onclick="placeOrder()">
                        <i class="bi bi-lock-fill"></i> Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedPayment  = 'online_banking';
let selectedDelivery = 'delivery';
const subtotal       = <?= $subtotal ?>;
const deliveryFee    = 3.50;

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

function selectPayment(method, el) {
    selectedPayment = method;
    el.closest('.form-card').querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}

function selectDelivery(method, el) {
    selectedDelivery = method;
    el.closest('.form-card').querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');

    if (method === 'pickup') {
        document.getElementById('addressField').style.display = 'none';
        document.getElementById('pickupInfo').style.display   = 'block';
        document.getElementById('deliveryVal').textContent    = 'Free';
        document.getElementById('totalVal').textContent       = 'RM ' + subtotal.toFixed(2);
    } else {
        document.getElementById('addressField').style.display = 'block';
        document.getElementById('pickupInfo').style.display   = 'none';
        document.getElementById('deliveryVal').textContent    = 'RM 3.50';
        document.getElementById('totalVal').textContent       = 'RM ' + (subtotal + deliveryFee).toFixed(2);
    }
}

async function placeOrder() {
    const name  = document.getElementById('shipping_name').value.trim();
    const phone = document.getElementById('shipping_phone').value.trim();
    let address = '';

    if (selectedDelivery === 'delivery') {
        address = document.getElementById('shipping_address').value.trim();
        if (!address) { alert('Please enter your delivery address.'); return; }
    } else {
        address = 'Self Pickup';
    }

    if (!name || !phone) { alert('Please fill in your name and phone number.'); return; }

    const btn = document.getElementById('placeOrderBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

    try {
        const fd = new FormData();
        fd.append('shipping_name',    name);
        fd.append('shipping_phone',   phone);
        fd.append('shipping_address', address);
        fd.append('payment_method',   selectedPayment);
        fd.append('delivery_method',  selectedDelivery);

        const res  = await fetch('place_order.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Something went wrong. Please try again.');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-lock-fill"></i> Place Order';
        }
    } catch (err) {
        alert('Connection error. Please try again.');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-lock-fill"></i> Place Order';
    }
}
</script>
</body>
</html>