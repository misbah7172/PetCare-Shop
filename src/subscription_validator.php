<?php
// Subscription Validation System
// This script should be run daily via cron job to check subscription status

header('Content-Type: application/json');

// Database configuration
$config = [
    'host' => 'localhost',
    'dbname' => 'pawconnect_db',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

class SubscriptionValidator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Check and update expired subscriptions
    public function validateSubscriptions() {
        try {
            // Get all active subscriptions that have expired
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id,
                    s.user_id,
                    s.plan_type,
                    s.start_date,
                    s.end_date,
                    s.status,
                    u.email,
                    u.username
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                WHERE s.status = 'active' 
                AND s.end_date < NOW()
            ");
            
            $stmt->execute();
            $expiredSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updatedCount = 0;
            $notifications = [];
            
            foreach ($expiredSubscriptions as $subscription) {
                // Update subscription status to expired
                $this->updateSubscriptionStatus($subscription['id'], 'expired');
                
                // Update user badge to normal
                $this->updateUserBadge($subscription['user_id'], 'normal');
                
                // Send notification to user
                $this->sendExpirationNotification($subscription);
                
                $updatedCount++;
                $notifications[] = "User {$subscription['username']} subscription expired";
            }
            
            // Log the validation
            $this->logValidation($updatedCount, $notifications);
            
            return [
                'success' => true,
                'message' => "Validated {$updatedCount} subscriptions",
                'updated_count' => $updatedCount,
                'notifications' => $notifications
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Validation failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Update subscription status
    private function updateSubscriptionStatus($subscriptionId, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE subscriptions 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $subscriptionId]);
    }
    
    // Update user badge
    private function updateUserBadge($userId, $badge) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET subscription_badge = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$badge, $userId]);
    }
    
    // Send expiration notification
    private function sendExpirationNotification($subscription) {
        // Create notification record
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, created_at)
            VALUES (?, 'subscription_expired', 'Subscription Expired', ?, NOW())
        ");
        
        $message = "Your {$subscription['plan_type']} subscription has expired. Renew now to continue enjoying premium features!";
        $stmt->execute([$subscription['user_id'], $message]);
        
        // Send email notification (if email service is configured)
        $this->sendEmailNotification($subscription['email'], $subscription['plan_type']);
    }
    
    // Send email notification
    private function sendEmailNotification($email, $planType) {
        $subject = "Your PawConnect Subscription Has Expired";
        $message = "
        <html>
        <body>
            <h2>Subscription Expired</h2>
            <p>Dear Valued Customer,</p>
            <p>Your {$planType} subscription has expired. To continue enjoying premium features, please renew your subscription.</p>
            <p><strong>Benefits you'll regain:</strong></p>
            <ul>
                <li>Priority vet appointments</li>
                <li>Exclusive pet care guides</li>
                <li>Product discounts</li>
                <li>Premium customer support</li>
            </ul>
            <p><a href='http://localhost/pawconnect/subscription_plans.html'>Renew Now</a></p>
            <p>Thank you for choosing PawConnect!</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PawConnect <noreply@pawconnect.com>" . "\r\n";
        
        // Uncomment to send actual emails
        // mail($email, $subject, $message, $headers);
    }
    
    // Log validation results
    private function logValidation($count, $notifications) {
        $stmt = $this->pdo->prepare("
            INSERT INTO system_logs (action, details, created_at)
            VALUES ('subscription_validation', ?, NOW())
        ");
        
        $details = json_encode([
            'updated_count' => $count,
            'notifications' => $notifications,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $stmt->execute([$details]);
    }
    
    // Get subscription statistics
    public function getSubscriptionStats() {
        $stats = [];
        
        // Total active subscriptions
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'
        ");
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Expired subscriptions
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM subscriptions WHERE status = 'expired'
        ");
        $stats['expired'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Expiring soon (within 7 days)
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count 
            FROM subscriptions 
            WHERE status = 'active' 
            AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ");
        $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Subscription by plan type
        $stmt = $this->pdo->query("
            SELECT plan_type, COUNT(*) as count 
            FROM subscriptions 
            WHERE status = 'active' 
            GROUP BY plan_type
        ");
        $stats['by_plan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // Get users with expiring subscriptions
    public function getExpiringSubscriptions($days = 7) {
        $stmt = $this->pdo->prepare("
            SELECT 
                s.id,
                s.user_id,
                s.plan_type,
                s.end_date,
                u.email,
                u.username
            FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'active' 
            AND s.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
            ORDER BY s.end_date ASC
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Send renewal reminders
    public function sendRenewalReminders($days = 3) {
        $expiringSubscriptions = $this->getExpiringSubscriptions($days);
        $sentCount = 0;
        
        foreach ($expiringSubscriptions as $subscription) {
            $this->sendRenewalReminder($subscription);
            $sentCount++;
        }
        
        return $sentCount;
    }
    
    // Send renewal reminder
    private function sendRenewalReminder($subscription) {
        // Create notification
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, created_at)
            VALUES (?, 'subscription_expiring', 'Subscription Expiring Soon', ?, NOW())
        ");
        
        $message = "Your {$subscription['plan_type']} subscription expires on " . date('M d, Y', strtotime($subscription['end_date'])) . ". Renew now to avoid interruption!";
        $stmt->execute([$subscription['user_id'], $message]);
        
        // Send email reminder
        $this->sendRenewalEmail($subscription);
    }
    
    // Send renewal email
    private function sendRenewalEmail($subscription) {
        $subject = "Your PawConnect Subscription Expires Soon";
        $message = "
        <html>
        <body>
            <h2>Subscription Expiring Soon</h2>
            <p>Dear {$subscription['username']},</p>
            <p>Your {$subscription['plan_type']} subscription will expire on " . date('M d, Y', strtotime($subscription['end_date'])) . ".</p>
            <p>Don't lose access to your premium features! Renew now to continue enjoying:</p>
            <ul>
                <li>Priority vet appointments</li>
                <li>Exclusive pet care guides</li>
                <li>Product discounts</li>
                <li>Premium customer support</li>
            </ul>
            <p><a href='http://localhost/pawconnect/subscription_plans.html'>Renew Now</a></p>
            <p>Thank you for choosing PawConnect!</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PawConnect <noreply@pawconnect.com>" . "\r\n";
        
        // Uncomment to send actual emails
        // mail($subscription['email'], $subject, $message, $headers);
    }
}

// Initialize validator
$validator = new SubscriptionValidator($pdo);

// Handle different actions
$action = $_GET['action'] ?? 'validate';

switch ($action) {
    case 'validate':
        $result = $validator->validateSubscriptions();
        break;
        
    case 'stats':
        $result = $validator->getSubscriptionStats();
        break;
        
    case 'expiring':
        $days = $_GET['days'] ?? 7;
        $result = $validator->getExpiringSubscriptions($days);
        break;
        
    case 'reminders':
        $days = $_GET['days'] ?? 3;
        $sentCount = $validator->sendRenewalReminders($days);
        $result = [
            'success' => true,
            'message' => "Sent {$sentCount} renewal reminders",
            'sent_count' => $sentCount
        ];
        break;
        
    default:
        $result = ['error' => 'Invalid action'];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?> 