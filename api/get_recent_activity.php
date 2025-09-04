<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // Obtener actividad reciente (últimos 10 registros)
    $stmt = $pdo->query("
        SELECT 
            u.name as user_name,
            u.email,
            s.plan_type,
            s.status,
            s.created_at,
            'Suscripción' as action
        FROM subscriptions s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener actividad: ' . $e->getMessage()
    ]);
}
?>