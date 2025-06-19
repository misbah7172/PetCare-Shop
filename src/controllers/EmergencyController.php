<?php
require_once __DIR__ . '/../models/Emergency.php';

class EmergencyController {
    private $emergency;
    public function __construct($pdo) {
        $this->emergency = new Emergency($pdo);
    }

    // Create an emergency request
    public function createRequest($user_id, $pet_id, $description) {
        if (!$user_id || !$description) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->emergency->createRequest($user_id, $pet_id, $description);
        return ['success' => $result];
    }
    // List all requests
    public function getAllRequests() {
        return $this->emergency->getAllRequests();
    }
    // List requests by user
    public function getUserRequests($user_id) {
        return $this->emergency->getUserRequests($user_id);
    }
    // Get a single request
    public function getRequest($id) {
        return $this->emergency->getRequest($id);
    }
    // Update request status
    public function updateStatus($id, $status) {
        if (!in_array($status, ['pending', 'in_progress', 'resolved', 'cancelled'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->emergency->updateStatus($id, $status);
        return ['success' => $result];
    }
    // Delete a request
    public function deleteRequest($id) {
        $result = $this->emergency->deleteRequest($id);
        return ['success' => $result];
    }
} 