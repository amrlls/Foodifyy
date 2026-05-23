<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$itemId = intval($_POST['item_id'] ?? 0);
$qty    = intval($_POST['quantity'] ?? 1);

if (!$itemId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item']);
    exit;
}

// Semak item wujud dan ada stock
$check = $conn->prepare("SELECT item_id FROM items WHERE item_id = ? AND stock > 0");
$check->bind_param("i", $itemId);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    echo json_encode(['status' => 'error', 'message' => 'Item not available']);
    exit;
}

// Kalau item dah ada dalam cart, tambah quantity
$stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND item_id = ?");
$stmt->bind_param("ii", $userId, $itemId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    $newQty = $existing['quantity'] + $qty;
    $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $upd->bind_param("ii", $newQty, $existing['id']);
    $upd->execute();
} else {
    $ins = $conn->prepare("INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)");
    $ins->bind_param("iii", $userId, $itemId, $qty);
    $ins->execute();
}

echo json_encode(['status' => 'success']);