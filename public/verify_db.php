<?php
// Verify the simplified database structure
echo "<h1>🔍 Database Structure Verification</h1>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>✅ Database Connection Successful</h2>";
    
    // Check if users table exists
    $tablesStmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tablesStmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Users table exists</p>";
        
        // Show current table structure
        echo "<h3>Current Table Structure:</h3>";
        $structureStmt = $pdo->query("DESCRIBE users");
        $columns = $structureStmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $expectedFields = ['id', 'name', 'username', 'password', 'email', 'created_at'];
        $actualFields = [];
        
        foreach ($columns as $column) {
            $actualFields[] = $column['Field'];
            $isExpected = in_array($column['Field'], $expectedFields);
            $bgColor = $isExpected ? '#e6ffe6' : '#ffe6e6';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if structure matches expected
        echo "<h3>Structure Validation:</h3>";
        $missingFields = array_diff($expectedFields, $actualFields);
        $extraFields = array_diff($actualFields, $expectedFields);
        
        if (empty($missingFields) && empty($extraFields)) {
            echo "<p style='color: green; font-weight: bold;'>🎉 Database structure is PERFECT!</p>";
            echo "<p>✅ All expected fields present</p>";
            echo "<p>✅ No extra fields</p>";
        } else {
            if (!empty($missingFields)) {
                echo "<p style='color: red;'>❌ Missing fields: " . implode(', ', $missingFields) . "</p>";
            }
            if (!empty($extraFields)) {
                echo "<p style='color: orange;'>⚠️ Extra fields: " . implode(', ', $extraFields) . "</p>";
            }
        }
        
        // Check current users
        echo "<h3>Current Users:</h3>";
        $usersStmt = $pdo->query("SELECT id, name, username, email, created_at FROM users ORDER BY id");
        $users = $usersStmt->fetchAll();
        
        if (!empty($users)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['name']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No users in database yet.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist!</p>";
        echo "<p><a href='setup_simple_db.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Run Database Setup</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Database Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🚀 Next Steps</h2>";
echo "<p><a href='login.html' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔗 Test Registration</a></p>";
echo "<p><a href='setup_simple_db.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Re-run Database Setup</a></p>";
?>
