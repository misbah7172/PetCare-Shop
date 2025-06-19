<?php
/**
 * Admin Panel Database Setup Script
 * This script creates all necessary tables and sample data for the admin panel
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Setting up Admin Panel Database...</h2>\n";
    
    // Read and execute the SQL setup file
    $sqlFile = __DIR__ . '/../database/admin_panel_setup.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL setup file not found: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split the SQL into individual statements
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "✓ Executed SQL statement successfully\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n<h3>Setup Complete!</h3>\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    // Verify admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "\n<h3>Admin User Found:</h3>\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "\nDefault password: 'password' (please change after first login)\n";
    } else {
        echo "\n<h3>Warning: No admin user found!</h3>\n";
        echo "Please create an admin user manually or check the setup.\n";
    }
    
    // Check table counts
    echo "\n<h3>Database Statistics:</h3>\n";
    
    $tables = ['users', 'pets', 'adoptions', 'veterinarians', 'shop_products', 'support_tickets'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "$table: $count records\n";
        } catch (Exception $e) {
            echo "$table: Error - " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <pre>
    <h1>PawConnect Admin Panel Setup</h1>
    
    <p>This script sets up the database tables required for the admin panel functionality.</p>
    
    <p><strong>Next Steps:</strong></p>
    <ol>
        <li>Navigate to <a href="../public/dashboard.html">Dashboard</a></li>
        <li>Login with admin credentials</li>
        <li>Access the Admin Panel from the dashboard</li>
        <li>Change the default admin password</li>
    </ol>
    
    <p><strong>Default Admin Login:</strong></p>
    <ul>
        <li>Username: admin</li>
        <li>Email: admin@pawconnect.com</li>
        <li>Password: password</li>
    </ul>
    
    <p><a href="../public/dashboard.html">Go to Dashboard →</a></p>
    </pre>
</body>
</html>
