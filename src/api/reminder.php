<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/ReminderController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new ReminderController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        $user_id = $_POST['user_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? '';
        $remind_at = $_POST['remind_at'] ?? null;
        $response = $controller->createReminder($user_id, $pet_id, $title, $description, $remind_at);
        break;
    case 'list':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserReminders($user_id);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        $response = $controller->getReminder($id);
        break;
    case 'update':
        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? '';
        $remind_at = $_POST['remind_at'] ?? null;
        $response = $controller->updateReminder($id, $title, $description, $remind_at);
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteReminder($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 