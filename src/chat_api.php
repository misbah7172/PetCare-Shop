<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'get_messages':
        try {
            $type = $_GET['type'] ?? '';
            $related_id = $_GET['related_id'] ?? null;
            
            if (!$type || !$related_id) {
                throw new Exception('Type and related_id are required');
            }
            
            // Find conversation
            $stmt = $db->prepare("
                SELECT id FROM chat_conversations 
                WHERE type = ? AND related_id = ? 
                AND (participant_1_id = ? OR participant_2_id = ?)
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$type, $related_id, $user_id, $user_id]);
            $conversation = $stmt->fetch();
            
            if (!$conversation) {
                echo json_encode(['success' => true, 'messages' => []]);
                exit;
            }
            
            // Get messages
            $stmt = $db->prepare("
                SELECT m.*, u.first_name, u.last_name,
                       CONCAT(u.first_name, ' ', u.last_name) as sender_name
                FROM chat_messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$conversation['id']]);
            $messages = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()]);
        }
        break;
        
    case 'send_message':
        try {
            $type = $_POST['type'] ?? '';
            $related_id = $_POST['related_id'] ?? null;
            $message = $_POST['message'] ?? '';
            
            if (!$type || !$related_id || !$message) {
                throw new Exception('Type, related_id, and message are required');
            }
            
            // Find or create conversation
            $stmt = $db->prepare("
                SELECT id, participant_1_id, participant_2_id FROM chat_conversations 
                WHERE type = ? AND related_id = ? 
                AND (participant_1_id = ? OR participant_2_id = ?)
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$type, $related_id, $user_id, $user_id]);
            $conversation = $stmt->fetch();
            
            if (!$conversation) {
                // Find the other participant (owner of the item)
                $other_user_id = null;
                
                if ($type === 'adoption') {
                    $stmt = $db->prepare("SELECT uploader_id FROM pets WHERE id = ?");
                    $stmt->execute([$related_id]);
                    $pet = $stmt->fetch();
                    $other_user_id = $pet['uploader_id'] ?? null;
                } elseif ($type === 'shop') {
                    $stmt = $db->prepare("SELECT owner_id FROM products WHERE id = ?");
                    $stmt->execute([$related_id]);
                    $product = $stmt->fetch();
                    $other_user_id = $product['owner_id'] ?? null;
                }
                
                if (!$other_user_id || $other_user_id == $user_id) {
                    throw new Exception('Cannot create conversation - invalid participants');
                }
                
                // Create new conversation
                $stmt = $db->prepare("
                    INSERT INTO chat_conversations (type, related_id, participant_1_id, participant_2_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$type, $related_id, $user_id, $other_user_id]);
                $conversation_id = $db->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
            }
            
            // Insert message
            $stmt = $db->prepare("
                INSERT INTO chat_messages (conversation_id, sender_id, message)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$conversation_id, $user_id, $message]);
            
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error sending message: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_conversations':
        try {
            $stmt = $db->prepare("
                SELECT c.*, 
                       CASE 
                           WHEN c.type = 'adoption' THEN p.name
                           WHEN c.type = 'shop' THEN pr.name
                           ELSE 'Conversation'
                       END as item_name,
                       CASE 
                           WHEN c.participant_1_id = ? THEN CONCAT(u2.first_name, ' ', u2.last_name)
                           ELSE CONCAT(u1.first_name, ' ', u1.last_name)
                       END as other_user_name,
                       (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                       (SELECT created_at FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM chat_conversations c
                LEFT JOIN pets p ON c.related_id = p.id AND c.type = 'adoption'
                LEFT JOIN products pr ON c.related_id = pr.id AND c.type = 'shop'
                LEFT JOIN users u1 ON c.participant_1_id = u1.id
                LEFT JOIN users u2 ON c.participant_2_id = u2.id
                WHERE c.participant_1_id = ? OR c.participant_2_id = ?
                ORDER BY last_message_time DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching conversations: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
