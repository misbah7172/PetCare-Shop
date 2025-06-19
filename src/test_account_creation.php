<?php
// Test Account Creation
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Account Creation - PawConnect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .btn { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5a6fd8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐾 Pet Corner Account Creation Test</h1>
        
        <?php
        // Database connection test
        echo "<div class='test-section info'>";
        echo "<h2>Database Connection Test</h2>";
        
        $host = 'localhost';
        $dbname = 'pawconnect';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p class='success'>✅ Database connection successful!</p>";
            
            // Check if users table exists
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            echo "<p>✅ Users table exists with $userCount records</p>";
            
        } catch(PDOException $e) {
            echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
            echo "<p>Please make sure:</p>";
            echo "<ul>";
            echo "<li>XAMPP is running (Apache & MySQL)</li>";
            echo "<li>Database 'pawconnect' exists</li>";
            echo "<li>You've imported setup_pet_corner.sql</li>";
            echo "</ul>";
        }
        echo "</div>";
        
        // Test account creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<div class='test-section'>";
            echo "<h2>Account Creation Test Results</h2>";
            
            $testUsername = $_POST['username'] ?? '';
            $testEmail = $_POST['email'] ?? '';
            $testPassword = $_POST['password'] ?? '';
            
            if (empty($testUsername) || empty($testEmail) || empty($testPassword)) {
                echo "<p class='error'>❌ All fields are required</p>";
            } else {
                try {
                    // Check if user already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                    $stmt->execute([$testEmail, $testUsername]);
                    
                    if ($stmt->fetch()) {
                        echo "<p class='error'>❌ User already exists with this email or username</p>";
                    } else {
                        // Create new user
                        $passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                        
                        if ($stmt->execute([$testUsername, $testEmail, $passwordHash])) {
                            $userId = $pdo->lastInsertId();
                            echo "<p class='success'>✅ Account created successfully!</p>";
                            echo "<p><strong>User ID:</strong> $userId</p>";
                            echo "<p><strong>Username:</strong> $testUsername</p>";
                            echo "<p><strong>Email:</strong> $testEmail</p>";
                            
                            // Test login
                            $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
                            $stmt->execute([$testEmail]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user && password_verify($testPassword, $user['password_hash'])) {
                                echo "<p class='success'>✅ Login test successful!</p>";
                            } else {
                                echo "<p class='error'>❌ Login test failed</p>";
                            }
                        } else {
                            echo "<p class='error'>❌ Failed to create account</p>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
                }
            }
            echo "</div>";
        }
        ?>
        
        <div class="test-section info">
            <h2>Test Account Creation</h2>
            <p>Fill out the form below to test account creation:</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required placeholder="testuser123">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required placeholder="test@example.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="password123">
                </div>
                
                <button type="submit" class="btn">Create Test Account</button>
            </form>
        </div>
        
        <div class="test-section">
            <h2>API Test</h2>
            <p>Test the registration API endpoint:</p>
            <pre>
POST pet_corner_api.php?action=register
Content-Type: application/json

{
    "username": "testuser",
    "email": "test@example.com", 
    "password": "password123"
}
            </pre>
        </div>
        
        <div class="test-section">
            <h2>Next Steps</h2>
            <p>If the tests pass, you can:</p>
            <ul>
                <li>Visit <a href="pet_corner.html">Pet Corner</a> to test the full registration flow</li>
                <li>Create a real account and pet profile</li>
                <li>Test photo uploads and story creation</li>
            </ul>
        </div>
    </div>
</body>
</html> 