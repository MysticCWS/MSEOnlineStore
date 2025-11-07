<?php
session_start();
include 'dbcon.php';

// Check login
if (!isset($_SESSION['verified_user_id']) || !isset($_SESSION['idTokenString'])) {
    $_SESSION['status'] = "Not Allowed.";
    header("Location: login.php");
    exit();
}

// Select current month/year if not set
if (!isset($_GET['month']) || !isset($_GET['year'])) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    header("Location: ?month=$currentMonth&year=$currentYear");
    exit();
}

$uid = $_SESSION['verified_user_id'];

// Month & Year Selection
date_default_timezone_set('Asia/Kuala_Lumpur');

$selectedYear = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : date('Y');
$selectedMonth = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : date('n');

// Ensure valid range
if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = date('n');
}
if ($selectedYear < 2000 || $selectedYear > intval(date('Y')) + 1) {
    $selectedYear = date('Y');
}

// Safe call with verified values
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);

// Fetch sales data from firebase
$salesData = [];
$totalSales = 0;
$topCustomers = [];
$topProducts = [];

try {
    $userList = $database->getReference('userInfo')->getValue();

    if ($userList) {
        foreach ($userList as $userId => $userData) {
            if (isset($userData['order'])) {
                $userTotal = 0;
                $userOrders = [];
                
                foreach ($userData['order'] as $orderId => $order) {
                    if (isset($order['paid_at']) && isset($order['total_amount'])) {
                        $date = new DateTime($order['paid_at']);
                        $orderMonth = (int)$date->format('n');
                        $orderYear = (int)$date->format('Y');
                        $orderDay = (int)$date->format('j');

                        if ($orderYear === $selectedYear && $orderMonth === $selectedMonth) {
                            $amount = floatval($order['total_amount']);
                            $salesData[$orderDay] = ($salesData[$orderDay] ?? 0) + $amount;
                            $totalSales += $amount;
                            
                            // Count customer stats
                            $userTotal += $amount;
                            $orderItems = [];
                            if (isset($order['items']) && is_array($order['items'])) {
                                foreach ($order['items'] as $itemName => $itemData) {
                                    $orderItems[] = $itemName . " (x" . ($itemData['quantity'] ?? 1) . ")";
                                }
                            }
                            $userOrders[] = [
                                'orderId' => $orderId,
                                'amount' => $amount,
                                'items' => $orderItems
                            ];
                            
                            // Count product stats
                            foreach ($order['items'] as $productName => $itemData) {
                                $qty = isset($itemData['quantity']) ? intval($itemData['quantity']) : 1;
                                $price = isset($itemData['product_price']) ? floatval($itemData['product_price']) : 0;
                                $total = $qty * $price;

                                if (!isset($topProducts[$productName])) {
                                    $topProducts[$productName] = ['quantity' => 0, 'sales' => 0];
                                }
                                $topProducts[$productName]['quantity'] += $qty;
                                $topProducts[$productName]['sales'] += $total;
                            }
                        }
                    }
                }
                
                // Save only if user has orders this month
                if ($userTotal > 0) {
                    $userName = $userData['displayName'] ?? ('User_' . $userId);
                    $topCustomers[$userId] = [
                        'name' => $userName,
                        'totalSpent' => $userTotal,
                        'orders' => $userOrders
                    ];
                }
                
            }
        }
    }
    // Sort top customers by total spent descending
    uasort($topCustomers, fn($a, $b) => $b['totalSpent'] <=> $a['totalSpent']);
    
    // Sort by highest sales
    uasort($topProducts, fn($a, $b) => $b['sales'] <=> $a['sales']);
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error fetching data: " . $e->getMessage() . "</div>";
}

// Export as CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filenameBase = "MSE_OS_Sales_{$selectedYear}_{$selectedMonth}";
    $completeData = array_replace(array_fill(1, $daysInMonth, 0), $salesData);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
    $output = fopen('php://output', 'w');

    // Chart info header
    fputcsv($output, ['MSE Online Store - Sales Analytics']);
    fputcsv($output, ['Month', date("F", mktime(0, 0, 0, $selectedMonth, 1))]);
    fputcsv($output, ['Year', $selectedYear]);
    fputcsv($output, ['Total Sales (RM)', number_format($totalSales, 2)]);
    fputcsv($output, []); // Blank line for spacing

    // Table header
    fputcsv($output, ['Day', 'Sales Tally (RM)', 'Sales (RM)']);

