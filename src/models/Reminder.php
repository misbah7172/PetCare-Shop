<?php
class Reminder {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }    // Create a reminder
    public function createReminder($user_id, $pet_id, $title, $description, $remind_at, $type = 'general', $frequency = 'once') {
        $stmt = $this->pdo->prepare("INSERT INTO reminders (user_id, pet_id, title, description, remind_at, type, frequency) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $pet_id, $title, $description, $remind_at, $type, $frequency]);
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
    public function updateReminder($id, $title, $description, $remind_at, $status = null) {
        if ($status) {
            $stmt = $this->pdo->prepare("UPDATE reminders SET title=?, description=?, remind_at=?, status=? WHERE id=?");
            return $stmt->execute([$title, $description, $remind_at, $status, $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE reminders SET title=?, description=?, remind_at=? WHERE id=?");
            return $stmt->execute([$title, $description, $remind_at, $id]);
        }
    }
    // Delete a reminder
    public function deleteReminder($id) {
        $stmt = $this->pdo->prepare("DELETE FROM reminders WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Mark reminder as complete
    public function markReminderComplete($id) {
        $stmt = $this->pdo->prepare("UPDATE reminders SET status = 'completed', completed_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Get upcoming reminders
    public function getUpcomingReminders($user_id, $days = 7) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, p.name AS pet_name 
            FROM reminders r 
            LEFT JOIN pets p ON r.pet_id = p.id 
            WHERE r.user_id = ? 
            AND r.remind_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
            AND r.status != 'completed'
            ORDER BY r.remind_at ASC
        ");
        $stmt->execute([$user_id, $days]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
    
    // Generate smart reminders based on pet profile
    public function generateSmartReminders($user_id, $pet_id) {
        // Get pet information
        $stmt = $this->pdo->prepare("SELECT * FROM pets WHERE id = ? AND user_id = ?");
        $stmt->execute([$pet_id, $user_id]);
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pet) {
            return ['success' => false, 'error' => 'Pet not found'];
        }
        
        $suggestions = [];
        $age_in_years = $pet['age'] ?? 1;
        $pet_type = strtolower($pet['type'] ?? 'dog');
        
        // Health reminders based on age and type
        if ($age_in_years < 1) {
            $suggestions[] = [
                'title' => 'Puppy/Kitten Vaccination Series',
                'description' => 'Complete vaccination series is crucial for young pets',
                'type' => 'health',
                'frequency' => 'monthly',
                'priority' => 'high'
            ];
        } else {
            $suggestions[] = [
                'title' => 'Annual Vaccination Check',
                'description' => 'Keep your pet\'s vaccinations up to date',
                'type' => 'health',
                'frequency' => 'yearly',
                'priority' => 'high'
            ];
        }
        
        // Senior pet care
        if ($age_in_years >= 7) {
            $suggestions[] = [
                'title' => 'Senior Pet Health Checkup',
                'description' => 'Bi-annual checkups recommended for senior pets',
                'type' => 'health',
                'frequency' => 'bi-yearly',
                'priority' => 'high'
            ];
        }
        
        // Type-specific reminders
        if ($pet_type === 'dog') {
            $suggestions[] = [
                'title' => 'Heartworm Prevention',
                'description' => 'Monthly heartworm prevention medication',
                'type' => 'health',
                'frequency' => 'monthly',
                'priority' => 'high'
            ];
            $suggestions[] = [
                'title' => 'Daily Exercise',
                'description' => 'Regular exercise to maintain health and happiness',
                'type' => 'exercise',
                'frequency' => 'daily',
                'priority' => 'medium'
            ];
        } elseif ($pet_type === 'cat') {
            $suggestions[] = [
                'title' => 'Litter Box Cleaning',
                'description' => 'Keep litter box clean for health and hygiene',
                'type' => 'care',
                'frequency' => 'daily',
                'priority' => 'medium'
            ];
        }
        
        // Universal care reminders
        $suggestions[] = [
            'title' => 'Grooming Session',
            'description' => 'Regular grooming for coat and nail health',
            'type' => 'grooming',
            'frequency' => 'monthly',
            'priority' => 'medium'
        ];
        
        $suggestions[] = [
            'title' => 'Dental Care',
            'description' => 'Brush teeth or provide dental treats',
            'type' => 'health',
            'frequency' => 'weekly',
            'priority' => 'medium'
        ];
        
        return [
            'success' => true, 
            'data' => [
                'pet' => $pet,
                'suggestions' => $suggestions,
                'message' => count($suggestions) . ' smart reminders generated for ' . $pet['name']
            ]
        ];
    }
} 