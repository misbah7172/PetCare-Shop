<?php
// Test Dual Login System
// PawConnect - Testing Main Website vs Community Login

session_start();

// Database connection
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color: green;'>✅ Database connection successful</div>";
} catch(PDOException $e) {
    die("<div style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</div>");
}

// Test results array
$testResults = [];

// Test 1: Check if users table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $testResults[] = "✅ Users table exists";
    } else {
        $testResults[] = "❌ Users table does not exist";
    }
} catch (Exception $e) {
    $testResults[] = "❌ Error checking users table: " . $e->getMessage();
}

// Test 2: Check if community_users table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_users'");
    if ($stmt->rowCount() > 0) {
        $testResults[] = "✅ Community users table exists";
    } else {
        $testResults[] = "❌ Community users table does not exist";
    }
} catch (Exception $e) {
    $testResults[] = "❌ Error checking community_users table: " . $e->getMessage();
}

// Test 3: Check table structures
try {
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredUserColumns = ['id', 'username', 'email', 'password', 'auth_token', 'token_expiry', 'created_at'];
    $missingUserColumns = array_diff($requiredUserColumns, $userColumns);
    
    if (empty($missingUserColumns)) {
        $testResults[] = "✅ Users table has all required columns";
    } else {
        $testResults[] = "❌ Users table missing columns: " . implode(', ', $missingUserColumns);
    }
} catch (Exception $e) {
    $testResults[] = "❌ Error checking users table structure: " . $e->getMessage();
}

try {
    $stmt = $pdo->query("DESCRIBE community_users");
    $communityColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredCommunityColumns = ['id', 'user_id', 'username', 'display_name', 'bio', 'experience_level', 'created_at'];
    $missingCommunityColumns = array_diff($requiredCommunityColumns, $communityColumns);
    
    if (empty($missingCommunityColumns)) {
        $testResults[] = "✅ Community users table has all required columns";
    } else {
        $testResults[] = "❌ Community users table missing columns: " . implode(', ', $missingCommunityColumns);
    }
} catch (Exception $e) {
    $testResults[] = "❌ Error checking community_users table structure: " . $e->getMessage();
}

// Test 4: Check if test users exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    $testResults[] = "📊 Total users in database: $userCount";
} catch (Exception $e) {
    $testResults[] = "❌ Error counting users: " . $e->getMessage();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM community_users");
    $communityUserCount = $stmt->fetchColumn();
    $testResults[] = "📊 Total community users in database: $communityUserCount";
} catch (Exception $e) {
    $testResults[] = "❌ Error counting community users: " . $e->getMessage();
}

// Test 5: Check sample users
try {
    $stmt = $pdo->query("SELECT u.username, u.email, cu.display_name, cu.experience_level 
                        FROM users u 
                        LEFT JOIN community_users cu ON u.id = cu.user_id 
                        LIMIT 5");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sampleUsers)) {
        $testResults[] = "✅ Sample users found:";
        foreach ($sampleUsers as $user) {
            $communityStatus = $user['display_name'] ? "Community User" : "Main Website Only";
            $testResults[] = "   - {$user['username']} ({$user['email']}) - $communityStatus";
        }
    } else {
        $testResults[] = "⚠️ No users found in database";
    }
} catch (Exception $e) {
    $testResults[] = "❌ Error fetching sample users: " . $e->getMessage();
}

// Test 6: Check login files exist
$loginFiles = [
    'register.php' => 'Main Website Registration',
    'login.php' => 'Main Website Login',
    'community_register.php' => 'Community Registration',
    'community_login.php' => 'Community Login'
];

foreach ($loginFiles as $file => $description) {
    if (file_exists($file)) {
        $testResults[] = "✅ $description file exists ($file)";
    } else {
        $testResults[] = "❌ $description file missing ($file)";
    }
}

// Test 7: Check feature pages exist
$featurePages = [
    'pet_corner.html' => 'Pet Corner Page',
    'pet_community.html' => 'Pet Community Page',
    'javascripts/pet_corner.js' => 'Pet Corner JavaScript',
    'javascripts/pet_community.js' => 'Pet Community JavaScript'
];

foreach ($featurePages as $file => $description) {
    if (file_exists($file)) {
        $testResults[] = "✅ $description file exists ($file)";
    } else {
        $testResults[] = "❌ $description file missing ($file)";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dual Login System Test - PawConnect</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-section h2 {
            color: #555;
            margin-top: 0;
        }
        .test-result {
            margin: 10px 0;
            padding: 8px;
            border-radius: 3px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .login-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        .login-card {
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }
        .login-card h3 {
            margin-top: 0;
            color: #333;
        }
        .login-card p {
            color: #666;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .summary {
            background: #e9ecef;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .summary h3 {
            margin-top: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐾 PawConnect Dual Login System Test</h1>
        
        <div class="test-section">
            <h2>System Overview</h2>
            <p>This test verifies the dual login system for PawConnect:</p>
            <ul>
                <li><strong>Main Website Login:</strong> Access to shop, vet appointments, pet adoption, donations, etc.</li>
                <li><strong>Community Login:</strong> Special access to Pet Corner and Pet Community features</li>
            </ul>
        </div>

        <div class="test-section">
            <h2>Test Results</h2>
            <?php foreach ($testResults as $result): ?>
                <div class="test-result <?php 
                    if (strpos($result, '✅') !== false) echo 'success';
                    elseif (strpos($result, '❌') !== false) echo 'error';
                    elseif (strpos($result, '⚠️') !== false) echo 'warning';
                    else echo 'info';
                ?>">
                    <?php echo $result; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="login-links">
            <div class="login-card">
                <h3>🌐 Main Website Access</h3>
                <p>Register and login for general website features like shop, vet appointments, pet adoption, and donations.</p>
                <a href="register.php" class="btn">Main Registration</a>
                <a href="login.php" class="btn btn-secondary">Main Login</a>
            </div>
            
            <div class="login-card">
                <h3>🌟 Community Access</h3>
                <p>Special registration and login for Pet Corner and Pet Community features.</p>
                <a href="community_register.php" class="btn">Community Registration</a>
                <a href="community_login.php" class="btn btn-secondary">Community Login</a>
            </div>
        </div>

        <div class="summary">
            <h3>How the Dual System Works:</h3>
            <ol>
                <li><strong>Main Website Registration:</strong> Creates a user account for general website access</li>
                <li><strong>Community Registration:</strong> Creates both a user account AND a community profile</li>
                <li><strong>Main Login:</strong> Access to shop, vet appointments, pet adoption, donations</li>
                <li><strong>Community Login:</strong> Access to Pet Corner and Pet Community features</li>
                <li><strong>Separation:</strong> Users with only main website accounts cannot access community features</li>
            </ol>
        </div>

        <div class="test-section">
            <h2>Feature Pages</h2>
            <p>Test the special community features:</p>
            <a href="pet_corner.html" class="btn">Pet Corner</a>
            <a href="pet_community.html" class="btn">Pet Community</a>
        </div>
    </div>
</body>
</html> 