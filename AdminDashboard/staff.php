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
$edit_mode = false;
$edit_user = null;

// Handle Add/Edit Worker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_worker'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        $errors[] = "All fields except password are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!in_array($role, ['Worker'])) {
        $errors[] = "Invalid role selected.";
    }
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        // Edit mode: Password is optional
        if (!empty($password) && strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
    } else {
        // Add mode: Password is required
        if (empty($password)) {
            $errors[] = "Password is required for new workers.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
    }

    if (empty($errors)) {
        if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            // Edit Worker
            $user_id = (int)$_POST['user_id'];
            if (empty($password)) {
                // Update without changing password
                $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ? AND role != 'Admin'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $email, $role, $user_id);
            } else {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ? AND role != 'Admin'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $user_id);
            }
            if ($stmt->execute()) {
                $success = "Worker updated successfully.";
            } else {
                $errors[] = "Failed to update worker.";
            }
            $stmt->close();
        } else {
            // Add New Worker
            $sql_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $errors[] = "Email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                if ($stmt->execute()) {
                    $success = "Worker added successfully.";
                } else {
                    $errors[] = "Failed to add worker.";
                }
            }
            $stmt_check->close();
            $stmt->close();
        }
    }
}

// Handle Delete Worker
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM users WHERE id = ? AND role != 'Admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Worker deleted successfully.";
    } else {
        $errors[] = "Failed to delete worker.";
    }
    $stmt->close();
}

// Handle Edit Worker (Load data for editing)
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $sql = "SELECT * FROM users WHERE id = ? AND role != 'Admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
        $edit_mode = true;
    } else {
        $errors[] = "Worker not found.";
    }
    $stmt->close();
}

// Fetch all workers
$sql = "SELECT * FROM users WHERE role != 'Admin'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Shop-Seva - Staff Management</title>
    <style>
        .table-container {
            margin: 20px auto;
            max-width: 1200px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .table-container h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
        }
        table tr:hover {
            background-color: #f9f9f9;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .add-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
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
        .form-group input, .form-group select {
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

            <li class="active">
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
                    <h1>Staff Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active" href="#">Staff</a></li>
                    </ul>
                </div>
            </div>

            <?php if ($edit_mode || isset($_GET['add'])): ?>
                <!-- Add/Edit Worker Form -->
                <div class="form-container">
                    <h2><?php echo $edit_mode ? 'Edit Worker' : 'Add New Worker'; ?></h2>
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
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" value="<?php echo $edit_mode ? htmlspecialchars($edit_user['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $edit_mode ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" required>
                                <option value="Worker" <?php echo ($edit_mode && $edit_user['role'] === 'Worker') ? 'selected' : ''; ?>>Worker</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password">Password <?php echo $edit_mode ? '(Leave blank to keep unchanged)' : ''; ?></label>
                            <input type="password" name="password" id="password" placeholder="Enter password">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="save_worker" class="submit-btn"><?php echo $edit_mode ? 'Update Worker' : 'Add Worker'; ?></button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Workers List -->
                <div class="table-container">
                    <a href="staff.php?add=1" class="add-btn">Add New Worker</a>
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
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($worker = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($worker['id']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['name']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['email']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['role']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['created_at']); ?></td>
                                        <td>
                                            <a href="staff.php?edit_id=<?php echo $worker['id']; ?>" class="action-btn edit-btn">Edit</a>
                                            <a href="staff.php?delete_id=<?php echo $worker['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this worker?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No workers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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