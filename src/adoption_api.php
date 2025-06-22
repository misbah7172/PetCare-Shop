<?php
// Adoption API - Handles adoption posts, requests, and chat functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config/database.php';

// Set a default user_id for testing if no session exists
$user_id = $_SESSION['user_id'] ?? 1;

$database = new Database();
$pdo = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $user_id, $action);
            break;
        case 'POST':
            handlePost($pdo, $user_id, $action);
            break;
        case 'PUT':
            handlePut($pdo, $user_id, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $user_id, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($pdo, $user_id, $action) {
    switch ($action) {
        case 'adoption_posts':
            getAdoptionPosts($pdo);
            break;
        case 'my_adoption_posts':
            getMyAdoptionPosts($pdo, $user_id);
            break;
        case 'adoption_requests':
            getAdoptionRequests($pdo, $user_id);
            break;
        case 'chat_conversations':
            getChatConversations($pdo, $user_id);
            break;
        case 'chat_messages':
            $conversation_id = $_GET['conversation_id'] ?? null;
            if ($conversation_id) {
                getChatMessages($pdo, $user_id, $conversation_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Conversation ID required']);
            }
            break;
        default:
            getAdoptionPosts($pdo);
    }
}

function handlePost($pdo, $user_id, $action) {
    // Handle both JSON and FormData
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'create_adoption_post':
            createAdoptionPost($pdo, $user_id, $input);
            break;
        case 'adoption_request':
            createAdoptionRequest($pdo, $user_id, $input);
            break;
        case 'send_message':
            sendChatMessage($pdo, $user_id, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($pdo, $user_id, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_adoption_request':
            $request_id = $_GET['id'] ?? null;
            if ($request_id) {
                updateAdoptionRequest($pdo, $user_id, $request_id, $input);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Request ID required']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($pdo, $user_id, $action) {
    switch ($action) {
        case 'adoption_post':
            $post_id = $_GET['id'] ?? null;
            if ($post_id) {
                deleteAdoptionPost($pdo, $user_id, $post_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Post ID required']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Get all adoption posts
function getAdoptionPosts($pdo) {
    $stmt = $pdo->prepare("
        SELECT ap.*, p.name as pet_name, p.type, p.breed, p.age, p.gender, p.photo_path,
               u.name as owner_name, u.username as owner_username
        FROM adoption_posts ap
        JOIN pets p ON ap.pet_id = p.id
        JOIN users u ON ap.user_id = u.id
        WHERE ap.status = 'available'
        ORDER BY ap.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'posts' => $posts]);
}

// Get user's adoption posts
function getMyAdoptionPosts($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT ap.*, p.name as pet_name, p.type, p.breed, p.age, p.gender, p.photo_path
        FROM adoption_posts ap
        JOIN pets p ON ap.pet_id = p.id
        WHERE ap.user_id = ?
        ORDER BY ap.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'posts' => $posts]);
}

// Create adoption post
function createAdoptionPost($pdo, $user_id, $data) {
    $pet_id = $data['pet_id'] ?? '';
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $adoption_fee = $data['adoption_fee'] ?? 0;
    $location = $data['location'] ?? '';
    $contact_phone = $data['contact_phone'] ?? '';
    $contact_email = $data['contact_email'] ?? '';
    
    if (empty($pet_id) || empty($title)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pet ID and title are required']);
        return;
    }
    
    // Verify pet exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, name FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found or does not belong to user']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert adoption post
        $stmt = $pdo->prepare("
            INSERT INTO adoption_posts (pet_id, user_id, title, description, adoption_fee, location, contact_phone, contact_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$pet_id, $user_id, $title, $description, $adoption_fee, $location, $contact_phone, $contact_email]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Failed to insert adoption post: ' . ($errorInfo[2] ?? 'Unknown error'));
        }
        
        $post_id = $pdo->lastInsertId();
        
        // Update pet to mark as for adoption
        $stmt = $pdo->prepare("UPDATE pets SET is_for_adoption = TRUE, adoption_post_id = ? WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$post_id, $pet_id, $user_id]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Failed to update pet adoption status: ' . ($errorInfo[2] ?? 'Unknown error'));
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'post_id' => $post_id, 'message' => 'Adoption post created successfully']);
    } catch (Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create adoption request
function createAdoptionRequest($pdo, $user_id, $data) {
    $adoption_post_id = $data['adoption_post_id'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($adoption_post_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Adoption post ID is required']);
        return;
    }
    
    // Get the owner user ID from the adoption post
    $stmt = $pdo->prepare("SELECT user_id FROM adoption_posts WHERE id = ?");
    $stmt->execute([$adoption_post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Adoption post not found']);
        return;
    }
    
    $owner_user_id = $post['user_id'];
    
    // Check if user already has a request for this post
    $stmt = $pdo->prepare("SELECT id FROM adoption_requests WHERE adoption_post_id = ? AND requester_user_id = ?");
    $stmt->execute([$adoption_post_id, $user_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'You have already requested this pet']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create adoption request
        $stmt = $pdo->prepare("
            INSERT INTO adoption_requests (adoption_post_id, requester_user_id, owner_user_id, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adoption_post_id, $user_id, $owner_user_id, $message]);
        
        $request_id = $pdo->lastInsertId();
        
        // Create chat conversation
        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations (adoption_request_id, user1_id, user2_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$request_id, $user_id, $owner_user_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'request_id' => $request_id, 'message' => 'Adoption request sent successfully']);
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// Get chat conversations for user
function getChatConversations($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT cc.*, ar.adoption_post_id, ap.title as post_title,
               p.name as pet_name, p.photo_path as pet_photo,
               u1.name as user1_name, u2.name as user2_name,
               u1.username as user1_username, u2.username as user2_username
        FROM chat_conversations cc
        JOIN adoption_requests ar ON cc.adoption_request_id = ar.id
        JOIN adoption_posts ap ON ar.adoption_post_id = ap.id
        JOIN pets p ON ap.pet_id = p.id
        JOIN users u1 ON cc.user1_id = u1.id
        JOIN users u2 ON cc.user2_id = u2.id
        WHERE cc.user1_id = ? OR cc.user2_id = ?
        ORDER BY cc.last_message_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $conversations = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

// Get chat messages
function getChatMessages($pdo, $user_id, $conversation_id) {
    // Verify user is part of this conversation
    $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT cm.*, u.name as sender_name, u.username as sender_username
        FROM chat_messages cm
        JOIN users u ON cm.sender_user_id = u.id
        WHERE cm.conversation_id = ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = TRUE WHERE conversation_id = ? AND sender_user_id != ?");
    $stmt->execute([$conversation_id, $user_id]);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

// Send chat message
function sendChatMessage($pdo, $user_id, $data) {
    $conversation_id = $data['conversation_id'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($conversation_id) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID and message are required']);
        return;
    }
    
    // Verify user is part of this conversation
    $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_user_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$conversation_id, $user_id, $message]);
        
        // Update conversation last message time
        $stmt = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$conversation_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

// Get adoption requests for user
function getAdoptionRequests($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT ar.*, ap.title as post_title, p.name as pet_name, p.photo_path as pet_photo,
               u.name as requester_name, u.username as requester_username
        FROM adoption_requests ar
        JOIN adoption_posts ap ON ar.adoption_post_id = ap.id
        JOIN pets p ON ap.pet_id = p.id
        JOIN users u ON ar.requester_user_id = u.id
        WHERE ar.owner_user_id = ?
        ORDER BY ar.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'requests' => $requests]);
}

// Update adoption request status
function updateAdoptionRequest($pdo, $user_id, $request_id, $data) {
    $status = $data['status'] ?? '';
    
    if (!in_array($status, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    // Verify user owns this request
    $stmt = $pdo->prepare("SELECT * FROM adoption_requests WHERE id = ? AND owner_user_id = ?");
    $stmt->execute([$request_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE adoption_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);
    
    echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
}

// Delete adoption post
function deleteAdoptionPost($pdo, $user_id, $post_id) {
    // Verify user owns this post
    $stmt = $pdo->prepare("SELECT pet_id FROM adoption_posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update pet to remove adoption status
        $stmt = $pdo->prepare("UPDATE pets SET is_for_adoption = FALSE, adoption_post_id = NULL WHERE id = ?");
        $stmt->execute([$post['pet_id']]);
        
        // Delete adoption post
        $stmt = $pdo->prepare("DELETE FROM adoption_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Adoption post deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

?>
