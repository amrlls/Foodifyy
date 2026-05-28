<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/toyyibpay.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId   = $_SESSION['user_id'];
$name     = trim($_POST['shipping_name']    ?? '');
$phone    = trim($_POST['shipping_phone']   ?? '');
$address  = trim($_POST['shipping_address'] ?? '');
$method   = $_POST['payment_method']  ?? '';
$delivery = $_POST['delivery_method'] ?? 'delivery';

if (!$name || !$phone || !$address) {
    echo json_encode(['status' => 'error', 'message' => 'Missing shipping details']);
    exit;
}
if (!in_array($method, ['online_banking', 'cod'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment method']);
    exit;
}

// Fetch user email for ToyyibPay
$stmtUser = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$email   = $userRow['email'] ?? 'customer@foodifyy.com';

// Delivery fee
$deliveryFee = ($delivery === 'pickup') ? 0.00 : 3.50;

// Fetch cart
$stmt = $conn->prepare("
    SELECT c.quantity, i.item_id, i.name, i.price, i.stock
    FROM cart c
    JOIN items i ON c.item_id = i.item_id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

$subtotal = array_sum(array_map(fn($r) => $r['price'] * $r['quantity'], $cartItems));
$total    = $subtotal + $deliveryFee;

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Insert order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_price, delivery_fee, status, shipping_name, shipping_phone, shipping_address)
        VALUES (?, ?, ?, 'pending', ?, ?, ?)
    ");
    $stmt->bind_param("iddsss", $userId, $total, $deliveryFee, $name, $phone, $address);
    $stmt->execute();
    $orderId = $conn->insert_id;

    // 2. Insert order_items
    $stmtItem = $conn->prepare("
        INSERT INTO order_items (order_id, item_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cartItems as $item) {
        $stmtItem->bind_param("iiid", $orderId, $item['item_id'], $item['quantity'], $item['price']);
        $stmtItem->execute();

        // Kurangkan stock
        $stmtStock = $conn->prepare("UPDATE items SET stock = stock - ? WHERE item_id = ?");
        $stmtStock->bind_param("ii", $item['quantity'], $item['item_id']);
        $stmtStock->execute();
    }

    // 3. Insert payment record
    $stmtPay = $conn->prepare("
        INSERT INTO payments (order_id, amount, method, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmtPay->bind_param("ids", $orderId, $total, $method);
    $stmtPay->execute();

    // 4. Clear cart
    $stmtClear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmtClear->bind_param("i", $userId);
    $stmtClear->execute();

    $conn->commit();

    // 5. COD — terus ke return page
    if ($method === 'cod') {
        echo json_encode(['status' => 'success', 'redirect' => 'payment_return.php?my_order_id=' . $orderId . '&status=cod']);
        exit;
    }

    // 6. Online banking — create ToyyibPay bill
    $billName        = 'Foodifyy Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    $billDesc        = 'Grocery order from Foodifyy';
    $billAmount      = number_format($total * 100, 0, '.', ''); // dalam sen
    $billReturnUrl   = TOYYIBPAY_RETURN_URL . '?my_order_id=' . $orderId;
    $billCallbackUrl = TOYYIBPAY_CALLBACK_URL;
    $billExternalRef = 'ORDER-' . $orderId;

    $postData = [
        'userSecretKey'   => TOYYIBPAY_SECRET_KEY,
        'categoryCode'    => TOYYIBPAY_CATEGORY_CODE,
        'billName'        => $billName,
        'billDescription' => $billDesc,
        'billPriceSetting'=> 1,
        'billPayorInfo'   => 1,
        'billAmount'      => $billAmount,
        'billReturnUrl'   => $billReturnUrl,
        'billCallbackUrl' => $billCallbackUrl,
        'billExternalReferenceNo' => $billExternalRef,
        'billTo'          => $name,
        'billEmail'       => $email,
        'billPhone'       => $phone,
        'billSplitPayment'=> 0,
        'billSplitPaymentArgs' => '',
        'billPaymentChannel'   => '0',
        'billContentEmail'     => 'Thank you for your order at Foodifyy!',
        'billChargeToCustomer' => 1,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TOYYIBPAY_BASE_URL . '/index.php/api/createBill');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result[0]['BillCode'])) {
        $billCode = $result[0]['BillCode'];
        $payUrl   = TOYYIBPAY_BASE_URL . '/' . $billCode;

        // Save bill code in payments table
        $stmtBill = $conn->prepare("UPDATE payments SET transaction_ref = ? WHERE order_id = ?");
        $stmtBill->bind_param("si", $billCode, $orderId);
        $stmtBill->execute();

        echo json_encode(['status' => 'success', 'redirect' => $payUrl]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create payment bill. Please try again.']);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Order failed: ' . $e->getMessage()]);
}