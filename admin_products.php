<?php
session_start();
include 'dbcon.php';
include 'includes\header.php'; 
echo ' | Manage Products';
include 'includes\header2.php';
include 'includes\navbar_admin.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

//Get UID
if(isset($_SESSION['verified_user_id'])){
    $uid = $_SESSION['verified_user_id'];
    try {
        $user = $auth->getUser($uid);
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        echo $e->getMessage();
    }
}

$products_ref_table = 'products';
$products = $database->getReference($products_ref_table)->getValue();

$productToEdit = null;
$enteredSKU = null;

// Admin entered SKU to check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkSKU'])) {
    $enteredSKU = trim($_POST['sku']);
    if (!empty($products)) {
        foreach ($products as $key => $prod) {
            if (isset($prod['sku']) && $prod['sku'] === $enteredSKU) {
                $productToEdit = $prod;
                $productToEdit['firebase_key'] = $key; // store key for updating later
                break;
            }
        }
    }
} else {
    // empty SKU = no form shown
    $enteredSKU = null;
}

// Admin submitted full product form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnSaveProduct'])) {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['product_name']);
    $price = floatval($_POST['product_price']);
    $promoprice = floatval($_POST['product_promoprice']);
    $description = trim($_POST['product_description']);
    $category = trim($_POST['product_category']);
    $brand = trim($_POST['product_brand']);
    $stock = intval($_POST['stockbalance']);
    
    if (isset($_FILES['myfile']['name']) && $_FILES['myfile']['error'] == UPLOAD_ERR_OK){
        // Firebase image URL formatting
        $product_imgurlprefix = "https://firebasestorage.googleapis.com/v0/b/mseonlinestore-44d53.firebasestorage.app/o/products%2F";
        $product_imgurlsuffix = "?alt=media";
        $product_imgurl = $product_imgurlprefix . urlencode($sku) . ".png" . $product_imgurlsuffix;

        // Upload to Firebase Storage
        $defaultBucket->upload(
            file_get_contents($_FILES['myfile']['tmp_name']),
            [
                'name' => "products/$sku.png"
            ]
        );
        
        $imgurl = $product_imgurl;
    } else {
        // Use existing URL or leave empty if not updating
        $imgurl = $_POST['product_imgurl'] ?? '';
    }

    $productData = [
        'sku' => $sku,
        'product_name' => $name,
        'product_price' => $price,
        'product_promoprice' => $promoprice,
        'product_description' => $description,
        'product_category' => $category,
        'product_brand' => $brand,
        'product_imgurl' => $imgurl,
        'stockbalance' => $stock
    ];

    // Check if exists
    $exists = false;
    $existingKey = null;
    if (!empty($products)) {
        foreach ($products as $key => $prod) {
            if (isset($prod['sku']) && $prod['sku'] === $sku) {
                $exists = true;
                $existingKey = $key;
                break;
            }
        }
    }

    if ($exists) {
        $database->getReference($products_ref_table . '/' . $sku)->update($productData);
        $_SESSION['status'] = "Product with SKU $sku updated successfully.";
    } else {
        $database->getReference($products_ref_table . '/' . $sku)->update($productData);
        $_SESSION['status'] = "New product with SKU $sku added successfully.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnDeleteProduct'])) {
    $deleteSKU = $_POST['delete_sku'] ?? '';
    if (!empty($products)) {
        foreach ($products as $key => $prod) {
            if ($prod['sku'] === $deleteSKU) {
                if ($prod['imgdir']){
                    try {
                        // Remove image from Firebase Storage
                        $imagePath = "products/{$deleteSKU}.png";

                        // Attempt to delete the image from Firebase Storage
                        $defaultBucket->object($imagePath)->delete();
                    } catch (StorageException $e) {
                        // Optional: log this error or show a warning
                        error_log("Image deletion failed: " . $e->getMessage());
                    }

                    $database->getReference('products/' . $key)->remove();
                    $_SESSION['status'] = "Product with SKU $deleteSKU and its image deleted successfully.";
                    break;
                } else {
                    $database->getReference('products/' . $key)->remove();
                    $_SESSION['status'] = "Product with SKU $deleteSKU deleted successfully.";
                    break;
                }
                
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

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
        <h2>Our Products</h2>
    </div>

    <!-- Check SKU -->
    <div class="container mb-4">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="sku" class="form-control" placeholder="Enter SKU to add or edit" value="<?php echo htmlspecialchars($enteredSKU ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <button type="submit" name="checkSKU" class="btn btn-outline-primary">Check SKU</button>
            </div>
        </form>
    </div>

    <!-- If SKU entered, show full form -->
    <?php if (!empty($enteredSKU)): ?>
        <div class="container mb-5">
            <h5><?php echo ($productToEdit) ? 'Update Product' : 'Add New Product'; ?> for SKU: <?php echo htmlspecialchars($enteredSKU); ?></h5>
            <form method="POST" class="row g-3" enctype="multipart/form-data">
                <input type="hidden" name="sku" value="<?php echo htmlspecialchars($enteredSKU); ?>">
                <div class="col-md-6 form-floating">
                    <input type="text" name="product_name" class="form-control" placeholder="Product Name" required value="<?php echo $productToEdit['product_name'] ?? ''; ?>">
                    <label for="product_name">Product Name</label>
                </div>
                <div class="col-md-3 form-floating">
                    <input type="number" step="0.01" name="product_price" class="form-control" placeholder="Price" required value="<?php echo $productToEdit['product_price'] ?? ''; ?>">
                    <label for="product_price">Product Price</label>
                </div>
                <div class="col-md-3 form-floating">
                    <input type="number" step="0.01" name="product_promoprice" class="form-control" placeholder="Promo Price" required value="<?php echo $productToEdit['product_promoprice'] ?? ''; ?>">
                    <label for="product_promoprice">Product Promo Price</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="text" name="product_category" class="form-control" placeholder="Category" value="<?php echo $productToEdit['product_category'] ?? ''; ?>">
                    <label for="product_category">Product Category</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="text" name="product_brand" class="form-control" placeholder="Brand" value="<?php echo $productToEdit['product_brand'] ?? ''; ?>">
                    <label for="product_brand">Product Brand</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="file" class="form-control" id="file-input" accept="image/png, image/jpeg" name="myfile" onchange="previewImage(event)">
                    <input type="hidden" name="product_imgurl" value="<?php echo $productToEdit['product_imgurl'] ?? ''; ?>">
                    <label for="product_imgurl">Product Image</label>
                </div>
                <div class="col-md-4 form-floating">
                    <input type="number" name="stockbalance" class="form-control" placeholder="Stock Balance" value="<?php echo $productToEdit['stockbalance'] ?? '0'; ?>">
                    <label for="stockbalance">Stock Balance</label>
                </div>
                <div class="col-12 row-3 form-floating">
                    <textarea name="product_description" class="form-control" rows="3" placeholder="Description" style="height: auto;"><?php echo $productToEdit['product_description'] ?? ''; ?></textarea>
                    <label for="product_description">Product Description</label>
                </div>
                <div class="col-12">
                    <button type="submit" name="btnSaveProduct" class="btn btn-success">Save Product</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
        

    <!-- Search and Filter by Category and Brand -->
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
                                <h5>RM <?php echo number_format($product['product_price'], 2); ?> (CS: RM <?php echo number_format($product['product_promoprice'], 2); ?>)</h5>
                                <p class="card-text">SKU: <?php echo $product['sku']; ?><br>
                                    Category: <?php echo $product['product_category']; ?><br>
                                    Brand: <?php echo $product['product_brand']; ?>
                                    Stock Balance: <?php echo $product['stockbalance']; ?></p>
                                <p class="card-text"><?php echo $product['product_description']; ?></p>
                            </div>
                            <div class="card-footer">
                                <!-- Edit Button -->
                                <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $product['sku']; ?>">
                                    Edit
                                </button>

                                <!-- Remove Button -->
                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-<?php echo $product['sku']; ?>">
                                    Remove
                                </button>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal-<?php echo $product['sku']; ?>" tabindex="-1" aria-labelledby="editModalLabel-<?php echo $product['sku']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel-<?php echo $product['sku']; ?>">Edit Product: <?php echo $product['product_name']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body row g-3">
                                                <input type="hidden" name="sku" value="<?php echo $product['sku']; ?>">
                                                <div class="col-md-6 form-floating">
                                                    <input type="text" name="product_name" class="form-control" placeholder="Product Name" required value="<?php echo $product['product_name']; ?>">
                                                    <label for="product_name">Product Name</label>
                                                </div>
                                                <div class="col-md-3 form-floating">
                                                    <input type="number" step="0.01" name="product_price" class="form-control" placeholder="Price" required value="<?php echo $product['product_price']; ?>">
                                                    <label for="product_price">Product Price</label>
                                                </div>
                                                <div class="col-md-3 form-floating">
                                                    <input type="number" step="0.01" name="product_promoprice" class="form-control" placeholder="Promo Price" required value="<?php echo $product['product_promoprice']; ?>">
                                                    <label for="product_promoprice">Product Promo Price</label>
                                                </div>
                                                <div class="col-md-4 form-floating">
                                                    <input type="text" name="product_category" class="form-control" placeholder="Category" value="<?php echo $product['product_category']; ?>">
                                                    <label for="product_category">Product Category</label>
                                                </div>
                                                <div class="col-md-4 form-floating">
                                                    <input type="text" name="product_brand" class="form-control" placeholder="Brand" value="<?php echo $product['product_brand']; ?>">
                                                    <label for="product_Brand">Product Brand</label>
                                                </div>
                                                <div class="col-md-4 form-floating">
                                                    <input type="file" class="form-control" accept="image/png, image/jpeg" name="myfile">
                                                    <input type="hidden" name="product_imgurl" value="<?php echo $product['product_imgurl']; ?>">
                                                    <label for="product_imgurl">Product Image</label>
                                                </div>
                                                <div class="col-md-4 form-floating">
                                                    <input type="number" name="stockbalance" class="form-control" placeholder="Stock Balance" value="<?php echo $product['stockbalance']; ?>">
                                                    <label for="stockbalance">Stock Balance</label>
                                                </div>
                                                <div class="col-12 form-floating">
                                                    <textarea name="product_description" class="form-control" rows="3" placeholder="Description" style="height: auto;"><?php echo $product['product_description']; ?></textarea>
                                                    <label for="product_description">Product Description</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="btnSaveProduct" class="btn btn-success">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End edit modal -->
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal-<?php echo $product['sku']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel-<?php echo $product['sku']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel-<?php echo $product['sku']; ?>">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete <strong><?php echo $product['product_name']; ?></strong> (SKU: <?php echo $product['sku']; ?>)?
                                                <input type="hidden" name="delete_sku" value="<?php echo $product['sku']; ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="btnDeleteProduct" class="btn btn-danger">Yes, Delete</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End delete modal -->
                            
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

<!-- JavaScript for Search & Filtering -->
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

            if (matchesSearch && matchesCategory && matchesBrand) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    brandFilter.addEventListener('change', filterProducts);
});
</script>

<?php
include 'includes\footer.php';
?>