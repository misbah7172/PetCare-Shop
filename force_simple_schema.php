<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
      echo "=== FORCING DATABASE SCHEMA CHANGE ===\n";
    
    // Disable foreign key checks temporarily
    echo "Disabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop the existing users table completely
    echo "Dropping existing users table...\n";
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "Table dropped successfully.\n";
    
    // Create the new simplified users table
    echo "Creating new simplified users table...\n";
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
      $pdo->exec($sql);
    echo "New table created successfully.\n";
    
    // Re-enable foreign key checks
    echo "Re-enabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verify the new structure
    echo "\n=== NEW TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "Field: " . $column['Field'] . " | Type: " . $column['Type'] . " | Null: " . $column['Null'] . " | Key: " . $column['Key'] . " | Default: " . $column['Default'] . " | Extra: " . $column['Extra'] . "\n";
    }
    
    echo "\n✅ DATABASE SCHEMA SUCCESSFULLY UPDATED TO SIMPLIFIED VERSION!\n";
    echo "Table now contains only: id, name, username, password, email, created_at\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
