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
        // POST: user_id, pet_id, type, title, description, reminder_date, reminder_time, frequency
        $user_id = $_POST['user_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $type = $_POST['type'] ?? 'general';
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? '';
        $reminder_date = $_POST['reminder_date'] ?? null;
        $reminder_time = $_POST['reminder_time'] ?? null;
        $frequency = $_POST['frequency'] ?? 'once';
        $remind_at = $reminder_date && $reminder_time ? $reminder_date . ' ' . $reminder_time : ($_POST['remind_at'] ?? null);
        $response = $controller->createReminder($user_id, $pet_id, $title, $description, $remind_at, $type, $frequency);
        break;
    case 'list':
        // GET: user_id
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserReminders($user_id);
        break;
    case 'get':
        // GET: id
        $id = $_GET['id'] ?? null;
        $response = $controller->getReminder($id);
        break;
    case 'update':
        // POST: id, title, description, reminder_date, reminder_time, status
        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? '';
        $reminder_date = $_POST['reminder_date'] ?? null;
        $reminder_time = $_POST['reminder_time'] ?? null;
        $status = $_POST['status'] ?? null;
        $remind_at = $reminder_date && $reminder_time ? $reminder_date . ' ' . $reminder_time : ($_POST['remind_at'] ?? null);
        $response = $controller->updateReminder($id, $title, $description, $remind_at, $status);
        break;
    case 'delete':
        // POST: id
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteReminder($id);
        break;
    case 'mark_complete':
        // POST: id
        $id = $_POST['id'] ?? null;
        $response = $controller->markReminderComplete($id);
        break;
    case 'upcoming':
        // GET: user_id, days (optional, default 7)
        $user_id = $_GET['user_id'] ?? null;
        $days = $_GET['days'] ?? 7;
        $response = $controller->getUpcomingReminders($user_id, $days);
        break;
    case 'generate_smart':
        // POST: user_id, pet_id
        $user_id = $_POST['user_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $response = $controller->generateSmartReminders($user_id, $pet_id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 