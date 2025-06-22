<?php
// Apply enhanced schema changes
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 Applying Enhanced Schema Changes</h2>";
    
    // Read and execute the enhanced schema
    $schema = file_get_contents(__DIR__ . '/../database/enhanced_features.sql');
    
    // Split into individual statements
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            try {
                $pdo->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ Warning: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    
    echo "<h3>✅ Enhanced schema applied successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
