<?php
// Ensure test user exists for the system to work
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Create users table
        $pdo->exec("
            CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    // Check if we have at least one user
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Create a default user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, email, password) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            'Demo User',
            'demo',
            'demo@pawconnect.com',
            password_hash('demo123', PASSWORD_DEFAULT)
        ]);
        
        echo "Demo user created successfully!";
    } else {
        echo "Users table already has data.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
