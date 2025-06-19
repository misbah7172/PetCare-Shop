<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/DonationController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new DonationController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    case 'create':
        $user_id = $_POST['user_id'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $message = $_POST['message'] ?? '';
        $response = $controller->createDonation($user_id, $amount, $message);
        break;
    case 'list':
        $response = $controller->getAllDonations();
        break;
    case 'user_donations':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserDonations($user_id);
        break;
    case 'get':
        $id = $_GET['id'] ?? null;
        $response = $controller->getDonation($id);
        break;
    case 'update_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateStatus($id, $status);
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteDonation($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 