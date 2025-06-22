<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class NotificationController {
    private $db;
    private $authMiddleware;

    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->authMiddleware = new AuthMiddleware($database);
    }

    public function handleRequest($method, $segments) {
        switch ($method) {
            case 'GET':
                if (empty($segments)) {
                    return $this->getNotifications();
                } elseif ($segments[0] === 'unread-count') {
                    return $this->getUnreadCount();
                } elseif ($segments[0] === 'settings') {
                    return $this->getNotificationSettings();
                } else {
                    return $this->getNotification($segments[0]);
                }
                break;
            case 'POST':
                if ($segments[0] === 'send') {
                    return $this->sendNotification();
                } elseif ($segments[0] === 'broadcast') {
                    return $this->broadcastNotification();
                } elseif ($segments[0] === 'mark-read') {
                    return $this->markAsRead();
                } elseif ($segments[0] === 'mark-all-read') {
                    return $this->markAllAsRead();
                }
                break;
            case 'PUT':
                if ($segments[0] === 'settings') {
                    return $this->updateNotificationSettings();
                } else {
                    return $this->updateNotification($segments[0]);
                }
                break;
            case 'DELETE':
                if ($segments[0] === 'clear-all') {
                    return $this->clearAllNotifications();
                } else {
                    return $this->deleteNotification($segments[0]);
                }
                break;
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }

    public function getNotifications() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $type = $_GET['type'] ?? null;
        $read_status = $_GET['read_status'] ?? null; // 'read', 'unread'
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $query = "SELECT n.* FROM notifications n WHERE n.user_id = ?";
        $params = [$user['id']];

        if ($type) {
            $query .= " AND n.type = ?";
            $params[] = $type;
        }

        if ($read_status === 'read') {
            $query .= " AND n.read_at IS NOT NULL";
        } elseif ($read_status === 'unread') {
            $query .= " AND n.read_at IS NULL";
        }

        $query .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON data for each notification
        foreach ($notifications as &$notification) {
            if ($notification['data']) {
                $notification['data'] = json_decode($notification['data'], true);
            }
        }

        return ['notifications' => $notifications];
    }

    public function getNotification($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "SELECT n.* FROM notifications n WHERE n.id = ? AND n.user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id, $user['id']]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$notification) {
            http_response_code(404);
            return ['error' => 'Notification not found'];
        }

        // Parse JSON data
        if ($notification['data']) {
            $notification['data'] = json_decode($notification['data'], true);
        }

        return ['notification' => $notification];
    }

    public function getUnreadCount() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND read_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['unread_count' => (int)$result['unread_count']];
    }

    public function getNotificationSettings() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "SELECT * FROM notification_settings WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user['id']]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            // Return default settings if none exist
            $settings = [
                'user_id' => $user['id'],
                'email_notifications' => 1,
                'push_notifications' => 1,
                'sms_notifications' => 0,
                'reminder_notifications' => 1,
                'adoption_updates' => 1,
                'appointment_reminders' => 1,
                'community_notifications' => 1,
                'promotional_emails' => 0,
                'emergency_alerts' => 1
            ];
        }

        return ['settings' => $settings];
    }

    public function sendNotification() {
        $user = $this->authMiddleware->authenticate();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Admin access required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['user_id', 'title', 'message', 'type'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        $allowedTypes = ['reminder', 'adoption', 'appointment', 'community', 'emergency', 'system', 'promotional'];
        if (!in_array($input['type'], $allowedTypes)) {
            http_response_code(400);
            return ['error' => 'Invalid notification type'];
        }

        // Verify target user exists
        $userQuery = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->db->prepare($userQuery);
        $stmt->execute([$input['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Target user not found'];
        }

        $query = "INSERT INTO notifications (user_id, type, title, message, data, action_url) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $input['user_id'],
            $input['type'],
            $input['title'],
            $input['message'],
            json_encode($input['data'] ?? []),
            $input['action_url'] ?? null
        ]);

        if ($success) {
            $notificationId = $this->db->lastInsertId();
            
            // TODO: Send actual push notification, email, SMS based on user preferences
            $this->sendActualNotification($input['user_id'], $input);
            
            http_response_code(201);
            return [
                'message' => 'Notification sent successfully',
                'notification_id' => $notificationId
            ];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to send notification'];
        }
    }

    public function broadcastNotification() {
        $user = $this->authMiddleware->authenticate();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Admin access required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'message', 'type'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        // Get target users based on criteria
        $targetQuery = "SELECT id FROM users WHERE status = 'active'";
        $params = [];

        if (!empty($input['user_role'])) {
            $targetQuery .= " AND role = ?";
            $params[] = $input['user_role'];
        }

        if (!empty($input['subscription_type'])) {
            $targetQuery .= " AND id IN (SELECT user_id FROM subscriptions WHERE plan_id IN 
                                        (SELECT id FROM subscription_plans WHERE type = ?) AND status = 'active')";
            $params[] = $input['subscription_type'];
        }

        $stmt = $this->db->prepare($targetQuery);
        $stmt->execute($params);
        $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($targetUsers)) {
            http_response_code(400);
            return ['error' => 'No target users found'];
        }

        try {
            $this->db->beginTransaction();

            $notificationQuery = "INSERT INTO notifications (user_id, type, title, message, data, action_url) 
                                 VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($notificationQuery);
            
            $sentCount = 0;
            foreach ($targetUsers as $userId) {
                $success = $stmt->execute([
                    $userId,
                    $input['type'],
                    $input['title'],
                    $input['message'],
                    json_encode($input['data'] ?? []),
                    $input['action_url'] ?? null
                ]);
                
                if ($success) {
                    $sentCount++;
                }
            }

            $this->db->commit();

            return [
                'message' => 'Broadcast notification sent successfully',
                'sent_count' => $sentCount,
                'target_count' => count($targetUsers)
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to send broadcast notification'];
        }
    }

    public function markAsRead() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['notification_id'])) {
            http_response_code(400);
            return ['error' => 'notification_id is required'];
        }

        $query = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP 
                  WHERE id = ? AND user_id = ? AND read_at IS NULL";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$input['notification_id'], $user['id']]);

        if ($success && $stmt->rowCount() > 0) {
            return ['message' => 'Notification marked as read'];
        } else {
            http_response_code(404);
            return ['error' => 'Notification not found or already read'];
        }
    }

    public function markAllAsRead() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP 
                  WHERE user_id = ? AND read_at IS NULL";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$user['id']]);

        if ($success) {
            $markedCount = $stmt->rowCount();
            return [
                'message' => 'All notifications marked as read',
                'marked_count' => $markedCount
            ];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to mark notifications as read'];
        }
    }

    public function updateNotificationSettings() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Check if settings exist
        $checkQuery = "SELECT user_id FROM notification_settings WHERE user_id = ?";
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute([$user['id']]);
        $exists = $stmt->fetch();

        $allowedFields = [
            'email_notifications', 'push_notifications', 'sms_notifications',
            'reminder_notifications', 'adoption_updates', 'appointment_reminders',
            'community_notifications', 'promotional_emails', 'emergency_alerts'
        ];

        if ($exists) {
            // Update existing settings
            $updates = [];
            $values = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = (int)$input[$field]; // Convert to boolean integer
                }
            }

            if (empty($updates)) {
                http_response_code(400);
                return ['error' => 'No valid fields to update'];
            }

            $values[] = $user['id'];
            $query = "UPDATE notification_settings SET " . implode(', ', $updates) . " WHERE user_id = ?";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute($values);
        } else {
            // Create new settings
            $fields = ['user_id'];
            $values = [$user['id']];
            $placeholders = ['?'];
            
            foreach ($allowedFields as $field) {
                $fields[] = $field;
                $values[] = isset($input[$field]) ? (int)$input[$field] : 1; // Default to enabled
                $placeholders[] = '?';
            }

            $query = "INSERT INTO notification_settings (" . implode(', ', $fields) . ") 
                      VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute($values);
        }

        if ($success) {
            return ['message' => 'Notification settings updated successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update notification settings'];
        }
    }

    public function updateNotification($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Admin access required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $updates = [];
        $values = [];
        
        $allowedFields = ['title', 'message', 'action_url', 'data'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'data') {
                    $updates[] = "$field = ?";
                    $values[] = json_encode($input[$field]);
                } else {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            return ['error' => 'No valid fields to update'];
        }

        $values[] = $id;
        $query = "UPDATE notifications SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($values);

        if ($success && $stmt->rowCount() > 0) {
            return ['message' => 'Notification updated successfully'];
        } else {
            http_response_code(404);
            return ['error' => 'Notification not found'];
        }
    }

    public function deleteNotification($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$id, $user['id']]);

        if ($success && $stmt->rowCount() > 0) {
            return ['message' => 'Notification deleted successfully'];
        } else {
            http_response_code(404);
            return ['error' => 'Notification not found'];
        }
    }

    public function clearAllNotifications() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $query = "DELETE FROM notifications WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$user['id']]);

        if ($success) {
            $deletedCount = $stmt->rowCount();
            return [
                'message' => 'All notifications cleared successfully',
                'deleted_count' => $deletedCount
            ];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to clear notifications'];
        }
    }

    private function sendActualNotification($userId, $notificationData) {
        // Get user's notification preferences
        $settingsQuery = "SELECT * FROM notification_settings WHERE user_id = ?";
        $stmt = $this->db->prepare($settingsQuery);
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            return; // No preferences set, skip actual sending
        }

        // TODO: Implement actual notification sending
        // - Email notifications via SMTP/SendGrid/etc.
        // - Push notifications via Firebase/OneSignal/etc.
        // - SMS notifications via Twilio/etc.
        
        // For now, just log the notification (in a real app, you'd send actual notifications)
        error_log("Notification would be sent to user $userId: " . json_encode($notificationData));
    }
}
?>
