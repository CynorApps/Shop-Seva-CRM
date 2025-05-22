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
        <a href="#" class="brand">
            <i class='bx bxs-smile'></i>
            <span class="text">Shop-Seva</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="#">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
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
                        <h3>1020</h3>
                        <p>Today Profits</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-cart'></i>
                    <span class="text">
                        <h3>34</h3>
                        <p>Today Sales</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3>12</h3>
                        <p>Today Customer</p>
                    </span>
                </li>
            </ul>
            <ul class="box-info">
                <li>
                    <i class='bx bx-rupee'></i>
                    <span class="text">
                        <h3>9020</h3>
                        <p>This Month Profits</p>
                    </span>
                </li>
                <li>
                    <i class='bx bx-cart'></i>
                    <span class="text">
                        <h3>934</h3>
                        <p>This Month Sales</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3>342</h3>
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
                            <tr>
                                <td>
                                    <img src="img/customer.png">
                                    <p>Sagar Navale</p>
                                </td>
                                <td>1100 RS</td>
                                <td><span class="status completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/customer.png">
                                    <p>Vishal Navale</p>
                                </td>
                                <td>2000 RS</td>
                                <td><span class="status completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/customer.png">
                                    <p>Bunny Vivek</p>
                                </td>
                                <td>250 RS</td>
                                <td><span class="status process">Process</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/customer.png">
                                    <p>Prashant Pekhale</p>
                                </td>
                                <td>7880 RS</td>
                                <td><span class="status pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/customer.png">
                                    <p>Rahul Navale</p>
                                </td>
                                <td>897 RS</td>
                                <td><span class="status completed">Completed</span></td>
                            </tr>
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
                            <tr>
                                <td>
                                    <img src="img/product.png">
                                    <p>Vivo T3 5G</p>
                                </td>
                                <td>55</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/product.png">
                                    <p>Samsung A36</p>
                                </td>
                                <td>20</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/product.png">
                                    <p>Redmi Note 11</p>
                                </td>
                                <td>45</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/product.png">
                                    <p>I Phone 13</p>
                                </td>
                                <td>25</td>
                            </tr>
                            <tr>
                                <td>
                                    <img src="img/product.png">
                                    <p>Vivo V50</p>
                                </td>
                                <td>27</td>
                            </tr>
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