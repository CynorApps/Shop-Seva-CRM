<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Get the logged-in user's name
$user_name = $_SESSION['user']['name'];

// Include database connection
require_once 'db.php';

// Fetch today's profit
$sql_today_profit = "SELECT SUM(total) as today_profit FROM sales WHERE DATE(created_at) = CURDATE()";
$result_today_profit = $conn->query($sql_today_profit);
$today_profit = $result_today_profit->fetch_assoc()['today_profit'] ?? 0;

// Fetch today's sales (total products sold)
$sql_today_sales = "SELECT SUM(si.quantity) as today_sales 
                    FROM sale_items si 
                    JOIN sales s ON si.sale_id = s.id 
                    WHERE DATE(s.created_at) = CURDATE()";
$result_today_sales = $conn->query($sql_today_sales);
$today_sales = $result_today_sales->fetch_assoc()['today_sales'] ?? 0;

// Fetch today's customers (distinct customer count)
$sql_today_customers = "SELECT COUNT(DISTINCT customer_name) as today_customers 
                        FROM sales 
                        WHERE DATE(created_at) = CURDATE()";
$result_today_customers = $conn->query($sql_today_customers);
$today_customers = $result_today_customers->fetch_assoc()['today_customers'] ?? 0;

// Fetch this month's profit
$sql_month_profit = "SELECT SUM(total) as month_profit 
                     FROM sales 
                     WHERE MONTH(created_at) = MONTH(CURDATE()) 
                     AND YEAR(created_at) = YEAR(CURDATE())";
$result_month_profit = $conn->query($sql_month_profit);
$month_profit = $result_month_profit->fetch_assoc()['month_profit'] ?? 0;

// Fetch this month's sales (total products sold)
$sql_month_sales = "SELECT SUM(si.quantity) as month_sales 
                    FROM sale_items si 
                    JOIN sales s ON si.sale_id = s.id 
                    WHERE MONTH(s.created_at) = MONTH(CURDATE()) 
                    AND YEAR(s.created_at) = YEAR(CURDATE())";
$result_month_sales = $conn->query($sql_month_sales);
$month_sales = $result_month_sales->fetch_assoc()['month_sales'] ?? 0;

// Fetch this month's customers (distinct customer count)
$sql_month_customers = "SELECT COUNT(DISTINCT customer_name) as month_customers 
                        FROM sales 
                        WHERE MONTH(created_at) = MONTH(CURDATE()) 
                        AND YEAR(created_at) = YEAR(CURDATE())";
$result_month_customers = $conn->query($sql_month_customers);
$month_customers = $result_month_customers->fetch_assoc()['month_customers'] ?? 0;

// Fetch recent bills (latest 5)
$sql_recent_bills = "SELECT customer_name, total 
                     FROM sales 
                     ORDER BY created_at DESC LIMIT 5";
$result_recent_bills = $conn->query($sql_recent_bills);

// Fetch top selling products (top 5 by quantity sold)
$sql_top_products = "SELECT p.id, p.name, p.stock, SUM(si.quantity) as total_sold 
                     FROM sale_items si 
                     JOIN products p ON si.product_id = p.id 
                     GROUP BY p.id, p.name, p.stock 
                     ORDER BY total_sold DESC LIMIT 5";
$result_top_products = $conn->query($sql_top_products);

// Fetch low stock products
$sql_low_stock = "SELECT p.name, p.stock, 
                  (SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = p.id) AS initial_stock
                  FROM products p
                  WHERE p.stock <= 0.1 * (SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = p.id)";
$result_low_stock = $conn->query($sql_low_stock);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva</title>
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
            <li class="active">
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

            <li>
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
                <a href="logs.php">
                    <i class='bx bxs-time'></i>
                    <span class="text">Logs</span>
                </a>
            </li>
            <li>
                <a href="contact.php">
                    <i class='bx bxs-message-dots'></i>
                    <span class="text">Contact US</span>
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
                <button type="submit" class="search-btn">
                    <i class='bx bx-briefcase'></i>
                </button>
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
                    <h1>Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Home</a>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="box-info">
                <li>
                    <i class='bx bx-rupee'></i>
                    <span class="text">
                        <h3>₹<?php echo number_format($today_profit, 2); ?></h3>
                        <p>Today Profits</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-cart'></i>
                    <span class="text">
                        <h3><?php echo $today_sales; ?></h3>
                        <p>Today Sales</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo $today_customers; ?></h3>
                        <p>Today Customer</p>
                    </span>
                </li>
            </ul>
            <ul class="box-info">
                <li>
                    <i class='bx bx-rupee'></i>
                    <span class="text">
                        <h3>₹<?php echo number_format($month_profit, 2); ?></h3>
                        <p>This Month Profits</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-cart'></i>
                    <span class="text">
                        <h3><?php echo $month_sales; ?></h3>
                        <p>This Month Sales</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo $month_customers; ?></h3>
                        <p>This Month Customer</p>
                    </span>
                </li>
            </ul>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Recent Bills</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_recent_bills->num_rows > 0): ?>
                                <?php while ($bill = $result_recent_bills->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="img/cutomer.png">
                                            <p><?php echo htmlspecialchars($bill['customer_name']); ?></p>
                                        </td>
                                        <td><?php echo number_format($bill['total'], 2); ?> RS</td>
                                        <td><span class="status completed">Completed</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No recent bills found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="order">
                    <div class="head">
                        <h3>Top Selling Products</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Products</th>
                                <th>Available Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_top_products->num_rows > 0): ?>
                                <?php while ($product = $result_top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="img/product.png">
                                            <p><?php echo htmlspecialchars($product['name']); ?></p>
                                        </td>
                                        <td><?php echo $product['stock']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2">No top selling products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="todo">
                    <div class="head">
                        <h3>Low Stock Alerts</h3>
                    </div>
                    <ul class="todo-list">
                        <?php
                        if ($result_low_stock->num_rows > 0) {
                            while ($row = $result_low_stock->fetch_assoc()) {
                                $status_class = $row['stock'] == 0 ? 'not-completed' : 'completed';
                        ?>
                                <li class="<?php echo $status_class; ?>">
                                    <p><?php echo htmlspecialchars($row['name']); ?></p>
                                    <i class='bx bx-trash'></i>
                                </li>
                        <?php
                            }
                        } else {
                            echo '<li class="completed"><p>No low stock alerts</p><i class="bx bx-trash"></i></li>';
                        }
                        ?>
                    </ul>
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