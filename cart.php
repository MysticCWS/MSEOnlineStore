<?php
session_start();
include 'dbcon.php';
include 'includes/header.php';
echo ' | My Cart';
include 'includes/header2.php';
include 'includes/navbar.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

//Get UID
if(isset($_SESSION['verified_user_id'])){
    $uid = $_SESSION['verified_user_id'];
    try {
        $user = $auth->getUser($uid);
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        echo $e->getMessage();
    }
} else {
    $uid = null; // not logged in
    $_SESSION['status'] = "Please log in to view your cart.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];
$cart_ref = "userInfo/$uid/cart";
$cart_items = $database->getReference($cart_ref)->getValue();
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
    <!--Show Status-->
    <?php
        if(isset($_SESSION['status'])){
            echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
            unset($_SESSION['status']);
        }
    ?>

    <div class="title">
        <h2>Shopping Cart</h2>
    </div>
    
    <div class="profile-container px-4 py-4 border rounded bg-white">
        <?php if ($cart_items): ?>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Remarks</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $grand_total = 0;
                foreach ($cart_items as $sku => $item): 
                    $total_price = $item['product_price'] * $item['quantity'];
                    $grand_total += $total_price;
                    
                    // Get stock balance from products table
                    $product_sku = $item['sku'];
                    $product = $database->getReference("products/$product_sku")->getValue();
                    $stock = $product['stockbalance'];
                ?>
                    <tr>
                        <td><img src="<?= $item['product_imgurl'] ?>" width="60" height="60" alt="<?= $item['product_name'] ?>"></td>
                        <td><?= $item['product_name'] ?></td>
                        <td><?= $item['sku'] ?></td>
                        <td>RM <?= number_format($item['product_price'], 2) ?></td>
                        <form method="POST" action="update_cart.php" class="d-flex">
                            <td>
                                <input type="hidden" name="sku" value="<?= $sku ?>">
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $stock ?>" class="form-control me-2" style="width: 80px;">
                                <button type="submit" name="updateQty" class="btn btn-sm btn-outline-secondary">Update</button>
                            </td>
                            <td>
                                <input type="text" name="remark" value="<?= htmlspecialchars($item['remark']) ?>" class="form-control mb-2" placeholder="Update remark">
                                <button type="submit" name="updateQty" class="btn btn-sm btn-outline-secondary">Update</button>
                            </td>
                            <td>RM <?= number_format($total_price, 2) ?></td>
                        </form>
                        <td>
                            <form method="POST" action="update_cart.php">
                                <input type="hidden" name="sku" value="<?= $sku ?>">
                                <button type="submit" name="removeItem" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr>
                        <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                        <td colspan="2"><strong>RM <?= number_format($grand_total, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <div class="text-end">
                <a href="checkout.php" class="btn btn-outline-primary">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn btn-outline-primary btn-sm">Shop now</a>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>