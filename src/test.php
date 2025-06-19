<?php
echo "<h1>PawConnect Test Page</h1>";
echo "<p>✅ PHP is working correctly</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Count users
        $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $countStmt->fetchColumn();
        echo "<p>📊 Total users: $userCount</p>";
    } else {
        echo "<p>⚠️ Users table not found. Run setup_database.php first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure MySQL is running and run setup_database.php</p>";
}

echo "<hr>";
echo "<h2>Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='setup_database.php'>Setup Database</a></li>";
echo "<li><a href='../public/index.html'>Go to Website</a></li>";
echo "<li><a href='../public/login.html'>Login Page</a></li>";
echo "<li><a href='../public/register.html'>Register Page</a></li>";
echo "</ul>";
?>
