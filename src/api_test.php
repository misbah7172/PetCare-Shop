<?php
// API Test Script for PawConnect
// This script tests all the backend API endpoints

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>PawConnect API Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .endpoint{border:1px solid #ddd;margin:10px 0;padding:10px;} h3{margin:0 0 10px 0;}</style>";
echo "</head><body>";

echo "<h1>PawConnect API Test Suite</h1>";

$baseUrl = 'http://localhost/pawconnect/src/api';
$testResults = [];

// Test endpoints
$endpoints = [
    // Auth endpoints
    'POST /auth/register' => [
        'url' => '/auth/register',
        'method' => 'POST',
        'data' => [
            'username' => 'testuser' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '555-1234'
        ]
    ],
    'POST /auth/login' => [
        'url' => '/auth/login',
        'method' => 'POST',
        'data' => [
            'email' => 'admin@pawconnect.com',
            'password' => 'admin123'
        ]
    ],
    
    // Pet endpoints
    'GET /pets' => [
        'url' => '/pets',
        'method' => 'GET'
    ],
    'GET /pets/search' => [
        'url' => '/pets/search?q=dog',
        'method' => 'GET'
    ],
    
    // Product endpoints
    'GET /products' => [
        'url' => '/products',
        'method' => 'GET'
    ],
    'GET /products/categories' => [
        'url' => '/products/categories',
        'method' => 'GET'
    ],
    
    // Appointment endpoints
    'GET /appointments' => [
        'url' => '/appointments',
        'method' => 'GET',
        'requireAuth' => true
    ],
    
    // Community endpoints
    'GET /community' => [
        'url' => '/community',
        'method' => 'GET'
    ],
    'GET /community/categories' => [
        'url' => '/community/categories',
        'method' => 'GET'
    ],
    
    // Support endpoints
    'GET /support/faq' => [
        'url' => '/support/faq',
        'method' => 'GET'
    ],
    'GET /support/categories' => [
        'url' => '/support/categories',
        'method' => 'GET'
    ],
    
    // Lost and Found endpoints
    'GET /lost-found' => [
        'url' => '/lost-found',
        'method' => 'GET'
    ],
    
    // Subscription endpoints
    'GET /subscriptions/plans' => [
        'url' => '/subscriptions/plans',
        'method' => 'GET'
    ],
    
    // Donation endpoints
    'GET /donations/campaigns' => [
        'url' => '/donations/campaigns',
        'method' => 'GET'
    ],
    
    // Emergency endpoints
    'GET /emergency/emergency-contacts' => [
        'url' => '/emergency/emergency-contacts',
        'method' => 'GET'
    ]
];

$authToken = null;

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'body' => $response,
        'status' => $httpCode,
        'error' => $error
    ];
}

echo "<h2>Running API Tests...</h2>";

foreach ($endpoints as $name => $config) {
    echo "<div class='endpoint'>";
    echo "<h3>$name</h3>";
    
    $url = $baseUrl . $config['url'];
    $headers = [];
    
    // Add auth header if required and we have a token
    if (isset($config['requireAuth']) && $config['requireAuth'] && $authToken) {
        $headers[] = "Authorization: Bearer $authToken";
    }
    
    $result = makeRequest(
        $url, 
        $config['method'], 
        $config['data'] ?? null, 
        $headers
    );
    
    echo "<p><strong>URL:</strong> " . htmlspecialchars($config['method'] . ' ' . $url) . "</p>";
    
    if ($result['error']) {
        echo "<p class='error'><strong>cURL Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
    } else {
        echo "<p><strong>Status:</strong> <span class='" . ($result['status'] < 400 ? 'success' : 'error') . "'>" . $result['status'] . "</span></p>";
        
        $responseData = json_decode($result['body'], true);
        if ($responseData) {
            echo "<p><strong>Response:</strong></p>";
            echo "<pre style='background:#f5f5f5;padding:10px;max-height:200px;overflow:auto;'>" . 
                 htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
            
            // Store auth token if this was a login request
            if ($name === 'POST /auth/login' && isset($responseData['token'])) {
                $authToken = $responseData['token'];
                echo "<p class='info'><strong>Auth token saved for subsequent requests</strong></p>";
            }
        } else {
            echo "<p><strong>Raw Response:</strong></p>";
            echo "<pre style='background:#f5f5f5;padding:10px;max-height:200px;overflow:auto;'>" . 
                 htmlspecialchars($result['body']) . "</pre>";
        }
    }
    
    echo "</div>";
    
    // Small delay between requests
    usleep(100000); // 0.1 second
}

