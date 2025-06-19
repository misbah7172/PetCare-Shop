<?php
class Donation {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a donation
    public function createDonation($user_id, $amount, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO donations (user_id, amount, message) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $amount, $message]);
    }
    // List all donations
    public function getAllDonations() {
        $stmt = $this->pdo->query("SELECT d.*, u.username FROM donations d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // List donations by user
    public function getUserDonations($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM donations WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get a single donation
    public function getDonation($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM donations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Update donation status
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE donations SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    // Delete a donation
    public function deleteDonation($id) {
        $stmt = $this->pdo->prepare("DELETE FROM donations WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 