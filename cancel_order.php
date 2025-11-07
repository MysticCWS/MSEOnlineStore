<?php
session_start();
include 'dbcon.php';

if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in first.";
    header("Location: login.php");
    exit();
}

if (isset($_POST['confirm_cancel'])) {
    $uid = $_SESSION['verified_user_id'];
    $orderId = $_POST['order_id'];

    $orderRef = "userInfo/$uid/order/$orderId";
    $orderData = $database->getReference($orderRef)->getValue();

    if ($orderData && isset($orderData['items'])) {
        foreach ($orderData['items'] as $sku => $item) {
            $productSku = $item['product_sku'];
            $qty = (int) $item['quantity'];

            // Get current stock
            $productRef = "products/$productSku";
            $product = $database->getReference($productRef)->getValue();

            if ($product && isset($product['stockbalance'])) {
                $newStock = (int)$product['stockbalance'] + $qty;

                // Update stock
                $database->getReference($productRef)->update([
                    'stockbalance' => $newStock
                ]);
            }
        }
    }

    // Delete order from database
    $database->getReference($orderRef)->remove();

    $_SESSION['status'] = "Order <b>$orderId</b> has been cancelled and removed.";
}

header("Location: orders.php");
exit();