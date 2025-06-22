<?php
session_start();

// Clear any old registration/login error messages
unset($_SESSION['registration_errors']);
unset($_SESSION['login_errors']);
unset($_SESSION['registration_success']);

// Include database configuration
require_once '../config/database.php';
require_once 'utils/auth.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin') {
        header("Location: ../public/admin_dashboard.html");
    } else {
        header("Location: ../public/home.html");
    }
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? $_POST['first_name'] ?? ''); // Support both 'name' and 'first_name'
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
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
    
    // If no validation errors, attempt registration
    if (empty($errors)) {
        try {
            // Create database connection
            $database = new Database();
            $pdo = $database->getConnection();
              // Check if username or email already exists - DATABASE ONLY
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Debug info (remove in production)
                if (isset($_GET['debug'])) {
                    $errors[] = "Debug: Found existing user - ID: {$existingUser['id']}, Username: {$existingUser['username']}, Email: {$existingUser['email']}";
                }
                $errors[] = "Username or email already exists";
            } else {                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user with simplified schema
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, username, password, email, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                if ($insertStmt->execute([$name, $username, $hashed_password, $email])) {
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
        header('Location: ../public/login.html?error=1');
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header('Location: ../public/login.html');
    exit();
}
?>