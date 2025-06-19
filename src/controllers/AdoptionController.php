<?php
require_once __DIR__ . '/../models/Adoption.php';

class AdoptionController {
    private $adoption;
    public function __construct($pdo) {
        $this->adoption = new Adoption($pdo);
    }

    // Create a new adoption listing
    public function create($pet_id, $listed_by, $description) {
        if (!$pet_id || !$listed_by) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->adoption->createAdoption($pet_id, $listed_by, $description);
        return ['success' => $result];
    }

    // Get all adoption listings
    public function list() {
        return $this->adoption->getAllAdoptions();
    }

    // Get a single adoption listing
    public function get($id) {
        return $this->adoption->getAdoption($id);
    }

    // Apply for adoption
    public function apply($adoption_id, $applicant_id, $message) {
        if (!$adoption_id || !$applicant_id) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->adoption->applyForAdoption($adoption_id, $applicant_id, $message);
        return ['success' => $result];
    }

    // Get applications for an adoption
    public function applications($adoption_id) {
        return $this->adoption->getApplications($adoption_id);
    }

    // Approve or reject an application
    public function updateApplication($application_id, $status) {
        if (!in_array($status, ['approved', 'rejected', 'pending', 'cancelled'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        $result = $this->adoption->updateApplicationStatus($application_id, $status);
        return ['success' => $result];
    }

    // Delete an adoption listing
    public function delete($id) {
        $result = $this->adoption->deleteAdoption($id);
        return ['success' => $result];
    }
} 