<?php
session_start();
include 'dbcon.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check user login
if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in to continue.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    $_SESSION['status'] = "Invalid order ID.";
    header("Location: orders.php");
    exit();
}

// Fetch order data
$orderRef = "userInfo/$uid/order/$orderId";
$orderData = $database->getReference($orderRef)->getValue();

if (!$orderData || empty($orderData['items'])) {
    $_SESSION['status'] = "Order not found or has no items.";
    header("Location: orders.php");
    exit();
}

// Loop through order items and re-add to cart
foreach ($orderData['items'] as $sku => $item) {
    $cartRef = "userInfo/$uid/cart/$sku";
    $cartItem = $database->getReference($cartRef)->getValue();

    if ($cartItem) {
        // If already in cart, just increase quantity
        $newQty = $cartItem['quantity'] + $item['quantity'];
        $database->getReference($cartRef)->update([
            'quantity' => $newQty,
            'remark'   => $cartItem['remark'] ?? ''
        ]);
    } else {
        // Add fresh item to cart
        $database->getReference($cartRef)->set([
            'sku'           => $sku,
            'product_name'  => $item['product_name'],
            'product_price' => $item['product_price'],
            'product_imgurl'=> $item['product_imgurl'] ?? 'default.png',
            'quantity'      => $item['quantity'],
            'remark'        => '',
        ]);
    }
}

$_SESSION['status'] = "Items from Order <b>$orderId</b> have been added to your cart.";
header("Location: cart.php");
exit();