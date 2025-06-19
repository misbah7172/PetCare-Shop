<?php
require_once '../config/database.php';
require_once '../src/session_manager.php';

// Check if user is admin
if (!AuthManager::isLoggedIn() || !AuthManager::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin privileges required.']);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    switch ($action) {
        case 'stats':
            handleGetStats($pdo);
            break;
        case 'users':
            if ($method === 'GET') {
                handleGetUsers($pdo);
            } elseif ($method === 'POST') {
                handleCreateUser($pdo);
            } elseif ($method === 'PUT') {
                handleUpdateUser($pdo);
            } elseif ($method === 'DELETE') {
                handleDeleteUser($pdo);
            }
            break;
        case 'pets':
            if ($method === 'GET') {
                handleGetPets($pdo);
            } elseif ($method === 'POST') {
                handleCreatePet($pdo);
            } elseif ($method === 'PUT') {
                handleUpdatePet($pdo);
            } elseif ($method === 'DELETE') {
                handleDeletePet($pdo);
            }
            break;
        case 'adoptions':
            handleGetAdoptions($pdo);
            break;
        case 'vets':
            if ($method === 'GET') {
                handleGetVets($pdo);
            } elseif ($method === 'POST') {
                handleCreateVet($pdo);
            } elseif ($method === 'PUT') {
                handleUpdateVet($pdo);
            } elseif ($method === 'DELETE') {
                handleDeleteVet($pdo);
            }
            break;
        case 'products':
            if ($method === 'GET') {
                handleGetProducts($pdo);
            } elseif ($method === 'POST') {
                handleCreateProduct($pdo);
            } elseif ($method === 'PUT') {
                handleUpdateProduct($pdo);
            } elseif ($method === 'DELETE') {
                handleDeleteProduct($pdo);
            }
            break;
        case 'support':
            handleGetSupportTickets($pdo);
            break;
        case 'reports':
            handleGenerateReport($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function handleGetStats($pdo) {
    try {
        // Get total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $stmt->fetch()['total'];

        // Get total pets
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pets");
        $totalPets = $stmt->fetch()['total'];

        // Get total adoptions
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM adoptions");
        $totalAdoptions = $stmt->fetch()['total'];

        // Get total products
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM shop_products");
        $totalProducts = $stmt->fetch()['total'];

        // Get open support tickets
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'");
        $openTickets = $stmt->fetch()['total'] ?? 0;

        // Calculate monthly revenue (mock data for now)
        $monthlyRevenue = 2850;

        // Get recent activity
        $stmt = $pdo->query("
            SELECT 'user_registration' as type, username as data, created_at as timestamp
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT 'adoption_application' as type, pet_name as data, created_at as timestamp
            FROM adoptions 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC 
            LIMIT 10
        ");
        $recentActivity = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $totalUsers,
                'pets' => $totalPets,
                'adoptions' => $totalAdoptions,
                'products' => $totalProducts,
                'support_tickets' => $openTickets,
                'revenue' => $monthlyRevenue,
                'recent_activity' => $recentActivity
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting statistics: ' . $e->getMessage());
    }
}

function handleGetUsers($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT id, username, email, first_name, last_name, role, status, created_at FROM users WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $countParams = [];

        if ($search) {
            $countSql .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = "%$search%";
            $countParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }

        if ($role) {
            $countSql .= " AND role = ?";
            $countParams[] = $role;
        }

        if ($status) {
            $countSql .= " AND status = ?";
            $countParams[] = $status;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalUsers = $countStmt->fetch()['total'];

        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalUsers,
                'pages' => ceil($totalUsers / $limit)
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting users: ' . $e->getMessage());
    }
}

function handleCreateUser($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['username', 'email', 'first_name', 'last_name', 'password', 'role'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, first_name, last_name, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $input['username'],
            $input['email'],
            $input['first_name'],
            $input['last_name'],
            $hashedPassword,
            $input['role']
        ]);

        $userId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $userId
        ]);
    } catch (Exception $e) {
        throw new Exception('Error creating user: ' . $e->getMessage());
    }
}

function handleUpdateUser($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['id'] ?? null;

        if (!$userId) {
            throw new Exception('User ID is required');
        }

        // Build update query dynamically
        $updateFields = [];
        $params = [];

        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'role', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }

        // Update password if provided
        if (isset($input['password']) && !empty($input['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        $params[] = $userId; // Add user ID for WHERE clause

        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error updating user: ' . $e->getMessage());
    }
}

function handleDeleteUser($pdo) {
    try {
        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            throw new Exception('User ID is required');
        }

        // Don't allow deletion of the current admin user
        $currentUser = AuthManager::getCurrentUser();
        if ($currentUser['id'] == $userId) {
            throw new Exception('Cannot delete your own account');
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('User not found');
        }

        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error deleting user: ' . $e->getMessage());
    }
}

function handleGetPets($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT p.*, u.username as owner_username 
            FROM pets p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE 1=1
        ";
        $params = [];

        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.breed LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        if ($type) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pets = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $pets
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting pets: ' . $e->getMessage());
    }
}

function handleCreatePet($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['name', 'type', 'age', 'gender'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO pets (name, type, breed, age, gender, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'available', NOW())
        ");
        
        $stmt->execute([
            $input['name'],
            $input['type'],
            $input['breed'] ?? '',
            $input['age'],
            $input['gender'],
            $input['description'] ?? ''
        ]);

        $petId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Pet added successfully',
            'pet_id' => $petId
        ]);
    } catch (Exception $e) {
        throw new Exception('Error creating pet: ' . $e->getMessage());
    }
}

