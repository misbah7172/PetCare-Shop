<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== CURRENT USERS TABLE STRUCTURE ===\n";
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "Field: " . $column['Field'] . " | Type: " . $column['Type'] . " | Null: " . $column['Null'] . " | Key: " . $column['Key'] . " | Default: " . $column['Default'] . " | Extra: " . $column['Extra'] . "\n";
    }
    
    echo "\n=== CHECKING IF TABLE EXISTS ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $exists = $stmt->fetch();
    echo $exists ? "Table 'users' exists\n" : "Table 'users' does NOT exist\n";
    
    echo "\n=== SAMPLE DATA (if any) ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total rows: " . $count['count'] . "\n";
    
    if ($count['count'] > 0) {
        $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            print_r($row);
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
