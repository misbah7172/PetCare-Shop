<?php
// Direct API router test
echo "<h2>Direct API Router Test</h2>";

// Simulate an API call by setting appropriate $_SERVER variables
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/pawconnect/src/api/pets';

echo "<h3>Testing /pets endpoint</h3>";
echo "<p>REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";

ob_start();
try {
    include 'api/index.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
}
ob_end_clean();

echo "<h4>API Response:</h4>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Test another endpoint
echo "<hr><h3>Testing /products endpoint</h3>";
$_SERVER['REQUEST_URI'] = '/pawconnect/src/api/products';
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";

ob_start();
try {
    include 'api/index.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
}
ob_end_clean();

echo "<h4>API Response:</h4>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";
?>
