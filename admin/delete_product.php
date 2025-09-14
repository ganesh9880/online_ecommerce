<?php
include '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = $_GET['id'] ?? null;
$error_message = '';
$success_message = '';

if (!$product_id) {
    header("Location: manage_products.php");
    exit();
}

// Fetch product details for confirmation
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: manage_products.php");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Check if product is in any cart
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $cart_count = $stmt->fetchColumn();
        
        if ($cart_count > 0) {
            // Remove from cart first
            $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$product_id]);
        }
        
        // Check if product is in any orders
        $stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            // For orders, we'll keep the order items but mark the product as deleted
            // This preserves order history
            $stmt = $conn->prepare("UPDATE products SET name = CONCAT(name, ' (DELETED)'), description = CONCAT(description, ' - This product has been removed from the catalog.') WHERE id = ?");
            $stmt->execute([$product_id]);
        } else {
            // No orders, safe to delete completely
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
        }
        
        // Delete image file if it exists
        if ($product['image'] && file_exists('../images/' . $product['image'])) {
            unlink('../images/' . $product['image']);
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Product deleted successfully!";
        
        // Redirect after 2 seconds
        header("refresh:2;url=manage_products.php");
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Error deleting product: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #dc3545;
            margin-bottom: 30px;
        }
        .product-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .product-info h3 {
            margin-top: 0;
            color: #333;
        }
        .product-image {
            text-align: center;
            margin: 15px 0;
        }
        .product-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        .btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>⚠️ Delete Product</h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <p>Redirecting to product management...</p>
        <?php else: ?>
        
        <div class="product-info">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p><strong>Price:</strong> $<?= number_format($product['price'], 2) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($product['description']) ?></p>
            <?php if ($product['image']): ?>
                <div class="product-image">
                    <img src="../images/<?= htmlspecialchars($product['image']) ?>" alt="Product Image">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> This action cannot be undone. 
            If this product is in any customer orders, it will be marked as deleted but preserved in order history.
            If no orders exist, the product will be permanently removed.
        </div>
        
        <form method="POST">
            <div class="form-actions">
                <button type="submit" name="confirm_delete" class="btn" onclick="return confirm('Are you absolutely sure you want to delete this product? This action cannot be undone!');">
                    Yes, Delete Product
                </button>
                <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <?php endif; ?>
    </div>
</body>
</html>
