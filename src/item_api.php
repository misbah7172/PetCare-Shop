<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'get_pet':
        try {
            if (!$id) {
                throw new Exception('Pet ID is required');
            }
            
            $stmt = $db->prepare("
                SELECT p.*, u.first_name, u.last_name,
                       CONCAT(u.first_name, ' ', u.last_name) as owner_name
                FROM pets p
                LEFT JOIN users u ON p.uploader_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $pet = $stmt->fetch();
            
            if (!$pet) {
                throw new Exception('Pet not found');
            }
            
            echo json_encode(['success' => true, 'pet' => $pet]);
            
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'get_product':
        try {
            if (!$id) {
                throw new Exception('Product ID is required');
            }
            
            $stmt = $db->prepare("
                SELECT p.*, u.first_name, u.last_name,
                       CONCAT(u.first_name, ' ', u.last_name) as owner_name
                FROM products p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            echo json_encode(['success' => true, 'product' => $product]);
            
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
