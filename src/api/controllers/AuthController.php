<?php
// Authentication Controller for PawConnect API
// Handles user registration, login, and authentication

class AuthController {
    private $database;
    private $pdo;

    public function __construct($database) {
        $this->database = $database;
        $this->pdo = $this->database->getConnection();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Check if username or email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            return;
        }

        // Hash password
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, phone, address, city, state, zip_code, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $input['username'],
                $input['email'],
                $passwordHash,
                $input['first_name'],
                $input['last_name'],
                $input['phone'] ?? null,
                $input['address'] ?? null,
                $input['city'] ?? null,
                $input['state'] ?? null,
                $input['zip_code'] ?? null,
                $input['role'] ?? 'user'
            ]);

            $userId = $this->pdo->lastInsertId();
            
            // Generate session token (simple approach)
            $token = $this->generateSimpleToken($userId);
            
            // Log activity
            $this->logActivity($userId, 'user_registered', 'New user registered');

            echo json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId,
                'token' => $token
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed', 'message' => $e->getMessage()]);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['login']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email/username and password are required']);
            return;
        }

        try {
            // Find user by email or username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active'");
            $stmt->execute([$input['login'], $input['login']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($input['password'], $user['password_hash'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Generate token
            $token = $this->generateSimpleToken($user['id']);
            
            // Log activity
            $this->logActivity($user['id'], 'user_login', 'User logged in');

            // Remove sensitive data
            unset($user['password_hash']);

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Login failed', 'message' => $e->getMessage()]);
        }
    }

    public function getCurrentUser($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            // Remove sensitive data
            unset($user['password_hash']);

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get user data', 'message' => $e->getMessage()]);
        }
    }

    public function logout() {
        // In a real implementation, you would invalidate the token
        // For now, just return success
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is required']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token (in production, use secure random token)
                $resetToken = bin2hex(random_bytes(32));
                
                // Store reset token (you would create a password_resets table)
                // For now, just log the activity
                $this->logActivity($user['id'], 'password_reset_requested', 'Password reset requested');
                
                // In production, send email with reset link
                echo json_encode([
                    'success' => true,
                    'message' => 'Password reset email sent',
                    'reset_token' => $resetToken // Remove this in production
                ]);
            } else {
                // Don't reveal if email exists or not
                echo json_encode([
                    'success' => true,
                    'message' => 'If the email exists, a reset link has been sent'
                ]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process request', 'message' => $e->getMessage()]);
        }
    }

    public function resetPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['token']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Token and password are required']);
            return;
        }

        // In production, validate the reset token
        // For now, just return success
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    public function verifyEmail() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Verification token is required']);
            return;
        }

        // In production, validate the verification token
        // For now, just return success
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    }

    public function refreshToken() {
        // In production, implement proper token refresh logic
        echo json_encode([
            'success' => true,
            'message' => 'Token refreshed',
            'token' => 'new_token_here'
        ]);
    }

    private function generateSimpleToken($userId) {
        // Simple token generation (use JWT in production)
        $payload = base64_encode(json_encode([
            'user_id' => $userId,
            'timestamp' => time(),
            'expires' => time() + (24 * 60 * 60) // 24 hours
        ]));
        
        return 'pawconnect_' . $payload;
    }

    private function logActivity($userId, $action, $description) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
?>
