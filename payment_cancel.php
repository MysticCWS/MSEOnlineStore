<?php
session_start();
include 'dbcon.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Ensure user is logged in
if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in first.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];
$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    $_SESSION['status'] = "Invalid order ID.";
    header("Location: home.php");
    exit();
}

// Reference to the order in Firebase
$orderRef = "userInfo/$uid/order/$orderId";
$orderData = $database->getReference($orderRef)->getValue();

if ($orderData) {
    // Ensure status stays as pending
    $database->getReference($orderRef)->update([
        'status' => 'pending'
    ]);

    $_SESSION['status'] = "Payment cancelled, please pay again if you wish to place the order.";
} else {
    $_SESSION['status'] = "Order not found.";
}

// Redirect user to their orders page
header("Location: orders.php");
exit();