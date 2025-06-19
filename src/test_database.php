<?php
echo "<h2>Database Table Structure Check</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p>✅ Database connection successful</p>";
    
    // Check if users table exists and get its structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Users Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $requiredFields = ['first_name', 'last_name', 'phone', 'status', 'last_login'];
    $foundFields = [];
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
        
        if (in_array($column['Field'], $requiredFields)) {
            $foundFields[] = $column['Field'];
        }
    }
    echo "</table>";
    
    echo "<h3>Required Fields Check:</h3>";
    foreach ($requiredFields as $field) {
        if (in_array($field, $foundFields)) {
            echo "<p>✅ $field - Found</p>";
        } else {
            echo "<p>❌ $field - Missing</p>";
        }
    }
    
    // Test a simple insert
    echo "<h3>Test Registration Process:</h3>";
    try {
        $testUsername = 'test_' . time();
        $testEmail = 'test_' . time() . '@test.com';
        $testPassword = password_hash('test123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
        ");
        
        $result = $stmt->execute([$testUsername, $testEmail, $testPassword, 'Test', 'User', '1234567890']);
        
        if ($result) {
            echo "<p>✅ Test insert successful</p>";
            
            // Clean up test data
            $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$testUsername]);
            echo "<p>✅ Test data cleaned up</p>";
        } else {
            echo "<p>❌ Test insert failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Test insert error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='setup_quick.php'>Re-run Database Setup</a> | ";
echo "<a href='../public/register.html'>Try Registration</a> | ";
echo "<a href='../public/register.html?debug=1'>Try Registration (Debug Mode)</a>";
?>
