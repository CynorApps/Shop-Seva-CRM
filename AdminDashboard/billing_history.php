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
$search_customer = $_GET['search_customer'] ?? '';
$search_product = $_GET['search_product'] ?? '';

// Fetch bills (join with users table to get worker name)
$sql_bills = "SELECT s.id, s.customer_name, s.total, s.created_at, 
                     GROUP_CONCAT(p.name SEPARATOR ', ') AS product_names,
                     u.name AS worker_name
              FROM sales s
              JOIN sale_items si ON s.id = si.sale_id
              JOIN products p ON si.product_id = p.id
              JOIN users u ON s.user_id = u.id
              WHERE 1=1";
$params = [];
$types = '';

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
if (!empty($params)) {
    $stmt_bills->bind_param($types, ...$params);
}
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
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Billing History</title>
    <style>
        .form-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .form-container h2 {
            margin-bottom: 20px;
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
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            margin-right: 10px;
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
            <li class="active">
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
                    <h1>Billing History</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Billing History</a></li>
                    </ul>
                </div>
            </div>

            <div class="form-container">
                <h2>Bill Records</h2>
                <form method="GET" class="search-form">
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
                                <th>Worker Name</th>
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
                                        <td><?php echo htmlspecialchars($bill['worker_name']); ?></td>
                                        <td><?php echo htmlspecialchars($bill['product_names']); ?></td>
                                        <td><?php echo number_format($bill['total'], 2); ?></td>
                                        <td>
                                            <a href="edit_bill.php?sale_id=<?php echo $bill['id']; ?>">Edit</a>
                                            <a href="generate_bill_pdf.php?sale_id=<?php echo $bill['id']; ?>">View PDF</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No bills found.</td></tr>
                            <?php endif; ?>
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
$stmt_bills->close();
$conn->close();
?>