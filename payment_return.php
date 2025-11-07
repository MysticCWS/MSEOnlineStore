<?php
session_start();
include 'dbcon.php';
include 'pgcon.php'; // load $fiuu and secret key

date_default_timezone_set('Asia/Kuala_Lumpur');

// Ensure script is receiving POST data only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['status'] = "Invalid request method.";
    header("Location: orders.php");
    exit();
}

// Retrieve all fields from Fiuu POST
$tranID   = $_POST['tranID'] ?? '';
$orderId  = $_POST['orderid'] ?? '';
$status   = $_POST['status'] ?? ''; // "00" = success
$domain   = $_POST['domain'] ?? '';
$amount   = $_POST['amount'] ?? '';
$currency = $_POST['currency'] ?? '';
$appcode  = $_POST['appcode'] ?? '';
$paydate  = $_POST['paydate'] ?? '';
$skey     = $_POST['skey'] ?? '';

$sec_key = $FIUU_SECRET_KEY; // From pgcon.php

// Verify signature using official formula from Fiuu documentation
$key0 = md5($tranID . $orderId . $status . $domain . $amount . $currency);
$key1 = md5($paydate . $domain . $key0 . $appcode . $sec_key);

if ($skey !== $key1) {
    $status = '-1'; // Invalid signature
}

// Try to find the order in Firebase
$users = $database->getReference("userInfo")->getValue();
$foundUid = null;
$orderData = null;

if ($users) {
    foreach ($users as $uid => $userData) {
        if (isset($userData['order'][$orderId])) {
            $foundUid = $uid;
            $orderData = $userData['order'][$orderId];
            break;
        }
    }
}

// Handle result and update Firebase
if ($orderData && $foundUid) {
    $orderRef = "userInfo/$foundUid/order/$orderId";

    if ($status === "00") {
        // Success
        $database->getReference($orderRef)->update([
            'status'   => 'success',
            'paid_at'  => date('c'),
            'tran_id'  => $tranID,
            'app_code' => $appcode,
            'verified_by_return' => true,
        ]);
        $_SESSION['status'] = "Payment successful! Your order <b>$orderId</b> has been placed.";
    } elseif ($status === '-1') {
        // Invalid signature
        $_SESSION['status'] = "Invalid payment signature received. Please contact support.";
    } else {
        // Failed / cancelled
        $database->getReference($orderRef)->update([
            'status' => 'failed',
            'verified_by_return' => true,
        ]);
        $_SESSION['status'] = "Payment failed or cancelled for order <b>$orderId</b>. Please try again.";
    }
} else {
    $_SESSION['status'] = "Order not found. Please contact support with Order ID: <b>$orderId</b>.";
}

// Redirect back to orders
header("Location: orders.php");
exit();