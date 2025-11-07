<?php
session_start();
include 'dbcon.php';
include 'includes/header.php';
echo ' | Checkout';
include 'includes/header2.php';
include 'includes/navbar.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if user is logged in
if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in to proceed to checkout.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];

// Get cart items
$cart_ref = "userInfo/$uid/cart";
$cart_items = $database->getReference($cart_ref)->getValue();

// Try fetching userInfo
$userInfo_ref = "userInfo/$uid";
$userInfo = $database->getReference($userInfo_ref)->getValue();

// If userInfo doesn't exist, create a basic profile
if (empty($userInfo['uid'])) {
    try {
        $user = $auth->getUser($uid);

        $basicInfo = [
            'uid' => $uid,
            'displayName' => $user->displayName ?? '',
            'email' => $user->email ?? '',
            'phoneNumber' => $user->phoneNumber ?? '',
            'deliveryAddress' => '', // set empty for now
        ];

        $database->getReference($userInfo_ref)->update($basicInfo);
        $userInfo = $basicInfo;
    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        $_SESSION['status'] = "User not found.";
        header("Location: login.php");
        exit();
    }
}

$deliveryAddress = $userInfo['deliveryAddress'] ?? '';
?>

<script>
  // Wait until the DOM is fully loaded
  document.addEventListener("DOMContentLoaded", function() {
    const msg = document.getElementById("statusMessage");
    if (msg) {
      // After 5 seconds, remove or hide the element
      setTimeout(() => {
        // Fade-out effect
        msg.style.transition = "opacity 0.5s ease";
        msg.style.opacity = "0";
        // Remove from DOM after fade-out
        setTimeout(() => msg.remove(), 500);
    }
  });
</script>

<div class="content">
    <?php
    if (isset($_SESSION['status'])) {
        echo "<h5 class='alert alert-success'>" . $_SESSION['status'] . "</h5>";
        unset($_SESSION['status']);
    }
    ?>

    <div class="container mt-4">
        <h2>Checkout</h2>

        <?php if (!$cart_items): ?>
            <p>Your cart is empty. <a href="products.php">Shop now</a></p>
        <?php else: ?>
            <form method="POST" action="fiuu_payment.php">
                <input type="hidden" name="uid" value="<?= $uid ?>">

                <!-- Show User Details -->
                <div class="mb-3">
                    <h5>User Details:</h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars($userInfo['displayName'] ?? '') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($userInfo['email'] ?? '') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($userInfo['phoneNumber'] ?? '') ?></p>
                </div>

                <!-- Delivery Address -->
                <div class="mb-3">
                    <label for="deliveryAddress" class="form-label">Delivery Address</label>
                    <textarea class="form-control" name="deliveryAddress" id="deliveryAddress" rows="3" required><?= htmlspecialchars($deliveryAddress) ?></textarea>
                    <small class="text-muted">Please confirm or update your delivery address before placing the order.</small>
                </div>

                <!-- Cart Summary -->
                <h5>Your Items:</h5>
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $grand_total = 0;
                    foreach ($cart_items as $sku => $item):
                        $total = $item['product_price'] * $item['quantity'];
                        $grand_total += $total;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td>RM <?= number_format($item['product_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>RM <?= number_format($total, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                            <td><strong>RM <?= number_format($grand_total, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <input type="hidden" name="amount" value="<?= $grand_total ?>">

                <div class="mt-4">
                    <a href="cart.php" class="btn btn-outline-success">Back to Cart</a>
                    <button type="submit" name="btnPayment" class="btn btn-primary">Proceed to Payment</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>