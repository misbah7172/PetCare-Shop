<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS veterinarians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        clinic_name VARCHAR(255) NOT NULL,
        license_number VARCHAR(100) UNIQUE NOT NULL,
        specialization VARCHAR(255),
        experience_years INT DEFAULT 0,
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        consultation_fee DECIMAL(10,2) DEFAULT 0.00,
        rating DECIMAL(3,2) DEFAULT 0.00,
        status ENUM('active', 'inactive', 'pending_verification') DEFAULT 'pending_verification',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($sql);
    echo "Veterinarians table created successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
