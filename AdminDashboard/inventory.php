<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Get the logged-in user's name
$user_name = $_SESSION['user']['name'];

// Include database connection
require_once 'db.php';

// Initialize variables
$errors = [];
$success = '';

// Fetch products and inventory details
$sql = "SELECT p.id, p.name, p.stock, 
               (SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = p.id) AS initial_stock
        FROM products p";
$result = $conn->query($sql);

// Handle update stock form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $stock = (int)$_POST['stock'];

    if ($stock < 0) {
        $errors[] = "Stock cannot be negative.";
    } else {
        $sql_update = "UPDATE products SET stock = ?, created_at = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $stock, $product_id);
        if ($stmt_update->execute()) {
            $success = "Stock updated successfully.";
        } else {
            $errors[] = "Failed to update stock.";
        }
        $stmt_update->close();
    }
}

// Handle add stock form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $stock_added = (int)$_POST['stock_added'];

    if ($stock_added <= 0) {
        $errors[] = "Stock to add must be greater than zero.";
    } else {
        $conn->begin_transaction();
        try {
            // Update products stock
            $sql_update = "UPDATE products SET stock = stock + ?, created_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $stock_added, $product_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Insert into purchase_items (assuming this is how stock is added)
            $sql_purchase = "INSERT INTO purchase_items (purchase_id, product_id, quantity, price) 
                            VALUES ((SELECT id FROM purchases ORDER BY id DESC LIMIT 1), ?, ?, 0.00)";
            $stmt_purchase = $conn->prepare($sql_purchase);
            $stmt_purchase->bind_param("ii", $product_id, $stock_added);
            $stmt_purchase->execute();
            $stmt_purchase->close();

            $conn->commit();
            $success = "Stock added successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to add stock: " . $e->getMessage();
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
    <title>Shop-Seva - Inventory</title>
    <style>
        .btn button {
            padding: 5px 5px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-update button {
            background-color: #4CAF50;
            color: white;
        }
        .btn-update button:hover {
            background-color: #45a049;
        }
        .btn-add button {
            background-color: #2196F3;
            color: white;
        }
        .btn-add button:hover {
            background-color: #1976D2;
        }
        .low-stock {
            background-color: #ffe6e6;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
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
                <?php echo htmlspecialchars($user_name); ?>
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
                    <h1>Inventory Management</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="admin_dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Inventory</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Manage Inventory</h3>
                    </div>
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
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Total Stock</th>
                                <th>Initial Stock</th>
                                <th>Status</th>
                                <th>Update Stock</th>
                                <th>Add Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $is_low_stock = $row['initial_stock'] && $row['stock'] <= 0.1 * $row['initial_stock'];
                                    $row_class = $is_low_stock ? 'low-stock' : '';
                            ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <img src="img/product.png" alt="Product">
                                            <p><?php echo htmlspecialchars($row['name']); ?></p>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['stock']); ?></td>
                                        <td><?php echo htmlspecialchars($row['initial_stock'] ?: 0); ?></td>
                                        <td><?php echo $is_low_stock ? 'Low Stock Alert' : 'Normal'; ?></td>
                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                <div class="form-group">
                                                    <input type="number" name="stock" value="<?php echo $row['stock']; ?>" required>
                                                    <button type="submit" name="update_stock" class="btn btn-update">Update</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                <div class="form-group">
                                                    <input type="number" name="stock_added" placeholder="Enter stock to add" required>
                                                    <button type="submit" name="add_stock" class="btn btn-add">Add</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="6">No products found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
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