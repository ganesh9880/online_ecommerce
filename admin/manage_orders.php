<?php
include '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        $success_message = "Order status updated successfully to: " . ucfirst($new_status);
    } catch (Exception $e) {
        $error_message = "Error updating order status: " . $e->getMessage();
    }
}

// Fetch orders with user information
$stmt = $conn->query("SELECT o.*, u.username, u.email 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 95%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 2px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-back {
            display: block;
            width: 150px;
            margin: 30px auto;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #545b62;
        }
        .order-details {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .status-select {
            border: 2px solid #007bff;
            border-radius: 4px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .status-select:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Orders</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: #6c757d; font-size: 18px;">No orders found.</p>
        <?php else: ?>
        
        <table>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($order['username'] ?: 'N/A') ?></strong><br>
                        <small><?= htmlspecialchars($order['email']) ?></small>
                    </td>
                    <td> ₹<?= number_format($order['total_amount'], 2) ?></td>
                    <td>
                        <span class="status status-<?= $order['status'] ?>">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>
                        <br><small style="color: #6c757d;">Current</small>
                    </td>
                    <td>
                        <span class="status status-<?= $order['payment_status'] ?>">
                            <?= htmlspecialchars($order['payment_status']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="manage_orders.php" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" onchange="if(confirm('Update order status to: ' + this.options[this.selectedIndex].text + '?')) this.form.submit();" class="status-select" style="font-size: 12px; padding: 4px;">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>


</body>
</html>
