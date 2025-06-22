<?php
echo "<h2>🗂️ PawConnect File Cleanup</h2>\n";
echo "<p>Removing unnecessary files and keeping only essential pages...</p>\n";

// Essential files to KEEP
$essential_files = [
    // Core pages
    'index.html',
    'home.html',
    'login.html', 
    'register.html',
    
    // Main features
    'pet_adoption_feed.html',
    'shop_feed.html',
    'pet_community.html',
    'pet_corner.html',
    'vet_appointment.html',
    'chat.html',
    
    // Admin (simplified)
    'admin_dashboard.html',
    
    // Support
    'customer_support.html',
    
    // Testing (temporary)
    'notification_test.html',
    
    // Directories (keep)
    'assets',
    'css',
    'js'
];

// Get all files in public directory
$public_dir = 'public';
$all_files = array_diff(scandir($public_dir), array('.', '..'));

// Files to remove
$files_to_remove = array_diff($all_files, $essential_files);

echo "<h3>Files to Keep:</h3>\n";
foreach ($essential_files as $file) {
    if (in_array($file, $all_files)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file (missing)\n";
    }
}

echo "\n<h3>Files to Remove:</h3>\n";
foreach ($files_to_remove as $file) {
    echo "- $file\n";
}

echo "\n<h3>🗑️ Removing Unnecessary Files:</h3>\n";

$removed_count = 0;
foreach ($files_to_remove as $file) {
    $file_path = $public_dir . '/' . $file;
    
    // Skip directories we want to keep
    if (is_dir($file_path) && in_array($file, ['assets', 'css', 'js'])) {
        continue;
    }
    
    try {
        if (is_file($file_path)) {
            unlink($file_path);
            echo "✅ Removed: $file\n";
            $removed_count++;
        } elseif (is_dir($file_path)) {
            // Remove directory recursively (if it's not essential)
            removeDirectory($file_path);
            echo "✅ Removed directory: $file\n";
            $removed_count++;
        }
    } catch (Exception $e) {
        echo "❌ Error removing $file: " . $e->getMessage() . "\n";
    }
}

echo "\n<h3>📂 Cleaning Up Source Files:</h3>\n";

// Clean up src directory - remove unnecessary files
$src_files_to_remove = [
    'src/fix_database.php',
    'src/community_login.php',
    'src/community_register.php'
];

foreach ($src_files_to_remove as $file) {
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

echo "\n<h3>✅ File Cleanup Complete!</h3>\n";
echo "<p>Removed $removed_count unnecessary files.</p>\n";
echo "<p>The application now contains only essential files for core functionality.</p>\n";

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    return rmdir($dir);
}
?>
