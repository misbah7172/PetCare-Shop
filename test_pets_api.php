<?php
// Test script for pets API
session_start();

// Simulate logged in user for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';

echo "=== PAWCONNECT PET CORNER API TEST ===\n\n";

// Test 1: Get user pets (should be empty initially)
echo "Test 1: Getting user pets...\n";
$url = 'http://localhost/pawconnect/src/pets_api.php?action=pets';
$response = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . session_name() . "=" . session_id()
    ]
]));
echo "Response: " . $response . "\n\n";

// Test 2: Add a test pet
echo "Test 2: Adding a test pet...\n";
$pet_data = json_encode([
    'name' => 'Buddy',
    'type' => 'dog',
    'breed' => 'Golden Retriever',
    'age' => '3 years',
    'gender' => 'male',
    'weight' => '30 kg',
    'description' => 'Friendly and energetic dog'
]);

$url = 'http://localhost/pawconnect/src/pets_api.php?action=add_pet';
$response = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nCookie: " . session_name() . "=" . session_id(),
        'content' => $pet_data
    ]
]));
echo "Response: " . $response . "\n\n";

// Test 3: Get user pets again (should now have one pet)
echo "Test 3: Getting user pets after adding one...\n";
$url = 'http://localhost/pawconnect/src/pets_api.php?action=pets';
$response = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . session_name() . "=" . session_id()
    ]
]));
echo "Response: " . $response . "\n\n";

// Test 4: Get activities
echo "Test 4: Getting activities...\n";
$url = 'http://localhost/pawconnect/src/pets_api.php?action=activities';
$response = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . session_name() . "=" . session_id()
    ]
]));
echo "Response: " . $response . "\n\n";

echo "=== API TEST COMPLETE ===\n";
echo "If you see successful responses above, the API is working correctly!\n";
echo "You can now test the Pet Corner page at: http://localhost/pawconnect/public/pet_corner.html\n";
?>
