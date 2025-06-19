<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/LostFoundController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new LostFoundController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        $pet_id = $_POST['pet_id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $type = $_POST['type'] ?? null;
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        $response = $controller->createReport($pet_id, $user_id, $type, $location, $description);
        break;
    case 'list':
        $response = $controller->getAllReports();
        break;
    case 'user_reports':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserReports($user_id);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        $response = $controller->getReport($id);
        break;
    case 'update_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateStatus($id, $status);
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteReport($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 