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

// Fetch products
$sql_products = "SELECT id, name, price, stock FROM products WHERE stock > 0";
$result_products = $conn->query($sql_products);

// Initialize variables
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $sale_items = [];
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
            if ($quantity > $product['stock']) {
                $errors[] = "Insufficient stock for {$product['name']}. Available: {$product['stock']}.";
            } else {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                $sale_items[] = [
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

            // Insert into sales
            $sql_sale = "INSERT INTO sales (user_id, customer_name, customer_phone, total, discount, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt_sale = $conn->prepare($sql_sale);
            $user_id = $_SESSION['user']['id'];
            $stmt_sale->bind_param("issdd", $user_id, $customer_name, $customer_phone, $final_total, $discount);
            $stmt_sale->execute();
            $sale_id = $conn->insert_id;
            $stmt_sale->close();

            // Insert sale items and update stock
            foreach ($sale_items as $item) {
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
            $action = "Generated bill for customer: $customer_name (Sale ID: $sale_id)";
            $sql_log = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("is", $user_id, $action);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            header("Location: generate_bill_pdf.php?sale_id=$sale_id");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to generate bill: " . $e->getMessage();
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
    <title>Shop-Seva - Billing</title>
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
            <li class="active"><a href="billing.php"><i class='bx bxs-receipt'></i><span class="text">Billing</span></a></li>
            <li><a href="billing_history.php"><i class='bx bxs-history'></i><span class="text">Billing History</span></a></li>
            <li><a href="#"><i class='bx bxs-message-dots'></i><span class="text">Message</span></a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="#"><i class='bx bxs-cog'></i><span class="text">Settings</span></a></li>
            <li><a href="../logout.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
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
                    <h1>Billing</h1>
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Billing</a></li>
                    </ul>
                </div>
            </div>
            <div class="form-container">
                <h2>Create Bill</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" id="billing-form">
                    <div id="product-list">
                        <div class="product-row">
                            <select name="products[]" onchange="updateTotal()">
                                <option value="">Select Product</option>
                                <?php while ($product = $result_products->fetch_assoc()): ?>
                                    <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo $product['stock']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="number" name="quantities[]" min="1" placeholder="Quantity" onchange="updateTotal()">
                            <button type="button" class="remove-btn" onclick="removeRow(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="add-product-btn" onclick="addProductRow()">Add Product</button>
                    <div class="form-group">
                        <label for="discount">Discount (%)</label>
                        <input type="number" id="discount" name="discount" min="0" max="5" step="0.1" value="0" onchange="updateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Customer Phone (Optional)</label>
                        <input type="text" id="customer_phone" name="customer_phone">
                    </div>
                    <div class="form-group">
                        <label>Total: ₹<span id="total">0.00</span></label>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="submit-btn">Generate Bill</button>
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