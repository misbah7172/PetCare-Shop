<?php
session_start();

// Include database configuration
require_once '../config/database.php';
require_once 'utils/auth.php';

// Initialize response
$response = array();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../public/admin_dashboard.html");
    } else {
        header("Location: ../public/home.html");
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        try {
            // Create database connection
            $database = new Database();
            $pdo = $database->getConnection();              // Check if user exists and password is correct
            $stmt = $pdo->prepare("SELECT id, name, username, password, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
              if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                
                // Determine redirect based on username (admin check)
                if ($user['username'] === 'admin') {
                    header("Location: ../public/admin_dashboard.html");
                } else {
                    header("Location: ../public/home.html");
                }
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch(Exception $e) {
            $errors[] = "Login failed. Please try again.";
            error_log("Login error: " . $e->getMessage());        }
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header('Location: ../public/login.html?error=1');
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header('Location: ../public/login.html');
    exit();
}
?>