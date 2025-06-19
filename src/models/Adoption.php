<?php
class Adoption {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a new adoption listing
    public function createAdoption($pet_id, $listed_by, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO adoptions (pet_id, listed_by, description) VALUES (?, ?, ?)");
        return $stmt->execute([$pet_id, $listed_by, $description]);
    }

    // Get all adoption listings
    public function getAllAdoptions() {
        $stmt = $this->pdo->query("SELECT a.*, p.name AS pet_name, u.username AS listed_by_name FROM adoptions a JOIN pets p ON a.pet_id = p.id JOIN users u ON a.listed_by = u.id ORDER BY a.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single adoption listing
    public function getAdoption($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM adoptions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Apply for adoption
    public function applyForAdoption($adoption_id, $applicant_id, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO adoption_applications (adoption_id, applicant_id, message) VALUES (?, ?, ?)");
        return $stmt->execute([$adoption_id, $applicant_id, $message]);
    }

    // Get applications for an adoption
    public function getApplications($adoption_id) {
        $stmt = $this->pdo->prepare("SELECT aa.*, u.username AS applicant_name FROM adoption_applications aa JOIN users u ON aa.applicant_id = u.id WHERE aa.adoption_id = ?");
        $stmt->execute([$adoption_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Approve or reject an application
    public function updateApplicationStatus($application_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE adoption_applications SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $application_id]);
    }

    // Delete an adoption listing
    public function deleteAdoption($id) {
        $stmt = $this->pdo->prepare("DELETE FROM adoptions WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 