//    // Daily sales data
//    foreach ($completeData as $day => $amount) {
//        fputcsv($output, [$day, number_format($amount, 2)]);
//    }

    // Chart-like sales summary
    foreach ($completeData as $day => $amount) {
        $bars = str_repeat('|', (int)round($amount)); // crude text bars
        fputcsv($output, [$day, $bars, number_format($amount, 2)]);
    }
    
    // Top customers
    fputcsv($output, []);
    fputcsv($output, []);
    fputcsv($output, ['MSE Online Store - Top Customers']);
    fputcsv($output, ['Month', date("F", mktime(0, 0, 0, $selectedMonth, 1))]);
    fputcsv($output, ['Year', $selectedYear]);
    fputcsv($output, []); // Blank line for spacing
    fputcsv($output, ['Customer Name', 'Total Spent (RM)', 'Orders & Items']);

    foreach ($topCustomers as $cust) {
        $itemsText = [];
        foreach ($cust['orders'] as $ord) {
            $itemsText[] = "Order {$ord['orderId']} - RM" . number_format($ord['amount'], 2) . ": " . implode(", ", $ord['items']);
        }
        fputcsv($output, [$cust['name'], number_format($cust['totalSpent'], 2), implode(" | ", $itemsText)]);
    }
    
    // Top products
    fputcsv($output, []);
    fputcsv($output, []);
    fputcsv($output, ['MSE Online Store - Top Products']);
    fputcsv($output, ['Month', date("F", mktime(0, 0, 0, $selectedMonth, 1))]);
    fputcsv($output, ['Year', $selectedYear]);
    fputcsv($output, []); // Blank line
    fputcsv($output, ['Product Name', 'Total Quantity Sold', 'Total Sales (RM)']);

    foreach ($topProducts as $name => $info) {
        fputcsv($output, [$name, $info['quantity'], number_format($info['sales'], 2)]);
    }

    fclose($output);
    exit();
}

