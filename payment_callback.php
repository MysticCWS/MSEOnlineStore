<?php
include 'dbcon.php';
include 'pgcon.php';

// Make sure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

// Get Fiuu data
$paydate = $_POST['paydate'] ?? '';
$domain = $_POST['domain'] ?? '';
$key = $_POST['key'] ?? '';
$appcode = $_POST['appcode'] ?? '';
$skey = $_POST['skey'] ?? '';
$orderId = $_POST['orderid'] ?? '';

// Verify Fiuu signature
$valid = $fiuu->verifySignature($paydate, $domain, $key, $appcode, $skey);

if ($valid && $orderId) {
    // 🔎 Find UID associated with this order
    $users = $database->getReference("userInfo")->getValue();
    $uid = null;

    foreach ($users as $userId => $userData) {
        if (isset($userData['order'][$orderId])) {
            $uid = $userId;
            break;
        }
    }

    if ($uid) {
        $orderRef = "userInfo/$uid/order/$orderId";
        $orderData = $database->getReference($orderRef)->getValue();

        if ($orderData) {
            // Update order status
            $database->getReference($orderRef)->update([
                'status' => 'success',
                'paid_at' => date('c'),
                'payment_ref' => $key,
                'payment_verified_by_callback' => true,
            ]);
        }
    }

    echo "OK";
} else {
    http_response_code(400);
    echo "Verification failed";
}