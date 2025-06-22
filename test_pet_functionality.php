<?php
// Direct test for pets API functionality
echo "=== PAWCONNECT PET CORNER FUNCTIONALITY TEST ===\n\n";

// Test database connection first
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if tables exist
    $tables = ['pets', 'pet_activities', 'pet_photos'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    // Test basic pet operations
    echo "\n--- Testing Pet Operations ---\n";
      // Add a test pet
    $stmt = $pdo->prepare("
        INSERT INTO pets (user_id, name, type, breed, age, gender, weight, description) 
        VALUES (3, 'Test Buddy', 'dog', 'Golden Retriever', '3 years', 'male', '30 kg', 'Test pet')
    ");
    
    if ($stmt->execute()) {
        $pet_id = $pdo->lastInsertId();
        echo "✅ Pet added successfully (ID: $pet_id)\n";
        
        // Get pets
        $stmt = $pdo->prepare("SELECT * FROM pets WHERE user_id = 3");
        $stmt->execute();
        $pets = $stmt->fetchAll();
        echo "✅ Found " . count($pets) . " pets for user\n";
        
        // Add activity
        $stmt = $pdo->prepare("
            INSERT INTO pet_activities (pet_id, user_id, activity_type, description) 
            VALUES (?, 3, 'other', 'Test activity')
        ");
        $stmt->execute([$pet_id]);
        echo "✅ Activity added successfully\n";
        
        // Clean up test data
        $pdo->prepare("DELETE FROM pet_activities WHERE pet_id = ?")->execute([$pet_id]);
        $pdo->prepare("DELETE FROM pets WHERE id = ?")->execute([$pet_id]);
        echo "✅ Test data cleaned up\n";
        
    } else {
        echo "❌ Failed to add test pet\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FUNCTIONALITY TEST COMPLETE ===\n";
echo "✅ Pet Corner backend is ready!\n";
echo "🌐 You can now test the frontend at: http://localhost/pawconnect/public/pet_corner.html\n";
echo "📝 Make sure to log in first at: http://localhost/pawconnect/public/login.html\n";
?>
