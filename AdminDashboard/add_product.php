<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Initialize variables
$errors = [];
$success = '';

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
            // Insert into products table
            $sql_product = "INSERT INTO products (product_name, description, unit_price, created_at) VALUES (?, ?, ?, NOW())";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("ssd", $product_name, $description, $unit_price);
            $stmt_product->execute();
            $product_id = $conn->insert_id;
            $stmt_product->close();

            // Insert into inventory table
            $sql_inventory = "INSERT INTO inventory (product_id, total_stock, last_updated) VALUES (?, ?, NOW())";
            $stmt_inventory = $conn->prepare($sql_inventory);
            $stmt_inventory->bind_param("ii", $product_id, $total_stock);
            $stmt_inventory->execute();
            $stmt_inventory->close();

            // Log initial stock in inventory_history
            $sql_history = "INSERT INTO inventory_history (product_id, stock_added, added_at) VALUES (?, ?, NOW())";
            $stmt_history = $conn->prepare($sql_history);
            $stmt_history->bind_param("ii", $product_id, $total_stock);
            $stmt_history->execute();
            $stmt_history->close();

            // Commit transaction
            $conn->commit();
            $success = "Product added successfully.";
            header("Location: products.php?message=Product+added+successfully");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Failed to add product: " . $e->getMessage();
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
    <title>Shop-Seva - Add Product</title>
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
                    <h1>Add Product</h1>
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
                            <a class="active" href="#">Add Product</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="form-container">
                <h2>Add New Product</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" value="<?php echo isset($product_name) ? htmlspecialchars($product_name) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="unit_price">Unit Price</label>
                        <input type="number" step="0.01" id="unit_price" name="unit_price" value="<?php echo isset($unit_price) ? htmlspecialchars($unit_price) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="total_stock">Total Stock</label>
                        <input type="number" id="total_stock" name="total_stock" value="<?php echo isset($total_stock) ? htmlspecialchars($total_stock) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit">Add Product</button>
                    </div>
                </form>
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