function handleUpdatePet($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $petId = $input['id'] ?? null;

        if (!$petId) {
            throw new Exception('Pet ID is required');
        }

        $updateFields = [];
        $params = [];

        $allowedFields = ['name', 'type', 'breed', 'age', 'gender', 'description', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }

        $params[] = $petId;

        $sql = "UPDATE pets SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Pet updated successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error updating pet: ' . $e->getMessage());
    }
}

function handleDeletePet($pdo) {
    try {
        $petId = $_GET['id'] ?? null;

        if (!$petId) {
            throw new Exception('Pet ID is required');
        }

        $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
        $stmt->execute([$petId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Pet not found');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Pet deleted successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error deleting pet: ' . $e->getMessage());
    }
}

function handleGetAdoptions($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT a.*, p.name as pet_name, u.username as applicant_username 
            FROM adoptions a 
            LEFT JOIN pets p ON a.pet_id = p.id 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC
        ");
        $adoptions = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $adoptions
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting adoptions: ' . $e->getMessage());
    }
}

function handleGetVets($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM users WHERE role = 'veterinarian' 
            ORDER BY created_at DESC
        ");
        $vets = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $vets
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting veterinarians: ' . $e->getMessage());
    }
}

function handleGetProducts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM shop_products 
            ORDER BY created_at DESC
        ");
        $products = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting products: ' . $e->getMessage());
    }
}

function handleCreateProduct($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['name', 'category', 'price'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO shop_products (name, description, category, price, stock_quantity, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'] ?? '',
            $input['category'],
            $input['price'],
            $input['stock_quantity'] ?? 0
        ]);

        $productId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'product_id' => $productId
        ]);
    } catch (Exception $e) {
        throw new Exception('Error creating product: ' . $e->getMessage());
    }
}

function handleUpdateProduct($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = $input['id'] ?? null;

        if (!$productId) {
            throw new Exception('Product ID is required');
        }

        $updateFields = [];
        $params = [];

        $allowedFields = ['name', 'description', 'category', 'price', 'stock_quantity', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }

        $params[] = $productId;

        $sql = "UPDATE shop_products SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error updating product: ' . $e->getMessage());
    }
}

function handleDeleteProduct($pdo) {
    try {
        $productId = $_GET['id'] ?? null;

        if (!$productId) {
            throw new Exception('Product ID is required');
        }

        $stmt = $pdo->prepare("DELETE FROM shop_products WHERE id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Product not found');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error deleting product: ' . $e->getMessage());
    }
}

function handleCreateVet($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['username', 'email', 'first_name', 'last_name', 'password'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

        // Insert veterinarian
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, first_name, last_name, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'veterinarian', 'active', NOW())
        ");
        
        $stmt->execute([
            $input['username'],
            $input['email'],
            $input['first_name'],
            $input['last_name'],
            $hashedPassword
        ]);

        $vetId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Veterinarian created successfully',
            'vet_id' => $vetId
        ]);
    } catch (Exception $e) {
        throw new Exception('Error creating veterinarian: ' . $e->getMessage());
    }
}

function handleUpdateVet($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $vetId = $input['id'] ?? null;

        if (!$vetId) {
            throw new Exception('Veterinarian ID is required');
        }

        // Build update query dynamically
        $updateFields = [];
        $params = [];

        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }

        // Update password if provided
        if (isset($input['password']) && !empty($input['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        $params[] = $vetId; // Add vet ID for WHERE clause

        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ? AND role = 'veterinarian'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Veterinarian updated successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error updating veterinarian: ' . $e->getMessage());
    }
}

function handleDeleteVet($pdo) {
    try {
        $vetId = $_GET['id'] ?? null;

        if (!$vetId) {
            throw new Exception('Veterinarian ID is required');
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'veterinarian'");
        $stmt->execute([$vetId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Veterinarian not found');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Veterinarian deleted successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Error deleting veterinarian: ' . $e->getMessage());
    }
}

function handleGetSupportTickets($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT st.*, u.username as user_username 
            FROM support_tickets st 
            LEFT JOIN users u ON st.user_id = u.id 
            ORDER BY st.created_at DESC
        ");
        $tickets = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $tickets
        ]);
    } catch (Exception $e) {
        throw new Exception('Error getting support tickets: ' . $e->getMessage());
    }
}

function handleGenerateReport($pdo) {
    try {
        $reportType = $_GET['type'] ?? 'users';
        
        switch ($reportType) {
            case 'users':
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                break;
            case 'pets':
                $stmt = $pdo->query("SELECT * FROM pets ORDER BY created_at DESC");
                break;
            case 'adoptions':
                $stmt = $pdo->query("
                    SELECT a.*, p.name as pet_name, u.username as applicant_username 
                    FROM adoptions a 
                    LEFT JOIN pets p ON a.pet_id = p.id 
                    LEFT JOIN users u ON a.user_id = u.id 
                    ORDER BY a.created_at DESC
                ");
                break;
            case 'financial':
                // Mock financial data
                $data = [
                    ['type' => 'Shop Sale', 'amount' => 45.99, 'date' => date('Y-m-d')],
                    ['type' => 'Subscription', 'amount' => 29.99, 'date' => date('Y-m-d')],
                    ['type' => 'Donation', 'amount' => 100.00, 'date' => date('Y-m-d', strtotime('-1 day'))]
                ];
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'filename' => "financial_report_" . date('Y-m-d') . ".csv"
                ]);
                return;
            default:
                throw new Exception('Invalid report type');
        }

        $data = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'filename' => $reportType . "_report_" . date('Y-m-d') . ".csv"
        ]);
    } catch (Exception $e) {
        throw new Exception('Error generating report: ' . $e->getMessage());
    }
}
?>
