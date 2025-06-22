<?php
// Check table structure
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=pawconnect_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if pets table exists and its structure
    $stmt = $pdo->query("DESCRIBE pets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Pets table structure:</h3>";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "<br>";
    }
    
    // Check existing indexes
    $stmt = $pdo->query("SHOW INDEX FROM pets");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Existing indexes:</h3>";
    foreach ($indexes as $index) {
        echo $index['Key_name'] . " on " . $index['Column_name'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
