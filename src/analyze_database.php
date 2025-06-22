<?php
require_once 'config/database.php';

echo "<h2>Database Table Analysis</h2>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all tables
    $result = $db->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Current Tables:</h3>\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Get table structure
        $columns = $db->query("DESCRIBE $table")->fetchAll();
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
