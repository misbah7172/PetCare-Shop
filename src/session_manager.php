<?php
// Session Management and Authentication Functions
session_start();

class AuthManager {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'email' => $_SESSION['email'] ?? '',
                'first_name' => $_SESSION['first_name'] ?? '',
                'last_name' => $_SESSION['last_name'] ?? '',
                'role' => $_SESSION['role'] ?? 'user'
            ];
        }
        return null;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.html');
            exit();
        }
    }
    
    public static function logout() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy session cookie if it exists
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Start a new session to show logout message
        session_start();
        $_SESSION['logout_message'] = 'You have been successfully logged out.';
        
        header('Location: ../public/index.html');
        exit();
    }
    
    public static function getSessionStatus() {
        return [
            'logged_in' => self::isLoggedIn(),
            'user' => self::getCurrentUser(),
            'session_id' => session_id(),
            'csrf_token' => self::generateCSRFToken()
        ];
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function refreshSession() {
        if (self::isLoggedIn()) {
            session_regenerate_id(true);
            $_SESSION['last_activity'] = time();
        }
    }
    
    public static function checkSessionTimeout($timeout = 3600) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
        }
        $_SESSION['last_activity'] = time();
    }
    
    // Premium user functionality
    public static function isPremiumUser() {
        return self::isLoggedIn() && isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'active';
    }
    
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public static function isPremium() {
        return self::isLoggedIn() && isset($_SESSION['premium']) && $_SESSION['premium'] === true;
    }
    
    public static function hasFeatureAccess($feature) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        // Admin has access to all features
        if (self::isAdmin()) {
            return true;
        }
        
        // Premium features
        $premiumFeatures = ['advanced_search', 'priority_support', 'premium_content'];
        if (in_array($feature, $premiumFeatures)) {
            return self::isPremium();
        }
        
        // Basic features available to all logged-in users
        return true;
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            http_response_code(403);
            header('Location: dashboard.html?error=access_denied');
            exit();
        }
    }
    
    public static function isVeterinarian() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'veterinarian';
    }
    
    public static function getUserRole() {
        return $_SESSION['role'] ?? 'user';
    }
    
    public static function getSessionInfo() {
        if (!self::isLoggedIn()) {
            return ['authenticated' => false];
        }
        
        return [
            'authenticated' => true,
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'role' => self::getUserRole(),
            'is_admin' => self::isAdmin(),
            'is_premium' => self::isPremium(),
            'is_veterinarian' => self::isVeterinarian()
        ];
    }
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check for session timeout (1 hour)
if (AuthManager::isLoggedIn()) {
    AuthManager::checkSessionTimeout();
}

// API endpoint for session status
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    header('Content-Type: application/json');
    echo json_encode(AuthManager::getSessionStatus());
    exit();
}

// API endpoint for logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthManager::logout();
}

// API endpoint for session refresh
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    header('Content-Type: application/json');
    if (AuthManager::isLoggedIn()) {
        AuthManager::refreshSession();
        echo json_encode(['success' => true, 'message' => 'Session refreshed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    }
    exit();
}

// API endpoint for checking admin status
if (isset($_GET['action']) && $_GET['action'] === 'check_admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'is_admin' => AuthManager::isAdmin(),
        'is_veterinarian' => AuthManager::isVeterinarian(),
        'is_premium' => AuthManager::isPremium(),
        'role' => AuthManager::getUserRole()
    ]);
    exit();
}

// API endpoint for feature access check
if (isset($_GET['action']) && $_GET['action'] === 'check_feature') {
    $feature = $_GET['feature'] ?? '';
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'has_access' => AuthManager::hasFeatureAccess($feature),
        'feature' => $feature
    ]);
    exit();
}
?>
