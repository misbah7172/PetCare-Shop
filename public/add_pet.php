<?php
// Simple Pet Addition - Direct in public folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Include database config
require_once '../config/database.php';

// Set user ID (using session or default for testing)
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
        $adoption_reason = $_POST['adoption_reason'] ?? '';
        $adoption_notes = $_POST['adoption_notes'] ?? '';
        $adoption_fee = $_POST['adoption_fee'] ?? 0;
        $urgent_adoption = isset($_POST['urgent_adoption']);
        
        // Validate required fields
        if (empty($pet_name) || empty($pet_type)) {
            throw new Exception('Pet name and type are required');
        }
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/pets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['pet_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'pet_' . uniqid() . '.' . $file_extension;
            $photo_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($_FILES['pet_photo']['tmp_name'], $photo_path)) {
                $photo_path = null; // Continue without photo if upload fails
            }
        }
        
        $pdo->beginTransaction();
        
        // Insert pet
        $stmt = $pdo->prepare("
            INSERT INTO pets (user_id, name, type, breed, age, gender, weight, description, photo_path, is_for_adoption) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
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
        
        if (!$result) {
            throw new Exception('Failed to insert pet');
        }
        
        $pet_id = $pdo->lastInsertId();
        
        // If marked for adoption, create adoption post
        if ($mark_for_adoption) {
            $adoption_title = $pet_name . ' - Looking for a loving home';
            if ($urgent_adoption) {
                $adoption_title = '🚨 URGENT: ' . $adoption_title;
            }
            
            $adoption_description = $adoption_notes;
            if (empty($adoption_description)) {
                $adoption_description = $pet_name . ' is looking for a loving new home.';
                if (!empty($adoption_reason)) {
                    $adoption_description .= ' Reason: ' . $adoption_reason;
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO adoption_posts (pet_id, user_id, title, description, adoption_fee, status, urgent) 
                VALUES (?, ?, ?, ?, ?, 'available', ?)
            ");
            
            $result = $stmt->execute([
                $pet_id,
                $user_id,
                $adoption_title,
                $adoption_description,
                $adoption_fee,
                $urgent_adoption ? 1 : 0
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create adoption post');
            }
            
            $adoption_post_id = $pdo->lastInsertId();
            
            // Update pet with adoption post ID
            $stmt = $pdo->prepare("UPDATE pets SET adoption_post_id = ? WHERE id = ?");
            $stmt->execute([$adoption_post_id, $pet_id]);
        }
        
        $pdo->commit();
        
        // Return JSON response
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

// If not POST, return error
header('Content-Type: application/json');
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
?>
