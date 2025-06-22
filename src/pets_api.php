<?php
// Pet Management API
// Handles CRUD operations for pets, activities, and photos

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
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Get the action from URL
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
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo, $user_id, $action) {
    switch ($action) {
        case 'pets':
            getUserPets($pdo, $user_id);
            break;
        case 'pet':
            $pet_id = $_GET['id'] ?? null;
            if ($pet_id) {
                getPet($pdo, $user_id, $pet_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Pet ID required']);
            }
            break;
        case 'activities':
            $pet_id = $_GET['pet_id'] ?? null;
            getActivities($pdo, $user_id, $pet_id);
            break;
        case 'photos':
            $pet_id = $_GET['pet_id'] ?? null;
            getPhotos($pdo, $user_id, $pet_id);
            break;
        default:
            getUserPets($pdo, $user_id);
    }
}

function handlePost($pdo, $user_id, $action) {
    // Handle both JSON and FormData
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Handle JSON data
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        // Handle FormData (for file uploads)
        $input = $_POST;
        // Handle file upload if present
        if (isset($_FILES['pet_photo'])) {
            $input['photo_file'] = $_FILES['pet_photo'];
        }
    }
    
    switch ($action) {
        case 'add_pet':
            addPet($pdo, $user_id, $input);
            break;
        case 'add_activity':
            addActivity($pdo, $user_id, $input);
            break;
        case 'upload_photo':
            uploadPhoto($pdo, $user_id);
            break;
        default:
            addPet($pdo, $user_id, $input);
    }
}

