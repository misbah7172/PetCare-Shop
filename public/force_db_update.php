<?php
// Force database restructure
echo "<h1>🔧 Force Database Restructure</h1>";

$host = 'localhost';
$username = 'root';
$password = '';
$database_name = 'pawconnect_db';

try {
    // Connect directly to MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color: green;'>✅ Connected to MySQL</p>";
    
    // Create database if it doesn't exist
    echo "<h2>Step 1: Database Creation</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Database '$database_name' ready</p>";
    
    // Use the database
    $pdo->exec("USE `$database_name`");
    echo "<p style='color: green;'>✅ Using database '$database_name'</p>";
    
    // Drop existing users table completely
    echo "<h2>Step 2: Removing Old Table</h2>";
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p style='color: green;'>✅ Old users table removed</p>";
    
    // Create new simplified users table
    echo "<h2>Step 3: Creating New Simplified Table</h2>";
    $createSQL = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_username (username),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createSQL);
    echo "<p style='color: green;'>✅ New simplified users table created</p>";
    
    // Verify the structure
    echo "<h2>Step 4: Verifying New Structure</h2>";
    $structureStmt = $pdo->query("DESCRIBE users");
    $columns = $structureStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #e6ffe6;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Create default admin user
    echo "<h2>Step 5: Creating Default Admin</h2>";
    $adminName = 'Administrator';
    $adminUsername = 'admin';
    $adminEmail = 'admin@pawconnect.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $insertStmt = $pdo->prepare("INSERT INTO users (name, username, password, email, created_at) VALUES (?, ?, ?, ?, NOW())");
    
    if ($insertStmt->execute([$adminName, $adminUsername, $adminPassword, $adminEmail])) {
        echo "<p style='color: green;'>✅ Default admin user created</p>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>🔑 Default Admin Credentials:</h3>";
        echo "<p><strong>Name:</strong> Administrator</p>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Email:</strong> admin@pawconnect.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create admin user</p>";
    }
    
    // Final verification
    echo "<h2>✅ Database Restructure Complete!</h2>";
    echo "<div style='background: #e6ffe6; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: green;'>🎉 Success!</h3>";
    echo "<p><strong>New Database Schema:</strong></p>";
    echo "<ul>";
    echo "<li>✅ id (auto increment)</li>";
    echo "<li>✅ name (full name)</li>";
    echo "<li>✅ username (unique)</li>";
    echo "<li>✅ password (hashed)</li>";
    echo "<li>✅ email (unique)</li>";
    echo "<li>✅ created_at (timestamp)</li>";
    echo "</ul>";
    echo "<p>The database is now ready for registration!</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Database Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Ensure XAMPP MySQL is running</li>";
    echo "<li>Check MySQL credentials</li>";
    echo "<li>Try accessing phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>Open phpMyAdmin</a></li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Unexpected Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='verify_db.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔍 Verify Database</a></p>";
echo "<p><a href='login.html' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Test Registration</a></p>";
?>
