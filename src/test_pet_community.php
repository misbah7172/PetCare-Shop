<?php
// Test Pet Community Database and API
// PawConnect Pet Community Testing

echo "<h1>🐾 Pet Community Database Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .info { color: #3498db; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f8f9fa; }
    .btn { padding: 10px 20px; background: #6f87f7; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .btn:hover { background: #5a6fd8; }
</style>";

// Database connection
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='test-section'>";
    echo "<h2>✅ Database Connection</h2>";
    echo "<p class='success'>Successfully connected to database: $dbname</p>";
    echo "</div>";
} catch(PDOException $e) {
    echo "<div class='test-section'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit();
}

// Test Community Tables
echo "<div class='test-section'>";
echo "<h2>📊 Community Database Tables</h2>";

$tables = [
    'community_users',
    'community_pets',
    'community_categories',
    'community_groups',
    'community_group_members',
    'community_posts',
    'community_post_reactions',
    'community_comments',
    'community_comment_likes',
    'community_connections',
    'community_events',
    'community_event_attendees',
    'community_badges',
    'community_user_badges',
    'community_reports',
    'community_activity_log'
];

echo "<table>";
echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td class='success'>✅ Exists</td>";
        echo "<td>$count records</td>";
        echo "</tr>";
    } catch (Exception $e) {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td class='error'>❌ Missing</td>";
        echo "<td>N/A</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "</div>";

// Test Categories
echo "<div class='test-section'>";
echo "<h2>🏷️ Community Categories</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM community_categories ORDER BY sort_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Icon</th><th>Color</th></tr>";
        
        foreach ($categories as $category) {
            echo "<tr>";
            echo "<td>{$category['id']}</td>";
            echo "<td>{$category['name']}</td>";
            echo "<td>{$category['description']}</td>";
            echo "<td><i class='{$category['icon']}' style='color: {$category['color']}'></i> {$category['icon']}</td>";
            echo "<td><span style='color: {$category['color']}'>{$category['color']}</span></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='info'>No categories found. Run the setup script to create default categories.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error loading categories: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test Badges
echo "<div class='test-section'>";
echo "<h2>🏆 Community Badges</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM community_badges ORDER BY id");
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($badges) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Icon</th><th>Color</th></tr>";
        
        foreach ($badges as $badge) {
            echo "<tr>";
            echo "<td>{$badge['id']}</td>";
            echo "<td>{$badge['name']}</td>";
            echo "<td>{$badge['description']}</td>";
            echo "<td><i class='{$badge['icon']}' style='color: {$badge['color']}'></i> {$badge['icon']}</td>";
            echo "<td><span style='color: {$badge['color']}'>{$badge['color']}</span></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='info'>No badges found. Run the setup script to create default badges.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error loading badges: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test API Endpoints
echo "<div class='test-section'>";
echo "<h2>🔗 API Endpoint Tests</h2>";

$endpoints = [
    'categories' => 'GET /pet_community_api.php/categories',
    'posts' => 'GET /pet_community_api.php/posts',
    'groups' => 'GET /pet_community_api.php/groups',
    'events' => 'GET /pet_community_api.php/events'
];

echo "<table>";
echo "<tr><th>Endpoint</th><th>Method</th><th>Status</th></tr>";

foreach ($endpoints as $endpoint => $description) {
    $url = "http://localhost/pawconnect/pet_community_api.php/$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<tr>";
        echo "<td>$endpoint</td>";
        echo "<td>$description</td>";
        echo "<td class='success'>✅ Working (HTTP $httpCode)</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$endpoint</td>";
        echo "<td>$description</td>";
        echo "<td class='error'>❌ Failed (HTTP $httpCode)</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "</div>";

// Setup Instructions
echo "<div class='test-section'>";
echo "<h2>🚀 Setup Instructions</h2>";

echo "<h3>1. Database Setup</h3>";
echo "<p>If any tables are missing, run the SQL setup script:</p>";
echo "<code>mysql -u root -p pawconnect < setup_pet_community.sql</code>";

echo "<h3>2. Test User Creation</h3>";
echo "<p>Create a test user to verify the community features:</p>";
echo "<button class='btn' onclick='createTestUser()'>Create Test User</button>";

echo "<h3>3. Test Post Creation</h3>";
echo "<p>Create a test post to verify the posting system:</p>";
echo "<button class='btn' onclick='createTestPost()'>Create Test Post</button>";

echo "<h3>4. Access Pet Community</h3>";
echo "<p>Visit the Pet Community page to test the full interface:</p>";
echo "<a href='pet_community.html' class='btn'>Open Pet Community</a>";

echo "</div>";

// JavaScript for testing
echo "<script>
function createTestUser() {
    fetch('test_create_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: 'test@petcommunity.com',
            password: 'test123',
            display_name: 'Test Pet Lover',
            bio: 'Testing the pet community features!'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test user created successfully!\\nEmail: test@petcommunity.com\\nPassword: test123');
        } else {
            alert('Error creating test user: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function createTestPost() {
    fetch('test_create_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            title: 'Welcome to Pet Community!',
            content: 'This is a test post to verify the community features are working properly.',
            category_id: 1,
            post_type: 'text'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test post created successfully!');
        } else {
            alert('Error creating test post: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>";

// Database Statistics
echo "<div class='test-section'>";
echo "<h2>📈 Database Statistics</h2>";

try {
    $stats = [];
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['Total Users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count community users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_users");
    $stats['Community Users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_posts");
    $stats['Total Posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count groups
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_groups");
    $stats['Total Groups'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count events
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM community_events");
    $stats['Total Events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<table>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    
    foreach ($stats as $metric => $count) {
        echo "<tr>";
        echo "<td>$metric</td>";
        echo "<td>$count</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error loading statistics: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Verify Database:</strong> Ensure all tables are created and populated</li>";
echo "<li><strong>Test API:</strong> Verify all API endpoints are responding correctly</li>";
echo "<li><strong>Create Test Data:</strong> Use the buttons above to create test users and posts</li>";
echo "<li><strong>Test Interface:</strong> Visit the Pet Community page and test all features</li>";
echo "<li><strong>Integration:</strong> Ensure the community integrates well with existing features</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔧 Troubleshooting</h2>";
echo "<ul>";
echo "<li><strong>Database Connection Issues:</strong> Check XAMPP is running and database exists</li>";
echo "<li><strong>Missing Tables:</strong> Run the setup SQL script to create all tables</li>";
echo "<li><strong>API Errors:</strong> Check file permissions and PHP configuration</li>";
echo "<li><strong>Frontend Issues:</strong> Verify all CSS and JS files are accessible</li>";
echo "</ul>";
echo "</div>";
?> 