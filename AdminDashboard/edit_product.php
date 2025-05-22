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
$inventory = null;
$errors = [];
$success = '';

if ($product_id > 0) {
    // Fetch product details
    $sql_product = "SELECT * FROM products WHERE product_id = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();
    $result_product->free();
    $stmt_product->close();

    // Fetch inventory details
    $sql_inventory = "SELECT * FROM inventory WHERE product_id = ?";
    $stmt_inventory = $conn->prepare($sql_inventory);
    $stmt_inventory->bind_param("i", $product_id);
    $stmt_inventory->execute();
    $result_inventory = $stmt_inventory->get_result();
    $inventory = $result_inventory->fetch_assoc();
    $result_inventory->free();
    $stmt_inventory->close();

    if (!$product) {
        $errors[] = "Product not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $unit_price = (float)$_POST['unit_price'];
    $total_stock = (int)$_POST['total_stock'];

    // Validation
    if (empty($product_name)) {
        $errors[] = "Product name is required.";
    }
    if ($unit_price <= 0) {
        $errors[] = "Unit price must be greater than zero.";
    }
    if ($total_stock < 0) {
        $errors[] = "Total stock cannot be negative.";
    }

    if (empty($errors)) {
        // Start a transaction
        $conn->begin_transaction();

        try {
            // Update product
            $sql_update_product = "UPDATE products SET product_name = ?, description = ?, unit_price = ? WHERE product_id = ?";
            $stmt_update_product = $conn->prepare($sql_update_product);
            $stmt_update_product->bind_param("ssdi", $product_name, $description, $unit_price, $product_id);
            $stmt_update_product->execute();
            $stmt_update_product->close();

            // Update inventory
            $sql_update_inventory = "UPDATE inventory SET total_stock = ?, last_updated = NOW() WHERE product_id = ?";
            $stmt_update_inventory = $conn->prepare($sql_update_inventory);
            $stmt_update_inventory->bind_param("ii", $total_stock, $product_id);
            $stmt_update_inventory->execute();
            $stmt_update_inventory->close();

            // Commit transaction
            $conn->commit();
            $success = "Product updated successfully.";
            header("Location: products.php?message=Product+updated+successfully");
            exit();

            // Refresh product and inventory data
            $sql_product = "SELECT * FROM products WHERE product_id = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();
            $product = $result_product->fetch_assoc();
            $result_product->free();
            $stmt_product->close();

            $sql_inventory = "SELECT * FROM inventory WHERE product_id = ?";
            $stmt_inventory = $conn->prepare($sql_inventory);
            $stmt_inventory->bind_param("i", $product_id);
            $stmt_inventory->execute();
            $result_inventory = $stmt_inventory->get_result();
            $inventory = $result_inventory->fetch_assoc();
            $result_inventory->free();
            $stmt_inventory->close();
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
        <a href="#" class="brand">
            <i class='bx bxs-smile'></i>
            <span class="text">Shop-Seva</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="admin_dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class='bx bxs-group'></i>
                    <span class="text">Team</span>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">Product</span>
                </a>
            </li>
            <li class="active">
                <a href="inventory.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class='bx bxs-message-dots'></i>
                    <span class="text">Message</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="#">
                    <i class='bx bxs-cog'></i>
                    <span class="text">Settings</span>
                </a>
            </li>
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
                    <input type="search" placeholder="Search...">
                    <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
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
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="unit_price">Unit Price</label>
                            <input type="number" step="0.01" id="unit_price" name="unit_price" value="<?php echo htmlspecialchars($product['unit_price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="total_stock">Total Stock</label>
                            <input type="number" id="total_stock" name="total_stock" value="<?php echo htmlspecialchars($inventory['total_stock']); ?>" required>
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