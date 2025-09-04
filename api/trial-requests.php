<?php
// API para solicitudes de prueba gratuita - Versión robusta
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

// Función para enviar respuesta JSON y terminar
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Verificar que el archivo de configuración existe
if (!file_exists('../config/database.php')) {
    sendResponse(['error' => 'Archivo de configuración no encontrado'], 500);
}

require_once '../config/database.php';

try {
    // Verificar conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendResponse(['error' => 'No se pudo conectar a la base de datos'], 500);
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
            sendResponse(['error' => 'Método no permitido'], 405);
    }
    
} catch (Exception $e) {
    sendResponse([
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], 500);
}

function handleGetRequests($db) {
    try {
        // Verificar acceso de administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            sendResponse(['error' => 'Acceso denegado - requiere rol de administrador'], 403);
        }
        
        // Verificar si la tabla trial_requests existe
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Crear tabla trial_requests
            $createTableSQL = "
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($createTableSQL);
            
            // Insertar datos de ejemplo
            $sampleRequests = [
                [
                    'id' => 'req-sample-001',
                    'user_id' => 'juan-user-1234-5678-9012-12345678901',
                    'status' => 'pending'
                ],
                [
                    'id' => 'req-sample-002',
                    'user_id' => 'fernando-user-1234-5678-9012-1234567',
                    'status' => 'approved',
                    'trial_website' => 'https://demo.dentexapro.com/fernando',
                    'trial_username' => 'fernando_demo',
                    'trial_password' => 'demo123',
                    'admin_notes' => 'Solicitud aprobada - cliente verificado'
                ]
            ];
            
            foreach ($sampleRequests as $request) {
                $stmt = $db->prepare("
                    INSERT INTO trial_requests (id, user_id, status, trial_website, trial_username, trial_password, admin_notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $request['id'],
                    $request['user_id'],
                    $request['status'],
                    $request['trial_website'] ?? null,
                    $request['trial_username'] ?? null,
                    $request['trial_password'] ?? null,
                    $request['admin_notes'] ?? null
                ]);
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
        
        sendResponse([
            'success' => true,
            'requests' => $requests
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'error' => 'Error al obtener solicitudes',
            'details' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ], 500);
    }
}

function handleCreateRequest($db) {
    try {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['error' => 'Usuario no autenticado'], 401);
        }
        
        $userId = $_SESSION['user_id'];
        
        // Verificar si ya tiene una solicitud pendiente
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Ya tienes una solicitud pendiente']);
        }
        
        // Crear nueva solicitud
        $requestId = 'req-' . uniqid() . '-' . time();
        $stmt = $db->prepare("INSERT INTO trial_requests (id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$requestId, $userId]);
        
        sendResponse([
            'success' => true,
            'message' => '¡Solicitud enviada! Un administrador la revisará pronto.'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'error' => 'Error al crear solicitud',
            'details' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ], 500);
    }
}

function handleUpdateRequest($db) {
    try {
        // Verificar acceso de administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            sendResponse(['error' => 'Acceso denegado'], 403);
        }
        
        $requestId = $_GET['id'] ?? '';
        
        if (empty($requestId)) {
            sendResponse(['error' => 'ID de solicitud requerido'], 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendResponse(['error' => 'Datos JSON inválidos'], 400);
        }
        
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
        
        $result = $stmt->execute([
            $input['status'],
            $input['admin_notes'] ?? '',
            $adminId,
            $input['trial_website'] ?? null,
            $input['trial_username'] ?? null,
            $input['trial_password'] ?? null,
            $requestId
        ]);
        
        if (!$result) {
            sendResponse(['error' => 'Error al actualizar la solicitud'], 500);
        }
        
        sendResponse([
            'success' => true,
            'message' => 'Solicitud procesada exitosamente'
        ]);
        
    } catch (Exception $e) {
        sendResponse([
            'error' => 'Error al procesar solicitud',
            'details' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ], 500);
    }
}
?>