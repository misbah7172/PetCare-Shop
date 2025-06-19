<?php
session_start();

// Include database configuration
require_once '../config/database.php';
require_once 'utils/auth.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../public/admin_dashboard.html");
    } else {
        header("Location: ../public/home.html");
    }
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    // If no validation errors, attempt registration
    if (empty($errors)) {
        try {
            // Create database connection
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Username or email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, phone, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
                ");
                
                if ($insertStmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone])) {
                    $_SESSION['registration_success'] = "Registration successful! You can now login.";
                    header('Location: ../public/login.html?success=1');
                    exit();
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }        } catch(Exception $e) {
            $errors[] = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
            // Add more detailed error for debugging (remove in production)
            if (isset($_GET['debug'])) {
                $errors[] = "Debug: " . $e->getMessage();
            }
        }
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        header('Location: ../public/register.html?error=1');
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header('Location: ../public/register.html');
    exit();
}
?>