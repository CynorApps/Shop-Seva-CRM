<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Get the logged-in user's name and ID
$user_name = $_SESSION['user']['name'];
$user_id = $_SESSION['user']['id'];

// Include database connection
require_once 'db.php';

// Initialize variables
$errors = [];
$success = '';
$sale_id = $_GET['sale_id'] ?? null;

// Fetch bill details
if (!$sale_id) {
    header("Location: billing_history.php");
    exit();
}

// Fetch sale details
$sql_sale = "SELECT s.*, u.name AS worker_name 
             FROM sales s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.id = ?";
$stmt_sale = $conn->prepare($sql_sale);
$stmt_sale->bind_param("i", $sale_id);
$stmt_sale->execute();
$sale = $stmt_sale->get_result()->fetch_assoc();
$stmt_sale->close();

if (!$sale) {
    header("Location: billing_history.php");
    exit();
}

// Fetch sale items
$sql_sale_items = "SELECT si.*, p.name AS product_name, p.price AS current_price 
                   FROM sale_items si 
                   JOIN products p ON si.product_id = p.id 
                   WHERE si.sale_id = ?";
$stmt_sale_items = $conn->prepare($sql_sale_items);
$stmt_sale_items->bind_param("i", $sale_id);
$stmt_sale_items->execute();
$sale_items = $stmt_sale_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_sale_items->close();

