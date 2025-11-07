<?php
session_start();
include 'dbcon.php';        // Firebase DB reference
include 'pgcon.php';        // Loads $fiuu Payment object
include 'includes/header.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check user login
if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in to checkout.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];

// Get user info
$userInfoRef = "userInfo/$uid";
$userInfo = $database->getReference($userInfoRef)->getValue();

$user = $auth->getUser($uid);

$userDisplayName = $user->displayName;
$userEmail = $user->email;
$userPhone = $user->phoneNumber;

if (isset($_POST['btnPayment'])){
    $userData = [
        'uid' => $uid,
        'displayName' => $userDisplayName,
        'email' => $userEmail,
        'phoneNumber' => $userPhone,
        'deliveryAddress' => $_POST['deliveryAddress'],
    ];
    $database->getReference($userInfoRef)->update($userData);
    $userInfo = $userData;
}

if (empty($userInfo['deliveryAddress'])) {
    $_SESSION['status'] = "Please update your delivery address before checkout.";
    header("Location: checkout.php");
    exit();
}

// Load cart
$cartRef = "userInfo/$uid/cart";
$cartItems = $database->getReference($cartRef)->getValue();

if (!$cartItems) {
    $_SESSION['status'] = "Your cart is empty.";
    header("Location: products.php");
    exit();
}

// Calculate grand total
$grandTotal = 0;
foreach ($cartItems as $item) {
    $grandTotal += $item['product_price'] * $item['quantity'];
}

// Generate unique order ID
$orderId = 'ORDER_' . time();

// Map cart items to include product_sku
$orderItems = [];
foreach ($cartItems as $sku => $item) {
    $orderItems[$sku] = [
        'product_sku'   => $sku,
        'product_name'  => $item['product_name'],
        'product_price' => $item['product_price'],
        'quantity'      => $item['quantity']
    ];
}

// Prepare order data
$orderData = [
    'order_id' => $orderId,
    'uid' => $uid,
    'user_fullname' => $userInfo['displayName'] ?? '',
    'user_email' => $userInfo['email'] ?? '',
    'user_phone' => $userInfo['phoneNumber'] ?? '',
    'delivery_address' => $userInfo['deliveryAddress'],
    'items' => $orderItems,
    'total_amount' => $grandTotal,
    'status' => 'pending',
    'created_at' => date('c'),
];

// Final check stock balance before allow payment
if (isset($orderData['items']) && is_array($orderData['items'])) {
    foreach ($orderData['items'] as $sku => $item) {
        if (!isset($item['quantity'])) {
            continue;
        }

        $qty = (int)$item['quantity'];
        $productRef = "products/$sku";
        $productData = $database->getReference($productRef)->getValue();

        if ($productData && isset($productData['stockbalance'])) {
            $stock = (int)$productData['stockbalance'];
            
            if ($qty > $stock){
                $_SESSION['status'] = "Cart item SKU: <b>$sku</b> quantity exceed available stock balance, please modify quantity accordingly.";
                header("Location: checkout.php");
                die();
            }
        }
    }
}

// Save order
$orderRef = "userInfo/$uid/order/$orderId";
$database->getReference($orderRef)->update($orderData);

// Clear cart
$cartRef = "userInfo/$uid/cart";
$database->getReference($cartRef)->remove();

// Deduct stock for each product in the order
if (isset($orderData['items']) && is_array($orderData['items'])) {
    foreach ($orderData['items'] as $sku => $item) {
        if (!isset($item['quantity'])) {
            continue;
        }

        $qty = (int)$item['quantity'];
        $productRef = "products/$sku";
        $productData = $database->getReference($productRef)->getValue();

        if ($productData && isset($productData['stockbalance'])) {
            $newStock = max(0, (int)$productData['stockbalance'] - $qty);
            $database->getReference($productRef)->update([
                'stockbalance' => $newStock
            ]);
        }
    }
}

// Payment details
$bill_name = $orderData['user_fullname'];
$bill_email = $orderData['user_email'];
$bill_mobile = $orderData['user_phone'];
$amount = $grandTotal;
$bill_desc = "Payment for Order: $orderId";

$baseUrl = "http://localhost/MSEOnlineStore"; // Website domain
$returnUrl = $baseUrl . "/payment_return.php?order_id=$orderId";
$callbackUrl = $baseUrl . "/payment_callback.php";
$cancelUrl = $baseUrl . "/payment_cancel.php?order_id=$orderId";

$paymentUrl = $fiuu->getPaymentUrl(
    $orderId,
    $amount,
    $bill_name,
    $bill_email,
    $bill_mobile,
    $bill_desc,
    $channel,
    $currency,
    $returnUrl,
    $callbackUrl,
    $cancelUrl
);

// Redirect to Fiuu
header("Location: $paymentUrl");
exit();