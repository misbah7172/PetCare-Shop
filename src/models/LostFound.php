<?php
class LostFound {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a lost/found report
    public function createReport($pet_id, $user_id, $type, $location, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO lost_found_reports (pet_id, user_id, type, location, description) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$pet_id, $user_id, $type, $location, $description]);
    }
    // List all reports
    public function getAllReports() {
        $stmt = $this->pdo->query("SELECT lfr.*, u.username, p.name AS pet_name FROM lost_found_reports lfr LEFT JOIN users u ON lfr.user_id = u.id LEFT JOIN pets p ON lfr.pet_id = p.id ORDER BY lfr.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // List reports by user
    public function getUserReports($user_id) {
        $stmt = $this->pdo->prepare("SELECT lfr.*, p.name AS pet_name FROM lost_found_reports lfr LEFT JOIN pets p ON lfr.pet_id = p.id WHERE lfr.user_id = ? ORDER BY lfr.created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get a single report
    public function getReport($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM lost_found_reports WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Update report status
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE lost_found_reports SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    // Delete a report
    public function deleteReport($id) {
        $stmt = $this->pdo->prepare("DELETE FROM lost_found_reports WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 