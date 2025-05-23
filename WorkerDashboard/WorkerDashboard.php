<?php
session_start();

// Check if user is logged in and is a Worker
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Worker') {
    header("Location: ../index.php");
    exit();
}

// Get the logged-in user's name and ID
$user_name = $_SESSION['user']['name'];
$user_id = $_SESSION['user']['id'];

// Include database connection
require_once '../AdminDashboard/db.php';

// Initialize variables
$errors = [];
$success = '';
$search_customer = $_GET['search_customer'] ?? '';
$search_product = $_GET['search_product'] ?? '';
$active_tab = $_GET['tab'] ?? 'billing'; // Default to billing tab

// Billing Logic (from billing.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bill'])) {
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
            // Redirect to PDF generation
            header("Location: ../AdminDashboard/generate_bill_pdf.php?sale_id=$sale_id");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to generate bill: " . $e->getMessage();
        }
    }
}

// Fetch products for billing form
$sql_products = "SELECT id, name, price, stock FROM products WHERE stock > 0";
$result_products = $conn->query($sql_products);

// Billing History Logic (from billing_history.php)
$sql_bills = "SELECT s.id, s.customer_name, s.total, s.created_at, 
                     GROUP_CONCAT(p.name SEPARATOR ', ') AS product_names
              FROM sales s
              JOIN sale_items si ON s.id = si.sale_id
              JOIN products p ON si.product_id = p.id
              WHERE s.user_id = ?"; // Filter by worker's user_id
$params = [$user_id];
$types = 'i';

if (!empty($search_customer)) {
    $sql_bills .= " AND s.customer_name LIKE ?";
    $params[] = "%$search_customer%";
    $types .= 's';
}
if (!empty($search_product)) {
    $sql_bills .= " AND p.name LIKE ?";
    $params[] = "%$search_product%";
    $types .= 's';
}

$sql_bills .= " GROUP BY s.id ORDER BY s.created_at DESC";

$stmt_bills = $conn->prepare($sql_bills);
if ($stmt_bills === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt_bills->bind_param($types, ...$params);
if (!$stmt_bills->execute()) {
    die("Execute failed: " . $stmt_bills->error);
}
$result_bills = $stmt_bills->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../AdminDashboard/style.css">
    <title>Shop-Seva - Worker Dashboard</title>
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tabs a {
            padding: 10px 20px;
            background-color: #f1f1f1;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .tabs a.active {
            background-color: #4CAF50;
            color: white;
        }
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
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-form input {
            flex: 1;
            min-width: 200px;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-form button:hover {
            background-color: #45a049;
        }
        .table-container {
            overflow-x: auto;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 4px;
        }
        .table-container table th,
        .table-container table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table-container table th {
            background: #f1f1f1;
            font-weight: bold;
        }
        .table-container table tbody tr:hover {
            background: #f5f5f5;
        }
        .table-container table td a {
            color: #4CAF50;
            text-decoration: none;
        }
        .table-container table td a:hover {
            color: #45a049;
            text-decoration: underline;
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
            <li class="<?php echo $active_tab === 'billing' ? 'active' : ''; ?>">
                <a href="?tab=billing"><i class='bx bxs-receipt'></i><span class="text">Billing</span></a>
            </li>
            <li class="<?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                <a href="?tab=history"><i class='bx bxs-history'></i><span class="text">Billing History</span></a>
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
                    <input type="search" placeholder="Search...">
                    <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
                </div>
            </form>
            <span class="text"><?php echo htmlspecialchars($user_name); ?></span>
            <input type="checkbox" id="switch-mode" hidden>
            <label for="switch-mode" class="switch-mode"></label>
            <a href="#" class="profile">
                <img src="../AdminDashboard/img/profile.png">
            </a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Worker Dashboard</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#"><?php echo $active_tab === 'billing' ? 'Billing' : 'Billing History'; ?></a></li>
                    </ul>
                </div>
            </div>

            <div class="tabs">
                <a href="?tab=billing" class="<?php echo $active_tab === 'billing' ? 'active' : ''; ?>">Billing</a>
                <a href="?tab=history" class="<?php echo $active_tab === 'history' ? 'active' : ''; ?>">Billing History</a>
            </div>

            <?php if ($active_tab === 'billing'): ?>
                <!-- Billing Section -->
                <div class="form-container">
                    <h2>Create Bill</h2>
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
                            <button type="submit" name="generate_bill" class="submit-btn">Generate Bill</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Billing History Section -->
                <div class="form-container">
                    <h2>Bill Records</h2>
                    <?php if (isset($_GET['message'])): ?>
                        <div class="success">
                            <p><?php echo htmlspecialchars($_GET['message']); ?></p>
                        </div>
                    <?php endif; ?>
                    <form method="GET" class="search-form">
                        <input type="hidden" name="tab" value="history">
                        <div class="form-group">
                            <input type="text" name="search_customer" placeholder="Search by customer name" value="<?php echo htmlspecialchars($search_customer); ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="search_product" placeholder="Search by product name" value="<?php echo htmlspecialchars($search_product); ?>">
                        </div>
                        <button type="submit">Search</button>
                    </form>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer Name</th>
                                    <th>Products</th>
                                    <th>Total (₹)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_bills->num_rows > 0): ?>
                                    <?php while ($bill = $result_bills->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($bill['created_at']))); ?></td>
                                            <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bill['product_names']); ?></td>
                                            <td><?php echo number_format($bill['total'], 2); ?></td>
                                            <td>
                                                <a href="../AdminDashboard/generate_bill_pdf.php?sale_id=<?php echo $bill['id']; ?>">View PDF</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No bills found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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
$stmt_bills->close();
$conn->close();
?>