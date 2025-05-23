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
$selected_month = isset($_POST['month']) ? (int)$_POST['month'] : date('m');
$selected_year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$selected_table = isset($_POST['table']) ? $_POST['table'] : 'sales';

// Generate list of years (last 5 years)
$current_year = date('Y');
$years = range($current_year, $current_year - 5);

// Available tables for backup
$tables = ['sales', 'sale_items', 'products', 'users', 'activity_logs'];

// Function to generate CSV content
function arrayToCsv($data, $delimiter = ',', $enclosure = '"') {
    $output = fopen('php://temp', 'r+');
    // Add BOM for UTF-8 support in Excel
    fwrite($output, "\xEF\xBB\xBF");
    foreach ($data as $row) {
        fputcsv($output, $row, $delimiter, $enclosure);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_backup'])) {
    // Validate month and year
    if ($selected_month < 1 || $selected_month > 12) {
        $errors[] = "Invalid month selected.";
    }
    if ($selected_year < 2000 || $selected_year > $current_year) {
        $errors[] = "Invalid year selected.";
    }
    if (!in_array($selected_table, $tables)) {
        $errors[] = "Invalid table selected.";
    }

    if (empty($errors)) {
        $data = [];
        if ($selected_table === 'sales') {
            $sql = "SELECT * FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $selected_month, $selected_year);
            $stmt->execute();
            $result = $stmt->get_result();

            $data[] = ['Sale ID', 'User ID', 'Customer Name', 'Customer Phone', 'Total', 'Discount', 'Created At'];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['id'],
                    $row['user_id'],
                    $row['customer_name'],
                    $row['customer_phone'],
                    $row['total'],
                    $row['discount'],
                    $row['created_at']
                ];
            }
            $stmt->close();
        } elseif ($selected_table === 'sale_items') {
            $sql = "SELECT si.* FROM sale_items si 
                    JOIN sales s ON si.sale_id = s.id 
                    WHERE MONTH(s.created_at) = ? AND YEAR(s.created_at) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $selected_month, $selected_year);
            $stmt->execute();
            $result = $stmt->get_result();

            $data[] = ['ID', 'Sale ID', 'Product ID', 'Quantity', 'Price'];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['id'],
                    $row['sale_id'],
                    $row['product_id'],
                    $row['quantity'],
                    $row['price']
                ];
            }
            $stmt->close();
        } elseif ($selected_table === 'products') {
            $sql = "SELECT * FROM products";
            $result = $conn->query($sql);

            $data[] = ['ID', 'Name', 'Price', 'Stock', 'Created At', 'Updated At'];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['id'],
                    $row['name'],
                    $row['price'],
                    $row['stock'],
                    $row['created_at'],
                    $row['updated_at']
                ];
            }
        } elseif ($selected_table === 'users') {
            $sql = "SELECT id, name, email, role, created_at FROM users";
            $result = $conn->query($sql);

            $data[] = ['ID', 'Name', 'Email', 'Role', 'Created At'];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['id'],
                    $row['name'],
                    $row['email'],
                    $row['role'],
                    $row['created_at']
                ];
            }
        } elseif ($selected_table === 'activity_logs') {
            $sql = "SELECT al.*, u.name as user_name 
                    FROM activity_logs al 
                    JOIN users u ON al.user_id = u.id 
                    WHERE MONTH(al.created_at) = ? AND YEAR(al.created_at) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $selected_month, $selected_year);
            $stmt->execute();
            $result = $stmt->get_result();

            $data[] = ['ID', 'User ID', 'User Name', 'Action', 'Created At'];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['id'],
                    $row['user_id'],
                    $row['user_name'],
                    $row['action'],
                    $row['created_at']
                ];
            }
            $stmt->close();
        }

        // Generate and download CSV file
        $filename = "backup_{$selected_table}_{$selected_year}_{$selected_month}.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        echo arrayToCsv($data);
        exit();
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
    <title>Shop-Seva - Backup</title>
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
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            <li><a href="billing_history.php"><i class='bx bxs-history'></i><span class="text">Billing History</span></a></li>
            <li><a href="#"><i class='bx bxs-message-dots'></i><span class="text">Message</span></a></li>
            <!-- Backup option will be added here by you -->
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
            <span class="text"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
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
                    <h1>Backup</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Backup</a></li>
                    </ul>
                </div>
            </div>

            <div class="form-container">
                <h2>Generate Monthly Backup</h2>
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
                <form method="POST">
                    <div class="form-group">
                        <label for="table">Select Table</label>
                        <select name="table" id="table" required>
                            <option value="sales" <?php echo $selected_table == 'sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="sale_items" <?php echo $selected_table == 'sale_items' ? 'selected' : ''; ?>>Sale Items</option>
                            <option value="products" <?php echo $selected_table == 'products' ? 'selected' : ''; ?>>Products</option>
                            <option value="users" <?php echo $selected_table == 'users' ? 'selected' : ''; ?>>Users</option>
                            <option value="activity_logs" <?php echo $selected_table == 'activity_logs' ? 'selected' : ''; ?>>Activity Logs</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="month">Select Month</label>
                        <select name="month" id="month" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Select Year</label>
                        <select name="year" id="year" required>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="generate_backup" class="submit-btn">Generate Backup</button>
                    </div>
                </form>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
</body>
</html>
<?php
$conn->close();
?>