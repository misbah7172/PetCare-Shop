<?php
// Pet Controller for PawConnect API
// Handles all pet-related operations

class PetController {
    private $database;
    private $pdo;

    public function __construct($database) {
        $this->database = $database;
        $this->pdo = $this->database->getConnection();
    }

    public function getAllPets() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $species = $_GET['species'] ?? '';
            $status = $_GET['status'] ?? 'available';
            $location = $_GET['location'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $where = ["adoption_status = ?"];
            $params = [$status];
            
            if ($species) {
                $where[] = "species = ?";
                $params[] = $species;
            }
            
            if ($location) {
                $where[] = "location LIKE ?";
                $params[] = "%$location%";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Get pets with owner information
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name, u.phone as owner_phone, u.email as owner_email
                FROM pets p 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE $whereClause 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $pets = $stmt->fetchAll();
            
            // Get total count
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM pets WHERE $whereClause");
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetchColumn();
            
            // Process images and videos JSON
            foreach ($pets as &$pet) {
                $pet['images'] = json_decode($pet['images'] ?? '[]', true);
                $pet['videos'] = json_decode($pet['videos'] ?? '[]', true);
                $pet['social_media_profiles'] = json_decode($pet['social_media_profiles'] ?? '[]', true);
            }
            
            echo json_encode([
                'success' => true,
                'pets' => $pets,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$limit,
                    'total' => (int)$total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get pets', 'message' => $e->getMessage()]);
        }
    }

    public function getPet($petId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name, u.phone as owner_phone, u.email as owner_email
                FROM pets p 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$petId]);
            $pet = $stmt->fetch();

            if (!$pet) {
                http_response_code(404);
                echo json_encode(['error' => 'Pet not found']);
                return;
            }

            // Process JSON fields
            $pet['images'] = json_decode($pet['images'] ?? '[]', true);
            $pet['videos'] = json_decode($pet['videos'] ?? '[]', true);
            $pet['social_media_profiles'] = json_decode($pet['social_media_profiles'] ?? '[]', true);

            // Increment view count
            $updateStmt = $this->pdo->prepare("UPDATE pets SET views = views + 1 WHERE id = ?");
            $updateStmt->execute([$petId]);

            echo json_encode([
                'success' => true,
                'pet' => $pet
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get pet', 'message' => $e->getMessage()]);
        }
    }

    public function getFeaturedPets() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name 
                FROM pets p 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE p.is_featured = TRUE AND p.adoption_status = 'available' 
                ORDER BY p.created_at DESC 
                LIMIT 12
            ");
            $stmt->execute();
            $pets = $stmt->fetchAll();

            foreach ($pets as &$pet) {
                $pet['images'] = json_decode($pet['images'] ?? '[]', true);
                $pet['videos'] = json_decode($pet['videos'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'pets' => $pets
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get featured pets', 'message' => $e->getMessage()]);
        }
    }

    public function searchPets() {
        try {
            $query = $_GET['q'] ?? '';
            $species = $_GET['species'] ?? '';
            $breed = $_GET['breed'] ?? '';
            $age_min = $_GET['age_min'] ?? 0;
            $age_max = $_GET['age_max'] ?? 20;
            $size = $_GET['size'] ?? '';
            $gender = $_GET['gender'] ?? '';
            $good_with_kids = $_GET['good_with_kids'] ?? '';
            $good_with_pets = $_GET['good_with_pets'] ?? '';
            
            $where = ["adoption_status = 'available'"];
            $params = [];
            
            if ($query) {
                $where[] = "(name LIKE ? OR description LIKE ? OR breed LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }
            
            if ($species) {
                $where[] = "species = ?";
                $params[] = $species;
            }
            
            if ($breed) {
                $where[] = "breed LIKE ?";
                $params[] = "%$breed%";
            }
            
            if ($age_min || $age_max) {
                $where[] = "age_years BETWEEN ? AND ?";
                $params[] = $age_min;
                $params[] = $age_max;
            }
            
            if ($size) {
                $where[] = "size = ?";
                $params[] = $size;
            }
            
            if ($gender) {
                $where[] = "gender = ?";
                $params[] = $gender;
            }
            
            if ($good_with_kids !== '') {
                $where[] = "good_with_kids = ?";
                $params[] = $good_with_kids === 'true' ? 1 : 0;
            }
            
            if ($good_with_pets !== '') {
                $where[] = "good_with_pets = ?";
                $params[] = $good_with_pets === 'true' ? 1 : 0;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name 
                FROM pets p 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE $whereClause 
                ORDER BY p.created_at DESC 
                LIMIT 50
            ");
            $stmt->execute($params);
            $pets = $stmt->fetchAll();
            
            foreach ($pets as &$pet) {
                $pet['images'] = json_decode($pet['images'] ?? '[]', true);
                $pet['videos'] = json_decode($pet['videos'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'pets' => $pets,
                'count' => count($pets)
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Search failed', 'message' => $e->getMessage()]);
        }
    }    public function createPet($userId) {
        require_once '../middleware/AuthMiddleware.php';
        $authMiddleware = new AuthMiddleware($this->database);
        $user = $authMiddleware->authenticate();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['name', 'species', 'gender'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO pets (
                    owner_id, uploader_id, name, species, breed, age_years, age_months, gender, size, weight, color,
                    description, personality, health_status, vaccination_status, spayed_neutered,
                    microchip_id, adoption_status, adoption_fee, special_needs, good_with_kids,
                    good_with_pets, energy_level, training_level, house_trained, location,
                    images, videos, social_media_profiles
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user['id'], // owner_id (user uploading the pet)
                $user['id'], // uploader_id 
                $input['name'],
                $input['species'],
                $input['breed'] ?? null,
                $input['age_years'] ?? 0,
                $input['age_months'] ?? 0,
                $input['gender'],
                $input['size'] ?? 'medium',
                $input['weight'] ?? null,
                $input['color'] ?? null,
                $input['description'] ?? null,
                $input['personality'] ?? null,
                $input['health_status'] ?? null,
                $input['vaccination_status'] ?? null,
                isset($input['spayed_neutered']) ? (bool)$input['spayed_neutered'] : false,
                $input['microchip_id'] ?? null,
                $input['adoption_status'] ?? 'available',
                $input['adoption_fee'] ?? 0.00,
                $input['special_needs'] ?? null,
                isset($input['good_with_kids']) ? (bool)$input['good_with_kids'] : true,
                isset($input['good_with_pets']) ? (bool)$input['good_with_pets'] : true,
                $input['energy_level'] ?? 'medium',
                $input['training_level'] ?? 'none',
                isset($input['house_trained']) ? (bool)$input['house_trained'] : false,
                $input['location'] ?? null,
                json_encode($input['images'] ?? []),
                json_encode($input['videos'] ?? []),
                json_encode($input['social_media_profiles'] ?? [])
            ]);

            $petId = $this->pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Pet uploaded for adoption successfully',
                'pet_id' => $petId
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create pet', 'message' => $e->getMessage()]);
        }
    }

    public function updatePet($petId, $userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Check if user owns the pet or is admin
            $stmt = $this->pdo->prepare("SELECT owner_id FROM pets WHERE id = ?");
            $stmt->execute([$petId]);
            $pet = $stmt->fetch();
            
            if (!$pet) {
                http_response_code(404);
                echo json_encode(['error' => 'Pet not found']);
                return;
            }
            
            // Get user role
            $userStmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            if ($pet['owner_id'] != $userId && $user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'You can only update your own pets']);
                return;
            }

            // Build update query dynamically
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'name', 'species', 'breed', 'age_years', 'age_months', 'gender', 'size', 'weight',
                'color', 'description', 'personality', 'health_status', 'vaccination_status',
                'spayed_neutered', 'microchip_id', 'adoption_status', 'adoption_fee', 'special_needs',
                'good_with_kids', 'good_with_pets', 'energy_level', 'training_level', 'house_trained',
                'location', 'images', 'videos', 'social_media_profiles'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if (in_array($field, ['images', 'videos', 'social_media_profiles'])) {
                        $fields[] = "$field = ?";
                        $params[] = json_encode($input[$field]);
                    } else {
                        $fields[] = "$field = ?";
                        $params[] = $input[$field];
                    }
                }
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                return;
            }
            
            $params[] = $petId;
            $updateStmt = $this->pdo->prepare("UPDATE pets SET " . implode(', ', $fields) . " WHERE id = ?");
            $updateStmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Pet updated successfully'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update pet', 'message' => $e->getMessage()]);
        }
    }

    public function deletePet($petId, $userId) {
        try {
            // Check if user owns the pet or is admin
            $stmt = $this->pdo->prepare("SELECT owner_id FROM pets WHERE id = ?");
            $stmt->execute([$petId]);
            $pet = $stmt->fetch();
            
            if (!$pet) {
                http_response_code(404);
                echo json_encode(['error' => 'Pet not found']);
                return;
            }
            
            // Get user role
            $userStmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            if ($pet['owner_id'] != $userId && $user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'You can only delete your own pets']);
                return;
            }

            $deleteStmt = $this->pdo->prepare("DELETE FROM pets WHERE id = ?");
            $deleteStmt->execute([$petId]);

            echo json_encode([
                'success' => true,
                'message' => 'Pet deleted successfully'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete pet', 'message' => $e->getMessage()]);
        }
    }

    public function toggleFavorite($userId, $petId) {
        try {
            // Check if already favorited
            $stmt = $this->pdo->prepare("SELECT id FROM pet_favorites WHERE user_id = ? AND pet_id = ?");
            $stmt->execute([$userId, $petId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Remove favorite
                $deleteStmt = $this->pdo->prepare("DELETE FROM pet_favorites WHERE user_id = ? AND pet_id = ?");
                $deleteStmt->execute([$userId, $petId]);
                
                // Decrease favorites count
                $updateStmt = $this->pdo->prepare("UPDATE pets SET favorites_count = favorites_count - 1 WHERE id = ?");
                $updateStmt->execute([$petId]);
                
                $message = 'Pet removed from favorites';
                $favorited = false;
            } else {
                // Add favorite
                $insertStmt = $this->pdo->prepare("INSERT INTO pet_favorites (user_id, pet_id) VALUES (?, ?)");
                $insertStmt->execute([$userId, $petId]);
                
                // Increase favorites count
                $updateStmt = $this->pdo->prepare("UPDATE pets SET favorites_count = favorites_count + 1 WHERE id = ?");
                $updateStmt->execute([$petId]);
                
                $message = 'Pet added to favorites';
                $favorited = true;
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'favorited' => $favorited
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to toggle favorite', 'message' => $e->getMessage()]);
        }
    }

    public function getUserFavorites($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name as owner_first_name, u.last_name as owner_last_name 
                FROM pets p 
                INNER JOIN pet_favorites pf ON p.id = pf.pet_id 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE pf.user_id = ? 
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute([$userId]);
            $pets = $stmt->fetchAll();

            foreach ($pets as &$pet) {
                $pet['images'] = json_decode($pet['images'] ?? '[]', true);
                $pet['videos'] = json_decode($pet['videos'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'pets' => $pets
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get favorites', 'message' => $e->getMessage()]);
        }
    }
}
?>
