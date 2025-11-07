<?php
session_start();
include 'dbcon.php';
include 'includes/header.php';
echo ' | My Orders';
include 'includes/header2.php';
include 'includes/navbar.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check login
if (!isset($_SESSION['verified_user_id'])) {
    $_SESSION['status'] = "Please log in to view your orders.";
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];

// Fetch orders
$ordersRef = "userInfo/$uid/order";
$orders = $database->getReference($ordersRef)->getValue();
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

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">    
            <h2>My Orders</h2>

            <!-- Filter Form -->
            <form method="GET" class="d-flex align-items-center mb-3">
              <label for="filter" class="me-2 mb-0 text-nowrap">Filter by Status:</label>
              <select name="filter" id="filter" class="form-select form-select-md" style="width: 180px;" onchange="this.form.submit()">
                  <?php
                  $validFilters = ['All', 'Pending', 'Shipped Out', 'Completed'];
                  $filter = isset($_GET['filter']) && in_array($_GET['filter'], $validFilters) ? $_GET['filter'] : 'All';
                  foreach ($validFilters as $f) {
                      $selected = ($filter === $f) ? 'selected' : '';
                      echo "<option value='$f' $selected>$f</option>";
                  }
                  ?>
              </select>
            </form>
        </div>

        <?php if (!$orders): ?>
            <p>You have not placed any orders yet.</p>
            <a href="products.php" class="btn btn-outline-primary btn-sm">Shop now</a>
        <?php else: ?>
            <?php
            // Sort orders latest to oldest
            usort($orders, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Apply filter
            if ($filter !== 'All') {
                $orders = array_filter($orders, function($order) use ($filter) {
                    $status = $order['delivery_status'] ?? 'Preparing';

                    if ($filter === 'Pending' && !in_array($status, ['Preparing', 'Packing'])) {
                        return false;
                    }
                    if ($filter === 'Shipped Out' && $status !== 'Shipped Out') {
                        return false;
                    }
                    if ($filter === 'Completed' && $status !== 'Completed') {
                        return false;
                    }
                    return true;
                });
            }
            ?>

            <?php if (empty($orders)): ?>
                <p class="text-muted">No orders found for the selected filter.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?>
                            <span class="badge bg-secondary float-end"><?= ucfirst($order['status']) ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></p>
                            <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>

                            <!-- Delivery status -->
                            <p><strong>Delivery Status:</strong> 
                                <?= htmlspecialchars($order['delivery_status'] ?? 'Not available yet') ?>
                            </p>
                            <?php if (!empty($order['courier']) || !empty($order['tracking_number'])): ?>
                                <p><strong>Courier:</strong> <?= htmlspecialchars($order['courier'] ?? '-') ?></p>
                                <p><strong>Tracking Number:</strong> <?= htmlspecialchars($order['tracking_number'] ?? '-') ?></p>
                            <?php endif; ?>

                            <h6>Items:</h6>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Product</th>
                                        <th>Unit Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_sku'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td>RM <?= number_format($item['product_price'], 2) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>RM <?= number_format($item['product_price'] * $item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                        <td><strong>RM <?= number_format($order['total_amount'], 2) ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Action buttons -->
                            <div class="mt-3">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="pay_again.php?order_id=<?= urlencode($order['order_id']) ?>" 
                                       class="btn btn-sm btn-outline-success">Pay Again</a>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'pending' || $order['status'] === 'failed'): ?>
                                    <button 
                                        class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#cancelOrderModal" 
                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>">
                                        Cancel Order
                                    </button>
                                <?php endif; ?>

                                <!-- Buy again button -->
                                <a href="buy_again.php?order_id=<?= urlencode($order['order_id']) ?>" 
                                   class="btn btn-sm btn-outline-primary">Buy Again</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="cancel_order.php">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Cancel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to cancel this order? This action cannot be undone.
          <input type="hidden" name="order_id" id="cancelOrderId">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
          <button type="submit" name="confirm_cancel" class="btn btn-danger">Yes, Cancel Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var cancelModal = document.getElementById("cancelOrderModal");
    cancelModal.addEventListener("show.bs.modal", function (event) {
        var button = event.relatedTarget;
        var orderId = button.getAttribute("data-order-id");
        document.getElementById("cancelOrderId").value = orderId;
    });
});
</script>

<?php 
include 'includes/footer.php'; 
?>