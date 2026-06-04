<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['items' => []]);
    exit;
}

$orderId = intval($_GET['order_id'] ?? 0);
if (!$orderId) {
    echo json_encode(['items' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT oi.quantity, oi.unit_price, i.name
    FROM order_items oi
    JOIN items i ON oi.item_id = i.item_id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['items' => $items]);