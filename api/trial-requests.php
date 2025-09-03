<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequests();
        break;
    case 'POST':
        handleCreateRequest();
        break;
    case 'PUT':
        handleUpdateRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetRequests() {
    global $db;
    
    // Require admin access for viewing requests
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    try {
        // Primero verificar si la tabla existe
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Crear la tabla si no existe
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS trial_requests (
                    id VARCHAR(36) NOT NULL DEFAULT (UUID()),
                    user_id VARCHAR(36) NOT NULL,
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    admin_notes TEXT DEFAULT NULL,
                    processed_by VARCHAR(36) DEFAULT NULL,
                    processed_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_trial_requests_user_id (user_id),
                    INDEX idx_trial_requests_status (status),
                    INDEX idx_trial_requests_date (request_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->exec($createTableSQL);
            
            // Agregar algunas solicitudes de ejemplo
            $sampleRequests = [
                [
                    'id' => 'req-' . uniqid(),
                    'user_id' => 'juan-user-1234-5678-9012-12345678901',
                    'status' => 'pending'
                ],
                [
                    'id' => 'req-' . uniqid(),
                    'user_id' => 'fernando-user-1234-5678-9012-1234567',
                    'status' => 'approved'
                ]
            ];
            
            foreach ($sampleRequests as $request) {
                $stmt = $db->prepare("
                    INSERT IGNORE INTO trial_requests (id, user_id, status, request_date) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$request['id'], $request['user_id'], $request['status']]);
            }
        }
        
        // Ahora cargar las solicitudes
        $stmt = $db->prepare("
            SELECT 
                tr.id,
                tr.user_id,
                tr.request_date,
                tr.status,
                tr.admin_notes,
                tr.processed_at,
                CONCAT(up.first_name, ' ', up.last_name) as user_name,
                up.email,
                up.clinic_name,
                up.phone,
                up.subscription_status,
                CONCAT(admin.first_name, ' ', admin.last_name) as processed_by_name
            FROM trial_requests tr
            JOIN user_profiles up ON tr.user_id = up.user_id
            LEFT JOIN user_profiles admin ON tr.processed_by = admin.user_id
            ORDER BY tr.request_date DESC
        ");
        $stmt->execute();
        $requests = $stmt->fetchAll();
        
        echo json_encode(['requests' => $requests]);
        
    } catch (Exception $e) {
        error_log("Error in trial requests: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Error al cargar solicitudes: ' . $e->getMessage(),
            'requests' => []
        ]);
    }
}

function handleCreateRequest() {
    global $db;
    
    // Require user to be logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Debes iniciar sesión']);
        return;
    }
    
    try {
        $userId = $_SESSION['user_id'];
        
        // Check if user already has a pending request
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya tienes una solicitud pendiente']);
            return;
        }
        
        // Create new trial request
        $stmt = $db->prepare("
            INSERT INTO trial_requests (id, user_id, request_date, status) 
            VALUES (?, ?, NOW(), 'pending')
        ");
        
        $requestId = generateUUID();
        $stmt->execute([$requestId, $userId]);
        
        echo json_encode([
            'success' => true, 
            'message' => '¡Solicitud enviada! Un administrador la revisará pronto.',
            'request_id' => $requestId
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear solicitud: ' . $e->getMessage()]);
    }
}

function handleUpdateRequest() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $requestId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($requestId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de solicitud requerido']);
        return;
    }
    
    try {
        $adminId = $_SESSION['user_id'];
        
        $stmt = $db->prepare("
            UPDATE trial_requests 
            SET status = ?, admin_notes = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['status'],
            $input['admin_notes'] ?? '',
            $adminId,
            $requestId
        ]);
        
        // If approved, update user's trial status
        if ($input['status'] === 'approved') {
            $stmt = $db->prepare("
                UPDATE user_profiles 
                SET subscription_status = 'trial', 
                    trial_start_date = NOW(), 
                    trial_end_date = DATE_ADD(NOW(), INTERVAL 15 DAY)
                WHERE user_id = (SELECT user_id FROM trial_requests WHERE id = ?)
            ");
            $stmt->execute([$requestId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Solicitud actualizada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar solicitud: ' . $e->getMessage()]);
    }
}
?>