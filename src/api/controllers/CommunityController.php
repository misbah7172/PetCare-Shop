<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CommunityController {
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
                    return $this->getPosts();
                } elseif ($segments[0] === 'posts' && isset($segments[1])) {
                    if ($segments[1] === 'search') {
                        return $this->searchPosts();
                    } else {
                        return $this->getPost($segments[1]);
                    }
                } elseif ($segments[0] === 'categories') {
                    return $this->getCategories();
                } elseif ($segments[0] === 'comments' && isset($segments[1])) {
                    return $this->getComments($segments[1]);
                } else {
                    return $this->getPosts();
                }
                break;
            case 'POST':
                if ($segments[0] === 'posts') {
                    return $this->createPost();
                } elseif ($segments[0] === 'comments') {
                    return $this->createComment();
                } elseif ($segments[0] === 'like') {
                    return $this->toggleLike();
                }
                break;
            case 'PUT':
                if ($segments[0] === 'posts' && isset($segments[1])) {
                    return $this->updatePost($segments[1]);
                } elseif ($segments[0] === 'comments' && isset($segments[1])) {
                    return $this->updateComment($segments[1]);
                }
                break;
            case 'DELETE':
                if ($segments[0] === 'posts' && isset($segments[1])) {
                    return $this->deletePost($segments[1]);
                } elseif ($segments[0] === 'comments' && isset($segments[1])) {
                    return $this->deleteComment($segments[1]);
                }
                break;
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }

    public function getPosts() {
        $category = $_GET['category'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';

        $query = "SELECT p.*, u.name as author_name, u.profile_image as author_image,
                         c.name as category_name,
                         (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.id) as comment_count,
                         (SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.id) as like_count
                  FROM community_posts p 
                  JOIN users u ON p.user_id = u.id 
                  LEFT JOIN community_categories c ON p.category_id = c.id 
                  WHERE p.status = 'active'";
        $params = [];

        if ($category) {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }

        $allowedSorts = ['created_at', 'like_count', 'comment_count', 'title'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        if ($sort === 'like_count' || $sort === 'comment_count') {
            $query .= " ORDER BY $sort $order, p.created_at DESC";
        } else {
            $query .= " ORDER BY p.$sort $order";
        }
        
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get post images for each post
        foreach ($posts as &$post) {
            $imageQuery = "SELECT image_url FROM community_post_images WHERE post_id = ? ORDER BY id";
            $stmt = $this->db->prepare($imageQuery);
            $stmt->execute([$post['id']]);
            $post['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return ['posts' => $posts];
    }

    public function getPost($id) {
        $query = "SELECT p.*, u.name as author_name, u.profile_image as author_image,
                         c.name as category_name,
                         (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.id) as comment_count,
                         (SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.id) as like_count
                  FROM community_posts p 
                  JOIN users u ON p.user_id = u.id 
                  LEFT JOIN community_categories c ON p.category_id = c.id 
                  WHERE p.id = ? AND p.status = 'active'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }

        // Get post images
        $imageQuery = "SELECT image_url FROM community_post_images WHERE post_id = ? ORDER BY id";
        $stmt = $this->db->prepare($imageQuery);
        $stmt->execute([$id]);
        $post['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get recent comments
        $commentQuery = "SELECT c.*, u.name as author_name, u.profile_image as author_image
                        FROM community_comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 10";
        $stmt = $this->db->prepare($commentQuery);
        $stmt->execute([$id]);
        $post['recent_comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['post' => $post];
    }

    public function getCategories() {
        $query = "SELECT * FROM community_categories WHERE status = 'active' ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['categories' => $categories];
    }

    public function getComments($postId) {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $query = "SELECT c.*, u.name as author_name, u.profile_image as author_image
                  FROM community_comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.post_id = ? 
                  ORDER BY c.created_at ASC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$postId, $limit, $offset]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['comments' => $comments];
    }

    public function searchPosts() {
        $searchTerm = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        if (empty($searchTerm)) {
            http_response_code(400);
            return ['error' => 'Search term is required'];
        }

        $query = "SELECT p.*, u.name as author_name, u.profile_image as author_image,
                         c.name as category_name,
                         (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.id) as comment_count,
                         (SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.id) as like_count
                  FROM community_posts p 
                  JOIN users u ON p.user_id = u.id 
                  LEFT JOIN community_categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' AND (p.title LIKE ? OR p.content LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%"];

        if ($category) {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }

        $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['posts' => $posts];
    }

    public function createPost() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'content'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO community_posts (user_id, title, content, category_id, status) 
                      VALUES (?, ?, ?, ?, 'active')";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $user['id'],
                $input['title'],
                $input['content'],
                $input['category_id'] ?? null
            ]);

            $postId = $this->db->lastInsertId();

            // Add images if provided
            if (!empty($input['images'])) {
                $imageQuery = "INSERT INTO community_post_images (post_id, image_url) VALUES (?, ?)";
                $stmt = $this->db->prepare($imageQuery);
                
                foreach ($input['images'] as $imageUrl) {
                    $stmt->execute([$postId, $imageUrl]);
                }
            }

            $this->db->commit();

            http_response_code(201);
            return ['message' => 'Post created successfully', 'post_id' => $postId];

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to create post'];
        }
    }    public function createComment() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['post_id', 'content'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        // Check if post exists and get post owner
        $postQuery = "SELECT id, user_id, title FROM community_posts WHERE id = ? AND status = 'active'";
        $stmt = $this->db->prepare($postQuery);
        $stmt->execute([$input['post_id']]);
        $post = $stmt->fetch();
        
        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }

        $query = "INSERT INTO community_comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $input['post_id'],
            $user['id'],
            $input['content']
        ]);

        if ($success) {
            $commentId = $this->db->lastInsertId();
            
            // Send notification to post owner (if not commenting on own post)
            if ($post['user_id'] != $user['id']) {
                $this->sendNotification($post['user_id'], [
                    'type' => 'comment',
                    'title' => 'New Comment',
                    'message' => "{$user['first_name']} {$user['last_name']} commented on your post: {$post['title']}",
                    'related_type' => 'community_post',
                    'related_id' => $input['post_id'],
                    'sender_id' => $user['id']
                ]);
            }
            
            http_response_code(201);
            return ['message' => 'Comment created successfully', 'comment_id' => $commentId];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to create comment'];
        }
    }

    public function toggleLike() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
          if (!isset($input['post_id'])) {
            http_response_code(400);
            return ['error' => 'post_id is required'];
        }

        // Check if post exists and get post owner
        $postQuery = "SELECT id, user_id, title FROM community_posts WHERE id = ? AND status = 'active'";
        $postStmt = $this->db->prepare($postQuery);
        $postStmt->execute([$input['post_id']]);
        $post = $postStmt->fetch();
        
        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }

        // Check if already liked
        $checkQuery = "SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute([$input['post_id'], $user['id']]);
        $existingLike = $stmt->fetch();

        if ($existingLike) {
            // Unlike
            $query = "DELETE FROM community_likes WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$existingLike['id']]);
            $action = 'unliked';        } else {
            // Like
            $query = "INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([$input['post_id'], $user['id']]);
            $action = 'liked';
            
            // Send notification to post owner (if not liking own post)
            if ($post['user_id'] != $user['id']) {
                $this->sendNotification($post['user_id'], [
                    'type' => 'like',
                    'title' => 'New Like',
                    'message' => "{$user['first_name']} {$user['last_name']} liked your post: {$post['title']}",
                    'related_type' => 'community_post',
                    'related_id' => $input['post_id'],
                    'sender_id' => $user['id']
                ]);
            }
        }

        if ($success) {
            return ['message' => "Post $action successfully"];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update like status'];
        }
    }

    public function updatePost($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Check if post exists and user owns it
        $checkQuery = "SELECT * FROM community_posts WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND user_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }

        $updates = [];
        $values = [];
        
        $allowedFields = ['title', 'content', 'category_id'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            return ['error' => 'No valid fields to update'];
        }

        $values[] = $id;
        $query = "UPDATE community_posts SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($values);

        if ($success) {
            return ['message' => 'Post updated successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update post'];
        }
    }

    public function updateComment($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['content'])) {
            http_response_code(400);
            return ['error' => 'content is required'];
        }

        // Check if comment exists and user owns it
        $checkQuery = "SELECT * FROM community_comments WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND user_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            http_response_code(404);
            return ['error' => 'Comment not found'];
        }

        $query = "UPDATE community_comments SET content = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$input['content'], $id]);

        if ($success) {
            return ['message' => 'Comment updated successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update comment'];
        }
    }

    public function deletePost($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        // Check if post exists and user owns it or is admin
        $checkQuery = "SELECT * FROM community_posts WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND user_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            http_response_code(404);
            return ['error' => 'Post not found'];
        }

        // Soft delete
        $query = "UPDATE community_posts SET status = 'deleted' WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$id]);

        if ($success) {
            return ['message' => 'Post deleted successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to delete post'];
        }
    }

    public function deleteComment($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            return ['error' => 'Authentication required'];
        }

        // Check if comment exists and user owns it or is admin
        $checkQuery = "SELECT * FROM community_comments WHERE id = ?";
        $params = [$id];
        
        if ($user['role'] !== 'admin') {
            $checkQuery .= " AND user_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute($params);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            http_response_code(404);
            return ['error' => 'Comment not found'];
        }

        $query = "DELETE FROM community_comments WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$id]);

        if ($success) {
            return ['message' => 'Comment deleted successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to delete comment'];
        }
    }

    private function sendNotification($user_id, $data) {
        try {
            $stmt = $this->db->prepare("
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
            error_log("Failed to send notification: " . $e->getMessage());
        }
    }
}
?>
