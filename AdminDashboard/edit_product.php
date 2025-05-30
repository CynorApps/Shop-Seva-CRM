<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$product = null;
$errors = [];
$success = '';

if ($product_id > 0) {
    // Fetch product details
    $sql_product = "SELECT * FROM products WHERE id = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();
    $stmt_product->close();

    if (!$product) {
        $errors[] = "Product not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $sku = trim($_POST['sku']);
    $tax_rate = (float)$_POST['tax_rate'];

    // Validation
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($sku)) {
        $errors[] = "SKU is required.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero.";
    }
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update product
            $sql_update_product = "UPDATE products SET name = ?, sku = ?, price = ?, stock = ?, tax_rate = ?, description = ? WHERE id = ?";
            $stmt_update_product = $conn->prepare($sql_update_product);
            $stmt_update_product->bind_param("ssdidsi", $name, $sku, $price, $stock, $tax_rate, $description, $product_id);
            $stmt_update_product->execute();
            $stmt_update_product->close();

            // Log activity
            $user_id = $_SESSION['user']['id'];
            $action = "Updated product: $name";
            $sql_log = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $user_id, $action);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            $success = "Product updated successfully.";
            header("Location: products.php?message=Product+updated+successfully");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to update product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Edit Product</title>
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .form-container h2 {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="https://cynortech.in/" class="brand" style="margin-top: 11px; margin-left: 11px; pading:8px; " >
            <img src="./img/crm.png" alt="CYNOR Logo" style="height: 30px; width: auto; margin-right: 19px; margin-left: 11px; ">
            <span class="text" style="font-size: 18px; font-weight: bold;">
                Shop-Seva<br>
                <span style="font-size: 10px; color: gray; font-weight: normal;">Powered by CYNOR</span>
            </span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="admin_dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>

             <li>
                <a href="staff.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Staff</span>
                </a>
            </li>

            <li class="active">
                <a href="products.php">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">Product</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Inventory</span>
                </a>
            </li>

            <li>
                <a href="billing.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">Billing</span>
                </a>
            </li>
            <li>
                <a href="billing_history.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">Billing History</span>
                </a>
            </li>

            <li>
                <a href="performance.php">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span class="text">Performance</span>
                </a>
            </li>
            <li>
                <a href="backup.php">
                    <i class='bx bxs-data'></i>
                    <span class="text">Backup</span>
                </a>
            </li>



            <li>
                <a href="contact.php">
                    <i class='bx bxs-message-dots'></i>
                    <span class="text">Contact US</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            
            <li>
                <a href="../logout.php" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <form action="#">
                <div class="form-input">
                <button type="submit" class="search-btn">
                    <i class='bx bx-briefcase'></i>
                </button>
            </div>
            </form>
            <span class="text">
                <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
            </span>
            <input type="checkbox" id="switch-mode" hidden>
            <label for="switch-mode" class="switch-mode"></label>
            <a href="#" class="profile">
                <img src="img/profile.png">
            </a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Edit Product</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="admin_dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a href="products.php">Products</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Edit Product</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="form-container">
                <h2>Edit Product</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($product): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sku">SKU</label>
                            <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="tax_rate">Tax Rate (%)</label>
                            <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="<?php echo htmlspecialchars($product['tax_rate']); ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit">Update Product</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p>Product not found.</p>
                <?php endif; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="script.js"></script>
</body>
</html>
<?php
$conn->close();
?>