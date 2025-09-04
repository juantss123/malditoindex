<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get-counts':
        handleGetCounts();
        break;
    case 'mark-viewed':
        handleMarkViewed();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción inválida']);
}

function handleGetCounts() {
    global $db;
    
    try {
        // Create table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS transfer_proofs (
                id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_type VARCHAR(50) NOT NULL,
                file_size INT NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_notes TEXT DEFAULT NULL,
                processed_by VARCHAR(36) DEFAULT NULL,
                processed_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Get pending transfer proofs count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM transfer_proofs WHERE status = 'pending'");
        $stmt->execute();
        $pendingTransferProofs = $stmt->fetch()['count'];
        
        // Get pending trial requests count
        $pendingTrialRequests = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM trial_requests WHERE status = 'pending'");
            $stmt->execute();
            $pendingTrialRequests = $stmt->fetch()['count'];
        } catch (Exception $e) {
            // Table might not exist, ignore
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => [
                'transfer_proofs' => $pendingTransferProofs,
                'trial_requests' => $pendingTrialRequests
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener notificaciones: ' . $e->getMessage()]);
    }
}

function handleMarkViewed() {
    global $db;
    
    $type = $_GET['type'] ?? '';
    
    if ($type === 'transfer_proofs') {
        // Mark as viewed by updating a session variable
        $_SESSION['transfer_proofs_last_viewed'] = time();
        echo json_encode(['success' => true, 'message' => 'Comprobantes marcados como vistos']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de notificación inválido']);
    }
}
?>