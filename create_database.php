<?php
/**
 * Database Setup Script for Ecommerce Application
 * Run this script to automatically create the database and tables
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';

try {
    // Connect to MySQL server (without selecting a database)
    $pdo = new PDO("mysql:host=$host", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully!\n\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'ecommerce' created successfully!\n";
    
    // Select the database
    $pdo->exec("USE ecommerce");
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) DEFAULT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        role ENUM('user','admin') DEFAULT 'user'
    )");
    echo "✅ Users table created successfully!\n";
    
    // Create products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Products table created successfully!\n";
    
    // Create cart table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "✅ Cart table created successfully!\n";
    
    // Create orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        shipping_address TEXT NOT NULL,
        billing_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Orders table created successfully!\n";
    
    // Create order_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "✅ Order items table created successfully!\n";
    
    // Insert sample products
    $sample_products = [
        ['Gaming Chair', 299.99, 'Comfortable gaming chair with RGB lighting', 'chair.jpg'],
        ['Wireless Mouse', 49.99, 'High-precision wireless gaming mouse', 'mouse.jpg'],
        ['Mechanical Keyboard', 129.99, 'RGB mechanical keyboard with blue switches', 'keyboard.jpg'],
        ['Gaming Monitor', 399.99, '27-inch 4K gaming monitor with 144Hz refresh rate', 'monitor.jpg'],
        ['Gaming Headset', 89.99, '7.1 surround sound gaming headset', 'headphones.jpg'],
        ['Webcam', 79.99, '4K webcam with built-in microphone', 'webcam.jpg'],
        ['Smartwatch', 199.99, 'Fitness tracking smartwatch with heart rate monitor', 'smartwatch.jpg'],
        ['Bluetooth Speaker', 59.99, 'Portable wireless speaker with 360-degree sound', 'speaker.jpg'],
        ['External Hard Drive', 89.99, '1TB portable external hard drive', 'HardDrive.jpg'],
        ['Phone Charger', 19.99, 'Fast charging USB-C cable and adapter', 'charger.jpg']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    foreach ($sample_products as $product) {
        $stmt->execute($product);
    }
    echo "✅ Sample products inserted successfully!\n";
    
    // Create admin user
    $admin_email = 'admin@ecommerce.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_username = 'admin';
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$admin_username, $admin_email, $admin_password]);
    echo "✅ Admin user created successfully!\n";
    
    echo "\n🎉 Database setup completed successfully!\n\n";
    echo "📋 Login Credentials:\n";
    echo "Admin Email: admin@ecommerce.com\n";
    echo "Admin Password: admin123\n\n";
    echo "🌐 Access your application at: http://localhost/ecommerce\n";
    echo "👨‍💼 Admin panel: http://localhost/ecommerce/admin/login.php\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please make sure XAMPP is running and MySQL is accessible.\n";
}
?>
