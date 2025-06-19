<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['photo'];
$caption = $_POST['caption'] ?? '';
$pet_profile_id = $_POST['pet_profile_id'] ?? null;

// Validate caption
if (empty($caption) || strlen($caption) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Caption is required and must be 50 characters or less']);
    exit;
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPEG, PNG, and GIF images are allowed']);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File size must be less than 5MB']);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = 'images/pet_photos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'pet_' . time() . '_' . uniqid() . '.' . $file_extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Save to database
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("INSERT INTO photo_posts (user_id, pet_profile_id, photo_url, caption) VALUES (?, ?, ?, ?)");

if ($stmt->execute([$user_id, $pet_profile_id, $filepath, $caption])) {
    $post_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'photo_url' => $filepath,
        'caption' => $caption
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save post to database']);
}
?> 