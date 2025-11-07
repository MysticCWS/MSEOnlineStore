<?php
session_start();
include 'dbcon.php';
include 'includes/header.php';
echo ' | Admin Orders';
include 'includes/header2.php';
include 'includes/navbar_admin.php';

// Ensure only admin can access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['status'] = "Access denied. Admins only.";
    header("Location: login.php");
    exit();
}

// Handle status/courier/tracking updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $uid       = $_POST['uid'];
    $orderId   = $_POST['order_id'];
    $status    = $_POST['delivery_status'];
    $courier   = $_POST['courier'];
    $tracking  = $_POST['tracking_number'];

    $updateData = [
        'delivery_status' => $status,
        'courier' => $courier,
        'tracking_number' => $tracking,
    ];

    $orderRef = "userInfo/$uid/order/$orderId";
    $database->getReference($orderRef)->update($updateData);

    $_SESSION['status'] = "Order <b>$orderId</b> updated successfully.";
    header("Location: admin_orders.php");
    exit();
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$validFilters = ['All', 'Pending', 'Shipped Out', 'Completed'];

// Fetch all userInfo
$usersRef = "userInfo";
$users = $database->getReference($usersRef)->getValue();
?>

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
            <h2>Manage Orders (Admin)</h2>

            <!-- Filter Dropdown -->
            <form method="GET" class="d-flex align-items-center">
                <label for="filter" class="me-2 mb-0 text-nowrap">Filter by Status:</label>
                <select name="filter" id="filter" class="form-select form-select-md" onchange="this.form.submit()">
                    <?php foreach ($validFilters as $f): ?>
                        <option value="<?= $f ?>" <?= $filter === $f ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!$users): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <?php
            $hasResults = false;
            foreach ($users as $uid => $user):
                if (!isset($user['order'])) continue;

                foreach ($user['order'] as $order):
                    // Only show payment success orders
                    if (!isset($order['status']) || strtolower($order['status']) !== 'success') continue;
                    
                    $status = $order['delivery_status'] ?? 'Preparing';

                    // Apply filter conditions
                    if ($filter === 'Pending' && !in_array($status, ['Preparing', 'Packing'])) continue;
                    if ($filter === 'Shipped Out' && $status !== 'Shipped Out') continue;
                    if ($filter === 'Completed' && $status !== 'Completed') continue;

                    $hasResults = true;
            ?>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?>
                            <span class="badge bg-secondary float-end">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><strong>User:</strong> <?= htmlspecialchars($user['displayName'] ?? 'Unknown') ?> (<?= $uid ?>)</p>
                            <p><strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></p>
                            <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                            
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

                            <!-- Delivery update form -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="uid" value="<?= $uid ?>">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">

                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>Delivery Status</strong></label>
                                        <select name="delivery_status" class="form-select">
                                            <?php 
                                            $statuses = ['Preparing', 'Packing', 'Shipped Out', 'Completed'];
                                            foreach ($statuses as $s): ?>
                                                <option value="<?= $s ?>" 
                                                    <?= $status === $s ? 'selected' : '' ?>>
                                                    <?= $s ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>Courier</strong></label>
                                        <input type="text" name="courier" class="form-control" 
                                               value="<?= htmlspecialchars($order['courier'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><strong>Tracking Number</strong></label>
                                        <input type="text" name="tracking_number" class="form-control" 
                                               value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
                                    </div>
                                </div>

                                <button type="submit" name="update_order" class="btn btn-sm btn-outline-primary">
                                    Update Order
                                </button>
                            </form>
                        </div>
                    </div>
            <?php
                endforeach;
            endforeach;

            if (!$hasResults) {
                echo "<p class='text-muted'>No orders found for the selected filter.</p>";
            }
            ?>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>