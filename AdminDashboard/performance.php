<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Fetch data for Monthly Sales Trend (Last 6 months)
$monthly_sales_data = [];
$labels_monthly_sales = [];
$current_month = date('m');
$current_year = date('Y');
for ($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $labels_monthly_sales[] = date('M Y', strtotime("-$i months"));

    $sql = "SELECT SUM(total) as total_sales FROM sales WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $monthly_sales_data[] = $row['total_sales'] ? (float)$row['total_sales'] : 0;
    $stmt->close();
}

// Fetch data for Top Selling Products (Top 5 by quantity sold)
$top_products_data = [];
$labels_top_products = [];
$sql = "SELECT p.name, SUM(si.quantity) as total_quantity 
        FROM sale_items si 
        JOIN products p ON si.product_id = p.id 
        GROUP BY si.product_id, p.name 
        ORDER BY total_quantity DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $labels_top_products[] = $row['name'];
    $top_products_data[] = (int)$row['total_quantity'];
}

// Fetch data for Worker Performance (Total sales by each worker)
$worker_performance_data = [];
$labels_worker_performance = [];
$sql = "SELECT u.name, SUM(s.total) as total_sales 
        FROM sales s 
        JOIN users u ON s.user_id = u.id 
        WHERE u.role != 'Admin' 
        GROUP BY s.user_id, u.name 
        ORDER BY total_sales DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $labels_worker_performance[] = $row['name'];
    $worker_performance_data[] = (float)$row['total_sales'];
}

// Fetch data for Sales by Day of Week (Average sales per day of the week)
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$sales_by_day_data = [];
foreach ($days_of_week as $day) {
    $sql = "SELECT AVG(total) as avg_sales 
            FROM sales 
            WHERE DAYNAME(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $day);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $sales_by_day_data[] = $row['avg_sales'] ? (float)$row['avg_sales'] : 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Performance</title>
    <style>
        .chart-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease-out forwards;
        }
        .chart-container:nth-child(2) { animation-delay: 0.2s; }
        .chart-container:nth-child(3) { animation-delay: 0.4s; }
        .chart-container:nth-child(4) { animation-delay: 0.6s; }
        .chart-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        canvas {
            max-width: 100%;
        }
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
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
            <li class="active">
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
                    <h1>Performance Analytics</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Performance</a></li>
                    </ul>
                </div>
            </div>

            <!-- Monthly Sales Trend Chart -->
            <div class="chart-container">
                <h2>Monthly Sales Trend (Last 6 Months)</h2>
                <canvas id="monthlySalesChart"></canvas>
            </div>

            <!-- Top Selling Products Chart -->
            <div class="chart-container">
                <h2>Top 5 Selling Products</h2>
                <canvas id="topProductsChart"></canvas>
            </div>

            <!-- Worker Performance Chart -->
            <div class="chart-container">
                <h2>Worker Performance (Total Sales)</h2>
                <canvas id="workerPerformanceChart"></canvas>
            </div>

            <!-- Sales by Day of Week Chart -->
            <div class="chart-container">
                <h2>Average Sales by Day of Week</h2>
                <canvas id="salesByDayChart"></canvas>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Common animation settings
        const animationSettings = {
            duration: 1500, // Animation duration in milliseconds
            easing: 'easeOutQuart', // Smooth easing function
            delay: (ctx) => ctx.dataIndex * 200 // Delay for each element
        };

        // Monthly Sales Trend Chart (Line Chart)
        const monthlySalesChart = new Chart(document.getElementById('monthlySalesChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_monthly_sales); ?>,
                datasets: [{
                    label: 'Total Sales (₹)',
                    data: <?php echo json_encode($monthly_sales_data); ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    fill: true,
                    pointBackgroundColor: '#4CAF50',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4CAF50'
                }]
            },
            options: {
                responsive: true,
                animation: animationSettings,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Amount (₹)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false,
                    axis: 'x'
                },
                hover: {
                    animationDuration: 400
                }
            }
        });

        // Top Selling Products Chart (Bar Chart)
        const topProductsChart = new Chart(document.getElementById('topProductsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_top_products); ?>,
                datasets: [{
                    label: 'Quantity Sold',
                    data: <?php echo json_encode($top_products_data); ?>,
                    backgroundColor: '#4CAF50',
                    borderColor: '#4CAF50',
                    borderWidth: 1,
                    hoverBackgroundColor: '#45a049',
                    hoverBorderColor: '#45a049'
                }]
            },
            options: {
                responsive: true,
                animation: animationSettings,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity Sold'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Product'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                hover: {
                    animationDuration: 400
                }
            }
        });

        // Worker Performance Chart (Bar Chart)
        const workerPerformanceChart = new Chart(document.getElementById('workerPerformanceChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_worker_performance); ?>,
                datasets: [{
                    label: 'Total Sales (₹)',
                    data: <?php echo json_encode($worker_performance_data); ?>,
                    backgroundColor: '#4CAF50',
                    borderColor: '#4CAF50',
                    borderWidth: 1,
                    hoverBackgroundColor: '#45a049',
                    hoverBorderColor: '#45a049'
                }]
            },
            options: {
                responsive: true,
                animation: animationSettings,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Amount (₹)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Worker'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                hover: {
                    animationDuration: 400
                }
            }
        });

        // Sales by Day of Week Chart (Bar Chart)
        const salesByDayChart = new Chart(document.getElementById('salesByDayChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($days_of_week); ?>,
                datasets: [{
                    label: 'Average Sales (₹)',
                    data: <?php echo json_encode($sales_by_day_data); ?>,
                    backgroundColor: '#4CAF50',
                    borderColor: '#4CAF50',
                    borderWidth: 1,
                    hoverBackgroundColor: '#45a049',
                    hoverBorderColor: '#45a049'
                }]
            },
            options: {
                responsive: true,
                animation: animationSettings,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Average Sales (₹)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Day of Week'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                hover: {
                    animationDuration: 400
                }
            }
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
<?php
$conn->close();
?>