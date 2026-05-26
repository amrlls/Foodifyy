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

$order_id = $_POST['order_id'] ?? null;
$new_status = $_POST['status'] ?? null;
if (!$order_id || !$new_status) { echo json_encode(['success' => false]); exit(); }
$valid_statuses = ['accepted', 'preparing', 'completed'];
if (!in_array($new_status, $valid_statuses)) { echo json_encode(['success' => false]); exit(); }

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$stmt->bind_param("si", $new_status, $order_id);
echo json_encode(['success' => $stmt->execute()]);
?>