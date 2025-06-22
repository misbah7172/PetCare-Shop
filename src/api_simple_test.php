<?php
// Simple API test
echo "<h2>Testing PawConnect API</h2>";

// Test direct API index
echo "<h3>1. Testing API Index</h3>";
$response = file_get_contents('http://localhost/pawconnect/src/api/index.php');
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test a simple GET endpoint
echo "<h3>2. Testing GET /pets endpoint</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/pawconnect/src/api/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'REQUEST_URI: /pets',
    'REQUEST_METHOD: GET'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test database connection
echo "<h3>3. Testing Database Connection</h3>";
try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pets");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Database connected! Pets in database: " . $result['count'] . "</p>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT * FROM pets LIMIT 3");
    $pets = $stmt->fetchAll();
    echo "<h4>Sample pets:</h4>";
    echo "<pre>" . print_r($pets, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
