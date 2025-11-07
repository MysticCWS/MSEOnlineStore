<?php
session_start();
include 'dbcon.php';   // Firebase DB reference
include 'pgcon.php';   // Loads $fiuu Payment object

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

// Get order from Firebase
$orderRef = "userInfo/$uid/order/$orderId";
$orderData = $database->getReference($orderRef)->getValue();

if (!$orderData) {
    $_SESSION['status'] = "Order not found.";
    header("Location: orders.php");
    exit();
}

// Block already paid orders
if ($orderData['status'] === 'success') {
    $_SESSION['status'] = "This order has already been paid.";
    header("Location: orders.php");
    exit();
}

// Extract payment details
$bill_name   = $orderData['user_fullname'];
$bill_email  = $orderData['user_email'];
$bill_mobile = $orderData['user_phone'];
$amount      = $orderData['total_amount'];
$bill_desc   = "Payment for Order: $orderId";

$baseUrl     = "http://localhost/MSEOnlineStore"; // Website domain
$returnUrl   = $baseUrl . "/payment_return.php?order_id=$orderId";
$callbackUrl = $baseUrl . "/payment_callback.php";
$cancelUrl   = $baseUrl . "/payment_cancel.php?order_id=$orderId";

// Re-generate payment URL
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