echo "<h2>Database Structure Test</h2>";

try {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<div class='endpoint'>";
    echo "<h3>Database Connection</h3>";
    echo "<p class='success'>✅ Successfully connected to database</p>";
    
    // Test table existence
    $tables = [
        'users', 'pets', 'veterinarians', 'appointments', 'products', 'product_categories',
        'orders', 'order_items', 'community_posts', 'community_categories', 'community_comments',
        'support_tickets', 'support_categories', 'lost_found_pets', 'subscription_plans',
        'subscriptions', 'reminders', 'emergency_requests', 'donations', 'donation_campaigns',
        'notifications'
    ];
    
    echo "<h4>Table Structure:</h4>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<span class='success'>✅ $table</span><br>";
        } else {
            echo "<span class='error'>❌ $table (missing)</span><br>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='endpoint'>";
    echo "<h3>Database Connection</h3>";
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<h2>Controller File Check</h2>";

$controllers = [
    'AuthController.php',
    'PetController.php',
    'AdoptionController.php',
    'VeterinarianController.php',
    'AppointmentController.php',
    'ProductController.php',
    'OrderController.php',
    'CommunityController.php',
    'SupportController.php',
    'LostFoundController.php',
    'SubscriptionController.php',
    'ReminderController.php',
    'EmergencyController.php',
    'DonationController.php',
    'NotificationController.php'
];

echo "<div class='endpoint'>";
echo "<h3>Controller Files</h3>";
foreach ($controllers as $controller) {
    $path = "../api/controllers/$controller";
    if (file_exists($path)) {
        echo "<span class='success'>✅ $controller</span><br>";
    } else {
        echo "<span class='error'>❌ $controller (missing)</span><br>";
    }
}
echo "</div>";

echo "<h2>Setup Instructions</h2>";
echo "<div class='endpoint'>";
echo "<h3>Getting Started</h3>";
echo "<ol>";
echo "<li>Make sure XAMPP is running with Apache and MySQL</li>";
echo "<li>Run the database setup: <a href='setup_complete.php' target='_blank'>setup_complete.php</a></li>";
echo "<li>Test individual API endpoints using the links above</li>";
echo "<li>Access the admin panel: <a href='../public/admin_comprehensive.html' target='_blank'>Admin Panel</a></li>";
echo "<li>Access the main app: <a href='../public/index.html' target='_blank'>PawConnect App</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>API Documentation</h2>";
echo "<div class='endpoint'>";
echo "<h3>Available Endpoints</h3>";
echo "<ul>";
echo "<li><strong>Authentication:</strong> /auth/register, /auth/login, /auth/logout, /auth/profile</li>";
echo "<li><strong>Pets:</strong> /pets, /pets/search, /pets/favorites</li>";
echo "<li><strong>Adoptions:</strong> /adoptions, /adoptions/apply</li>";
echo "<li><strong>Veterinarians:</strong> /veterinarians, /veterinarians/search</li>";
echo "<li><strong>Appointments:</strong> /appointments, /appointments/available-slots</li>";
echo "<li><strong>Products:</strong> /products, /products/categories, /products/search</li>";
echo "<li><strong>Orders:</strong> /orders, /orders/cart, /orders/checkout</li>";
echo "<li><strong>Community:</strong> /community, /community/posts, /community/comments</li>";
echo "<li><strong>Support:</strong> /support, /support/ticket, /support/faq</li>";
echo "<li><strong>Lost & Found:</strong> /lost-found, /lost-found/search, /lost-found/report</li>";
echo "<li><strong>Subscriptions:</strong> /subscriptions/plans, /subscriptions/subscribe</li>";
echo "<li><strong>Reminders:</strong> /reminders, /reminders/upcoming, /reminders/create</li>";
echo "<li><strong>Emergency:</strong> /emergency, /emergency/nearby-services, /emergency/request</li>";
echo "<li><strong>Donations:</strong> /donations/campaigns, /donations/donate</li>";
echo "<li><strong>Notifications:</strong> /notifications, /notifications/settings</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
