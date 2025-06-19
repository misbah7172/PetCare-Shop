<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateToken($token) {
    global $pdo;
    if (empty($token)) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE auth_token = ? AND token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['user_id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function logActivity($userId, $activityType, $contentId = null, $metadata = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO community_activity_log (user_id, activity_type, content_id, metadata) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $activityType, $contentId, $metadata ? json_encode($metadata) : null]);
    } catch (Exception $e) {
        // Log error silently
    }
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Get authorization token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
$userId = validateToken($token);

// Route handling
try {
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'categories':
                    // Get all categories
                    $stmt = $pdo->query("SELECT * FROM community_categories WHERE is_active = 1 ORDER BY sort_order, name");
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'data' => $categories]);
                    break;

                case 'posts':
                    // Get posts with filters
                    $categoryId = $_GET['category_id'] ?? null;
                    $groupId = $_GET['group_id'] ?? null;
                    $limit = min(20, intval($_GET['limit'] ?? 10));
                    $offset = intval($_GET['offset'] ?? 0);
                    
                    $where = "WHERE p.status = 'active'";
                    $params = [];
                    
                    if ($categoryId) {
                        $where .= " AND p.category_id = ?";
                        $params[] = $categoryId;
                    }
                    if ($groupId) {
                        $where .= " AND p.group_id = ?";
                        $params[] = $groupId;
                    }
                    
                    $sql = "SELECT p.*, u.username, u.email, cu.display_name, cu.avatar_url,
                           c.name as category_name, g.name as group_name,
                           (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = p.id) as reaction_count,
                           (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id AND status = 'active') as comment_count
                           FROM community_posts p
                           LEFT JOIN users u ON p.user_id = u.id
                           LEFT JOIN community_users cu ON p.user_id = cu.user_id
                           LEFT JOIN community_categories c ON p.category_id = c.id
                           LEFT JOIN community_groups g ON p.group_id = g.id
                           $where
                           ORDER BY p.is_pinned DESC, p.created_at DESC
                           LIMIT ? OFFSET ?";
                    
                    $params[] = $limit;
                    $params[] = $offset;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $posts]);
                    break;

                case 'post':
                    // Get single post with comments
                    $postId = $_GET['id'] ?? null;
                    if (!$postId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Post ID required']);
                        break;
                    }
                    
                    // Get post details
                    $stmt = $pdo->prepare("SELECT p.*, u.username, u.email, cu.display_name, cu.avatar_url,
                                         c.name as category_name, g.name as group_name
                                         FROM community_posts p
                                         LEFT JOIN users u ON p.user_id = u.id
                                         LEFT JOIN community_users cu ON p.user_id = cu.user_id
                                         LEFT JOIN community_categories c ON p.category_id = c.id
                                         LEFT JOIN community_groups g ON p.group_id = g.id
                                         WHERE p.id = ? AND p.status = 'active'");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$post) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Post not found']);
                        break;
                    }
                    
                    // Get comments
                    $stmt = $pdo->prepare("SELECT c.*, u.username, u.email, cu.display_name, cu.avatar_url
                                         FROM community_comments c
                                         LEFT JOIN users u ON c.user_id = u.id
                                         LEFT JOIN community_users cu ON c.user_id = cu.user_id
                                         WHERE c.post_id = ? AND c.status = 'active'
                                         ORDER BY c.created_at ASC");
                    $stmt->execute([$postId]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get reactions
                    $stmt = $pdo->prepare("SELECT reaction_type, COUNT(*) as count FROM community_post_reactions WHERE post_id = ? GROUP BY reaction_type");
                    $stmt->execute([$postId]);
                    $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $post['comments'] = $comments;
                    $post['reactions'] = $reactions;
                    
                    echo json_encode(['success' => true, 'data' => $post]);
                    break;

                case 'groups':
                    // Get groups
                    $categoryId = $_GET['category_id'] ?? null;
                    $limit = min(20, intval($_GET['limit'] ?? 10));
                    $offset = intval($_GET['offset'] ?? 0);
                    
                    $where = "WHERE g.is_active = 1";
                    $params = [];
                    
                    if ($categoryId) {
                        $where .= " AND g.category_id = ?";
                        $params[] = $categoryId;
                    }
                    
                    $sql = "SELECT g.*, c.name as category_name, u.username as creator_name,
                           (SELECT COUNT(*) FROM community_group_members WHERE group_id = g.id AND is_active = 1) as member_count
                           FROM community_groups g
                           LEFT JOIN community_categories c ON g.category_id = c.id
                           LEFT JOIN users u ON g.created_by = u.id
                           $where
                           ORDER BY g.created_at DESC
                           LIMIT ? OFFSET ?";
                    
                    $params[] = $limit;
                    $params[] = $offset;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $groups]);
                    break;

                case 'events':
                    // Get events
                    $limit = min(20, intval($_GET['limit'] ?? 10));
                    $offset = intval($_GET['offset'] ?? 0);
                    
                    $sql = "SELECT e.*, u.username as creator_name, g.name as group_name,
                           (SELECT COUNT(*) FROM community_event_attendees WHERE event_id = e.id AND status = 'going') as attendee_count
                           FROM community_events e
                           LEFT JOIN users u ON e.created_by = u.id
                           LEFT JOIN community_groups g ON e.group_id = g.id
                           WHERE e.status = 'published' AND e.start_date > NOW()
                           ORDER BY e.start_date ASC
                           LIMIT ? OFFSET ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$limit, $offset]);
                    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $events]);
                    break;

                case 'profile':
                    // Get user profile
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['error' => 'Authentication required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("SELECT cu.*, u.email, u.created_at as user_created_at
                                         FROM community_users cu
                                         LEFT JOIN users u ON cu.user_id = u.id
                                         WHERE cu.user_id = ?");
                    $stmt->execute([$userId]);
                    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$profile) {
                        // Create profile if doesn't exist
                        $stmt = $pdo->prepare("INSERT INTO community_users (user_id, username, display_name) 
                                             SELECT id, email, email FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $stmt = $pdo->prepare("SELECT cu.*, u.email, u.created_at as user_created_at
                                             FROM community_users cu
                                             LEFT JOIN users u ON cu.user_id = u.id
                                             WHERE cu.user_id = ?");
                        $stmt->execute([$userId]);
                        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                    
                    // Get user's pets
                    $stmt = $pdo->prepare("SELECT * FROM community_pets WHERE user_id = ? AND is_public = 1");
                    $stmt->execute([$userId]);
                    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get user's badges
                    $stmt = $pdo->prepare("SELECT b.* FROM community_badges b
                                         INNER JOIN community_user_badges ub ON b.id = ub.badge_id
                                         WHERE ub.user_id = ?");
                    $stmt->execute([$userId]);
                    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $profile['pets'] = $pets;
                    $profile['badges'] = $badges;
                    
                    echo json_encode(['success' => true, 'data' => $profile]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        case 'POST':
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($endpoint) {
                case 'post':
                    // Create new post
                    $title = sanitizeInput($input['title'] ?? '');
                    $content = sanitizeInput($input['content'] ?? '');
                    $categoryId = $input['category_id'] ?? null;
                    $groupId = $input['group_id'] ?? null;
                    $postType = $input['post_type'] ?? 'text';
                    $mediaUrls = $input['media_urls'] ?? null;
                    $isAnonymous = $input['is_anonymous'] ?? false;
                    
                    if (empty($content)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Content is required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, category_id, group_id, title, content, post_type, media_urls, is_anonymous) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $categoryId, $groupId, $title, $content, $postType, $mediaUrls ? json_encode($mediaUrls) : null, $isAnonymous]);
                    
                    $postId = $pdo->lastInsertId();
                    logActivity($userId, 'post_created', $postId);
                    
                    echo json_encode(['success' => true, 'data' => ['id' => $postId]]);
                    break;

                case 'comment':
                    // Add comment
                    $postId = $input['post_id'] ?? null;
                    $content = sanitizeInput($input['content'] ?? '');
                    $parentId = $input['parent_id'] ?? null;
                    $isAnonymous = $input['is_anonymous'] ?? false;
                    
                    if (!$postId || empty($content)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Post ID and content are required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO community_comments (post_id, user_id, parent_id, content, is_anonymous) 
                                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$postId, $userId, $parentId, $content, $isAnonymous]);
                    
                    $commentId = $pdo->lastInsertId();
                    logActivity($userId, 'comment_added', $commentId);
                    
                    // Update post comment count
                    $stmt = $pdo->prepare("UPDATE community_posts SET comment_count = comment_count + 1 WHERE id = ?");
                    $stmt->execute([$postId]);
                    
                    echo json_encode(['success' => true, 'data' => ['id' => $commentId]]);
                    break;

                case 'reaction':
                    // Add reaction to post
                    $postId = $input['post_id'] ?? null;
                    $reactionType = $input['reaction_type'] ?? 'like';
                    
                    if (!$postId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Post ID is required']);
                        break;
                    }
                    
                    // Remove existing reaction if any
                    $stmt = $pdo->prepare("DELETE FROM community_post_reactions WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$postId, $userId]);
                    
                    // Add new reaction
                    $stmt = $pdo->prepare("INSERT INTO community_post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
                    $stmt->execute([$postId, $userId, $reactionType]);
                    
                    logActivity($userId, 'reaction_given', $postId, ['reaction_type' => $reactionType]);
                    
                    // Update post reaction count
                    $stmt = $pdo->prepare("UPDATE community_posts SET like_count = (SELECT COUNT(*) FROM community_post_reactions WHERE post_id = ?) WHERE id = ?");
                    $stmt->execute([$postId, $postId]);
                    
                    echo json_encode(['success' => true]);
                    break;

                case 'group':
                    // Create new group
                    $name = sanitizeInput($input['name'] ?? '');
                    $description = sanitizeInput($input['description'] ?? '');
                    $categoryId = $input['category_id'] ?? null;
                    $groupType = $input['group_type'] ?? 'public';
                    $maxMembers = $input['max_members'] ?? 1000;
                    
                    if (empty($name)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Group name is required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO community_groups (name, description, category_id, group_type, created_by, max_members) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $categoryId, $groupType, $userId, $maxMembers]);
                    
                    $groupId = $pdo->lastInsertId();
                    
                    // Add creator as admin
                    $stmt = $pdo->prepare("INSERT INTO community_group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
                    $stmt->execute([$groupId, $userId]);
                    
                    logActivity($userId, 'group_joined', $groupId, ['role' => 'admin']);
                    
                    echo json_encode(['success' => true, 'data' => ['id' => $groupId]]);
                    break;

                case 'event':
                    // Create new event
                    $title = sanitizeInput($input['title'] ?? '');
                    $description = sanitizeInput($input['description'] ?? '');
                    $eventType = $input['event_type'] ?? 'meetup';
                    $location = sanitizeInput($input['location'] ?? '');
                    $startDate = $input['start_date'] ?? null;
                    $endDate = $input['end_date'] ?? null;
                    $maxAttendees = $input['max_attendees'] ?? null;
                    $groupId = $input['group_id'] ?? null;
                    
                    if (empty($title) || empty($startDate) || empty($endDate)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Title, start date, and end date are required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO community_events (title, description, event_type, location, start_date, end_date, max_attendees, created_by, group_id) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $eventType, $location, $startDate, $endDate, $maxAttendees, $userId, $groupId]);
                    
                    $eventId = $pdo->lastInsertId();
                    
                    echo json_encode(['success' => true, 'data' => ['id' => $eventId]]);
                    break;

                case 'rsvp':
                    // RSVP to event
                    $eventId = $input['event_id'] ?? null;
                    $status = $input['status'] ?? 'going';
                    
                    if (!$eventId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Event ID is required']);
                        break;
                    }
                    
                    // Remove existing RSVP if any
                    $stmt = $pdo->prepare("DELETE FROM community_event_attendees WHERE event_id = ? AND user_id = ?");
                    $stmt->execute([$eventId, $userId]);
                    
                    // Add new RSVP
                    $stmt = $pdo->prepare("INSERT INTO community_event_attendees (event_id, user_id, status) VALUES (?, ?, ?)");
                    $stmt->execute([$eventId, $userId, $status]);
                    
                    logActivity($userId, 'event_rsvp', $eventId, ['status' => $status]);
                    
                    echo json_encode(['success' => true]);
                    break;

                case 'profile':
                    // Update user profile
                    $displayName = sanitizeInput($input['display_name'] ?? '');
                    $bio = sanitizeInput($input['bio'] ?? '');
                    $experienceLevel = $input['experience_level'] ?? 'beginner';
                    $location = sanitizeInput($input['location'] ?? '');
                    
                    $stmt = $pdo->prepare("INSERT INTO community_users (user_id, username, display_name, bio, experience_level, location) 
                                         VALUES (?, ?, ?, ?, ?, ?)
                                         ON DUPLICATE KEY UPDATE 
                                         display_name = VALUES(display_name),
                                         bio = VALUES(bio),
                                         experience_level = VALUES(experience_level),
                                         location = VALUES(location)");
                    $stmt->execute([$userId, $input['username'] ?? '', $displayName, $bio, $experienceLevel, $location]);
                    
                    echo json_encode(['success' => true]);
                    break;

                case 'pet':
                    // Add pet profile
                    $petName = sanitizeInput($input['pet_name'] ?? '');
                    $petType = $input['pet_type'] ?? 'other';
                    $breed = sanitizeInput($input['breed'] ?? '');
                    $age = $input['age'] ?? null;
                    $ageUnit = $input['age_unit'] ?? 'years';
                    $gender = $input['gender'] ?? 'unknown';
                    $description = sanitizeInput($input['description'] ?? '');
                    $photoUrl = $input['photo_url'] ?? null;
                    
                    if (empty($petName)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Pet name is required']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO community_pets (user_id, pet_name, pet_type, breed, age, age_unit, gender, description, photo_url) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $petName, $petType, $breed, $age, $ageUnit, $gender, $description, $photoUrl]);
                    
                    $petId = $pdo->lastInsertId();
                    
                    echo json_encode(['success' => true, 'data' => ['id' => $petId]]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        case 'PUT':
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($endpoint) {
                case 'post':
                    // Update post
                    $postId = $input['id'] ?? null;
                    $title = sanitizeInput($input['title'] ?? '');
                    $content = sanitizeInput($input['content'] ?? '');
                    
                    if (!$postId || empty($content)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Post ID and content are required']);
                        break;
                    }
                    
                    // Check if user owns the post
                    $stmt = $pdo->prepare("SELECT user_id FROM community_posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$post || $post['user_id'] != $userId) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Not authorized to edit this post']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE community_posts SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $content, $postId]);
                    
                    echo json_encode(['success' => true]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        case 'DELETE':
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                break;
            }
            
            switch ($endpoint) {
                case 'post':
                    // Delete post
                    $postId = $_GET['id'] ?? null;
                    
                    if (!$postId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Post ID is required']);
                        break;
                    }
                    
                    // Check if user owns the post
                    $stmt = $pdo->prepare("SELECT user_id FROM community_posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$post || $post['user_id'] != $userId) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Not authorized to delete this post']);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE community_posts SET status = 'deleted' WHERE id = ?");
                    $stmt->execute([$postId]);
                    
                    echo json_encode(['success' => true]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 