<?php
// Veterinarian Controller for PawConnect API
// Handles veterinarian registration and management

class VeterinarianController {
    private $database;
    private $pdo;

    public function __construct($database) {
        $this->database = $database;
        $this->pdo = $this->database->getConnection();
    }

    public function getAllVeterinarians() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $specialty = $_GET['specialty'] ?? '';
            $location = $_GET['location'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $where = ["v.profile_verified = TRUE"];
            $params = [];
            
            if ($specialty) {
                $where[] = "JSON_SEARCH(v.specialties, 'one', ?) IS NOT NULL";
                $params[] = $specialty;
            }
            
            if ($location) {
                $where[] = "(v.clinic_address LIKE ? OR u.city LIKE ?)";
                $params[] = "%$location%";
                $params[] = "%$location%";
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $this->pdo->prepare("
                SELECT v.*, u.first_name, u.last_name, u.email, u.phone, u.profile_image
                FROM veterinarians v
                INNER JOIN users u ON v.user_id = u.id
                WHERE $whereClause
                ORDER BY v.rating DESC, v.total_reviews DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $vets = $stmt->fetchAll();
            
            // Process JSON fields
            foreach ($vets as &$vet) {
                $vet['specialties'] = json_decode($vet['specialties'] ?? '[]', true);
                $vet['services_offered'] = json_decode($vet['services_offered'] ?? '[]', true);
                $vet['languages_spoken'] = json_decode($vet['languages_spoken'] ?? '[]', true);
                $vet['availability_schedule'] = json_decode($vet['availability_schedule'] ?? '[]', true);
                $vet['insurance_accepted'] = json_decode($vet['insurance_accepted'] ?? '[]', true);
                $vet['payment_methods'] = json_decode($vet['payment_methods'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'veterinarians' => $vets
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get veterinarians', 'message' => $e->getMessage()]);
        }
    }

    public function getVeterinarian($vetId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.*, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.bio
                FROM veterinarians v
                INNER JOIN users u ON v.user_id = u.id
                WHERE v.id = ?
            ");
            $stmt->execute([$vetId]);
            $vet = $stmt->fetch();

            if (!$vet) {
                http_response_code(404);
                echo json_encode(['error' => 'Veterinarian not found']);
                return;
            }

            // Process JSON fields
            $vet['specialties'] = json_decode($vet['specialties'] ?? '[]', true);
            $vet['services_offered'] = json_decode($vet['services_offered'] ?? '[]', true);
            $vet['languages_spoken'] = json_decode($vet['languages_spoken'] ?? '[]', true);
            $vet['availability_schedule'] = json_decode($vet['availability_schedule'] ?? '[]', true);
            $vet['insurance_accepted'] = json_decode($vet['insurance_accepted'] ?? '[]', true);
            $vet['payment_methods'] = json_decode($vet['payment_methods'] ?? '[]', true);

            echo json_encode([
                'success' => true,
                'veterinarian' => $vet
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get veterinarian', 'message' => $e->getMessage()]);
        }
    }

    public function searchVeterinarians() {
        try {
            $query = $_GET['q'] ?? '';
            $specialty = $_GET['specialty'] ?? '';
            $emergency = $_GET['emergency'] ?? '';
            $telemedicine = $_GET['telemedicine'] ?? '';
            
            $where = ["v.profile_verified = TRUE"];
            $params = [];
            
            if ($query) {
                $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR v.clinic_name LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }
            
            if ($specialty) {
                $where[] = "JSON_SEARCH(v.specialties, 'one', ?) IS NOT NULL";
                $params[] = $specialty;
            }
            
            if ($emergency === 'true') {
                $where[] = "v.emergency_available = TRUE";
            }
            
            if ($telemedicine === 'true') {
                $where[] = "v.telemedicine = TRUE";
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $this->pdo->prepare("
                SELECT v.*, u.first_name, u.last_name, u.profile_image
                FROM veterinarians v
                INNER JOIN users u ON v.user_id = u.id
                WHERE $whereClause
                ORDER BY v.rating DESC
                LIMIT 50
            ");
            $stmt->execute($params);
            $vets = $stmt->fetchAll();
            
            foreach ($vets as &$vet) {
                $vet['specialties'] = json_decode($vet['specialties'] ?? '[]', true);
                $vet['services_offered'] = json_decode($vet['services_offered'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'veterinarians' => $vets
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Search failed', 'message' => $e->getMessage()]);
        }
    }

    public function getNearbyVeterinarians() {
        $lat = $_GET['lat'] ?? 0;
        $lng = $_GET['lng'] ?? 0;
        $radius = $_GET['radius'] ?? 25; // miles
        
        try {
            // This is a simplified distance calculation
            // In production, you'd use proper geospatial queries
            $stmt = $this->pdo->prepare("
                SELECT v.*, u.first_name, u.last_name, u.profile_image
                FROM veterinarians v
                INNER JOIN users u ON v.user_id = u.id
                WHERE v.profile_verified = TRUE
                ORDER BY v.rating DESC
                LIMIT 20
            ");
            $stmt->execute();
            $vets = $stmt->fetchAll();

            foreach ($vets as &$vet) {
                $vet['specialties'] = json_decode($vet['specialties'] ?? '[]', true);
                $vet['services_offered'] = json_decode($vet['services_offered'] ?? '[]', true);
            }

            echo json_encode([
                'success' => true,
                'veterinarians' => $vets
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get nearby veterinarians', 'message' => $e->getMessage()]);
        }
    }

    public function registerVeterinarian($userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['license_number', 'clinic_name', 'clinic_address', 'clinic_phone'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        try {
            // Check if user is already registered as veterinarian
            $existingStmt = $this->pdo->prepare("SELECT id FROM veterinarians WHERE user_id = ?");
            $existingStmt->execute([$userId]);
            if ($existingStmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'User is already registered as a veterinarian']);
                return;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO veterinarians (
                    user_id, license_number, clinic_name, clinic_address, clinic_phone, clinic_email,
                    clinic_website, specialties, services_offered, years_experience, education,
                    certifications, languages_spoken, consultation_fee, emergency_available,
                    home_visits, telemedicine, availability_schedule, insurance_accepted, payment_methods
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $input['license_number'],
                $input['clinic_name'],
                $input['clinic_address'],
                $input['clinic_phone'],
                $input['clinic_email'] ?? null,
                $input['clinic_website'] ?? null,
                json_encode($input['specialties'] ?? []),
                json_encode($input['services_offered'] ?? []),
                $input['years_experience'] ?? 0,
                $input['education'] ?? null,
                $input['certifications'] ?? null,
                json_encode($input['languages_spoken'] ?? []),
                $input['consultation_fee'] ?? 0.00,
                isset($input['emergency_available']) ? (bool)$input['emergency_available'] : false,
                isset($input['home_visits']) ? (bool)$input['home_visits'] : false,
                isset($input['telemedicine']) ? (bool)$input['telemedicine'] : false,
                json_encode($input['availability_schedule'] ?? []),
                json_encode($input['insurance_accepted'] ?? []),
                json_encode($input['payment_methods'] ?? [])
            ]);

            // Update user role to veterinarian
            $roleStmt = $this->pdo->prepare("UPDATE users SET role = 'veterinarian' WHERE id = ?");
            $roleStmt->execute([$userId]);

            $vetId = $this->pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Veterinarian profile created successfully',
                'veterinarian_id' => $vetId
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to register veterinarian', 'message' => $e->getMessage()]);
        }
    }

    public function updateVeterinarian($vetId, $userId) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Check if user owns this veterinarian profile
            $stmt = $this->pdo->prepare("SELECT user_id FROM veterinarians WHERE id = ?");
            $stmt->execute([$vetId]);
            $vet = $stmt->fetch();
            
            if (!$vet) {
                http_response_code(404);
                echo json_encode(['error' => 'Veterinarian not found']);
                return;
            }
            
            if ($vet['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['error' => 'You can only update your own profile']);
                return;
            }

            // Build update query dynamically
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'license_number', 'clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email',
                'clinic_website', 'specialties', 'services_offered', 'years_experience', 'education',
                'certifications', 'languages_spoken', 'consultation_fee', 'emergency_available',
                'home_visits', 'telemedicine', 'availability_schedule', 'insurance_accepted', 'payment_methods'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if (in_array($field, ['specialties', 'services_offered', 'languages_spoken', 'availability_schedule', 'insurance_accepted', 'payment_methods'])) {
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
            
            $params[] = $vetId;
            $updateStmt = $this->pdo->prepare("UPDATE veterinarians SET " . implode(', ', $fields) . " WHERE id = ?");
            $updateStmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Veterinarian profile updated successfully'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update veterinarian', 'message' => $e->getMessage()]);
        }
    }
}
?>
