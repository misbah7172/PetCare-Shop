<?php
// Database setup script for PawConnect
// Run this script once to set up the database and tables

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'pawconnect_db';

echo "<h1>PawConnect Database Setup</h1>";

try {
    // Create connection without database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server successfully</p>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "<p>✓ Database '$dbname' created or already exists</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(50) UNIQUE NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        transaction_type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        mobile_number VARCHAR(15),
        card_name VARCHAR(100),
        card_number VARCHAR(20),
        email VARCHAR(100) NOT NULL,
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        zipcode VARCHAR(10),
        country VARCHAR(10),
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_transaction_id (transaction_id),
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p>✓ Transactions table created successfully</p>";
    
    // Create users table (for future use)
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        phone VARCHAR(15),
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        zipcode VARCHAR(10),
        country VARCHAR(10),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p>✓ Users table created successfully</p>";
    
    // Create products table (for future use)
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50),
        image_url VARCHAR(255),
        stock_quantity INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_is_active (is_active),
        INDEX idx_price (price)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p>✓ Products table created successfully</p>";
    
    // Insert sample data for testing
    $sample_transactions = [
        [
            'transaction_id' => 'PC-2024-0001',
            'payment_method' => 'bkash',
            'transaction_type' => 'purchase',
            'amount' => 3099.00,
            'mobile_number' => '01712345678',
            'email' => 'customer1@example.com',
            'address' => '123 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'zipcode' => '1000',
            'country' => 'BD',
            'status' => 'completed'
        ],
        [
            'transaction_id' => 'PC-2024-0002',
            'payment_method' => 'card',
            'transaction_type' => 'subscription',
            'amount' => 1999.00,
            'card_name' => 'John Doe',
            'card_number' => '1234',
            'email' => 'customer2@example.com',
            'address' => '456 Oak Ave',
            'city' => 'Chittagong',
            'state' => 'Chittagong',
            'zipcode' => '4000',
            'country' => 'BD',
            'status' => 'completed'
        ],
        [
            'transaction_id' => 'PC-2024-0003',
            'payment_method' => 'nagad',
            'transaction_type' => 'donation',
            'amount' => 5000.00,
            'mobile_number' => '01887654321',
            'email' => 'customer3@example.com',
            'address' => '789 Pine Rd',
            'city' => 'Sylhet',
            'state' => 'Sylhet',
            'zipcode' => '3100',
            'country' => 'BD',
            'status' => 'completed'
        ]
    ];
    
    // Check if sample data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sql = "INSERT INTO transactions (
            transaction_id, payment_method, transaction_type, amount,
            mobile_number, card_name, card_number, email,
            address, city, state, zipcode, country, status
        ) VALUES (
            :transaction_id, :payment_method, :transaction_type, :amount,
            :mobile_number, :card_name, :card_number, :email,
            :address, :city, :state, :zipcode, :country, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($sample_transactions as $transaction) {
            $stmt->execute($transaction);
        }
        
        echo "<p>✓ Sample transaction data inserted successfully</p>";
    } else {
        echo "<p>ℹ Sample data already exists, skipping insertion</p>";
    }
    
    // Insert sample products
    $sample_products = [
        [
            'name' => 'Premium Pet Food',
            'description' => 'High-quality premium pet food for dogs and cats',
            'price' => 2999.00,
            'category' => 'Pet Food',
            'stock_quantity' => 50
        ],
        [
            'name' => 'Pet Toys Set',
            'description' => 'Assorted pet toys for dogs and cats',
            'price' => 1500.00,
            'category' => 'Pet Toys',
            'stock_quantity' => 30
        ],
        [
            'name' => 'Pet Grooming Kit',
            'description' => 'Complete grooming kit for pets',
            'price' => 2500.00,
            'category' => 'Grooming',
            'stock_quantity' => 20
        ]
    ];
    
    // Check if sample products already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sql = "INSERT INTO products (name, description, price, category, stock_quantity) 
                VALUES (:name, :description, :price, :category, :stock_quantity)";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($sample_products as $product) {
            $stmt->execute($product);
        }
        
        echo "<p>✓ Sample product data inserted successfully</p>";
    } else {
        echo "<p>ℹ Sample products already exist, skipping insertion</p>";
    }
    
    echo "<h2>🎉 Database setup completed successfully!</h2>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Tables created:</strong></p>";
    echo "<ul>";
    echo "<li>transactions - for storing payment transactions</li>";
    echo "<li>users - for user management (future use)</li>";
    echo "<li>products - for product catalog (future use)</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test the transaction page: <a href='transaction.html'>transaction.html</a></li>";
    echo "<li>View transaction history: <a href='transaction_history.php'>transaction_history.php</a></li>";
    echo "<li>Make sure your web server supports PHP and MySQL</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h2>❌ Database setup failed!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check if the username and password are correct</li>";
    echo "<li>Ensure you have permission to create databases</li>";
    echo "<li>If using XAMPP, make sure Apache and MySQL services are started</li>";
    echo "</ul>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 2rem;
    background: #f7fafc;
}

h1 {
    color: #2d3748;
    border-bottom: 3px solid #667eea;
    padding-bottom: 0.5rem;
}

h2 {
    color: #4a5568;
    margin-top: 2rem;
}

p {
    margin: 0.5rem 0;
    padding: 0.5rem;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #48bb78;
}

ul {
    background: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    margin: 1rem 0;
}

li {
    margin: 0.5rem 0;
}

a {
    color: #667eea;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style> 