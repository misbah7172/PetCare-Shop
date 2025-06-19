<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/AdoptionController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new AdoptionController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        // POST: pet_id, listed_by, description
        $pet_id = $_POST['pet_id'] ?? null;
        $listed_by = $_POST['listed_by'] ?? null;
        $description = $_POST['description'] ?? '';
        $response = $controller->create($pet_id, $listed_by, $description);
        break;
    case 'list':
        // GET: list all adoptions
        $response = $controller->list();
        break;
    case 'get':
        // GET: id
        $id = $_GET['id'] ?? null;
        $response = $controller->get($id);
        break;
    case 'apply':
        // POST: adoption_id, applicant_id, message
        $adoption_id = $_POST['adoption_id'] ?? null;
        $applicant_id = $_POST['applicant_id'] ?? null;
        $message = $_POST['message'] ?? '';
        $response = $controller->apply($adoption_id, $applicant_id, $message);
        break;
    case 'applications':
        // GET: adoption_id
        $adoption_id = $_GET['adoption_id'] ?? null;
        $response = $controller->applications($adoption_id);
        break;
    case 'update_application':
        // POST: application_id, status
        $application_id = $_POST['application_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateApplication($application_id, $status);
        break;
    case 'delete':
        // POST: id
        $id = $_POST['id'] ?? null;
        $response = $controller->delete($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 