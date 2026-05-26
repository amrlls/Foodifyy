<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $cartId = intval($_POST['cart_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'update') {
        $qty = intval($_POST['quantity'] ?? 0);
        if ($cartId && $qty >= 1) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $qty, $cartId, $userId);
            echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error']);
        }

    } elseif ($action === 'remove') {
        if ($cartId) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cartId, $userId);
            echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
    exit;
}

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

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, 
           i.item_id, i.name, i.price, i.image, i.category, i.unit, i.stock
    FROM cart c
    JOIN items i ON c.item_id = i.item_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subtotal = array_sum(array_map(fn($r) => $r['price'] * $r['quantity'], $cartItems));
$cartCount = array_sum(array_column($cartItems, 'quantity'));

$catColors = [
    'Vegetables' => 'linear-gradient(135deg,#1e5128,#4e944f)',
    'Fruits'     => 'linear-gradient(135deg,#c72b2b,#ff6b6b)',
    'Meat'       => 'linear-gradient(135deg,#7b2d00,#d45700)',
    'Seafood'    => 'linear-gradient(135deg,#0a3d62,#1e90ff)',
    'Dairy'      => 'linear-gradient(135deg,#4a4a8a,#a29bfe)',
    'Grains'     => 'linear-gradient(135deg,#7d6608,#f9ca24)',
    'Spices'     => 'linear-gradient(135deg,#6d1a36,#e84393)',
    'Beverages'  => 'linear-gradient(135deg,#1a4a6d,#00b894)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart – Foodify</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--soft-bg); color: #1A1C1E; overflow-x: hidden; }

        /* Sidebar — same as items.php */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05); overflow-y: auto;
        }
        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif; font-weight: 900; letter-spacing: -1px;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem; padding-left: 1rem;
        }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 15px; padding: 14px 18px;
            color: #949494; text-decoration: none; border-radius: 16px;
            font-weight: 500; transition: all 0.3s;
        }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }

        /* Main */
        .main-content { margin-left: var(--sidebar-w); padding: 0; min-height: 100vh; }
        .header-section { padding: 3rem 4rem 2rem; background: white; border-bottom: 1px solid #f5f5f5; }
        .header-section h1 {
            font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 900; color: #1A1C1E;
        }
        .header-section p { color: #7f8c8d; font-size: 1rem; margin-top: 0.5rem; }

        .content-body { padding: 2.5rem 4rem; background: #fdfdfd; }

        /* Cart item row */
        .cart-item {
            background: white; border-radius: 20px; padding: 1.2rem 1.4rem;
            display: flex; align-items: center; gap: 1.2rem;
            border: 1px solid #f0f0f0; box-shadow: var(--card-shadow);
            transition: 0.3s;
        }
        .cart-item:hover { box-shadow: 0 16px 36px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .item-img {
            width: 72px; height: 72px; border-radius: 14px; overflow: hidden;
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }
        .item-img i { font-size: 1.8rem; color: white; }

        .item-info { flex-grow: 1; min-width: 0; }
        .item-name { font-weight: 800; font-size: 0.95rem; color: #1A1C1E; margin-bottom: 3px; }
        .item-cat  { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #bdc3c7; }

        .item-price {
            font-size: 1rem; font-weight: 800;
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            white-space: nowrap;
        }

        /* Qty control */
        .qty-control { display: flex; align-items: center; gap: 10px; }
        .qty-btn {
            width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #eee;
            background: white; color: #1A1C1E; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover { background: #1A1C1E; color: white; border-color: #1A1C1E; }
        .qty-num { font-weight: 800; font-size: 0.95rem; min-width: 24px; text-align: center; }

        /* Remove btn */
        .btn-remove {
            background: none; border: none; color: #dfe6e9;
            cursor: pointer; transition: 0.2s; padding: 6px;
            border-radius: 8px;
        }
        .btn-remove:hover { color: #e17055; background: #fff5f3; }

        /* Summary card */
        .summary-card {
            background: white; border-radius: 24px; padding: 2rem;
            border: 1px solid #f0f0f0; box-shadow: var(--card-shadow);
            position: sticky; top: 2rem;
        }
        .summary-card h5 { font-weight: 800; font-size: 1.1rem; margin-bottom: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.8rem; font-size: 0.9rem; }
        .summary-row span:first-child { color: #7f8c8d; }
        .summary-row span:last-child  { font-weight: 700; }
        .summary-divider { border: none; border-top: 1px solid #f5f5f5; margin: 1.2rem 0; }
        .summary-total { font-size: 1.2rem; font-weight: 800; }
        .summary-total span:last-child {
            background: var(--primary-grad); background-clip: text;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .btn-checkout {
            width: 100%; padding: 14px; border: none; border-radius: 16px;
            background: var(--primary-grad); color: white;
            font-weight: 800; font-size: 0.95rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: 0.3s; margin-top: 1.5rem;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-checkout:hover { opacity: 0.88; box-shadow: 0 10px 28px rgba(255,107,107,0.3); transform: translateY(-2px); }

        .btn-continue {
            width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 16px;
            background: white; color: #1A1C1E;
            font-weight: 700; font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: 0.3s; margin-top: 0.8rem;
            text-align: center; text-decoration: none; display: block;
        }
        .btn-continue:hover { border-color: #1A1C1E; color: #1A1C1E; }

        /* Empty state */
        .empty-state { text-align: center; padding: 5rem 2rem; }
        .empty-state i { font-size: 4rem; color: #eee; display: block; margin-bottom: 1.5rem; }
        .empty-state h4 { font-weight: 800; color: #1A1C1E; margin-bottom: 0.5rem; }
        .empty-state p  { color: #bdc3c7; font-size: 0.9rem; }

        @media (max-width: 992px) {
            .header-section, .content-body { padding: 2rem; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Review your bag.</p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <li><a href="../recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
        <li><a href="../order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
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

<!-- Main -->
<div class="main-content">
    <div class="header-section">
        <h1>Your Bag</h1>
        <p id="headerCount"><?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?> in your cart</p>
    </div>

    <div class="content-body">
        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <i class="bi bi-bag-x"></i>
                <h4>Your bag is empty</h4>
                <p>Add some fresh groceries to get started.</p>
                <a href="items.php" class="btn mt-3 px-4 py-2 fw-bold text-white" style="background:var(--primary-grad);border-radius:14px;">
                    Browse Market
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Cart items list -->
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-3" id="cartList">
                        <?php foreach ($cartItems as $row):
                            $grad   = $catColors[$row['category']] ?? 'linear-gradient(135deg,#2D3436,#000)';
                            $imgSrc = getImageSrc($row['image'] ?? '', '../../assets/images/items/');
                            $lineTotal = $row['price'] * $row['quantity'];
                        ?>
                        <div class="cart-item" id="row-<?= $row['cart_id'] ?>">
                            <!-- Image -->
                            <div class="item-img" style="background:<?= $grad ?>;">
                                <?php if ($imgSrc): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                <?php else: ?>
                                    <i class="bi bi-bag"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Info -->
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="item-cat"><?= htmlspecialchars($row['category']) ?><?= $row['unit'] ? ' · per ' . htmlspecialchars($row['unit']) : '' ?></div>
                            </div>

                            <!-- Qty control -->
                            <div class="qty-control">
                                <button class="qty-btn" onclick="updateQty(<?= $row['cart_id'] ?>, -1)">−</button>
                                <span class="qty-num" id="qty-<?= $row['cart_id'] ?>"><?= $row['quantity'] ?></span>
                                <button class="qty-btn" onclick="updateQty(<?= $row['cart_id'] ?>, 1)">+</button>
                            </div>

                            <!-- Line total -->
                            <div class="item-price" id="linetotal-<?= $row['cart_id'] ?>">
                                RM <?= number_format($lineTotal, 2) ?>
                            </div>

                            <!-- Remove -->
                            <button class="btn-remove" onclick="removeItem(<?= $row['cart_id'] ?>)" title="Remove">
                                <i class="bi bi-trash3 fs-5"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Summary -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5>Order Summary</h5>

                        <div class="summary-row">
                            <span>Subtotal (<span id="itemCount"><?= $cartCount ?></span> items)</span>
                            <span id="subtotalVal">RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery</span>
                            <span>RM 3.50</span>
                        </div>

                        <hr class="summary-divider">

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="totalVal">RM <?= number_format($subtotal + 5, 2) ?></span>
                        </div>

                        <button class="btn-checkout" onclick="window.location.href='checkout.php'">
                            <i class="bi bi-lock-fill"></i> Proceed to Checkout
                        </button>
                        <a href="items.php" class="btn-continue">
                            <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const prices = {
    <?php foreach ($cartItems as $r): ?>
    <?= $r['cart_id'] ?>: <?= $r['price'] ?>,
    <?php endforeach; ?>
};

async function updateQty(cartId, delta) {
    const qtyEl = document.getElementById('qty-' + cartId);
    let qty = parseInt(qtyEl.textContent) + delta;
    if (qty < 1) { removeItem(cartId); return; }

    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('cart_id', cartId);
    fd.append('quantity', qty);

    const res  = await fetch('cart.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        qtyEl.textContent = qty;
        document.getElementById('linetotal-' + cartId).textContent =
            'RM ' + (prices[cartId] * qty).toFixed(2);
        recalcTotal();
    }
}

async function removeItem(cartId) {
    const fd = new FormData();
    fd.append('action', 'remove');
    fd.append('cart_id', cartId);

    const res  = await fetch('cart.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') {
        document.getElementById('row-' + cartId).remove();
        delete prices[cartId];
        recalcTotal();
        if (Object.keys(prices).length === 0) location.reload();
    }
}

function recalcTotal() {
    let sub = 0;
    let totalQty = 0;
    document.querySelectorAll('[id^="qty-"]').forEach(el => {
        const cid = el.id.replace('qty-', '');
        const qty = parseInt(el.textContent);
        sub      += (prices[cid] || 0) * qty;
        totalQty += qty;
    });
    document.getElementById('subtotalVal').textContent = 'RM ' + sub.toFixed(2);
    document.getElementById('totalVal').textContent    = 'RM ' + (sub + 3.50).toFixed(2);
    document.getElementById('itemCount').textContent   = totalQty;
    document.getElementById('headerCount').textContent = totalQty + ' item' + (totalQty !== 1 ? 's' : '') + ' in your cart';
}
</script>
</body>
</html>