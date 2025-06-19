<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/SupportController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new SupportController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    // Ticket actions
    case 'create_ticket':
        $user_id = $_POST['user_id'] ?? null;
        $subject = $_POST['subject'] ?? null;
        $message = $_POST['message'] ?? null;
        $response = $controller->createTicket($user_id, $subject, $message);
        break;
    case 'get_user_tickets':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->getUserTickets($user_id);
        break;
    case 'get_all_tickets':
        $response = $controller->getAllTickets();
        break;
    case 'get_ticket':
        $id = $_GET['id'] ?? null;
        $response = $controller->getTicket($id);
        break;
    case 'update_ticket_status':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateTicketStatus($id, $status);
        break;
    case 'delete_ticket':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteTicket($id);
        break;
    // Reply actions
    case 'add_reply':
        $ticket_id = $_POST['ticket_id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $message = $_POST['message'] ?? null;
        $response = $controller->addReply($ticket_id, $user_id, $message);
        break;
    case 'get_replies':
        $ticket_id = $_GET['ticket_id'] ?? null;
        $response = $controller->getReplies($ticket_id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 