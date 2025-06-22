<?php
require_once 'config/database.php';

echo "<h2>🧹 PawConnect Database Cleanup</h2>\n";
echo "<p>Removing unnecessary tables and simplifying the database structure...</p>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // List of tables to KEEP (essential only)
    $essential_tables = [
        'users',
        'pets',
        'products',
        'notifications',
        'chat_conversations', 
        'chat_messages',
        'adoption_applications',
        'community_posts',
        'community_comments',
        'community_likes',
        'community_reactions',
        'product_categories',
        'vet_appointments',
        'veterinarians'
    ];
    
    // Get all existing tables
    $result = $db->query("SHOW TABLES");
    $all_tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    // Tables to drop
    $tables_to_drop = array_diff($all_tables, $essential_tables);
    
    echo "<h3>Tables to Remove:</h3>\n";
    foreach ($tables_to_drop as $table) {
        echo "- $table\n";
    }
    
    echo "\n<h3>Tables to Keep:</h3>\n";
    foreach ($essential_tables as $table) {
        if (in_array($table, $all_tables)) {
            echo "✅ $table\n";
        } else {
            echo "❌ $table (missing)\n";
        }
    }
    
    echo "\n<h3>🗑️ Dropping Unnecessary Tables:</h3>\n";
    
    // Disable foreign key checks temporarily
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $dropped_count = 0;
    foreach ($tables_to_drop as $table) {
        try {
            $db->exec("DROP TABLE IF EXISTS `$table`");
            echo "✅ Dropped: $table\n";
            $dropped_count++;
        } catch (Exception $e) {
            echo "❌ Error dropping $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n<h3>🧹 Cleaning Up Unnecessary Columns:</h3>\n";
    
    // Remove unnecessary columns from remaining tables
    $cleanup_columns = [
        'users' => [
            'profile_image', 'bio', 'date_of_birth', 'is_premium', 'premium_expires_at',
            'email_verified', 'phone_verified', 'two_factor_enabled', 'last_login'
        ],
        'pets' => [
            'owner_id', 'vaccination_status', 'microchip_id', 'social_media_profiles',
            'is_featured', 'views', 'favorites_count', 'videos', 'energy_level',
            'training_level', 'house_trained'
        ],
        'products' => [
            'slug', 'short_description', 'sku', 'sale_price', 'cost_price',
            'manage_stock', 'stock_status', 'weight', 'dimensions', 'gallery',
            'brand', 'model', 'tags', 'attributes', 'prescription_required',
            'age_restriction', 'pet_species', 'rating', 'review_count',
            'sales_count', 'featured', 'seo_title', 'seo_description'
        ],
        'community_posts' => [
            'pet_id', 'post_type', 'videos', 'tags', 'location', 'privacy',
            'shares_count', 'views_count', 'is_pinned', 'is_featured',
            'moderation_status'
        ]
    ];
    
    foreach ($cleanup_columns as $table => $columns) {
        echo "\nCleaning up table: $table\n";
        foreach ($columns as $column) {
            try {
                // Check if column exists first
                $check = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                if ($check->rowCount() > 0) {
                    $db->exec("ALTER TABLE `$table` DROP COLUMN `$column`");
                    echo "  ✅ Removed column: $column\n";
                }
            } catch (Exception $e) {
                echo "  ⚠️ Warning removing $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n<h3>✅ Database Cleanup Complete!</h3>\n";
    echo "<p>Removed $dropped_count unnecessary tables and cleaned up columns.</p>\n";
    echo "<p>The database now contains only essential tables for core functionality.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
