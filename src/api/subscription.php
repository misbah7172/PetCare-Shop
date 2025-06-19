<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/SubscriptionController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new SubscriptionController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    // Subscription actions
    case 'create_subscription':
        $user_id = $_POST['user_id'] ?? null;
        $plan_type = $_POST['plan_type'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $response = $controller->createSubscription($user_id, $plan_type, $start_date, $end_date);
        break;
    case 'get_user_subscriptions':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserSubscriptions($user_id);
        break;
    case 'get_all_subscriptions':
        $response = $controller->getAllSubscriptions();
        break;
    case 'update_subscription_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateSubscriptionStatus($id, $status);
        break;
    case 'delete_subscription':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteSubscription($id);
        break;
    // Subscription box actions
    case 'get_all_boxes':
        $response = $controller->getAllBoxes();
        break;
    case 'subscribe_box':
        $user_id = $_POST['user_id'] ?? null;
        $box_id = $_POST['box_id'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $response = $controller->subscribeBox($user_id, $box_id, $start_date, $end_date);
        break;
    case 'get_user_boxes':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserBoxes($user_id);
        break;
    case 'update_box_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateBoxStatus($id, $status);
        break;
    case 'unsubscribe_box':
        $id = $_POST['id'] ?? null;
        $response = $controller->unsubscribeBox($id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 