<?php
require_once __DIR__ . '/../models/Reminder.php';

class ReminderController {
    private $reminder;
    public function __construct($pdo) {
        $this->reminder = new Reminder($pdo);
    }    // Create a reminder
    public function createReminder($user_id, $pet_id, $title, $description, $remind_at, $type = 'general', $frequency = 'once') {
        if (!$user_id || !$title || !$remind_at) return ['success' => false, 'error' => 'Missing required fields'];
        $result = $this->reminder->createReminder($user_id, $pet_id, $title, $description, $remind_at, $type, $frequency);
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
    public function updateReminder($id, $title, $description, $remind_at, $status = null) {
        $result = $this->reminder->updateReminder($id, $title, $description, $remind_at, $status);
        return ['success' => $result];
    }
    // Delete a reminder
    public function deleteReminder($id) {
        $result = $this->reminder->deleteReminder($id);
        return ['success' => $result];
    }
    
    // Mark reminder as complete
    public function markReminderComplete($id) {
        $result = $this->reminder->markReminderComplete($id);
        return ['success' => $result];
    }
    
    // Get upcoming reminders
    public function getUpcomingReminders($user_id, $days = 7) {
        return $this->reminder->getUpcomingReminders($user_id, $days);
    }
    
    // Generate smart reminders based on pet profile
    public function generateSmartReminders($user_id, $pet_id) {
        return $this->reminder->generateSmartReminders($user_id, $pet_id);
    }
} 