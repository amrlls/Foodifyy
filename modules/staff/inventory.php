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

if (!$user || $user['role'] !== 'staff') {
    header('Location: ../index.php');
    exit();
}

$staff_username = $user['username'];
$profile_img = $user['profile_image'];
$items = $conn->query("SELECT * FROM items ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$greeting = "Manage your inventory stock.";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; }
            .inventory-card { background: white; border-radius: 20px; padding: 1rem; transition: 0.3s; height: 100%; }
            .inventory-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
            .stock-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
            .stock-high { background: #E8F5E9; color: #4CAF50; }
            .stock-medium { background: #FFF3E0; color: #FF9800; }
            .stock-low { background: #FFEBEE; color: #F44336; }
            .btn-update { background: #1A1C1E; color: white; border: none; padding: 6px 16px; border-radius: 12px; font-size: 0.75rem; }
            .btn-update:hover { background: #FF8E53; }
        </style>
    </head>
    <body>
        <?php include 'includes/staff_nav.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="fw-bold mb-1">Inventory</h2><p class="text-muted mb-0">Update stock quantities</p></div><div><div class="small text-muted">Total Items</div><div class="h4 fw-bold"><?= count($items) ?></div></div></div>
            <div class="row g-4">
                <?php foreach ($items as $item): 
                    $stockClass = $item['stock'] <= 0 ? 'stock-low' : ($item['stock'] <= 10 ? 'stock-medium' : 'stock-high');
                    $stockText = $item['stock'] <= 0 ? 'Out of Stock' : ($item['stock'] <= 10 ? 'Low Stock' : 'In Stock');
                    $imgSrc = getImageSrc($item['image'], '../../assets/images/items/');
                ?>
                <div class="col-md-6 col-lg-4"><div class="inventory-card"><div class="d-flex gap-3"><div style="width:70px; height:70px; border-radius:16px; overflow:hidden; background:#f0f0f0;"><img src="<?= $imgSrc && $imgSrc !== 'placeholder' ? htmlspecialchars($imgSrc) : 'https://placehold.co/400x400?text='.urlencode($item['name'][0]) ?>" class="w-100 h-100 object-fit-cover"></div><div class="flex-grow-1"><div class="d-flex justify-content-between"><h6 class="fw-bold mb-0"><?= htmlspecialchars($item['name']) ?></h6><span class="stock-badge <?= $stockClass ?>"><?= $stockText ?></span></div><div class="small text-muted mb-2"><?= htmlspecialchars($item['category']) ?></div><div class="d-flex justify-content-between align-items-end"><div><span class="fw-bold text-primary">RM <?= number_format($item['price'], 2) ?></span><div class="small">Stock: <span class="fw-bold"><?= $item['stock'] ?></span></div></div><button class="btn-update" onclick="openStockModal(<?= $item['item_id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['stock'] ?>)"><i class="bi bi-pencil"></i> Update</button></div></div></div></div></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal fade" id="stockModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius: 28px;"><div class="modal-header border-0 pt-4 px-4"><h5 class="modal-title fw-bold">Update Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body px-4"><p>Update stock for <span id="modalItemName" class="fw-bold"></span></p><div class="mb-3"><label>Current Stock</label><input type="text" id="currentStock" class="form-control" readonly disabled style="border-radius: 12px;"></div><div class="mb-3"><label>New Stock Quantity</label><input type="number" id="newStock" class="form-control" min="0" step="1" style="border-radius: 12px; padding: 12px;"></div></div><div class="modal-footer border-0 pb-4 px-4"><button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn-update px-4 py-2" onclick="updateStock()">Update</button></div></div></div></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        let currentItemId = null, modal = null;
        function openStockModal(itemId, itemName, currentStock) {
            currentItemId = itemId;
            document.getElementById('modalItemName').innerText = itemName;
            document.getElementById('currentStock').value = currentStock;
            document.getElementById('newStock').value = '';
            if (!modal) modal = new bootstrap.Modal(document.getElementById('stockModal'));
            modal.show();
        }
        async function updateStock() {
            let newStock = parseInt(document.getElementById('newStock').value);
            if (isNaN(newStock) || newStock < 0) { alert('Enter a valid quantity'); return; }
            const formData = new FormData();
            formData.append('item_id', currentItemId);
            formData.append('stock', newStock);
            const response = await fetch('update_stock.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) { modal.hide(); location.reload(); }
            else alert('Failed to update stock');
        }
        </script>
    </body>
</html>