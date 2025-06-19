<?php
session_start();
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$response = array();

switch ($type) {
    case 'login_errors':
        if (isset($_SESSION['login_errors'])) {
            $response['messages'] = $_SESSION['login_errors'];
            unset($_SESSION['login_errors']);
        }
        break;
        
    case 'registration_errors':
        if (isset($_SESSION['registration_errors'])) {
            $response['messages'] = $_SESSION['registration_errors'];
            unset($_SESSION['registration_errors']);
        }
        break;
        
    case 'registration_success':
        if (isset($_SESSION['registration_success'])) {
            $response['message'] = $_SESSION['registration_success'];
            unset($_SESSION['registration_success']);
        }
        break;
        
    case 'login_success':
        if (isset($_SESSION['login_success'])) {
            $response['message'] = $_SESSION['login_success'];
            unset($_SESSION['login_success']);
        }
        break;
}

echo json_encode($response);
?>
