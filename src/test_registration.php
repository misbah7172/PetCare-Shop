<?php
// Test Registration System
// PawConnect Registration Testing

echo "<h1>PawConnect Registration System Test</h1>";

// Test database connection
echo "<h2>1. Database Connection Test</h2>";
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test users table
echo "<h2>2. Users Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking users table: " . $e->getMessage() . "</p>";
}

// Test community_users table
echo "<h2>3. Community Users Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Community users table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE community_users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Community users table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking community_users table: " . $e->getMessage() . "</p>";
}

// Test existing users
echo "<h2>4. Existing Users Test</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total users in database: {$result['count']}</p>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Recent users:</p><ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']} - Username: {$user['username']} - Email: {$user['email']} - Created: {$user['created_at']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking existing users: " . $e->getMessage() . "</p>";
}

// Test registration functionality
echo "<h2>5. Registration Test</h2>";
echo "<form method='POST' action='test_registration.php'>";
echo "<p><strong>Test Registration:</strong></p>";
echo "<p>Username: <input type='text' name='test_username' value='testuser" . rand(1000, 9999) . "' required></p>";
echo "<p>Email: <input type='email' name='test_email' value='test" . rand(1000, 9999) . "@example.com' required></p>";
echo "<p>Password: <input type='password' name='test_password' value='testpass123' required></p>";
echo "<p><input type='submit' name='test_register' value='Test Registration'></p>";
echo "</form>";

// Handle test registration
if (isset($_POST['test_register'])) {
    $username = trim($_POST['test_username']);
    $email = trim($_POST['test_email']);
    $password = $_POST['test_password'];
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate auth token
            $authToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, auth_token, token_expiry, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashedPassword, $authToken, $tokenExpiry]);
            
            $userId = $pdo->lastInsertId();
            
            // Create community user profile
            $stmt = $pdo->prepare("INSERT INTO community_users (user_id, username, display_name, experience_level) VALUES (?, ?, ?, 'beginner')");
            $stmt->execute([$userId, $username, $username]);
            
            echo "<p style='color: green;'>✓ Test registration successful!</p>";
            echo "<p>User ID: $userId</p>";
            echo "<p>Username: $username</p>";
            echo "<p>Email: $email</p>";
            echo "<p>Auth Token: " . substr($authToken, 0, 20) . "...</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Test registration failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Validation errors:</p><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
}

// Test login functionality
echo "<h2>6. Login Test</h2>";
echo "<form method='POST' action='test_registration.php'>";
echo "<p><strong>Test Login:</strong></p>";
echo "<p>Username/Email: <input type='text' name='login_username' placeholder='Enter username or email' required></p>";
echo "<p>Password: <input type='password' name='login_password' placeholder='Enter password' required></p>";
echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
echo "</form>";

// Handle test login
if (isset($_POST['test_login'])) {
    $username = trim($_POST['login_username']);
    $password = $_POST['login_password'];
    
    try {
        // Check if user exists and password is correct
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✓ Test login successful!</p>";
            echo "<p>User ID: {$user['id']}</p>";
            echo "<p>Username: {$user['username']}</p>";
            echo "<p>Email: {$user['email']}</p>";
            echo "<p>Role: {$user['role']}</p>";
            
            // Generate new auth token
            $authToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Update auth token
            $stmt = $pdo->prepare("UPDATE users SET auth_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$authToken, $tokenExpiry, $user['id']]);
            
            echo "<p>New Auth Token: " . substr($authToken, 0, 20) . "...</p>";
            
        } else {
            echo "<p style='color: red;'>✗ Test login failed: Invalid username/email or password</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test login error: " . $e->getMessage() . "</p>";
    }
}

// Test file permissions
echo "<h2>7. File Permissions Test</h2>";
$files = ['register.php', 'login.php', 'pet_community_api.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p style='color: green;'>✓ $file exists and is readable</p>";
        } else {
            echo "<p style='color: red;'>✗ $file exists but is not readable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ $file does not exist</p>";
    }
}

// Instructions
echo "<h2>8. Next Steps</h2>";
echo "<p><strong>To test the registration system:</strong></p>";
echo "<ol>";
echo "<li>Make sure XAMPP Apache and MySQL services are running</li>";
echo "<li>Access this test page at: <code>http://localhost/pawconnect/test_registration.php</code></li>";
echo "<li>Try the test registration form above</li>";
echo "<li>Try the test login form above</li>";
echo "<li>If tests pass, try registering through the main login page</li>";
echo "</ol>";

echo "<p><strong>To access the main registration page:</strong></p>";
echo "<p><a href='login.html' target='_blank'>Open Login/Registration Page</a></p>";

echo "<p><strong>To access Pet Community:</strong></p>";
echo "<p><a href='pet_community.html' target='_blank'>Open Pet Community Page</a></p>";

echo "<h2>9. Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Database connection fails:</strong> Make sure MySQL is running in XAMPP</li>";
echo "<li><strong>Tables don't exist:</strong> Run the database setup scripts</li>";
echo "<li><strong>Registration fails:</strong> Check for validation errors above</li>";
echo "<li><strong>Login fails:</strong> Make sure you're using correct credentials</li>";
echo "<li><strong>File not found:</strong> Make sure all PHP files are in the correct directory</li>";
echo "</ul>";
?> 