<?php
session_start();

require_once __DIR__ . '/../../config/database.php'; 

header('Content-Type: application/json');

// Cek jika user dah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sila login terlebih dahulu']);
    exit;
}

$user_id = $_SESSION['user_id'];
$recipe_id = $_POST['recipe_id'] ?? null;

if (!$recipe_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID Resipi tidak dijumpai']);
    exit;
}

// 1. Semak jika resipi sudah disimpan oleh user ini
$check = $conn->prepare("SELECT * FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
$check->bind_param("ii", $user_id, $recipe_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // 2. Jika sudah ada, kita PADAM (Unsave)
    $stmt = $conn->prepare("DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();
    echo json_encode(['status' => 'removed']);
} else {
    // 3. Jika belum ada, kita SIMPAN (Save)
    $stmt = $conn->prepare("INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();
    echo json_encode(['status' => 'saved']);
}
exit;