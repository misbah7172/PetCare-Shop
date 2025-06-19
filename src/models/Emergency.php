<?php
class Emergency {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create an emergency request
    public function createRequest($user_id, $pet_id, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO emergency_requests (user_id, pet_id, description) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $pet_id, $description]);
    }
    // List all requests
    public function getAllRequests() {
        $stmt = $this->pdo->query("SELECT er.*, u.username, p.name AS pet_name FROM emergency_requests er LEFT JOIN users u ON er.user_id = u.id LEFT JOIN pets p ON er.pet_id = p.id ORDER BY er.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // List requests by user
    public function getUserRequests($user_id) {
        $stmt = $this->pdo->prepare("SELECT er.*, p.name AS pet_name FROM emergency_requests er LEFT JOIN pets p ON er.pet_id = p.id WHERE er.user_id = ? ORDER BY er.created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get a single request
    public function getRequest($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM emergency_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Update request status
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE emergency_requests SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    // Delete a request
    public function deleteRequest($id) {
        $stmt = $this->pdo->prepare("DELETE FROM emergency_requests WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 