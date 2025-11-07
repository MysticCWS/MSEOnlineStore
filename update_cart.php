<?php
session_start();
include 'dbcon.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['verified_user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];
$sku = $_POST['sku'];
$cart_ref = "userInfo/$uid/cart/$sku";

if (isset($_POST['updateQty'])) {
    $newQty = (int)$_POST['quantity'];
    $remark = trim($_POST['remark']);

    // Get product stock balance
    $product = $database->getReference("products/$sku")->getValue();
    $stock = $product['stockbalance'] ?? 999;

    if ($newQty > $stock) {
        $_SESSION['status'] = "Cannot exceed available stock ($stock).";
    } else {
        $updateData = [
            'quantity' => max(1, $newQty),
            'remark' => $remark,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $database->getReference($cart_ref)->update($updateData);
        $_SESSION['status'] = "Cart updated successfully.";
        header("Location: cart.php");
        exit();
    }

} elseif (isset($_POST['removeItem'])) {
    $database->getReference($cart_ref)->remove();
    $_SESSION['status'] = "Item removed from cart.";
}

header("Location: cart.php");
exit();