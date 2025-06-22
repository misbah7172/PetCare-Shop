<?php
// Simple Adoption Posts - Direct Database Approach
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all adoption posts
        $stmt = $pdo->prepare("
            SELECT ap.*, 
                   p.name as pet_name, 
                   p.type as pet_type, 
                   p.breed as pet_breed, 
                   p.age as pet_age, 
                   p.gender as pet_gender, 
                   p.weight as pet_weight,
                   p.description as pet_description,
                   p.photo_path,
                   u.name as owner_name,
                   u.username as owner_username
            FROM adoption_posts ap
            JOIN pets p ON ap.pet_id = p.id
            JOIN users u ON ap.user_id = u.id
            WHERE ap.status = 'available'
            ORDER BY ap.created_at DESC
        ");
        
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'posts' => $posts]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
