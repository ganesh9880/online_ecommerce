<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Get cart items
        $stmt = $conn->prepare("SELECT c.*, p.name, p.price FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cart_items)) {
            throw new Exception("Your cart is empty.");
        }
        
        // Calculate total
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        // Prepare addresses
        $shipping_address = $_POST['address'] . ', ' . $_POST['city'] . ', ' . $_POST['state'] . ' ' . $_POST['zip_code'];
        $billing_address = isset($_POST['same_as_shipping']) ? $shipping_address : 
                          ($_POST['billing_address'] . ', ' . $_POST['billing_city'] . ', ' . $_POST['billing_state'] . ' ' . $_POST['billing_zip_code']);
        
        // Start transaction
        $conn->beginTransaction();
        
        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_address, billing_address, payment_method) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $order_number, $total_amount, $shipping_address, $billing_address, $_POST['payment_method']]);
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Order placed successfully! Order Number: " . $order_number;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items for display
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Store</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #343a40;
            margin-bottom: 30px;
        }
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .form-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        .required {
            color: #dc3545;
        }
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .order-item-details {
            flex-grow: 1;
        }
        .order-item-name {
            font-weight: bold;
            color: #343a40;
        }
        .order-item-price {
            color: #6c757d;
        }
        .order-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #343a40;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
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
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .section-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="cart.php" class="back-link">← Back to Cart</a>
        <h1>Checkout</h1>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php else: ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-error">Your cart is empty. <a href="../index.php">Continue Shopping</a></div>
        <?php else: ?>
        
        <div class="checkout-container">
            <div class="form-section">
                <form method="POST" action="">
                    <div class="section-title">Shipping Information</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State <span class="required">*</span></label>
                            <input type="text" id="state" name="state" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip_code">ZIP Code <span class="required">*</span></label>
                        <input type="text" id="zip_code" name="zip_code" required>
                    </div>
                    
                    <div class="section-title">Billing Information</div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="same_as_shipping" name="same_as_shipping" checked>
                        <label for="same_as_shipping">Same as shipping address</label>
                    </div>
                    
                    <div id="billing_fields" style="display: none;">
                        <div class="form-group">
                            <label for="billing_address">Billing Address</label>
                            <textarea id="billing_address" name="billing_address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_city">Billing City</label>
                                <input type="text" id="billing_city" name="billing_city">
                            </div>
                            <div class="form-group">
                                <label for="billing_state">Billing State</label>
                                <input type="text" id="billing_state" name="billing_state">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_zip_code">Billing ZIP Code</label>
                            <input type="text" id="billing_zip_code" name="billing_zip_code">
                        </div>
                    </div>
                    
                    <div class="section-title">Payment Information</div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <div class="section-title">Order Summary</div>
                
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <img src="../images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="order-item-details">
                            <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="order-item-price">₹<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    Total: ₹<?= number_format($total_amount, 2) ?>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Toggle billing fields
        document.getElementById('same_as_shipping').addEventListener('change', function() {
            const billingFields = document.getElementById('billing_fields');
            if (this.checked) {
                billingFields.style.display = 'none';
            } else {
                billingFields.style.display = 'block';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ced4da';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    </script>
</body>
</html>
