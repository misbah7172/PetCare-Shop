<?php
session_start();
header('Content-Type: application/json');

$response = array();

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $response['authenticated'] = true;
    $response['user_id'] = $_SESSION['user_id'];
    $response['username'] = $_SESSION['username'];
    $response['role'] = $_SESSION['role'] ?? 'user';
    $response['email'] = $_SESSION['email'] ?? '';
} else {
    $response['authenticated'] = false;
}

echo json_encode($response);
?>
