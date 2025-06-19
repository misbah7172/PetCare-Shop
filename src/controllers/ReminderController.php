<?php
require_once __DIR__ . '/../models/Reminder.php';

class ReminderController {
    private $reminder;
    public function __construct($pdo) {
        $this->reminder = new Reminder($pdo);
    }

    // Create a reminder
    public function createReminder($user_id, $pet_id, $title, $description, $remind_at) {
        if (!$user_id || !$title || !$remind_at) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->reminder->createReminder($user_id, $pet_id, $title, $description, $remind_at);
        return ['success' => $result];
    }
    // List reminders for a user
    public function getUserReminders($user_id) {
        return $this->reminder->getUserReminders($user_id);
    }
    // Get a single reminder
    public function getReminder($id) {
        return $this->reminder->getReminder($id);
    }
    // Update a reminder
    public function updateReminder($id, $title, $description, $remind_at) {
        $result = $this->reminder->updateReminder($id, $title, $description, $remind_at);
        return ['success' => $result];
    }
    // Delete a reminder
    public function deleteReminder($id) {
        $result = $this->reminder->deleteReminder($id);
        return ['success' => $result];
    }
} 