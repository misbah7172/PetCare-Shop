<?php
// Get User Pets - Direct in public folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include database config
require_once '../config/database.php';

// Set user ID (using session or default for testing)
$user_id = $_SESSION['user_id'] ?? 1;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               ap.title as adoption_title,
               ap.description as adoption_description,
               ap.adoption_fee,
               ap.status as adoption_status,
               ap.urgent
        FROM pets p 
        LEFT JOIN adoption_posts ap ON p.adoption_post_id = ap.id
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'pets' => $pets]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
