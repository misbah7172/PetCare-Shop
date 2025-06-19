<?php
// Pet Corner API
// PawConnect Pet Corner Feature - Unified Profile System

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$host = 'localhost';
$dbname = 'pawconnect';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$endpoint = end($path_parts);

// Get authorization token
function getAuthToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            return substr($auth, 7);
        }
    }
    return null;
}

// Get current user
function getCurrentUser($pdo) {
    $token = getAuthToken();
    if (!$token) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, cu.display_name, cu.bio, cu.avatar_url, cu.experience_level 
                          FROM users u 
                          LEFT JOIN community_users cu ON u.id = cu.user_id 
                          WHERE u.auth_token = ? AND u.token_expiry > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle different endpoints
switch ($endpoint) {
    case 'auth':
        handleAuth($pdo);
        break;
    case 'profile':
        handleProfile($pdo);
        break;
    case 'pets':
        handlePets($pdo);
        break;
    case 'photos':
        handlePhotos($pdo);
        break;
    case 'stories':
        handleStories($pdo);
        break;
    case 'like':
        handleLike($pdo);
        break;
    case 'vote':
        handleVote($pdo);
        break;
    case 'comment':
        handleComment($pdo);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

function handleAuth($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        // Check if user exists and password is correct
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.password, cu.display_name, cu.bio, cu.avatar_url 
                              FROM users u 
                              LEFT JOIN community_users cu ON u.id = cu.user_id 
                              WHERE u.email = ? OR u.username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Generate new auth token
            $authToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Update auth token
            $stmt = $pdo->prepare("UPDATE users SET auth_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$authToken, $tokenExpiry, $user['id']]);
            
            echo json_encode([
                'success' => true,
                'token' => $authToken,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'display_name' => $user['display_name'] ?? $user['username'],
                    'bio' => $user['bio'] ?? '',
                    'avatar_url' => $user['avatar_url'] ?? ''
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
    }
}

function handleProfile($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'GET') {
        // Get user profile with pets
        $stmt = $pdo->prepare("SELECT * FROM community_pets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => $user,
                'pets' => $pets
            ]
        ]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Update profile in community_users table
        $stmt = $pdo->prepare("UPDATE community_users SET display_name = ?, bio = ?, experience_level = ? WHERE user_id = ?");
        $stmt->execute([
            $data['display_name'] ?? $user['username'],
            $data['bio'] ?? '',
            $data['experience_level'] ?? 'beginner',
            $user['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    }
}

function handlePets($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM community_pets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $pets]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("INSERT INTO community_pets (user_id, pet_name, pet_type, breed, age, age_unit, gender, description) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $data['pet_name'],
            $data['pet_type'],
            $data['breed'] ?? '',
            $data['age'] ?? null,
            $data['age_unit'] ?? 'years',
            $data['gender'] ?? 'unknown',
            $data['description'] ?? ''
        ]);
        
        $petId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'data' => ['id' => $petId]]);
    }
}

function handlePhotos($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'GET') {
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, cu.display_name, cu.avatar_url,
                   (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = p.id AND reaction_type = 'like') as likes_count,
                   (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) as comments_count
            FROM community_posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN community_users cu ON u.id = cu.user_id
            WHERE p.post_type = 'photo' AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $photos]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $mediaUrls = json_encode([$data['photo_url']]);
        
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, title, content, post_type, media_urls, is_anonymous) 
                              VALUES (?, ?, ?, 'photo', ?, ?)");
        $stmt->execute([
            $user['id'],
            $data['caption'] ?? '',
            $data['caption'] ?? '',
            $mediaUrls,
            $data['is_anonymous'] ?? false
        ]);
        
        $postId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'data' => ['id' => $postId]]);
    }
}

function handleStories($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'GET') {
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, cu.display_name, cu.avatar_url,
                   (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = p.id AND reaction_type = 'like') as upvotes,
                   (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = p.id AND reaction_type = 'angry') as downvotes
            FROM community_posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN community_users cu ON u.id = cu.user_id
            WHERE p.post_type = 'text' AND p.status = 'active' AND LENGTH(p.content) > 100
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $stories]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (strlen($data['content']) > 250) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Story content must be 250 characters or less']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, title, content, post_type, is_anonymous) 
                              VALUES (?, ?, ?, 'text', ?)");
        $stmt->execute([
            $user['id'],
            $data['title'] ?? '',
            $data['content'],
            $data['is_anonymous'] ?? false
        ]);
        
        $postId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'data' => ['id' => $postId]]);
    }
}

function handleLike($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['post_id'];
        
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM community_post_reactions WHERE post_id = ? AND user_id = ? AND reaction_type = 'like'");
        $stmt->execute([$postId, $user['id']]);
        
        if ($stmt->fetch()) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM community_post_reactions WHERE post_id = ? AND user_id = ? AND reaction_type = 'like'");
            $stmt->execute([$postId, $user['id']]);
            echo json_encode(['success' => true, 'action' => 'unliked']);
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO community_post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, 'like')");
            $stmt->execute([$postId, $user['id']]);
            echo json_encode(['success' => true, 'action' => 'liked']);
        }
    }
}

function handleVote($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $postId = $data['post_id'];
        $voteType = $data['vote_type']; // 'upvote' or 'downvote'
        
        $reactionType = ($voteType === 'upvote') ? 'like' : 'angry';
        
        // Remove existing vote
        $stmt = $pdo->prepare("DELETE FROM community_post_reactions WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user['id']]);
        
        // Add new vote
        $stmt = $pdo->prepare("INSERT INTO community_post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $user['id'], $reactionType]);
        
        echo json_encode(['success' => true, 'action' => $voteType]);
    }
}

function handleComment($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $pdo->prepare("INSERT INTO community_comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['post_id'],
            $user['id'],
            $data['comment_text']
        ]);
        
        $commentId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'data' => ['id' => $commentId]]);
    }
}
?> 