<?php
// Adoption Controller for PawConnect API
// Handles pet adoption applications and processes

class AdoptionController {
    private $database;
    private $pdo;

    public function __construct($database) {
        $this->database = $database;
        $this->pdo = $this->database->getConnection();
    }

    public function getUserApplications($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.name as pet_name, p.species, p.breed, p.images,
                       u.first_name as owner_first_name, u.last_name as owner_last_name, u.email as owner_email
                FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                INNER JOIN users u ON p.owner_id = u.id
                WHERE aa.applicant_id = ?
                ORDER BY aa.created_at DESC
            ");
            $stmt->execute([$userId]);
            $applications = $stmt->fetchAll();

            foreach ($applications as &$application) {
                $application['application_data'] = json_decode($application['application_data'], true);
                $application['pet_images'] = json_decode($application['images'] ?? '[]', true);
                unset($application['images']); // Remove to avoid confusion
            }

            echo json_encode([
                'success' => true,
                'applications' => $applications
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get applications', 'message' => $e->getMessage()]);
        }
    }

    public function getApplication($applicationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.name as pet_name, p.species, p.breed, p.images, p.description,
                       u.first_name as owner_first_name, u.last_name as owner_last_name, u.email as owner_email, u.phone as owner_phone
                FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                INNER JOIN users u ON p.owner_id = u.id
                WHERE aa.id = ? AND (aa.applicant_id = ? OR p.owner_id = ?)
            ");
            $stmt->execute([$applicationId, $userId, $userId]);
            $application = $stmt->fetch();

            if (!$application) {
                http_response_code(404);
                echo json_encode(['error' => 'Application not found']);
                return;
            }

            $application['application_data'] = json_decode($application['application_data'], true);
            $application['pet_images'] = json_decode($application['images'] ?? '[]', true);
            unset($application['images']);

            echo json_encode([
                'success' => true,
                'application' => $application
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get application', 'message' => $e->getMessage()]);
        }
    }

    public function getAllApplications() {
        try {
            $status = $_GET['status'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $offset = ($page - 1) * $limit;

            $where = [];
            $params = [];

            if ($status) {
                $where[] = "aa.status = ?";
                $params[] = $status;
            }

            $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.name as pet_name, p.species, p.breed,
                       applicant.first_name as applicant_first_name, applicant.last_name as applicant_last_name, applicant.email as applicant_email,
                       owner.first_name as owner_first_name, owner.last_name as owner_last_name
                FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                INNER JOIN users applicant ON aa.applicant_id = applicant.id
                INNER JOIN users owner ON p.owner_id = owner.id
                $whereClause
                ORDER BY aa.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $applications = $stmt->fetchAll();

            // Get total count
            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                $whereClause
            ");
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetchColumn();

            foreach ($applications as &$application) {
                $application['application_data'] = json_decode($application['application_data'], true);
            }

            echo json_encode([
                'success' => true,
                'applications' => $applications,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$limit,
                    'total' => (int)$total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get applications', 'message' => $e->getMessage()]);
        }
    }

    public function submitApplication($userId) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['pet_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Pet ID is required']);
            return;
        }

        try {
            // Check if pet exists and is available
            $petStmt = $this->pdo->prepare("SELECT id, owner_id, adoption_status FROM pets WHERE id = ?");
            $petStmt->execute([$input['pet_id']]);
            $pet = $petStmt->fetch();

            if (!$pet) {
                http_response_code(404);
                echo json_encode(['error' => 'Pet not found']);
                return;
            }

            if ($pet['adoption_status'] !== 'available') {
                http_response_code(400);
                echo json_encode(['error' => 'Pet is not available for adoption']);
                return;
            }

            if ($pet['owner_id'] == $userId) {
                http_response_code(400);
                echo json_encode(['error' => 'You cannot adopt your own pet']);
                return;
            }

            // Check if user already has a pending application for this pet
            $existingStmt = $this->pdo->prepare("
                SELECT id FROM adoption_applications 
                WHERE pet_id = ? AND applicant_id = ? AND status IN ('pending', 'approved')
            ");
            $existingStmt->execute([$input['pet_id'], $userId]);
            if ($existingStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'You already have a pending application for this pet']);
                return;
            }

            // Prepare application data
            $applicationData = [
                'personal_info' => [
                    'age' => $input['age'] ?? null,
                    'occupation' => $input['occupation'] ?? null,
                    'income_range' => $input['income_range'] ?? null
                ],
                'housing' => [
                    'type' => $input['housing_type'] ?? null,
                    'owned_or_rented' => $input['owned_or_rented'] ?? null,
                    'yard' => $input['has_yard'] ?? false,
                    'landlord_permission' => $input['landlord_permission'] ?? null
                ],
                'experience' => [
                    'previous_pets' => $input['previous_pets'] ?? false,
                    'current_pets' => $input['current_pets'] ?? [],
                    'veterinarian' => $input['veterinarian_info'] ?? null
                ],
                'lifestyle' => [
                    'hours_alone' => $input['hours_alone'] ?? null,
                    'exercise_plan' => $input['exercise_plan'] ?? null,
                    'travel_frequency' => $input['travel_frequency'] ?? null
                ],
                'additional_info' => [
                    'why_adopt' => $input['why_adopt'] ?? null,
                    'expectations' => $input['expectations'] ?? null,
                    'references' => $input['references'] ?? []
                ]
            ];

            $stmt = $this->pdo->prepare("
                INSERT INTO adoption_applications (pet_id, applicant_id, application_data, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $input['pet_id'],
                $userId,
                json_encode($applicationData)
            ]);

            $applicationId = $this->pdo->lastInsertId();            // Send notification to pet owner
            $this->sendNotification($pet['owner_id'], [
                'type' => 'new_adoption_application',
                'title' => 'New adoption application received',
                'message' => "Someone has applied to adopt your pet. Review the application to proceed.",
                'related_type' => 'adoption_application',
                'related_id' => $applicationId,
                'sender_id' => $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $applicationId
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit application', 'message' => $e->getMessage()]);
        }
    }

    public function updateApplication($applicationId, $userId) {
        $input = json_decode(file_get_contents('php://input'), true);

        try {
            // Check if user owns the application
            $stmt = $this->pdo->prepare("SELECT applicant_id, status FROM adoption_applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();

            if (!$application) {
                http_response_code(404);
                echo json_encode(['error' => 'Application not found']);
                return;
            }

            if ($application['applicant_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['error' => 'You can only update your own applications']);
                return;
            }

            if ($application['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot update application that is not pending']);
                return;
            }

            // Update application data
            if (isset($input['application_data'])) {
                $updateStmt = $this->pdo->prepare("UPDATE adoption_applications SET application_data = ? WHERE id = ?");
                $updateStmt->execute([json_encode($input['application_data']), $applicationId]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Application updated successfully'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update application', 'message' => $e->getMessage()]);
        }
    }

    public function approveApplication($applicationId) {
        try {
            $this->pdo->beginTransaction();

            // Get application details
            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.owner_id, p.name as pet_name
                FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                WHERE aa.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();

            if (!$application) {
                http_response_code(404);
                echo json_encode(['error' => 'Application not found']);
                return;
            }

            if ($application['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['error' => 'Application is not pending']);
                return;
            }

            // Approve the application
            $approveStmt = $this->pdo->prepare("
                UPDATE adoption_applications 
                SET status = 'approved', approval_date = NOW() 
                WHERE id = ?
            ");
            $approveStmt->execute([$applicationId]);

            // Update pet status to pending
            $petStmt = $this->pdo->prepare("UPDATE pets SET adoption_status = 'pending' WHERE id = ?");
            $petStmt->execute([$application['pet_id']]);

            // Reject other pending applications for this pet
            $rejectStmt = $this->pdo->prepare("
                UPDATE adoption_applications 
                SET status = 'rejected', rejection_reason = 'Pet was adopted by another applicant' 
                WHERE pet_id = ? AND id != ? AND status = 'pending'
            ");
            $rejectStmt->execute([$application['pet_id'], $applicationId]);            // Send notification to applicant
            $this->sendNotification($application['applicant_id'], [
                'type' => 'application_approved',
                'title' => 'Adoption application approved!',
                'message' => "Great news! Your application to adopt {$application['pet_name']} has been approved.",
                'related_type' => 'adoption_application',
                'related_id' => $applicationId,
                'sender_id' => $userId
            ]);

            $this->pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Application approved successfully'
            ]);

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to approve application', 'message' => $e->getMessage()]);
        }
    }    public function rejectApplication($applicationId) {
        require_once '../middleware/AuthMiddleware.php';
        $authMiddleware = new AuthMiddleware($this->database);
        $user = $authMiddleware->authenticate();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        try {
            // Get application details
            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.name as pet_name
                FROM adoption_applications aa
                INNER JOIN pets p ON aa.pet_id = p.id
                WHERE aa.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();

            if (!$application) {
                http_response_code(404);
                echo json_encode(['error' => 'Application not found']);
                return;
            }

            if ($application['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['error' => 'Application is not pending']);
                return;
            }

            // Reject the application
            $rejectStmt = $this->pdo->prepare("
                UPDATE adoption_applications 
                SET status = 'rejected', rejection_reason = ? 
                WHERE id = ?
            ");
            $rejectStmt->execute([
                $input['reason'] ?? 'Application did not meet requirements',
                $applicationId
            ]);            // Send notification to applicant
            $this->sendNotification($application['applicant_id'], [
                'type' => 'application_rejected',
                'title' => 'Adoption application update',
                'message' => "Thank you for your interest in adopting {$application['pet_name']}. Unfortunately, we've decided to proceed with another applicant.",
                'related_type' => 'adoption_application',
                'related_id' => $applicationId,
                'sender_id' => $user['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Application rejected'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reject application', 'message' => $e->getMessage()]);
        }
    }

    // Start adoption process with chat
    public function startAdoptionChat($data) {
        require_once '../middleware/AuthMiddleware.php';
        $authMiddleware = new AuthMiddleware($this->database);
        $user = $authMiddleware->authenticate();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $pet_id = $data['pet_id'] ?? null;
        if (!$pet_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Pet ID is required']);
            return;
        }

        try {
            // Get pet and owner information
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.id as owner_id, u.first_name, u.last_name, u.email
                FROM pets p
                JOIN users u ON p.owner_id = u.id
                WHERE p.id = ? AND p.adoption_status = 'available'
            ");
            $stmt->execute([$pet_id]);
            $pet = $stmt->fetch();

            if (!$pet) {
                http_response_code(404);
                echo json_encode(['error' => 'Pet not found or not available for adoption']);
                return;
            }

            if ($pet['owner_id'] == $user['id']) {
                http_response_code(400);
                echo json_encode(['error' => 'You cannot adopt your own pet']);
                return;
            }

            // Create adoption application
            $applicationData = [
                'interested_reason' => $data['message'] ?? 'Interested in adopting this pet',
                'experience' => $data['experience'] ?? '',
                'housing_situation' => $data['housing_situation'] ?? '',
                'other_pets' => $data['other_pets'] ?? false
            ];

            $stmt = $this->pdo->prepare("
                INSERT INTO adoption_applications (pet_id, applicant_id, application_data, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$pet_id, $user['id'], json_encode($applicationData)]);
            $applicationId = $this->pdo->lastInsertId();

            // Start chat conversation
            require_once 'ChatController.php';
            $chatController = new ChatController($this->database);
            $chatResult = $chatController->startConversation([
                'type' => 'adoption',
                'related_id' => $pet_id,
                'other_user_id' => $pet['owner_id']
            ]);

            if ($chatResult['success']) {
                // Update application with conversation ID
                $stmt = $this->pdo->prepare("
                    UPDATE adoption_applications 
                    SET conversation_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$chatResult['conversation_id'], $applicationId]);

                // Send initial message
                $chatController->sendMessage([
                    'conversation_id' => $chatResult['conversation_id'],
                    'message' => $data['message'] ?? "Hi! I'm interested in adopting {$pet['name']}. Could we discuss the adoption process?"
                ]);

                // Send notification to pet owner
                $this->sendNotification($pet['owner_id'], [
                    'type' => 'adoption_interest',
                    'title' => 'Adoption Interest',
                    'message' => "Someone is interested in adopting {$pet['name']}",
                    'related_type' => 'adoption_application',
                    'related_id' => $applicationId,
                    'sender_id' => $user['id']
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Adoption interest sent successfully',
                'application_id' => $applicationId,
                'conversation_id' => $chatResult['conversation_id'] ?? null
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to start adoption process', 'message' => $e->getMessage()]);
        }
    }

    // Accept or reject adoption application
    public function updateApplicationStatus($applicationId, $data) {
        require_once '../middleware/AuthMiddleware.php';
        $authMiddleware = new AuthMiddleware($this->database);
        $user = $authMiddleware->authenticate();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        $status = $data['status'] ?? '';
        $notes = $data['notes'] ?? '';

        if (!in_array($status, ['approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status. Must be approved or rejected']);
            return;
        }

        try {
            // Get application and verify ownership
            $stmt = $this->pdo->prepare("
                SELECT aa.*, p.name as pet_name, p.owner_id,
                       u.first_name, u.last_name, u.email
                FROM adoption_applications aa
                JOIN pets p ON aa.pet_id = p.id
                JOIN users u ON aa.applicant_id = u.id
                WHERE aa.id = ? AND p.owner_id = ?
            ");
            $stmt->execute([$applicationId, $user['id']]);
            $application = $stmt->fetch();

            if (!$application) {
                http_response_code(404);
                echo json_encode(['error' => 'Application not found or unauthorized']);
                return;
            }

            // Update application status
            $stmt = $this->pdo->prepare("
                UPDATE adoption_applications 
                SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $applicationId]);

            // Send notification to applicant
            $this->sendNotification($application['applicant_id'], [
                'type' => 'adoption_' . $status,
                'title' => 'Adoption Application ' . ucfirst($status),
                'message' => "Your adoption application for {$application['pet_name']} has been {$status}",
                'related_type' => 'adoption_application',
                'related_id' => $applicationId,
                'sender_id' => $user['id']
            ]);

            // If approved, update pet status
            if ($status === 'approved') {
                $stmt = $this->pdo->prepare("
                    UPDATE pets 
                    SET adoption_status = 'pending' 
                    WHERE id = ?
                ");
                $stmt->execute([$application['pet_id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => "Application {$status} successfully"
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update application', 'message' => $e->getMessage()]);
        }
    }

    private function sendNotification($user_id, $data) {
        try {
            $stmt = $this->pdo->prepare("
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
