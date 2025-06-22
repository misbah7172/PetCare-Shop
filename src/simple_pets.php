<?php
// Simple Pet Addition with Adoption - Direct Database Approach
session_start();
require_once __DIR__ . '/../config/database.php';

// Set default user ID for testing
$user_id = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Get form data
        $pet_name = $_POST['pet_name'] ?? '';
        $pet_type = $_POST['pet_type'] ?? '';
        $pet_breed = $_POST['pet_breed'] ?? '';
        $pet_age = $_POST['pet_age'] ?? '';
        $pet_gender = $_POST['pet_gender'] ?? '';
        $pet_weight = $_POST['pet_weight'] ?? '';
        $pet_description = $_POST['pet_description'] ?? '';
        $mark_for_adoption = isset($_POST['mark_for_adoption']);
        $adoption_notes = $_POST['adoption_notes'] ?? '';
        $adoption_fee = $_POST['adoption_fee'] ?? 0;
        
        // Validate required fields
        if (empty($pet_name) || empty($pet_type)) {
            throw new Exception('Pet name and type are required');
        }
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../public/uploads/pets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['pet_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'pet_' . uniqid() . '.' . $file_extension;
            $photo_path = 'uploads/pets/' . $filename;
            
            if (move_uploaded_file($_FILES['pet_photo']['tmp_name'], $upload_dir . $filename)) {
                // Photo uploaded successfully
            } else {
                $photo_path = null; // Continue without photo if upload fails
            }
        }
        
        $pdo->beginTransaction();
        
        // Insert pet
        $stmt = $pdo->prepare("
            INSERT INTO pets (user_id, name, type, breed, age, gender, weight, description, photo_path, is_for_adoption) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $pet_name,
            $pet_type,
            $pet_breed,
            $pet_age,
            $pet_gender,
            $pet_weight,
            $pet_description,
            $photo_path,
            $mark_for_adoption ? 1 : 0
        ]);
        
        $pet_id = $pdo->lastInsertId();
        
        // If marked for adoption, create adoption post
        if ($mark_for_adoption) {
            $adoption_title = $pet_name . ' - Looking for a loving home';
            $adoption_description = $adoption_notes ?: $pet_name . ' is looking for a loving new home.';
            
            $stmt = $pdo->prepare("
                INSERT INTO adoption_posts (pet_id, user_id, title, description, adoption_fee, status) 
                VALUES (?, ?, ?, ?, ?, 'available')
            ");
            
            $stmt->execute([
                $pet_id,
                $user_id,
                $adoption_title,
                $adoption_description,
                $adoption_fee
            ]);
            
            $adoption_post_id = $pdo->lastInsertId();
            
            // Update pet with adoption post ID
            $stmt = $pdo->prepare("UPDATE pets SET adoption_post_id = ? WHERE id = ?");
            $stmt->execute([$adoption_post_id, $pet_id]);
        }
        
        $pdo->commit();
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $mark_for_adoption ? 'Pet added and posted for adoption successfully!' : 'Pet added successfully!',
            'pet_id' => $pet_id
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// If GET request, return current pets
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   ap.title as adoption_title,
                   ap.description as adoption_description,
                   ap.adoption_fee,
                   ap.status as adoption_status
            FROM pets p 
            LEFT JOIN adoption_posts ap ON p.adoption_post_id = ap.id
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC
        ");
        
        $stmt->execute([$user_id]);
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pets' => $pets]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
