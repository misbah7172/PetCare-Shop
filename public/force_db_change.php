<?php
// Direct database modification - Force change the users table
echo "<h1>🔧 Direct Database Modification</h1>";

try {
    // Connect directly without using the Database class
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "<p style='color: green;'>✅ Connected to MySQL server</p>";
    
    // Show current databases
    echo "<h2>Step 1: Available Databases</h2>";
    $databases = $pdo->query("SHOW DATABASES")->fetchAll();
    echo "<ul>";
    foreach ($databases as $db) {
        echo "<li>" . $db['Database'] . "</li>";
    }
    echo "</ul>";
    
    // Create database if needed
    echo "<h2>Step 2: Ensure Database Exists</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pawconnect_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Database pawconnect_db created/verified</p>";
    
    // Use the database
    $pdo->exec("USE pawconnect_db");
    echo "<p style='color: green;'>✅ Using pawconnect_db</p>";
    
    // Show current tables
    echo "<h2>Step 3: Current Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    if (!empty($tables)) {
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li>$tableName</li>";
        }
        echo "</ul>";
        
        // If users table exists, show its current structure
        $userTableExists = false;
        foreach ($tables as $table) {
            if (array_values($table)[0] === 'users') {
                $userTableExists = true;
                break;
            }
        }
        
        if ($userTableExists) {
            echo "<h3>Current users table structure:</h3>";
            $structure = $pdo->query("DESCRIBE users")->fetchAll();
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($structure as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>No tables found in database</p>";
    }
    
    // Force drop and recreate users table
    echo "<h2>Step 4: Force Recreate users Table</h2>";
    
    // Drop existing users table
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "<p style='color: orange;'>⚠️ Dropped existing users table</p>";
    
    // Create new simplified users table
    $createSQL = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createSQL);
    echo "<p style='color: green;'>✅ Created new simplified users table</p>";
    
    // Verify the new structure
    echo "<h2>Step 5: Verify New Structure</h2>";
    $newStructure = $pdo->query("DESCRIBE users")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; background: #e6ffe6;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($newStructure as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Add default admin user
    echo "<h2>Step 6: Add Default Admin User</h2>";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare("INSERT INTO users (name, username, password, email) VALUES (?, ?, ?, ?)");
    
    if ($insertStmt->execute(['Administrator', 'admin', $adminPassword, 'admin@pawconnect.com'])) {
        echo "<p style='color: green;'>✅ Default admin user created</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create admin user</p>";
    }
    
    // Show final result
    echo "<h2>✅ SUCCESS! Database Modified</h2>";
    echo "<div style='background: #e6ffe6; padding: 20px; border-radius: 8px;'>";
    echo "<h3>New Database Schema:</h3>";
    echo "<ul>";
    echo "<li>id - INT AUTO_INCREMENT PRIMARY KEY</li>";
    echo "<li>name - VARCHAR(100) NOT NULL</li>";
    echo "<li>username - VARCHAR(50) UNIQUE NOT NULL</li>";
    echo "<li>password - VARCHAR(255) NOT NULL</li>";
    echo "<li>email - VARCHAR(100) UNIQUE NOT NULL</li>";
    echo "<li>created_at - TIMESTAMP DEFAULT CURRENT_TIMESTAMP</li>";
    echo "</ul>";
    echo "<p><strong>Default Admin:</strong> username: admin, password: admin123</p>";
    echo "</div>";
    
    // Test registration immediately
    echo "<h2>Step 7: Test Registration Query</h2>";
    echo "<p>Testing if registration query will work...</p>";
    
    $testName = "Test User";
    $testUsername = "testuser_" . time();
    $testEmail = "test_" . time() . "@example.com";
    $testPassword = password_hash("test123", PASSWORD_DEFAULT);
    
    $testStmt = $pdo->prepare("INSERT INTO users (name, username, password, email) VALUES (?, ?, ?, ?)");
    
    if ($testStmt->execute([$testName, $testUsername, $testPassword, $testEmail])) {
        echo "<p style='color: green;'>✅ Test registration successful!</p>";
        
        // Clean up test user
        $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$testUsername]);
        echo "<p style='color: blue;'>🧹 Test user cleaned up</p>";
    } else {
        echo "<p style='color: red;'>❌ Test registration failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Registration Now</a></p>";
?>
