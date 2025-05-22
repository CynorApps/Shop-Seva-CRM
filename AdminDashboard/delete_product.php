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
    // Delete product (inventory will be deleted automatically due to ON DELETE CASCADE)
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        // Redirect to products page with success message
        header("Location: products.php?message=Product+deleted+successfully");
    } else {
        // Redirect to products page with error message
        header("Location: products.php?error=Failed+to+delete+product");
    }
} else {
    // Redirect to products page with error message
    header("Location: products.php?error=Invalid+product+ID");
}

$conn->close();
?>