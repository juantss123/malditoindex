<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequests($db);
            break;
        case 'POST':
            handleCreateRequest($db);
            break;
        case 'PUT':
            handleUpdateRequest($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    error_log("Trial requests API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function handleGetRequests($db) {
    // Require admin access for viewing requests
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado - no es administrador']);
        return;
    }
    
    try {
        // Verificar si la tabla trial_requests existe
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Crear la tabla trial_requests
            $createTableSQL = "
                CREATE TABLE trial_requests (
                    id VARCHAR(36) NOT NULL,
                    user_id VARCHAR(36) NOT NULL,
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    admin_notes TEXT DEFAULT NULL,
                    processed_by VARCHAR(36) DEFAULT NULL,
                    processed_at TIMESTAMP NULL DEFAULT NULL,
                    trial_website VARCHAR(500) DEFAULT NULL,
                    trial_username VARCHAR(100) DEFAULT NULL,
                    trial_password VARCHAR(100) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_trial_requests_user_id (user_id),
                    INDEX idx_trial_requests_status (status),
                    INDEX idx_trial_requests_date (request_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->exec($createTableSQL);
            
            // Agregar solicitudes de ejemplo
            $sampleRequests = [
                [
                    'id' => 'req-juan-001',
                    'user_id' => 'juan-user-1234-5678-9012-12345678901',
                    'status' => 'pending'
                ],
                [
                    'id' => 'req-fernando-001',
                    'user_id' => 'fernando-user-1234-5678-9012-1234567',
                    'status' => 'approved',
                    'trial_website' => 'https://demo.dentexapro.com/fernando',
                    'trial_username' => 'fernando_demo',
                    'trial_password' => 'demo123',
                    'admin_notes' => 'Prueba aprobada para Dr. Fernando García',
                    'processed_by' => 'admin-user-1234-5678-9012-1234567890'
                ]
            ];
            
            foreach ($sampleRequests as $request) {
                $stmt = $db->prepare("
                    INSERT INTO trial_requests (id, user_id, status, request_date, trial_website, trial_username, trial_password, admin_notes, processed_by, processed_at) 
                    VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $request['id'], 
                    $request['user_id'], 
                    $request['status'],
                    $request['trial_website'] ?? null,
                    $request['trial_username'] ?? null,
                    $request['trial_password'] ?? null,
                    $request['admin_notes'] ?? null,
                    $request['processed_by'] ?? null
                ]);
            }
        }
        
        // Cargar las solicitudes con información del usuario usando JOIN directo
        $stmt = $db->prepare("
            SELECT 
                tr.id,
                tr.user_id,
                tr.request_date,
                tr.status,
                tr.admin_notes,
                tr.processed_at,
                tr.trial_website,
                tr.trial_username,
                tr.trial_password,
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
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'requests' => $requests]);
        
    } catch (Exception $e) {
        error_log("Error in handleGetRequests: " . $e->getMessage());
        throw new Exception("Error al cargar solicitudes: " . $e->getMessage());
    }
}

function handleCreateRequest($db) {
    // Require user to be logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Debes iniciar sesión']);
        return;
    }
    
    try {
        $userId = $_SESSION['user_id'];
        
        // Verificar si la tabla existe, si no, crearla
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Crear tabla si no existe
            $createTableSQL = "
                CREATE TABLE trial_requests (
                    id VARCHAR(36) NOT NULL,
                    user_id VARCHAR(36) NOT NULL,
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    admin_notes TEXT DEFAULT NULL,
                    processed_by VARCHAR(36) DEFAULT NULL,
                    processed_at TIMESTAMP NULL DEFAULT NULL,
                    trial_website VARCHAR(500) DEFAULT NULL,
                    trial_username VARCHAR(100) DEFAULT NULL,
                    trial_password VARCHAR(100) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->exec($createTableSQL);
        }
        
        // Check if user already has a pending request
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya tienes una solicitud pendiente']);
            return;
        }
        
        // Create new trial request
        $requestId = 'req-' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO trial_requests (id, user_id, request_date, status) 
            VALUES (?, ?, NOW(), 'pending')
        ");
        $stmt->execute([$requestId, $userId]);
        
        echo json_encode([
            'success' => true, 
            'message' => '¡Solicitud enviada! Un administrador la revisará pronto.',
            'request_id' => $requestId
        ]);
        
    } catch (Exception $e) {
        error_log("Error in handleCreateRequest: " . $e->getMessage());
        throw new Exception("Error al crear solicitud: " . $e->getMessage());
    }
}

function handleUpdateRequest($db) {
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
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos de solicitud requeridos']);
        return;
    }
    
    try {
        $adminId = $_SESSION['user_id'];
        
        // Actualizar la solicitud con todos los datos
        $stmt = $db->prepare("
            UPDATE trial_requests 
            SET status = ?, 
                admin_notes = ?, 
                processed_by = ?, 
                processed_at = NOW(),
                trial_website = ?, 
                trial_username = ?, 
                trial_password = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['status'],
            $input['admin_notes'] ?? '',
            $adminId,
            $input['trial_website'] ?? null,
            $input['trial_username'] ?? null,
            $input['trial_password'] ?? null,
            $requestId
        ]);
        
        // Si se aprueba, actualizar el estado del usuario
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
        
        echo json_encode(['success' => true, 'message' => 'Solicitud procesada exitosamente']);
        
    } catch (Exception $e) {
        error_log("Error in handleUpdateRequest: " . $e->getMessage());
        throw new Exception("Error al actualizar solicitud: " . $e->getMessage());
    }
}
?>