function handlePut($pdo, $user_id, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_pet':
            $pet_id = $_GET['id'] ?? null;
            if ($pet_id) {
                updatePet($pdo, $user_id, $pet_id, $input);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Pet ID required']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($pdo, $user_id, $action) {
    switch ($action) {
        case 'delete_pet':
            $pet_id = $_GET['id'] ?? null;
            if ($pet_id) {
                deletePet($pdo, $user_id, $pet_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Pet ID required']);
            }
            break;
        case 'delete_photo':
            $photo_id = $_GET['id'] ?? null;
            if ($photo_id) {
                deletePhoto($pdo, $user_id, $photo_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Photo ID required']);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getUserPets($pdo, $user_id) {
    $sql = "SELECT p.*, 
                   ap.id as adoption_post_id,
                   ap.title as adoption_title,
                   ap.description as adoption_description,
                   ap.adoption_fee,
                   ap.location as adoption_location,
                   ap.contact_phone,
                   ap.contact_email,
                   ap.status as adoption_status,
                   CASE WHEN ap.id IS NOT NULL THEN 1 ELSE 0 END as is_for_adoption
            FROM pets p 
            LEFT JOIN adoption_posts ap ON p.id = ap.pet_id AND ap.status = 'available'
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $pets = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'pets' => $pets]);
}

function getPet($pdo, $user_id, $pet_id) {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    $pet = $stmt->fetch();
    
    if ($pet) {
        echo json_encode(['success' => true, 'pet' => $pet]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
    }
}

function addPet($pdo, $user_id, $data) {
    // Handle both JSON and FormData field names
    $name = $data['name'] ?? $data['pet_name'] ?? '';
    $type = $data['type'] ?? $data['pet_type'] ?? '';
    $breed = $data['breed'] ?? $data['pet_breed'] ?? null;
    $age = $data['age'] ?? $data['pet_age'] ?? null;
    $gender = $data['gender'] ?? $data['pet_gender'] ?? null;
    $weight = $data['weight'] ?? $data['pet_weight'] ?? null;
    $description = $data['description'] ?? $data['pet_description'] ?? null;
    
    // Validate required fields
    if (empty($name) || empty($type)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pet name and type are required']);
        return;
    }      // Handle photo upload if present
    $photo_path = null;
    if (isset($data['photo_file']) && $data['photo_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $photo_path = handlePhotoUpload($data['photo_file']);
        } catch (Exception $e) {
            // Continue without photo if upload fails
            error_log("Photo upload failed: " . $e->getMessage());
        }
    }
    // Also check for direct file upload via $_FILES
    elseif (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
        try {
            $photo_path = handlePhotoUpload($_FILES['pet_photo']);
        } catch (Exception $e) {
            // Continue without photo if upload fails
            error_log("Photo upload failed: " . $e->getMessage());
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pets (user_id, name, type, breed, age, gender, weight, description, photo_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_id,
            $name,
            $type,
            $breed,
            $age,
            $gender,
            $weight,
            $description,
            $photo_path
        ]);
        
        if ($result) {
            $pet_id = $pdo->lastInsertId();
            echo json_encode([
                'success' => true, 
                'pet_id' => $pet_id, 
                'message' => 'Pet added successfully'
            ]);
        } else {
            $errorInfo = $stmt->errorInfo();
            http_response_code(500);
            echo json_encode([
                'error' => 'Database insert failed', 
                'sql_error' => $errorInfo[2] ?? 'Unknown SQL error',
                'debug_info' => $errorInfo
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage(),
            'code' => $e->getCode()        ]);
    }
}

function updatePet($pdo, $user_id, $pet_id, $data) {
    // Check if pet belongs to user
    $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE pets 
        SET name = ?, type = ?, breed = ?, age = ?, gender = ?, weight = ?, description = ?
        WHERE id = ? AND user_id = ?
    ");
    
    $result = $stmt->execute([
        $data['name'],
        $data['type'],
        $data['breed'] ?? null,
        $data['age'] ?? null,
        $data['gender'] ?? null,
        $data['weight'] ?? null,
        $data['description'] ?? null,
        $pet_id,
        $user_id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Pet updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update pet']);
    }
}

function deletePet($pdo, $user_id, $pet_id) {
    // Check if pet belongs to user
    $stmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $stmt->execute([$pet_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$pet_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Pet deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete pet']);
    }
}

function getActivities($pdo, $user_id, $pet_id = null) {
    if ($pet_id) {
        $stmt = $pdo->prepare("
            SELECT pa.*, p.name as pet_name 
            FROM pet_activities pa 
            JOIN pets p ON pa.pet_id = p.id 
            WHERE pa.user_id = ? AND pa.pet_id = ? 
            ORDER BY pa.activity_date DESC
        ");
        $stmt->execute([$user_id, $pet_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT pa.*, p.name as pet_name 
            FROM pet_activities pa 
            JOIN pets p ON pa.pet_id = p.id 
            WHERE pa.user_id = ? 
            ORDER BY pa.activity_date DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
    }
    
    $activities = $stmt->fetchAll();
    echo json_encode(['success' => true, 'activities' => $activities]);
}

function addActivity($pdo, $user_id, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO pet_activities (pet_id, user_id, activity_type, description) 
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['pet_id'],
        $user_id,
        $data['activity_type'],
        $data['description']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Activity added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add activity']);
    }
}

function getPhotos($pdo, $user_id, $pet_id = null) {
    if ($pet_id) {
        $stmt = $pdo->prepare("
            SELECT pp.*, p.name as pet_name 
            FROM pet_photos pp 
            JOIN pets p ON pp.pet_id = p.id 
            WHERE pp.user_id = ? AND pp.pet_id = ? 
            ORDER BY pp.uploaded_at DESC
        ");
        $stmt->execute([$user_id, $pet_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT pp.*, p.name as pet_name 
            FROM pet_photos pp 
            JOIN pets p ON pp.pet_id = p.id 
            WHERE pp.user_id = ? 
            ORDER BY pp.uploaded_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    
    $photos = $stmt->fetchAll();
    echo json_encode(['success' => true, 'photos' => $photos]);
}

function uploadPhoto($pdo, $user_id) {
    // Handle file upload - this would be more complex in a real implementation
    echo json_encode(['success' => true, 'message' => 'Photo upload functionality would be implemented here']);
}

function deletePhoto($pdo, $user_id, $photo_id) {
    $stmt = $pdo->prepare("DELETE FROM pet_photos WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$photo_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete photo']);
    }
}

// Helper function to handle photo uploads
function handlePhotoUpload($file) {
    // Create uploads directory if it doesn't exist
    $upload_dir = '../public/uploads/pets/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('pet_') . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload file.');
    }
    
    // Return relative path for database storage
    return 'uploads/pets/' . $filename;
}
?>
