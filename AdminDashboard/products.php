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

// Fetch products and inventory details
$sql = "SELECT p.id, p.name, p.stock,
               (SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = p.id) AS initial_stock
        FROM products p";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Products</title>
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
        .btn-edit button {
            background-color: #4CAF50;
            color: white;
        }
        .btn-edit button:hover {
            background-color: #45a049;
        }
        .btn-delete button {
            background-color: #f44336;
            color: white;
        }
        .btn-delete button:hover {
            background-color: #d32f2f;
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
        .add-product {
            margin-bottom: 20px;
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
                    <h1>Products</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="admin_dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Products</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Welcome to Product Section</h3>
                    </div>
                    <?php if (isset($_GET['message'])): ?>
                        <div class="success">
                            <?php echo htmlspecialchars($_GET['message']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="error">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="add-product">
                        <a href="add_product.php" class="btn btn-add">
                            <button>Add Product</button>
                        </a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Available Stock</th>
                                <th>Action</th>
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
                                        <td><?php echo htmlspecialchars($row['stock']); ?><?php echo $is_low_stock ? ' (Low Stock Alert)' : ''; ?></td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                                                <button>Edit</button>
                                            </a>
                                            <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">
                                                <button>Delete</button>
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="3">No products found.</td></tr>';
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