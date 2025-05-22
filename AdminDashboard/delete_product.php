<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    $conn->begin_transaction();
    try {
        // Fetch product name for logging
        $sql_product = "SELECT name FROM products WHERE id = ?";
        $stmt_product = $conn->prepare($sql_product);
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        $product = $result_product->fetch_assoc();
        $stmt_product->close();

        // Delete product
        $sql_delete = "DELETE FROM products WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $product_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Log activity
        $user_id = $_SESSION['user']['id'];
        $action = "Deleted product: " . ($product['name'] ?? 'Unknown');
        $sql_log = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("is", $user_id, $action);
        $stmt_log->execute();
        $stmt_log->close();

        $conn->commit();
        header("Location: products.php?message=Product+deleted+successfully");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: products.php?error=Failed+to+delete+product:+" . urlencode($e->getMessage()));
    }
} else {
    header("Location: products.php?error=Invalid+product+ID");
}

$conn->close();
?>