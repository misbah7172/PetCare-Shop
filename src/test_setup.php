<?php
// Simple database test setup
require_once __DIR__ . '/../config/database.php';

try {
    // Create database connection without specifying database name
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pawconnect_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE pawconnect_db");
    
    echo "✅ Database created successfully<br>";
    
    // Test creating a simple table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "✅ Test table created successfully<br>";
    
    // Try to create the pets table specifically
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(100) NOT NULL,
            species ENUM('dog', 'cat', 'bird', 'rabbit', 'fish', 'reptile', 'hamster', 'guinea_pig', 'other') NOT NULL,
            breed VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "✅ Pets table created successfully<br>";
    
    // Try to create the index
    $pdo->exec("CREATE INDEX idx_pets_species ON pets(species)");
    
    echo "✅ Index created successfully<br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
