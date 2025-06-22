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

switch ($action) {    case 'count':
        try {
            $query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'unread_count' => (int)$row['unread_count']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error getting unread count']);
        }
        break;
          case 'list':
        try {
            $limit = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;
            
            $query = "SELECT 
                        n.*,
                        CASE 
                            WHEN n.type = 'adoption_interest' THEN p.name
                            WHEN n.type = 'adoption_approved' THEN p.name
                            WHEN n.type = 'adoption_rejected' THEN p.name
                            WHEN n.type = 'shop_interest' THEN pr.name
                            WHEN n.type = 'comment' THEN 'Community Post'
                            WHEN n.type = 'like' THEN 'Community Post'
                            ELSE 'Notification'
                        END as item_name
                      FROM notifications n
                      LEFT JOIN pets p ON n.related_id = p.id AND n.type LIKE 'adoption_%'
                      LEFT JOIN products pr ON n.related_id = pr.id AND n.type = 'shop_interest'
                      WHERE n.user_id = ?
                      ORDER BY n.created_at DESC
                      LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $limit, $offset]);
            
            $notifications = [];
            while ($row = $stmt->fetch()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'title' => getNotificationTitle($row['type']),
                    'message' => $row['message'],
                    'item_name' => $row['item_name'],
                    'related_id' => $row['related_id'],
                    'is_read' => (bool)$row['is_read'],
                    'created_at' => $row['created_at'],
                    'time_ago' => timeAgo($row['created_at'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching notifications']);
        }
        break;
          case 'mark_read':
        try {
            $notification_id = $_POST['notification_id'] ?? $_GET['id'];
            
            if (!$notification_id) {
                throw new Exception('Notification ID is required');
            }
            
            $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$notification_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error marking notification as read']);
        }
        break;
          case 'mark_all_read':
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read',
                'affected_rows' => $stmt->rowCount()
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error marking all notifications as read']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getNotificationTitle($type) {
    $titles = [
        'adoption_interest' => 'Adoption Interest',
        'adoption_approved' => 'Adoption Approved',
        'adoption_rejected' => 'Adoption Declined',
        'shop_interest' => 'Shop Item Interest',
        'comment' => 'New Comment',
        'like' => 'New Like'
    ];
    
    return $titles[$type] ?? 'Notification';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
