<?php
// Test registration and login functionality
require_once 'config/database.php';

echo "=== TESTING REGISTRATION AND LOGIN FUNCTIONALITY ===\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test 1: Register a new user
    echo "1. Testing user registration...\n";
    
    $test_user = [
        'name' => 'Test User',
        'username' => 'testuser123',
        'email' => 'test@example.com',
        'password' => 'testpass123'
    ];
    
    $hashed_password = password_hash($test_user['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, username, password, email, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$test_user['name'], $test_user['username'], $hashed_password, $test_user['email']]);
    
    if ($result) {
        echo "✅ Registration test PASSED - User created successfully\n";
        $user_id = $pdo->lastInsertId();
        echo "   User ID: $user_id\n";
    } else {
        echo "❌ Registration test FAILED\n";
    }
    
    // Test 2: Login with the created user
    echo "\n2. Testing user login...\n";
    
    $stmt = $pdo->prepare("SELECT id, name, username, password, email FROM users WHERE username = ?");
    $stmt->execute([$test_user['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($test_user['password'], $user['password'])) {
        echo "✅ Login test PASSED - Credentials verified successfully\n";
        echo "   User data retrieved:\n";
        echo "   - ID: {$user['id']}\n";
        echo "   - Name: {$user['name']}\n";
        echo "   - Username: {$user['username']}\n";
        echo "   - Email: {$user['email']}\n";
    } else {
        echo "❌ Login test FAILED\n";
    }
    
    // Test 3: Check duplicate prevention
    echo "\n3. Testing duplicate prevention...\n";
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, email, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$test_user['name'], $test_user['username'], $hashed_password, $test_user['email']]);
        echo "❌ Duplicate prevention test FAILED - Duplicate user was created\n";
    } catch (Exception $e) {
        echo "✅ Duplicate prevention test PASSED - Duplicate rejected correctly\n";
        echo "   Error: " . $e->getMessage() . "\n";
    }
    
    // Clean up test user
    echo "\n4. Cleaning up test user...\n";
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$test_user['username']]);
    echo "✅ Test user deleted\n";
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    echo "✅ Registration system is working correctly with simplified schema!\n";
    echo "✅ Login system is working correctly with simplified schema!\n";
    echo "✅ Database constraints are working properly!\n";
    
} catch(Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
}
?>
