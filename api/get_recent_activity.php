<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Require admin access
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get recent user registrations and updates
    $stmt = $db->prepare("
        SELECT 
            CONCAT(first_name, ' ', last_name) as user_name,
            email,
            subscription_plan as plan_type,
            subscription_status as status,
            created_at,
            'Registro' as action
        FROM user_profiles 
        WHERE role = 'user'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener actividad: ' . $e->getMessage()
    ]);
}
?>