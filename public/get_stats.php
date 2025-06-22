<?php
// Get System Statistics
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $usersResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers = $usersResult['total'] ?? 0;
    
    // Get successful adoptions count (adoption posts with status 'adopted')
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM adoption_posts WHERE status = 'adopted'");
    $adoptionsResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $successfulAdoptions = $adoptionsResult['total'] ?? 0;
    
    // Get total pets count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pets");
    $petsResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPets = $petsResult['total'] ?? 0;
      // Get available adoptions count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM adoption_posts WHERE status = 'available'");
    $availableResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $availableAdoptions = $availableResult['total'] ?? 0;
    
    // Get adoption requests count (if table exists)
    $totalRequests = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM adoption_requests");
        $requestsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalRequests = $requestsResult['total'] ?? 0;
    } catch (Exception $e) {
        // Table doesn't exist yet, return 0
        $totalRequests = 0;
    }    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'total_adoptions' => $availableAdoptions,
            'total_pets' => $totalPets,
            'total_requests' => $totalRequests
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
