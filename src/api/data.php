<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_GET['endpoint'] ?? '';
    
    switch ($endpoint) {
        case 'pets':
            if ($method === 'GET') {
                // Get all pets for adoption
                $stmt = $pdo->prepare("
                    SELECT p.*, u.username as owner_name, a.status as adoption_status 
                    FROM pets p 
                    JOIN users u ON p.user_id = u.id 
                    LEFT JOIN adoptions a ON p.id = a.pet_id 
                    WHERE p.is_public = 1 
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'adoptions':
            if ($method === 'GET') {
                // Get available pets for adoption
                $stmt = $pdo->prepare("
                    SELECT a.*, p.name as pet_name, p.type, p.breed, p.age, p.age_unit, 
                           p.gender, p.photo_url, p.description as pet_description,
                           u.username as listed_by_name
                    FROM adoptions a
                    JOIN pets p ON a.pet_id = p.id
                    JOIN users u ON a.listed_by = u.id
                    WHERE a.status = 'available'
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'products':
            if ($method === 'GET') {
                // Get shop products
                $stmt = $pdo->prepare("
                    SELECT * FROM shop_products 
                    WHERE is_active = 1 
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'vets':
            if ($method === 'GET') {
                // Get available vets
                $stmt = $pdo->prepare("
                    SELECT v.*, u.username, u.email 
                    FROM vets v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE v.is_active = 1 
                    ORDER BY v.name
                ");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'community_posts':
            if ($method === 'GET') {
                // Get community posts
                $stmt = $pdo->prepare("
                    SELECT cp.*, u.username, u.first_name, u.last_name,
                           cg.name as group_name
                    FROM community_posts cp
                    JOIN users u ON cp.user_id = u.id
                    LEFT JOIN community_groups cg ON cp.group_id = cg.id
                    ORDER BY cp.created_at DESC
                    LIMIT 20
                ");
                $stmt->execute();
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
