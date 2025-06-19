<?php
// Test Pet Corner Database and API
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Corner Test - PawConnect</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Pet Corner Database & API Test</h1>
    
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
        
        // Check if tables exist
        $tables = ['users', 'pet_profiles', 'photo_posts', 'post_likes', 'post_comments', 'pet_stories', 'story_votes'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<p>✅ Table '$table' exists with $count records</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Table '$table' not found or error: " . $e->getMessage() . "</p>";
            }
        }
        
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "<p>Please make sure:</p>";
        echo "<ul>";
        echo "<li>XAMPP is running</li>";
        echo "<li>MySQL service is started</li>";
        echo "<li>Database 'pawconnect' exists</li>";
        echo "<li>You've imported the setup_pet_corner.sql file</li>";
        echo "</ul>";
    }
    echo "</div>";
    
    // API test
    echo "<div class='test-section info'>";
    echo "<h2>API Endpoints Test</h2>";
    
    $api_endpoints = [
        'get_photos' => 'pet_corner_api.php?action=get_photos',
        'get_stories' => 'pet_corner_api.php?action=get_stories',
        'get_profiles' => 'pet_corner_api.php?action=get_profiles'
    ];
    
    foreach ($api_endpoints as $name => $url) {
        echo "<h3>Testing $name</h3>";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data) {
                echo "<p class='success'>✅ $name API working</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='error'>❌ $name API returned invalid JSON</p>";
                echo "<pre>$response</pre>";
            }
        } else {
            echo "<p class='error'>❌ $name API failed</p>";
        }
    }
    echo "</div>";
    
    // File upload test
    echo "<div class='test-section info'>";
    echo "<h2>File Upload Directory Test</h2>";
    
    $upload_dir = 'images/pet_photos/';
    if (file_exists($upload_dir)) {
        echo "<p class='success'>✅ Upload directory exists: $upload_dir</p>";
        if (is_writable($upload_dir)) {
            echo "<p class='success'>✅ Upload directory is writable</p>";
        } else {
            echo "<p class='error'>❌ Upload directory is not writable</p>";
        }
    } else {
        echo "<p class='error'>❌ Upload directory does not exist: $upload_dir</p>";
        echo "<p>Creating directory...</p>";
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p class='success'>✅ Upload directory created successfully</p>";
        } else {
            echo "<p class='error'>❌ Failed to create upload directory</p>";
        }
    }
    echo "</div>";
    
    // Sample data display
    echo "<div class='test-section info'>";
    echo "<h2>Sample Data</h2>";
    
    try {
        // Show sample users
        $stmt = $pdo->query("SELECT id, username, email FROM users LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Users:</h3>";
        echo "<pre>" . json_encode($users, JSON_PRETTY_PRINT) . "</pre>";
        
        // Show sample pet profiles
        $stmt = $pdo->query("SELECT * FROM pet_profiles LIMIT 3");
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Pet Profiles:</h3>";
        echo "<pre>" . json_encode($profiles, JSON_PRETTY_PRINT) . "</pre>";
        
        // Show sample photo posts
        $stmt = $pdo->query("SELECT * FROM photo_posts LIMIT 3");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Photo Posts:</h3>";
        echo "<pre>" . json_encode($posts, JSON_PRETTY_PRINT) . "</pre>";
        
        // Show sample stories
        $stmt = $pdo->query("SELECT * FROM pet_stories LIMIT 3");
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Pet Stories:</h3>";
        echo "<pre>" . json_encode($stories, JSON_PRETTY_PRINT) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error fetching sample data: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    ?>
    
    <div class="test-section">
        <h2>Next Steps</h2>
        <p>If all tests pass, you can:</p>
        <ul>
            <li>Visit <a href="pet_corner.html">Pet Corner</a> to test the full functionality</li>
            <li>Register a new account and create pet profiles</li>
            <li>Upload photos and share stories</li>
            <li>Test the like and comment features</li>
        </ul>
    </div>
</body>
</html> 