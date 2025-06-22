<?php
// Chat Controller for adoption and shop communications
require_once '../middleware/AuthMiddleware.php';

class ChatController {
    private $pdo;
    private $authMiddleware;

    public function __construct($database) {
        $this->pdo = $database->getConnection();
        $this->authMiddleware = new AuthMiddleware($database);
    }

    // Start a new conversation (adoption or shop)
    public function startConversation($data) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $type = $data['type'] ?? ''; // 'adoption' or 'shop'
        $related_id = $data['related_id'] ?? null;
        $other_user_id = $data['other_user_id'] ?? null;

        if (empty($type) || !$related_id || !$other_user_id) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        try {
            // Check if conversation already exists
            $stmt = $this->pdo->prepare("
                SELECT id FROM chat_conversations 
                WHERE type = ? AND related_id = ? 
                AND ((participant_1_id = ? AND participant_2_id = ?) 
                OR (participant_1_id = ? AND participant_2_id = ?))
            ");
            $stmt->execute([
                $type, $related_id, 
                $user['id'], $other_user_id,
                $other_user_id, $user['id']
            ]);
            
            $existing = $stmt->fetch();
            if ($existing) {
                return ['success' => true, 'conversation_id' => $existing['id'], 'message' => 'Existing conversation found'];
            }

            // Create new conversation
            $stmt = $this->pdo->prepare("
                INSERT INTO chat_conversations (type, related_id, participant_1_id, participant_2_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$type, $related_id, $user['id'], $other_user_id]);
            
            $conversation_id = $this->pdo->lastInsertId();

            // Send notification to the other user
            $this->sendNotification($other_user_id, [
                'type' => 'chat_started',
                'title' => 'New Chat Started',
                'message' => 'Someone wants to chat about your ' . $type,
                'related_type' => 'conversation',
                'related_id' => $conversation_id,
                'sender_id' => $user['id']
            ]);

            return ['success' => true, 'conversation_id' => $conversation_id];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error starting conversation: ' . $e->getMessage()];
        }
    }

    // Send a message in a conversation
    public function sendMessage($data) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $conversation_id = $data['conversation_id'] ?? null;
        $message = trim($data['message'] ?? '');
        $message_type = $data['message_type'] ?? 'text';

        if (!$conversation_id || empty($message)) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        try {
            // Verify user is part of this conversation
            $stmt = $this->pdo->prepare("
                SELECT participant_1_id, participant_2_id 
                FROM chat_conversations 
                WHERE id = ? AND (participant_1_id = ? OR participant_2_id = ?)
            ");
            $stmt->execute([$conversation_id, $user['id'], $user['id']]);
            $conversation = $stmt->fetch();

            if (!$conversation) {
                return ['success' => false, 'message' => 'Conversation not found or unauthorized'];
            }

            // Insert message
            $stmt = $this->pdo->prepare("
                INSERT INTO chat_messages (conversation_id, sender_id, message, message_type)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$conversation_id, $user['id'], $message, $message_type]);

            // Update conversation last message time
            $stmt = $this->pdo->prepare("
                UPDATE chat_conversations 
                SET last_message_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$conversation_id]);

            // Send notification to the other participant
            $other_user_id = ($conversation['participant_1_id'] == $user['id']) 
                ? $conversation['participant_2_id'] 
                : $conversation['participant_1_id'];

            $this->sendNotification($other_user_id, [
                'type' => 'new_message',
                'title' => 'New Message',
                'message' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
                'related_type' => 'conversation',
                'related_id' => $conversation_id,
                'sender_id' => $user['id']
            ]);

            return ['success' => true, 'message' => 'Message sent'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending message: ' . $e->getMessage()];
        }
    }

    // Get conversation messages
    public function getMessages($conversation_id, $page = 1, $limit = 50) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        try {
            // Verify user is part of this conversation
            $stmt = $this->pdo->prepare("
                SELECT * FROM chat_conversations 
                WHERE id = ? AND (participant_1_id = ? OR participant_2_id = ?)
            ");
            $stmt->execute([$conversation_id, $user['id'], $user['id']]);
            $conversation = $stmt->fetch();

            if (!$conversation) {
                return ['success' => false, 'message' => 'Conversation not found'];
            }

            $offset = ($page - 1) * $limit;

            // Get messages
            $stmt = $this->pdo->prepare("
                SELECT cm.*, u.username, u.first_name, u.last_name 
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.id
                WHERE cm.conversation_id = ?
                ORDER BY cm.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$conversation_id, $limit, $offset]);
            $messages = $stmt->fetchAll();

            // Mark messages as read
            $stmt = $this->pdo->prepare("
                UPDATE chat_messages 
                SET is_read = TRUE 
                WHERE conversation_id = ? AND sender_id != ?
            ");
            $stmt->execute([$conversation_id, $user['id']]);

            return [
                'success' => true,
                'conversation' => $conversation,
                'messages' => array_reverse($messages) // Reverse to show oldest first
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()];
        }
    }

    // Get user's conversations
    public function getUserConversations() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT cc.*, 
                       u1.username as participant_1_name,
                       u2.username as participant_2_name,
                       cm.message as last_message,
                       cm.created_at as last_message_time,
                       (SELECT COUNT(*) FROM chat_messages 
                        WHERE conversation_id = cc.id AND sender_id != ? AND is_read = FALSE) as unread_count
                FROM chat_conversations cc
                LEFT JOIN users u1 ON cc.participant_1_id = u1.id
                LEFT JOIN users u2 ON cc.participant_2_id = u2.id
                LEFT JOIN chat_messages cm ON cc.id = cm.conversation_id 
                    AND cm.created_at = cc.last_message_at
                WHERE cc.participant_1_id = ? OR cc.participant_2_id = ?
                ORDER BY cc.last_message_at DESC
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id']]);
            $conversations = $stmt->fetchAll();

            return ['success' => true, 'conversations' => $conversations];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching conversations: ' . $e->getMessage()];
        }
    }

    private function sendNotification($user_id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_type, related_id, sender_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $data['type'],
                $data['title'],
                $data['message'],
                $data['related_type'] ?? null,
                $data['related_id'] ?? null,
                $data['sender_id'] ?? null
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to send notification: " . $e->getMessage());
        }
    }
}
?>
