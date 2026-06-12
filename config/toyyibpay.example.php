<?php
define('TOYYIBPAY_SECRET_KEY',    'your_secret_key_here');
define('TOYYIBPAY_CATEGORY_CODE', 'your_category_code_here');
define('TOYYIBPAY_BASE_URL',      'https://toyyibpay.com');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'];

// Auto detect subfolder
$scriptDir = dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))); 
$appBase   = rtrim($scriptDir, '/');

define('TOYYIBPAY_RETURN_URL',   $baseUrl . $appBase . '/modules/shop/payment_return.php');
define('TOYYIBPAY_CALLBACK_URL', $baseUrl . $appBase . '/modules/shop/payment_callback.php');