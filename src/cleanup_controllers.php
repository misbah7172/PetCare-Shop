<?php
echo "<h2>🧹 API Controllers Cleanup</h2>\n";
echo "<p>Removing unnecessary API controllers...</p>\n";

// Essential API controllers to KEEP
$essential_controllers = [
    'AuthController.php',
    'PetController.php',
    'ProductController.php',
    'AdoptionController.php',
    'CommunityController.php',
    'ChatController.php',
    'NotificationController.php',
    'VeterinarianController.php',
    'AppointmentController.php'
];

// Get all controllers
$controllers_dir = 'src/api/controllers';
$all_controllers = array_diff(scandir($controllers_dir), array('.', '..'));

// Controllers to remove
$controllers_to_remove = array_diff($all_controllers, $essential_controllers);

echo "<h3>Controllers to Keep:</h3>\n";
foreach ($essential_controllers as $controller) {
    if (in_array($controller, $all_controllers)) {
        echo "✅ $controller\n";
    } else {
        echo "❌ $controller (missing)\n";
    }
}

echo "\n<h3>Controllers to Remove:</h3>\n";
foreach ($controllers_to_remove as $controller) {
    echo "- $controller\n";
}

echo "\n<h3>🗑️ Removing Unnecessary Controllers:</h3>\n";

$removed_count = 0;
foreach ($controllers_to_remove as $controller) {
    $file_path = $controllers_dir . '/' . $controller;
    
    try {
        if (file_exists($file_path)) {
            unlink($file_path);
            echo "✅ Removed: $controller\n";
            $removed_count++;
        }
    } catch (Exception $e) {
        echo "❌ Error removing $controller: " . $e->getMessage() . "\n";
    }
}

echo "\n<h3>📋 Cleaning Up Root Directory:</h3>\n";

// Remove unnecessary files from root
$root_files_to_remove = [
    'ADMIN_IMPLEMENTATION_COMPLETE.md',
    'ADMIN_PANEL_GUIDE.md', 
    'ADMIN_SESSION_FIX.md',
    'CHATBOT_SETUP_GUIDE.md',
    'check_admin.php',
    'create_vets_table.php',
    'FINAL_IMPLEMENTATION_REPORT.md',
    'IMPLEMENTATION_COMPLETE.md',
    'IMPLEMENTATION_FINAL_REPORT.md',
    'IMPLEMENTATION_SUCCESS_FINAL.md',
    'script.js',
    'SETUP_GUIDE.md',
    'SUBSCRIPTION_SETUP_GUIDE.md',
    'UI_ENHANCEMENT_SUMMARY.md'
];

foreach ($root_files_to_remove as $file) {
    if (file_exists($file)) {
        try {
            unlink($file);
            echo "✅ Removed: $file\n";
            $removed_count++;
        } catch (Exception $e) {
            echo "❌ Error removing $file: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n<h3>✅ API Cleanup Complete!</h3>\n";
echo "<p>Removed $removed_count unnecessary files.</p>\n";
echo "<p>The application now has a clean, minimal structure.</p>\n";
?>
