<?php
session_start();
include 'dbcon.php';
include 'includes/header.php'; 
echo ' | Cyberstore';
include 'includes/header2.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

//Get UID & user info
$allowedDomain = '@student.newinti.edu.my';
$uid = null;
$userEmail = null;

if(isset($_SESSION['verified_user_id'])){
    $uid = $_SESSION['verified_user_id'];
    try {
        $user = $auth->getUser($uid);
        $userEmail = $user->email;
    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        echo $e->getMessage();
    }
}

$products_ref_table = 'products';
$products = $database->getReference($products_ref_table)->getValue();

// Restrict access
if (!$uid || !str_ends_with(strtolower($userEmail), strtolower($allowedDomain))) {
    include 'includes/navbar.php';
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>
            Please login with INTI International College Penang email address to access this page.
          </div></div>";
    include 'includes/footer.php';
    exit;
}

// Add to cart function
if(isset($_POST['btnAddToCart'])) {
    $uid = $_POST['user_id'];
    $sku = $_POST['sku'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price']; // here it will be promoprice
    $product_imgurl = $_POST['product_imgurl'];
    $product_category = $_POST['product_category'];
    $product_brand = $_POST['product_brand'];
    $quantity = $_POST['quantity'];
    $remark = $_POST['remark'];

    $cart_item = [
        'sku' => $sku,
        'product_name' => $product_name,
        'product_price' => $product_price,
        'product_imgurl' => $product_imgurl,
        'product_category' => $product_category,
        'product_brand' => $product_brand,
        'quantity' => (int)$quantity,
        'remark' => $remark,
        'added_at' => date('Y-m-d H:i:s'),
    ];

    $cart_ref = "userInfo/$uid/cart/$sku";
    $existing = $database->getReference($cart_ref)->getValue();
    if ($existing) {
        $new_qty = $existing['quantity'] + (int)$quantity;
        $cart_item['quantity'] = $new_qty;
    }

    $updatedCartData = $database->getReference($cart_ref)->update($cart_item);
    if ($updatedCartData){
        $_SESSION['status'] = "Item added to cart!";
        header("Location: cyberstore.php#product_list"); 
        die();
    }
}

include 'includes/navbar.php';
?>

<div class="content">
<!--Show Status-->
    <?php
        if(isset($_SESSION['status'])){
            echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
            unset($_SESSION['status']);
        }
    ?>

    <div class="title">
        <h2>CyberStore</h2>
    </div>

    <!-- Search and Filter -->
    <div class="container mt-3 mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or SKU">
            </div>
            <div class="col-md-4">
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                    <?php
                    if (!empty($products)) {
                        $categories = array_unique(array_column($products, 'product_category'));
                        foreach ($categories as $category) {
                            echo "<option value=\"$category\">$category</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <select id="brandFilter" class="form-select">
                    <option value="">All Brands</option>
                    <?php
                    if (!empty($products)) {
                        $brands = array_unique(array_column($products, 'product_brand'));
                        foreach ($brands as $brand) {
                            echo "<option value=\"$brand\">$brand</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Product List -->
    <div class="container px-4 py-4 border rounded bg-white" id="product_list">
        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4 product-card"
                        data-name="<?php echo strtolower($product['product_name']); ?>"
                        data-sku="<?php echo strtolower($product['sku']); ?>"
                        data-category="<?php echo strtolower($product['product_category']); ?>"
                        data-brand="<?php echo strtolower($product['product_brand']); ?>">

                        <div class="card h-100">
                            <img class="card-img-top" src="<?php echo $product['product_imgurl']; ?>" style="width:100%; height:200px; object-fit:contain;" alt="<?php echo $product['product_name']; ?>">
                            <div class="card-body">
                                <h4 class="card-title"><?php echo $product['product_name']; ?></h4>
                                <h5>RM <?php echo number_format($product['product_promoprice'], 2); ?></h5>
                                <p class="card-text">SKU: <?php echo $product['sku']; ?><br>
                                    Category: <?php echo $product['product_category']; ?><br>
                                    Brand: <?php echo $product['product_brand']; ?></p>
                                <p class="card-text"><?php echo $product['product_description']; ?></p>
                            </div>
                            <div class="card-footer">
                                <!-- Add to Cart -->
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modifyOrderModal-<?php echo $product['sku']; ?>">
                                    Add to Cart
                                </button>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="modifyOrderModal-<?php echo $product['sku']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Modify Order: <?php echo $product['product_name']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                                <input type="hidden" name="sku" value="<?php echo $product['sku']; ?>">
                                                <input type="hidden" name="product_name" value="<?php echo $product['product_name']; ?>">
                                                <input type="hidden" name="product_imgurl" value="<?php echo $product['product_imgurl']; ?>">
                                                <input type="hidden" name="product_price" value="<?php echo $product['product_promoprice']; ?>">
                                                <input type="hidden" name="product_category" value="<?php echo $product['product_category']; ?>">
                                                <input type="hidden" name="product_brand" value="<?php echo $product['product_brand']; ?>">

                                                <p>SKU: <?php echo $product['sku']; ?></p>
                                                <p>Price per Unit: RM <?php echo number_format($product['product_promoprice'], 2); ?></p>
                                                <p>Stock Balance: <?php echo $product['stockbalance']; ?></p>

                                                <div class="mb-3">
                                                    <label for="quantity-<?php echo $product['sku']; ?>" class="form-label">Quantity</label>
                                                    <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stockbalance']; ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Order Remarks (Optional)</label>
                                                    <input type="text" class="form-control" name="remark" value="">
                                                </div>

                                                <button type="submit" class="btn btn-outline-secondary" name="btnAddToCart">Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Modal -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h5>No products found.</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JS for search and filtering -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const brandFilter = document.getElementById('brandFilter');
    const productCards = document.querySelectorAll('.product-card');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        const selectedBrand = brandFilter.value.toLowerCase();

        productCards.forEach(card => {
            const name = card.dataset.name;
            const sku = card.dataset.sku;
            const category = card.dataset.category;
            const brand = card.dataset.brand;

            const matchesSearch = name.includes(searchTerm) || sku.includes(searchTerm);
            const matchesCategory = !selectedCategory || category === selectedCategory;
            const matchesBrand = !selectedBrand || brand === selectedBrand;

            card.style.display = (matchesSearch && matchesCategory && matchesBrand) ? 'block' : 'none';
        });
    }

    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    brandFilter.addEventListener('change', filterProducts);
});
</script>

<?php
include 'includes/footer.php';
?>