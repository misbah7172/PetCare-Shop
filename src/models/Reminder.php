<?php
class Reminder {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Create a reminder
    public function createReminder($user_id, $pet_id, $title, $description, $remind_at) {
        $stmt = $this->pdo->prepare("INSERT INTO reminders (user_id, pet_id, title, description, remind_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $pet_id, $title, $description, $remind_at]);
    }
    // List reminders for a user
    public function getUserReminders($user_id) {
        $stmt = $this->pdo->prepare("SELECT r.*, p.name AS pet_name FROM reminders r LEFT JOIN pets p ON r.pet_id = p.id WHERE r.user_id = ? ORDER BY r.remind_at ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get a single reminder
    public function getReminder($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reminders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Update a reminder
    public function updateReminder($id, $title, $description, $remind_at) {
        $stmt = $this->pdo->prepare("UPDATE reminders SET title=?, description=?, remind_at=? WHERE id=?");
        return $stmt->execute([$title, $description, $remind_at, $id]);
    }
    // Delete a reminder
    public function deleteReminder($id) {
        $stmt = $this->pdo->prepare("DELETE FROM reminders WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 