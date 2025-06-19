<?php
// Test Unified Pet Corner and Pet Community System
// PawConnect Unified System Testing

echo "<h1>PawConnect Unified System Test</h1>";
echo "<p><strong>This test verifies that Pet Corner and Pet Community use the same unified user profile system.</strong></p>";

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

// Test unified users table
echo "<h2>2. Unified Users Table Test</h2>";
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

// Test unified community_users table
echo "<h2>3. Unified Community Users Table Test</h2>";
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

// Test unified community_pets table
echo "<h2>4. Unified Community Pets Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_pets'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Community pets table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE community_pets");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Community pets table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking community_pets table: " . $e->getMessage() . "</p>";
}

// Test unified community_posts table
echo "<h2>5. Unified Community Posts Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_posts'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Community posts table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE community_posts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Community posts table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking community_posts table: " . $e->getMessage() . "</p>";
}

// Test existing users and their unified profiles
echo "<h2>6. Unified User Profiles Test</h2>";
try {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, cu.display_name, cu.bio, cu.experience_level,
               (SELECT COUNT(*) FROM community_pets WHERE user_id = u.id) as pet_count,
               (SELECT COUNT(*) FROM community_posts WHERE user_id = u.id) as post_count
        FROM users u
        LEFT JOIN community_users cu ON u.id = cu.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total users with unified profiles: " . count($users) . "</p>";
    
    if (count($users) > 0) {
        echo "<p>User profiles:</p><ul>";
        foreach ($users as $user) {
            echo "<li><strong>{$user['username']}</strong> ({$user['email']})";
            echo "<br>Display Name: {$user['display_name']}";
            echo "<br>Bio: " . ($user['bio'] ?: 'No bio');
            echo "<br>Experience: {$user['experience_level']}";
            echo "<br>Pets: {$user['pet_count']}";
            echo "<br>Posts: {$user['post_count']}";
            echo "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking user profiles: " . $e->getMessage() . "</p>";
}

// Test unified posts (both Pet Corner and Pet Community)
echo "<h2>7. Unified Posts Test</h2>";
try {
    $stmt = $pdo->query("
        SELECT p.*, u.username, cu.display_name,
               (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = p.id) as reaction_count,
               (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) as comment_count
        FROM community_posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN community_users cu ON u.id = cu.user_id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Recent unified posts (Pet Corner + Pet Community):</p><ul>";
    foreach ($posts as $post) {
        $authorName = $post['display_name'] ?: $post['username'];
        $content = strlen($post['content']) > 100 ? substr($post['content'], 0, 100) . '...' : $post['content'];
        
        echo "<li><strong>{$post['title']}</strong> by {$authorName}";
        echo "<br>Type: {$post['type']}";
        echo "<br>Content: {$content}";
        echo "<br>Reactions: {$post['reaction_count']}, Comments: {$post['comment_count']}";
        echo "<br>Created: {$post['created_at']}";
        echo "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking posts: " . $e->getMessage() . "</p>";
}

// Test unified pet profiles
echo "<h2>8. Unified Pet Profiles Test</h2>";
try {
    $stmt = $pdo->query("
        SELECT cp.*, u.username, cu.display_name
        FROM community_pets cp
        JOIN users u ON cp.user_id = u.id
        LEFT JOIN community_users cu ON u.id = cu.user_id
        ORDER BY cp.created_at DESC
    ");
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Unified pet profiles:</p><ul>";
    foreach ($pets as $pet) {
        $ownerName = $pet['display_name'] ?: $pet['username'];
        echo "<li><strong>{$pet['pet_name']}</strong> ({$pet['pet_type']})";
        echo "<br>Owner: {$ownerName}";
        echo "<br>Breed: " . ($pet['breed'] ?: 'Unknown');
        echo "<br>Age: " . ($pet['age'] ? "{$pet['age']} {$pet['age_unit']}" : 'Unknown');
        echo "<br>Gender: {$pet['gender']}";
        echo "<br>Description: " . ($pet['description'] ?: 'No description');
        echo "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking pet profiles: " . $e->getMessage() . "</p>";
}

// Test API endpoints
echo "<h2>9. API Endpoints Test</h2>";
$apiFiles = ['pet_corner_api.php', 'pet_community_api.php'];
foreach ($apiFiles as $apiFile) {
    if (file_exists($apiFile)) {
        echo "<p style='color: green;'>✓ {$apiFile} exists</p>";
    } else {
        echo "<p style='color: red;'>✗ {$apiFile} does not exist</p>";
    }
}

// Test unified registration
echo "<h2>10. Unified Registration Test</h2>";
echo "<form method='POST' action='test_unified_system.php'>";
echo "<p><strong>Test Unified Registration:</strong></p>";
echo "<p>Username: <input type='text' name='test_username' value='unifieduser" . rand(1000, 9999) . "' required></p>";
echo "<p>Email: <input type='email' name='test_email' value='unified" . rand(1000, 9999) . "@example.com' required></p>";
echo "<p>Password: <input type='password' name='test_password' value='unifiedpass123' required></p>";
echo "<p><input type='submit' name='test_unified_register' value='Test Unified Registration'></p>";
echo "</form>";

// Handle unified registration test
if (isset($_POST['test_unified_register'])) {
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
    
    // If no errors, create unified user
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate auth token
            $authToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Insert user into main users table
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, auth_token, token_expiry, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashedPassword, $authToken, $tokenExpiry]);
            
            $userId = $pdo->lastInsertId();
            
            // Create unified community user profile (works for both Pet Corner and Pet Community)
            $stmt = $pdo->prepare("INSERT INTO community_users (user_id, username, display_name, experience_level) VALUES (?, ?, ?, 'beginner')");
            $stmt->execute([$userId, $username, $username]);
            
            // Commit transaction
            $pdo->commit();
            
            echo "<p style='color: green;'>✓ Unified registration successful!</p>";
            echo "<p>User ID: $userId</p>";
            echo "<p>Username: $username</p>";
            echo "<p>Email: $email</p>";
            echo "<p>Auth Token: " . substr($authToken, 0, 20) . "...</p>";
            echo "<p><strong>This user can now access both Pet Corner and Pet Community with the same profile!</strong></p>";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            echo "<p style='color: red;'>✗ Unified registration failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Validation errors:</p><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
}

// Instructions
echo "<h2>11. How the Unified System Works</h2>";
echo "<p><strong>Unified Profile System:</strong></p>";
echo "<ul>";
echo "<li><strong>One Registration:</strong> Users register once and get access to both Pet Corner and Pet Community</li>";
echo "<li><strong>Shared Profile:</strong> The same user profile works across both features</li>";
echo "<li><strong>Unified Pets:</strong> Pet profiles are shared between both systems</li>";
echo "<li><strong>Unified Posts:</strong> All posts (photos, stories, discussions) are in one system</li>";
echo "<li><strong>Shared Authentication:</strong> Login once, access both features</li>";
echo "</ul>";

echo "<h2>12. Next Steps</h2>";
echo "<p><strong>To test the unified system:</strong></p>";
echo "<ol>";
echo "<li>Make sure XAMPP Apache and MySQL services are running</li>";
echo "<li>Run the unified database setup: <code>setup_unified_database.sql</code></li>";
echo "<li>Try the unified registration test above</li>";
echo "<li>Test both Pet Corner and Pet Community with the same account</li>";
echo "</ol>";

echo "<p><strong>To access the features:</strong></p>";
echo "<p><a href='login.html' target='_blank'>Unified Login/Registration Page</a></p>";
echo "<p><a href='pet_corner.html' target='_blank'>Pet Corner (Unified)</a></p>";
echo "<p><a href='pet_community.html' target='_blank'>Pet Community (Unified)</a></p>";

echo "<h2>13. Benefits of Unified System</h2>";
echo "<ul>";
echo "<li><strong>Simplified User Experience:</strong> One account for all features</li>";
echo "<li><strong>Consistent Data:</strong> Pet profiles and posts are shared</li>";
echo "<li><strong>Easier Management:</strong> Single user management system</li>";
echo "<li><strong>Better Integration:</strong> Seamless experience between features</li>";
echo "<li><strong>Reduced Complexity:</strong> No duplicate user systems</li>";
echo "</ul>";

echo "<h2>14. Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Database issues:</strong> Run the unified setup script</li>";
echo "<li><strong>Registration fails:</strong> Check for validation errors above</li>";
echo "<li><strong>Login issues:</strong> Make sure you're using the unified login system</li>";
echo "<li><strong>Profile not showing:</strong> Check that community_users table has the user record</li>";
echo "<li><strong>API errors:</strong> Verify both API files exist and are accessible</li>";
echo "</ul>";
?> 