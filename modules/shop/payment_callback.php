<?php

require_once __DIR__ . '/../../config/database.php';

// ToyyibPay POST parameters
$refNo      = $_POST['refno']      ?? '';
$orderId    = $_POST['order_id']   ?? ''; 
$status     = $_POST['status']     ?? ''; // 1 = success, 2 = pending, 3 = fail
$reason     = $_POST['reason']     ?? '';
$billCode   = $_POST['billcode']   ?? '';
$amount     = $_POST['amount']     ?? 0;


file_put_contents(__DIR__ . '/callback_log.txt',
    date('Y-m-d H:i:s') . " | orderId: $orderId | status: $status | refNo: $refNo\n",
    FILE_APPEND
);

// Extract order_id dari external ref (format: ORDER-123)
$orderIdClean = intval(str_replace('ORDER-', '', $orderId));
if (!$orderIdClean) {
    http_response_code(400);
    exit('Invalid order');
}

if ($status == 1) {
    // Payment success
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'success', transaction_ref = ?, paid_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->bind_param("si", $refNo, $orderIdClean);
    $stmt->execute();

    $stmt2 = $conn->prepare("UPDATE orders SET status = 'processing' WHERE order_id = ?");
    $stmt2->bind_param("i", $orderIdClean);
    $stmt2->execute();

} elseif ($status == 3) {
    // Payment failed
    $stmt = $conn->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ?");
    $stmt->bind_param("i", $orderIdClean);
    $stmt->execute();
}

http_response_code(200);
echo 'OK';