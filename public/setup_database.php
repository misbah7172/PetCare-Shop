<?php
// Quick Database Setup for PawConnect
echo "<h1>🛠️ PawConnect Database Setup</h1>";

$host = 'localhost';
$username = 'root';
$password = '';
$database_name = 'pawconnect_db';

try {
    echo "<h2>Step 1: Connecting to MySQL...</h2>";
    
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color: green;'>✅ Connected to MySQL successfully!</p>";
    
    echo "<h2>Step 2: Creating Database...</h2>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Database '$database_name' created/verified!</p>";
    
    // Select the database
    $pdo->exec("USE `$database_name`");
    echo "<p style='color: green;'>✅ Using database '$database_name'</p>";
    
    echo "<h2>Step 3: Creating Users Table...</h2>";
    
    // Create users table
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) DEFAULT '',
        last_name VARCHAR(50) DEFAULT '',
        phone VARCHAR(20) DEFAULT '',
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
        profile_picture VARCHAR(255) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        location VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createUsersTable);
    echo "<p style='color: green;'>✅ Users table created successfully!</p>";
    
    echo "<h2>Step 4: Verifying Setup...</h2>";
    
    // Verify table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<p style='color: green;'>✅ Users table structure verified!</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are any users
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $countStmt->fetch()['count'];
    echo "<p>Current users in database: <strong>$userCount</strong></p>";
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>Database setup completed successfully!</p>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 You can now:</h3>";
    echo "<ul>";
    echo "<li><a href='test_register.php'>✅ Test Registration</a></li>";
    echo "<li><a href='login.html'>✅ Go to Login Page</a></li>";
    echo "<li><a href='database_diagnostic.php'>🔍 View Database Diagnostic</a></li>";
    echo "</ul>";
    echo "</div>";
    
    // Create a default admin user if none exists
    if ($userCount == 0) {
        echo "<h2>Step 5: Creating Default Admin User...</h2>";
        
        $adminUsername = 'admin';
        $adminEmail = 'admin@pawconnect.com';
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertAdmin = $pdo->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, role, status, created_at) 
            VALUES (?, ?, ?, 'Admin', 'User', 'admin', 'active', NOW())
        ");
        
        if ($insertAdmin->execute([$adminUsername, $adminEmail, $adminPassword])) {
            echo "<p style='color: green;'>✅ Default admin user created!</p>";
            echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>Default Admin Credentials:</strong></p>";
            echo "<p>Username: <code>admin</code></p>";
            echo "<p>Password: <code>admin123</code></p>";
            echo "<p><em>⚠️ Please change this password after first login!</em></p>";
            echo "</div>";
        } else {
            echo "<p style='color: orange;'>⚠️ Could not create default admin user</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Database Setup Failed</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check MySQL port (usually 3306)</li>";
    echo "<li>Verify MySQL root password (usually empty for XAMPP)</li>";
    echo "<li>Try accessing phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Unexpected Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
