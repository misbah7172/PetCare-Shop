<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ProductController {
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
                    return $this->getProducts();
                } elseif ($segments[0] === 'categories') {
                    return $this->getCategories();
                } elseif ($segments[0] === 'search') {
                    return $this->searchProducts();
                } else {
                    return $this->getProduct($segments[0]);
                }
                break;
            case 'POST':
                return $this->createProduct();
                break;
            case 'PUT':
                return $this->updateProduct($segments[0]);
                break;
            case 'DELETE':
                return $this->deleteProduct($segments[0]);
                break;
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }

    public function getProducts() {
        $category = $_GET['category'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';

        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.status = 'active'";
        $params = [];

        if ($category) {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }

        $allowedSorts = ['name', 'price', 'created_at', 'stock_quantity'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY p.$sort $order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) FROM products p WHERE p.status = 'active'";
        $countParams = [];
        if ($category) {
            $countQuery .= " AND p.category_id = ?";
            $countParams[] = $category;
        }
        
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'products' => $products,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ];
    }

    public function getProduct($id) {
        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.id = ? AND p.status = 'active'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            return ['error' => 'Product not found'];
        }

        // Get product images
        $imageQuery = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC";
        $stmt = $this->db->prepare($imageQuery);
        $stmt->execute([$id]);
        $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['product' => $product];
    }

    public function getCategories() {
        $query = "SELECT * FROM product_categories WHERE status = 'active' ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['categories' => $categories];
    }

    public function searchProducts() {
        $searchTerm = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? null;
        $minPrice = $_GET['min_price'] ?? null;
        $maxPrice = $_GET['max_price'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        if (empty($searchTerm)) {
            http_response_code(400);
            return ['error' => 'Search term is required'];
        }

        $query = "SELECT p.*, pc.name as category_name 
                  FROM products p 
                  LEFT JOIN product_categories pc ON p.category_id = pc.id 
                  WHERE p.status = 'active' AND (p.name LIKE ? OR p.description LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%"];

        if ($category) {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if ($minPrice !== null) {
            $query .= " AND p.price >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $query .= " AND p.price <= ?";
            $params[] = $maxPrice;
        }

        $query .= " ORDER BY p.name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['products' => $products];
    }    public function createProduct() {
        $user = $this->authMiddleware->authenticate();
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'description', 'price', 'category_id'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                return ['error' => "Field '$field' is required"];
            }
        }

        // Generate SKU if not provided
        $sku = $input['sku'] ?? 'USER-' . time() . '-' . $user['id'];
        
        // Set default values for user uploads
        $stock_quantity = $input['stock_quantity'] ?? 1;
        $images = isset($input['images']) ? json_encode($input['images']) : '[]';
        $pet_species = isset($input['pet_species']) ? json_encode($input['pet_species']) : '[]';

        $query = "INSERT INTO products (
            owner_id, name, slug, description, sku, price, stock_quantity, 
            category_id, brand, pet_species, images, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
        // Generate slug
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])) . '-' . time();
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $user['id'], // owner_id - user who uploaded the product
            $input['name'],
            $slug,
            $input['description'],
            $sku,
            $input['price'],
            $stock_quantity,
            $input['category_id'],
            $input['brand'] ?? '',
            $pet_species,
            $images
        ]);

        if ($success) {
            $productId = $this->db->lastInsertId();
            http_response_code(201);
            return [
                'success' => true,
                'message' => 'Product uploaded successfully', 
                'product_id' => $productId
            ];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to create product'];
        }
    }

    public function updateProduct($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Admin access required'];
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Check if product exists
        $checkQuery = "SELECT id FROM products WHERE id = ?";
        $stmt = $this->db->prepare($checkQuery);
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Product not found'];
        }

        $updates = [];
        $values = [];
        
        $allowedFields = ['name', 'description', 'price', 'category_id', 'stock_quantity', 'sku', 'status'];
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
        $query = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($values);

        if ($success) {
            return ['message' => 'Product updated successfully'];
        } else {
            http_response_code(500);
            return ['error' => 'Failed to update product'];
        }
    }

    public function deleteProduct($id) {
        $user = $this->authMiddleware->authenticate();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            return ['error' => 'Admin access required'];
        }

        // Soft delete - set status to inactive
        $query = "UPDATE products SET status = 'inactive' WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$id]);

        if ($success && $stmt->rowCount() > 0) {
            return ['message' => 'Product deleted successfully'];
        } else {
            http_response_code(404);
            return ['error' => 'Product not found'];
        }
    }
}
?>
