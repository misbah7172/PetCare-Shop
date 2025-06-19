<?php
require_once __DIR__ . '/../models/LostFound.php';

class LostFoundController {
    private $lostfound;
    public function __construct($pdo) {
        $this->lostfound = new LostFound($pdo);
    }

    // Create a lost/found report
    public function createReport($pet_id, $user_id, $type, $location, $description) {
        if (!$user_id || !$type) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->lostfound->createReport($pet_id, $user_id, $type, $location, $description);
        return ['success' => $result];
    }
    // List all reports
    public function getAllReports() {
        return $this->lostfound->getAllReports();
    }
    // List reports by user
    public function getUserReports($user_id) {
        return $this->lostfound->getUserReports($user_id);
    }
    // Get a single report
    public function getReport($id) {
        return $this->lostfound->getReport($id);
    }
    // Update report status
    public function updateStatus($id, $status) {
        if (!in_array($status, ['open', 'resolved', 'closed'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->lostfound->updateStatus($id, $status);
        return ['success' => $result];
    }
    // Delete a report
    public function deleteReport($id) {
        $result = $this->lostfound->deleteReport($id);
        return ['success' => $result];
    }
} 