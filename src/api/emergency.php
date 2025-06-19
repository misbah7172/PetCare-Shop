<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/EmergencyController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new EmergencyController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        $user_id = $_POST['user_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $description = $_POST['description'] ?? null;
        $response = $controller->createRequest($user_id, $pet_id, $description);
        break;
    case 'list':
        $response = $controller->getAllRequests();
        break;
    case 'user_requests':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserRequests($user_id);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        $response = $controller->getRequest($id);
        break;
    case 'update_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateStatus($id, $status);
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteRequest($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 