include 'includes\header.php'; 
echo ' | Admin Home';
include 'includes\header2.php';
include 'includes\navbar_admin.php';
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
        <h2>Sales Analytic</h2>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4 align-items-center">
        <div class="col-auto">
            <label for="year" class="form-label">Select Year:</label>
            <select class="form-select" name="year" id="year" onchange="this.form.submit()">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                    $selected = ($y == $selectedYear) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-auto">
            <label for="month" class="form-label">Select Month:</label>
            <select class="form-select" name="month" id="month" onchange="this.form.submit()">
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $monthName = date("F", mktime(0, 0, 0, $m, 1));
                    $selected = ($m == $selectedMonth) ? 'selected' : '';
                    echo "<option value='$m' $selected>$monthName</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="col-auto">
            <br/>
            <a href="?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="btn btn-outline-primary">
                Go to Current Month
            </a>
        </div>
    </form>
    
    <div>
        <!-- Download CSV Button -->
        <a href="?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&export=csv" 
           class="btn btn-outline-secondary btn-sm mt-2">Download Sales Analytics CSV
        </a>
    </div>
    <br/>

    <div class="card p-4">
        <h5 class="mb-3">Sales for <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?></h5>
        <canvas id="salesChart"></canvas>

        <hr>
        <h6 class="mt-3">Total Sales: RM <?php echo number_format($totalSales, 2); ?></h6>
    </div>
    
    <br/>
    
    <!-- Top Customers -->
    <div class="card p-4">
        <h5>Top Customers (<?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>)</h5>
        <?php if (!empty($topCustomers)): ?>
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th>Total Spent (RM)</th>
                        <th>Orders & Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($topCustomers as $cust): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($cust['name']); ?></td>
                            <td><?= number_format($cust['totalSpent'], 2); ?></td>
                            <td>
                                <?php foreach ($cust['orders'] as $ord): ?>
                                    <strong><?= htmlspecialchars($ord['orderId']); ?></strong> - RM<?= number_format($ord['amount'], 2); ?><br>
                                    <?= implode(", ", array_map('htmlspecialchars', $ord['items'])); ?><br><br>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No customer data for this month.</p>
        <?php endif; ?>
    </div>
    <br/>
    
    <!-- Top Products -->
    <div class="card p-4">
        <h5>Top Products (<?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>)</h5>
        <?php if (!empty($topProducts)): ?>
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Total Quantity Sold</th>
                        <th>Total Sales (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($topProducts as $name => $info): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($name); ?></td>
                            <td><?= intval($info['quantity']); ?></td>
                            <td><?= number_format($info['sales'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted mt-3">No product sales data for this month.</p>
        <?php endif; ?>
    </div>
    <br/>
    <br/>
    
    <!-- Carousel Management -->
    <div class="title">
        <h2>Homepage Carousel Management</h2>
    </div>
    
    <div class="card p-4">
        <h5 class="mb-3">Manage promotional banner images displayed to customers on the homepage carousel.</h4>
        
        <?php
        $carouselRef = 'carousel';

        // Handle Image Upload
        if (isset($_POST['upload_image']) && isset($_FILES['carousel_image'])) {
            $fileTmpPath = $_FILES['carousel_image']['tmp_name'];
            $fileName = $_FILES['carousel_image']['name'];
            $newFileName = 'carousel_' . time();

            try {
                $object = $defaultBucket->upload(
                    file_get_contents($fileTmpPath),
                    [
                        'name' => "carousel/$newFileName.png",
                    ]
                );

                $imageUrl = "https://firebasestorage.googleapis.com/v0/b/mseonlinestore-44d53.firebasestorage.app/o/carousel%2F$newFileName.png?alt=media";

                $carouselData = $database->getReference($carouselRef)->getValue();
                if (!is_array($carouselData)) $carouselData = [];

                $numericKeys = array_filter(array_keys($carouselData ?? []), 'is_numeric');
                $nextKey = empty($numericKeys) ? 0 : max($numericKeys) + 1;
                
                $carouselData = [
                    'imageUrl'=> $imageUrl
                ];

                $database->getReference("carousel/$nextKey")->set($carouselData);

                echo "<div class='alert alert-success'>Image uploaded successfully!</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Upload failed: " . $e->getMessage() . "</div>";
            }
        }

        // Handle Delete Image
        if (isset($_POST['delete_image'])) {
            $imgKey = $_POST['image_key'];

            try {
                $carouselRefKey = "carousel/$imgKey";
                $imgData = $database->getReference($carouselRefKey)->getValue();

                if ($imgData && isset($imgData['imageUrl'])) {
                    $imgUrl = $imgData['imageUrl'];

                    // Extract filename safely
                    $path = parse_url($imgUrl, PHP_URL_PATH); 
                    $basename = basename($path); 
                    $filename = urldecode($basename); // decode if %2F present

                    // Delete from Firebase Storage
                    $defaultBucket->object($filename)->delete();
                }

                // Remove from Realtime Database
                $database->getReference($carouselRefKey)->remove();

                echo "<div class='alert alert-success'>Image deleted successfully!</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Delete failed: " . $e->getMessage() . "</div>";
            }
        }

        // Fetch updated carousel images after upload/delete
        $carouselImages = $database->getReference($carouselRef)->getValue();
        ?>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="carousel_image" class="form-label"><strong>Upload New Promotion Image:</strong></label>
                <input type="file" name="carousel_image" id="carousel_image" class="form-control" accept="image/*" required>
            </div>
            <button type="submit" name="upload_image" class="btn btn-outline-primary">Upload Image</button>
        </form>

        <hr>

        <h5>Current Carousel Images</h5>
        <?php if (!empty($carouselImages)): ?>
            <div class="row">
                <?php foreach ($carouselImages as $imgKey => $imgData): ?>
                    <?php if (empty($imgData) || !isset($imgData['imageUrl'])) continue; ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <img src="<?= htmlspecialchars($imgData['imageUrl']); ?>" 
                                 class="card-img-top" style="height: 180px; object-fit: cover;">
                            <div class="card-body text-center">
                                <form method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this image?');">
                                    <input type="hidden" name="image_key" value="<?= htmlspecialchars($imgKey); ?>">
                                    <button type="submit" name="delete_image" 
                                            class="btn btn-sm btn-outline-danger">
                                        Delete Image
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No images currently in the carousel.</p>
        <?php endif; ?>
    </div>
    <br/>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = Array.from({length: <?php echo $daysInMonth; ?>}, (_, i) => i + 1);
    const data = <?php echo json_encode(array_values(array_replace(array_fill(1, $daysInMonth, 0), $salesData))); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (RM)',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Days',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales (RM)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Daily Sales Overview',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true
                }
            }
        }
    });
    
    // Table Sorting for All Tables
    document.addEventListener('DOMContentLoaded', () => {
        const tables = document.querySelectorAll('table.table');

        tables.forEach(table => {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const asc = header.classList.toggle('asc');
                    header.classList.toggle('desc', !asc);

                    rows.sort((a, b) => {
                        const aText = a.children[index].innerText.trim();
                        const bText = b.children[index].innerText.trim();
                        const aNum = parseFloat(aText.replace(/[^\d.-]/g, ''));
                        const bNum = parseFloat(bText.replace(/[^\d.-]/g, ''));
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return asc ? aNum - bNum : bNum - aNum;
                        }
                        return asc
                            ? aText.localeCompare(bText)
                            : bText.localeCompare(aText);
                    });

                    tbody.innerHTML = '';
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
    });
</script>

<br>
<?php
include 'includes\footer.php';
?>