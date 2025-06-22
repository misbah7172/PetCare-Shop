<?php
// Clean Database - Remove all test/mock data
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>🧹 Cleaning Database - Removing Test/Mock Data</h2>";
    
    // Disable foreign key checks to avoid constraint issues
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // List of tables to clean (remove all data)
    $tables = [
        'users', 'pets', 'veterinarians', 'products', 'orders', 'order_items',
        'community_posts', 'community_comments', 'community_likes',
        'support_tickets', 'support_ticket_messages', 'lost_found_pets',
        'subscription_plans', 'user_subscriptions', 'smart_reminders',
        'emergency_services', 'donations', 'donation_campaigns',
        'adoption_applications', 'pet_favorites', 'notifications',
        'shopping_cart', 'product_categories', 'vet_appointments'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DELETE FROM `$table`");
            echo "✅ Cleaned table: $table<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not clean $table: " . $e->getMessage() . "<br>";
        }
    }
    
    // Reset auto-increment counters
    foreach ($tables as $table) {
        try {
            $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
        } catch (Exception $e) {
            // Ignore errors for tables without auto-increment
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h3>✅ Database cleaned successfully! All test/mock data removed.</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error cleaning database: " . $e->getMessage() . "</h3>";
}
?>
