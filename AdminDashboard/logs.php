<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Fetch activity logs from the database using LEFT JOIN (without description)
$sql = "SELECT al.id, al.user_id, u.name AS user_name, al.action, al.created_at 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC";
$result = $conn->query($sql);
$logs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $result->free();
} else {
    echo "Error executing query: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Activity Logs</title>
    <style>
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            overflow-x: auto;
        }
        .logs-table th, .logs-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            box-sizing: border-box;
        }
        .logs-table th {
            background-color: #4CAF50;
            color: #fff;
            font-weight: 600;
        }
        .logs-table tr:hover {
            background-color: #f5f5f5;
        }
        .logs-table td {
            color: #333;
        }
        /* Responsive Design for Mobile */
        @media (max-width: 768px) {
            .logs-table {
                width: 100%;
                display: block !important;
                overflow-x: hidden !important;
            }
            .logs-table thead {
                display: none !important;
            }
            .logs-table tbody {
                display: block !important;
            }
            .logs-table tr {
                display: block !important;
                margin-bottom: 20px !important;
                border-bottom: 2px solid #ddd !important;
                padding: 10px 0 !important;
            }
            .logs-table td {
                display: block !important;
                text-align: right !important;
                padding: 8px 15px !important;
                position: relative !important;
                border: none !important;
                font-size: 14px !important;
            }
            .logs-table td:before {
                content: attr(data-label) !important;
                position: absolute !important;
                left: 15px !important;
                width: 40% !important;
                padding-right: 10px !important;
                font-weight: bold !important;
                text-align: left !important;
                color: #4CAF50 !important;
            }
            .logs-table td[colspan] {
                text-align: center !important;
                padding: 15px !important;
            }
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
            <li class="active">
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
                    <h1>Activity Logs</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Logs</a></li>
                    </ul>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="table-data">
                <div class="order">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td data-label="User"><?php echo htmlspecialchars($log['user_name'] ?? 'Unknown User'); ?></td>
                                        <td data-label="Action"><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td data-label="Timestamp"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
$conn->close();
?>