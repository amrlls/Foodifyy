<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false]); exit(); }
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user || $user['role'] !== 'staff') { echo json_encode(['success' => false]); exit(); }

$item_id = $_POST['item_id'] ?? null;
$new_stock = $_POST['stock'] ?? null;
if (!$item_id || $new_stock === null) { echo json_encode(['success' => false]); exit(); }
$new_stock = max(0, intval($new_stock));
$stmt = $conn->prepare("UPDATE items SET stock = ? WHERE item_id = ?");
$stmt->bind_param("ii", $new_stock, $item_id);
echo json_encode(['success' => $stmt->execute()]);
?>