// Fetch all products for selection
$sql_products = "SELECT id, name, price, stock FROM products WHERE stock > 0";
$result_products = $conn->query($sql_products);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bill'])) {
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $discount = (float)($_POST['discount'] ?? 0);
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);

    // Validation
    if (empty($customer_name)) {
        $errors[] = "Customer name is required.";
    }
    if ($discount < 0 || $discount > 5) {
        $errors[] = "Discount must be between 0% and 5%.";
    }
    if (empty($products)) {
        $errors[] = "At least one product must be selected.";
    }

    // Validate products and quantities
    $total = 0;
    $new_sale_items = [];
    foreach ($products as $index => $product_id) {
        $quantity = (int)($quantities[$index] ?? 0);
        if ($quantity <= 0) {
            $errors[] = "Quantity for product ID $product_id must be greater than zero.";
            continue;
        }

        // Fetch product details
        $sql_product = "SELECT name, price, stock FROM products WHERE id = ?";
        $stmt_product = $conn->prepare($sql_product);
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $product = $stmt_product->get_result()->fetch_assoc();
        $stmt_product->close();

        if ($product) {
            // Check stock (considering old quantities)
            $old_quantity = 0;
            foreach ($sale_items as $item) {
                if ($item['product_id'] == $product_id) {
                    $old_quantity = $item['quantity'];
                    break;
                }
            }
            $stock_change = $old_quantity - $quantity; // Positive means stock will increase, negative means decrease
            $new_stock = $product['stock'] + $stock_change;

            if ($new_stock < 0) {
                $errors[] = "Insufficient stock for {$product['name']}. Available: {$product['stock']}.";
            } else {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                $new_sale_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            }
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Apply discount
            $discount_amount = ($discount / 100) * $total;
            $final_total = $total - $discount_amount;

            // Update sales table
            $sql_update_sale = "UPDATE sales SET customer_name = ?, customer_phone = ?, total = ?, discount = ? WHERE id = ?";
            $stmt_update_sale = $conn->prepare($sql_update_sale);
            $stmt_update_sale->bind_param("ssddi", $customer_name, $customer_phone, $final_total, $discount, $sale_id);
            $stmt_update_sale->execute();
            $stmt_update_sale->close();

            // Revert stock for old items
            foreach ($sale_items as $item) {
                $sql_revert_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
                $stmt_revert_stock = $conn->prepare($sql_revert_stock);
                $stmt_revert_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt_revert_stock->execute();
                $stmt_revert_stock->close();
            }

            // Delete old sale items
            $sql_delete_items = "DELETE FROM sale_items WHERE sale_id = ?";
            $stmt_delete_items = $conn->prepare($sql_delete_items);
            $stmt_delete_items->bind_param("i", $sale_id);
            $stmt_delete_items->execute();
            $stmt_delete_items->close();

            // Insert new sale items and update stock
            foreach ($new_sale_items as $item) {
                // Insert into sale_items
                $sql_sale_item = "INSERT INTO sale_items (sale_id, product_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)";
                $stmt_sale_item = $conn->prepare($sql_sale_item);
                $stmt_sale_item->bind_param("iiid", $sale_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt_sale_item->execute();
                $stmt_sale_item->close();

                // Update product stock
                $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt_update_stock->execute();
                $stmt_update_stock->close();
            }

            // Log activity
            $action = "Edited bill for customer: $customer_name (Sale ID: $sale_id)";
            $sql_log = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $user_id, $action);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            $success = "Bill updated successfully! Sale ID: $sale_id";
            header("Location: billing_history.php?message=" . urlencode($success));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to update bill: " . $e->getMessage();
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
    <title>Shop-Seva - Edit Bill</title>
    <style>
        .form-container {
            max-width: 800px;
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .product-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .product-row select, .product-row input {
            flex: 1;
            min-width: 150px;
        }
        .remove-btn {
            padding: 8px 16px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .remove-btn:hover {
            background-color: #cc0000;
        }
        .add-product-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-product-btn:hover {
            background-color: #45a049;
        }
        .submit-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
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
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
            <li><a href="#"><i class='bx bxs-group'></i><span class="text">Staff</span></a></li>
            <li><a href="products.php"><i class='bx bxs-shopping-bag-alt'></i><span class="text">Product</span></a></li>
            <li><a href="inventory.php"><i class='bx bxs-doughnut-chart'></i><span class="text">Inventory</span></a></li>
            <li><a href="billing.php"><i class='bx bxs-receipt'></i><span class="text">Billing</span></a></li>
            <li class="active"><a href="billing_history.php"><i class='bx bxs-history'></i><span class="text">Billing History</span></a></li>
            <li><a href="#"><i class='bx bxs-message-dots'></i><span class="text">Message</span></a></li>
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
                    <input type="search" placeholder="Search...">
                    <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                </div>
            </form>
            <span class="text"><?php echo htmlspecialchars($user_name); ?></span>
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
                    <h1>Edit Bill</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a href="billing_history.php">Billing History</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Edit Bill</a></li>
                    </ul>
                </div>
            </div>

            <div class="form-container">
                <h2>Edit Bill (Sale ID: <?php echo $sale_id; ?>)</h2>
                <p><strong>Created by Worker:</strong> <?php echo htmlspecialchars($sale['worker_name']); ?></p>
                <p><strong>Created on:</strong> <?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($sale['created_at']))); ?></p>
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
                <form method="POST" id="billing-form">
                    <div id="product-list">
                        <?php foreach ($sale_items as $item): ?>
                            <div class="product-row">
                                <select name="products[]" onchange="updateTotal()">
                                    <option value="">Select Product</option>
                                    <?php
                                    $result_products->data_seek(0);
                                    while ($product = $result_products->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" <?php echo $product['id'] == $item['product_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['stock']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="number" name="quantities[]" min="1" placeholder="Quantity" value="<?php echo $item['quantity']; ?>" onchange="updateTotal()">
                                <button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-product-btn" onclick="addProductRow()">Add Product</button>
                    <div class="form-group">
                        <label for="discount">Discount (%)</label>
                        <input type="number" id="discount" name="discount" min="0" max="5" step="0.1" value="<?php echo $sale['discount']; ?>" onchange="updateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($sale['customer_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Customer Phone (Optional)</label>
                        <input type="text" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($sale['customer_phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Total: ₹<span id="total"><?php echo number_format($sale['total'], 2); ?></span></label>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_bill" class="submit-btn">Update Bill</button>
                    </div>
                </form>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script>
        function addProductRow() {
            const productList = document.getElementById('product-list');
            const row = document.createElement('div');
            row.className = 'product-row';
            row.innerHTML = `
                <select name="products[]" onchange="updateTotal()">
                    <option value="">Select Product</option>
                    <?php
                    $result_products->data_seek(0);
                    while ($product = $result_products->fetch_assoc()):
                    ?>
                        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['stock']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="quantities[]" min="1" placeholder="Quantity" onchange="updateTotal()">
                <button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button>
            `;
            productList.appendChild(row);
            updateTotal();
        }

        function removeRow(button) {
            if (document.querySelectorAll('.product-row').length > 1) {
                button.parentElement.remove();
                updateTotal();
            }
        }

        function updateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                const select = row.querySelector('select');
                const quantity = row.querySelector('input').value || 0;
                const price = select.options[select.selectedIndex]?.dataset.price || 0;
                total += price * quantity;
            });
            const discount = document.getElementById('discount').value || 0;
            const finalTotal = total * (1 - discount / 100);
            document.getElementById('total').textContent = finalTotal.toFixed(2);
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>