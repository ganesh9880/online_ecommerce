<?php
include '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        th, td {
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .col-id {
            width: 60px;
        }
        .col-name {
            width: 200px;
        }
        .col-price {
            width: 100px;
        }
        .col-description {
            width: 250px;
            max-width: 250px;
        }
        .col-image {
            width: 80px;
        }
        .col-actions {
            width: 150px;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        td img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .description-text {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }
        .description-text:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 300px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .actions {
            white-space: nowrap;
        }
        .actions a {
            margin: 0 5px;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-edit {
            background-color: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        .btn-edit:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-view {
            background-color: #17a2b8;
            color: white;
            border: 1px solid #17a2b8;
        }
        .btn-view:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        .btn-back {
            display: block;
            width: 150px;
            margin: 30px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 20px auto;
                padding: 15px;
            }
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            table {
                min-width: 600px;
            }
            .col-description {
                width: 200px;
                max-width: 200px;
            }
            .description-text {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Products</h2>
    
    <div style="text-align: right; margin-bottom: 20px;">
        <a href="add_product.php" class="btn" style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">➕ Add New Product</a>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <h3>No Products Found</h3>
            <p>Start by adding your first product!</p>
            <a href="add_product.php" class="btn" style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px;">➕ Add First Product</a>
        </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-name">Name</th>
                <th class="col-price">Price</th>
                <th class="col-description">Description</th>
                <th class="col-image">Image</th>
                <th class="col-actions">Actions</th>
            </tr>

            <?php foreach ($products as $product) : ?>
                <tr>
                    <td class="col-id"><?= $product['id']; ?></td>
                    <td class="col-name"><?= htmlspecialchars($product['name']); ?></td>
                    <td class="col-price">$<?= number_format($product['price'], 2); ?></td>
                    <td class="col-description">
                        <span class="description-text" title="<?= htmlspecialchars($product['description']); ?>">
                            <?= htmlspecialchars($product['description']); ?>
                        </span>
                    </td>
                    <td class="col-image">
                        <img src="../images/<?= htmlspecialchars($product['image']); ?>" alt="Product Image">
                    </td>
                    <td class="col-actions actions">
                        <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn-edit">✏️ Edit</a>
                        <a href="delete_product.php?id=<?= $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
</div>

</body>
</html>
