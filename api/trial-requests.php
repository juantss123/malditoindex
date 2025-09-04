<?php
// API simplificada para solicitudes de prueba gratuita
session_start();

// Headers básicos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
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
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}

function handleGetRequests($db) {
    // Verificar acceso de administrador
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado - requiere rol de administrador']);
        return;
    }
    
    try {
        // Verificar si la tabla existe
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Crear tabla si no existe
            $createSQL = "
                CREATE TABLE trial_requests (
                    id VARCHAR(36) PRIMARY KEY,
                    user_id VARCHAR(36) NOT NULL,
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    admin_notes TEXT,
                    processed_by VARCHAR(36),
                    processed_at TIMESTAMP NULL,
                    trial_website VARCHAR(500),
                    trial_username VARCHAR(100),
                    trial_password VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $db->exec($createSQL);
            
            // Insertar datos de ejemplo
            $sampleData = [
                [
                    'req-001',
                    'juan-user-1234-5678-9012-12345678901',
                    'pending'
                ],
                [
                    'req-002', 
                    'fernando-user-1234-5678-9012-1234567',
                    'approved'
                ]
            ];
            
            foreach ($sampleData as $data) {
                $stmt = $db->prepare("INSERT INTO trial_requests (id, user_id, status) VALUES (?, ?, ?)");
                $stmt->execute($data);
            }
        }
        
        // Obtener solicitudes con información del usuario
        $sql = "
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
                up.phone
            FROM trial_requests tr
            INNER JOIN user_profiles up ON tr.user_id = up.user_id
            ORDER BY tr.request_date DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'requests' => $requests
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al obtener solicitudes: ' . $e->getMessage());
    }
}

function handleCreateRequest($db) {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }
    
    try {
        $userId = $_SESSION['user_id'];
        
        // Verificar si ya tiene una solicitud pendiente
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Ya tienes una solicitud pendiente']);
            return;
        }
        
        // Crear nueva solicitud
        $requestId = 'req-' . uniqid();
        $stmt = $db->prepare("INSERT INTO trial_requests (id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$requestId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => '¡Solicitud enviada! Un administrador la revisará pronto.'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al crear solicitud: ' . $e->getMessage());
    }
}

function handleUpdateRequest($db) {
    // Verificar acceso de administrador
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $requestId = $_GET['id'] ?? '';
    
    if (empty($requestId)) {
        echo json_encode(['error' => 'ID de solicitud requerido']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    
    try {
        $adminId = $_SESSION['user_id'];
        
        // Actualizar solicitud
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud procesada exitosamente'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al procesar solicitud: ' . $e->getMessage());
    }
}
?>