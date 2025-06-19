<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/ShopController.php';

// Database connection (XAMPP default)
$host = 'localhost';
$dbname = 'pawconnect_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

$controller = new ShopController($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

switch ($action) {
    // Product actions
    case 'create_product':
        $name = $_POST['name'] ?? null;
        $category = $_POST['category'] ?? null;
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? null;
        $stock = $_POST['stock'] ?? 0;
        $image_url = $_POST['image_url'] ?? '';
        $response = $controller->createProduct($name, $category, $description, $price, $stock, $image_url);
        break;
    case 'list_products':
        $response = $controller->listProducts();
        break;
    case 'get_product':
        $id = $_GET['id'] ?? null;
        $response = $controller->getProduct($id);
        break;
    case 'update_product':
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? null;
        $category = $_POST['category'] ?? null;
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? null;
        $stock = $_POST['stock'] ?? 0;
        $image_url = $_POST['image_url'] ?? '';
        $response = $controller->updateProduct($id, $name, $category, $description, $price, $stock, $image_url);
        break;
    case 'delete_product':
        $id = $_POST['id'] ?? null;
        $response = $controller->deleteProduct($id);
        break;
    // Order actions
    case 'create_order':
        $user_id = $_POST['user_id'] ?? null;
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        $total = $_POST['total'] ?? null;
        $response = $controller->createOrder($user_id, $items, $total);
        break;
    case 'list_user_orders':
        $user_id = $_GET['user_id'] ?? null;
        $response = $controller->listUserOrders($user_id);
        break;
    case 'get_order':
        $order_id = $_GET['order_id'] ?? null;
        $response = $controller->getOrder($order_id);
        break;
    case 'update_order_status':
        $order_id = $_POST['order_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $response = $controller->updateOrderStatus($order_id, $status);
        break;
    case 'delete_order':
        $order_id = $_POST['order_id'] ?? null;
        $response = $controller->deleteOrder($order_id);
        break;
    default:
        $response = ['success' => false, 'error' => 'Unknown or missing action'];
}

echo json_encode($response); 