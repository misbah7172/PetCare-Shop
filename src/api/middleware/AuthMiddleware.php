<?php
// Authentication Middleware for PawConnect API
// Handles JWT token validation and user authentication

require_once '../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    private $database;
    private $secretKey;

    public function __construct($database) {
        $this->database = $database;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'pawconnect_secret_key_2024';
    }

    public function authenticate($requiredRoles = []) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization header missing']);
            exit;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid authorization header format']);
            exit;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $userId = $decoded->user_id;

            // Get user from database
            $pdo = $this->database->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'User not found or inactive']);
                exit;
            }

            // Check required roles
            if (!empty($requiredRoles) && !in_array($user['role'], $requiredRoles)) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            }

            return $user;

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token', 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function generateToken($userId) {
        $payload = [
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function refreshToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Check if token is close to expiring (within 1 hour)
            if ($decoded->exp - time() < 3600) {
                return $this->generateToken($decoded->user_id);
            }
            
            return $token; // Return original token if not close to expiring
            
        } catch (Exception $e) {
            throw new Exception('Invalid token for refresh');
        }
    }
}
?>
